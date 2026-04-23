# UTF-8 Import And Recovery

## Root Cause

- Vietnamese text corruption is a data-import problem, not a frontend rendering bug.
- The current MySQL runtime and PHP PDO runtime are already using `utf8mb4`.
- Existing corrupted rows contain literal `?` (`0x3F`) in MySQL, which means the original characters were already lost before display.

## Approved Import Paths

- Local / repo root: `./import-db.sh`
- VPS deployment: `deploy/vps/import-db.sh`
- Smoke test import: `BE/scripts/seed_test.ps1` or `php BE/scripts/seed_test.php`

All approved paths now:

- validate that SQL input files are valid UTF-8 before import
- force MySQL client charset with `--default-character-set=utf8mb4`
- prepend `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`
- set `character_set_client`, `character_set_connection`, and `character_set_results` to `utf8mb4`

## Blocked Unsafe Import Paths

The following ad-hoc scripts are intentionally blocked because they bypass UTF-8 validation and previously imported through unsafe client defaults:

- `deploy_temp/clean_deploy.js`
- `deploy_temp/db_fix.js`
- `deploy_temp/final_fix.js`
- `deploy_temp/fix_remote_db.js`
- `deploy_temp/robust_fix.js`
- `deploy_temp/wipe_fix.js`

Use the approved import scripts instead.

## Current Source Quality

- Missing in current working tree:
  - `SQL_hackathon v4.sql`
  - `data.sql`
- Clean UTF-8 reference available:
  - `BE/scripts/test_accounts_by_role.md`
  - `BE/scripts/test_accounts.tsv`
- Partial issue in reference TSV:
  - employee names and department labels are clean UTF-8
  - `employee_status` is mojibake (`ÄANG...`) but still recoverable with deterministic decoding

## Recovery Strategy

### If clean base SQL becomes available

1. Remove old corrupted data volume / database.
2. Re-import from clean UTF-8 `SQL_hackathon v4.sql` and `data.sql` using the approved import script.
3. Run smoke test import (`seed_test.sql`) to verify Vietnamese round-trip.
4. Verify user-facing tables with `php BE/scripts/audit_vietnamese_seed_data.php`.

### If only current repo contents are available

Use `php BE/scripts/repair_vietnamese_seed_data.php --apply`.

What this script repairs safely:

- `employees.full_name` and `employees.status` from `test_accounts.tsv`
- `departments.department_name` from deterministic `department_code -> Vietnamese name`
- `positions.position_name` from deterministic `position_code -> Vietnamese name`
- `request_types.request_type_name`, `description`, and `category` from deterministic `request_type_code -> Vietnamese name`
- known enum/status values that are fully determined by schema/business codes

What it cannot promise:

- any row where the original Vietnamese text was replaced by `?` and no clean source truth exists
- free-text descriptions/notes without a clean reference file

## Verification Commands

- Runtime / source audit:
  - `docker exec hrm-be php scripts/audit_vietnamese_seed_data.php`
- Deterministic repair preview:
  - `docker exec hrm-be php scripts/repair_vietnamese_seed_data.php`
- Deterministic repair apply:
  - `docker exec hrm-be php scripts/repair_vietnamese_seed_data.php --apply`
- Smoke test import:
  - `powershell -ExecutionPolicy Bypass -File .\BE\scripts\seed_test.ps1`
  - `docker exec hrm-be php scripts/seed_test.php`

## Regression Checklist

- `@@character_set_server`, `@@character_set_database`, and connection charset are `utf8mb4`
- base SQL files are validated as UTF-8 before import
- no ad-hoc import runs through `mysql < file.sql` or `source file.sql` without utf8mb4 session bootstrap
- `SELECT employee_code, full_name FROM employees` returns Vietnamese correctly
- `HEX(sample_text)` for smoke test rows does not contain `3F` in place of Vietnamese characters
- API responses return correct Vietnamese after DB repair/reseed
