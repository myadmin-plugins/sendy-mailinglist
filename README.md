# MyAdmin Sendy Mailing List Plugin

Sendy mailing list integration plugin for the [MyAdmin](https://github.com/detain/myadmin) control panel. Provides automated subscriber management through the [Sendy](https://sendy.co/) API, including event-driven hooks for account activation and mailing list subscription workflows.

[![Build Status](https://github.com/detain/myadmin-sendy-mailinglist/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-sendy-mailinglist/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-sendy-mailinglist/version)](https://packagist.org/packages/detain/myadmin-sendy-mailinglist)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-sendy-mailinglist/downloads)](https://packagist.org/packages/detain/myadmin-sendy-mailinglist)
[![License](https://poser.pugx.org/detain/myadmin-sendy-mailinglist/license)](https://packagist.org/packages/detain/myadmin-sendy-mailinglist)

## Features

- Automatic mailing list subscription on account activation
- Event-driven architecture using Symfony EventDispatcher
- Configurable Sendy API endpoint, API key, and list ID via admin settings
- Toggle enable/disable from the MyAdmin settings panel

## Requirements

- PHP >= 7.4
- ext-soap
- Symfony EventDispatcher ^5.0 || ^6.0 || ^7.0

## Installation

```sh
composer require detain/myadmin-sendy-mailinglist
```

## Configuration

The plugin registers four settings in the MyAdmin admin panel under **Accounts > Sendy**:

| Setting         | Description                          |
|-----------------|--------------------------------------|
| `sendy_enable`  | Enable or disable Sendy integration  |
| `sendy_api_key` | Your Sendy API key                   |
| `sendy_list_id` | The target Sendy mailing list ID     |
| `sendy_apiurl`  | Base URL of your Sendy installation  |

## Event Hooks

| Event                    | Handler                   | Description                            |
|--------------------------|---------------------------|----------------------------------------|
| `system.settings`        | `getSettings`             | Registers admin panel settings         |
| `account.activated`      | `doAccountActivated`      | Subscribes user on account activation  |
| `mailinglist.subscribe`  | `doMailinglistSubscribe`  | Subscribes an email address to the list|

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) license.
