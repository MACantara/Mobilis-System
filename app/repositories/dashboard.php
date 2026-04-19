<?php
declare(strict_types=1);

if (!function_exists('getDashboardMetrics')) {
    function getDashboardMetrics(): array
    {
        if (!dbConnected()) {
            return fallbackMetrics();
        }

        try {
            $pdo = db();
            $totalFleet = (int) $pdo->query('SELECT COUNT(*) FROM Vehicle')->fetchColumn();
            $activeRentals = (int) $pdo->query("SELECT COUNT(*) FROM Rental WHERE status = 'active'")->fetchColumn();
            $revenueTodayStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM Invoice WHERE DATE(issued_at) = :today');
            $revenueTodayStmt->execute(['today' => dbNow()]);
            $revenueToday = (float) $revenueTodayStmt->fetchColumn();
            $utilization = $totalFleet > 0 ? (int) round(($activeRentals / $totalFleet) * 100) : 0;

            return [
                'total_fleet' => $totalFleet,
                'active_rentals' => $activeRentals,
                'revenue_today' => $revenueToday,
                'utilization_rate' => $utilization,
            ];
        } catch (Throwable $e) {
            return fallbackMetrics();
        }
    }
}

if (!function_exists('getVehicleStatusList')) {
    function getVehicleStatusList(int $limit = 6): array
    {
        if (!dbConnected()) {
            return [
                ['icon' => '🚙', 'name' => 'Toyota Fortuner', 'plate' => 'ABC-1234', 'status' => 'rented'],
                ['icon' => '🚗', 'name' => 'Honda Civic', 'plate' => 'XYZ-5678', 'status' => 'available'],
                ['icon' => '🚐', 'name' => 'Toyota HiAce', 'plate' => 'DEF-9012', 'status' => 'maintenance'],
                ['icon' => '🚗', 'name' => 'Mitsubishi Xpander', 'plate' => 'GHI-3456', 'status' => 'reserved'],
                ['icon' => '🛻', 'name' => 'Ford Ranger', 'plate' => 'JKL-7890', 'status' => 'available'],
            ];
        }

        try {
            $sql = "
                SELECT
                    CASE
                        WHEN LOWER(vc.category_name) LIKE '%motorcycle%' THEN '🏍️'
                        WHEN LOWER(vc.category_name) LIKE '%pickup%' THEN '🛻'
                        WHEN LOWER(vc.category_name) LIKE '%van%' THEN '🚐'
                        WHEN LOWER(vc.category_name) LIKE '%suv%' THEN '🚙'
                        ELSE '🚗'
                    END AS icon,
                    CONCAT(v.brand, ' ', v.model) AS name,
                    REPLACE(v.plate_number, ' ', '-') AS plate,
                    v.status
                FROM Vehicle v
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                ORDER BY FIELD(v.status, 'rented', 'available', 'maintenance'), v.vehicle_id ASC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getUpcomingBookings')) {
    function getUpcomingBookings(int $limit = 6): array
    {
        if (!dbConnected()) {
            return [
                ['customer_name' => 'Maria Reyes', 'vehicle_name' => 'Toyota Fortuner', 'pickup_date' => '2026-04-13', 'return_date' => '2026-04-16', 'status' => 'confirmed'],
                ['customer_name' => 'Juan dela Cruz', 'vehicle_name' => 'Honda Civic', 'pickup_date' => '2026-04-14', 'return_date' => '2026-04-14', 'status' => 'pending'],
                ['customer_name' => 'Ana Lim', 'vehicle_name' => 'Toyota HiAce', 'pickup_date' => '2026-04-15', 'return_date' => '2026-04-20', 'status' => 'confirmed'],
            ];
        }

        try {
            $sql = "
                SELECT
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                    CONCAT(v.brand, ' ', v.model) AS vehicle_name,
                    r.pickup_date,
                    r.return_date,
                    CASE
                        WHEN r.status = 'active' THEN 'confirmed'
                        WHEN r.status = 'completed' THEN 'completed'
                        WHEN r.status = 'cancelled' THEN 'cancelled'
                        ELSE 'pending'
                    END AS status
                FROM Rental r
                INNER JOIN User u ON u.user_id = r.user_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                                WHERE r.pickup_date >= :today
                                    AND r.status IN ('pending', 'active')
                ORDER BY r.pickup_date ASC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':today', dbNow());
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}
