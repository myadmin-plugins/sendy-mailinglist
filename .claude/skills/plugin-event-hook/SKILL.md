---
name: plugin-event-hook
description: Adds a new event hook to src/Plugin.php following the existing pattern. Registers the hook in getHooks(), implements the handler method with GenericEvent $event, guards with SENDY_ENABLE, and calls the appropriate internal method. Use when user says 'add hook', 'listen to event', 'handle event', or needs to respond to a new MyAdmin event. Do NOT use for modifying existing hooks or for the system.settings hook (which has its own settings-registration pattern).
---
# plugin-event-hook

## Critical

- ALL handler methods MUST be `public static` — the event dispatcher calls them statically
- ALWAYS type-hint `GenericEvent $event` — never use untyped `$event`
- ALWAYS guard the body with `if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1)` — loose `==` not `===`
- The hook key in `getHooks()` MUST use dot notation (e.g. `account.cancelled`)
- Do NOT add a handler that does side-effectful work directly — delegate to an internal `doXxx()` method
- Adding a new public method changes the public API; `testExpectedPublicMethods()` in `tests/PluginTest.php` will fail unless you add the new method name to its `$expected` array

## Instructions

1. **Identify the event name and subject type.**  
   Confirm the event name uses dot notation (e.g. `account.cancelled`). Determine what `$event->getSubject()` returns (an account object, an email string, etc.).  
   Verify no existing key in `getHooks()` in `src/Plugin.php` already handles this event before continuing.

2. **Register the hook in `getHooks()`** in `src/Plugin.php`.  
   Add one entry following the exact format of existing entries:
   ```php
   'account.cancelled' => [__CLASS__, 'doAccountCancelled'],
   ```
   Use `__CLASS__` — never the string `'Detain\MyAdminSendy\Plugin'`.  
   Verify the method name you reference does not yet exist — you will create it in Step 3.

3. **Implement the public static handler method** directly after the last existing handler (before `getSettings()`).  
   Copy this exact skeleton — replace subject extraction to match the event's subject type:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function doAccountCancelled(GenericEvent $event)
   {
       $account = $event->getSubject();
       if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1) {
           self::doHandleCancellation($account->getId());
       }
   }
   ```
   For string subjects (like an email), use `$email = $event->getSubject();` and pass `$email` directly, matching `doMailinglistSubscribe` in `src/Plugin.php`.

4. **Implement the internal `doHandleXxx()` worker method.**  
   Add it after the handler. Log entry with `myadmin_log()`, perform the action, guard optional `StatisticClient` calls:
   ```php
   public static function doHandleCancellation($accountId)
   {
       myadmin_log('accounts', 'info', "sendy_cancellation({$accountId}) Called", __LINE__, __FILE__);
       // ... perform work ...
       if (class_exists(\StatisticClient::class, false)) {
           \StatisticClient::tick('Sendy', 'unsubscribe');
       }
   }
   ```
   Do NOT access `$_GET`/`$_POST` directly — data comes from the event subject.

5. **Update `tests/PluginTest.php`.**  
   Add the new handler name to the `$expected` array in `testExpectedPublicMethods()` at `tests/PluginTest.php:623`, and add a new test asserting the hook key is present:
   ```php
   public function testGetHooksContainsAccountCancelled(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey('account.cancelled', $hooks);
       $this->assertSame([Plugin::class, 'doAccountCancelled'], $hooks['account.cancelled']);
   }
   ```
   Verify `vendor/bin/phpunit` passes before committing.

## Examples

**User says:** "Add a hook for `account.cancelled` that unsubscribes the account from Sendy."

**Actions taken:**
1. Confirm `account.cancelled` is not in `getHooks()` in `src/Plugin.php` — it isn't.
2. Add to `getHooks()`: `'account.cancelled' => [__CLASS__, 'doAccountCancelled'],`
3. Add handler after `doMailinglistSubscribe()`:
   ```php
   public static function doAccountCancelled(GenericEvent $event)
   {
       $account = $event->getSubject();
       if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1) {
           self::doHandleCancellation($account->getId());
       }
   }
   ```
4. Add worker:
   ```php
   public static function doHandleCancellation($accountId)
   {
       myadmin_log('accounts', 'info', "sendy_cancellation({$accountId}) Called", __LINE__, __FILE__);
       // unsubscribe HTTP POST to SENDY_APIURL.'/unsubscribe'
   }
   ```
5. Add `'doAccountCancelled'` and `'doHandleCancellation'` to `$expected` in `testExpectedPublicMethods()` at `tests/PluginTest.php:623`; add `testGetHooksContainsAccountCancelled()` test.

**Result:** `vendor/bin/phpunit` passes; `Plugin::getHooks()` returns the new key.

## Common Issues

- **`testExpectedPublicMethods` fails with "Arrays do not match":** You added a public method but didn't add its name to `$expected` at `tests/PluginTest.php:623`. Add every new `public static function` name to that array.
- **`testHookEventNamesAreDotNotation` fails:** Your event key is missing a `.` (e.g. `accountcancelled`). Use dot notation: `account.cancelled`.
- **`testAllHookCallbacksAreCallable` fails with "references non-existent method":** The method name in `getHooks()` doesn't match the actual method name. Check for typos — PHP method names are case-insensitive but the string comparison in the test is exact.
- **`SENDY_ENABLE` guard silently skips execution in tests:** `tests/bootstrap.php` defines `SENDY_ENABLE` as `'1'` for test runs. If your guard never executes, check you used `== 1` not `=== 1` (the constant is a string `'1'`).
- **`StatisticClient` call throws "Class not found":** You called `\StatisticClient::tick(...)` without the `class_exists(\StatisticClient::class, false)` guard. Always wrap every `StatisticClient` call in this check (see `src/Plugin.php:113`).
