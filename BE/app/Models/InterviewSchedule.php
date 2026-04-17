<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class InterviewSchedule extends Model
{
    protected string $table = 'interview_schedules';
    protected string $primaryKey = 'interview_id';
    private ?array $featureSupport = null;
    protected array $fillable = [
        'candidate_id',
        'interviewer_id',
        'department_manager_id',
        'interview_date',
        'interview_time',
        'interview_mode',
        'meeting_link',
        'location',
        'status',
        'result',
        'evaluation_notes',
        'manager_review_notes',
        'manager_decision',
        'reviewed_at',
        'created_by',
        'updated_by',
    ];

    public function paginateList(
        int $offset,
        int $limit,
        ?string $status = null,
        ?int $interviewerId = null,
        ?int $candidateId = null
    ): array {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'i.status = :status';
            $params['status'] = $status;
        }
        if ($interviewerId !== null) {
            $where[] = 'i.interviewer_id = :interviewer_id';
            $params['interviewer_id'] = $interviewerId;
        }
        if ($candidateId !== null) {
            $where[] = 'i.candidate_id = :candidate_id';
            $params['candidate_id'] = $candidateId;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $support = $this->featureSupport();

        $selectManagerCols = $support['interview_department_manager_id_column']
            ? 'i.department_manager_id,
                       dm.full_name AS department_manager_name'
            : 'NULL AS department_manager_id,
                       NULL AS department_manager_name';
        $joinManager = $support['interview_department_manager_id_column']
            ? 'LEFT JOIN employees dm ON dm.employee_id = i.department_manager_id'
            : '';

        $sql = "SELECT i.*,
                       c.full_name AS candidate_name,
                       rp.position_name,
                       rp.department_id,
                       d.department_name,
                       e.full_name AS interviewer_name,
                       {$selectManagerCols}
                FROM interview_schedules i
                JOIN recruitment_candidates c ON c.candidate_id = i.candidate_id
                LEFT JOIN recruitment_positions rp ON rp.recruitment_position_id = c.recruitment_position_id
                LEFT JOIN employees e ON e.employee_id = i.interviewer_id
                LEFT JOIN departments d ON d.department_id = rp.department_id
                {$joinManager}
                $whereSql
                ORDER BY i.interview_date DESC, i.interview_time DESC, i.interview_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) AS total
                     FROM interview_schedules i
                     JOIN recruitment_candidates c ON c.candidate_id = i.candidate_id
                     LEFT JOIN recruitment_positions rp ON rp.recruitment_position_id = c.recruitment_position_id
                     LEFT JOIN employees e ON e.employee_id = i.interviewer_id
                     $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function findDetail(int $id): ?array
    {
        $support = $this->featureSupport();
        $selectManagerCols = $support['interview_department_manager_id_column']
            ? 'i.department_manager_id,
                       dm.full_name AS department_manager_name'
            : 'NULL AS department_manager_id,
                       NULL AS department_manager_name';
        $joinManager = $support['interview_department_manager_id_column']
            ? 'LEFT JOIN employees dm ON dm.employee_id = i.department_manager_id'
            : '';

        $sql = "SELECT i.*,
                       c.full_name AS candidate_name,
                       rp.position_name,
                       rp.department_id,
                       d.department_name,
                       e.full_name AS interviewer_name,
                       {$selectManagerCols}
                FROM interview_schedules i
                JOIN recruitment_candidates c ON c.candidate_id = i.candidate_id
                LEFT JOIN recruitment_positions rp ON rp.recruitment_position_id = c.recruitment_position_id
                LEFT JOIN employees e ON e.employee_id = i.interviewer_id
                LEFT JOIN departments d ON d.department_id = rp.department_id
                {$joinManager}
                WHERE i.interview_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findReviewContext(int $id): ?array
    {
        $support = $this->featureSupport();
        $selectDepartmentManager = $support['interview_department_manager_id_column']
            ? 'i.department_manager_id,'
            : 'NULL AS department_manager_id,';

        $sql = "SELECT i.interview_id,
                       i.candidate_id,
                       {$selectDepartmentManager}
                       i.status,
                       i.result,
                       c.full_name AS candidate_name,
                       c.recruitment_position_id,
                       rp.position_name,
                       rp.department_id,
                       d.department_name,
                       d.manager_id AS current_department_manager_id
                FROM interview_schedules i
                JOIN recruitment_candidates c ON c.candidate_id = i.candidate_id
                LEFT JOIN recruitment_positions rp ON rp.recruitment_position_id = c.recruitment_position_id
                LEFT JOIN departments d ON d.department_id = rp.department_id
                WHERE i.interview_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLatestByCandidate(int $candidateId): ?array
    {
        $sql = "SELECT i.*
                FROM interview_schedules i
                WHERE i.candidate_id = :candidate_id
                ORDER BY i.interview_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['candidate_id' => $candidateId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function featureSupport(): array
    {
        if (is_array($this->featureSupport)) {
            return $this->featureSupport;
        }

        $columns = [];
        $columnStmt = $this->db->prepare("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'interview_schedules'
        ");
        $columnStmt->execute();
        foreach (($columnStmt->fetchAll(PDO::FETCH_COLUMN) ?: []) as $columnName) {
            $columns[(string) $columnName] = true;
        }

        $this->featureSupport = [
            'interview_department_manager_id_column' => isset($columns['department_manager_id']),
        ];

        return $this->featureSupport;
    }
}
