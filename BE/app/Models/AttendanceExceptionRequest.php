<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AttendanceExceptionRequest extends Model
{
    protected string $table = 'attendance_exception_requests';
    protected string $primaryKey = 'exception_request_id';
    protected array $fillable = [
        'employee_id',
        'attendance_id',
        'precheck_token',
        'reason',
        'lat',
        'lng',
        'status',
        'requested_at',
        'approved_by',
        'approved_at',
        'valid_until',
        'review_note',
    ];

    public function findDetail(int $id): ?array
    {
        $sql = "SELECT er.*,
                       e.full_name AS employee_name,
                       approver.full_name AS approved_by_name
                FROM attendance_exception_requests er
                JOIN employees e ON e.employee_id = er.employee_id
                LEFT JOIN employees approver ON approver.employee_id = er.approved_by
                WHERE er.exception_request_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
