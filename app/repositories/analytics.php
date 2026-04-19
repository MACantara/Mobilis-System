<?php
declare(strict_types=1);

if (!function_exists('getAnalyticsSummary')) {
    function getAnalyticsSummary(): array
    {
        try {
            $metrics = getDashboardMetrics();
            $vehicles = getVehicles(100);
            $bookings = getBookings(100);
            
            // Fleet health stats
            $totalFleet = (int) ($metrics['total_fleet'] ?? 0);
            $activeRentals = (int) ($metrics['active_rentals'] ?? 0);
            $utilizationRate = (int) ($metrics['utilization_rate'] ?? 0);
            
            // Status breakdown
            $statusBreakdown = [];
            foreach ($vehicles as $vehicle) {
                $status = $vehicle['status'] ?? 'unknown';
                $statusBreakdown[$status] = ($statusBreakdown[$status] ?? 0) + 1;
            }
            
            // Booking behavior
            $days = [];
            foreach ($bookings as $booking) {
                $dayDiff = (int) ($booking['days'] ?? 0);
                if ($dayDiff > 0) {
                    $days[] = $dayDiff;
                }
            }
            $avgDays = count($days) > 0 ? round(array_sum($days) / count($days), 2) : 0;
            
            // Top vehicle demand
            $vehicleDemand = [];
            foreach ($bookings as $booking) {
                $vehicleName = $booking['vehicle'] ?? 'Unknown';
                $vehicleDemand[$vehicleName] = ($vehicleDemand[$vehicleName] ?? 0) + 1;
            }
            arsort($vehicleDemand);
            $topVehicleDemand = array_slice(array_map(fn($v, $c) => ['vehicle' => $v, 'count' => $c], array_keys($vehicleDemand), array_values($vehicleDemand)), 0, 3, true);
            
            // Maintenance alerts
            $maintenanceAlerts = getOverdueMaintenanceAlerts();
            
            // Generate recommendations
            $recommendations = [];
            if ($utilizationRate >= 75) {
                $recommendations[] = 'Utilization is high; consider adding fleet capacity for top-demand categories.';
            } elseif ($utilizationRate <= 45) {
                $recommendations[] = 'Utilization is low; run promos on idle vehicles and tighten acquisition spending.';
            } else {
                $recommendations[] = 'Utilization is healthy; prioritize reliability and on-time returns.';
            }
            
            if (!empty($maintenanceAlerts)) {
                $recommendations[] = 'Maintenance backlog detected; schedule service windows for high-mileage units this week.';
            }
            
            if ($avgDays > 4) {
                $recommendations[] = 'Average rental duration is long; prepare long-term rental bundles and retention offers.';
            }
            
            return [
                'fleet_health' => [
                    'total_fleet' => $totalFleet,
                    'active_rentals' => $activeRentals,
                    'utilization_rate' => $utilizationRate,
                    'status_breakdown' => $statusBreakdown,
                ],
                'booking_behavior' => [
                    'observed_bookings' => count($bookings),
                    'average_rental_days' => $avgDays,
                    'top_vehicle_demand' => array_values($topVehicleDemand),
                ],
                'financial_snapshot' => [
                    'revenue_today' => (float) ($metrics['revenue_today'] ?? 0),
                ],
                'maintenance_alerts' => $maintenanceAlerts,
                'recommendations' => $recommendations,
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'fleet_health' => ['total_fleet' => 0, 'active_rentals' => 0, 'utilization_rate' => 0, 'status_breakdown' => []],
                'booking_behavior' => ['observed_bookings' => 0, 'average_rental_days' => 0, 'top_vehicle_demand' => []],
                'financial_snapshot' => ['revenue_today' => 0],
                'maintenance_alerts' => [],
                'recommendations' => [],
                'generated_at' => date('Y-m-d H:i:s'),
            ];
        }
    }
}

if (!function_exists('getOverdueMaintenanceAlerts')) {
    function getOverdueMaintenanceAlerts(): array
    {
        try {
            $sql = "SELECT v.name as vehicle, v.mileage_km, m.last_service, m.service_type 
                    FROM Vehicle v 
                    LEFT JOIN Maintenance m ON v.vehicle_id = m.vehicle_id 
                    ORDER BY v.vehicle_id DESC";
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            $alerts = [];
            foreach ($rows as $row) {
                $mileage = (int) ($row['mileage_km'] ?? 0);
                $lastService = $row['last_service'] ?? null;
                $daysSinceService = 0;
                
                if ($lastService) {
                    $lastServiceDate = new DateTime($lastService);
                    $today = new DateTime();
                    $daysSinceService = $today->diff($lastServiceDate)->days;
                }
                
                if ($mileage >= 90000 || $daysSinceService > 120) {
                    $alerts[] = [
                        'vehicle' => $row['vehicle'] ?? 'Unknown',
                        'mileage_km' => $mileage,
                        'days_since_service' => $daysSinceService,
                        'recent_work' => $row['service_type'] ?? 'N/A',
                    ];
                }
            }
            
            return $alerts;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getRevenueByPeriod')) {
    function getRevenueByPeriod(string $period = 'month'): array
    {
        try {
            $sql = "SELECT DATE(i.issued_at) as date, SUM(i.total_amount) as total 
                    FROM Invoice i 
                    WHERE i.payment_status = 'paid'";
            
            if ($period === 'week') {
                $sql .= " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($period === 'month') {
                $sql .= " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            } elseif ($period === 'year') {
                $sql .= " AND i.issued_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            }
            
            $sql .= " GROUP BY DATE(i.issued_at) ORDER BY date DESC";
            
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'date' => $row['date'],
                'total' => (float) ($row['total'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getBookingTrends')) {
    function getBookingTrends(string $period = 'month'): array
    {
        try {
            $sql = "SELECT DATE(r.start_date) as date, COUNT(*) as count, SUM(r.total_amount) as revenue 
                    FROM Rental r 
                    WHERE r.status != 'cancelled'";
            
            if ($period === 'week') {
                $sql .= " AND r.start_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($period === 'month') {
                $sql .= " AND r.start_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            } elseif ($period === 'year') {
                $sql .= " AND r.start_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)";
            }
            
            $sql .= " GROUP BY DATE(r.start_date) ORDER BY date DESC";
            
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'date' => $row['date'],
                'count' => (int) ($row['count'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }
}

if (!function_exists('getTopCustomersByRevenue')) {
    function getTopCustomersByRevenue(int $limit = 10): array
    {
        try {
            $sql = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name, u.email, COUNT(r.rental_id) as bookings, SUM(i.total_amount) as total_revenue
                    FROM User u
                    JOIN Rental r ON r.user_id = u.user_id
                    LEFT JOIN Invoice i ON i.rental_id = r.rental_id
                    WHERE u.role = 'customer' AND r.status != 'cancelled'
                    GROUP BY u.user_id, u.first_name, u.last_name, u.email
                    ORDER BY total_revenue DESC
                    LIMIT ?";
            $stmt = db()->prepare($sql);
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();

            return array_map(fn($row) => [
                'name' => $row['name'] ?? 'Unknown',
                'email' => $row['email'] ?? '',
                'bookings' => (int) ($row['bookings'] ?? 0),
                'total_revenue' => (float) ($row['total_revenue'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getVehiclePerformance')) {
    function getVehiclePerformance(): array
    {
        try {
            $sql = "SELECT v.name, v.type, COUNT(r.rental_id) as rentals, SUM(r.total_amount) as revenue
                    FROM Vehicle v
                    LEFT JOIN Rental r ON v.vehicle_id = r.vehicle_id AND r.status != 'cancelled'
                    GROUP BY v.vehicle_id
                    ORDER BY revenue DESC";
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'name' => $row['name'] ?? 'Unknown',
                'type' => $row['type'] ?? 'Unknown',
                'rentals' => (int) ($row['rentals'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getPaymentStatusBreakdown')) {
    function getPaymentStatusBreakdown(): array
    {
        try {
            $sql = "SELECT payment_status, COUNT(*) as count, SUM(total_amount) as total
                    FROM Invoice
                    GROUP BY payment_status";
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'status' => $row['payment_status'] ?? 'unknown',
                'count' => (int) ($row['count'] ?? 0),
                'total' => (float) ($row['total'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getBookingStatusBreakdown')) {
    function getBookingStatusBreakdown(): array
    {
        try {
            $sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as total
                    FROM Rental
                    GROUP BY status";
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'status' => $row['status'] ?? 'unknown',
                'count' => (int) ($row['count'] ?? 0),
                'total' => (float) ($row['total'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getAverageRevenuePerBooking')) {
    function getAverageRevenuePerBooking(): float
    {
        try {
            $sql = "SELECT AVG(total_amount) as avg_revenue
                    FROM Rental
                    WHERE status != 'cancelled'";
            $stmt = db()->query($sql);
            $row = $stmt->fetch();
            
            return (float) ($row['avg_revenue'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('getRevenueByVehicleType')) {
    function getRevenueByVehicleType(): array
    {
        try {
            $sql = "SELECT v.type, SUM(r.total_amount) as total_revenue, COUNT(r.rental_id) as rentals
                    FROM Vehicle v
                    LEFT JOIN Rental r ON v.vehicle_id = r.vehicle_id AND r.status != 'cancelled'
                    GROUP BY v.type
                    ORDER BY total_revenue DESC";
            $stmt = db()->query($sql);
            $rows = $stmt->fetchAll();
            
            return array_map(fn($row) => [
                'type' => $row['type'] ?? 'Unknown',
                'total_revenue' => (float) ($row['total_revenue'] ?? 0),
                'rentals' => (int) ($row['rentals'] ?? 0),
            ], $rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}
