# B2B Demo Data Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 25 sample wholesale products and three login-enabled demo clients to the local B2B SQLite database through a guarded, repeatable CLI seeder.

**Architecture:** Add a CLI seeder that requires the project's local development config, refuses non-development or non-SQLite runs, and uses `App\B2bRepository` to add only missing products and clients. Add a standalone PHP integration test that invokes the seeder against a temporary SQLite file twice, checking the inserted data, credentials, idempotency, and preservation of existing hashes.

**Tech Stack:** PHP 8.3, PDO SQLite, existing `App\B2bRepository`, standalone PHP tests.

---

## Chunk 1: Repeatable local B2B demo seeder

### Task 1: Specify seeder behavior in a failing integration test

**Files:**
- Create: `tests/test_b2b_demo_seed.php`
- Create later: `tests/seed_b2b_demo.php`

- [ ] **Step 1: Create a temporary-database integration test**

Create a standalone PHP test that:

1. Creates a unique temporary SQLite database path.
2. Invokes `tests/seed_b2b_demo.php --db=<path>` using `PHP_BINARY` and `proc_open`.
3. Checks the process exit status and captures stdout/stderr.
4. Reads the resulting SQLite database directly with PDO.
5. Verifies exactly 25 sample products and three clients with the reserved demo logins exist.
6. Parses the three first-run credential lines from stdout and verifies the printed passwords against their stored hashes with `password_verify`.
7. Re-runs the seeder against the same file and verifies no new records or credentials are reported, counts are unchanged, and each hash is unchanged.
8. Cleans up the database and its SQLite `-wal` and `-shm` files in a `finally` block.

Use clear assertions and print a concise PASS/FAIL result, matching the repository's standalone PHP test style. Do not connect the test to `db/b2b.sqlite`.

- [ ] **Step 2: Run the test and verify it fails for the missing seeder**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' tests\test_b2b_demo_seed.php
```

Expected: FAIL because `tests/seed_b2b_demo.php` does not exist yet. Fix test harness errors if the process invocation or assertions themselves fail.

### Task 2: Implement the guarded, idempotent seeder

**Files:**
- Create: `tests/seed_b2b_demo.php`
- Modify: `tests/test_b2b_demo_seed.php`

- [ ] **Step 1: Add CLI and environment guards**

In the seeder:

- Derive the repository root from the script location and define `BASE_PATH` only if not already defined.
- Exit with an explicit error unless `PHP_SAPI === 'cli'`.
- Require `program/config/data.php`; require `APP_ENV === 'development'`.
- Refuse a configured `B2B_DB_DRIVER` other than `sqlite`; when the constant is absent, use the repository's existing SQLite default.
- Use `db/b2b.sqlite` by default. Accept only the documented optional `--db=<path>` override for isolated test databases.
- Load the project autoloader and instantiate `App\B2bRepository` with a PDO connection to the selected SQLite file; initialize its schema before seeding.
- Send errors to STDERR and return a non-zero exit status rather than silently falling back.

- [ ] **Step 2: Add a fixed 25-item sample catalog**

Define exactly 25 fictional fruit and vegetable wholesale products in several categories. Give each product a non-empty name and representative category, unit, price, package size, package unit, and availability. Check existing product names using the repository's normalization behavior and pass only missing products to `saveProductsBatch($missingProducts, false)` so existing catalog rows are not cleared or updated.

- [ ] **Step 3: Add three demo client accounts**

Define three fictional clients with reserved, stable demo logins such as `demo-sklep-1`, `demo-sklep-2`, and `demo-sklep-3`; use fictional contact data and active status. Before creating each one, check whether its reserved login already exists. Generate a unique random password for each new client, pass it through `createClient` so only its hash is stored, and print its login and password to stdout only at creation time. Never reset or print a password for an existing account.

- [ ] **Step 4: Print inserted/skipped counts and run the integration test**

Print one `DEMO_CLIENT` line with the login and generated password only for each newly created client, followed by a summary with product/client added and skipped counts. Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' tests\test_b2b_demo_seed.php
```

Expected: PASS on the first run and on the idempotency check; exactly 25 demo products and three demo clients in the temporary database, with new credentials accepted by `password_verify`.

## Chunk 2: Validate syntax and local database seeding

### Task 3: Validate the seeder files and populate the existing local database

**Files:**
- Check: `tests/seed_b2b_demo.php`
- Check: `tests/test_b2b_demo_seed.php`
- Data target: ignored local file `db/b2b.sqlite`

- [ ] **Step 1: Run PHP syntax checks**

Run:

```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' -l tests\seed_b2b_demo.php
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' -l tests\test_b2b_demo_seed.php
```

Expected: both report `No syntax errors detected`.

- [ ] **Step 2: Run the regression test**

Run `tests\test_b2b_demo_seed.php` again and confirm exit status 0.

- [ ] **Step 3: Seed the existing local SQLite database**

First inspect the target counts so this operation is deliberate:

```powershell
$code = @'
$pdo = new PDO('sqlite:db/b2b.sqlite');
foreach (['b2b_products', 'b2b_clients'] as $table) {
    echo $table . ': ' . $pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() . PHP_EOL;
}
'@
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' -r $code
```

Run the seeder against the default database only after confirming it is still the expected local development database:

```powershell
& 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe' tests\seed_b2b_demo.php
```

Expected: 25 products and three clients are added; the command prints the newly generated demo client credentials once. Preserve those credentials in the task handoff, not in a tracked file.

- [ ] **Step 4: Verify final database counts and worktree**

Query product/client counts again and confirm all demo logins are present. Run `git diff --check` and inspect `git status --short`; do not stage or commit changes unless separately requested.
