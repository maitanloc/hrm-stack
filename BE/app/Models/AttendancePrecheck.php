<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AttendancePrecheck extends Model
{
    protected string $table = 'attendance_prechecks';
    protected string $primaryKey = 'precheck_id';
    protected array $fillable = [
        'precheck_token',
        'employee_id',
        'device_id',
        'attendance_type',
        'risk_level',
        'action',
        'reason_code',
        'next_action',
        'lat',
        'lng',
        'accuracy_m',
        'distance_m',
        'requires_manager_review',
        'expires_at',
        'is_used',
    ];

    public function findByTokenHash(string $tokenHash): ?array
    {
        $sql = "SELECT *
                FROM attendance_prechecks
                WHERE precheck_token = :precheck_token
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['precheck_token' => $tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function markUsed(int $precheckId): bool
    {
        $sql = "UPDATE attendance_prechecks
                SET is_used = 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE precheck_id = :precheck_id
                  AND is_used = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['precheck_id' => $precheckId]);
        return $stmt->rowCount() > 0;
    }

    public function latestByEmployee(int $employeeId): ?array
    {
        $sql = "SELECT *
                FROM attendance_prechecks
                WHERE employee_id = :employee_id
                ORDER BY precheck_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
