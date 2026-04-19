#!/usr/bin/env python3
"""Lightweight analytics processor for Mobilis prototype.

Reads JSON payload from stdin and returns actionable insights as JSON.
"""

from __future__ import annotations

import json
import statistics
import sys
from collections import Counter
from datetime import datetime


def safe_int(value, default=0):
    try:
        return int(value)
    except (TypeError, ValueError):
        return default


def safe_float(value, default=0.0):
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def parse_date(raw: str):
    try:
        return datetime.strptime(raw, "%Y-%m-%d").date()
    except Exception:
        return None


def main() -> int:
    raw = sys.stdin.read().strip()
    if not raw:
        print(json.dumps({"status": "error", "message": "Missing payload"}))
        return 1

    payload = json.loads(raw)
    metrics = payload.get("metrics", {})
    vehicles = payload.get("vehicles", [])
    bookings = payload.get("bookings", [])
    maintenance = payload.get("maintenance", [])

    total_fleet = safe_int(metrics.get("total_fleet"))
    active_rentals = safe_int(metrics.get("active_rentals"))
    utilization_rate = safe_int(metrics.get("utilization_rate"))

    status_counter = Counter((v.get("status") or "unknown") for v in vehicles)

    days = [safe_int(b.get("days"), 1) for b in bookings if safe_int(b.get("days"), 0) > 0]
    avg_days = round(statistics.mean(days), 2) if days else 0

    overdue_maintenance = []
    for row in maintenance:
        mileage = safe_int(row.get("mileage_km"))
        last_service = parse_date(str(row.get("last_service", "")))
        overdue_days = 0
        if last_service:
            overdue_days = (datetime.today().date() - last_service).days

        if mileage >= 90000 or overdue_days > 120:
            overdue_maintenance.append(
                {
                    "vehicle": row.get("vehicle", "Unknown"),
                    "mileage_km": mileage,
                    "days_since_service": overdue_days,
                    "recent_work": row.get("service_type", "N/A"),
                }
            )

    top_vehicle_demand = Counter(b.get("vehicle", "Unknown") for b in bookings).most_common(3)

    recommendations = []
    if utilization_rate >= 75:
        recommendations.append("Utilization is high; consider adding fleet capacity for top-demand categories.")
    elif utilization_rate <= 45:
        recommendations.append("Utilization is low; run promos on idle vehicles and tighten acquisition spending.")
    else:
        recommendations.append("Utilization is healthy; prioritize reliability and on-time returns.")

    if overdue_maintenance:
        recommendations.append("Maintenance backlog detected; schedule service windows for high-mileage units this week.")

    if avg_days > 4:
        recommendations.append("Average rental duration is long; prepare long-term rental bundles and retention offers.")

    summary = {
        "fleet_health": {
            "total_fleet": total_fleet,
            "active_rentals": active_rentals,
            "utilization_rate": utilization_rate,
            "status_breakdown": dict(status_counter),
        },
        "booking_behavior": {
            "observed_bookings": len(bookings),
            "average_rental_days": avg_days,
            "top_vehicle_demand": [{"vehicle": v, "count": c} for v, c in top_vehicle_demand],
        },
        "financial_snapshot": {
            "revenue_today": safe_float(metrics.get("revenue_today")),
        },
    }

    output = {
        "status": "ok",
        "generated_at": payload.get("generated_at"),
        "summary": summary,
        "maintenance_alerts": overdue_maintenance,
        "recommendations": recommendations,
    }

    print(json.dumps(output, ensure_ascii=True, indent=2))
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"status": "error", "message": str(exc)}))
        raise
