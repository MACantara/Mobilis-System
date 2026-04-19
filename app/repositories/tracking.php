<?php
declare(strict_types=1);

if (!function_exists('trackingDefaultCenter')) {
    function trackingDefaultCenter(): array
    {
        return ['lat' => 14.5995, 'lng' => 121.0223];
    }
}

if (!function_exists('trackingSimulatedCoordinate')) {
    function trackingSimulatedCoordinate(array $vehicle, int $tick): array
    {
        $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);

        $base = trackingDefaultCenter();
        $baseLat = isset($vehicle['latitude']) ? (float) $vehicle['latitude'] : null;
        $baseLng = isset($vehicle['longitude']) ? (float) $vehicle['longitude'] : null;

        if ($baseLat === null || $baseLng === null || $baseLat == 0.0 || $baseLng == 0.0) {
            $baseLat = $base['lat'] + (($vehicleId % 9) - 4) * 0.01;
            $baseLng = $base['lng'] + (($vehicleId % 11) - 5) * 0.01;
        }

        $phase = (($vehicleId * 17) + $tick) / 9;
        $latOffset = sin($phase) * 0.0022 + cos($phase / 2.7) * 0.0011;
        $lngOffset = cos($phase) * 0.0020 + sin($phase / 3.1) * 0.0012;

        return [
            'lat' => round($baseLat + $latOffset, 6),
            'lng' => round($baseLng + $lngOffset, 6),
        ];
    }
}

if (!function_exists('getTrackingVehiclesForUser')) {
    function getTrackingVehiclesForUser(array $user, int $limit = 200): array
    {
        $vehicles = getVehicles($limit);
        $role = (string) ($user['role'] ?? 'staff');

        if ($role !== 'customer') {
            return $vehicles;
        }

        $email = (string) ($user['email'] ?? '');
        $customerBookings = getCustomerBookings($email, 100);

        $activeVehicleIds = [];
        foreach ($customerBookings as $booking) {
            $status = strtolower((string) ($booking['status'] ?? ''));
            if (in_array($status, ['active', 'confirmed'], true)) {
                $activeVehicleIds[] = (int) ($booking['vehicle_id'] ?? 0);
            }
        }

        $activeVehicleIds = array_values(array_unique(array_filter($activeVehicleIds)));
        if ($activeVehicleIds === []) {
            return [];
        }

        return array_values(array_filter($vehicles, static function (array $vehicle) use ($activeVehicleIds): bool {
            return in_array((int) ($vehicle['vehicle_id'] ?? 0), $activeVehicleIds, true);
        }));
    }
}

if (!function_exists('getLiveTrackingSnapshot')) {
    function getLiveTrackingSnapshot(array $user, int $limit = 200, int $stepSeconds = 5): array
    {
        $stepSeconds = max(1, $stepSeconds);
        $tick = (int) floor(time() / $stepSeconds);

        $vehicles = getTrackingVehiclesForUser($user, $limit);
        $tracked = [];

        foreach ($vehicles as $vehicle) {
            $coords = trackingSimulatedCoordinate($vehicle, $tick);
            $tracked[] = [
                'vehicle_id' => (int) ($vehicle['vehicle_id'] ?? 0),
                'name' => (string) ($vehicle['name'] ?? 'Unknown vehicle'),
                'plate' => (string) ($vehicle['plate'] ?? ''),
                'status' => strtolower((string) ($vehicle['status'] ?? 'available')),
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'updated_at' => date('c'),
            ];
        }

        $center = trackingDefaultCenter();
        if ($tracked !== []) {
            $latSum = 0.0;
            $lngSum = 0.0;
            foreach ($tracked as $item) {
                $latSum += (float) $item['lat'];
                $lngSum += (float) $item['lng'];
            }

            $center = [
                'lat' => round($latSum / count($tracked), 6),
                'lng' => round($lngSum / count($tracked), 6),
            ];
        }

        return [
            'generated_at' => date('c'),
            'step_seconds' => $stepSeconds,
            'center' => $center,
            'vehicles' => $tracked,
        ];
    }
}
