#!/usr/bin/env python3
import sys
import json
from decimal import Decimal
from db_client import execute_query

def decimal_to_float(obj):
    """Convert Decimal objects to float for JSON serialization"""
    if isinstance(obj, Decimal):
        return float(obj)
    elif isinstance(obj, dict):
        return {k: decimal_to_float(v) for k, v in obj.items()}
    elif isinstance(obj, list):
        return [decimal_to_float(item) for item in obj]
    elif isinstance(obj, tuple):
        return tuple(decimal_to_float(item) for item in obj)
    return obj

def get_analytics_summary():
    """Get comprehensive analytics summary including fleet health, booking behavior, financial snapshot, and recommendations."""
    try:
        # Get fleet metrics
        fleet_query = """
            SELECT 
                COUNT(*) as total_fleet,
                SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as active_rentals
            FROM Vehicle
        """
        fleet_data = execute_query(fleet_query)
        total_fleet = fleet_data[0]['total_fleet'] if fleet_data else 0
        active_rentals = fleet_data[0]['active_rentals'] if fleet_data else 0
        utilization_rate = int((active_rentals / total_fleet * 100)) if total_fleet > 0 else 0
        
        # Get status breakdown
        status_query = "SELECT status, COUNT(*) as count FROM Vehicle GROUP BY status"
        status_data = execute_query(status_query)
        status_breakdown = {row['status']: row['count'] for row in status_data}
        
        # Get booking behavior - use actual rental data
        bookings_query = """
            SELECT DATEDIFF(COALESCE(return_date, CURDATE()), pickup_date) as days
            FROM Rental
            WHERE status != 'cancelled' AND pickup_date IS NOT NULL
        """
        bookings_data = execute_query(bookings_query)
        days = [row['days'] for row in bookings_data if row['days'] and row['days'] > 0]
        avg_days = round(sum(days) / len(days), 2) if days else 0
        
        # Get top vehicle demand
        demand_query = """
            SELECT CONCAT(v.brand, ' ', v.model) as vehicle, COUNT(*) as count
            FROM Rental r
            JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
            WHERE r.status != 'cancelled'
            GROUP BY v.vehicle_id, v.brand, v.model
            ORDER BY count DESC
            LIMIT 3
        """
        demand_data = execute_query(demand_query)
        top_vehicle_demand = [{'vehicle': row['vehicle'], 'count': row['count']} for row in demand_data]
        
        # Get revenue today
        revenue_query = """
            SELECT COALESCE(SUM(total_amount), 0) as revenue_today
            FROM Invoice
            WHERE DATE(issued_at) = CURDATE() AND payment_status = 'paid'
        """
        revenue_data = execute_query(revenue_query)
        revenue_today = revenue_data[0]['revenue_today'] if revenue_data else 0
        
        # Get maintenance alerts
        maintenance_alerts = get_maintenance_alerts()
        
        # Generate recommendations
        recommendations = []
        if utilization_rate >= 75:
            recommendations.append('Utilization is high; consider adding fleet capacity for top-demand categories.')
        elif utilization_rate <= 45:
            recommendations.append('Utilization is low; run promos on idle vehicles and tighten acquisition spending.')
        else:
            recommendations.append('Utilization is healthy; prioritize reliability and on-time returns.')
        
        if maintenance_alerts:
            recommendations.append('Maintenance backlog detected; schedule service windows for high-mileage units this week.')
        
        if avg_days > 4:
            recommendations.append('Average rental duration is long; prepare long-term rental bundles and retention offers.')
        
        from datetime import datetime
        result = {
            'fleet_health': {
                'total_fleet': total_fleet,
                'active_rentals': active_rentals,
                'utilization_rate': utilization_rate,
                'status_breakdown': status_breakdown
            },
            'booking_behavior': {
                'observed_bookings': len(bookings_data),
                'average_rental_days': avg_days,
                'top_vehicle_demand': top_vehicle_demand
            },
            'financial_snapshot': {
                'revenue_today': float(revenue_today)
            },
            'maintenance_alerts': maintenance_alerts,
            'recommendations': recommendations,
            'generated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        return decimal_to_float(result)
    except Exception as e:
        from datetime import datetime
        return {
            'error': str(e),
            'fleet_health': {'total_fleet': 0, 'active_rentals': 0, 'utilization_rate': 0, 'status_breakdown': {}},
            'booking_behavior': {'observed_bookings': 0, 'average_rental_days': 0, 'top_vehicle_demand': []},
            'financial_snapshot': {'revenue_today': 0},
            'maintenance_alerts': [],
            'recommendations': [],
            'generated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }

def get_maintenance_alerts():
    """Get vehicles needing maintenance based on mileage and service history."""
    try:
        query = """
            SELECT
                CONCAT(v.brand, ' ', v.model) AS vehicle,
                v.mileage_km,
                lm.last_service,
                lm.service_type
            FROM Vehicle v
            LEFT JOIN (
                SELECT
                    latest.vehicle_id,
                    latest.last_service,
                    ml.service_type
                FROM (
                    SELECT vehicle_id, MAX(service_date) AS last_service
                    FROM MaintenanceLog
                    GROUP BY vehicle_id
                ) latest
                LEFT JOIN MaintenanceLog ml
                    ON ml.vehicle_id = latest.vehicle_id
                   AND ml.service_date = latest.last_service
            ) lm ON lm.vehicle_id = v.vehicle_id
            ORDER BY v.vehicle_id DESC
        """
        data = execute_query(query)
        
        alerts = []
        for row in data:
            mileage = row['mileage_km'] or 0
            last_service = row['last_service']
            days_since_service = 0
            
            if last_service:
                from datetime import datetime
                last_service_date = datetime.strptime(str(last_service), '%Y-%m-%d')
                today = datetime.now()
                days_since_service = (today - last_service_date).days
            
            if mileage >= 90000 or days_since_service > 120:
                alerts.append({
                    'vehicle': row['vehicle'] or 'Unknown',
                    'mileage_km': mileage,
                    'days_since_service': days_since_service,
                    'recent_work': row['service_type'] or 'N/A'
                })
        
        return alerts
    except Exception as e:
        return []

def get_revenue_trends(period='month'):
    """Get revenue trends by date for the specified period."""
    try:
        query = """
            SELECT DATE(i.issued_at) as date, SUM(i.total_amount) as total 
            FROM Invoice i 
            WHERE i.payment_status = 'paid'
        """
        
        if period == 'week':
            query += " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        elif period == 'month':
            query += " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        elif period == 'year':
            query += " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)"
        
        query += " GROUP BY DATE(i.issued_at) ORDER BY date DESC"
        
        data = execute_query(query)
        return [{'date': str(row['date']), 'total': float(row['total'] or 0)} for row in data]
    except Exception as e:
        return []

def get_booking_trends(period='month'):
    """Get booking count and revenue trends by date for the specified period."""
    try:
        query = """
            SELECT
                DATE(r.pickup_date) AS date,
                COUNT(*) AS count,
                COALESCE(SUM(i.total_amount), 0) AS revenue
            FROM Rental r
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            WHERE r.status != 'cancelled'
        """
        
        if period == 'week':
            query += " AND r.pickup_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        elif period == 'month':
            query += " AND r.pickup_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        elif period == 'year':
            query += " AND r.pickup_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)"
        
        query += " GROUP BY DATE(r.pickup_date) ORDER BY date DESC"
        
        data = execute_query(query)
        return [{'date': str(row['date']), 'count': int(row['count'] or 0), 'revenue': float(row['revenue'] or 0)} for row in data]
    except Exception as e:
        return []

def get_top_customers(limit=10):
    """Get top customers by revenue."""
    try:
        query = """
            SELECT CONCAT(u.first_name, ' ', u.last_name) as name, u.email, COUNT(r.rental_id) as bookings, SUM(i.total_amount) as total_revenue
            FROM User u
            JOIN Rental r ON r.user_id = u.user_id
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            WHERE u.role = 'customer' AND r.status != 'cancelled'
            GROUP BY u.user_id, u.first_name, u.last_name, u.email
            ORDER BY total_revenue DESC
            LIMIT %s
        """
        data = execute_query(query, (limit,))
        return [{'name': row['name'] or 'Unknown', 'email': row['email'] or '', 'bookings': int(row['bookings'] or 0), 'total_revenue': float(row['total_revenue'] or 0)} for row in data]
    except Exception as e:
        return []

def get_vehicle_performance():
    """Get vehicle performance metrics."""
    try:
        query = """
            SELECT
                CONCAT(v.brand, ' ', v.model) AS name,
                vc.category_name AS type,
                COUNT(r.rental_id) AS rentals,
                COALESCE(SUM(i.total_amount), 0) AS revenue
            FROM Vehicle v
            INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
            LEFT JOIN Rental r ON r.vehicle_id = v.vehicle_id AND r.status != 'cancelled'
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            GROUP BY v.vehicle_id, v.brand, v.model, vc.category_name
            ORDER BY revenue DESC, rentals DESC
        """
        data = execute_query(query)
        return [{'name': row['name'] or 'Unknown', 'type': row['type'] or 'Unknown', 'rentals': int(row['rentals'] or 0), 'revenue': float(row['revenue'] or 0)} for row in data]
    except Exception as e:
        return []

def get_payment_status_breakdown():
    """Get payment status distribution."""
    try:
        query = """
            SELECT payment_status, COUNT(*) as count, SUM(total_amount) as total
            FROM Invoice
            GROUP BY payment_status
        """
        data = execute_query(query)
        return [{'status': row['payment_status'] or 'unknown', 'count': int(row['count'] or 0), 'total': float(row['total'] or 0)} for row in data]
    except Exception as e:
        return []

def get_booking_status_breakdown():
    """Get booking status distribution."""
    try:
        query = """
            SELECT
                r.status,
                COUNT(*) AS count,
                COALESCE(SUM(i.total_amount), 0) AS total
            FROM Rental r
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            GROUP BY r.status
        """
        data = execute_query(query)
        return [{'status': row['status'] or 'unknown', 'count': int(row['count'] or 0), 'total': float(row['total'] or 0)} for row in data]
    except Exception as e:
        return []

def get_average_revenue_per_booking():
    """Get average revenue per booking."""
    try:
        query = """
            SELECT AVG(i.total_amount) AS avg_revenue
            FROM Rental r
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            WHERE r.status != 'cancelled'
        """
        data = execute_query(query)
        return float(data[0]['avg_revenue'] or 0) if data else 0
    except Exception as e:
        return 0

def get_revenue_by_vehicle_type():
    """Get revenue breakdown by vehicle type."""
    try:
        query = """
            SELECT
                vc.category_name AS type,
                COALESCE(SUM(i.total_amount), 0) AS total_revenue,
                COUNT(r.rental_id) AS rentals
            FROM VehicleCategory vc
            LEFT JOIN Vehicle v ON v.category_id = vc.category_id
            LEFT JOIN Rental r ON r.vehicle_id = v.vehicle_id AND r.status != 'cancelled'
            LEFT JOIN Invoice i ON i.rental_id = r.rental_id
            GROUP BY vc.category_id, vc.category_name
            ORDER BY total_revenue DESC
        """
        data = execute_query(query)
        return [{'type': row['type'] or 'Unknown', 'total_revenue': float(row['total_revenue'] or 0), 'rentals': int(row['rentals'] or 0)} for row in data]
    except Exception as e:
        return []

def get_all_analytics():
    """Get all analytics data in a single call for performance optimization."""
    return {
        'summary': get_analytics_summary(),
        'maintenance_alerts': get_maintenance_alerts(),
        'revenue_trends': get_revenue_trends('month'),
        'booking_trends': get_booking_trends('month'),
        'top_customers': get_top_customers(10),
        'vehicle_performance': get_vehicle_performance(),
        'payment_status_breakdown': get_payment_status_breakdown(),
        'booking_status_breakdown': get_booking_status_breakdown(),
        'average_revenue': get_average_revenue_per_booking(),
        'revenue_by_vehicle_type': get_revenue_by_vehicle_type()
    }

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No analytics function specified'}))
        sys.exit(1)
    
    function_name = sys.argv[1]
    args = sys.argv[2:]
    
    functions = {
        'summary': get_analytics_summary,
        'maintenance_alerts': get_maintenance_alerts,
        'revenue_trends': get_revenue_trends,
        'booking_trends': get_booking_trends,
        'top_customers': get_top_customers,
        'vehicle_performance': get_vehicle_performance,
        'payment_status_breakdown': get_payment_status_breakdown,
        'booking_status_breakdown': get_booking_status_breakdown,
        'average_revenue': get_average_revenue_per_booking,
        'revenue_by_vehicle_type': get_revenue_by_vehicle_type,
        'all': get_all_analytics
    }
    
    if function_name not in functions:
        print(json.dumps({'error': f'Unknown function: {function_name}'}))
        sys.exit(1)
    
    try:
        if function_name in ['revenue_trends', 'booking_trends']:
            period = args[0] if args else 'month'
            result = functions[function_name](period)
        elif function_name == 'top_customers':
            limit = int(args[0]) if args else 10
            result = functions[function_name](limit)
        else:
            result = functions[function_name]()
        
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'error': str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()
