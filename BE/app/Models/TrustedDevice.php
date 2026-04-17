<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrustedDevice extends Model
{
    protected string $table = 'trusted_devices';
    protected string $primaryKey = 'trusted_device_id';
    protected array $fillable = [
        'employee_id',
        'device_id',
        'platform',
        'status',
        'origin_lat',
        'origin_lng',
        'origin_accuracy_m',
        'working_area_label',
        'origin_captured_at',
        'first_verified_at',
        'last_verified_at',
    ];
    /** @var array<string, bool>|null */
    private ?array $columns = null;

    public function findActiveByEmployeeAndDevice(int $employeeId, string $deviceId): ?array
    {
        $sql = "SELECT " . $this->selectColumns() . "
                FROM trusted_devices
                WHERE employee_id = :employee_id
                  AND device_id = :device_id
                  AND status = 'ACTIVE'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'device_id' => $deviceId,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function activateForEmployee(
        int $employeeId,
        string $deviceId,
        string $platform,
        ?float $originLat = null,
        ?float $originLng = null,
        ?float $originAccuracyM = null,
        ?string $workingAreaLabel = null,
        bool $revokeOthers = false
    ): void
    {
        if ($revokeOthers) {
            $revokeFields = ["status = 'REVOKED'"];
            if ($this->hasColumn('updated_at')) {
                $revokeFields[] = 'updated_at = CURRENT_TIMESTAMP';
            }
            $revokeSql = "UPDATE trusted_devices
                          SET " . implode(', ', $revokeFields) . "
                          WHERE employee_id = :employee_id
                            AND device_id <> :device_id
                            AND status = 'ACTIVE'";
            $revokeStmt = $this->db->prepare($revokeSql);
            $revokeStmt->execute([
                'employee_id' => $employeeId,
                'device_id' => $deviceId,
            ]);
        }

        $insertColumns = ['employee_id', 'device_id', 'platform', 'status'];
        $insertValues = [':employee_id', ':device_id', ':platform', "'ACTIVE'"];
        $updateFields = [
            'platform = VALUES(platform)',
            "status = 'ACTIVE'",
        ];

        if ($this->hasColumn('origin_lat')) {
            $insertColumns[] = 'origin_lat';
            $insertValues[] = ':origin_lat';
            $updateFields[] = 'origin_lat = COALESCE(origin_lat, VALUES(origin_lat))';
        }
        if ($this->hasColumn('origin_lng')) {
            $insertColumns[] = 'origin_lng';
            $insertValues[] = ':origin_lng';
            $updateFields[] = 'origin_lng = COALESCE(origin_lng, VALUES(origin_lng))';
        }
        if ($this->hasColumn('origin_accuracy_m')) {
            $insertColumns[] = 'origin_accuracy_m';
            $insertValues[] = ':origin_accuracy_m';
            $updateFields[] = 'origin_accuracy_m = COALESCE(origin_accuracy_m, VALUES(origin_accuracy_m))';
        }
        if ($this->hasColumn('working_area_label')) {
            $insertColumns[] = 'working_area_label';
            $insertValues[] = ':working_area_label';
            $updateFields[] = "working_area_label = COALESCE(NULLIF(working_area_label, ''), VALUES(working_area_label))";
        }
        if ($this->hasColumn('origin_captured_at')) {
            $insertColumns[] = 'origin_captured_at';
            $insertValues[] = ':origin_captured_at';
            $updateFields[] = 'origin_captured_at = COALESCE(origin_captured_at, VALUES(origin_captured_at))';
        }
        if ($this->hasColumn('first_verified_at')) {
            $insertColumns[] = 'first_verified_at';
            $insertValues[] = 'CURRENT_TIMESTAMP';
        }
        if ($this->hasColumn('last_verified_at')) {
            $insertColumns[] = 'last_verified_at';
            $insertValues[] = 'CURRENT_TIMESTAMP';
            $updateFields[] = 'last_verified_at = CURRENT_TIMESTAMP';
        }
        if ($this->hasColumn('updated_at')) {
            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        $upsertSql = "INSERT INTO trusted_devices (" . implode(', ', $insertColumns) . ")
                      VALUES (" . implode(', ', $insertValues) . ")
                      ON DUPLICATE KEY UPDATE " . implode(",\n                          ", $updateFields);
        $params = [
            'employee_id' => $employeeId,
            'device_id' => $deviceId,
            'platform' => $platform,
        ];
        if ($this->hasColumn('origin_lat')) {
            $params['origin_lat'] = $originLat;
        }
        if ($this->hasColumn('origin_lng')) {
            $params['origin_lng'] = $originLng;
        }
        if ($this->hasColumn('origin_accuracy_m')) {
            $params['origin_accuracy_m'] = $originAccuracyM;
        }
        if ($this->hasColumn('working_area_label')) {
            $params['working_area_label'] = $workingAreaLabel;
        }
        if ($this->hasColumn('origin_captured_at')) {
            $params['origin_captured_at'] = ($originLat !== null && $originLng !== null) ? date('Y-m-d H:i:s') : null;
        }

        $upsertStmt = $this->db->prepare($upsertSql);
        $upsertStmt->execute($params);
    }

    public function findLatestActiveByEmployee(int $employeeId): ?array
    {
        $orderCandidates = [];
        if ($this->hasColumn('last_verified_at')) {
            $orderCandidates[] = 'last_verified_at';
        }
        if ($this->hasColumn('first_verified_at')) {
            $orderCandidates[] = 'first_verified_at';
        }
        if ($this->hasColumn('created_at')) {
            $orderCandidates[] = 'created_at';
        }
        $orderExpr = $orderCandidates === [] ? 'trusted_device_id' : 'COALESCE(' . implode(', ', $orderCandidates) . ')';
        $sql = "SELECT " . $this->selectColumns() . "
                FROM trusted_devices
                WHERE employee_id = :employee_id
                  AND status = 'ACTIVE'
                ORDER BY {$orderExpr} DESC, trusted_device_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<string, bool>
     */
    private function loadColumns(): array
    {
        if ($this->columns !== null) {
            return $this->columns;
        }

        $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['table_name' => $this->table]);
        $rows = $stmt->fetchAll();

        $this->columns = [];
        foreach ($rows as $row) {
            $name = strtolower((string) ($row['column_name'] ?? ''));
            if ($name !== '') {
                $this->columns[$name] = true;
            }
        }

        return $this->columns;
    }

    private function hasColumn(string $column): bool
    {
        $columns = $this->loadColumns();
        return isset($columns[strtolower($column)]);
    }

    private function selectColumns(): string
    {
        $columns = [
            'trusted_device_id',
            'employee_id',
            'device_id',
            'platform',
            'status',
            $this->hasColumn('origin_lat') ? 'origin_lat' : 'NULL AS origin_lat',
            $this->hasColumn('origin_lng') ? 'origin_lng' : 'NULL AS origin_lng',
            $this->hasColumn('origin_accuracy_m') ? 'origin_accuracy_m' : 'NULL AS origin_accuracy_m',
            $this->hasColumn('working_area_label') ? 'working_area_label' : 'NULL AS working_area_label',
            $this->hasColumn('origin_captured_at') ? 'origin_captured_at' : 'NULL AS origin_captured_at',
            $this->hasColumn('first_verified_at') ? 'first_verified_at' : 'NULL AS first_verified_at',
            $this->hasColumn('last_verified_at') ? 'last_verified_at' : 'NULL AS last_verified_at',
            $this->hasColumn('created_at') ? 'created_at' : 'NULL AS created_at',
            $this->hasColumn('updated_at') ? 'updated_at' : 'NULL AS updated_at',
        ];

        return implode(', ', $columns);
    }
}
