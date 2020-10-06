<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use YaSD\AnkiConnect\Model;

class ModelTest extends TestCase
{
    public function testConstructExceptionWhenInvalidTemplates()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Model('modelName', ['a', 'b'], ['error']);
    }
}
