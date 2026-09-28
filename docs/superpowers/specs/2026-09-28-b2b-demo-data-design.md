# B2B demo catalog and clients

## Goal

Populate the existing local B2B SQLite database with a realistic sample catalog
of 25 wholesale products and three test client accounts that can sign in to the
B2B store. The current local database was checked and contains zero products
and zero clients.

## Proposed implementation

Add a CLI-only demo seeder under `tests/` and run it once against
`db/b2b.sqlite`. It will use `App\B2bRepository` and its existing product/client
creation methods rather than bypassing repository behavior or adding data to
application startup. An optional `--db=<path>` argument will allow tests to
point it at a temporary SQLite database; without it, the script uses the local
`db/b2b.sqlite`.

The script will:

- Require the local development configuration and refuse to run outside the
  development environment or when the configured B2B database driver is not
  SQLite.
- Add 25 fictional fruit and vegetable wholesale products across several
  categories, with representative units, package sizes, and prices.
- Add three fictional, active B2B clients with unique, stable demo logins.
- Generate a unique random password for each newly created client, store only
  the password hash, and print each new login/password once to the CLI. A
  subsequent run will preserve existing accounts and will not reset passwords.
- Skip sample products and clients that already exist, without deleting or
  overwriting unrelated records.
- Print a concise summary of records added and skipped.

Product matching will use the product name; client matching will use the
reserved demo login. The script will not create orders or modify application
code paths executed during normal web requests.

## Alternatives considered

1. Insert the records directly into the current database with a one-off command.
   This is quick but difficult to reproduce or safely re-run.
2. Add and run a guarded CLI seeder (recommended). It documents the sample data,
   is repeatable, and does not change production behavior.
3. Seed records automatically from `B2bRepository::initDatabase()`. This would
   unexpectedly populate any database, including non-demo environments, so it
   is rejected.

## Verification

- Run the seeder against a temporary SQLite database and assert exactly 25
  products and three demo clients are created.
- Run it a second time and verify no duplicate records are added and existing
  client passwords remain unchanged.
- Verify new client passwords authenticate using `password_verify()` and that
  no plaintext password is stored.
- Run PHP syntax checks and the focused seeder test.

## Scope and operational notes

The target is the existing ignored local file `db/b2b.sqlite`; no database
contents are committed. The seeder is intended for local development only.
The demo account credentials are shown only when accounts are first created;
save them from the command output if needed.
