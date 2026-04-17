<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class SalaryPeriod extends Model
{
    protected string $table = 'salary_periods';
    protected string $primaryKey = 'period_id';
    protected array $fillable = [
        'period_code',
        'period_name',
        'period_type',
        'year',
        'month',
        'start_date',
        'end_date',
        'payment_date',
        'standard_working_days',
        'status',
        'closed_by',
        'closed_date',
        'notes',
    ];

    public function paginateList(int $offset, int $limit, ?int $year = null, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($year !== null) {
            $where[] = 'sp.year = :year';
            $params['year'] = $year;
        }
        if ($status !== null && $status !== '') {
            $where[] = 'sp.status = :status';
            $params['status'] = $status;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT sp.*, e.full_name AS closed_by_name
                FROM salary_periods sp
                LEFT JOIN employees e ON e.employee_id = sp.closed_by
                $whereSql
                ORDER BY sp.start_date DESC, sp.period_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) AS total FROM salary_periods sp $whereSql";
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
        $sql = "SELECT sp.*, e.full_name AS closed_by_name
                FROM salary_periods sp
                LEFT JOIN employees e ON e.employee_id = sp.closed_by
                WHERE sp.period_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLockedWithinRange(string $fromDate, string $toDate, array $lockedStatuses = ['PAID', 'CLOSED']): ?array
    {
        $statuses = $this->normalizeStatuses($lockedStatuses);
        if ($statuses === []) {
            return null;
        }

        $statusParams = [];
        foreach ($statuses as $index => $status) {
            $statusParams['status_' . $index] = $status;
        }
        $statusSql = implode(', ', array_map(static fn(string $key): string => ':' . $key, array_keys($statusParams)));

        $sql = "SELECT sp.*
                FROM salary_periods sp
                WHERE sp.status IN ($statusSql)
                  AND sp.start_date <= :to_date
                  AND sp.end_date >= :from_date
                ORDER BY sp.start_date ASC, sp.period_id ASC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        foreach ($statusParams as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':from_date', $fromDate);
        $stmt->bindValue(':to_date', $toDate);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLockedByApplyMonth(string $applyMonth, array $lockedStatuses = ['PAID', 'CLOSED']): ?array
    {
        $statuses = $this->normalizeStatuses($lockedStatuses);
        if ($statuses === []) {
            return null;
        }
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $applyMonth) !== 1) {
            return null;
        }

        $year = (int) substr($applyMonth, 0, 4);
        $month = (int) substr($applyMonth, 5, 2);

        $statusParams = [];
        foreach ($statuses as $index => $status) {
            $statusParams['status_' . $index] = $status;
        }
        $statusSql = implode(', ', array_map(static fn(string $key): string => ':' . $key, array_keys($statusParams)));

        $sql = "SELECT sp.*
                FROM salary_periods sp
                WHERE sp.status IN ($statusSql)
                  AND (
                    (sp.year = :year AND sp.month = :month)
                    OR DATE_FORMAT(sp.start_date, '%Y-%m') = :apply_month
                  )
                ORDER BY sp.start_date DESC, sp.period_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        foreach ($statusParams as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':apply_month', $applyMonth);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function normalizeStatuses(array $statuses): array
    {
        $items = [];
        foreach ($statuses as $status) {
            $normalized = strtoupper(trim((string) $status));
            if ($normalized !== '') {
                $items[] = $normalized;
            }
        }

        return array_values(array_unique($items));
    }
}
