<?php

namespace ForceHttpsModuleSpec;

use ForceHttpsModule\Module;

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

});
