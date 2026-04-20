<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\AttendancePrecheck;

class AttendanceRiskService
{
    private const GREEN_RADIUS_M = 120.0;
    private const YELLOW_RADIUS_M = 250.0;
    private const IMPOSSIBLE_TRAVEL_M = 20000.0;
    private const SUSPICIOUS_WINDOW_SECONDS = 1800;

    private SystemConfig $systemConfigs;
    private AttendancePrecheck $prechecks;

    public function __construct()
    {
        $this->systemConfigs = new SystemConfig();
        $this->prechecks = new AttendancePrecheck();
    }

    /**
     * Evaluate the risk of an attendance event based on location and historical data.
     */
    public function evaluateRisk(
        int $employeeId,
        string $deviceId,
        float $lat,
        float $lng,
        float $accuracyM,
        int $clientTs,
        ?array $trustedDeviceRow = null
    ): array {
        $geoPolicy = $this->loadGeoPolicy();
        $distanceRef = $this->resolveDistanceReference($lat, $lng, $trustedDeviceRow, $geoPolicy);
        $distanceM = (float) ($distanceRef['distance_m'] ?? 0.0);
        
        $greenRadius = (float) ($geoPolicy['green_radius_m'] ?? self::GREEN_RADIUS_M);
        $yellowRadius = (float) ($geoPolicy['yellow_radius_m'] ?? self::YELLOW_RADIUS_M);
        
        $riskMeta = [
            'company_anchor_label' => $distanceRef['label'] ?? null,
            'company_anchor_distance_m' => round($distanceM, 1),
            'company_anchor_count' => (int) ($distanceRef['anchor_count'] ?? 0),
            'company_geo_lock_enabled' => (bool) ($distanceRef['geo_lock_enabled'] ?? false),
        ];

        // 1. Check for impossible travel
        $latest = $this->prechecks->latestByEmployee($employeeId);
        if ($latest !== null) {
            $latestTs = strtotime((string) ($latest['created_at'] ?? '')) ?: null;
            if ($latestTs !== null) {
                $elapsed = abs($clientTs - $latestTs);
                if ($elapsed <= self::SUSPICIOUS_WINDOW_SECONDS) {
                    $latestLat = isset($latest['lat']) ? (float) $latest['lat'] : null;
                    $latestLng = isset($latest['lng']) ? (float) $latest['lng'] : null;
                    if ($latestLat !== null && $latestLng !== null) {
                        $moveDistance = $this->haversineMeters($lat, $lng, $latestLat, $latestLng);
                        if ($moveDistance >= self::IMPOSSIBLE_TRAVEL_M) {
                            return array_merge([
                                'risk_level' => 'RED',
                                'action' => 'BLOCK',
                                'reason_code' => 'IMPOSSIBLE_TRAVEL',
                                'next_action' => 'REQUEST_EXCEPTION',
                                'user_message' => 'Phát hiện di chuyển bất thường. Vui lòng liên hệ quản lý để xác minh.',
                                'distance_m' => round($moveDistance, 1),
                                'requires_manager_review' => true,
                            ], $riskMeta);
                        }
                    }
                }
            }
        }

        // 2. Geofence Check
        if ($distanceM <= $greenRadius && $accuracyM <= 120) {
            return array_merge([
                'risk_level' => 'GREEN',
                'action' => 'ALLOW',
                'reason_code' => 'OK_IN_ZONE',
                'next_action' => 'NONE',
                'user_message' => 'Bạn đang ở đúng khu vực làm việc.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => false,
            ], $riskMeta);
        }

        if ($distanceM <= $yellowRadius || ($accuracyM > 120 && $distanceM <= 1000.0)) {
            return array_merge([
                'risk_level' => 'YELLOW',
                'action' => 'ALLOW_FLAG',
                'reason_code' => $accuracyM > 120 ? 'SIGNAL_WEAK' : 'GPS_DRIFT_MINOR',
                'next_action' => 'NONE',
                'user_message' => 'Vị trí đang lệch nhẹ, hệ thống vẫn ghi nhận chấm công.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => true,
            ], $riskMeta);
        }

        return array_merge([
            'risk_level' => 'RED',
            'action' => 'BLOCK',
            'reason_code' => 'OUT_OF_GEOFENCE',
            'next_action' => 'RETRY',
            'user_message' => 'Bạn đang ngoài khu vực làm việc.',
            'distance_m' => round($distanceM, 1),
            'requires_manager_review' => true,
        ], $riskMeta);
    }

    private function loadGeoPolicy(): array
    {
        $enabledVal = $this->systemConfigs->get('attendance_company_geo_lock_enabled', 'true');
        $enabled = ($enabledVal === 'true' || $enabledVal === '1');
        $anchorsJson = $this->systemConfigs->get('attendance_company_anchor_points_json', '[]');
        $anchors = json_decode($anchorsJson, true) ?: [];

        return [
            'geo_lock_enabled' => $enabled,
            'anchors' => $anchors,
            'green_radius_m' => (float) $this->systemConfigs->get('attendance_green_radius_m', (string) self::GREEN_RADIUS_M),
            'yellow_radius_m' => (float) $this->systemConfigs->get('attendance_yellow_radius_m', (string) self::YELLOW_RADIUS_M),
        ];
    }

    private function resolveDistanceReference(float $lat, float $lng, ?array $trustedDevice, array $policy): array
    {
        $anchors = $policy['anchors'] ?? [];
        if (!empty($anchors)) {
            $best = null;
            $minDist = PHP_FLOAT_MAX;

            foreach ($anchors as $anchor) {
                $dist = $this->haversineMeters($lat, $lng, (float) $anchor['lat'], (float) $anchor['lng']);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $best = $anchor;
                }
            }

            return [
                'distance_m' => $minDist,
                'label' => $best['label'] ?? 'Comapny Anchor',
                'anchor_count' => count($anchors),
                'geo_lock_enabled' => true
            ];
        }

        if ($trustedDevice !== null) {
            $originLat = $trustedDevice['origin_lat'] ?? null;
            $originLng = $trustedDevice['origin_lng'] ?? null;
            if ($originLat !== null && $originLng !== null) {
                $dist = $this->haversineMeters($lat, $lng, (float) $originLat, (float) $originLng);
                return [
                    'distance_m' => $dist,
                    'label' => $trustedDevice['working_area_label'] ?? 'Device Origin',
                    'anchor_count' => 0,
                    'geo_lock_enabled' => false
                ];
            }
        }

        return ['distance_m' => 0.0, 'anchor_count' => 0, 'geo_lock_enabled' => false];
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
