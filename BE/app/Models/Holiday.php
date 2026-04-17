<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Holiday extends Model
{
    protected string $table = 'holidays';
    protected string $primaryKey = 'holiday_id';
    protected array $fillable = [
        'holiday_name',
        'holiday_date',
        'holiday_type',
        'is_recurring',
        'year',
        'paid_holiday',
        'salary_multiplier',
        'allowance_amount',
        'description',
    ];

    public function paginateList(int $offset, int $limit, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = [];
        $params = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = 'holiday_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = 'holiday_date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT * FROM holidays $whereSql ORDER BY holiday_date ASC, holiday_id ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM holidays $whereSql");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function findApplicableByDate(string $workDate): ?array
    {
        $sql = "SELECT *,
                       CASE
                           WHEN is_recurring = 1 THEN CONCAT(:work_year, DATE_FORMAT(holiday_date, '-%m-%d'))
                           ELSE holiday_date
                       END AS resolved_holiday_date
                FROM holidays
                WHERE (
                    (is_recurring = 0 AND holiday_date = :work_date)
                    OR (is_recurring = 1 AND DATE_FORMAT(holiday_date, '%m-%d') = DATE_FORMAT(:work_date, '%m-%d'))
                )
                ORDER BY is_recurring ASC, holiday_id ASC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'work_date' => $workDate,
            'work_year' => substr($workDate, 0, 4),
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
