# Launcher Logout Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a clear logout action to the post-login launcher without changing its three main module elements or the existing session logic.

**Architecture:** Keep `HomeController::actionLogout()` and the session flow unchanged. Add one secondary link in `views/home/launcher.php`, outside the module list, using the existing `App::baseUrl()` helper and the current `home/logout` action.

**Tech Stack:** PHP MVC views, `App::baseUrl()`, existing inline launcher CSS, PHP CLI syntax checks, and the local HTTP flow.

---

## File map

- Modify: `views/home/launcher.php`
  - Add the secondary “Wyloguj” link in the launcher header.
  - Preserve the three existing module-area elements and their order.
- Preserve: `program/script/HomeController.php`
  - Do not modify `actionLogout()` or authentication/session behavior.
- Verify: `views/home/dashboard.php`
  - Confirm the existing dashboard and its logout link remain unchanged.
- Documentation:
  - `docs/superpowers/specs/2026-09-18-launcher-logout-design.md`

## Chunk 1: Add the launcher logout action

### Task 1: Add a secondary logout link

**Files:**
- Modify: `views/home/launcher.php` near the `<body>`/launcher header

- [ ] **Step 1: Verify the current view syntax**

Run:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
& $php -l views/home/launcher.php
```

Expected: `No syntax errors detected in views/home/launcher.php`.

- [ ] **Step 2: Add the logout link outside the module list**

Add a small secondary link in the launcher header, before the module list:

```php
<a class="logout-link" href="<?= $base ?>home/logout">Wyloguj</a>
```

The link must use the already-defined `$base` variable and must not be placed
inside `.module-list`. It must not replace or reorder Order, the placeholder,
or “Otwórz pulpit”.

- [ ] **Step 3: Add focused styling**

Add only the CSS needed for a visible, accessible secondary action:

```css
.launcher-header {
    position: relative;
}

.logout-link {
    display: inline-block;
    margin-top: 14px;
    color: #dc2626;
    font-size: .9rem;
    font-weight: 600;
    text-decoration: none;
}

.logout-link:hover,
.logout-link:focus-visible {
    color: #991b1b;
    text-decoration: underline;
}

.logout-link:focus-visible {
    outline: 2px solid #dc2626;
    outline-offset: 3px;
}
```

Keep the action visually secondary; do not add JavaScript confirmation or
change global styles.

- [ ] **Step 4: Verify the view syntax after editing**

Run:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
& $php -l views/home/launcher.php
```

Expected: no syntax errors.

## Chunk 2: Verify navigation and logout behavior

### Task 2: Validate the launcher contract

**Files:**
- Verify: `views/home/launcher.php`
- Verify: `program/script/HomeController.php`
- Verify: `views/home/dashboard.php`

- [ ] **Step 1: Confirm only the launcher view changed**

Run:

```powershell
git --no-pager diff --name-only
```

Expected: `views/home/launcher.php` is the only implementation file changed.

- [ ] **Step 2: Confirm the launcher has the required links**

Run:

```powershell
rg -n -C 4 'module-list|Wyloguj|home/logout|order/index|home/dashboard|Miejsce na następny moduł' views/home/launcher.php
```

Expected:

- one `home/logout` anchor labeled “Wyloguj” before the opening
  `.module-list` element, not nested inside it,
- one Order link,
- one dashboard link,
- one non-interactive placeholder,
- no new button or JavaScript handler.

- [ ] **Step 3: Confirm session logic and old dashboard are unchanged**

Run:

```powershell
git --no-pager diff -- program/script/HomeController.php views/home/dashboard.php
```

Expected: no diff in either file.

## Chunk 3: Functional validation

### Task 3: Test the logout flow

**Files:**
- Verify: `views/home/launcher.php`
- Verify: `program/script/HomeController.php`

- [ ] **Step 1: Start the local PHP server**

From the repository root, with the local ignored `program/config/data.php`
available, run:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
& $php -S 127.0.0.1:8000 index.php
```

- [ ] **Step 2: Verify the authenticated launcher exposes logout**

Using the configured local credentials and one persistent browser/session
cookie:

1. Open `/home/login` and preserve the returned session cookie.
2. Extract the hidden CSRF field name and value from the login form.
3. Submit `login`, `password`, and that CSRF field in a POST using the same
   session cookie.
4. Follow or request `/home/index` with the same cookie and confirm its HTML
   contains an anchor `href` ending in `home/logout` labeled “Wyloguj”,
   an Order anchor targeting `order/index`, the exact placeholder text
   `Miejsce na następny moduł`, and a dashboard anchor targeting
   `home/dashboard`.

- [ ] **Step 3: Verify logout ends the session**

With the same session:

1. Request `/home/logout`.
2. Confirm the response redirects to `/home/login`.
3. Request `/home/dashboard` with the same session.
4. Confirm it redirects to `/home/login`, proving the session was ended.

- [ ] **Step 4: Run the final diff checks**

Run:

```powershell
git --no-pager diff --check
git --no-pager status --short
```

Expected: no whitespace errors, no changes to `data.php`, and only the
intended launcher change remains.

The user commits the implementation manually after verification, in accordance
with the repository instructions.
