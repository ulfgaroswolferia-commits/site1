# Order Module Label Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename only the launcher module title from `Order` to `Zamówienia z cennika excel`.

**Architecture:** Make one presentation-only edit in `views/home/launcher.php`. Keep the existing `order/index` URL, controller/model names, internal Order views, placeholder, dashboard link, and logout link unchanged.

**Tech Stack:** PHP view template, `App::baseUrl()`, PHP CLI syntax check, and a local HTTP response check.

---

## File map

- Modify: `views/home/launcher.php`
  - Replace the first module card title text only.
- Preserve: `program/script/OrderController.php`, `program/model/OrderModel.php`, and all `views/order/*.php`
  - No technical route or internal module naming changes.
- Verify: `views/home/launcher.php`
  - Confirm the new label, existing target, and remaining launcher elements.

## Chunk 1: Update the visible module label

### Task 1: Replace the launcher title

**Files:**
- Modify: `views/home/launcher.php` at the first `.module-card-title`

- [ ] **Step 1: Verify the current label and target**

Run:

```powershell
rg -n -C 2 'module-card-title|order/index|Order' views/home/launcher.php
```

Expected: the first module card contains:

```php
<span class="module-card-title">Order</span>
```

and its link still targets `<?= $base ?>order/index`.

- [ ] **Step 2: Replace only the visible title**

Change:

```php
<span class="module-card-title">Order</span>
```

to:

```php
<span class="module-card-title">Zamówienia z cennika excel</span>
```

Do not change the surrounding anchor, class names, description, or any other
launcher element.

- [ ] **Step 3: Run the PHP syntax check**

Run:

```powershell
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
& $php -l views/home/launcher.php
```

Expected: `No syntax errors detected in views/home/launcher.php`.

## Chunk 2: Validate unchanged navigation and rendering

### Task 2: Verify the exact visible result

**Files:**
- Verify: `views/home/launcher.php`
- Verify: `program/script/OrderController.php`
- Verify: `program/model/OrderModel.php`
- Verify: `views/order/*.php`

- [ ] **Step 1: Confirm the launcher contract**

Run:

```powershell
rg -n 'Zamówienia z cennika excel|Order|order/index|Miejsce na następny moduł|home/dashboard|Wyloguj|home/logout' views/home/launcher.php
```

Expected:

- the new label appears once as the first module title,
- `Order` no longer appears as that title,
- the first module still links to `order/index`,
- the placeholder, dashboard link, and logout link remain present.

- [ ] **Step 2: Confirm no internal module code changed**

Run:

```powershell
git --no-pager diff --name-only
```

Expected: only `views/home/launcher.php` is changed for the implementation.

- [ ] **Step 3: Check the rendered authenticated launcher**

Using the local PHP server and an authenticated session, request:

```text
http://localhost/home/index
```

Expected: the page visibly shows `Zamówienia z cennika excel`, and clicking
that card still opens the existing Order module.

- [ ] **Step 4: Run final repository checks**

Run:

```powershell
git --no-pager diff --check
git --no-pager status --short
```

Expected: no whitespace errors, no changes to `program/config/data.php`, and
only the intended launcher label change remains.

The user commits the implementation manually after verification, in accordance
with the repository instructions.
