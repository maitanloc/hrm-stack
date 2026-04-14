<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class RecruitmentAiScoringJob extends Model
{
    protected string $table = 'recruitment_ai_scoring_jobs';
    protected string $primaryKey = 'job_id';
    protected array $fillable = [
        'candidate_id',
        'status',
        'attempt_count',
        'max_attempts',
        'next_retry_at',
        'last_error',
        'locked_at',
        'finished_at',
    ];

    public function findOpenByCandidateId(int $candidateId): ?array
    {
        $sql = "SELECT *
                FROM recruitment_ai_scoring_jobs
                WHERE candidate_id = :candidate_id
                  AND status IN ('PENDING', 'RUNNING')
                ORDER BY job_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['candidate_id' => $candidateId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function enqueueCandidate(int $candidateId, int $maxAttempts = 4): int
    {
        $open = $this->findOpenByCandidateId($candidateId);
        if ($open !== null) {
            return (int) ($open['job_id'] ?? 0);
        }

        return $this->create([
            'candidate_id' => $candidateId,
            'status' => 'PENDING',
            'attempt_count' => 0,
            'max_attempts' => max(1, $maxAttempts),
            'next_retry_at' => null,
            'last_error' => null,
            'locked_at' => null,
            'finished_at' => null,
        ]);
    }

    public function resetOpenByCandidateId(int $candidateId): ?int
    {
        $open = $this->findOpenByCandidateId($candidateId);
        if ($open === null) {
            return null;
        }

        $jobId = (int) ($open['job_id'] ?? 0);
        if ($jobId <= 0) {
            return null;
        }

        $sql = "UPDATE recruitment_ai_scoring_jobs
                SET status = 'PENDING',
                    attempt_count = 0,
                    next_retry_at = NULL,
                    last_error = NULL,
                    locked_at = NULL,
                    finished_at = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['job_id' => $jobId]);
        return $jobId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function claimDueJobs(int $limit = 5, ?int $preferredPositionId = null): array
    {
        $limit = max(1, min($limit, 50));

        $params = ['limit' => $limit];
        if ($preferredPositionId !== null && $preferredPositionId > 0) {
            $sql = "SELECT j.job_id
                    FROM recruitment_ai_scoring_jobs j
                    JOIN recruitment_candidates c ON c.candidate_id = j.candidate_id
                    WHERE j.status IN ('PENDING', 'FAILED')
                      AND j.attempt_count < j.max_attempts
                      AND (j.next_retry_at IS NULL OR j.next_retry_at <= NOW())
                      AND (j.locked_at IS NULL OR j.locked_at <= DATE_SUB(NOW(), INTERVAL 10 MINUTE))
                    ORDER BY CASE WHEN c.recruitment_position_id = :preferred_position_id THEN 0 ELSE 1 END ASC,
                             j.job_id ASC
                    LIMIT :limit";
            $params['preferred_position_id'] = $preferredPositionId;
        } else {
            $sql = "SELECT job_id
                    FROM recruitment_ai_scoring_jobs
                    WHERE status IN ('PENDING', 'FAILED')
                      AND attempt_count < max_attempts
                      AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                      AND (locked_at IS NULL OR locked_at <= DATE_SUB(NOW(), INTERVAL 10 MINUTE))
                    ORDER BY job_id ASC
                    LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        if (isset($params['preferred_position_id'])) {
            $stmt->bindValue(':preferred_position_id', (int) $params['preferred_position_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', (int) $params['limit'], PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        $claimed = [];
        foreach ($rows as $row) {
            $jobId = (int) ($row['job_id'] ?? 0);
            if ($jobId <= 0) {
                continue;
            }

            $updated = $this->markRunning($jobId);
            if (!$updated) {
                continue;
            }

            $job = $this->find($jobId);
            if ($job !== null) {
                $claimed[] = $job;
            }
        }

        return $claimed;
    }

    public function markRunning(int $jobId): bool
    {
        $sql = "UPDATE recruitment_ai_scoring_jobs
                SET status = 'RUNNING',
                    attempt_count = attempt_count + 1,
                    locked_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP
                WHERE job_id = :job_id
                  AND status IN ('PENDING', 'FAILED')
                  AND attempt_count < max_attempts";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['job_id' => $jobId]);
        return $stmt->rowCount() > 0;
    }

    public function markDone(int $jobId): void
    {
        $sql = "UPDATE recruitment_ai_scoring_jobs
                SET status = 'DONE',
                    locked_at = NULL,
                    next_retry_at = NULL,
                    last_error = NULL,
                    finished_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP
                WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['job_id' => $jobId]);
    }

    public function markFailed(int $jobId, string $errorMessage, int $retryAfterSeconds, bool $isTerminal): void
    {
        $retryAt = $isTerminal ? null : date('Y-m-d H:i:s', time() + max(60, $retryAfterSeconds));

        $sql = "UPDATE recruitment_ai_scoring_jobs
                SET status = 'FAILED',
                    locked_at = NULL,
                    next_retry_at = :next_retry_at,
                    last_error = :last_error,
                    finished_at = :finished_at,
                    updated_at = CURRENT_TIMESTAMP
                WHERE job_id = :job_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'job_id' => $jobId,
            'next_retry_at' => $retryAt,
            'last_error' => mb_substr($errorMessage, 0, 2000),
            'finished_at' => $isTerminal ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
