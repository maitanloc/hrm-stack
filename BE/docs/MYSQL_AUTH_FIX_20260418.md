# MySQL Authentication Error: Complete Diagnosis & Fix Plan
**Date:** 2026-04-18  
**Error:** `SQLSTATE[HY000] [1045] Access denied for user 'hrm_user'@'localhost'`  
**System:** HRM Stack (PHP + MySQL + Docker)

---

## EXECUTIVE SUMMARY

The app is **failing authentication** because of a **configuration mismatch** between:
- **Docker MySQL initialization** (.env): Creates `root` user on `HRM_SYSTEM` database
- **PHP app connection** (be.env): Attempts to use `hrm_user` on `HRM_SYSTEM` database

**The user `hrm_user` DOES NOT EXIST** in the current MySQL container, causing the error.

**Impact:** HTTP 500 error on all database operations including login, dashboard, and any query.

---

## ROOT CAUSE ANALYSIS

### Error Classification: SQLSTATE[HY000] [1045]
```
[HY000] = General error (host/socket/connection)
[1045]  = Access denied for user
```

This error means:
✅ Network connection to MySQL succeeded (DNS OK, port OK)
❌ Authentication failed (wrong user/password/no such user/host mismatch)

### Configuration Analysis

#### 1. **Docker Compose Base Variables** (`.env`)
```env
MYSQL_ROOT_PASSWORD=change_root_password
MYSQL_DATABASE=hrm_db
MYSQL_USER=hrm_user
MYSQL_PASSWORD=change_user_password
```
Used by `docker-compose up` → Creates MySQL container

#### 2. **Application Configuration** (`be.env`)
```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=HRM_SYSTEM        ← MISMATCH: .env says hrm_db
DB_USERNAME=hrm_user
DB_PASSWORD=change_user_password
```

#### 3. **Top-level Orchestration** (`.env`)
```env
DB_DATABASE=HRM_SYSTEM
DB_USERNAME=root              ← App uses hrm_user instead!
DB_PASSWORD=change_root_password  ← App uses change_user_password!
```

### Why Connection Fails

```
MySQL Container Initialization (from docker-compose.yml):
├─ MYSQL_ROOT_PASSWORD → root user created
├─ MYSQL_DATABASE → hrm_db created (not HRM_SYSTEM)
├─ MYSQL_USER=hrm_user → hrm_user created
└─ MYSQL_PASSWORD → hrm_user password set
   └─ BUT: hrm_user privileges are tied to hrm_db, NOT HRM_SYSTEM

PHP App Connection Attempt:
├─ Hostname: mysql (correct)
├─ Port: 3306 (correct)
├─ Database: HRM_SYSTEM (WRONG - MySQL created hrm_db)
├─ Username: hrm_user (probably doesn't exist on HRM_SYSTEM)
└─ Password: change_user_password
    └─ Result: [1045] Access denied
```

---

## EXACT CHECKS TO RUN

### Phase 1: Verify MySQL Container State

#### Check 1: What users exist?
```sql
SELECT user, host FROM mysql.user WHERE user IN ('root', 'hrm_user');
```

**Expected:**
```
root      | %
root      | localhost
hrm_user  | %
```

#### Check 2: What grants does hrm_user have?
```sql
SHOW GRANTS FOR 'hrm_user'@'%';
```

**Expected:** Something like
```
GRANT ALL PRIVILEGES ON `hrm_db`.* TO 'hrm_user'@'%'
```

**If MySQL created only `hrm_db`, NOT `HRM_SYSTEM`:**
```sql
SHOW DATABASES;
```

#### Check 3: Does HRM_SYSTEM exist?
```sql
SHOW DATABASES LIKE 'HRM_SYSTEM';
```

### Phase 2: Verify Container Has Right Data

```bash
# Check what databases exist
docker compose exec mysql mysql -uroot -pchange_root_password -e "SHOW DATABASES;"

# Check if HRM_SYSTEM has tables
docker compose exec mysql mysql -uroot -pchange_root_password HRM_SYSTEM -e "SHOW TABLES;" 2>/dev/null || echo "Database doesn't exist or error"

# Check if hrm_user can connect
docker compose exec mysql mysql -uhrm_user -pchange_user_password -e "SELECT 1;" 2>&1
```

### Phase 3: Check PHP Config Runtime

Add to any PHP endpoint (temporary):
```php
// PUT THIS IN BE/public/index.php or a test endpoint TEMPORARILY
error_log('CONFIG CHECK:');
error_log('DB_HOST: ' . env('DB_HOST'));
error_log('DB_DATABASE: ' . env('DB_DATABASE'));
error_log('DB_USERNAME: ' . env('DB_USERNAME'));
error_log('DB_PASSWORD: ' . env('DB_PASSWORD'));
```

Then check `docker logs hrm-be` or PHP error log.

---

## DIAGNOSIS: WHICH SCENARIO?

### Scenario A: MySQL Container Never Had HRM_SYSTEM
**Symptom:** `SHOW DATABASES` in MySQL shows only `hrm_db`, not `HRM_SYSTEM`

**Cause:** Docker-compose.yml uses `MYSQL_DATABASE=hrm_db` but app expects `HRM_SYSTEM`

**Status:** 🔴 CRITICAL - Database doesn't exist

**Solution:** Recreate container with correct database name

### Scenario B: MySQL Created HRM_SYSTEM But No hrm_user
**Symptom:** `HRM_SYSTEM` exists, but `SELECT * FROM mysql.user WHERE user='hrm_user'` returns nothing

**Cause:** Someone ran SQL to create `HRM_SYSTEM` manually, but forgot to create `hrm_user`

**Status:** 🔴 CRITICAL - User doesn't exist

**Solution:** Create the user and grant privileges

### Scenario C: User Exists But Host Binding Wrong
**Symptom:** `hrm_user@'%'` exists but not `hrm_user@'localhost'`

**Cause:** User created for hostname `%` but app tries `localhost`

**Status:** 🟡 MEDIUM - Can be fixed either way

**Solution:** Create `hrm_user@'localhost'` OR change app to use hostname `mysql`

### Scenario D: User/Password Exists But Database Changed
**Symptom:** Everything exists but app trying wrong database

**Cause:** `.env` and `be.env` have different `DB_DATABASE` values

**Status:** 🔴 CRITICAL - Referencing wrong database

**Solution:** Fix `.env` or `be.env` to match, then restart containers

---

## SQL FIX OPTIONS

### Option 1: Create Missing hrm_user on HRM_SYSTEM (RECOMMENDED)

```sql
-- Step 1: Create user if not exists
CREATE USER IF NOT EXISTS 'hrm_user'@'localhost' IDENTIFIED BY 'change_user_password';
CREATE USER IF NOT EXISTS 'hrm_user'@'%' IDENTIFIED BY 'change_user_password';

-- Step 2: Grant all privileges on HRM_SYSTEM
GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO 'hrm_user'@'localhost';
GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO 'hrm_user'@'%';

-- Step 3: Apply changes
FLUSH PRIVILEGES;

-- Step 4: Verify
SELECT user, host FROM mysql.user WHERE user='hrm_user';
SHOW GRANTS FOR 'hrm_user'@'localhost';
SHOW GRANTS FOR 'hrm_user'@'%';
```

### Option 2: Recreate Container With Correct Settings

```bash
# Edit docker-compose.yml environment section:
# Change line:
#   MYSQL_DATABASE: ${MYSQL_DATABASE:-hrm_db}
# To:
#   MYSQL_DATABASE: ${MYSQL_DATABASE:-HRM_SYSTEM}

# Then:
docker compose down -v  # DANGER: Removes data!
docker compose up -d mysql
docker compose exec -T mysql mysql -uroot -pchange_root_password << EOF
GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO 'hrm_user'@'%';
FLUSH PRIVILEGES;
EOF

# Import data if needed
./import-db.sh

# Restart app
docker compose up -d hrm-be
```

### Option 3: Change App to Use root Account (TEMPORARY/NOT RECOMMENDED)

```bash
# Edit be.env:
DB_USERNAME=root
DB_PASSWORD=change_root_password
DB_DATABASE=HRM_SYSTEM

docker compose restart hrm-be
```

**⚠️ NOT RECOMMENDED:** Using root for apps violates security principle of least privilege.

### Option 4: Align Both .env Files (BEST PRACTICE)

```bash
# Ensure .env and be.env both target same database/user:

# .env (for docker-compose):
MYSQL_DATABASE=HRM_SYSTEM              # Not hrm_db
MYSQL_USER=hrm_user
MYSQL_PASSWORD=change_user_password

# be.env (for PHP app):
DB_DATABASE=HRM_SYSTEM                 # Match docker
DB_USERNAME=hrm_user
DB_PASSWORD=change_user_password

docker compose down -v
docker compose up -d
./import-db.sh
docker compose restart hrm-be
```

---

## STEP-BY-STEP FIX (RECOMMENDED APPROACH)

### Step 1: Stop The System
```bash
cd /path/to/hrm-stack
docker compose stop

# Leave data volume intact: do NOT use 'down -v'
```

### Step 2: Diagnose Current MySQL State
```bash
# Start MySQL only
docker compose up -d mysql

# Wait for startup
sleep 5

# Check what we have
docker compose exec -T mysql mysql -uroot -pchange_root_password -e "SHOW DATABASES;"
docker compose exec -T mysql mysql -uroot -pchange_root_password -e "SELECT user, host FROM mysql.user WHERE user IN ('root', 'hrm_user');"
```

**Record the output** - It will tell you which scenario applies.

### Step 3: Apply the Fix

#### If Database HRM_SYSTEM Exists:
```bash
docker compose exec -T mysql mysql -uroot -pchange_root_password HRM_SYSTEM << 'EOF'
CREATE USER IF NOT EXISTS 'hrm_user'@'localhost' IDENTIFIED BY 'change_user_password';
CREATE USER IF NOT EXISTS 'hrm_user'@'%' IDENTIFIED BY 'change_user_password';
GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO 'hrm_user'@'localhost';
GRANT ALL PRIVILEGES ON HRM_SYSTEM.* TO 'hrm_user'@'%';
FLUSH PRIVILEGES;
EOF

echo "User created. Verifying..."
docker compose exec -T mysql mysql -uroot -pchange_root_password -e "SHOW GRANTS FOR 'hrm_user'@'%';"
```

#### If Database Doesn't Exist:
```bash
# Option A: Rename hrm_db to HRM_SYSTEM
docker compose exec -T mysql mysql -uroot -pchange_root_password << 'EOF'
RENAME TABLE hrm_db.* TO HRM_SYSTEM.*;  -- This doesn't work in MySQL!

-- Instead, use:
CREATE DATABASE HRM_SYSTEM;
-- Then restore data from SQL files
EOF

# Option B: Just rebuild from scratch (see Option 2 above)
```

### Step 4: Verify the Fix
```bash
# Can hrm_user connect?
docker compose exec -T mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM -e "SELECT 1 AS connection_test;" 2>&1

# What tables are there?
docker compose exec -T mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM -e "SHOW TABLES LIMIT 5;"
```

### Step 5: Start the App
```bash
docker compose up -d hrm-be

# Wait 3 seconds
sleep 3

# Check logs
docker compose logs -f hrm-be --tail=50

# Should NOT see: "Cannot connect database: SQLSTATE[HY000] [1045]"
```

### Step 6: Test the Application
```bash
# Try to login or access an endpoint that queries database
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test"}'

# Should NOT return HTTP 500 with "db_connection_error"
# It might return 401 (invalid credentials) which is OK - means DB connected!
```

---

## CONFIGURATION FILES TO UPDATE

### File 1: `be.env`
**Current:**
```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=HRM_SYSTEM
DB_USERNAME=hrm_user
DB_PASSWORD=change_user_password
```

**✓ This is CORRECT, no change needed**

### File 2: `.env` (Docker Compose)
**Current:**
```env
MYSQL_DATABASE=hrm_db
MYSQL_USER=hrm_user
MYSQL_PASSWORD=change_user_password
```

**Should be:**
```env
MYSQL_DATABASE=HRM_SYSTEM      ← CHANGE from hrm_db
MYSQL_USER=hrm_user
MYSQL_PASSWORD=change_user_password
```

### File 3: `docker-compose.yml`
**Current:**
```yaml
environment:
  MYSQL_DATABASE: ${MYSQL_DATABASE:-hrm_db}
  MYSQL_USER: ${MYSQL_USER:-hrm_user}
  MYSQL_PASSWORD: ${MYSQL_PASSWORD:-change_user_password}
```

**No change needed** - Uses .env defaults which we're fixing

---

## VERIFICATION CHECKLIST

✅ **Phase 1: MySQL Connectivity**
- [ ] hrm_user@localhost exists: `SELECT * FROM mysql.user WHERE user='hrm_user' AND host='localhost';`
- [ ] hrm_user@% exists: `SELECT * FROM mysql.user WHERE user='hrm_user' AND host='%';`
- [ ] HRM_SYSTEM database exists: `SHOW DATABASES LIKE 'HRM_SYSTEM';`
- [ ] hrm_user has GRANT on HRM_SYSTEM: `SHOW GRANTS FOR 'hrm_user'@'%';`
- [ ] Can log in as hrm_user: `mysql -u hrm_user -p'change_user_password' HRM_SYSTEM -e "SELECT 1;"`

✅ **Phase 2: Application Config**
- [ ] be.env exists and readable: `cat BE/.env 2>/dev/null || cat be.env`
- [ ] DB_DATABASE in be.env matches MySQL: `echo $DB_DATABASE`
- [ ] DB_USERNAME in be.env exists in MySQL: `mysql -uroot -p ... -e "SELECT * FROM mysql.user WHERE user='$(grep DB_USERNAME be.env | cut -d= -f2)';"`
- [ ] PHP can read config: Add debug log endpoint and verify values

✅ **Phase 3: Container Startup**
- [ ] MySQL container running: `docker ps | grep hrm-mysql`
- [ ] PHP-FPM container running: `docker ps | grep hrm-be`
- [ ] No connection errors in logs: `docker logs hrm-be 2>&1 | grep -i 'cannot connect\|access denied\|1045'`
- [ ] Container can resolve hostname: `docker compose exec hrm-be ping -c 1 mysql`

✅ **Phase 4: Application Functionality**
- [ ] API responds without 500 error: `curl -s http://localhost/api/v1/health || echo "No health endpoint"`
- [ ] Login endpoint reachable: `curl -s -X POST http://localhost/api/v1/auth/login`
- [ ] GET request works (no DB error): `curl -s http://localhost/api/v1/users | head -20`
- [ ] No errors in logs during request: `docker logs hrm-be --tail 20`

✅ **Phase 5: Data Integrity**
- [ ] Tables exist in HRM_SYSTEM: `mysql -u hrm_user -p'change_user_password' HRM_SYSTEM -e "SHOW TABLES;" | wc -l`
- [ ] Sample data readable: `mysql -u hrm_user -p'change_user_password' HRM_SYSTEM -e "SELECT COUNT(*) FROM employees;"`
- [ ] No charset/collation errors in logs: `docker logs hrm-be 2>&1 | grep -i 'charset\|collation'`

---

## REGRESSION TESTING

After fix is applied, run these tests:

### Test 1: Direct MySQL Connection (Bypass App)
```bash
# Test root access still works (for backups, maintenance)
docker compose exec mysql mysql -uroot -pchange_root_password HRM_SYSTEM -e "SELECT COUNT(*) AS table_count FROM information_schema.tables WHERE table_schema='HRM_SYSTEM';"

# Test hrm_user access works
docker compose exec mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM -e "SELECT 1 AS hrm_user_works;"
```

### Test 2: Critical Business Workflows
```bash
# Test employee lookup
curl -X GET http://localhost/api/v1/employees \
  -H "Authorization: Bearer <token>" | jq '.success'

# Test attendance system
curl -X GET http://localhost/api/v1/attendance/today \
  -H "Authorization: Bearer <token>" | jq '.success'

# Test payroll query
curl -X GET http://localhost/api/v1/payroll/current \
  -H "Authorization: Bearer <token>" | jq '.success'
```

### Test 3: Error Handling
```bash
# Verify HTTP 500 errors are GONE
curl -s -w "\nHTTP Status: %{http_code}\n" http://localhost/api/v1/employees \
  -H "Authorization: Bearer invalid" || echo "Got error (expected)"

# Should NOT mention: "SQLSTATE[HY000] [1045]"
# Should mention: "Unauthorized" or similar
```

### Test 4: Log Cleanliness
```bash
# Should have ZERO "Access denied" messages in last 24 hours
docker logs hrm-be --since 24h 2>&1 | grep -ic "access denied" || echo "✓ No access denied errors"
docker logs hrm-be --since 24h 2>&1 | grep -ic "SQLSTATE" || echo "✓ No SQL errors"
```

---

## HARDENING RECOMMENDATIONS

### 1. Database Connection Health Check

Add to `BE/bootstrap.php` after loading env:
```php
// Emergency connection test on startup
if (env('APP_ENV') === 'production' && !isset($_SERVER['DB_SKIP_HEALTH_CHECK'])) {
    try {
        Database::connection();  // Trigger connection
    } catch (HttpException $e) {
        error_log('CRITICAL: Database connection failed on app startup!');
        error_log('Error: ' . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'database_unavailable',
            'message' => 'Database connection failed. Please contact system administrator.',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        exit(1);
    }
}
```

### 2. Credential Validation at Deployment

Create `BE/scripts/validate-deployment.sh`:
```bash
#!/bin/bash
set -e

echo "=== HRM Deployment Validation ==="

# Check environment variables
test -n "$DB_HOST" || { echo "ERROR: DB_HOST not set"; exit 1; }
test -n "$DB_DATABASE" || { echo "ERROR: DB_DATABASE not set"; exit 1; }
test -n "$DB_USERNAME" || { echo "ERROR: DB_USERNAME not set"; exit 1; }
test -n "$DB_PASSWORD" || { echo "ERROR: DB_PASSWORD not set"; exit 1; }

# Validate placeholders are replaced
if [[ "$DB_PASSWORD" == "change_user_password" ]] && [[ "$APP_ENV" == "production" ]]; then
    echo "ERROR: Using placeholder password in production!"
    exit 1
fi

# Test database connection
mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
    -e "SELECT 1 AS connection_ok;" || {
    echo "ERROR: Cannot connect to database with provided credentials"
    exit 1
}

echo "✓ All validations passed"
```

### 3. Hide Raw SQL Errors from UI

Update `BE/bootstrap.php` exception handler:
```php
set_exception_handler(static function (Throwable $exception): void {
    $status = 500;
    $error = 'internal_server_error';
    $message = 'Internal server error';

    if ($exception instanceof App\Core\HttpException) {
        $status = $exception->getStatusCode();
        $error = $exception->getErrorCode();
        
        // NEVER expose PDOException details to client
        if (str_contains($exception->getMessage(), 'SQLSTATE')) {
            error_log('DATABASE ERROR: ' . $exception->getMessage());
            $message = 'Database operation failed. Please try again.';
        } else {
            $message = $exception->getMessage();
        }
    } elseif ((bool) env('APP_DEBUG', true)) {
        $message = $exception->getMessage();
    }

    // ... rest of handler
});
```

### 4. Logging Best Practices

Add structured logging (without exposing credentials):
```php
// Safe database error logging
error_log(json_encode([
    'timestamp' => date('c'),
    'event' => 'db_connection_failed',
    'user' => $config['username'] ?? 'unknown',
    'database' => $config['database'] ?? 'unknown',
    'host' => $config['host'] ?? 'unknown',
    'error_code' => $exception->getCode(),
    'message' => $exception->getMessage(),  // Will contain SQLSTATE codes
    'trace' => substr($exception->getTraceAsString(), 0, 1000),  // First 1000 chars
]));
```

### 5. Configuration Per Environment

Keep three distinct files:
```
BE/
├─ .env.example          (no passwords, default values)
├─ .env.local           (gitignored, local dev)
├─ config/database.php  (loads from .env)
BE/
├─ scripts/
│  ├─ validate-deployment.sh     (pre-deploy check)
│  └─ configure-production.sh    (set correct .env values)
```

### 6. Pre-Deployment Checklist

Create `DEPLOYMENT_CHECKLIST.md`:
```markdown
# Deployment Checklist

## Configuration
- [ ] `.env` file updated with correct `MYSQL_DATABASE`
- [ ] `be.env` credentials match `.env` values
- [ ] No placeholder passwords (change_user_password, change_root_password)
- [ ] `DB_HOST` points to correct MySQL instance
- All files committed or checked before deployment

## MySQL Preparation
- [ ] Target database exists: `SHOW DATABASES LIKE '<DB_NAME>';`
- [ ] Application user exists: `SELECT * FROM mysql.user WHERE user='<username>';`
- [ ] User has correct grants: `SHOW GRANTS FOR '<user>'@'<host>';`
- [ ] Character set matches: `SHOW VARIABLES LIKE 'character_set_database';`

## Pre-Flight Tests
- [ ] Run `validate-deployment.sh` successfully
- [ ] MySQL accepts connection from app with provided credentials
- [ ] All configuration values are non-default in production

## Post-Deployment
- [ ] App logs show no "Access denied" errors
- [ ] First API request succeeds (no HTTP 500)
- [ ] Database tables are accessible
- [ ] Health check endpoint returns 200 OK
```

---

## RESIDUAL RISKS & MONITORING

### Risk 1: Configuration Drift
**Problem:** Multiple `.env` files (`.env`, `be.env`, `.env.deploy`) can get out of sync.

**Mitigation:**
- Maintain single source of truth (`.env`)
- Generate other files from it
- Document which file is used where

**Monitoring:**
```bash
# Daily check
for env_file in .env be.env .env.deploy; do
    grep "DB_PASSWORD=" "$env_file" | cut -d= -f2 | sort | uniq -c | awk '{if ($1 != 3) print "MISMATCH in DB_PASSWORD"}'
done
```

### Risk 2: Container Out of Sync
**Problem:** Docker container keeps old data if .env changes.

**Mitigation:**
- Document: `docker compose down -v` destroys data
- Use proper upgrade procedure for data migrations
- Always backup before `down -v`

### Risk 3: Secret Exposure
**Problem:** Credentials visible in `.env`, logs, exception messages.

**Mitigation:**
- ✓ Never commit `.env` to git (use `.gitignore`)
- ✓ Mask credentials in error messages (see section above)  
- ✓ Use environment variables, not config files, in production
- ✓ Rotate credentials regularly

**Check for leaks:**
```bash
git log -p --all -S "change_user_password" | head -20
git log -p --all -S "Hoang2002@" | head -20
```

### Risk 4: Host Binding Issues
**Problem:** `localhost` vs `%` vs `127.0.0.1` vs `mysql` hostname mismatch.

**Monitoring:**
```
User exists as...│App connects from...│Result
└─ 'user'@'%'          │localhost           │✓ Works
├─ 'user'@'localhost'  │127.0.0.1           │May fail
└─ 'user'@'127.0.0.1'  │mysql container     │May fail
```

Create both:
```sql
CREATE USER 'hrm_user'@'localhost' IDENTIFIED BY '...';
CREATE USER 'hrm_user'@'%' IDENTIFIED BY '...';
```

### Risk 5: Character Set Misalignment
**Problem:** MySQL utf8 ≠ utf8mb4, or collation mismatch causes "mojibake" (garbled text).

**Current protection:** `be.env` sets:
```env
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

**Verify:**
```sql
SELECT @@character_set_database, @@collation_database;
-- Should be: utf8mb4, utf8mb4_unicode_ci
```

---

## QUICK REFERENCE: WHICH FIX TO APPLY

| Symptom | Root Cause | Fix |
|---------|-----------|-----|
| HTTP 500, logs say "Access denied" | No user `hrm_user` | Create user + GRANT |
| HTTP 500, `HRM_SYSTEM` doesn't exist | Wrong DB created | Fix `.env`, restart, restore data |
| HTTP 500, user exists but logs say "Using wrong user" | `.env` vs `be.env` mismatch | Align DB_USERNAME values |
| Works locally, fails on VPS | IP/hostname different | Create `hrm_user`@`'<vps-ip>'` |
| Works after restart, fails after N requests | PHP opcache cached old config | Clear opcache + restart |

---

## IMMEDIATE ACTION ITEMS

### Priority 1 (DO NOW):
```bash
# 1. Check MySQL state
docker compose exec mysql mysql -uroot -pchange_root_password -e "SELECT user, host FROM mysql.user;"
docker compose exec mysql mysql -uroot -pchange_root_password -e "SHOW DATABASES;"

# 2. Record findings in a file
# (Attach to next communication)

# 3. Look at current be.env vs .env
diff -u .env be.env || true
```

### Priority 2 (NEXT):
```bash
# 1. Decide which scenario matches your situation (A, B, C, D above)
# 2. Apply corresponding SQL or config fix
# 3. Restart containers
```

### Priority 3 (VERIFY):
```bash
# 1. Test connectivity from app
# 2. Run regression tests
# 3. Check logs for errors
```

---

## SUPPORT ESCALATION

If after applying fixes you still see errors:

1. **Share MySQL user list:**
   ```bash
   docker compose exec mysql mysql -uroot -pchange_root_password \
       -e "SELECT user, host, authentication_string FROM mysql.user;" > mysql_users.txt
   cat mysql_users.txt
   ```

2. **Share exact error message:**
   ```bash
   docker logs hrm-be 2>&1 | tail -50 > app_logs.txt
   cat app_logs.txt
   ```

3. **Share configuration:**
   ```bash
   echo "=== be.env ===" && cat be.env && \
   echo "=== .env ===" && cat .env && \
   echo "=== docker-compose.yml (excerpt) ===" && \
   grep -A 10 "hrm-be:" docker-compose.yml
   ```

---

**Document Version:** 1.0  
**Last Updated:** 2026-04-18  
**Status:** Pending Implementation & Verification
