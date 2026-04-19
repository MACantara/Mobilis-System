<?php
declare(strict_types=1);

if (!function_exists('trackingDefaultCenter')) {
    function trackingDefaultCenter(): array
    {
        return ['lat' => 14.5995, 'lng' => 121.0223];
    }
}

if (!function_exists('trackingRouteCatalog')) {
    function trackingRouteCatalog(): array
    {
        // Route waypoints are based on real Metro Manila road corridors.
        return [
            [
                [14.5547, 121.0244], [14.5667, 121.0437], [14.5763, 121.0517], [14.5887, 121.0553],
                [14.6009, 121.0471], [14.6172, 121.0408], [14.6325, 121.0339], [14.6404, 121.0250],
                [14.6282, 121.0186], [14.6098, 121.0207], [14.5883, 121.0229], [14.5692, 121.0252],
            ],
            [
                [14.5258, 121.0448], [14.5434, 121.0550], [14.5589, 121.0685], [14.5731, 121.0770],
                [14.5906, 121.0808], [14.6090, 121.0794], [14.6263, 121.0762], [14.6421, 121.0698],
                [14.6518, 121.0564], [14.6379, 121.0501], [14.6204, 121.0545], [14.6028, 121.0627],
                [14.5860, 121.0688], [14.5678, 121.0611], [14.5489, 121.0523],
            ],
            [
                [14.5412, 120.9934], [14.5540, 120.9907], [14.5664, 120.9879], [14.5808, 120.9839],
                [14.5959, 120.9810], [14.6108, 120.9806], [14.6235, 120.9837], [14.6340, 120.9916],
                [14.6299, 121.0048], [14.6160, 121.0089], [14.6008, 121.0068], [14.5854, 121.0048],
                [14.5708, 121.0016], [14.5564, 120.9984],
            ],
            [
                [14.5525, 121.0248], [14.5628, 121.0354], [14.5717, 121.0469], [14.5799, 121.0562],
                [14.5871, 121.0660], [14.5952, 121.0728], [14.6034, 121.0697], [14.6098, 121.0607],
                [14.6121, 121.0492], [14.6065, 121.0378], [14.5981, 121.0296], [14.5869, 121.0227],
                [14.5754, 121.0191], [14.5638, 121.0196],
            ],
            [
                [14.6044, 121.0320], [14.6148, 121.0419], [14.6257, 121.0508], [14.6351, 121.0622],
                [14.6312, 121.0743], [14.6201, 121.0822], [14.6069, 121.0845], [14.5932, 121.0813],
                [14.5821, 121.0738], [14.5765, 121.0611], [14.5814, 121.0482], [14.5918, 121.0382],
            ],
        ];
    }
}

if (!function_exists('trackingHaversineMeters')) {
    function trackingHaversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * (sin($dLng / 2) ** 2);

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
    }
}

if (!function_exists('trackingRouteMetrics')) {
    function trackingRouteMetrics(): array
    {
        static $routes = null;
        if (is_array($routes)) {
            return $routes;
        }

        $routes = [];

        foreach (trackingRouteCatalog() as $rawRoute) {
            $points = [];
            foreach ($rawRoute as $rawPoint) {
                $points[] = [
                    'lat' => (float) ($rawPoint[0] ?? 0),
                    'lng' => (float) ($rawPoint[1] ?? 0),
                ];
            }

            $pointCount = count($points);
            if ($pointCount < 2) {
                continue;
            }

            $segments = [];
            $pointStarts = [];
            $distance = 0.0;

            for ($i = 0; $i < $pointCount; $i++) {
                $pointStarts[$i] = $distance;

                $next = ($i + 1) % $pointCount;
                if ($next === 0 && $pointCount < 3) {
                    break;
                }

                $segmentLength = trackingHaversineMeters(
                    $points[$i]['lat'],
                    $points[$i]['lng'],
                    $points[$next]['lat'],
                    $points[$next]['lng']
                );

                if ($segmentLength <= 0.0) {
                    if ($next === 0) {
                        break;
                    }
                    continue;
                }

                $segments[] = [
                    'from' => $i,
                    'to' => $next,
                    'start' => $distance,
                    'length' => $segmentLength,
                ];

                $distance += $segmentLength;

                if ($next === 0) {
                    break;
                }
            }

            if ($distance <= 0.0 || $segments === []) {
                continue;
            }

            $routes[] = [
                'points' => $points,
                'segments' => $segments,
                'point_starts' => $pointStarts,
                'total' => $distance,
            ];
        }

        return $routes;
    }
}

if (!function_exists('trackingBaseCoordinate')) {
    function trackingBaseCoordinate(array $vehicle): array
    {
        $lat = isset($vehicle['latitude']) ? (float) $vehicle['latitude'] : 0.0;
        $lng = isset($vehicle['longitude']) ? (float) $vehicle['longitude'] : 0.0;

        if ($lat !== 0.0 && $lng !== 0.0) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        $routes = trackingRouteMetrics();
        if ($routes === []) {
            return trackingDefaultCenter();
        }

        $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
        $route = $routes[abs($vehicleId) % count($routes)];
        $points = (array) ($route['points'] ?? []);
        if ($points === []) {
            return trackingDefaultCenter();
        }

        $index = abs($vehicleId * 3) % count($points);
        return $points[$index];
    }
}

if (!function_exists('trackingSelectRouteForVehicle')) {
    function trackingSelectRouteForVehicle(array $base, int $vehicleId): ?array
    {
        $routes = trackingRouteMetrics();
        if ($routes === []) {
            return null;
        }

        $bestRoute = null;
        $bestStart = 0.0;
        $bestDistance = INF;

        foreach ($routes as $route) {
            $points = (array) ($route['points'] ?? []);
            $pointStarts = (array) ($route['point_starts'] ?? []);

            foreach ($points as $index => $point) {
                $distance = trackingHaversineMeters(
                    (float) $base['lat'],
                    (float) $base['lng'],
                    (float) ($point['lat'] ?? 0),
                    (float) ($point['lng'] ?? 0)
                );

                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestRoute = $route;
                    $bestStart = (float) ($pointStarts[$index] ?? 0.0);
                }
            }
        }

        if (!is_array($bestRoute)) {
            return null;
        }

        $routeTotal = (float) ($bestRoute['total'] ?? 0.0);
        if ($routeTotal <= 0.0) {
            return null;
        }

        // Small deterministic spacing to avoid marker overlap while staying on route.
        $staggerMeters = (abs($vehicleId) % 11) * 35.0;

        return [
            'route' => $bestRoute,
            'start' => fmod($bestStart + $staggerMeters, $routeTotal),
        ];
    }
}

if (!function_exists('trackingInterpolatePoint')) {
    function trackingInterpolatePoint(array $route, float $distance): array
    {
        $segments = (array) ($route['segments'] ?? []);
        $points = (array) ($route['points'] ?? []);
        $routeTotal = (float) ($route['total'] ?? 0.0);

        if ($segments === [] || $points === [] || $routeTotal <= 0.0) {
            return trackingDefaultCenter();
        }

        $distance = fmod(max(0.0, $distance), $routeTotal);

        foreach ($segments as $segment) {
            $start = (float) ($segment['start'] ?? 0.0);
            $length = (float) ($segment['length'] ?? 0.0);
            if ($length <= 0.0) {
                continue;
            }

            $end = $start + $length;
            if ($distance > $end) {
                continue;
            }

            $fromIndex = (int) ($segment['from'] ?? 0);
            $toIndex = (int) ($segment['to'] ?? 0);
            $from = $points[$fromIndex] ?? null;
            $to = $points[$toIndex] ?? null;

            if (!is_array($from) || !is_array($to)) {
                continue;
            }

            $ratio = ($distance - $start) / $length;

            return [
                'lat' => (float) $from['lat'] + (((float) $to['lat'] - (float) $from['lat']) * $ratio),
                'lng' => (float) $from['lng'] + (((float) $to['lng'] - (float) $from['lng']) * $ratio),
            ];
        }

        return $points[0];
    }
}

if (!function_exists('trackingMetersPerTick')) {
    function trackingMetersPerTick(string $status, int $stepSeconds): float
    {
        $status = strtolower(trim($status));

        $metersPerSecond = match ($status) {
            'rented', 'active', 'confirmed' => 10.0,
            'maintenance' => 1.0,
            'available' => 5.0,
            default => 6.0,
        };

        return $metersPerSecond * max(1, $stepSeconds);
    }
}

if (!function_exists('trackingSimulatedCoordinate')) {
    function trackingSimulatedCoordinate(array $vehicle, int $tick, int $stepSeconds = 5): array
    {
        $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0);
        $base = trackingBaseCoordinate($vehicle);

        $selection = trackingSelectRouteForVehicle($base, $vehicleId);
        if (!is_array($selection)) {
            return [
                'lat' => round((float) $base['lat'], 6),
                'lng' => round((float) $base['lng'], 6),
            ];
        }

        $route = (array) ($selection['route'] ?? []);
        $routeTotal = (float) ($route['total'] ?? 0.0);
        if ($routeTotal <= 0.0) {
            return [
                'lat' => round((float) $base['lat'], 6),
                'lng' => round((float) $base['lng'], 6),
            ];
        }

        $status = (string) ($vehicle['status'] ?? 'available');
        $metersPerTick = trackingMetersPerTick($status, $stepSeconds);
        $travelDistance = fmod((float) ($selection['start'] ?? 0.0) + ($tick * $metersPerTick), $routeTotal);
        $point = trackingInterpolatePoint($route, $travelDistance);

        return [
            'lat' => round((float) ($point['lat'] ?? $base['lat']), 6),
            'lng' => round((float) ($point['lng'] ?? $base['lng']), 6),
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
            $coords = trackingSimulatedCoordinate($vehicle, $tick, $stepSeconds);
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
