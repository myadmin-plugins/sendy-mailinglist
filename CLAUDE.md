# MyAdmin Sendy Mailing List Plugin

Sendy mailing list integration plugin for the MyAdmin control panel. Namespace: `Detain\MyAdminSendy\` → `src/` · Tests: `Detain\MyAdminSendy\Tests\` → `tests/`.

## Commands

```bash
composer install        # install deps (requires PHP >= 7.4, ext-soap)
vendor/bin/phpunit      # run all tests
vendor/bin/phpunit tests/PluginTest.php  # run specific test file
```

```bash
vendor/bin/phpunit --coverage-text                  # text coverage report
vendor/bin/phpunit --coverage-html coverage-html/   # HTML coverage report
```

```bash
composer validate       # validate composer.json
composer dump-autoload  # regenerate autoloader
```

## Architecture

- **Plugin entry**: `src/Plugin.php` — single class, all logic here
- **Tests**: `tests/PluginTest.php` · bootstrap stubs: `tests/bootstrap.php`
- **Deps**: `symfony/event-dispatcher` `^5.0||^6.0||^7.0` · optional `workerman/statistics` `StatisticClient`
- **CI/CD**: `.github/` contains workflows (`.github/workflows/tests.yml`) for automated testing and deployment pipelines
- **IDE config**: `.idea/` contains inspectionProfiles, deployment.xml, and encodings.xml for PhpStorm

## Event Hooks

All hooks registered in `Plugin::getHooks()` as `['event.name' => [__CLASS__, 'methodName']]`:

| Hook | Handler | Action |
|------|---------|--------|
| `system.settings` | `getSettings` | registers `sendy_enable`, `sendy_api_key`, `sendy_list_id`, `sendy_apiurl` |
| `account.activated` | `doAccountActivated` | calls `doSetup($account->getId())` |
| `mailinglist.subscribe` | `doMailinglistSubscribe` | calls `doEmailSetup($email)` |

## Config Constants

All config via PHP constants (`tests/bootstrap.php` defines test values):
- `SENDY_ENABLE` — `'0'` or `'1'`
- `SENDY_API_KEY` — Sendy API key
- `SENDY_LIST_ID` — mailing list ID
- `SENDY_APIURL` — base URL, e.g. `https://sendy.example.com`

## Conventions

- Guard handlers: `if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1)`
- Log via `myadmin_log('accounts', 'info', $message, __LINE__, __FILE__)`
- HTTP POST to Sendy uses `stream_context_create()` + `file_get_contents(SENDY_APIURL.'/subscribe', false, $context)`
- Guard optional dep: `if (class_exists(\StatisticClient::class, false))`
- Settings registration: `$settings->add_dropdown_setting()` / `add_password_setting()` / `add_text_setting()`
- i18n: wrap all user-facing strings in `_()`
- Successful Sendy response is `'1'` or `'Already subscribed.'`; anything else is an error

## Testing

`tests/bootstrap.php` stubs `myadmin_log()`, `_()`, `StatisticClient`, and all `SENDY_*` constants so tests run standalone without MyAdmin core.

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
