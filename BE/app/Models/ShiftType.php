<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ShiftType extends Model
{
    protected string $table = 'shift_types';
    protected string $primaryKey = 'shift_type_id';
    protected array $fillable = [
        'shift_code',
        'shift_name',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'working_hours',
        'is_night_shift',
        'allow_overtime',
        'allow_wfh',
        'coefficient',
        'color_code',
        'description',
        'status',
    ];

    public function listActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM shift_types WHERE status = 'ACTIVE' ORDER BY shift_type_id ASC");
        return $stmt->fetchAll() ?: [];
    }
}
