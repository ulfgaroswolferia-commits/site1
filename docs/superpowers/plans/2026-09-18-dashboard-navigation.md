# Dashboard Navigation Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the post-login screen with a focused module launcher containing Order, a non-interactive future-module placeholder, and a link to the preserved existing dashboard.

**Architecture:** Keep the existing `home` route and `HomeController`. Change `actionIndex()` so authenticated users receive a new standalone launcher view, and add an authenticated `actionDashboard()` that renders the current dashboard view unchanged. Use a dedicated `views/home/launcher.php` for the three-item screen and preserve all existing Order routes and models.

**Tech Stack:** PHP MVC micro-framework in the repository, PHP views, `App::baseUrl()`, `Tools::h()`, existing CSS conventions, CLI PHP syntax checks, and the existing PHP test scripts.

---

## File map

- Modify: `program/script/HomeController.php`
  - Split the authenticated landing screen from the existing dashboard action.
  - Preserve login/logout behavior and the existing dashboard data contract.
- Create: `views/home/launcher.php`
  - Render only the module launcher UI.
  - Use `App::baseUrl()` for links and avoid additional interactive controls.
- Preserve: `views/home/dashboard.php`
  - Do not alter its dashboard content or existing navigation.
- Verify: `views/order/index.php`, `views/order/history.php`, `views/order/view.php`
  - Confirm their existing “Pulpit” links continue to target `home/index`.
- Test/verify: `tests/`
  - Use existing PHP test scripts where applicable and add no new database behavior.
- Documentation already completed:
  - `docs/superpowers/specs/2026-09-18-dashboard-navigation-design.md`

## Chunk 1: Controller split and launcher view

### Task 1: Add the authenticated dashboard action

**Files:**
- Modify: `program/script/HomeController.php:10-25`

- [ ] **Step 1: Record the current behavior before editing**

Run:

```powershell
php -l program/script/HomeController.php
```

Expected: `No syntax errors detected in program/script/HomeController.php`.

- [ ] **Step 2: Update `actionIndex()` to return the launcher**

Keep the unauthenticated branch calling `actionLogin()`. In the authenticated
branch, retain the title and user values needed by the new view, but return
`'launcher'` instead of `'dashboard'`. Retain
`$this->layout = '';` so the launcher remains a standalone page, consistent
with the existing dashboard.

The action must continue setting:

```php
$this->outputData['title'] = 'Panel użytkownika';
$this->outputData['user'] = Tools::getSessionVar('app_login')
    ?: (defined('APP_LOGIN') ? APP_LOGIN : 'użytkownik');
```

- [ ] **Step 3: Add `actionDashboard()`**

Add a new GET action after `actionIndex()`:

```php
public function actionDashboard()
{
    $this->requireAuth();
    $this->layout = '';

    $this->outputData['title'] = 'Panel użytkownika';
    $this->outputData['user']  = Tools::getSessionVar('app_login')
        ?: (defined('APP_LOGIN') ? APP_LOGIN : 'użytkownik');

    return 'dashboard';
}
```

This preserves the existing standalone dashboard rendering and makes the
dashboard directly accessible at `/home/dashboard` only for authenticated
users.

- [ ] **Step 4: Run the controller syntax check**

Run:

```powershell
php -l program/script/HomeController.php
```

Expected: no syntax errors.

### Task 2: Create the focused launcher view

**Files:**
- Create: `views/home/launcher.php`

- [ ] **Step 1: Create the view with only the approved module choices**

Build a standalone HTML document, matching the existing self-contained
dashboard style but with a substantially smaller surface. The view should:

1. Define `$base = App::baseUrl()`.
2. Render a page title and a short heading such as “Wybierz moduł”.
3. Render one interactive Order card/link:
   `href="<?= $base ?>order/index"`.
4. Render one non-interactive placeholder as a `div` (not an anchor and not
   an enabled button) with the exact user-facing text:
   `Miejsce na następny moduł`.
5. Render the final dashboard action:
   `href="<?= $base ?>home/dashboard"` with a label such as
   `Otwórz pulpit`.

Do not add links to logout, documentation, examples, order history, or any
other destination. Do not add JavaScript click handlers to the placeholder.
Use `Tools::h()` for dynamic title/user text if displayed.
The module area must contain exactly these three elements, in this order:
Order link/card, non-interactive placeholder, dashboard link/button.

- [ ] **Step 2: Keep visual hierarchy aligned with the approved variant B**

Use a centered, readable vertical layout:

- Order receives the strongest visual emphasis as the primary module card.
- The placeholder uses a muted border/background and appears disabled.
- The dashboard link is visually secondary and is the final item.

Reuse the existing color language where practical, but do not modify the
existing global stylesheet or the existing dashboard stylesheet unless the
launcher cannot be rendered cleanly without it.

- [ ] **Step 3: Check the new view syntax**

Run:

```powershell
php -l views/home/launcher.php
```

Expected: no syntax errors.

## Chunk 2: Navigation regression checks

### Task 3: Verify existing module links and preserve the dashboard

**Files:**
- Verify only: `views/home/dashboard.php`
- Verify only: `views/order/index.php`
- Verify only: `views/order/history.php`
- Verify only: `views/order/view.php`

- [ ] **Step 1: Confirm the existing dashboard remains unchanged**

Run:

```powershell
git diff -- views/home/dashboard.php
```

Expected: no diff for the existing dashboard view.

- [ ] **Step 2: Confirm Order “Pulpit” links target the launcher**

Inspect every matching navigation link:

```powershell
rg -n -C 2 'Pulpit|home/index|home/dashboard' views/order
```

Expected: every existing Order navigation link labeled “Pulpit” uses
`App::baseUrl()` followed by `home/index`; no such link is changed to
`home/dashboard`.

- [ ] **Step 3: Confirm no forbidden launcher links were introduced**

List the launcher’s links and inspect its interactive elements:

```powershell
rg -n '<a |<button|href=|onclick=|home/logout|docs/|home/example|order/history' views/home/launcher.php
```

Expected: exactly two links exist, targeting `order/index` and
`home/dashboard`; there are no other anchors, enabled buttons, click handlers,
logout/documentation/example/history links, or network-triggering controls.

## Chunk 3: Functional and syntax validation

### Task 4: Validate the complete change

**Files:**
- Verify: all modified PHP files

- [ ] **Step 1: Run syntax checks for every changed PHP file**

Run:

```powershell
php -l program/script/HomeController.php
php -l views/home/launcher.php
```

Expected: both commands report no syntax errors.

- [ ] **Step 2: Run the existing targeted Order tests when the configured
  test environment is available**

Run:

```powershell
php tests/test_order_model.php
php tests/test_e2e_ordering.php
```

These legacy scripts currently hardcode `BASE_PATH = 'C:/laragon/www'`.
Expected when run from that configured Laragon path: existing tests pass; the
dashboard-only change must not alter Order model or ordering behavior. If the
current checkout is not available at that path, record the tests as blocked by
the hardcoded test harness rather than changing unrelated test infrastructure.

- [ ] **Step 3: Verify the authenticated navigation manually**

Start the local server with the repository entry point as its router:

```powershell
php -S localhost:8000 index.php
```

Use the configured credentials from `program/config/data.php` in the local
environment (or the local, non-committed `data.php` values) and keep the same
browser session/cookie while checking:

1. An unauthenticated request to `/` still shows the login screen.
2. After login, `/` shows only Order, the non-clickable placeholder, and
   “Otwórz pulpit”.
3. Order opens `/order/index`.
4. “Otwórz pulpit” opens `/home/dashboard` and displays the unchanged
   existing dashboard.
5. Direct `/home/dashboard` access redirects an unauthenticated user to
   login.
6. Order’s “Pulpit” link returns to `/home/index`, the new launcher.

- [ ] **Step 4: Inspect the final diff**

Run:

```powershell
git --no-pager diff --check
git --no-pager status --short
```

Expected: no whitespace errors, only the intended controller/view changes
remain unstaged, and no generated files or secrets are added.

The user will commit the implementation manually after local verification, in
accordance with the repository instructions.
