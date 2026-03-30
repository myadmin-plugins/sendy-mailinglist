---
name: sendy-api-call
description: Makes an HTTP POST to the Sendy API following the pattern in `Plugin::doEmailSetup()`. Builds `$postarray` with `email`, `api_key`, `list`, `boolean`, calls `http_build_query()`, creates a stream context, posts to `SENDY_APIURL.'/subscribe'`, and handles StatisticClient tick/report. Use when user says 'call Sendy API', 'subscribe email', 'send to Sendy', or adds a new Sendy endpoint call. Do NOT use for settings registration (`add_dropdown_setting`, `add_text_setting`, `add_password_setting`).
---
# sendy-api-call

## Critical

- Always guard the call with `if (defined('SENDY_ENABLE') && SENDY_ENABLE == 1)` before invoking any Sendy method.
- Never interpolate `$_GET`/`$_POST` directly — all user input must be validated before it reaches `$postarray`.
- `SENDY_API_KEY`, `SENDY_LIST_ID`, and `SENDY_APIURL` are PHP constants — access them as bare constants, not variables.
- Always wrap `StatisticClient` calls with `if (class_exists(\StatisticClient::class, false))` — it is an optional dependency.
- Successful Sendy responses are exactly `'1'` or `'Already subscribed.'` — anything else is an error.

## Instructions

1. **Log the call entry** before any HTTP work:
   ```php
   myadmin_log('accounts', 'info', "sendy_setup({$email}) Called", __LINE__, __FILE__);
   ```
   Verify `myadmin_log` is available (stubbed in `tests/bootstrap.php` for tests).

2. **Build `$postarray`** with the four required keys:
   ```php
   $postarray = [
       'email'   => $email,
       'api_key' => SENDY_API_KEY,
       'list'    => SENDY_LIST_ID,
       'boolean' => 'true'
   ];
   ```
   If the caller passes extra params (e.g. `['name' => $name]`), merge them in:
   ```php
   if ($params !== false) {
       $postarray = array_merge($postarray, $params);
   }
   ```
   Verify all four base keys are present before proceeding.

3. **Encode and build the stream context**:
   ```php
   $postdata = http_build_query($postarray);
   $opts = [
       'http' => [
           'method'  => 'POST',
           'header'  => 'Content-type: application/x-www-form-urlencoded',
           'content' => $postdata
       ]
   ];
   $context = stream_context_create($opts);
   ```

4. **Tick StatisticClient** (before the HTTP call):
   ```php
   if (class_exists(\StatisticClient::class, false)) {
       \StatisticClient::tick('Sendy', 'subscribe');
   }
   ```

5. **Execute the POST** and trim the response:
   ```php
   $result = trim(file_get_contents(SENDY_APIURL.'/subscribe', false, $context));
   ```
   For a different endpoint (e.g. unsubscribe) replace `'/subscribe'` with the target path.

6. **Handle the response** — report success or failure to StatisticClient and log errors:
   ```php
   if ($result != '1' && $result != 'Already subscribed.') {
       if (class_exists(\StatisticClient::class, false)) {
           \StatisticClient::report('Sendy', 'subscribe', false, 100, $result, STATISTICS_SERVER);
       }
       myadmin_log('accounts', 'info', "Sendy Response: {$result}", __LINE__, __FILE__);
   } else {
       if (class_exists(\StatisticClient::class, false)) {
           \StatisticClient::report('Sendy', 'subscribe', true, 0, '', STATISTICS_SERVER);
       }
   }
   ```

## Examples

**User says:** "Add a method that subscribes a new email with a name to the Sendy list."

**Actions taken:**
- Add `doEmailSetup(string $email, $params = false)` to `src/Plugin.php` following steps 1–6.
- Caller passes `['name' => $name]` as `$params`; `array_merge` appends it to `$postarray`.
- Hook handler guards with `SENDY_ENABLE == 1` before calling `self::doEmailSetup($email, ['name' => $name])`.

**Result:**
```php
public static function doEmailSetup($email, $params = false)
{
    myadmin_log('accounts', 'info', "sendy_setup({$email}) Called", __LINE__, __FILE__);
    $postarray = [
        'email'   => $email,
        'api_key' => SENDY_API_KEY,
        'list'    => SENDY_LIST_ID,
        'boolean' => 'true'
    ];
    if ($params !== false) {
        $postarray = array_merge($postarray, $params);
    }
    $postdata = http_build_query($postarray);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => $postdata]];
    if (class_exists(\StatisticClient::class, false)) {
        \StatisticClient::tick('Sendy', 'subscribe');
    }
    $context = stream_context_create($opts);
    $result = trim(file_get_contents(SENDY_APIURL.'/subscribe', false, $context));
    if ($result != '1' && $result != 'Already subscribed.') {
        if (class_exists(\StatisticClient::class, false)) {
            \StatisticClient::report('Sendy', 'subscribe', false, 100, $result, STATISTICS_SERVER);
        }
        myadmin_log('accounts', 'info', "Sendy Response: {$result}", __LINE__, __FILE__);
    } else {
        if (class_exists(\StatisticClient::class, false)) {
            \StatisticClient::report('Sendy', 'subscribe', true, 0, '', STATISTICS_SERVER);
        }
    }
}
```

## Common Issues

- **`file_get_contents()` returns `false` or empty string:** `SENDY_APIURL` is not defined or is wrong. Run `var_dump(SENDY_APIURL)` — must be a full URL like `https://sendy.example.com` with no trailing slash.
- **`Undefined constant SENDY_API_KEY`:** The constant is not defined. In tests, constants are set in `tests/bootstrap.php`. In production they come from the MyAdmin settings system. Add `if (!defined('SENDY_API_KEY'))` guard when testing outside the full stack.
- **`Call to undefined function myadmin_log()`:** Running outside MyAdmin core without the test bootstrap. Include `tests/bootstrap.php` or ensure MyAdmin core is loaded.
- **`Call to undefined class StatisticClient`:** Missing `if (class_exists(\StatisticClient::class, false))` guard. Never call `StatisticClient` directly — always use the guard.
- **Sendy returns an unexpected string (not `'1'` or `'Already subscribed.'`):** The API key, list ID, or URL is wrong. Check `SENDY_API_KEY` and `SENDY_LIST_ID` values match the Sendy admin panel. The error string is logged via `myadmin_log` and reported to `StatisticClient::report` with error code `100`.
- **`array_merge` overwrites base keys:** Never pass `email`, `api_key`, `list`, or `boolean` inside `$params` — they would silently overwrite the base values. Only pass supplemental keys like `name`.