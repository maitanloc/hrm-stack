<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class WorkflowAuditLog extends Model
{
    protected string $table = 'workflow_audit_logs';
    protected string $primaryKey = 'audit_log_id';
    protected array $fillable = [
        'entity_type',
        'entity_ref',
        'action_type',
        'from_state',
        'to_state',
        'actor_id',
        'context_json',
        'created_at',
    ];

    public function paginateList(
        int $offset,
        int $limit,
        ?string $entityType = null,
        ?string $entityRef = null,
        ?int $actorId = null
    ): array {
        $where = [];
        $params = [];

        if ($entityType !== null && $entityType !== '') {
            $where[] = 'wal.entity_type = :entity_type';
            $params['entity_type'] = strtoupper(trim($entityType));
        }
        if ($entityRef !== null && $entityRef !== '') {
            $where[] = 'wal.entity_ref = :entity_ref';
            $params['entity_ref'] = trim($entityRef);
        }
        if ($actorId !== null && $actorId > 0) {
            $where[] = 'wal.actor_id = :actor_id';
            $params['actor_id'] = $actorId;
        }

        $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
        $sql = "SELECT wal.*,
                       actor.full_name AS actor_name,
                       actor.employee_code AS actor_code
                FROM workflow_audit_logs wal
                LEFT JOIN employees actor ON actor.employee_id = wal.actor_id
                $whereSql
                ORDER BY wal.audit_log_id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) AS total FROM workflow_audit_logs wal $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

