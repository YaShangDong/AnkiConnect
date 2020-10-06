<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Exception;

use YaSD\AnkiConnect\Exception;

class ActionFailedException extends \RuntimeException implements Exception
{
    /**
     * @throws ActionFailedException
     */
    public static function throwWhenFalse(bool $result, string $msg): void
    {
        if (!$result) {
            throw new static($msg);
        }
    }
}
