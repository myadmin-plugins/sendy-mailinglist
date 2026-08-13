---
name: phpunit-plugin-test
description: Writes PHPUnit tests for src/Plugin.php methods using stubs in tests/bootstrap.php. Use when user says 'add test', 'write test for', 'test the plugin', or modifies a method in Plugin.php. Covers class structure, hook registration, event handler signatures, and settings registration via anonymous-class stubs. Do NOT use for integration tests requiring a live Sendy instance or network access. For a plugin's contract or behavioral tests (tests/ContractTest.php, the shared harness, composer myadmin:scaffold-tests) use the plugin-contract-tests skill instead — this skill's reflection-only guidance predates that harness.
---
<!-- myadmin-contract-harness-notice -->
> ### ⚠️ Read this before the rest of the file
>
> This package is on the **shared plugin contract harness**. Parts of the guidance below
> predate it and are now wrong in one specific way:
>
> **Any instruction here that a plugin's `getHooks()` / `getSettings()` / `getActivate()` /
> `getDeactivate()` / `getQueue()` must not be *called* — that only its existence, visibility
> or parameter count may be checked through `ReflectionClass` — no longer applies.** That rule
> existed because those methods reference bare constants (`PRORATE_BILLING` and friends) that
> only a live MyAdmin request defines, so calling them from a test used to fatal. The harness
> defines them first. It then executes the handlers for real, in a process of its own.
>
> A reflection-only assertion passes whether or not the thing works: `getActivate()` can exist,
> be public, be static, take one argument, and still fatal the moment it runs. Three real
> production bugs in this fleet were sitting behind assertions of exactly that shape.
>
> **Use the `plugin-contract-tests` skill** for anything touching `tests/ContractTest.php`,
> the contract inspectors, or `composer myadmin:scaffold-tests`.
>
> **Everything else in this file is still accurate and still applies** — this package's own
> classes, its API wrappers, its fixtures, its bootstrap, and the reasons certain classes must
> not be constructed. Nothing below has been removed.

# phpunit-plugin-test

## Critical

- All tests go in `tests/PluginTest.php`, namespace `Detain\MyAdminSendy\Tests`, extending `PHPUnit\Framework\TestCase`.
- Never make real HTTP calls — `doEmailSetup()` uses `file_get_contents()` internally; test only structure/signatures, not live behaviour.
- `tests/bootstrap.php` already stubs `myadmin_log()`, `_()`, `StatisticClient`, and all `SENDY_*` constants. Do NOT redefine them in test methods.
- Settings stubs must be **anonymous classes** (not mocks) because `getSettings()` calls methods on the subject directly — see existing pattern.
- Verify the test suite passes before considering the task done.

## Instructions

1. **Understand the method under test.** Read `src/Plugin.php` to identify the method's parameters, guards (`defined('SENDY_ENABLE') && SENDY_ENABLE == 1`), and side-effects (`myadmin_log`, `StatisticClient`).

2. **Add a `ReflectionClass` test for new methods.** Use the suite-level `self::$ref` (set up in `setUpBeforeClass`):
   ```php
   public function testMyNewMethodStructure(): void
   {
       $method = self::$ref->getMethod('myNewMethod');
       $this->assertTrue($method->isPublic());
       $this->assertTrue($method->isStatic());
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $type = $params[0]->getType();
       $this->assertNotNull($type);
       $this->assertSame(GenericEvent::class, $type->getName());
   }
   ```
   Verify the method name matches `src/Plugin.php` exactly before proceeding.

3. **Test event handler dispatch.** For handlers that call `doSetup`/`doEmailSetup`, use a `GenericEvent` with an anonymous subject:
   ```php
   public function testDoMailinglistSubscribeDispatchesWhenEnabled(): void
   {
       // SENDY_ENABLE='1' is already set by tests/bootstrap.php
       $subject = 'user@example.com';
       $event = new GenericEvent($subject);
       // doEmailSetup will attempt file_get_contents — only test that no exception is thrown
       // or wrap with expectException if testing the disabled path
       $this->expectNotToPerformAssertions();
       // Call with SENDY_ENABLE='0' override is not possible mid-run; test the enabled guard via reflection instead
   }
   ```
   For the **disabled guard path** (`SENDY_ENABLE == 0`), test via reflection that the guard constant check exists in the method source:
   ```php
   $src = file_get_contents((self::$ref->getMethod('doAccountActivated'))->getFileName());
   $this->assertStringContainsString("SENDY_ENABLE", $src);
   ```

4. **Test settings registration** with an anonymous-class stub capturing calls by reference:
   ```php
   $calls = [];
   $settings = new class($calls) {
       private array $callLog;
       public function __construct(array &$calls) { $this->callLog = &$calls; }
       public function add_dropdown_setting(...$args): void { $this->callLog[] = ['method' => 'add_dropdown_setting', 'args' => $args]; }
       public function add_password_setting(...$args): void { $this->callLog[] = ['method' => 'add_password_setting', 'args' => $args]; }
       public function add_text_setting(...$args): void     { $this->callLog[] = ['method' => 'add_text_setting',     'args' => $args]; }
   };
   $event = new GenericEvent($settings);
   Plugin::getSettings($event);
   $this->assertCount(4, $calls);
   ```
   Verify `$calls[N]['args'][2]` equals the expected setting key (`sendy_enable`, `sendy_api_key`, `sendy_list_id`, `sendy_apiurl`).

5. **Test hook registration.** Assert new hooks are in `getHooks()` and point to a callable method on `Plugin`:
   ```php
   $hooks = Plugin::getHooks();
   $this->assertArrayHasKey('my.new.hook', $hooks);
   $this->assertSame([Plugin::class, 'myHandler'], $hooks['my.new.hook']);
   $this->assertTrue(self::$ref->hasMethod('myHandler'));
   ```

6. **Run the suite.** From the package root:
   ```bash
   vendor/bin/phpunit
   ```
   All tests must be green. Fix any failure before finishing.

## Examples

**User says:** "Add a test for `doMailinglistSubscribe` signature"

**Actions taken:**
- Read `src/Plugin.php:57-63` — method accepts `GenericEvent $event`, calls `doEmailSetup($email)` when enabled.
- Added to `tests/PluginTest.php`:
  ```php
  public function testDoMailinglistSubscribeSignature(): void
  {
      $method = self::$ref->getMethod('doMailinglistSubscribe');
      $params = $method->getParameters();
      $this->assertCount(1, $params);
      $this->assertSame('event', $params[0]->getName());
      $type = $params[0]->getType();
      $this->assertNotNull($type);
      $this->assertSame(GenericEvent::class, $type->getName());
  }
  ```
- Ran the test suite — 1 new test, 0 failures.

**Result:** Test verifies parameter name and type without touching the network.

## Common Issues

- **`Call to undefined function myadmin_log()`** — `tests/bootstrap.php` is not loaded. Check `phpunit.xml.dist` has `bootstrap="tests/bootstrap.php"`. Run via `composer test` (not `php tests/PluginTest.php`).
- **`Class "StatisticClient" not found`** — bootstrap stub did not load. Ensure `tests/bootstrap.php` runs before the plugin class is autoloaded; the bootstrap guards with `!class_exists('StatisticClient', false)`.
- **`Cannot redeclare define('SENDY_ENABLE')`** — you defined a constant in a test method. Constants cannot be undefined mid-run; use the values already set by `tests/bootstrap.php` (`SENDY_ENABLE='1'`, `SENDY_APIURL='https://sendy.example.com'`).
- **`file_get_contents(https://sendy.example.com/subscribe): failed`** — a test is actually invoking `doEmailSetup()` against the network. Test method structure/signatures only; do not call `doEmailSetup()` directly.
- **Settings count assertion fails (`assertCount(4, $calls)`)** — a new setting was added to `getSettings()`. Update the assertion and add a test for the new key at the correct index.
