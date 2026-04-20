<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class SchedulePublishLog extends Model
{
    protected string $table = 'schedule_publish_logs';
    protected string $primaryKey = 'publish_log_id';
    protected array $fillable = [
        'scope_type',
        'scope_id',
        'from_date',
        'to_date',
        'published_by',
        'published_at',
        'notes',
    ];

    public function findLatestCoveringDate(int $employeeId, ?int $departmentId, string $workDate): ?array
    {
        $sql = "SELECT spl.*, publisher.full_name AS published_by_name
                FROM schedule_publish_logs spl
                LEFT JOIN employees publisher ON publisher.employee_id = spl.published_by
                WHERE spl.from_date <= :work_date_from
                  AND spl.to_date >= :work_date_to
                  AND (
                    (spl.scope_type = 'EMPLOYEE' AND spl.scope_id = :employee_id)";

        if ($departmentId !== null && $departmentId > 0) {
            $sql .= " OR (spl.scope_type = 'DEPARTMENT' AND spl.scope_id = :department_id)";
        }

        $sql .= ")
                ORDER BY spl.published_at DESC, spl.publish_log_id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':work_date_from', $workDate);
        $stmt->bindValue(':work_date_to', $workDate);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        if ($departmentId !== null && $departmentId > 0) {
            $stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
