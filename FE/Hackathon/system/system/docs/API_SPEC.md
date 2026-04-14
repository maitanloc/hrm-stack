# HRM API v1 (Pure PHP, No Framework)

Base URL:
- `/api/v1`

Response format:
```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "meta": {}
}
```

## 1) Auth

1. `POST /api/v1/auth/login`
- body:
```json
{
  "employee_code": "NV0001",
  "company_email": "an.nguyen@company.com"
}
```
- returns JWT + user + roles + permissions.

2. `GET /api/v1/auth/me` (Bearer required)
3. `POST /api/v1/auth/refresh` (Bearer required)

## 2) Health

1. `GET /api/v1/health`

## 3) Employees (Bearer required)

1. `GET /api/v1/employees`
- query: `page`, `per_page`, `q`, `status`, `department_id`
2. `GET /api/v1/employees/{id}`
3. `POST /api/v1/employees`
4. `PUT|PATCH /api/v1/employees/{id}`
5. `DELETE /api/v1/employees/{id}`

## 4) Departments (Bearer required)

1. `GET /api/v1/departments`
- query: `page`, `per_page`, `q`, `manager_id`
2. `GET /api/v1/departments/{id}`
3. `POST /api/v1/departments`
4. `PUT|PATCH /api/v1/departments/{id}`
5. `DELETE /api/v1/departments/{id}`

## 5) Request Types (Bearer required)

1. `GET /api/v1/request-types`
2. `GET /api/v1/request-types/{id}`
3. `POST /api/v1/request-types`
4. `PUT|PATCH /api/v1/request-types/{id}`
5. `DELETE /api/v1/request-types/{id}`

## 6) Requests (Bearer required)

1. `GET /api/v1/requests`
- query: `page`, `per_page`, `q`, `status`, `requester_id`, `request_type_id`
2. `GET /api/v1/requests/{id}`
3. `POST /api/v1/requests`
4. `PUT|PATCH /api/v1/requests/{id}`
5. `DELETE /api/v1/requests/{id}`

## 7) Leave (Bearer required)

1. `GET /api/v1/leave-requests`
- query: `page`, `per_page`, `employee_id`, `leave_type_id`, `date_from`, `date_to`
2. `GET /api/v1/leave-requests/{id}`
3. `POST /api/v1/leave-requests`
- creates both:
  - one record in `requests`
  - one record in `leave_requests`
4. `GET /api/v1/leave-balances`
- query: `page`, `per_page`, `employee_id`, `year`
5. `GET /api/v1/leave-balances/{id}`

## 8) Assets (Bearer required)

1. `GET /api/v1/assets`
- query: `page`, `per_page`, `q`, `status`
2. `GET /api/v1/assets/{id}`
3. `POST /api/v1/assets`
4. `PUT|PATCH /api/v1/assets/{id}`

## 9) Asset Assignments (Bearer required)

1. `GET /api/v1/asset-assignments`
- query: `page`, `per_page`, `employee_id`, `status`
2. `POST /api/v1/asset-assignments`

## 10) Attendance & Overtime (Bearer required)

1. Attendance
- `GET /api/v1/attendances`
  - query: `page`, `per_page`, `date_from`, `date_to`, `status`, `employee_id`, `scope=self|hierarchy`
- `GET /api/v1/attendances/{id}`
- `POST /api/v1/attendances`
- `PUT|PATCH /api/v1/attendances/{id}`
- `DELETE /api/v1/attendances/{id}`

2. Overtime
- `GET /api/v1/overtime-requests`
  - query: `page`, `per_page`, `date_from`, `date_to`, `status`, `employee_id`, `scope=self|hierarchy`
- `GET /api/v1/overtime-requests/{id}`
- `POST /api/v1/overtime-requests`
- `PUT|PATCH /api/v1/overtime-requests/{id}`
- `DELETE /api/v1/overtime-requests/{id}`

## 11) Payroll (Bearer required)

1. Salary periods
- `GET /api/v1/salary-periods`
- `GET /api/v1/salary-periods/{id}`
- `POST /api/v1/salary-periods`
- `PUT|PATCH /api/v1/salary-periods/{id}`

2. Salary details
- `GET /api/v1/salary-details`
  - query: `page`, `per_page`, `period_id`, `transfer_status`, `employee_id`, `scope=self|hierarchy`
- `GET /api/v1/salary-details/{id}`
- `POST /api/v1/salary-details`
- `PUT|PATCH /api/v1/salary-details/{id}`

3. Salary breakdowns
- `GET /api/v1/salary-breakdowns`
- `GET /api/v1/salary-breakdowns/{id}`
- `POST /api/v1/salary-breakdowns`
- `PUT|PATCH /api/v1/salary-breakdowns/{id}`
- `DELETE /api/v1/salary-breakdowns/{id}`

## 12) News & Policies (Bearer required)

1. News categories
- `GET /api/v1/news-categories`
- `GET /api/v1/news-categories/{id}`
- `POST /api/v1/news-categories`
- `PUT|PATCH /api/v1/news-categories/{id}`
- `DELETE /api/v1/news-categories/{id}`

2. News
- `GET /api/v1/news`
- `GET /api/v1/news/{id}`
- `POST /api/v1/news`
- `PUT|PATCH /api/v1/news/{id}`
- `DELETE /api/v1/news/{id}`
- `POST /api/v1/news/{id}/read`

3. Policies
- `GET /api/v1/policies`
- `GET /api/v1/policies/{id}`
- `POST /api/v1/policies`
- `PUT|PATCH /api/v1/policies/{id}`
- `DELETE /api/v1/policies/{id}`
- `POST /api/v1/policies/{id}/acknowledge`

## 13) Permission middleware (role_permissions)

Route-level permission checks are mapped to `role_permissions`:
- action `access/create/edit/delete/approve/export`
- permission code examples:
  - `ATTENDANCE_VIEW`, `ATTENDANCE_EDIT`
  - `SALARY_VIEW`, `SALARY_CALCULATE`, `SALARY_APPROVE`
  - `NEWS_VIEW`, `NEWS_CREATE`, `NEWS_EDIT`, `NEWS_DELETE`
  - `EMP_*`, `DEPARTMENT_*`, `ASSET_*`

## 14) Self-referencing hierarchy authorization

1. Auth payload includes:
- `managed_department_ids`
- `hierarchy_employee_ids`

2. Non-privileged users:
- default scope = self
- with `scope=hierarchy`, can access self + subordinate employees in department tree.

3. Endpoint:
- `GET /api/v1/auth/hierarchy` returns current hierarchy scope.

## 15) Validation, Error, Pagination

1. Validation errors return HTTP 422 with `error=validation_error`.
2. Auth failures return HTTP 401.
3. Not found returns HTTP 404.
4. List endpoints return pagination metadata:
```json
{
  "meta": {
    "total": 120,
    "page": 2,
    "per_page": 20,
    "last_page": 6
  }
}
```

## 16) Versioning strategy

- Version in URL: `/api/v1/...`
- Add future version side-by-side: `/api/v2/...` with separate route file.
