ForceHttpsModule
================

[![Latest Version](https://img.shields.io/github/release/samsonasik/ForceHttpsModule.svg?style=flat-square)](https://github.com/samsonasik/ForceHttpsModule/releases)
[![Build Status](https://travis-ci.org/samsonasik/ForceHttpsModule.svg?branch=master)](https://travis-ci.org/samsonasik/ForceHttpsModule)
[![Coverage Status](https://coveralls.io/repos/github/samsonasik/ForceHttpsModule/badge.svg?branch=master)](https://coveralls.io/github/samsonasik/ForceHttpsModule?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)
[![Downloads](https://img.shields.io/packagist/dt/samsonasik/force-https-module.svg?style=flat-square)](https://packagist.org/packages/samsonasik/force-https-module)

Introduction
------------

ForceHttpsModule is a configurable module for force https in your ZF2/ZF3 Mvc Application.

Features
--------

- [x] Enable/disable force https.
- [x] Force Https to All routes.
- [x] Force Https to specific routes only.
- [x] Keep headers, request method, and request body.
- [x] Enable/disable HTTP Strict Transport Security Header and set its value.

Installation
------------

**1. Require this module uses [composer](https://getcomposer.org/).**

```sh
composer require samsonasik/force-https-module
```

**2. Copy `force-https-module.local.php.dist` config to your local's autoload and configure it**

| source                                                                       | destination                                 |
|------------------------------------------------------------------------------|---------------------------------------------|
|  vendor/samsonasik/force-https-module/config/force-https-module.local.php.dist | config/autoload/force-https-module.local.php |

Or run copy command:

```sh
cp vendor/samsonasik/force-https-module/config/force-https-module.local.php.dist config/autoload/force-https-module.local.php
```

When done, you can modify `config/autoload/force-https-module.local.php` config in your's local config:

```php
<?php
// config/autoload/force-https-module.local.php
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
            'enable' => true, // set to false to disable it
            'value'  => 'max-age=31536000',
        ],
    ],
];
```

**3. Lastly, enable it**
```php
// config/modules.config.php or config/application.config.php
return [
    'Application'
    'ForceHttpsModule', // register here
],
```


Contributing
------------
Contributions are very welcome. Please read [CONTRIBUTING.md](https://github.com/samsonasik/ForceHttpsModule/blob/master/CONTRIBUTING.md)
