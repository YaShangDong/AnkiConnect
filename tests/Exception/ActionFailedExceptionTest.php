<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use YaSD\AnkiConnect\Exception\ActionFailedException;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ActionFailedExceptionTest extends TestCase
{
    public function testNotThrowWhenTrue()
    {
        ActionFailedException::throwWhenFalse(true, 'something');
        $this->assertTrue(true);
    }

    public function testThrowWhenFalse()
    {
        $this->expectException(ActionFailedException::class);
        ActionFailedException::throwWhenFalse(false, 'something');
    }
}
