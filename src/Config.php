<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

class Config
{
    protected int $id;
    protected \stdClass $config;

    public function __construct(\stdClass $config)
    {
        if (!isset($config->id)) {
            throw new \InvalidArgumentException("Invalid_Config: " . \json_encode($config));
        }
        $this->id = $config->id;
        $this->config = $config;
    }

    public function getConfig(): \stdClass
    {
        return $this->config;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
