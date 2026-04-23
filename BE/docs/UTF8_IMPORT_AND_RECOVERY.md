# UTF-8 Import And Recovery

## Current State

- Vietnamese corruption is stored in the database itself, not just rendered incorrectly in the UI.
- Runtime MySQL and backend connections are already `utf8mb4`.
- A full clean reseed is currently blocked because the working `data.sql` source is text-corrupted.
- `hrm-stack.tar.gz` is the approved local recovery source for:
  - clean files that remain valid UTF-8
  - archived mojibake rows that can be deterministically decoded

## Approved Recovery Workflow

1. Run a full audit:
   - `php BE/scripts/audit_vietnamese_data.php --source-archive=hrm-stack.tar.gz`
2. Review the generated reports under `scratch/vietnamese-audit`.
3. Run a repair preview:
   - `php BE/scripts/repair_vietnamese_data.php --source-archive=hrm-stack.tar.gz --dry-run`
4. Apply the repair with automatic backups:
   - `php BE/scripts/repair_vietnamese_data.php --source-archive=hrm-stack.tar.gz --backup-dir=scratch/db-backups --apply`
5. Review:
   - `scratch/vietnamese-repair/repair_plan.json`
   - `scratch/vietnamese-repair/applied_changes.json`
   - `scratch/vietnamese-repair/unrecoverable_rows.json`
   - `scratch/vietnamese-repair/repair_report.md`

Legacy wrappers are still available, but they now forward to the new system-wide scripts:

- `php BE/scripts/audit_vietnamese_seed_data.php`
- `php BE/scripts/repair_vietnamese_seed_data.php`

## Source Policy

Recovery sources are used in this order:

1. Clean repo files
2. Clean files inside `hrm-stack.tar.gz`
3. Archived `data.sql` rows that are deterministically decodable from mojibake
4. Deterministic code-to-label mappings
5. Otherwise mark the row unrecoverable exactly

Never invent Vietnamese text for rows where the original characters have already been lost and no trusted source remains.

## What Can Be Repaired Safely

- `employees.full_name`
  - `NV0001`..`NV0020`, `E999`, `SEED*` from `BE/scripts/test_accounts.tsv`
  - `NV0021`..`NV0130` from decoded archived `data.sql`
- `shift_types.shift_name` from deterministic `shift_code`
- `leave_types.leave_type_name` from deterministic `leave_type_code`
- `news.title`, `news.summary`, `news.content` from decoded archived `data.sql`
- `news.status` -> `ĐÃ_XUẤT_BẢN` where corrupted
- `news.priority` -> `TRUNG_BÌNH` where corrupted
- `notifications.title`, `notifications.content` from decoded archived `data.sql`
- `notifications.priority` -> `TRUNG_BÌNH` where corrupted
- `report_templates.template_name` and `report_templates.columns_config` from deterministic mappings
- `report_templates.sql_query`
  - only `RP_DEPARTMENT` is repaired
  - SQL parameter `?` placeholders in other rows are left untouched

## What Is Not Safely Recoverable Right Now

- `contracts.work_location`
- `contracts.job_title`
- any other free-text field where every available source already contains literal `?`

These rows are exported and reported as unrecoverable. They must stay unresolved until a new clean source of truth is available.

## Approved Import Paths

- Local / repo root: `./import-db.sh`
- VPS deployment: `deploy/vps/import-db.sh`
- Vietnamese round-trip smoke test:
  - `powershell -ExecutionPolicy Bypass -File .\BE\scripts\seed_test.ps1`
  - `php BE/scripts/seed_test.php`

The approved import scripts now:

- require `SQL_hackathon v4.sql`
- require `data.sql`
- validate UTF-8 before import
- reject `data.sql` when it is UTF-8-valid but text-corrupted
- force MySQL client/session charset to `utf8mb4`
- stop relying on missing ad-hoc Vietnamese repair migrations

## Blocked Unsafe Paths

The following ad-hoc deployment scripts remain blocked for data import/reseed purposes:

- `deploy_temp/clean_deploy.js`
- `deploy_temp/db_fix.js`
- `deploy_temp/final_fix.js`
- `deploy_temp/fix_remote_db.js`
- `deploy_temp/robust_fix.js`
- `deploy_temp/wipe_fix.js`

Use only the approved import scripts above.

## Verification Checklist

- Audit output classifies each dataset as clean, recoverable, or unrecoverable.
- Backup manifest exists before any applied repair.
- `SELECT employee_code, full_name, HEX(full_name)` returns valid Vietnamese for:
  - `NV0001`
  - `NV0002`
  - `NV0021`
  - `NV0125`
  - `NV0130`
- Repaired fields no longer show `3F` in place of Vietnamese bytes.
- API responses return corrected Vietnamese for employees, leave types, shift types, news, notifications, and report templates.
- Import scripts fail fast if base SQL files are missing or if `data.sql` is text-corrupted.
