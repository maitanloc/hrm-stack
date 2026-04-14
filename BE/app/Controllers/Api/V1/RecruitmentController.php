<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Models\InterviewSchedule;
use App\Models\Notification;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentCandidateCv;
use App\Models\RecruitmentAiScoringJob;
use App\Models\RecruitmentCandidateManagerReview;
use App\Models\RecruitmentPosition;
use App\Models\RecruitmentRejectedArchive;
use App\Models\Position;
use App\Services\MailService;

class RecruitmentController extends Controller
{
    private const MAX_CV_FILE_SIZE = 10485760; // 10 MB
    private const DEFAULT_CV_STORAGE_PATH = 'storage/cv-uploads';
    private const AI_SCORING_DEFAULT_MAX_ATTEMPTS = 4;

    private RecruitmentPosition $positions;
    private RecruitmentCandidate $candidates;
    private RecruitmentCandidateCv $candidateCvs;
    private RecruitmentAiScoringJob $aiScoringJobs;
    private RecruitmentCandidateManagerReview $managerReviews;
    private RecruitmentRejectedArchive $rejectedArchive;
    private InterviewSchedule $interviews;
    private Position $positionsMaster;
    private Notification $notifications;
    private MailService $mailer;
    /** @var array<int, string> */
    private array $departmentNameCache = [];

    public function __construct()
    {
        $this->positions = new RecruitmentPosition();
        $this->candidates = new RecruitmentCandidate();
        $this->candidateCvs = new RecruitmentCandidateCv();
        $this->aiScoringJobs = new RecruitmentAiScoringJob();
        $this->managerReviews = new RecruitmentCandidateManagerReview();
        $this->rejectedArchive = new RecruitmentRejectedArchive();
        $this->interviews = new InterviewSchedule();
        $this->positionsMaster = new Position();
        $this->notifications = new Notification();
        $this->mailer = new MailService();
    }

    public function positionIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $search = $request->query('q');
        $status = $request->query('status');

        $result = $this->positions->paginateList(
            $paging['offset'],
            $paging['per_page'],
            is_string($search) ? $search : null,
            is_string($status) ? $status : null
        );

        return $this->ok(
            $result['items'],
            'Recruitment position list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function positionStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'position_code' => ['string'],
            'position_name' => ['required', 'string'],
            'department_id' => ['integer'],
            'employment_type' => ['string'],
            'vacancy_count' => ['integer'],
            'description' => ['string'],
            'status' => ['string'],
            'opened_at' => ['date'],
            'closed_at' => ['date'],
        ]);

        if ($request->input('required_skills_json') !== null) {
            $payload['required_skills_json'] = $this->normalizeRequiredSkillsPayload($request->input('required_skills_json'));
        }

        if (!isset($payload['position_code']) || trim((string) $payload['position_code']) === '') {
            $payload['position_code'] = 'REC-' . date('Y') . '-' . random_int(100, 999);
        }
        $payload['employment_type'] = $this->normalizeEmploymentType($payload['employment_type'] ?? null);
        $payload['status'] = $this->normalizeRecruitmentPositionStatus($payload['status'] ?? null);
        $payload['vacancy_count'] = (int) ($payload['vacancy_count'] ?? 1);

        $authUser = $request->attribute('auth_user');
        $actorId = (int) (($authUser['employee_id'] ?? 0));
        $payload['created_by'] = $actorId > 0 ? $actorId : null;
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $id = $this->positions->create($payload);
        return $this->created($this->positions->find($id), 'Recruitment position created');
    }

    public function positionUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->positions->find($id);
        if ($existing === null) {
            throw new HttpException('Recruitment position not found', 404, 'not_found');
        }

        $payload = Validator::validate($request->all(), [
            'position_name' => ['string'],
            'department_id' => ['integer'],
            'employment_type' => ['string'],
            'vacancy_count' => ['integer'],
            'description' => ['string'],
            'status' => ['string'],
            'opened_at' => ['date'],
            'closed_at' => ['date'],
        ]);

        if ($request->input('required_skills_json') !== null) {
            $payload['required_skills_json'] = $this->normalizeRequiredSkillsPayload($request->input('required_skills_json'));
        }

        if (isset($payload['employment_type'])) {
            $payload['employment_type'] = $this->normalizeEmploymentType($payload['employment_type']);
        }
        if (isset($payload['status'])) {
            $payload['status'] = $this->normalizeRecruitmentPositionStatus($payload['status']);
        }

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $this->positions->updateById($id, $payload);
        return $this->ok($this->positions->find($id), 'Recruitment position updated');
    }

    public function positionDelete(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->positions->find($id);
        if ($existing === null) {
            throw new HttpException('Recruitment position not found', 404, 'not_found');
        }
        $this->positions->deleteById($id);
        return $this->ok(null, 'Recruitment position deleted');
    }

    public function candidateIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $search = $request->query('q');
        $status = $request->query('status');
        $positionId = $request->query('recruitment_position_id') !== null ? (int) $request->query('recruitment_position_id') : null;

        $result = $this->candidates->paginateList(
            $paging['offset'],
            $paging['per_page'],
            is_string($search) ? $search : null,
            is_string($status) ? $status : null,
            $positionId
        );

        $hydrated = [];
        foreach ($result['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }

            try {
                $hydrated[] = $this->ensureCandidateDepartmentResolved($item);
            } catch (\Throwable $throwable) {
                // Never let enrichment failure break candidate listing.
                // Keep original row so HR can still see candidates/CVs.
                error_log('[recruitment] candidateIndex enrichment skipped: ' . $throwable->getMessage());
                $hydrated[] = $item;
            }
        }

        return $this->ok(
            $this->mapCandidateListWithCvAccess($hydrated),
            'Recruitment candidate list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function candidateShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->candidates->findDetail($id);
        if ($item === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }
        return $this->ok($this->mapCandidateWithCvAccess($this->ensureCandidateDepartmentResolved($item)), 'Candidate detail');
    }

    public function candidateStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'candidate_code' => ['string'],
            'full_name' => ['required', 'string'],
            'email' => ['string'],
            'phone_number' => ['string'],
            'recruitment_position_id' => ['required', 'integer'],
            'cv_url' => ['string'],
            'source_channel' => ['string'],
            'ai_score' => ['numeric'],
            'application_status' => ['string'],
            'applied_at' => ['date'],
            'notes' => ['string'],
        ]);

        if (!isset($payload['candidate_code']) || trim((string) $payload['candidate_code']) === '') {
            $payload['candidate_code'] = 'UV-' . date('Y') . '-' . random_int(1000, 9999);
        }
        if (!isset($payload['application_status'])) {
            $payload['application_status'] = 'NEW';
        }
        $payload['application_status'] = $this->normalizeCandidateStatus((string) $payload['application_status']);
        if (!isset($payload['applied_at'])) {
            $payload['applied_at'] = date('Y-m-d');
        }

        if (isset($payload['ai_score']) && $payload['ai_score'] !== null && $payload['ai_score'] !== '') {
            $payload['ai_scoring_status'] = 'DONE';
            $payload['ai_scored_at'] = date('Y-m-d H:i:s');
            $payload['ai_scoring_error'] = null;
        } else {
            $payload['ai_score'] = null;
            $payload['ai_scoring_status'] = 'PENDING';
            $payload['ai_scored_at'] = null;
            $payload['ai_scoring_error'] = null;
        }
        $payload['ai_semantic_score'] = null;
        $payload['ai_must_have_score'] = null;
        $payload['ai_nice_score'] = null;
        $payload['ai_exp_score'] = null;
        $payload['ai_matched_skills_json'] = null;
        $payload['ai_missing_skills_json'] = null;

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $payload['created_by'] = $actorId > 0 ? $actorId : null;
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $id = $this->candidates->create($payload);

        $this->managerReviews->upsertByCandidateId($id, [
            'workflow_status' => 'PENDING',
            'manager_decision_proposal' => 'PENDING',
            'created_by' => $actorId > 0 ? $actorId : null,
            'updated_by' => $actorId > 0 ? $actorId : null,
        ]);

        if (($payload['ai_scoring_status'] ?? 'PENDING') !== 'DONE') {
            $this->enqueueAiScoringForCandidate($id, $actorId > 0 ? $actorId : null);
        }

        $saved = $this->candidates->findDetail($id);
        return $this->created($saved !== null ? $this->mapCandidateWithCvAccess($saved) : null, 'Candidate created');
    }

    public function publicCandidateApply(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'full_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone_number' => ['string'],
            'department_id' => ['integer'],
            'recruitment_position_id' => ['integer'],
            'position_name' => ['string'],
            'source_channel' => ['string'],
            'ai_score' => ['numeric'],
            'application_status' => ['string'],
            'applied_at' => ['date'],
            'notes' => ['string'],
        ]);

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new HttpException('CV file is required (field: file).', 422, 'validation_error');
        }

        $uploadMeta = $this->validateUploadedCv($_FILES['file']);
        $connection = Database::connection();
        $candidateId = 0;
        $positionId = 0;

        try {
            $connection->beginTransaction();

            $positionId = $this->resolvePublicPositionId($payload);
            $candidatePayload = [
                'candidate_code' => 'UV-' . date('Y') . '-' . random_int(1000, 9999),
                'full_name' => trim((string) $payload['full_name']),
                'email' => trim((string) ($payload['email'] ?? '')),
                'phone_number' => $payload['phone_number'] ?? null,
                'recruitment_position_id' => $positionId,
                'source_channel' => $payload['source_channel'] ?? 'LANDING_PAGE',
                'ai_score' => null,
                'ai_scoring_status' => 'PENDING',
                'ai_scoring_error' => null,
                'ai_scored_at' => null,
                'ai_semantic_score' => null,
                'ai_must_have_score' => null,
                'ai_nice_score' => null,
                'ai_exp_score' => null,
                'ai_matched_skills_json' => null,
                'ai_missing_skills_json' => null,
                'application_status' => $this->normalizeCandidateStatus((string) ($payload['application_status'] ?? 'NEW')),
                'applied_at' => $payload['applied_at'] ?? date('Y-m-d'),
                'notes' => $payload['notes'] ?? null,
                'created_by' => null,
                'updated_by' => null,
            ];

            $candidateId = $this->candidates->create($candidatePayload);

            $this->managerReviews->upsertByCandidateId($candidateId, [
                'workflow_status' => 'PENDING',
                'manager_decision_proposal' => 'PENDING',
                'created_by' => null,
                'updated_by' => null,
            ]);

            $this->persistCandidateCv($candidateId, $uploadMeta, null);
            $this->enqueueAiScoringForCandidate($candidateId, null);
            $connection->commit();
            $saved = $this->candidates->findDetail($candidateId);
            if (is_array($saved)) {
                $this->sendCandidateApplicationReceivedEmail($saved);
            }

            // Trigger fast-path scoring right after submit, and prioritize
            // other pending candidates in the same recruitment position for fair comparison.
            if ($this->isImmediateAiScoringEnabled()) {
                $this->processAiScoringQueue($this->getImmediateAiScoringBatchSize(), $positionId > 0 ? $positionId : null);
                $saved = $this->candidates->findDetail($candidateId);
            }

            return $this->created(
                $saved !== null ? $this->mapCandidateWithCvAccess($saved) : null,
                'Candidate application submitted'
            );
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            if ($candidateId > 0) {
                $this->deleteStoredCandidateCv($candidateId);
            }
            throw $exception;
        }
    }

    public function candidateUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->candidates->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }

        $payload = Validator::validate($request->all(), [
            'full_name' => ['string'],
            'email' => ['string'],
            'phone_number' => ['string'],
            'recruitment_position_id' => ['integer'],
            'cv_url' => ['string'],
            'source_channel' => ['string'],
            'ai_score' => ['numeric'],
            'application_status' => ['string'],
            'applied_at' => ['date'],
            'notes' => ['string'],
        ]);

        $previousStatus = strtoupper(trim((string) ($existing['application_status'] ?? 'NEW')));
        $nextStatus = $previousStatus;
        if (isset($payload['application_status'])) {
            $payload['application_status'] = $this->normalizeCandidateStatus((string) $payload['application_status']);
            $nextStatus = strtoupper(trim((string) $payload['application_status']));
        }

        $authUser = $request->attribute('auth_user');
        $actorId = (int) (($authUser['employee_id'] ?? 0));
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $this->candidates->updateById($id, $payload);
        $saved = $this->candidates->findDetail($id);

        $shouldSendFinalEmail = $saved !== null
            && $nextStatus !== $previousStatus
            && in_array($nextStatus, ['PASSED', 'REJECTED'], true)
            && is_array($authUser)
            && Auth::isPrivileged($authUser);

        if ($shouldSendFinalEmail) {
            $this->sendCandidateFinalDecisionEmail($saved, $nextStatus);
            if ($nextStatus === 'REJECTED') {
                $this->archiveRejectedCandidate($saved, $actorId > 0 ? $actorId : null, (string) ($payload['notes'] ?? ''));
            }
        }

        return $this->ok($saved !== null ? $this->mapCandidateWithCvAccess($saved) : null, 'Candidate updated');
    }

    public function candidateManagerReviewShow(Request $request, array $params): array
    {
        $candidateId = (int) ($params['id'] ?? 0);
        $candidate = $this->candidates->findDetail($candidateId);
        if ($candidate === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }

        $review = $this->managerReviews->findByCandidateId($candidateId);
        if ($review === null) {
            $review = [
                'candidate_id' => $candidateId,
                'workflow_status' => 'PENDING',
                'manager_id' => null,
                'manager_score' => null,
                'manager_decision_proposal' => 'PENDING',
                'manager_review_notes' => null,
                'suggested_interview_date' => null,
                'suggested_interview_time' => null,
                'reviewed_at' => null,
            ];
        }

        return $this->ok($review, 'Candidate manager review');
    }

    public function candidateManagerReviewUpsert(Request $request, array $params): array
    {
        $candidateId = (int) ($params['id'] ?? 0);
        $candidate = $this->candidates->findDetail($candidateId);
        if ($candidate === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }

        $payload = Validator::validate($request->all(), [
            'workflow_status' => ['string'],
            'manager_decision' => ['string'],
            'manager_decision_proposal' => ['string'],
            'manager_id' => ['integer'],
            'manager_score' => ['numeric'],
            'manager_review_notes' => ['string'],
            'suggested_interview_date' => ['date'],
            'suggested_interview_time' => ['string'],
        ]);

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $decisionInput = (string) ($payload['manager_decision_proposal'] ?? $payload['manager_decision'] ?? $payload['workflow_status'] ?? 'PENDING');
        $workflowStatus = $this->normalizeManagerWorkflowStatus($decisionInput);
        $decisionProposal = $this->normalizeManagerDecisionProposal($decisionInput);

        $managerId = isset($payload['manager_id']) ? (int) $payload['manager_id'] : null;
        if (($managerId === null || $managerId <= 0) && $workflowStatus !== 'PENDING' && $actorId > 0) {
            $managerId = $actorId;
        }

        $reviewPayload = [
            'manager_id' => ($managerId !== null && $managerId > 0) ? $managerId : null,
            'workflow_status' => $workflowStatus,
            'manager_score' => $payload['manager_score'] ?? null,
            'manager_decision_proposal' => $decisionProposal,
            'manager_review_notes' => $payload['manager_review_notes'] ?? null,
            'suggested_interview_date' => $payload['suggested_interview_date'] ?? null,
            'suggested_interview_time' => $payload['suggested_interview_time'] ?? null,
            'reviewed_at' => ($decisionProposal !== 'PENDING' || in_array($workflowStatus, ['APPROVED', 'REJECTED'], true))
                ? date('Y-m-d H:i:s')
                : null,
            'created_by' => $actorId > 0 ? $actorId : null,
            'updated_by' => $actorId > 0 ? $actorId : null,
        ];

        $this->managerReviews->upsertByCandidateId($candidateId, $reviewPayload);
        $this->syncCandidateStatusAfterManagerWorkflow(
            $candidateId,
            (string) ($candidate['application_status'] ?? 'NEW'),
            $workflowStatus,
            $decisionProposal,
            $actorId > 0 ? $actorId : null
        );
        $this->syncInterviewSuggestionFromManagerWorkflow($candidateId, $reviewPayload, $actorId > 0 ? $actorId : null);

        // Avoid duplicate interview invitation emails:
        // official interview emails are sent from interviewStore/interviewUpdate only.

        $savedReview = $this->managerReviews->findByCandidateId($candidateId);
        $updatedCandidate = $this->candidates->findDetail($candidateId);
        if (
            is_array($savedReview)
            && is_array($updatedCandidate)
            && ($decisionProposal !== 'PENDING' || in_array($workflowStatus, ['APPROVED', 'REJECTED'], true))
        ) {
            $this->notifyHrAfterManagerReview($updatedCandidate, $savedReview, $actorId > 0 ? $actorId : null);
        }
        return $this->ok($savedReview, 'Candidate manager review updated');
    }

    public function candidateUploadCv(Request $request, array $params): array
    {
        $candidateId = (int) ($params['id'] ?? 0);
        $candidate = $this->candidates->findDetail($candidateId);
        if ($candidate === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            throw new HttpException('CV file is required (field: file).', 422, 'validation_error');
        }

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $uploadMeta = $this->validateUploadedCv($_FILES['file']);
        $meta = $this->persistCandidateCv($candidateId, $uploadMeta, $actorId > 0 ? $actorId : null);
        return $this->ok($meta, 'Candidate CV uploaded');
    }

    public function candidateDownloadCv(Request $request, array $params): array
    {
        $candidateId = (int) ($params['id'] ?? 0);
        $cv = $this->candidateCvs->findFileByCandidate($candidateId);
        if ($cv === null) {
            throw new HttpException('CV not found', 404, 'not_found');
        }

        $download = (string) ($request->query('download', '0') ?? '0');
        $disposition = $download === '1' ? 'attachment' : 'inline';
        $filename = $this->normalizeCvFilename((string) ($cv['original_filename'] ?? 'candidate-cv.pdf'));
        $body = $this->readCandidateCvBody($candidateId, $cv);
        if ($body === '') {
            throw new HttpException('CV file is unavailable.', 404, 'not_found');
        }

        return [
            'status' => 200,
            'response_type' => 'binary',
            'headers' => [
                'Content-Type' => 'application/pdf',
                'Content-Length' => (string) strlen($body),
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'body' => $body,
        ];
    }

    public function candidateRetryAiScore(Request $request, array $params): array
    {
        $candidateId = (int) ($params['id'] ?? 0);
        $candidate = $this->candidates->findDetail($candidateId);
        if ($candidate === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $jobId = $this->enqueueAiScoringForCandidate($candidateId, $actorId > 0 ? $actorId : null, true);
        $updated = $this->candidates->findDetail($candidateId);

        return $this->ok([
            'job_id' => $jobId,
            'candidate' => is_array($updated) ? $this->mapCandidateWithCvAccess($updated) : null,
        ], 'Candidate AI scoring retry queued');
    }

    public function internalProcessAiScoringJobs(Request $request): array
    {
        $this->assertInternalServiceToken($request);
        $limit = (int) ($request->query('limit') ?? $request->input('limit') ?? 5);
        $positionId = (int) ($request->query('recruitment_position_id') ?? $request->input('recruitment_position_id') ?? 0);
        $result = $this->processAiScoringQueue($limit, $positionId > 0 ? $positionId : null);
        return $this->ok($result, 'AI scoring queue processed');
    }

    public function interviewIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $status = $request->query('status');
        $interviewerId = $request->query('interviewer_id') !== null ? (int) $request->query('interviewer_id') : null;
        $candidateId = $request->query('candidate_id') !== null ? (int) $request->query('candidate_id') : null;

        $result = $this->interviews->paginateList(
            $paging['offset'],
            $paging['per_page'],
            is_string($status) ? $this->normalizeInterviewStatus($status) : null,
            $interviewerId,
            $candidateId
        );

        return $this->ok(
            $result['items'],
            'Interview list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function interviewStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'candidate_id' => ['required', 'integer'],
            'interviewer_id' => ['integer'],
            'interview_date' => ['required', 'date'],
            'interview_time' => ['required', 'string'],
            'interview_mode' => ['string'],
            'meeting_link' => ['string'],
            'location' => ['string'],
            'status' => ['string'],
            'result' => ['string'],
            'evaluation_notes' => ['string'],
        ]);

        $payload['interview_mode'] = $this->normalizeInterviewMode($payload['interview_mode'] ?? null);
        $payload['status'] = $this->normalizeInterviewStatus($payload['status'] ?? null);
        $payload['result'] = $this->normalizeInterviewResult($payload['result'] ?? null);
        $payload['manager_decision'] = 'PENDING';

        $candidateRouting = $this->candidates->findInterviewRouting((int) $payload['candidate_id']);
        if ($candidateRouting === null) {
            throw new HttpException('Candidate not found', 404, 'not_found');
        }
        $managerId = (int) ($candidateRouting['department_manager_id'] ?? 0);
        $payload['department_manager_id'] = $managerId > 0 ? $managerId : null;

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $payload['created_by'] = $actorId > 0 ? $actorId : null;
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $id = $this->interviews->create($payload);
        $this->syncCandidateStatusAfterInterviewScheduled(
            (int) $payload['candidate_id'],
            (string) ($candidateRouting['application_status'] ?? ''),
            $actorId > 0 ? $actorId : null
        );

        $saved = $this->interviews->findDetail($id);
        if (is_array($saved)) {
            $this->notifyDepartmentManagerForInterview($saved, $candidateRouting, $actorId > 0 ? $actorId : null);
            $this->sendCandidateInterviewScheduledEmail($saved, $candidateRouting);
        }

        return $this->created($saved, 'Interview created');
    }

    public function interviewUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->interviews->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Interview not found', 404, 'not_found');
        }

        $payload = Validator::validate($request->all(), [
            'interviewer_id' => ['integer'],
            'department_manager_id' => ['integer'],
            'interview_date' => ['date'],
            'interview_time' => ['string'],
            'interview_mode' => ['string'],
            'meeting_link' => ['string'],
            'location' => ['string'],
            'status' => ['string'],
            'result' => ['string'],
            'evaluation_notes' => ['string'],
            'manager_review_notes' => ['string'],
            'manager_decision' => ['string'],
        ]);

        if (isset($payload['interview_mode'])) {
            $payload['interview_mode'] = $this->normalizeInterviewMode((string) $payload['interview_mode']);
        }
        if (isset($payload['status'])) {
            $payload['status'] = $this->normalizeInterviewStatus((string) $payload['status']);
        }
        if (isset($payload['result'])) {
            $payload['result'] = $this->normalizeInterviewResult((string) $payload['result']);
        }
        if (isset($payload['manager_decision'])) {
            $payload['manager_decision'] = $this->normalizeInterviewResult((string) $payload['manager_decision']);
            if (in_array($payload['manager_decision'], ['PASS', 'FAIL'], true) && !isset($payload['reviewed_at'])) {
                $payload['reviewed_at'] = date('Y-m-d H:i:s');
            }
        }
        if (isset($payload['manager_review_notes']) && !isset($payload['evaluation_notes'])) {
            $payload['evaluation_notes'] = $payload['manager_review_notes'];
        }

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $payload['updated_by'] = $actorId > 0 ? $actorId : null;

        $this->interviews->updateById($id, $payload);
        $saved = $this->interviews->findDetail($id);
        $candidateId = (int) ($saved['candidate_id'] ?? $existing['candidate_id'] ?? 0);
        $candidateRouting = $candidateId > 0 ? $this->candidates->findInterviewRouting($candidateId) : null;
        if (
            is_array($saved)
            && is_array($candidateRouting)
            && $this->hasInterviewScheduleChanged($existing, $payload)
        ) {
            $this->notifyDepartmentManagerForInterview($saved, $candidateRouting, $actorId > 0 ? $actorId : null);
            $this->sendCandidateInterviewScheduledEmail($saved, $candidateRouting);
        }

        return $this->ok($saved, 'Interview updated');
    }

    public function interviewManagerReview(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $context = $this->interviews->findReviewContext($id);
        if ($context === null) {
            throw new HttpException('Interview not found', 404, 'not_found');
        }

        $actorId = (int) (($request->attribute('auth_user')['employee_id'] ?? 0));
        $managerIds = array_values(array_unique(array_filter([
            (int) ($context['department_manager_id'] ?? 0),
            (int) ($context['current_department_manager_id'] ?? 0),
        ], static fn(int $value): bool => $value > 0)));

        if ($managerIds === [] || !in_array($actorId, $managerIds, true)) {
            throw new HttpException('Only department manager can submit interview review.', 403, 'forbidden');
        }

        $payload = Validator::validate($request->all(), [
            'manager_decision' => ['required', 'string'],
            'manager_score' => ['numeric'],
            'manager_review_notes' => ['string'],
        ]);

        $decision = $this->normalizeInterviewResult((string) $payload['manager_decision']);
        if (!in_array($decision, ['PASS', 'FAIL'], true)) {
            throw new HttpException('manager_decision must be PASS or FAIL.', 422, 'validation_error');
        }

        $updatePayload = [
            'department_manager_id' => $actorId,
            'manager_decision' => $decision,
            'result' => 'PENDING',
            'status' => 'COMPLETED',
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $actorId,
        ];

        if (isset($payload['manager_review_notes'])) {
            $updatePayload['manager_review_notes'] = $payload['manager_review_notes'];
            $updatePayload['evaluation_notes'] = $payload['manager_review_notes'];
        }

        $this->interviews->updateById($id, $updatePayload);
        $saved = $this->interviews->findDetail($id);
        if (is_array($saved)) {
            $candidateId = (int) ($saved['candidate_id'] ?? 0);
            $candidateDetail = $candidateId > 0 ? $this->candidates->findDetail($candidateId) : null;
            $reviewPayload = [
                'manager_id' => $actorId,
                'workflow_status' => $decision === 'PASS' ? 'APPROVED' : 'REJECTED',
                'manager_decision_proposal' => $decision,
                'manager_score' => $payload['manager_score'] ?? null,
                'manager_review_notes' => $payload['manager_review_notes'] ?? null,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'updated_by' => $actorId,
                'created_by' => $actorId,
            ];
            if ($candidateId > 0) {
                $this->managerReviews->upsertByCandidateId($candidateId, $reviewPayload);
            }
            $this->syncCandidateStatusAfterManagerWorkflow(
                $candidateId,
                (string) (($candidateDetail['application_status'] ?? 'NEW')),
                $reviewPayload['workflow_status'],
                $decision,
                $actorId
            );
            $this->notifyHrAfterInterviewReview($saved, $actorId);
        }

        return $this->ok($saved, 'Department manager review saved');
    }

    private function normalizeEmploymentType(mixed $value): string
    {
        $type = strtoupper(trim((string) ($value ?? 'FULL_TIME')));
        return in_array($type, ['FULL_TIME', 'PART_TIME', 'CONTRACT', 'INTERN'], true) ? $type : 'FULL_TIME';
    }

    private function normalizeRecruitmentPositionStatus(mixed $value): string
    {
        $status = strtoupper(trim((string) ($value ?? 'OPEN')));
        return in_array($status, ['OPEN', 'CLOSED', 'ON_HOLD'], true) ? $status : 'OPEN';
    }

    private function normalizeRequiredSkillsPayload(mixed $value): ?string
    {
        $skills = [];

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                $skills = $decoded;
            } else {
                $skills = preg_split('/[,;\n]+/', $trimmed) ?: [];
            }
        } elseif (is_array($value)) {
            $skills = $value;
        }

        $normalized = [];
        foreach ($skills as $skill) {
            $skillText = trim((string) $skill);
            if ($skillText !== '') {
                $normalized[] = $skillText;
            }
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === []) {
            return null;
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    private function normalizeCandidateStatus(string $status): string
    {
        $mapped = strtoupper(trim($status));
        return match ($mapped) {
            'SCREENING', 'INTERVIEWING', 'PASSED', 'REJECTED', 'HIRED' => $mapped,
            'PASS' => 'PASSED',
            'FAIL' => 'REJECTED',
            default => 'NEW',
        };
    }

    private function normalizeManagerWorkflowStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));
        return match ($normalized) {
            'APPROVED', 'APPROVE', 'PASS', 'PASSED', 'TP_DA_DUYET' => 'APPROVED',
            'REJECTED', 'REJECT', 'FAIL', 'TU_CHOI' => 'REJECTED',
            default => 'PENDING',
        };
    }

    private function normalizeManagerDecisionProposal(string $decision): string
    {
        $normalized = strtoupper(trim($decision));
        return match ($normalized) {
            'APPROVED', 'APPROVE', 'PASS', 'PASSED', 'TP_DA_DUYET' => 'PASS',
            'REJECTED', 'REJECT', 'FAIL', 'TU_CHOI' => 'FAIL',
            default => 'PENDING',
        };
    }

    private function normalizeInterviewMode(mixed $value): string
    {
        $mode = strtoupper(trim((string) ($value ?? 'ONLINE')));
        return in_array($mode, ['ONLINE', 'OFFLINE'], true) ? $mode : 'ONLINE';
    }

    private function normalizeInterviewStatus(mixed $value): string
    {
        $status = strtoupper(trim((string) ($value ?? 'SCHEDULED')));
        if (in_array($status, ['SCHEDULED', 'COMPLETED', 'CANCELED'], true)) {
            return $status;
        }

        return match ($status) {
            'SẮP DIỄN RA' => 'SCHEDULED',
            'ĐÃ XONG' => 'COMPLETED',
            'ĐÃ HỦY' => 'CANCELED',
            default => 'SCHEDULED',
        };
    }

    private function normalizeInterviewResult(mixed $value): string
    {
        $result = strtoupper(trim((string) ($value ?? 'PENDING')));
        return in_array($result, ['PASS', 'FAIL', 'PENDING'], true) ? $result : 'PENDING';
    }

    private function mapCandidateListWithCvAccess(array $items): array
    {
        $mapped = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $mapped[] = $this->mapCandidateWithCvAccess($item);
            }
        }
        return $mapped;
    }

    private function mapCandidateWithCvAccess(array $candidate): array
    {
        $candidateId = (int) ($candidate['candidate_id'] ?? 0);
        $hasCv = (int) ($candidate['has_cv'] ?? 0) === 1;
        if ($candidateId > 0 && $hasCv) {
            $downloadUrl = '/api/v1/recruitment-candidates/' . $candidateId . '/cv';
            $candidate['cv_download_url'] = $downloadUrl;
            if (!isset($candidate['cv_url']) || trim((string) $candidate['cv_url']) === '') {
                $candidate['cv_url'] = $downloadUrl;
            }
        }

        $applicationStatus = strtoupper(trim((string) ($candidate['application_status'] ?? 'NEW')));
        $workflowStatus = strtoupper(trim((string) ($candidate['workflow_status'] ?? 'PENDING')));
        $managerProposal = strtoupper(trim((string) ($candidate['manager_decision_proposal'] ?? 'PENDING')));

        $candidate['frontend_workflow_status'] = match (true) {
            $applicationStatus === 'REJECTED' => 'fail',
            in_array($applicationStatus, ['PASSED', 'HIRED'], true) => 'pass',
            $applicationStatus === 'INTERVIEWING' => 'interviewing',
            $managerProposal === 'PASS' => 'mgr_approved',
            $managerProposal === 'FAIL' => 'pending_hr',
            $workflowStatus === 'APPROVED' => 'mgr_approved',
            $workflowStatus === 'PENDING' && $applicationStatus === 'SCREENING' => 'pending_mgr',
            default => 'pending_hr',
        };
        $candidate['ai_scoring_status'] = strtoupper(trim((string) ($candidate['ai_scoring_status'] ?? 'PENDING')));
        $candidate['ai_matched_skills'] = $this->decodeJsonArray($candidate['ai_matched_skills_json'] ?? null);
        $candidate['ai_missing_skills'] = $this->decodeJsonArray($candidate['ai_missing_skills_json'] ?? null);

        return $candidate;
    }

    private function normalizeCvFilename(string $filename): string
    {
        $clean = trim($filename);
        $clean = str_replace(["\r", "\n"], '', $clean);
        $clean = preg_replace('/[^A-Za-z0-9._ -]/', '_', $clean) ?? $clean;
        if ($clean === '' || $clean === '.' || $clean === '..') {
            $clean = 'candidate-cv.pdf';
        }
        if (!str_ends_with(strtolower($clean), '.pdf')) {
            $clean .= '.pdf';
        }
        return $clean;
    }

    private function validateUploadedCv(array $upload): array
    {
        $errorCode = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new HttpException('Uploaded CV is invalid.', 422, 'validation_error');
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $originalName = trim((string) ($upload['name'] ?? ''));
        $fileSize = (int) ($upload['size'] ?? 0);

        if ($tmpName === '' || !is_file($tmpName)) {
            throw new HttpException('Uploaded CV is invalid.', 422, 'validation_error');
        }
        if ($fileSize <= 0) {
            throw new HttpException('Uploaded CV is empty.', 422, 'validation_error');
        }
        if ($fileSize > self::MAX_CV_FILE_SIZE) {
            throw new HttpException('CV size must be <= 10MB.', 422, 'validation_error');
        }

        $fileContent = file_get_contents($tmpName);
        if ($fileContent === false || $fileContent === '') {
            throw new HttpException('Cannot read uploaded CV.', 422, 'validation_error');
        }
        // Some valid PDFs can contain leading whitespace/newline/BOM before "%PDF-".
        // Accept these while still enforcing real PDF signature.
        if (!preg_match('/^(?:\xEF\xBB\xBF)?\s*%PDF-/', $fileContent)) {
            throw new HttpException('Only PDF CV is supported.', 422, 'validation_error');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) ($finfo->file($tmpName) ?: 'application/pdf');
        $allowedMimeTypes = ['application/pdf', 'application/x-pdf', 'application/octet-stream', 'binary/octet-stream'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new HttpException('Only PDF CV is supported.', 422, 'validation_error');
        }

        return [
            'original_filename' => $this->normalizeCvFilename($originalName),
            'file_size' => $fileSize,
            'file_content' => $fileContent,
        ];
    }

    private function persistCandidateCv(int $candidateId, array $uploadMeta, ?int $actorId): array
    {
        $fileContent = (string) ($uploadMeta['file_content'] ?? '');
        if ($fileContent === '') {
            throw new HttpException('Cannot read uploaded CV.', 422, 'validation_error');
        }

        $this->storeCandidateCvToFileSystem($candidateId, $fileContent);
        $this->candidateCvs->upsertByCandidate($candidateId, [
            'original_filename' => (string) ($uploadMeta['original_filename'] ?? 'candidate-cv.pdf'),
            'mime_type' => 'application/pdf',
            'file_size' => (int) ($uploadMeta['file_size'] ?? strlen($fileContent)),
            'file_data' => $fileContent,
            'uploaded_by' => $actorId,
        ]);

        $this->candidates->updateById($candidateId, [
            'cv_url' => '/api/v1/recruitment-candidates/' . $candidateId . '/cv',
            'updated_by' => $actorId,
        ]);

        $meta = $this->candidateCvs->findMetaByCandidate($candidateId);
        if ($meta === null) {
            throw new HttpException('CV upload failed.', 500, 'upload_failed');
        }
        $meta['cv_download_url'] = '/api/v1/recruitment-candidates/' . $candidateId . '/cv';

        return $meta;
    }

    private function storeCandidateCvToFileSystem(int $candidateId, string $fileContent): void
    {
        $targetPath = $this->resolveCandidateCvPath($candidateId);
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new HttpException('Cannot prepare CV storage directory.', 500, 'storage_error');
        }

        $written = file_put_contents($targetPath, $fileContent, LOCK_EX);
        if ($written === false || $written <= 0) {
            throw new HttpException('Cannot store CV file on server.', 500, 'storage_error');
        }

        @chmod($targetPath, 0640);
    }

    private function readCandidateCvBody(int $candidateId, array $cv): string
    {
        $targetPath = $this->resolveCandidateCvPath($candidateId);
        if (is_file($targetPath)) {
            $fileContent = file_get_contents($targetPath);
            if ($fileContent !== false && $fileContent !== '') {
                return $fileContent;
            }
        }

        $legacyBody = (string) ($cv['file_data'] ?? '');
        return $legacyBody;
    }

    private function resolveCandidateCvPath(int $candidateId): string
    {
        $storageRoot = trim((string) env('CV_STORAGE_PATH', ''));
        if ($storageRoot === '') {
            $storageRoot = base_path(self::DEFAULT_CV_STORAGE_PATH);
        }

        return rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'candidate-' . $candidateId . '.pdf';
    }

    private function deleteStoredCandidateCv(int $candidateId): void
    {
        $targetPath = $this->resolveCandidateCvPath($candidateId);
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
    }

    private function resolvePublicPositionId(array $payload): int
    {
        $resolvedDepartmentId = $this->resolveDepartmentIdForPublicPosition($payload);
        $positionId = isset($payload['recruitment_position_id']) ? (int) $payload['recruitment_position_id'] : 0;
        if ($positionId > 0) {
            $existing = $this->positions->find($positionId);
            if ($existing === null) {
                throw new HttpException('Recruitment position not found', 404, 'not_found');
            }
            if ((int) ($existing['department_id'] ?? 0) <= 0 && $resolvedDepartmentId !== null) {
                $this->positions->updateById($positionId, [
                    'department_id' => $resolvedDepartmentId,
                    'updated_by' => null,
                ]);
            }
            return $positionId;
        }

        $positionName = trim((string) ($payload['position_name'] ?? ''));
        if ($positionName === '') {
            $positionName = 'Ung tuyen tu do';
        }

        $existing = $this->positions->findByName($positionName);
        if ($existing !== null) {
            $existingDepartmentId = (int) ($existing['department_id'] ?? 0);
            if ($existingDepartmentId <= 0 && $resolvedDepartmentId !== null) {
                $this->positions->updateById((int) $existing['recruitment_position_id'], [
                    'department_id' => $resolvedDepartmentId,
                    'updated_by' => null,
                ]);
            }
            return (int) $existing['recruitment_position_id'];
        }

        $newPositionId = $this->positions->create([
            'position_code' => 'PUB-' . date('Y') . '-' . random_int(100, 999),
            'position_name' => $positionName,
            'department_id' => $resolvedDepartmentId,
            'employment_type' => 'FULL_TIME',
            'vacancy_count' => 1,
            'description' => 'Auto-created from public application form',
            'status' => 'OPEN',
            'opened_at' => date('Y-m-d'),
            'created_by' => null,
            'updated_by' => null,
        ]);

        return $newPositionId;
    }

    /**
     * @param array<string, mixed> $candidate
     * @return array<string, mixed>
     */
    private function ensureCandidateDepartmentResolved(array $candidate): array
    {
        $departmentId = (int) ($candidate['department_id'] ?? 0);
        $positionId = (int) ($candidate['recruitment_position_id'] ?? 0);
        $positionName = trim((string) ($candidate['position_name'] ?? ''));

        if ($departmentId <= 0 && $positionId > 0 && $positionName !== '') {
            $inferredDepartmentId = $this->inferDepartmentIdFromPositionName($positionName);
            if ($inferredDepartmentId !== null) {
                $this->positions->updateById($positionId, [
                    'department_id' => $inferredDepartmentId,
                    'updated_by' => null,
                ]);
                $departmentId = $inferredDepartmentId;
                $candidate['department_id'] = $inferredDepartmentId;
            }
        }

        if ($departmentId > 0) {
            $departmentName = trim((string) ($candidate['department_name'] ?? ''));
            if ($departmentName === '') {
                $candidate['department_name'] = $this->findDepartmentNameById($departmentId) ?? '';
            }
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveDepartmentIdForPublicPosition(array $payload): ?int
    {
        $departmentId = isset($payload['department_id']) ? (int) $payload['department_id'] : 0;
        if ($departmentId > 0 && $this->findDepartmentNameById($departmentId) !== null) {
            return $departmentId;
        }

        $positionName = trim((string) ($payload['position_name'] ?? ''));
        if ($positionName === '') {
            return null;
        }

        return $this->inferDepartmentIdFromPositionName($positionName);
    }

    private function inferDepartmentIdFromPositionName(string $positionName): ?int
    {
        $name = trim($positionName);
        if ($name === '') {
            return null;
        }

        $existingDepartmentId = $this->findExistingRecruitmentDepartmentIdByName($name);
        if ($existingDepartmentId !== null) {
            return $existingDepartmentId;
        }

        $masterPosition = $this->positionsMaster->findBestByName($name);
        if (is_array($masterPosition)) {
            $positionGroup = trim((string) ($masterPosition['position_group'] ?? ''));
            if ($positionGroup !== '') {
                $groupNormalized = strtolower($this->normalizeForKeywordMatch($positionGroup));
                $groupCodeMap = ['IT', 'CNTT', 'MKT', 'MARKETING', 'HCNS', 'HR', 'KT', 'KETOAN', 'KD', 'CSKH', 'KHO', 'PHA', 'SX', 'QLDA'];
                foreach ($groupCodeMap as $code) {
                    $codeNorm = strtolower($this->normalizeForKeywordMatch($code));
                    if ($codeNorm !== '' && str_contains($groupNormalized, $codeNorm)) {
                        $byGroup = $this->findDepartmentIdByCodes([$code]);
                        if ($byGroup !== null) {
                            return $byGroup;
                        }
                    }
                }
            }
        }

        $connection = Database::connection();
        $sql = "SELECT eh.department_id, COUNT(*) AS total
                FROM employment_histories eh
                LEFT JOIN positions p ON p.position_id = eh.position_id
                WHERE eh.department_id IS NOT NULL
                  AND eh.is_current = TRUE
                  AND (
                    LOWER(TRIM(p.position_name)) = LOWER(TRIM(:position_name_exact))
                    OR LOWER(p.position_name) LIKE LOWER(:position_like)
                    OR LOWER(:position_name_contains) LIKE CONCAT('%', LOWER(p.position_name), '%')
                  )
                GROUP BY eh.department_id
                ORDER BY total DESC, eh.department_id ASC
                LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([
            'position_name_exact' => $name,
            'position_like' => '%' . $name . '%',
            'position_name_contains' => $name,
        ]);
        $row = $stmt->fetch();
        if ($row !== false) {
            $departmentId = (int) ($row['department_id'] ?? 0);
            if ($departmentId > 0) {
                return $departmentId;
            }
        }

        $normalized = $this->normalizeForKeywordMatch($name);

        $codeMap = [
            ['IT', 'CNTT'],
            ['MKT', 'MARKETING'],
            ['HCNS', 'HR', 'NHANSU', 'HCSN'],
            ['KT', 'KETOAN', 'TAICHINH'],
            ['KD', 'KINHDOANH', 'SALE', 'BUSINESS'],
            ['CSKH', 'SUPPORT'],
            ['KHO', 'LOGISTIC'],
            ['PHA', 'LEGAL'],
            ['SX', 'PRODUCTION'],
            ['QLDA', 'PROJECT'],
        ];

        $keywordMap = [
            ['keywords' => ['lap trinh', 'developer', 'it', 'phan mem', 'backend', 'frontend', 'fullstack', 'devops', 'qa', 'tester'], 'codes' => ['IT', 'CNTT']],
            ['keywords' => ['marketing', 'seo', 'content', 'ads', 'truyen thong'], 'codes' => ['MKT']],
            ['keywords' => ['nhan su', 'tuyen dung', 'hanh chinh', 'hr'], 'codes' => ['HCNS']],
            ['keywords' => ['ke toan', 'tai chinh', 'kiem toan'], 'codes' => ['KT']],
            ['keywords' => ['kinh doanh', 'ban hang', 'sale', 'business'], 'codes' => ['KD']],
            ['keywords' => ['cham soc khach hang', 'cskh', 'customer'], 'codes' => ['CSKH']],
            ['keywords' => ['kho', 'thu kho', 'logistic'], 'codes' => ['KHO']],
            ['keywords' => ['phap che', 'phap ly', 'legal'], 'codes' => ['PHA']],
            ['keywords' => ['san xuat', 'production'], 'codes' => ['SX']],
            ['keywords' => ['du an', 'project'], 'codes' => ['QLDA']],
        ];

        foreach ($codeMap as $codes) {
            foreach ($codes as $code) {
                $normalizedCode = strtolower($this->normalizeForKeywordMatch($code));
                if ($normalizedCode !== '' && str_contains($normalized, $normalizedCode)) {
                    $found = $this->findDepartmentIdByCodes($codes);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        foreach ($keywordMap as $entry) {
            $keywords = is_array($entry['keywords'] ?? null) ? $entry['keywords'] : [];
            $codes = is_array($entry['codes'] ?? null) ? $entry['codes'] : [];
            foreach ($keywords as $keyword) {
                $needle = strtolower($this->normalizeForKeywordMatch((string) $keyword));
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    $found = $this->findDepartmentIdByCodes($codes);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }

        return null;
    }

    private function findExistingRecruitmentDepartmentIdByName(string $positionName): ?int
    {
        $name = trim($positionName);
        if ($name === '') {
            return null;
        }

        $connection = Database::connection();
        $sql = "SELECT department_id
                FROM recruitment_positions
                WHERE LOWER(TRIM(position_name)) = LOWER(TRIM(:position_name))
                  AND department_id IS NOT NULL
                ORDER BY recruitment_position_id DESC
                LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute(['position_name' => $name]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $departmentId = (int) ($row['department_id'] ?? 0);
        return $departmentId > 0 ? $departmentId : null;
    }

    /**
     * @param list<string> $codes
     */
    private function findDepartmentIdByCodes(array $codes): ?int
    {
        $normalized = [];
        foreach ($codes as $code) {
            $trimmed = strtoupper(trim((string) $code));
            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }
        $normalized = array_values(array_unique($normalized));
        if ($normalized === []) {
            return null;
        }

        $connection = Database::connection();
        $params = [];
        $placeholders = [];
        foreach ($normalized as $index => $code) {
            $key = ':code_' . $index;
            $params[$key] = $code;
            $placeholders[] = $key;
        }

        $sql = "SELECT department_id
                FROM departments
                WHERE UPPER(TRIM(department_code)) IN (" . implode(',', $placeholders) . ")
                ORDER BY department_id ASC
                LIMIT 1";
        $stmt = $connection->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, \PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row !== false) {
            $departmentId = (int) ($row['department_id'] ?? 0);
            if ($departmentId > 0) {
                return $departmentId;
            }
        }

        return null;
    }

    private function findDepartmentNameById(int $departmentId): ?string
    {
        if ($departmentId <= 0) {
            return null;
        }
        if (isset($this->departmentNameCache[$departmentId])) {
            return $this->departmentNameCache[$departmentId];
        }

        $connection = Database::connection();
        $stmt = $connection->prepare("SELECT department_name FROM departments WHERE department_id = :department_id LIMIT 1");
        $stmt->execute(['department_id' => $departmentId]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        $name = trim((string) ($row['department_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $this->departmentNameCache[$departmentId] = $name;
        return $name;
    }

    private function normalizeForKeywordMatch(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $lower = function_exists('mb_strtolower') ? mb_strtolower($trimmed, 'UTF-8') : strtolower($trimmed);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
        $normalized = $ascii !== false ? $ascii : $lower;
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? $normalized;
        return trim($normalized);
    }

    private function assertInternalServiceToken(Request $request): void
    {
        $expected = trim((string) env('INTERNAL_SERVICE_TOKEN', ''));
        if ($expected === '') {
            throw new HttpException('INTERNAL_SERVICE_TOKEN is not configured.', 500, 'internal_config_error');
        }

        $provided = trim((string) ($request->header('x-internal-token', '') ?? ''));
        if ($provided === '') {
            $provided = trim((string) ($request->bearerToken() ?? ''));
        }

        if ($provided === '' || !hash_equals($expected, $provided)) {
            throw new HttpException('Invalid internal token.', 403, 'forbidden');
        }
    }

    private function isImmediateAiScoringEnabled(): bool
    {
        $raw = trim((string) env('AI_SCORING_IMMEDIATE_ON_APPLY', '1'));
        $normalized = strtolower($raw);
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function getImmediateAiScoringBatchSize(): int
    {
        $limit = (int) env('AI_SCORING_IMMEDIATE_BATCH_SIZE', '5');
        return max(1, min($limit, 20));
    }

    private function enqueueAiScoringForCandidate(int $candidateId, ?int $actorId, bool $force = false): int
    {
        if ($candidateId <= 0) {
            return 0;
        }

        $jobId = 0;
        if ($force) {
            $resetJobId = $this->aiScoringJobs->resetOpenByCandidateId($candidateId);
            if ($resetJobId !== null && $resetJobId > 0) {
                $jobId = $resetJobId;
            }
        }
        if ($jobId <= 0) {
            $jobId = $this->aiScoringJobs->enqueueCandidate($candidateId, self::AI_SCORING_DEFAULT_MAX_ATTEMPTS);
        }

        $this->candidates->updateById($candidateId, [
            'ai_score' => null,
            'ai_scoring_status' => 'PENDING',
            'ai_scoring_error' => null,
            'ai_scored_at' => null,
            'ai_semantic_score' => null,
            'ai_must_have_score' => null,
            'ai_nice_score' => null,
            'ai_exp_score' => null,
            'ai_matched_skills_json' => null,
            'ai_missing_skills_json' => null,
            'updated_by' => ($actorId !== null && $actorId > 0) ? $actorId : null,
        ]);

        return $jobId;
    }

    private function processAiScoringQueue(int $limit, ?int $preferredPositionId = null): array
    {
        $jobs = $this->aiScoringJobs->claimDueJobs($limit, $preferredPositionId);
        if ($jobs === []) {
            return [
                'processed' => 0,
                'done' => 0,
                'failed' => 0,
                'jobs' => [],
            ];
        }

        $processed = 0;
        $done = 0;
        $failed = 0;
        $results = [];

        foreach ($jobs as $job) {
            $processed++;
            $jobResult = $this->processSingleAiScoringJob($job);
            $results[] = $jobResult;
            if (($jobResult['status'] ?? '') === 'DONE') {
                $done++;
            } else {
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'done' => $done,
            'failed' => $failed,
            'jobs' => $results,
        ];
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private function processSingleAiScoringJob(array $job): array
    {
        $jobId = (int) ($job['job_id'] ?? 0);
        $candidateId = (int) ($job['candidate_id'] ?? 0);
        if ($jobId <= 0 || $candidateId <= 0) {
            return [
                'job_id' => $jobId,
                'candidate_id' => $candidateId,
                'status' => 'FAILED',
                'error' => 'Invalid job payload',
            ];
        }

        $this->candidates->updateById($candidateId, [
            'ai_scoring_status' => 'RUNNING',
            'ai_scoring_error' => null,
        ]);

        try {
            $candidate = $this->candidates->findDetail($candidateId);
            if (!is_array($candidate)) {
                throw new \RuntimeException('Candidate not found.');
            }

            $jdText = $this->buildJdTextForCandidate($candidate);
            $scoring = $this->requestAiScoreForCandidate($candidateId, $jdText);
            $scores = is_array($scoring['scores'] ?? null) ? $scoring['scores'] : [];

            $finalRaw = (float) ($scores['final_score'] ?? 0.0);
            $aiScore = $finalRaw <= 1.0001 ? round($finalRaw * 100, 2) : round($finalRaw, 2);
            $matchedSkills = $scores['matched_skills'] ?? [];
            $missingSkills = $scores['missing_skills'] ?? [];
            $matchedSkillsJson = json_encode(is_array($matchedSkills) ? array_values($matchedSkills) : [], JSON_UNESCAPED_UNICODE);
            $missingSkillsJson = json_encode(is_array($missingSkills) ? array_values($missingSkills) : [], JSON_UNESCAPED_UNICODE);

            $this->candidates->updateById($candidateId, [
                'ai_score' => $aiScore,
                'ai_scoring_status' => 'DONE',
                'ai_scoring_error' => null,
                'ai_scored_at' => date('Y-m-d H:i:s'),
                'ai_semantic_score' => isset($scores['semantic_score']) ? (float) $scores['semantic_score'] : null,
                'ai_must_have_score' => isset($scores['must_have_score']) ? (float) $scores['must_have_score'] : null,
                'ai_nice_score' => isset($scores['nice_score']) ? (float) $scores['nice_score'] : null,
                'ai_exp_score' => isset($scores['exp_score']) ? (float) $scores['exp_score'] : null,
                'ai_matched_skills_json' => $matchedSkillsJson ?: '[]',
                'ai_missing_skills_json' => $missingSkillsJson ?: '[]',
            ]);
            $this->aiScoringJobs->markDone($jobId);

            return [
                'job_id' => $jobId,
                'candidate_id' => $candidateId,
                'status' => 'DONE',
                'ai_score' => $aiScore,
            ];
        } catch (\Throwable $exception) {
            $attemptCount = (int) ($job['attempt_count'] ?? 1);
            $maxAttempts = (int) ($job['max_attempts'] ?? self::AI_SCORING_DEFAULT_MAX_ATTEMPTS);
            $isTerminal = $attemptCount >= max(1, $maxAttempts);
            $retrySeconds = min((int) (pow(2, max(1, $attemptCount)) * 60), 3600);
            $errorMessage = mb_substr($exception->getMessage(), 0, 1500);

            $this->aiScoringJobs->markFailed($jobId, $errorMessage, $retrySeconds, $isTerminal);
            $this->candidates->updateById($candidateId, [
                'ai_scoring_status' => $isTerminal ? 'FAILED' : 'PENDING',
                'ai_scoring_error' => $errorMessage,
            ]);

            return [
                'job_id' => $jobId,
                'candidate_id' => $candidateId,
                'status' => 'FAILED',
                'terminal' => $isTerminal,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function buildJdTextForCandidate(array $candidate): string
    {
        $positionName = trim((string) ($candidate['position_name'] ?? ''));
        $positionId = (int) ($candidate['recruitment_position_id'] ?? 0);
        $position = $positionId > 0 ? $this->positions->find($positionId) : null;
        $requiredSkills = $this->decodeJsonArray($position['required_skills_json'] ?? null);
        $positionDescription = trim((string) ($position['description'] ?? ''));

        $masterPosition = $positionName !== '' ? $this->positionsMaster->findBestByName($positionName) : null;
        $jobDescription = trim((string) ($masterPosition['job_description'] ?? ''));
        $requirements = trim((string) ($masterPosition['requirements'] ?? ''));

        $parts = [];
        if ($positionName !== '') {
            $parts[] = 'Vị trí tuyển dụng: ' . $positionName;
        }
        if ($requiredSkills !== []) {
            $parts[] = 'Kỹ năng bắt buộc: ' . implode(', ', array_map(static fn($item): string => (string) $item, $requiredSkills));
        }
        if ($positionDescription !== '') {
            $parts[] = 'Mô tả công việc (Recruitment): ' . $positionDescription;
        }
        if ($jobDescription !== '') {
            $parts[] = 'Mô tả công việc (HRM): ' . $jobDescription;
        }
        if ($requirements !== '') {
            $parts[] = 'Yêu cầu công việc (HRM): ' . $requirements;
        }

        if ($parts === []) {
            return 'Vị trí tuyển dụng: ' . ($positionName !== '' ? $positionName : 'Ứng viên tự do');
        }

        return implode("\n\n", $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestAiScoreForCandidate(int $candidateId, string $jdText): array
    {
        $baseUrl = rtrim(trim((string) env('HRM_API_BASE_URL', '')), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('HRM_API_BASE_URL is not configured.');
        }

        $timeout = max(10, (int) env('HRM_API_TIMEOUT', '60'));
        $internalToken = trim((string) env('HRM_INTERNAL_TOKEN', ''));
        $headers = [];
        if ($internalToken !== '') {
            $headers['X-Internal-Token'] = $internalToken;
        }

        $url = $baseUrl . '/screen/from-be-candidate';
        $response = $this->postJsonRequest($url, [
            'candidate_id' => $candidateId,
            'jd_text' => $jdText,
        ], $headers, $timeout);

        if (!isset($response['candidate']) || !is_array($response['candidate'])) {
            throw new \RuntimeException('Unexpected response from resume scoring service.');
        }

        return $response['candidate'];
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function postJsonRequest(string $url, array $payload, array $headers = [], int $timeout = 30): array
    {
        $headerLines = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Cannot connect to scoring service.');
        }

        $statusCode = 0;
        $responseHeaders = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?: [])
            : [];
        if (isset($responseHeaders[0])) {
            if (preg_match('/\\s(\\d{3})\\s/', (string) $responseHeaders[0], $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response from scoring service.');
        }

        if ($statusCode >= 400 || (($decoded['success'] ?? true) === false)) {
            $message = (string) ($decoded['message'] ?? $decoded['detail'] ?? 'Scoring request failed');
            throw new \RuntimeException($message);
        }

        $data = $decoded['data'] ?? $decoded;
        if (!is_array($data)) {
            throw new \RuntimeException('Scoring response data is invalid.');
        }

        return $data;
    }

    private function archiveRejectedCandidate(array $candidate, ?int $actorId, string $hrReason): void
    {
        $candidateId = (int) ($candidate['candidate_id'] ?? 0);
        if ($candidateId <= 0) {
            return;
        }

        $snapshot = [
            'candidate_id' => $candidateId,
            'candidate_code' => $candidate['candidate_code'] ?? null,
            'full_name' => $candidate['full_name'] ?? null,
            'email' => $candidate['email'] ?? null,
            'phone_number' => $candidate['phone_number'] ?? null,
            'position_name' => $candidate['position_name'] ?? null,
            'manager_score' => $candidate['manager_score'] ?? null,
            'manager_review_notes' => $candidate['manager_review_notes'] ?? null,
            'manager_decision_proposal' => $candidate['manager_decision_proposal'] ?? null,
            'hr_reason' => $hrReason,
            'cv_url' => $candidate['cv_url'] ?? null,
            'application_status' => $candidate['application_status'] ?? null,
            'rejected_at' => date('Y-m-d H:i:s'),
        ];

        $this->rejectedArchive->upsertSnapshot([
            'candidate_id' => $candidateId,
            'full_name' => (string) ($candidate['full_name'] ?? ''),
            'position_name' => $candidate['position_name'] ?? null,
            'email' => $candidate['email'] ?? null,
            'phone_number' => $candidate['phone_number'] ?? null,
            'manager_score' => $candidate['manager_score'] ?? null,
            'manager_reason' => $candidate['manager_review_notes'] ?? null,
            'hr_reason' => $hrReason !== '' ? $hrReason : (string) ($candidate['notes'] ?? ''),
            'rejected_by' => ($actorId !== null && $actorId > 0) ? $actorId : null,
            'rejected_at' => date('Y-m-d H:i:s'),
            'cv_url' => $candidate['cv_url'] ?? null,
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function syncCandidateStatusAfterInterviewScheduled(int $candidateId, string $currentStatus, ?int $actorId): void
    {
        $status = strtoupper(trim($currentStatus));
        if (in_array($status, ['PASSED', 'REJECTED', 'HIRED'], true)) {
            return;
        }

        $payload = ['application_status' => 'INTERVIEWING'];
        if ($actorId !== null && $actorId > 0) {
            $payload['updated_by'] = $actorId;
        }
        $this->candidates->updateById($candidateId, $payload);
    }

    private function syncCandidateStatusAfterManagerWorkflow(
        int $candidateId,
        string $currentStatus,
        string $workflowStatus,
        string $decisionProposal,
        ?int $actorId
    ): void {
        if ($candidateId <= 0) {
            return;
        }

        $status = strtoupper(trim($currentStatus));
        if (in_array($status, ['PASSED', 'REJECTED', 'HIRED'], true)) {
            return;
        }

        $proposal = strtoupper(trim($decisionProposal));
        $next = match (true) {
            $proposal === 'PASS' => 'INTERVIEWING',
            $proposal === 'FAIL' => 'INTERVIEWING',
            $workflowStatus === 'APPROVED' => 'SCREENING',
            $workflowStatus === 'REJECTED' => 'SCREENING',
            default => 'SCREENING',
        };

        $payload = ['application_status' => $next];
        if ($actorId !== null && $actorId > 0) {
            $payload['updated_by'] = $actorId;
        }
        $this->candidates->updateById($candidateId, $payload);
    }

    private function syncInterviewSuggestionFromManagerWorkflow(
        int $candidateId,
        array $reviewPayload,
        ?int $actorId
    ): void {
        $suggestedDate = (string) ($reviewPayload['suggested_interview_date'] ?? '');
        $suggestedTime = (string) ($reviewPayload['suggested_interview_time'] ?? '');
        if ($suggestedDate === '' || $suggestedTime === '') {
            return;
        }

        $existing = $this->interviews->findLatestByCandidate($candidateId);
        if ($existing !== null) {
            $update = [
                'interview_date' => $suggestedDate,
                'interview_time' => $suggestedTime,
                'status' => 'SCHEDULED',
            ];
            if ($actorId !== null && $actorId > 0) {
                $update['updated_by'] = $actorId;
            }
            $this->interviews->updateById((int) $existing['interview_id'], $update);
            return;
        }

        $insert = [
            'candidate_id' => $candidateId,
            'interviewer_id' => null,
            'department_manager_id' => $reviewPayload['manager_id'] ?? null,
            'interview_date' => $suggestedDate,
            'interview_time' => $suggestedTime,
            'interview_mode' => 'ONLINE',
            'status' => 'SCHEDULED',
            'result' => 'PENDING',
            'manager_decision' => 'PENDING',
        ];
        if ($actorId !== null && $actorId > 0) {
            $insert['created_by'] = $actorId;
            $insert['updated_by'] = $actorId;
        }
        $this->interviews->create($insert);
    }

    private function syncCandidateStatusAfterInterviewResult(array $interview, ?int $actorId): void
    {
        $candidateId = (int) ($interview['candidate_id'] ?? 0);
        if ($candidateId <= 0) {
            return;
        }

        $result = strtoupper(trim((string) ($interview['result'] ?? 'PENDING')));
        if (!in_array($result, ['PASS', 'FAIL'], true)) {
            return;
        }

        $payload = [
            'application_status' => $result === 'PASS' ? 'PASSED' : 'REJECTED',
        ];
        if ($actorId !== null && $actorId > 0) {
            $payload['updated_by'] = $actorId;
        }
        $this->candidates->updateById($candidateId, $payload);
    }

    private function notifyDepartmentManagerForInterview(array $interview, array $candidateRouting, ?int $actorId): void
    {
        $receiverId = (int) ($candidateRouting['department_manager_id'] ?? 0);
        if ($receiverId <= 0) {
            return;
        }

        $candidateName = (string) ($candidateRouting['candidate_name'] ?? 'Candidate');
        $positionName = (string) ($candidateRouting['position_name'] ?? 'Unknown position');
        $departmentName = (string) ($candidateRouting['department_name'] ?? 'Unknown department');
        $interviewDate = (string) ($interview['interview_date'] ?? '');
        $interviewTime = (string) ($interview['interview_time'] ?? '');
        $interviewId = (int) ($interview['interview_id'] ?? 0);

        $content = sprintf(
            'HR scheduled interview for %s (%s) on %s %s for %s.',
            $candidateName,
            $positionName,
            $interviewDate,
            $interviewTime,
            $departmentName
        );

        $payload = [
            'notification_type' => 'INTERVIEW_SCHEDULED',
            'title' => 'New interview schedule',
            'content' => $content,
            'receiver_id' => $receiverId,
            'department_id' => (int) ($candidateRouting['department_id'] ?? 0) > 0 ? (int) $candidateRouting['department_id'] : null,
            'reference_type' => null,
            'reference_id' => null,
            'action_url' => $interviewId > 0 ? '/recruitment/interviews/' . $interviewId : null,
        ];

        if ($actorId !== null && $actorId > 0) {
            $payload['sender_id'] = $actorId;
        }

        try {
            $this->notifications->create($payload);
        } catch (\Throwable) {
            // Best-effort notification only.
        }

        $this->sendDepartmentManagerInterviewScheduledEmail($interview, $candidateRouting);
    }

    private function sendCandidateApplicationReceivedEmail(array $candidate): void
    {
        $candidateEmail = trim((string) ($candidate['email'] ?? ''));
        if (!$this->isValidEmail($candidateEmail)) {
            error_log('[recruitment_mail] skip application_received: invalid candidate email, candidate_id=' . (int) ($candidate['candidate_id'] ?? 0));
            return;
        }

        $candidateName = (string) ($candidate['full_name'] ?? 'Bạn');
        $positionName = trim((string) ($candidate['position_name'] ?? 'Vị trí ứng tuyển'));
        if ($positionName === '') {
            $positionName = 'Vị trí ứng tuyển';
        }

        $subject = sprintf(
            'ATE HRM – Xác nhận tiếp nhận hồ sơ ứng tuyển – Vị trí %s – %s',
            $positionName,
            $candidateName
        );
        $textBody = "Chào {$candidateName},\n\n"
            . "Cảm ơn bạn đã quan tâm và dành thời gian gửi hồ sơ ứng tuyển cho vị trí {$positionName} tại ATE HRM.\n\n"
            . "Bộ phận Tuyển dụng xác nhận đã nhận được CV và các tài liệu liên quan của bạn. "
            . "Hiện tại, chúng tôi đang trong quá trình xem xét hồ sơ của các ứng viên. "
            . "Nếu hồ sơ của bạn phù hợp với các tiêu chí mà công ty đang tìm kiếm, "
            . "chúng tôi sẽ liên hệ với bạn trong vòng 5-7 ngày làm việc để trao đổi thêm về lịch phỏng vấn.\n\n"
            . "Một lần nữa, cảm ơn bạn đã lựa chọn ATE HRM là nơi phát triển sự nghiệp. Chúc bạn một ngày làm việc hiệu quả!\n\n"
            . "Trân trọng,\n"
            . "Trần Thị Mai\n"
            . "Bộ phận tuyển dụng – ATE HRM";
        $htmlBody = '<p>Chào <strong>' . htmlspecialchars($candidateName) . '</strong>,</p>'
            . '<p>Cảm ơn bạn đã quan tâm và dành thời gian gửi hồ sơ ứng tuyển cho vị trí <strong>'
            . htmlspecialchars($positionName) . '</strong> tại <strong>ATE HRM</strong>.</p>'
            . '<p>Bộ phận Tuyển dụng xác nhận đã nhận được CV và các tài liệu liên quan của bạn. '
            . 'Hiện tại, chúng tôi đang trong quá trình xem xét hồ sơ của các ứng viên. '
            . 'Nếu hồ sơ của bạn phù hợp với các tiêu chí mà công ty đang tìm kiếm, '
            . 'chúng tôi sẽ liên hệ với bạn trong vòng <strong>5-7 ngày làm việc</strong> để trao đổi thêm về lịch phỏng vấn.</p>'
            . '<p>Một lần nữa, cảm ơn bạn đã lựa chọn ATE HRM là nơi phát triển sự nghiệp. '
            . 'Chúc bạn một ngày làm việc hiệu quả!</p>'
            . '<p>Trân trọng,<br>'
            . 'Trần Thị Mai<br>'
            . 'Bộ phận tuyển dụng – ATE HRM</p>';

        $this->sendWorkflowEmail($candidateEmail, $subject, $textBody, $htmlBody);
    }

    private function sendCandidateInterviewScheduledEmail(array $interview, array $candidateRouting): void
    {
        $candidateEmail = trim((string) ($candidateRouting['candidate_email'] ?? ''));
        if (!$this->isValidEmail($candidateEmail)) {
            error_log('[recruitment_mail] skip interview_invitation: invalid candidate email, candidate_id=' . (int) ($candidateRouting['candidate_id'] ?? 0));
            return;
        }

        $candidateName = (string) ($candidateRouting['candidate_name'] ?? 'Bạn');
        $positionName = (string) ($candidateRouting['position_name'] ?? 'vị trí ứng tuyển');
        $departmentName = (string) ($candidateRouting['department_name'] ?? 'bộ phận tuyển dụng');
        $interviewerName = trim((string) ($candidateRouting['department_manager_name'] ?? 'Trưởng phòng'));
        $interviewDateRaw = (string) ($interview['interview_date'] ?? '');
        $interviewTimeRaw = (string) ($interview['interview_time'] ?? '');
        $dateLabel = $this->formatDateForMail($interviewDateRaw);
        $timeLabel = $this->formatTimeForMail($interviewTimeRaw);
        $interviewMode = strtoupper(trim((string) ($interview['interview_mode'] ?? 'ONLINE')));
        $modeLabel = $interviewMode === 'OFFLINE' ? 'Trực tiếp tại văn phòng' : 'Trực tuyến qua Google Meet/Zoom';
        $meetingLink = trim((string) ($interview['meeting_link'] ?? ''));
        $location = trim((string) ($interview['location'] ?? ''));
        if ($interviewMode === 'OFFLINE' && $location === '') {
            $location = '193 Đỗ Văn Thi, Phường Trấn Biên, Đồng Nai';
        }

        $subject = sprintf(
            'ATE HRM – Thư mời phỏng vấn – Vị trí %s – %s',
            $positionName,
            $candidateName
        );
        $textBody = "Chào {$candidateName},\n\n"
            . "Cảm ơn bạn đã quan tâm đến cơ hội nghề nghiệp tại ATE HRM. "
            . "Qua việc xem xét hồ sơ, chúng tôi rất ấn tượng với kinh nghiệm của bạn và muốn mời bạn tham gia một buổi phỏng vấn "
            . "để trao đổi chi tiết hơn về vị trí {$positionName}.\n\n"
            . "Dưới đây là thông tin chi tiết về buổi phỏng vấn:\n"
            . "Thời gian: {$timeLabel}, ngày {$dateLabel}\n"
            . "Hình thức: {$modeLabel}\n"
            . ($location !== '' ? "Địa điểm: {$location}\n" : '')
            . "Người phỏng vấn: {$interviewerName} – Trưởng phòng {$departmentName}\n"
            . "Nội dung chuẩn bị: Vui lòng mang theo laptop/portfolio hoặc chuẩn bị bài test 15 phút (nếu có).\n"
            . ($meetingLink !== '' ? "Lưu ý nhỏ: Link meeting {$meetingLink}. Bạn vui lòng kiểm tra kết nối mạng và micro 5 phút trước khi bắt đầu nhé.\n" : '')
            . "\nBạn vui lòng phản hồi email này để xác nhận tham gia hoặc đề xuất khung giờ khác nếu lịch trình trên không thuận tiện.\n\n"
            . "Rất mong được gặp và trao đổi cùng bạn.\n\n"
            . "Trân trọng,\n"
            . "Trần Thị Mai\n"
            . "Bộ phận tuyển dụng – ATE HRM";
        $htmlBody = '<p>Chào <strong>' . htmlspecialchars($candidateName) . '</strong>,</p>'
            . '<p>Cảm ơn bạn đã quan tâm đến cơ hội nghề nghiệp tại <strong>ATE HRM</strong>. '
            . 'Qua việc xem xét hồ sơ, chúng tôi rất ấn tượng với kinh nghiệm của bạn và muốn mời bạn tham gia một buổi phỏng vấn '
            . 'để trao đổi chi tiết hơn về vị trí <strong>' . htmlspecialchars($positionName) . '</strong>.</p>'
            . '<p><strong>Thời gian:</strong> ' . htmlspecialchars($timeLabel . ', ngày ' . $dateLabel) . '<br>'
            . '<strong>Hình thức:</strong> ' . htmlspecialchars($modeLabel) . '<br>'
            . ($location !== '' ? '<strong>Địa điểm:</strong> ' . htmlspecialchars($location) . '<br>' : '')
            . '<strong>Người phỏng vấn:</strong> ' . htmlspecialchars($interviewerName) . ' – Trưởng phòng ' . htmlspecialchars($departmentName) . '<br>'
            . '<strong>Nội dung chuẩn bị:</strong> Vui lòng mang theo laptop/portfolio hoặc chuẩn bị bài test 15 phút (nếu có).'
            . '</p>'
            . ($meetingLink !== '' ? '<p><strong>Lưu ý nhỏ:</strong> Link meeting <a href="' . htmlspecialchars($meetingLink) . '">' . htmlspecialchars($meetingLink) . '</a>. Bạn vui lòng kiểm tra kết nối mạng và micro 5 phút trước khi bắt đầu nhé.</p>' : '')
            . '<p>Bạn vui lòng phản hồi email này để xác nhận tham gia hoặc đề xuất khung giờ khác nếu lịch trình trên không thuận tiện.</p>'
            . '<p>Rất mong được gặp và trao đổi cùng bạn.</p>'
            . '<p>Trân trọng,<br>'
            . 'Trần Thị Mai<br>'
            . 'Bộ phận tuyển dụng – ATE HRM</p>';

        $this->sendWorkflowEmail($candidateEmail, $subject, $textBody, $htmlBody);
    }

    private function sendDepartmentManagerInterviewScheduledEmail(array $interview, array $candidateRouting): void
    {
        $managerEmail = trim((string) ($candidateRouting['department_manager_email'] ?? ''));
        if (!$this->isValidEmail($managerEmail)) {
            return;
        }

        $managerName = trim((string) ($candidateRouting['department_manager_name'] ?? 'Trưởng phòng'));
        $candidateName = (string) ($candidateRouting['candidate_name'] ?? 'Ứng viên');
        $positionName = (string) ($candidateRouting['position_name'] ?? 'vị trí tuyển dụng');
        $departmentName = (string) ($candidateRouting['department_name'] ?? 'phòng ban');
        $dateTimeLabel = $this->normalizeInterviewDateTime(
            (string) ($interview['interview_date'] ?? ''),
            (string) ($interview['interview_time'] ?? '')
        );

        $subject = '[AET HRM] Thông báo lịch phỏng vấn mới';
        $textBody = "Xin chào {$managerName},\n\n"
            . "HR vừa lên lịch phỏng vấn cho ứng viên {$candidateName}.\n"
            . "Vị trí: {$positionName} ({$departmentName})\n"
            . "Thời gian: {$dateTimeLabel}\n\n"
            . "Vui lòng vào hệ thống để đánh giá và phản hồi cho HR sau buổi phỏng vấn.\n\n"
            . "Trân trọng,\nAET Recruitment Team";
        $htmlBody = '<p>Xin chào <strong>' . htmlspecialchars($managerName) . '</strong>,</p>'
            . '<p>HR vừa lên lịch phỏng vấn cho ứng viên <strong>' . htmlspecialchars($candidateName) . '</strong>.</p>'
            . '<p><strong>Vị trí:</strong> ' . htmlspecialchars($positionName) . ' (' . htmlspecialchars($departmentName) . ')<br>'
            . '<strong>Thời gian:</strong> ' . htmlspecialchars($dateTimeLabel) . '</p>'
            . '<p>Vui lòng vào hệ thống để đánh giá và phản hồi cho HR sau buổi phỏng vấn.</p>'
            . '<p>Trân trọng,<br>AET Recruitment Team</p>';

        $this->sendWorkflowEmail($managerEmail, $subject, $textBody, $htmlBody);
    }

    private function sendCandidateFinalDecisionEmail(array $candidate, string $normalizedStatus): void
    {
        $candidateEmail = trim((string) ($candidate['email'] ?? ''));
        if (!$this->isValidEmail($candidateEmail)) {
            error_log('[recruitment_mail] skip final_decision: invalid candidate email, candidate_id=' . (int) ($candidate['candidate_id'] ?? 0));
            return;
        }

        $candidateName = (string) ($candidate['full_name'] ?? 'Bạn');
        $positionName = (string) ($candidate['position_name'] ?? 'Vị trí ứng tuyển');
        $isPassed = strtoupper(trim($normalizedStatus)) === 'PASSED';

        if ($isPassed) {
            $startDateRaw = trim((string) ($candidate['latest_interview_date'] ?? ''));
            if ($startDateRaw === '') {
                $candidateId = (int) ($candidate['candidate_id'] ?? 0);
                if ($candidateId > 0) {
                    $latestInterview = $this->interviews->findLatestByCandidate($candidateId);
                    if (is_array($latestInterview)) {
                        $startDateRaw = trim((string) ($latestInterview['interview_date'] ?? ''));
                    }
                }
            }
            if ($startDateRaw === '') {
                $startDateRaw = trim((string) ($candidate['applied_at'] ?? date('Y-m-d')));
            }
            $startDate = $this->formatDateForMail($startDateRaw);
            $responseDeadlineDate = $this->addDaysForMail($startDateRaw, 3);
            $responseDeadline = '17:00 ngày ' . $responseDeadlineDate;
            $officeAddress = '193 Đỗ Văn Thi, Phường Trấn Biên, Đồng Nai';

            $subject = sprintf(
                'ATE HRM – Thư mời làm việc (Job Offer) – Vị trí %s – %s',
                $positionName,
                $candidateName
            );
            $textBody = "Chào {$candidateName},\n\n"
                . "Sau quá trình phỏng vấn và đánh giá, ATE HRM rất ấn tượng với năng lượng cũng như những kinh nghiệm mà bạn đã chia sẻ. "
                . "Thay mặt công ty, tôi rất vui mừng thông báo bạn đã chính thức trúng tuyển vào vị trí {$positionName}.\n\n"
                . "Chúng tôi tin rằng với năng lực của mình, bạn sẽ là một mảnh ghép tuyệt vời giúp đội ngũ của chúng ta phát triển mạnh mẽ hơn nữa.\n\n"
                . "Dưới đây là một số thông tin sơ bộ về lời mời làm việc:\n"
                . "Ngày bắt đầu dự kiến: {$startDate}\n"
                . "Địa điểm làm việc: {$officeAddress}\n"
                . "Chi tiết về lương và phúc lợi: (Chi tiết đính kèm trong file Offer Letter bên dưới)\n\n"
                . "Bạn vui lòng phản hồi email này trước {$responseDeadline} để xác nhận việc tiếp nhận lời mời làm việc nhé. "
                . "Sau khi nhận được xác nhận, tôi sẽ hướng dẫn bạn các thủ tục nhận việc cần thiết.\n\n"
                . "Chào mừng bạn gia nhập đại gia đình ATE HRM!\n\n"
                . "Trân trọng,\n"
                . "Trần Thị Mai\n"
                . "Bộ phận tuyển dụng – ATE HRM";
            $htmlBody = '<p>Chào <strong>' . htmlspecialchars($candidateName) . '</strong>,</p>'
                . '<p>Sau quá trình phỏng vấn và đánh giá, <strong>ATE HRM</strong> rất ấn tượng với năng lượng cũng như những kinh nghiệm mà bạn đã chia sẻ. '
                . 'Thay mặt công ty, tôi rất vui mừng thông báo bạn đã chính thức trúng tuyển vào vị trí <strong>' . htmlspecialchars($positionName) . '</strong>.</p>'
                . '<p>Chúng tôi tin rằng với năng lực của mình, bạn sẽ là một mảnh ghép tuyệt vời giúp đội ngũ của chúng ta phát triển mạnh mẽ hơn nữa.</p>'
                . '<p><strong>Ngày bắt đầu dự kiến:</strong> ' . htmlspecialchars($startDate) . '<br>'
                . '<strong>Địa điểm làm việc:</strong> ' . htmlspecialchars($officeAddress) . '<br>'
                . '<strong>Chi tiết về lương và phúc lợi:</strong> (Chi tiết đính kèm trong file Offer Letter bên dưới)</p>'
                . '<p>Bạn vui lòng phản hồi email này trước <strong>' . htmlspecialchars($responseDeadline) . '</strong> để xác nhận việc tiếp nhận lời mời làm việc nhé. '
                . 'Sau khi nhận được xác nhận, tôi sẽ hướng dẫn bạn các thủ tục nhận việc cần thiết.</p>'
                . '<p>Chào mừng bạn gia nhập đại gia đình ATE HRM!</p>'
                . '<p>Trân trọng,<br>'
                . 'Trần Thị Mai<br>'
                . 'Bộ phận tuyển dụng – ATE HRM</p>';
            $this->sendWorkflowEmail($candidateEmail, $subject, $textBody, $htmlBody);
            return;
        }

        $subject = sprintf(
            'ATE HRM – Kết quả ứng tuyển vị trí %s – %s',
            $positionName,
            $candidateName
        );
        $textBody = "Chào {$candidateName},\n\n"
            . "Lời đầu tiên, ATE HRM xin cảm ơn bạn đã dành thời gian và tâm huyết tham gia vào quy trình tuyển dụng của chúng tôi cho vị trí {$positionName}.\n\n"
            . "Hội đồng tuyển dụng đánh giá cao nền tảng kiến thức và thái độ chuyên nghiệp của bạn. "
            . "Tuy nhiên, ở thời điểm hiện tại, chúng tôi đang ưu tiên những ứng viên có sự tương quan sát nhất với một số yêu cầu đặc thù của dự án. "
            . "Vì vậy, rất tiếc chúng tôi chưa thể đồng hành cùng bạn trong đợt này.\n\n"
            . "Chúng tôi sẽ lưu hồ sơ của bạn vào \"Nguồn ứng viên tiềm năng\" và sẽ chủ động liên hệ nếu có những cơ hội khác phù hợp hơn trong tương lai.\n\n"
            . "Chúc bạn sớm tìm được bến đỗ mới phù hợp và gặt hái được nhiều thành công trên con đường sự nghiệp của mình.\n\n"
            . "Trân trọng,\n"
            . "Trần Thị Mai\n"
            . "Bộ phận tuyển dụng – ATE HRM";
        $htmlBody = '<p>Chào <strong>' . htmlspecialchars($candidateName) . '</strong>,</p>'
            . '<p>Lời đầu tiên, <strong>ATE HRM</strong> xin cảm ơn bạn đã dành thời gian và tâm huyết tham gia vào quy trình tuyển dụng của chúng tôi cho vị trí <strong>'
            . htmlspecialchars($positionName) . '</strong>.</p>'
            . '<p>Hội đồng tuyển dụng đánh giá cao nền tảng kiến thức và thái độ chuyên nghiệp của bạn. '
            . 'Tuy nhiên, ở thời điểm hiện tại, chúng tôi đang ưu tiên những ứng viên có sự tương quan sát nhất với một số yêu cầu đặc thù của dự án. '
            . 'Vì vậy, rất tiếc chúng tôi chưa thể đồng hành cùng bạn trong đợt này.</p>'
            . '<p>Chúng tôi sẽ lưu hồ sơ của bạn vào "Nguồn ứng viên tiềm năng" và sẽ chủ động liên hệ nếu có những cơ hội khác phù hợp hơn trong tương lai.</p>'
            . '<p>Chúc bạn sớm tìm được bến đỗ mới phù hợp và gặt hái được nhiều thành công trên con đường sự nghiệp của mình.</p>'
            . '<p>Trân trọng,<br>'
            . 'Trần Thị Mai<br>'
            . 'Bộ phận tuyển dụng – ATE HRM</p>';
        $this->sendWorkflowEmail($candidateEmail, $subject, $textBody, $htmlBody);
    }

    private function hasInterviewScheduleChanged(array $existing, array $payload): bool
    {
        if (isset($payload['interview_date']) && trim((string) $payload['interview_date']) !== trim((string) ($existing['interview_date'] ?? ''))) {
            return true;
        }
        if (isset($payload['interview_time']) && trim((string) $payload['interview_time']) !== trim((string) ($existing['interview_time'] ?? ''))) {
            return true;
        }
        if (isset($payload['interview_mode']) && trim((string) $payload['interview_mode']) !== trim((string) ($existing['interview_mode'] ?? ''))) {
            return true;
        }
        if (isset($payload['meeting_link']) && trim((string) $payload['meeting_link']) !== trim((string) ($existing['meeting_link'] ?? ''))) {
            return true;
        }
        if (isset($payload['location']) && trim((string) $payload['location']) !== trim((string) ($existing['location'] ?? ''))) {
            return true;
        }
        return false;
    }

    private function notifyHrAfterManagerReview(array $candidate, array $review, ?int $actorId): void
    {
        $receiverIds = $this->findEmployeeIdsByRoleCodes(['HR', 'ADMIN']);
        if ($receiverIds === []) {
            return;
        }

        $candidateId = (int) ($candidate['candidate_id'] ?? 0);
        $candidateName = (string) ($candidate['full_name'] ?? 'Ứng viên');
        $positionName = (string) ($candidate['position_name'] ?? 'vị trí tuyển dụng');
        $workflowStatus = strtoupper(trim((string) ($review['workflow_status'] ?? 'PENDING')));
        $decisionProposal = strtoupper(trim((string) ($review['manager_decision_proposal'] ?? 'PENDING')));
        $score = $review['manager_score'] ?? null;
        $notes = trim((string) ($review['manager_review_notes'] ?? ''));
        $statusLabel = match (true) {
            $decisionProposal === 'PASS' => 'ĐỀ XUẤT ĐẠT',
            $decisionProposal === 'FAIL' => 'ĐỀ XUẤT KHÔNG ĐẠT',
            $workflowStatus === 'APPROVED' => 'ĐÃ ĐẠT SAU PHỎNG VẤN',
            $workflowStatus === 'REJECTED' => 'KHÔNG ĐẠT',
            default => 'CHỜ THẨM ĐỊNH',
        };

        $content = sprintf(
            'Trưởng phòng đã phản hồi ứng viên %s (%s): %s%s%s',
            $candidateName,
            $positionName,
            $statusLabel,
            $score !== null ? ', điểm: ' . (float) $score : '',
            $notes !== '' ? ', nhận xét: ' . $notes : ''
        );

        $employees = $this->findEmployeesByIds($receiverIds);
        foreach ($receiverIds as $receiverId) {
            if ($receiverId <= 0) {
                continue;
            }

            if ($actorId !== null && $actorId > 0 && $receiverId === $actorId) {
                continue;
            }

            $this->createRecruitmentNotification(
                $receiverId,
                'Trưởng phòng đã gửi kết quả phỏng vấn',
                $content,
                $actorId,
                '/admin/tuyendung'
            );

            $receiver = $employees[$receiverId] ?? null;
            $receiverEmail = trim((string) ($receiver['company_email'] ?? ''));
            if (!$this->isValidEmail($receiverEmail)) {
                continue;
            }

            $receiverName = (string) ($receiver['full_name'] ?? 'HR');
            $subject = '[AET HRM] Trưởng phòng đã gửi kết quả phỏng vấn';
            $textBody = "Xin chào {$receiverName},\n\n"
                . "{$content}\n"
                . "Vui lòng vào hệ thống để xử lý quyết định cuối cùng cho ứng viên.\n\n"
                . "Trân trọng,\nAET Recruitment System";
            $htmlBody = '<p>Xin chào <strong>' . htmlspecialchars($receiverName) . '</strong>,</p>'
                . '<p>' . htmlspecialchars($content) . '</p>'
                . '<p>Vui lòng vào hệ thống để xử lý quyết định cuối cùng cho ứng viên.</p>'
                . '<p>Trân trọng,<br>AET Recruitment System</p>';
            $this->sendWorkflowEmail($receiverEmail, $subject, $textBody, $htmlBody);
        }
    }

    private function notifyHrAfterInterviewReview(array $interview, int $actorId): void
    {
        $candidateId = (int) ($interview['candidate_id'] ?? 0);
        if ($candidateId <= 0) {
            return;
        }

        $managerDecision = strtoupper(trim((string) ($interview['manager_decision'] ?? 'PENDING')));
        $review = [
            'workflow_status' => $managerDecision === 'PASS' ? 'APPROVED' : ($managerDecision === 'FAIL' ? 'REJECTED' : 'PENDING'),
            'manager_decision_proposal' => $managerDecision,
            'manager_score' => null,
            'manager_review_notes' => (string) ($interview['manager_review_notes'] ?? $interview['evaluation_notes'] ?? ''),
        ];
        $candidate = $this->candidates->findDetail($candidateId);
        if (!is_array($candidate)) {
            return;
        }

        $this->notifyHrAfterManagerReview($candidate, $review, $actorId > 0 ? $actorId : null);
    }

    private function createRecruitmentNotification(
        int $receiverId,
        string $title,
        string $content,
        ?int $senderId,
        ?string $actionUrl = null
    ): void {
        try {
            $this->notifications->create([
                'notification_type' => 'RECRUITMENT_WORKFLOW',
                'title' => $title,
                'content' => $content,
                'sender_id' => ($senderId !== null && $senderId > 0) ? $senderId : null,
                'receiver_id' => $receiverId,
                'priority' => 'TRUNG_BÌNH',
                'reference_type' => null,
                'reference_id' => null,
                'action_url' => $actionUrl,
            ]);
        } catch (\Throwable) {
            // Best-effort notification only.
        }
    }

    /**
     * @param array<int, string> $roleCodes
     * @return array<int, int>
     */
    private function findEmployeeIdsByRoleCodes(array $roleCodes): array
    {
        if ($roleCodes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleCodes), '?'));
        $sql = "SELECT DISTINCT er.employee_id
                FROM employee_roles er
                JOIN roles r ON r.role_id = er.role_id
                WHERE r.role_code IN ($placeholders)
                  AND er.is_active = 1
                  AND (er.expiry_date IS NULL OR er.expiry_date >= CURDATE())";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(array_values($roleCodes));
        $rows = $stmt->fetchAll() ?: [];
        return array_values(array_unique(array_map(static fn(array $row): int => (int) ($row['employee_id'] ?? 0), $rows)));
    }

    /**
     * @param array<int, int> $employeeIds
     * @return array<int, array<string, mixed>>
     */
    private function findEmployeesByIds(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $sql = "SELECT employee_id, full_name, company_email
                FROM employees
                WHERE employee_id IN ($placeholders)";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(array_values($employeeIds));
        $rows = $stmt->fetchAll() ?: [];

        $mapped = [];
        foreach ($rows as $row) {
            $employeeId = (int) ($row['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                continue;
            }
            $mapped[$employeeId] = $row;
        }
        return $mapped;
    }

    private function normalizeInterviewDateTime(string $date, string $time): string
    {
        $date = trim($date);
        $time = trim($time);
        if ($date === '') {
            return 'Chưa xác định';
        }

        try {
            $safeTime = $time !== '' ? $time : '00:00:00';
            $dateTime = new \DateTime($date . ' ' . $safeTime);
            return $dateTime->format('d/m/Y H:i');
        } catch (\Throwable) {
            return trim($date . ' ' . $time);
        }
    }

    private function formatDateForMail(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return 'Chưa cập nhật';
        }

        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format('d/m/Y');
        } catch (\Throwable) {
            return $date;
        }
    }

    private function addDaysForMail(string $date, int $days): string
    {
        $date = trim($date);
        if ($date === '') {
            $date = date('Y-m-d');
        }

        try {
            $dateTime = new \DateTime($date);
            $dateTime->modify('+' . max(0, $days) . ' days');
            return $dateTime->format('d/m/Y');
        } catch (\Throwable) {
            return $this->formatDateForMail($date);
        }
    }

    private function formatTimeForMail(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return 'Chưa xác định';
        }

        try {
            $dateTime = new \DateTime('1970-01-01 ' . $time);
            return $dateTime->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values($decoded);
    }

    private function sendWorkflowEmail(string $to, string $subject, string $textBody, ?string $htmlBody = null): bool
    {
        try {
            $sent = $this->mailer->send($to, $subject, $textBody, $htmlBody);
            if (!$sent) {
                error_log('[recruitment_mail] send failed to=' . $to . ' subject=' . $subject);
            }
            return $sent;
        } catch (\Throwable $exception) {
            error_log('[recruitment_mail] exception to=' . $to . ' subject=' . $subject . ' error=' . $exception->getMessage());
            return false;
        }
    }

    private function isValidEmail(string $email): bool
    {
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
