# Vietnamese Data Audit

## Runtime
- `database_name`: `hrm_db`
- `character_set_server`: `utf8mb4`
- `collation_server`: `utf8mb4_unicode_ci`
- `character_set_database`: `utf8mb4`
- `collation_database`: `utf8mb4_unicode_ci`
- `character_set_connection`: `utf8mb4`
- `character_set_client`: `utf8mb4`
- `character_set_results`: `utf8mb4`

## Decoder Fixtures
- `LÃª Há»“ng Quang` => `Lê Hồng Quang` [ok]
- `Trá»‹nh Quá»‘c Äáº¡i` => `Trịnh Quốc Đại` [ok]
- `Phiáº¿u lÆ°Æ¡ng thÃ¡ng 03/2024` => `Phiếu lương tháng 03/2024` [ok]
- `ThÃ´ng bÃ¡o lá»‹ch nghá»‰ lá»… 30/04 vÃ  01/05` => `Thông báo lịch nghỉ lễ 30/04 và 01/05` [ok]

## Source Files
- `repo:SQL_hackathon v4.sql`: `missing`
- `repo:data.sql`: `missing`
- `repo:BE/scripts/test_accounts.tsv`: `utf8_but_text_corrupted`
- `repo:BE/scripts/test_accounts_by_role.md`: `clean_utf8`
- `archive:SQL_hackathon v4.sql`: `clean_utf8`
- `archive:data.sql`: `unsafe_for_reseed`
- `archive:BE/scripts/test_accounts.tsv`: `utf8_but_text_corrupted`
- `archive:BE/scripts/test_accounts_by_role.md`: `clean_utf8`

## Dataset Summary
- `employees.full_name`: `0` affected, recoverability `mixed_clean_and_mojibake_sources`
- `shift_types.shift_name`: `0` affected, recoverability `recoverable_from_deterministic_mapping`
- `leave_types.leave_type_name`: `0` affected, recoverability `recoverable_from_deterministic_mapping`
- `news.title`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `news.summary`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `news.content`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `news.status`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `news.priority`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `notifications.title`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `notifications.content`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `notifications.priority`: `0` affected, recoverability `recoverable_from_mojibake_source`
- `report_templates.template_name`: `0` affected, recoverability `recoverable_from_deterministic_mapping`
- `report_templates.columns_config`: `0` affected, recoverability `recoverable_from_deterministic_mapping`
- `report_templates.sql_query`: `0` affected, recoverability `recoverable_from_deterministic_mapping`
- `contracts.work_location`: `10` affected, recoverability `unrecoverable_exactly`
- `contracts.job_title`: `10` affected, recoverability `unrecoverable_exactly`

## Affected Records
- `contracts` `contract_id=1` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=2` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=3` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=4` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=5` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=6` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=7` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=8` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=9` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=10` `work_location`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=1` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=2` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=3` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=4` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=5` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=6` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=7` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=8` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=9` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
- `contracts` `contract_id=10` `job_title`: `literal_question_mark` -> `unrecoverable_exactly`
