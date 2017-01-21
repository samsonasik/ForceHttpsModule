<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Module;
use Kahlan\Plugin\Double;
use Zend\Mvc\MvcEvent;
use Zend\Uri\UriFactory;

describe('Module', function () {

    beforeAll(function () {
        $this->module = new Module;
    });

    describe('->getConfig()', function () {

        it('returns config', function () {

            $expected = include 'config/module.config.php';
            expect($this->module->getConfig())->toBe($expected);

        });

    });

    describe('->onBootstrap()', function () {

        it('register chrome-extension', function () {

            $mvcEvent = Double::instance(['extends' => MvcEvent::class]);
            $this->module->onBootstrap($mvcEvent);

            expect(UriFactory::getRegisteredSchemeClass('chrome-extension'))->toBe('Zend\Uri\Http');

        });

    });

});
