# FreeScout Modernized - Laravel 11

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-AGPL--3.0--or--later-blue.svg)](LICENSE)

> **Modern Laravel 11 implementation of FreeScout - Free self-hosted helpdesk and shared mailbox**

## 🎯 Project Status

**Branch**: `laravel-11-foundation`  
**Progress**: ~45% Complete (Email System Functional!)  
**Latest Update**: November 5, 2025

### What's Working ✅
- Laravel 11.46.1 with PHP 8.2+ foundation
- Complete database layer (27 tables)
- All core controllers and business logic
- **Full email system** with IMAP/SMTP, auto-replies, threading, attachments
- Event system with auto-reply rate limiting
- Bounce and auto-responder detection
- 11 responsive Tailwind CSS views

### 📚 Documentation

- **[Progress Tracking](docs/PROGRESS.md)** - Current status and next steps
- **[Planning Documents](docs/archive/)** - Original analysis and strategy

---

## About FreeScout

Free Self-Hosted Zendesk & Help Scout Alternative

If you want to support the project feel free to **star this repository**. It helps to increase the visibility of the project and let people know that it is valuable. Thanks for your support!

![FreeScout](https://freescout-helpdesk.github.io/img/screenshots/screenshot.png)

## Table of Contents
   * [Demo](#demo)
   * [Features](#features)
   * [Mobile Apps](#mobile-apps)
   * [Requirements](#requirements)
   * [Installation](#installation)
   * [Cloud Hosted](#cloud-hosted)
   * [Modules](#modules)
   * [Tools & Integrations](#tools--integrations)
   * [News & Updates](#news--updates)
   * [Contributing](#contributing)
   * [Screenshots](#screenshots)

## Demo

**[Live Demo](https://demo.freescout.net)**

## Features

  * No limitations on the number of users, tickets, mailboxes, etc.
  * 100% Mobile-friendly.
  * Multilingual: English, Chinese, Croatian, Czech, Danish, Dutch, Finnish, French, German, Hebrew, Hungarian, Italian, Japanese, Kazakh, Korean, Norwegian, Persian, Polish, Portuguese, Romanian, Russian, Spanish, Slovak, Swedish, Turkish, Ukrainian.
  * Seamless email integration.
  * Supports modern Microsoft Exchange authentication.
  * Fully supports screen readers (for visually impaired).
  * Built with strong focus on [security](https://freescout.net/security).
  * Web installer & updater.
  * Starred conversations.
  * Forwarding conversations.
  * Merging conversations.
  * Moving conversations between mailboxes.
  * Phone conversations.
  * Sending new conversations to multiple recipients at once.
  * Collision detection – notice is shown when two agents open the same conversation.
  * Push notifications.
  * Following a conversation.
  * Auto reply.
  * Internal notes.
  * Automatic refreshing of the conversations list without the need to reload the page.
  * Pasting screenshots from the clipboard into the reply area.
  * Configuring notifications on a per user basis.
  * Open tracking.
  * Editing threads.
  * Search.
  * And more…

Need anything else? Suggest features [here](https://freescout.net/request-feature/).

## Mobile Apps

Mobile apps support the same functionality and modules as the web version of your FreeScout installation. Both support agents and administrators can use mobile apps.

<a href="https://freescout.net/android-app/" target="_blank" rel="nofollow"><img alt="Android App" src="https://freescout-helpdesk.github.io/img/apps/android.png" width="200px" /></a> <a href="https://freescout.net/ios-app/" target="_blank" rel="nofollow"><img alt="iOS App" src="https://freescout-helpdesk.github.io/img/apps/ios.png?v=1" width="200px" /></a>

[MacOS Menu Bar App](https://github.com/jonalaniz/scouter)

## Requirements

FreeScout is a pure PHP/MySQL application, so it can be easily deployed even on a [shared hosting](https://github.com/freescout-help-desk/freescout/wiki/Choosing-a-Server).

  * Nginx / Apache / IIS
  * PHP 7.1 - 8.x
  * MySQL 5.0+ / MariaDB 5.0+ / PostgreSQL

There are no minimum system requirements (CPU / RAM) – FreeScout will run on any system.

## Installation

[Installation Guide](https://github.com/freescout-help-desk/freescout/wiki/Installation-Guide)

Images & one-click installs:

* [Docker Image](http://freescout.net/docker/)
* [Softaculous](http://www.softaculous.com/apps/customersupport/FreeScout) (cPanel, Plesk, ISPmanager, H-Sphere, DirectAdmin, InterWorx)
* [Fantastico](http://ff3.netenberg.com/visitors/scripts/freescout/view) (cPanel, DirectAdmin, ISP Manager, ISP Config)
* [Cloudron](https://cloudron.io/store/net.freescout.cloudronapp.html)
* [Ubuntu](https://github.com/freescout-help-desk/freescout/wiki/Installation-Guide#interactive-installation-bash-script-ubuntu) (bash script)

## Cloud Hosted

[Cloud Hosted FreeScout](https://freescout.net/cloud-hosted/)

## Modules

* [Official Modules](https://freescout.net/modules/)
* [Community Modules](https://freescout.net/community-modules/)

## Tools & Integrations
  
  * [API](https://api-docs.freescout.net/)
  * [Migrate to FreeScout](http://freescout.net/migrate/) (from any help desk)
  * [Zapier](https://freescout.net/zapier/)
  * [Make](https://freescout.net/make-integration/) (Integromat)

## News & Updates

Don't miss news, updates and new modules!

[Email Newsletter](https://freescout.net/subscribe/) | [Facebook](https://freescout.net/facebook/) | [Twitter](https://freescout.net/twitter/) | [YouTube](https://freescout.net/youtube/) | [Telegram](https://freescout.net/telegram/) | [RSS](https://freescout.net/feed/)

## Contributing

* [Support the project by leaving a feedback](https://github.com/freescout-help-desk/freescout/issues/288)
* [Development Guide](https://github.com/freescout-help-desk/freescout/wiki/Development-Guide)
* [Todo list](https://github.com/freescout-help-desk/freescout/labels/help%20wanted)
* [Translate](https://github.com/freescout-help-desk/freescout/wiki/Translate)

## Screenshots

Dashboard:

![Dashboard](https://freescout-helpdesk.github.io/img/screenshots/dashboard.png)

Conversation:

![Conversation](https://freescout-helpdesk.github.io/img/screenshots/conversation.png)


Mailbox connection settings page:

![Mailbox connection settings page](https://freescout-helpdesk.github.io/img/screenshots/mailbox-connection.png)

Notifications:

![Notifications](https://freescout-helpdesk.github.io/img/screenshots/notifications.png)

Push notification:

![Push notification](https://freescout-helpdesk.github.io/img/screenshots/push.png)

Web installer:

![Web installer](https://freescout-helpdesk.github.io/img/screenshots/installer.png)

Login page:

![Login page](https://freescout-helpdesk.github.io/img/screenshots/freescout-login.png)