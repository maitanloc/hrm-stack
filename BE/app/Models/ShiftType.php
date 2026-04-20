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
        // Handle ENUM variants for 'ACTIVE' / 'HIỆU_LỰC'
        $statusCandidates = ['ACTIVE', 'HIỆU_LỰC', 'HIEU_LUC', 'HI?U L?C', '1', 1];
        $placeholders = implode(', ', array_map(fn($i) => ":st_$i", range(0, count($statusCandidates) - 1)));
        
        $sql = "SELECT * FROM shift_types 
                WHERE status IN ($placeholders) 
                ORDER BY shift_type_id ASC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($statusCandidates as $i => $val) {
            $stmt->bindValue(":st_$i", $val);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
