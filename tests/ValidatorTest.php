<?php
declare(strict_types=1);

namespace PPMWP\Tests;

use PHPUnit\Framework\TestCase;
use PPMWP\Utilities\ValidatorFactory;

class ValidatorTest extends TestCase {

    public function testIntegerValid() {

        $this->assertTrue(
            true === ValidatorFactory::validate(
                '4',
                [
                    'typeRule' => 'number',
                ]
            )
        );

        $this->assertTrue(
            true === ValidatorFactory::validate(
                4,
                [
                    'typeRule' => 'number',
                ]
            )
        );

        $this->assertTrue(
            true === ValidatorFactory::validate(
                '4',
                [
                    'typeRule' => 'number',
                    'max'      => 30,
                ]
            )
        );

        $this->assertTrue(
            true === ValidatorFactory::validate(
                '4',
                [
                    'typeRule' => 'number',
                    'max'      => 10,
                    'min'      => 0,
                ]
            )
        );
    }

    public function testOutOfRange() {

        $this->assertTrue(
            false === ValidatorFactory::validate(
                '-4',
                [
                    'typeRule' => 'number',
                    'max'      => 10,
                    'min'      => 0,
                ]
            )
        );
    }

    public function testString() {

        $this->assertTrue(
            false === ValidatorFactory::validate(
                'testing',
                [
                    'typeRule' => 'number',
                ]
            )
        );
    }

    public function testInSet() {

        $this->assertTrue(
            true === ValidatorFactory::validate(
                'mine',
                [
                    'typeRule' => 'inset',
                    'set'      => [
                        'mine',
                        'yours',
                    ],
                ]
            )
        );
    }

    public function testNotInSet() {

        $this->assertTrue(
            false === ValidatorFactory::validate(
                'theirs',
                [
                    'typeRule' => 'inset',
                    'set'      => [
                        'mine',
                        'yours',
                    ],
                ]
            )
        );
    }

    public function testNotSetProvided() {

        $this->assertTrue(
            false === ValidatorFactory::validate(
                'theirs',
                [
                    'typeRule' => 'inset',
                ]
            )
        );
    }
}
