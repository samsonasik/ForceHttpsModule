<?php

namespace ForceHttpsModule\Spec;

use ForceHttpsModule\Module;

describe('Module', function (): void {

    beforeAll(function (): void {
        $this->module = new Module;
    });

    describe('->getConfig()', function (): void {

        it('returns config', function (): void {

            $expected = include 'config/module.config.php';
            expect($this->module->getConfig())->toBe($expected);

        });

    });

});
