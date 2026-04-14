<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class RecruitmentRejectedArchive extends Model
{
    protected string $table = 'recruitment_rejected_archive';
    protected string $primaryKey = 'archive_id';
    protected array $fillable = [
        'candidate_id',
        'full_name',
        'position_name',
        'email',
        'phone_number',
        'manager_score',
        'manager_reason',
        'hr_reason',
        'rejected_by',
        'rejected_at',
        'cv_url',
        'snapshot_json',
    ];

    public function upsertSnapshot(array $payload): void
    {
        $data = $this->filterFillable($payload);
        if (!isset($data['candidate_id'])) {
            return;
        }

        $sql = "INSERT INTO recruitment_rejected_archive (
                    candidate_id,
                    full_name,
                    position_name,
                    email,
                    phone_number,
                    manager_score,
                    manager_reason,
                    hr_reason,
                    rejected_by,
                    rejected_at,
                    cv_url,
                    snapshot_json
                ) VALUES (
                    :candidate_id,
                    :full_name,
                    :position_name,
                    :email,
                    :phone_number,
                    :manager_score,
                    :manager_reason,
                    :hr_reason,
                    :rejected_by,
                    :rejected_at,
                    :cv_url,
                    :snapshot_json
                )
                ON DUPLICATE KEY UPDATE
                    full_name = VALUES(full_name),
                    position_name = VALUES(position_name),
                    email = VALUES(email),
                    phone_number = VALUES(phone_number),
                    manager_score = VALUES(manager_score),
                    manager_reason = VALUES(manager_reason),
                    hr_reason = VALUES(hr_reason),
                    rejected_by = VALUES(rejected_by),
                    rejected_at = VALUES(rejected_at),
                    cv_url = VALUES(cv_url),
                    snapshot_json = VALUES(snapshot_json)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'candidate_id' => (int) ($data['candidate_id'] ?? 0),
            'full_name' => (string) ($data['full_name'] ?? ''),
            'position_name' => $data['position_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'manager_score' => $data['manager_score'] ?? null,
            'manager_reason' => $data['manager_reason'] ?? null,
            'hr_reason' => $data['hr_reason'] ?? null,
            'rejected_by' => $data['rejected_by'] ?? null,
            'rejected_at' => (string) ($data['rejected_at'] ?? date('Y-m-d H:i:s')),
            'cv_url' => $data['cv_url'] ?? null,
            'snapshot_json' => $data['snapshot_json'] ?? null,
        ]);
    }
}
