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
        'first_verified_at',
        'last_verified_at',
    ];

    public function findActiveByEmployeeAndDevice(int $employeeId, string $deviceId): ?array
    {
        $sql = "SELECT *
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

    public function activateForEmployee(int $employeeId, string $deviceId, string $platform): void
    {
        $revokeSql = "UPDATE trusted_devices
                      SET status = 'REVOKED',
                          updated_at = CURRENT_TIMESTAMP
                      WHERE employee_id = :employee_id
                        AND device_id <> :device_id
                        AND status = 'ACTIVE'";
        $revokeStmt = $this->db->prepare($revokeSql);
        $revokeStmt->execute([
            'employee_id' => $employeeId,
            'device_id' => $deviceId,
        ]);

        $upsertSql = "INSERT INTO trusted_devices (
                          employee_id,
                          device_id,
                          platform,
                          status,
                          first_verified_at,
                          last_verified_at
                      ) VALUES (
                          :employee_id,
                          :device_id,
                          :platform,
                          'ACTIVE',
                          CURRENT_TIMESTAMP,
                          CURRENT_TIMESTAMP
                      )
                      ON DUPLICATE KEY UPDATE
                          platform = VALUES(platform),
                          status = 'ACTIVE',
                          last_verified_at = CURRENT_TIMESTAMP,
                          updated_at = CURRENT_TIMESTAMP";
        $upsertStmt = $this->db->prepare($upsertSql);
        $upsertStmt->execute([
            'employee_id' => $employeeId,
            'device_id' => $deviceId,
            'platform' => $platform,
        ]);
    }
}
