# HRM System API (Pure PHP)

This folder contains a framework-free PHP API implementing:
- Routing
- MVC structure
- RESTful JSON API
- Request handling
- PDO database access
- Middleware (CORS, Auth, Permission, Hierarchy scope)
- Error handling
- JWT authentication
- Validation
- Pagination
- API versioning (`/api/v1`)
- Self-referencing hierarchy authorization (manager -> child departments -> subordinates)

## 1) Prerequisites

1. PHP 8.1+
2. MySQL 8.x
3. Imported schema/data:
- `SQL_hackathon v4.sql`
- `data.sql`

## 2) Setup

1. Copy `.env.example` to `.env` and update DB/JWT values.
2. Run local server from `system` folder:

```bash
php -S 127.0.0.1:8080 -t public
```

API base:
- `http://127.0.0.1:8080/api/v1`

## 3) Auto seed test data

1. Using mysql CLI:
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\seed_test.ps1 -HostName 127.0.0.1 -Port 3306 -Database HRM_SYSTEM -Username root -Password ""
```

2. Using PHP script:
```bash
php scripts/seed_test.php
```

## 4) Quick test

1. Health check:
```bash
curl http://127.0.0.1:8080/api/v1/health
```

2. Login:
```bash
curl -X POST http://127.0.0.1:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"employee_code\":\"NV0001\",\"company_email\":\"an.nguyen@company.com\"}"
```

3. Use token:
```bash
curl http://127.0.0.1:8080/api/v1/employees \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

## 5) Postman

- Collection: `postman/HRM_API_v1.postman_collection.json`
- Environment: `postman/HRM_API_v1.postman_environment.json`

## 6) Documentation

- Business summary: `docs/BUSINESS_SPEC.md`
- API spec: `docs/API_SPEC.md`

## 7) Import nhân viên thử việc từ Excel/CSV

Endpoint:
- `POST /api/v1/employees/import-probation`
- Auth: Bearer token
- Content-Type: `multipart/form-data`

Form-data fields:
- `file` (required): file `.xlsx` hoặc `.csv`
- `dry_run` (optional): `true|false` (mặc định `false`)
- `create_contract` (optional): `true|false` (mặc định `true`)
- `create_history` (optional): `true|false` (mặc định `true`)

Template mẫu:
- `docs/probation_employees_template.csv`

Ví dụ curl:
```bash
curl -X POST http://127.0.0.1:8080/api/v1/employees/import-probation \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -F "file=@docs/probation_employees_template.csv" \
  -F "dry_run=true"
```

Ghi chú:
- Bắt buộc có cột: `employee_code`, `full_name`, `company_email`, `hire_date`.
- API tự set `status = THỬ_VIỆC`.
- Nếu `create_contract=true`, hệ thống sẽ tự lấy `contract_types.is_probation=1` để tạo hợp đồng thử việc.
