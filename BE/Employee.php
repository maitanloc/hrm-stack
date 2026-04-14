<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Employee extends Model
{
    protected string $table = 'employees';
    protected string $primaryKey = 'employee_id';
    protected array $fillable = [
        'employee_code',
        'full_name',
        'date_of_birth',
        'gender',
        'place_of_birth',
        'ethnicity',
        'religion',
        'marital_status',
        'phone_number',
        'personal_email',
        'company_email',
        'permanent_address',
        'current_address',
        'avatar_url',
        'bank_account',
        'bank_id',
        'bank_branch',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'password_hash',
        'nationality_id',
        'status',
        'hire_date',
        'seniority_start_date',
        'base_leave_days',
        'resignation_date',
        'machine_user_id',
        'created_by',
        'updated_by',
    ];

    public function paginateList(
        int $offset,
        int $limit,
        ?string $search = null,
        ?string $status = null,
        ?int $departmentId = null,
        ?array $employeeIds = null
    ): array
    {
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(e.employee_code LIKE :search OR e.full_name LIKE :search OR e.company_email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($status !== null && $status !== '') {
            $where[] = 'e.status = :status';
            $params['status'] = $status;
        }
        if ($departmentId !== null) {
            $where[] = 'd.department_id = :department_id';
            $params['department_id'] = $departmentId;
        }
        if (is_array($employeeIds) && $employeeIds !== []) {
            $inParams = [];
            foreach (array_values($employeeIds) as $index => $id) {
                $key = 'emp_id_' . $index;
                $inParams[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'e.employee_id IN (' . implode(', ', $inParams) . ')';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT e.employee_id, e.employee_code, e.full_name, e.company_email, e.phone_number, e.status, e.hire_date,
                       d.department_id, d.department_name
                FROM employees e
                LEFT JOIN employment_histories eh
                  ON eh.employee_id = e.employee_id AND eh.is_current = 1
                LEFT JOIN departments d
                  ON d.department_id = eh.department_id
                $whereSql
                ORDER BY e.employee_id ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, (string) $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) AS total
                     FROM employees e
                     LEFT JOIN employment_histories eh
                       ON eh.employee_id = e.employee_id AND eh.is_current = 1
                     LEFT JOIN departments d
                       ON d.department_id = eh.department_id
                     $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue(':' . $key, (string) $value, PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function findWithDepartment(int $id): ?array
    {
        $sql = "SELECT e.*,
                       d.department_id,
                       d.department_name,
                       p.position_id,
                       p.position_name
                FROM employees e
                LEFT JOIN employment_histories eh
                  ON eh.employee_id = e.employee_id AND eh.is_current = 1
                LEFT JOIN departments d
                  ON d.department_id = eh.department_id
                LEFT JOIN positions p
                  ON p.position_id = eh.position_id
                WHERE e.employee_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function existsByEmployeeCode(string $employeeCode): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM employees WHERE employee_code = :employee_code LIMIT 1');
        $stmt->execute(['employee_code' => $employeeCode]);
        return $stmt->fetch() !== false;
    }

    public function existsByCompanyEmail(string $companyEmail): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM employees WHERE company_email = :company_email LIMIT 1');
        $stmt->execute(['company_email' => $companyEmail]);
        return $stmt->fetch() !== false;
    }

    public function probationContractType(): ?array
    {
        $sql = "SELECT contract_type_id, max_duration_months
                FROM contract_types
                WHERE is_probation = 1
                ORDER BY contract_type_id ASC
                LIMIT 1";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function createContract(array $payload): int
    {
        $sql = "INSERT INTO contracts (
                    contract_code,
                    employee_id,
                    contract_type_id,
                    contract_number,
                    sign_date,
                    effective_date,
                    expiry_date,
                    position_id,
                    department_id,
                    basic_salary,
                    gross_salary,
                    net_salary,
                    work_location,
                    job_title,
                    status,
                    created_by,
                    updated_by
                ) VALUES (
                    :contract_code,
                    :employee_id,
                    :contract_type_id,
                    :contract_number,
                    :sign_date,
                    :effective_date,
                    :expiry_date,
                    :position_id,
                    :department_id,
                    :basic_salary,
                    :gross_salary,
                    :net_salary,
                    :work_location,
                    :job_title,
                    :status,
                    :created_by,
                    :updated_by
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    public function createEmploymentHistory(array $payload): int
    {
        $sql = "INSERT INTO employment_histories (
                    employee_id,
                    department_id,
                    position_id,
                    start_date,
                    end_date,
                    is_current,
                    notes,
                    created_by
                ) VALUES (
                    :employee_id,
                    :department_id,
                    :position_id,
                    :start_date,
                    :end_date,
                    :is_current,
                    :notes,
                    :created_by
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    public function findCurrentEmployment(int $employeeId): ?array
    {
        $sql = "SELECT *
                FROM employment_histories
                WHERE employee_id = :employee_id
                  AND is_current = 1
                ORDER BY history_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function setCurrentEmployment(
        int $employeeId,
        int $departmentId,
        int $positionId,
        string $startDate,
        ?int $actorId = null,
        ?string $notes = null
    ): void {
        $current = $this->findCurrentEmployment($employeeId);
        if ($current !== null) {
            $sameDepartment = (int) ($current['department_id'] ?? 0) === $departmentId;
            $samePosition = (int) ($current['position_id'] ?? 0) === $positionId;
            if ($sameDepartment && $samePosition) {
                return;
            }

            $closeStmt = $this->db->prepare(
                "UPDATE employment_histories
                 SET is_current = 0,
                     end_date = COALESCE(end_date, :end_date)
                 WHERE history_id = :history_id"
            );
            $closeStmt->execute([
                'end_date' => date('Y-m-d'),
                'history_id' => (int) ($current['history_id'] ?? 0),
            ]);
        }

        $this->createEmploymentHistory([
            'employee_id' => $employeeId,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'start_date' => $startDate,
            'end_date' => null,
            'is_current' => 1,
            'notes' => $notes,
            'created_by' => $actorId,
        ]);
    }

    public function listEmploymentHistories(int $employeeId, int $limit = 30): array
    {
        $sql = "SELECT eh.history_id,
                       eh.employee_id,
                       eh.department_id,
                       d.department_name,
                       eh.position_id,
                       p.position_name,
                       eh.start_date,
                       eh.end_date,
                       eh.is_current,
                       eh.notes,
                       eh.created_by,
                       eh.created_at
                FROM employment_histories eh
                LEFT JOIN departments d ON d.department_id = eh.department_id
                LEFT JOIN positions p ON p.position_id = eh.position_id
                WHERE eh.employee_id = :employee_id
                ORDER BY eh.is_current DESC, eh.start_date DESC, eh.history_id DESC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, min($limit, 200)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function listCertificates(int $employeeId): array
    {
        $sql = "SELECT c.certificate_id,
                       c.employee_id,
                       c.certificate_type_id,
                       ct.certificate_type_name,
                       c.certificate_name,
                       c.issued_by,
                       c.issued_date,
                       c.expiry_date,
                       c.certificate_number,
                       c.score,
                       c.file_url,
                       c.created_at,
                       c.updated_at
                FROM certificates c
                LEFT JOIN certificate_types ct ON ct.certificate_type_id = c.certificate_type_id
                WHERE c.employee_id = :employee_id
                ORDER BY c.issued_date DESC, c.certificate_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);
        return $stmt->fetchAll() ?: [];
    }

    public function listCertificateTypes(): array
    {
        $sql = "SELECT certificate_type_id, certificate_type_code, certificate_type_name
                FROM certificate_types
                ORDER BY certificate_type_name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function createCertificate(int $employeeId, array $payload): int
    {
        $sql = "INSERT INTO certificates (
                    employee_id,
                    certificate_type_id,
                    certificate_name,
                    issued_by,
                    issued_date,
                    expiry_date,
                    certificate_number,
                    score,
                    file_url
                ) VALUES (
                    :employee_id,
                    :certificate_type_id,
                    :certificate_name,
                    :issued_by,
                    :issued_date,
                    :expiry_date,
                    :certificate_number,
                    :score,
                    :file_url
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'certificate_type_id' => $payload['certificate_type_id'] ?? null,
            'certificate_name' => (string) ($payload['certificate_name'] ?? ''),
            'issued_by' => $payload['issued_by'] ?? null,
            'issued_date' => $payload['issued_date'] ?? null,
            'expiry_date' => $payload['expiry_date'] ?? null,
            'certificate_number' => $payload['certificate_number'] ?? null,
            'score' => $payload['score'] ?? null,
            'file_url' => $payload['file_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteCertificateById(int $employeeId, int $certificateId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM certificates
             WHERE certificate_id = :certificate_id
               AND employee_id = :employee_id'
        );
        return $stmt->execute([
            'certificate_id' => $certificateId,
            'employee_id' => $employeeId,
        ]);
    }
}
