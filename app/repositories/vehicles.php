<?php
declare(strict_types=1);

if (!function_exists('getVehicles')) {
    function getVehicles(int $limit = 12): array
    {
        if (!dbConnected()) {
            return [
                ['vehicle_id' => 1, 'category_id' => 2, 'name' => 'Toyota Fortuner', 'plate' => 'ABC-1234', 'category_name' => 'SUV', 'year' => 2022, 'color' => 'Blue', 'mileage_km' => 38200, 'status' => 'rented', 'daily_rate' => 3500],
                ['vehicle_id' => 2, 'category_id' => 1, 'name' => 'Honda Civic', 'plate' => 'XYZ-5678', 'category_name' => 'Sedan', 'year' => 2023, 'color' => 'Gray', 'mileage_km' => 12500, 'status' => 'available', 'daily_rate' => 2200],
            ];
        }

        try {
            $sql = "
                SELECT
                    v.vehicle_id,
                    v.category_id,
                    CONCAT(v.brand, ' ', v.model) AS name,
                    REPLACE(v.plate_number, ' ', '-') AS plate,
                    vc.category_name,
                    v.year,
                    v.color,
                    v.mileage_km,
                    v.status,
                    vc.daily_rate
                FROM Vehicle v
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                ORDER BY v.vehicle_id DESC
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

if (!function_exists('getVehicleCategories')) {
    function getVehicleCategories(): array
    {
        if (!dbConnected()) {
            return [
                ['category_id' => 1, 'category_name' => 'Sedan', 'daily_rate' => 1500],
                ['category_id' => 2, 'category_name' => 'SUV', 'daily_rate' => 2500],
                ['category_id' => 3, 'category_name' => 'Van', 'daily_rate' => 3000],
                ['category_id' => 4, 'category_name' => 'Motorcycle', 'daily_rate' => 600],
                ['category_id' => 5, 'category_name' => 'Pickup Truck', 'daily_rate' => 2000],
            ];
        }

        try {
            $sql = "
                SELECT category_id, category_name, daily_rate
                FROM VehicleCategory
                ORDER BY category_name ASC
            ";
            return db()->query($sql)->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getVehicleById')) {
    function getVehicleById(int $vehicleId): ?array
    {
        if ($vehicleId <= 0) {
            return null;
        }

        foreach (getVehicles(500) as $vehicle) {
            if ((int) ($vehicle['vehicle_id'] ?? 0) === $vehicleId) {
                return $vehicle;
            }
        }

        return null;
    }
}

if (!function_exists('splitVehicleName')) {
    function splitVehicleName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        return [
            'brand' => (string) ($parts[0] ?? ''),
            'model' => (string) ($parts[1] ?? ''),
        ];
    }
}

if (!function_exists('createVehicleRecord')) {
    function createVehicleRecord(array $payload): array
    {
        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        try {
            $nameParts = splitVehicleName((string) ($payload['name'] ?? ''));
            $brand = trim((string) ($payload['brand'] ?? $nameParts['brand']));
            $model = trim((string) ($payload['model'] ?? $nameParts['model']));

            $sql = "
                INSERT INTO Vehicle (
                    category_id,
                    plate_number,
                    brand,
                    model,
                    year,
                    color,
                    mileage_km,
                    status
                ) VALUES (
                    :category_id,
                    :plate_number,
                    :brand,
                    :model,
                    :year,
                    :color,
                    :mileage_km,
                    :status
                )
            ";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                'category_id' => (int) ($payload['category_id'] ?? 0),
                'plate_number' => trim(str_replace('-', ' ', (string) ($payload['plate_number'] ?? ''))),
                'brand' => $brand,
                'model' => $model,
                'year' => (int) ($payload['year'] ?? 0),
                'color' => trim((string) ($payload['color'] ?? '')),
                'mileage_km' => (int) ($payload['mileage_km'] ?? 0),
                'status' => trim((string) ($payload['status'] ?? 'available')),
            ]);

            return ['ok' => true, 'vehicle_id' => (int) db()->lastInsertId()];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not create vehicle. Plate number might already exist.'];
        }
    }
}

if (!function_exists('updateVehicleRecord')) {
    function updateVehicleRecord(int $vehicleId, array $payload): array
    {
        if ($vehicleId <= 0) {
            return ['ok' => false, 'error' => 'Invalid vehicle ID.'];
        }

        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        try {
            $nameParts = splitVehicleName((string) ($payload['name'] ?? ''));
            $brand = trim((string) ($payload['brand'] ?? $nameParts['brand']));
            $model = trim((string) ($payload['model'] ?? $nameParts['model']));

            $sql = "
                UPDATE Vehicle
                SET
                    category_id = :category_id,
                    plate_number = :plate_number,
                    brand = :brand,
                    model = :model,
                    year = :year,
                    color = :color,
                    mileage_km = :mileage_km,
                    status = :status
                WHERE vehicle_id = :vehicle_id
            ";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                'vehicle_id' => $vehicleId,
                'category_id' => (int) ($payload['category_id'] ?? 0),
                'plate_number' => trim(str_replace('-', ' ', (string) ($payload['plate_number'] ?? ''))),
                'brand' => $brand,
                'model' => $model,
                'year' => (int) ($payload['year'] ?? 0),
                'color' => trim((string) ($payload['color'] ?? '')),
                'mileage_km' => (int) ($payload['mileage_km'] ?? 0),
                'status' => trim((string) ($payload['status'] ?? 'available')),
            ]);

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not update vehicle. Plate number might already exist.'];
        }
    }
}

if (!function_exists('getFleetByCategory')) {
    function getFleetByCategory(): array
    {
        if (!dbConnected()) {
            return [
                ['category_name' => 'Pick-ups', 'utilization' => 80],
                ['category_name' => 'Sedans', 'utilization' => 70],
                ['category_name' => 'SUVs', 'utilization' => 55],
                ['category_name' => 'Vans', 'utilization' => 40],
            ];
        }

        try {
            $sql = "
                SELECT
                    vc.category_name,
                    ROUND(SUM(CASE WHEN v.status = 'rented' THEN 1 ELSE 0 END) * 100 / NULLIF(COUNT(v.vehicle_id), 0), 0) AS utilization
                FROM VehicleCategory vc
                LEFT JOIN Vehicle v ON v.category_id = vc.category_id
                GROUP BY vc.category_id, vc.category_name
                ORDER BY utilization DESC
            ";
            return db()->query($sql)->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getMaintenanceBacklog')) {
    function getMaintenanceBacklog(): array
    {
        if (!dbConnected()) {
            return [
                ['vehicle' => 'Mitsubishi Montero', 'mileage_km' => 95000, 'last_service' => '2026-04-01', 'service_type' => 'Engine overhaul'],
                ['vehicle' => 'Toyota HiAce', 'mileage_km' => 130000, 'last_service' => '2026-02-20', 'service_type' => 'Tire rotation'],
            ];
        }

        try {
            $threshold = (int) appConfig()['mileage_alert_threshold'];
            $sql = "
                SELECT
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    v.mileage_km,
                    MAX(m.service_date) AS last_service,
                    SUBSTRING_INDEX(GROUP_CONCAT(m.service_type ORDER BY m.service_date DESC SEPARATOR ','), ',', 1) AS service_type
                FROM Vehicle v
                LEFT JOIN MaintenanceLog m ON m.vehicle_id = v.vehicle_id
                WHERE v.mileage_km >= :threshold OR v.status = 'maintenance'
                GROUP BY v.vehicle_id, v.brand, v.model, v.mileage_km
                ORDER BY v.mileage_km DESC
                LIMIT 8
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['threshold' => $threshold]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getAvailableVehicles')) {
    function getAvailableVehicles(int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                ['vehicle_id' => 2, 'name' => 'Honda Civic', 'plate' => 'XYZ-5678', 'category_name' => 'Sedan', 'daily_rate' => 2200, 'status' => 'available'],
                ['vehicle_id' => 5, 'name' => 'Mitsubishi Xpander', 'plate' => 'JKL-7890', 'category_name' => 'Sedan', 'daily_rate' => 2800, 'status' => 'available'],
            ];
        }

        try {
            $sql = "
                SELECT
                    v.vehicle_id,
                    CONCAT(v.brand, ' ', v.model) AS name,
                    REPLACE(v.plate_number, ' ', '-') AS plate,
                    vc.category_name,
                    vc.daily_rate,
                    v.status
                FROM Vehicle v
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                WHERE v.status = 'available'
                ORDER BY v.vehicle_id DESC
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
