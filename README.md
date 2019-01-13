ForceHttpsModule
================

[![Latest Version](https://img.shields.io/github/release/samsonasik/ForceHttpsModule.svg?style=flat-square)](https://github.com/samsonasik/ForceHttpsModule/releases)
[![Build Status](https://travis-ci.org/samsonasik/ForceHttpsModule.svg?branch=master)](https://travis-ci.org/samsonasik/ForceHttpsModule)
[![Coverage Status](https://coveralls.io/repos/github/samsonasik/ForceHttpsModule/badge.svg?branch=master)](https://coveralls.io/github/samsonasik/ForceHttpsModule?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Downloads](https://img.shields.io/packagist/dt/samsonasik/force-https-module.svg?style=flat-square)](https://packagist.org/packages/samsonasik/force-https-module)

Introduction
------------

ForceHttpsModule is a configurable module for force https in your [ZF Mvc](https://zendframework.github.io/tutorials/getting-started/overview/) and [ZF Expressive](https://zendframework.github.io/zend-expressive/) Application.

> This is README for version ^2.0 which only support ZF3 and ZF Expressive version 3 with php ^7.1.

> For version 1, you can read at [version 1 readme](https://github.com/samsonasik/ForceHttpsModule/tree/1.x.x) which still support ZF2 and ZF Expressive version 1 and 2 with php ^5.6|^7.0 support.

Features
--------

- [x] Enable/disable force https.
- [x] Force Https to All routes.
- [x] Force Https to specific routes only.
- [x] Keep headers, request method, and request body.
- [x] Enable/disable HTTP Strict Transport Security Header and set its value.
- [x] Allow add `www.` prefix during redirection from http or already https.
- [x] Allow remove `www.` prefix during redirection from http or already https.
- [x] Force Https for 404 pages

Installation
------------

**1. Require this module uses [composer](https://getcomposer.org/).**

```sh
composer require samsonasik/force-https-module
```

**2. Copy config**

***a. For [ZF3 Mvc](https://zendframework.github.io/tutorials/getting-started/overview/) application, copy `force-https-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/force-https-module/config/force-https-module.local.php.dist | config/autoload/force-https-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/force-https-module/config/force-https-module.local.php.dist config/autoload/force-https-module.local.php
```

***b. For [ZF Expressive](https://zendframework.github.io/zend-expressive/) application, copy `expressive-force-https-module.local.php.dist` config to your local's autoload and configure it***

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/force-https-module/config/expressive-force-https-module.local.php.dist | config/autoload/expressive-force-https-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/force-https-module/config/expressive-force-https-module.local.php.dist config/autoload/expressive-force-https-module.local.php
```

When done, you can modify your local config:

```php
<?php
// config/autoload/force-https-module.local.php or config/autoload/expressive-force-https-module.local.php
return [
    'force-https-module' => [
        'enable'                => true,
        'force_all_routes'      => true,
        'force_specific_routes' => [
            // only works if previous's config 'force_all_routes' => false
            'checkout',
            'payment'
        ],
        // set HTTP Strict Transport Security Header
        'strict_transport_security' => [
            // set to false to disable it
            'enable' => true,
            'value'  => 'max-age=31536000',
        ],
        // set to true to add "www." prefix during redirection from http or already https
        'add_www_prefix'        => false,
        // remove existing "www." prefix during redirection from http or already https
        // only works if previous's config 'add_www_prefix' => false
        'remove_www_prefix'     => false,
        // Force Https for 404 pages
        'allow_404'             => true,
    ],
    // ...
];
```

**3. Lastly, enable it**

***a. For ZF Mvc application***

```php
// config/modules.config.php or config/application.config.php
return [
    'Application'
    'ForceHttpsModule', // register here
],
```

***b. For ZF Expressive application***

For [zend-expressive-skeleton](https://github.com/zendframework/zend-expressive-skeleton) ^3.0, you need to open `config/pipeline.php` and add:

```php
$app->pipe(ForceHttpsModule\Middleware\ForceHttps::class);
```

at the very first pipeline records.

Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ForceHttpsModule/blob/master/CONTRIBUTING.md)
