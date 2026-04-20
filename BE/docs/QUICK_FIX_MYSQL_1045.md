# QUICK FIX: MySQL [1045] Access Denied

**Problem:** `SQLSTATE[HY000] [1045] Access denied for user 'hrm_user'@'localhost'`  
**Cause:** Database user doesn't exist or has wrong permissions  
**Fix Time:** < 5 minutes  
**Data Loss Risk:** None (only creates user/permissions)

---

## ONE-MINUTE FIX

```bash
cd /path/to/hrm-stack

# 1. Start MySQL if not running
docker compose up -d mysql
sleep 3

# 2. Run the fix script
docker compose exec -T mysql mysql -uroot -pchange_root_password HRM_SYSTEM < BE/scripts/immediate-fix.sql

# 3. Restart PHP app
docker compose restart hrm-be

# 4. Check logs
docker logs hrm-be --tail 20
```

**Success:** No `[1045]` error in logs, app starts normally.

---

## IF THAT DOESN'T WORK: 5-MINUTE DIAGNOSTIC

```bash
# Show what users exist
docker compose exec -T mysql mysql -uroot -pchange_root_password \
    -e "SELECT user, host FROM mysql.user WHERE user LIKE 'hrm%';"

# Show what databases exist
docker compose exec -T mysql mysql -uroot -pchange_root_password \
    -e "SHOW DATABASES;"

# Show grants for hrm_user
docker compose exec -T mysql mysql -uroot -pchange_root_password \
    -e "SHOW GRANTS FOR 'hrm_user'@'%';"

# Try to connect as hrm_user (should work after fix)
docker compose exec -T mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM \
    -e "SELECT 1 AS test;"
```

---

## CONFIGURATION MISMATCH CHECK

```bash
# Compare database names
echo "=== .env (Docker) ==="
grep "MYSQL_DATABASE\|MYSQL_USER\|MYSQL_PASSWORD" .env

echo "=== be.env (App) ==="
grep "DB_DATABASE\|DB_USERNAME\|DB_PASSWORD" be.env
```

**If they don't match:** Manually update `.env` to match `be.env`:

```bash
# Edit .env and ensure:
# MYSQL_DATABASE=HRM_SYSTEM        (not hrm_db)
# MYSQL_USER=hrm_user
# MYSQL_PASSWORD=change_user_password

# Then restart MySQL
docker compose down -v  # WARNING: DELETES DATA
docker compose up -d mysql
```

---

## CHECK IF MySQL NEEDS OOH PASSWORD

If using production password (not placeholder), update `.env`:

```bash
MYSQL_ROOT_PASSWORD=your_actual_password
MYSQL_PASSWORD=your_actual_hrm_password

# Then run fix with correct password:
docker compose exec -T mysql mysql -uroot -p"your_actual_password" HRM_SYSTEM < BE/scripts/immediate-fix.sql
```

---

## STILL NOT WORKING?

Run comprehensive diagnostic:

```bash
bash BE/scripts/fix-mysql-auth.sh diagnose
```

This will show:
- Current MySQL state
- What users exist
- What databases exist
- Exact errors preventing connection
- Recommended fix

If diagnostic shows the issue, run:

```bash
bash BE/scripts/fix-mysql-auth.sh fix
```

---

## VERIFY THE FIX

After running fixes, check these:

```bash
# 1. User exists
docker compose exec mysql mysql -uroot -pchange_root_password \
    -e "SELECT user, host FROM mysql.user WHERE user='hrm_user';"

# 2. User can access database
docker compose exec mysql mysql -uhrm_user -pchange_user_password HRM_SYSTEM \
    -e "SELECT COUNT(*) FROM information_schema.tables;"

# 3. App can connect (no logs errors)
docker logs hrm-be --tail 30 | grep -i "access denied\|SQLSTATE" || echo "✓ No errors"

# 4. API works
curl -s http://localhost/api/v1/health || echo "API not yet ready"
```

---

## LAST RESORT: Nuclear Option

Only if absolutely nothing works and you accept data loss:

```bash
cd /path/to/hrm-stack

# 1. Stop everything
docker compose stop

# 2. Delete MySQL data volume
docker compose down -v

# 3. Restart
docker compose up -d mysql
docker compose up -d hrm-be

# 4. Re-import database
./import-db.sh

# 5. Apply fix
docker compose exec -T mysql mysql -uroot -pchange_root_password HRM_SYSTEM < BE/scripts/immediate-fix.sql

# 6. Done
docker compose logs -f hrm-be
```

---

## ROOT CAUSE REFERENCE

| Symptom | Cause | Fix |
|---------|-------|-----|
| `[1045] Access denied` | User doesn't exist | Run immediate-fix.sql |
| Works then fails | App reconnected, user gone | Fix was temporary, re-run |
| Works locally, fails on server | Different MySQL instance | Create user on that server |
| All commands fail, MySQL won't start | Docker out of space | Clean: `docker system prune -a` |

---

## FILES CREATED FOR YOU

- `BE/docs/MYSQL_AUTH_FIX_20260418.md` - Comprehensive guide (read if confused)
- `BE/scripts/fix-mysql-auth.sh` - Automated diagnostic & fix script
- `BE/scripts/immediate-fix.sql` - Direct SQL to create user/grants
- This file - Quick emergency reference

---

**Last Updated:** 2026-04-18  
**Estimated Fix Time:** 1-5 minutes
