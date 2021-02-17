# Messenger Faker Commands

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

## This package is an addon for [rtippin/messenger][link-messenger]

### It is NOT advised to install this in a production app.

### Features:
- Commands to mock realtime events such as knocks, typing, marking read, online status.
- Command to seed realtime messages with typing included.

---

# Installation

### Via Composer

``` bash
$ composer require rtippin/messenger-faker --dev
```

---

# Commands

- `php artisan messenger:faker:knock {thread}`
    * Send a knock to the given thread.
- `php artisan messenger:faker:message {thread}` | `--count=5` | `--delay=3` | `--admins`
    * Make participants send messages. Will also emit typing and mark read.
    * Default count of 5 messages sent.
    * Default delay of 3 seconds between each message.
    * Admins flag will only use admin participants if using a group thread.
- `php artisan messenger:faker:status {thread}` | `--status=online` | `--admins`
    * Set participants online status. Default of online, May use (online/offline/away)
    * Admins flag will only use admin participants if using a group thread.
- `php artisan messenger:faker:read {thread}` | `--admins`
    * Mark participants in the thread as read.
    * Admins flag will only use admin participants if using a group thread.
- `php artisan messenger:faker:typing {thread}` | `--admins`
    * Make participants in the thread type.
    * Admins flag will only use admin participants if using a group thread.
- `php artisan messenger:faker:unread {thread}` | `--admins`
    * Mark participants in the thread as unread.
    * Admins flag will only use admin participants if using a group thread.

---

[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger-faker.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger-faker.svg?style=plastic&cacheSeconds=3600
[ico-styleci]: https://styleci.io/repos/339475680/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger-faker?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger-faker
[link-downloads]: https://packagist.org/packages/rtippin/messenger-faker
[link-license]: https://packagist.org/packages/rtippin/messenger-faker
[link-styleci]: https://styleci.io/repos/339475680
[link-messenger]: https://github.com/RTippin/messenger