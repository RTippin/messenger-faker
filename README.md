# Messenger Faker Commands

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Tests][ico-test]][link-test]
[![StyleCI][ico-styleci]][link-styleci]
[![License][ico-license]][link-license]

---

![Preview](https://i.imgur.com/NTjc1Pm.png)

## This package is an addon for [rtippin/messenger][link-messenger]

### It is NOT advised to install this in a production app.

### Features:
- Commands to mock realtime events such as knocks, typing, and marking read.
- Command to seed realtime messages with typing.
- Commands to seed attachment messages (images, documents, audio, videos).
- Commands to seed system messages and message reactions.
- `FakerBot` pre-registered with `Messenger` that allows you to trigger our commands through chat-bots.

---

# Installation

### Via Composer

``` bash
$ composer require rtippin/messenger-faker --dev
```

---

# Config

- Default values for local storage location of the files we use when seeding.
  - When seeding using local files, a random file from the message types specified folder will be used.
  - When seeding image files with no url/local flag specified, it will use the default image url from the config.
- Flag to enable or disable registering our `FakerBot`.

***Defaults***
```php
'paths' => [
    'images' => storage_path('faker/images'),
    'documents' => storage_path('faker/documents'),
    'audio' => storage_path('faker/audio'),
    'videos' => storage_path('faker/videos'),
],

'default_image_url' => 'https://source.unsplash.com/random',

'enable_bot' => true,
```

### To override the file paths, please publish our config and edit accordingly

``` bash
$ php artisan vendor:publish --tag=messenger-faker
```

___

# Commands

---

### `php artisan messenger:faker:knock {thread?}`
- Send a knock to the given thread.

---

### `php artisan messenger:faker:message {thread?}` | `--count=5` | `--delay=2` | `--admins` | `--silent`
- Make participants send messages. Will also emit typing and mark read.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many messages are sent.
- `--delay=X` flag to set delay in seconds between each message.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:react {thread?}` | `--count=5` | `--messages=5` | `--delay=1` | `--admins` | `--silent`
- Make participants add reactions to the latest messages.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many reactions are added.
- `--messages=X` flag to set how many latest messages are chosen at random to be reacted to.
- `--delay=X` flag to set delay in seconds between each reaction.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:system {thread?}` | `--type=` | `--count=1` | `--delay=2` | `--admins` | `--silent`
- Make participants send system messages.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many system messages are sent.
- `--type=X` flag to set the system message type. `88, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103`
- `--delay=X` flag to set delay in seconds between each system message.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:image {thread?}` | `--count=1` | `--delay=2` | `--admins` | `--local` | `--url=` | `--silent`
- Make participants send image messages. Will also emit typing and mark read. If `--local` or `--url` is not set, we pull images from the default image url in the config.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many images are sent.
- `--delay=X` flag to set delay in seconds between each image.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--local` flag will choose a random image from the directory specified for images in the config file.
- `--url=X` flag lets you directly specify an image URL to download and emit.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:document {thread?}` | `--count=1` | `--delay=2` | `--admins` | `--url=` | `--silent`
- Make participants send document messages. Will also emit typing and mark read. If `--url` is not set, will choose a random document from the directory specified for documents in the config file.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many documents are sent.
- `--delay=X` flag to set delay in seconds between each document.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--url=X` flag lets you directly specify a document URL to download and emit.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:audio {thread?}` | `--count=1` | `--delay=2` | `--admins` | `--url=` | `--silent`
- Make participants send audio messages. Will also emit typing and mark read. If `--url` is not set, will choose a random audio file from the directory specified for audio in the config file.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many audio files are sent.
- `--delay=X` flag to set delay in seconds between each audio file.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--url=X` flag lets you directly specify an audio URL to download and emit.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:video {thread?}` | `--count=1` | `--delay=2` | `--admins` | `--url=` | `--silent`
- Make participants send video messages. Will also emit typing and mark read. If `--url` is not set, will choose a random video file from the directory specified for videos in the config file.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many video files are sent.
- `--delay=X` flag to set delay in seconds between each video file.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--url=X` flag lets you directly specify a video URL to download and emit.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:random {thread?}` | `--count=5` | `--delay=2` | `--admins` | `--silent`
- Send random commands using `['audio', 'document', 'image', 'knock', 'message', 'react', 'system', 'typing']`
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--count=X` flag to set how many messages are sent.
- `--delay=X` flag to set delay in seconds between each message.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.
- `--silent` flag that will suppress all broadcast and event dispatches.

---

### `php artisan messenger:faker:read {thread?}` | `--admins`
- Mark participants in the thread as read.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--admins` flag will only use admin participants if using a group thread.

---

### `php artisan messenger:faker:typing {thread?}` | `--admins`
- Make participants in the thread type.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--admins` flag will only use admin participants if using a group thread.
- `--bots` flag will only use bots if using a group thread.

---

### `php artisan messenger:faker:unread {thread?}` | `--admins`
- Mark participants in the thread as unread.
- `{thread?}` ID of the thread you want to seed. Random if not set.
- `--admins` flag will only use admin participants if using a group thread.

---

# FakerBot

---

- Our service provider will have already registered `FakerBot` for you if enabled in our config.
- You should ensure your main `messenger.php` config has the bots feature enabled.
- When you use the messenger API to add handlers onto a bot, you will see our bot listed.
- Once our `FakerBot` is attached to a thread's bot, you can trigger it by sending a message using the following syntax:
  - `!faker {action} {count?} {delay?}`
- Available actions: `audio`, `document`, `image`, `knock`, `message`, `random`, `react`, `system`, `typing`, `video`


[ico-version]: https://img.shields.io/packagist/v/rtippin/messenger-faker.svg?style=plastic&cacheSeconds=3600
[ico-downloads]: https://img.shields.io/packagist/dt/rtippin/messenger-faker.svg?style=plastic&cacheSeconds=3600
[link-test]: https://github.com/RTippin/messenger-faker/actions
[ico-test]: https://img.shields.io/github/actions/workflow/status/rtippin/messenger-faker/test.yml?branch=master&style=plastic
[ico-styleci]: https://styleci.io/repos/339475680/shield?style=plastic&cacheSeconds=3600
[ico-license]: https://img.shields.io/github/license/RTippin/messenger-faker?style=plastic
[link-packagist]: https://packagist.org/packages/rtippin/messenger-faker
[link-downloads]: https://packagist.org/packages/rtippin/messenger-faker
[link-license]: https://packagist.org/packages/rtippin/messenger-faker
[link-styleci]: https://styleci.io/repos/339475680
[link-messenger]: https://github.com/RTippin/messenger