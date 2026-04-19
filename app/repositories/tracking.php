<?php
declare(strict_types=1);

if (!function_exists('trackingDefaultCenter')) {
    function trackingDefaultCenter(): array
    {
        return ['lat' => 14.5995, 'lng' => 121.0223];
    }
}

if (!function_exists('trackingRouteApiBase')) {
    function trackingRouteApiBase(): string
    {
        $envUrl = getenv('MOBILIS_ROUTING_URL');
        if (is_string($envUrl) && $envUrl !== '') {
            return rtrim($envUrl, '/');
        }

        return 'https://router.project-osrm.org/route/v1/driving';
    }
}

if (!function_exists('trackingCacheDirectory')) {
    function trackingCacheDirectory(): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mobilis_tracking_cache';
    }
}

if (!function_exists('trackingCachePath')) {
    function trackingCachePath(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $key);
        return trackingCacheDirectory() . DIRECTORY_SEPARATOR . $safe . '.json';
    }
}

if (!function_exists('trackingReadCache')) {
    function trackingReadCache(string $key, int $ttlSeconds): ?array
    {
        $path = trackingCachePath($key);
        if (!is_file($path)) {
            return null;
        }

        $mtime = @filemtime($path);
        if (!is_int($mtime) || (time() - $mtime) > max(1, $ttlSeconds)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('trackingWriteCache')) {
    function trackingWriteCache(string $key, array $payload): void
    {
        $directory = trackingCacheDirectory();
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        if (!is_dir($directory)) {
            return;
        }

        @file_put_contents(
            trackingCachePath($key),
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
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

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));
    }
}

if (!function_exists('trackingOffsetCoordinate')) {
    function trackingOffsetCoordinate(array $origin, float $distanceMeters, float $bearingDegrees): array
    {
        $lat = (float) ($origin['lat'] ?? 0.0);
        $lng = (float) ($origin['lng'] ?? 0.0);

        $bearing = deg2rad($bearingDegrees);
        $latRad = deg2rad($lat);

        $metersPerDegreeLat = 111320.0;
        $metersPerDegreeLng = 111320.0 * max(0.1, cos($latRad));

        return [
            'lat' => $lat + (($distanceMeters * cos($bearing)) / $metersPerDegreeLat),
            'lng' => $lng + (($distanceMeters * sin($bearing)) / $metersPerDegreeLng),
        ];
    }
}

if (!function_exists('trackingVehicleCoordinatePool')) {
    function trackingVehicleCoordinatePool(): array
    {
        static $pool = null;
        if (is_array($pool)) {
            return $pool;
        }

        $pool = [];

        foreach (getVehicles(300) as $vehicle) {
            $lat = isset($vehicle['latitude']) ? (float) $vehicle['latitude'] : 0.0;
            $lng = isset($vehicle['longitude']) ? (float) $vehicle['longitude'] : 0.0;
            if ($lat === 0.0 || $lng === 0.0) {
                continue;
            }

            $pool[] = ['lat' => $lat, 'lng' => $lng];
        }

        return $pool;
    }
}

if (!function_exists('trackingDynamicAnchors')) {
    function trackingDynamicAnchors(): array
    {
        $pool = trackingVehicleCoordinatePool();

        if ($pool !== []) {
            $latSum = 0.0;
            $lngSum = 0.0;
            foreach ($pool as $point) {
                $latSum += (float) $point['lat'];
                $lngSum += (float) $point['lng'];
            }

            $center = [
                'lat' => $latSum / count($pool),
                'lng' => $lngSum / count($pool),
            ];

            $minLat = $pool[0];
            $maxLat = $pool[0];
            $minLng = $pool[0];
            $maxLng = $pool[0];

            foreach ($pool as $point) {
                if ($point['lat'] < $minLat['lat']) {
                    $minLat = $point;
                }
                if ($point['lat'] > $maxLat['lat']) {
                    $maxLat = $point;
                }
                if ($point['lng'] < $minLng['lng']) {
                    $minLng = $point;
                }
                if ($point['lng'] > $maxLng['lng']) {
                    $maxLng = $point;
                }
            }

            $anchors = [$center, $maxLat, $maxLng, $minLat, $minLng, $center];
        } else {
            $center = trackingDefaultCenter();
            $anchors = [
                $center,
                trackingOffsetCoordinate($center, 1900.0, 20.0),
                trackingOffsetCoordinate($center, 1700.0, 110.0),
                trackingOffsetCoordinate($center, 1800.0, 210.0),
                trackingOffsetCoordinate($center, 1650.0, 300.0),
                $center,
            ];
        }

        return array_map(static function (array $point): array {
            return [
                'lat' => round((float) ($point['lat'] ?? 0.0), 6),
                'lng' => round((float) ($point['lng'] ?? 0.0), 6),
            ];
        }, $anchors);
    }
}

if (!function_exists('trackingAnchorVariants')) {
    function trackingAnchorVariants(array $anchors): array
    {
        $count = count($anchors);
        if ($count < 4) {
            return [$anchors];
        }

        $start = $anchors[0];
        $end = $anchors[$count - 1];
        $middle = array_slice($anchors, 1, $count - 2);

        if (count($middle) < 3) {
            return [$anchors];
        }

        $variants = [];
        $rotationCount = min(3, count($middle));

        for ($shift = 0; $shift < $rotationCount; $shift++) {
            $rotated = array_merge(
                array_slice($middle, $shift),
                array_slice($middle, 0, $shift)
            );
            $variants[] = array_merge([$start], $rotated, [$end]);
        }

        return $variants;
    }
}

if (!function_exists('trackingFetchRouteFromMap')) {
    function trackingFetchRouteFromMap(array $anchors): ?array
    {
        $coordinateParts = [];
        foreach ($anchors as $point) {
            $lat = (float) ($point['lat'] ?? 0.0);
            $lng = (float) ($point['lng'] ?? 0.0);
            $coordinateParts[] = sprintf('%.6f,%.6f', $lng, $lat);
        }

        if (count($coordinateParts) < 2) {
            return null;
        }

        $url = trackingRouteApiBase() . '/' . implode(';', $coordinateParts)
            . '?overview=full&geometries=geojson&steps=false';

        $body = null;
        $httpStatus = 0;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);

            $response = curl_exec($ch);
            if (is_string($response)) {
                $body = $response;
            }
            $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Accept: application/json\r\n",
                    'timeout' => 6,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            if (is_string($response)) {
                $body = $response;
                $httpStatus = 200;
            }
        }

        if (!is_string($body) || $body === '' || $httpStatus < 200 || $httpStatus >= 300) {
            return null;
        }

        $payload = json_decode($body, true);
        $coordinates = $payload['routes'][0]['geometry']['coordinates'] ?? null;

        $points = [];
        foreach ($coordinates as $coord) {
            if (!is_array($coord) || count($coord) < 2) {
                continue;
            }

            $points[] = [
                'lat' => round($coord[1], 6),
                'lng' => round($coord[0], 6),
            ];
        }

        return count($points) >= 2 ? $points : null;
    }
}

if (!function_exists('trackingBuildRouteMetrics')) {
    function trackingBuildRouteMetrics(array $points): ?array
    {
        if (count($points) < 2) {
            return null;
        }

        $segments = [];
        $pointStarts = [];
        $distance = 0.0;

        $lastIndex = count($points) - 1;
        for ($i = 0; $i < $lastIndex; $i++) {
            $pointStarts[$i] = $distance;

            $from = $points[$i];
            $to = $points[$i + 1];

            $segmentLength = trackingHaversineMeters(
                (float) ($from['lat'] ?? 0.0),
                (float) ($from['lng'] ?? 0.0),
                (float) ($to['lat'] ?? 0.0),
                (float) ($to['lng'] ?? 0.0)
            );

            if ($segmentLength <= 0.0) {
                continue;
            }

            $segments[] = [
                'from' => $i,
                'to' => $i + 1,
                'start' => $distance,
                'length' => $segmentLength,
            ];

            $distance += $segmentLength;
        }

        $pointStarts[$lastIndex] = $distance;

        if ($distance <= 0.0 || $segments === []) {
            return null;
        }

        return [
            'points' => $points,
            'segments' => $segments,
            'point_starts' => $pointStarts,
            'total' => $distance,
        ];
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

        $cacheKey = 'map_routes_v1';
        $cached = trackingReadCache($cacheKey, 1800);

        $routePointSets = [];
        if (is_array($cached) && isset($cached['routes']) && is_array($cached['routes'])) {
            $routePointSets = $cached['routes'];
        } else {
            $anchors = trackingDynamicAnchors();
            $variants = trackingAnchorVariants($anchors);

            foreach ($variants as $variant) {
                $points = trackingFetchRouteFromMap($variant);
                if ($points === null) {
                    $points = $variant;
                }
                $routePointSets[] = $points;
            }

            trackingWriteCache($cacheKey, ['routes' => $routePointSets]);
        }

        foreach ($routePointSets as $points) {
            if (!is_array($points)) {
                continue;
            }

            $metrics = trackingBuildRouteMetrics($points);
            if ($metrics !== null) {
                $routes[] = $metrics;
            }
        }

        if ($routes === []) {
            $center = trackingDefaultCenter();
            $fallback = [
                $center,
                trackingOffsetCoordinate($center, 1200.0, 45.0),
                trackingOffsetCoordinate($center, 1200.0, 135.0),
                trackingOffsetCoordinate($center, 1200.0, 225.0),
                trackingOffsetCoordinate($center, 1200.0, 315.0),
                $center,
            ];

            $metrics = trackingBuildRouteMetrics($fallback);
            if ($metrics !== null) {
                $routes[] = $metrics;
            }
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

        return trackingDefaultCenter();
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
                    (float) ($base['lat'] ?? 0.0),
                    (float) ($base['lng'] ?? 0.0),
                    (float) ($point['lat'] ?? 0.0),
                    (float) ($point['lng'] ?? 0.0)
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
        if ($travelDistance < 0.0) {
            $travelDistance += $routeTotal;
        }
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
