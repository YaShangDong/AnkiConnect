<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

class Template
{
    protected string $name;
    protected string $front;
    protected string $back;

    public function __construct(string $name, string $front, string $back)
    {
        $this->name = $name;
        $this->front = $front;
        $this->back = $back;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFront(): string
    {
        return $this->front;
    }

    public function getBack(): string
    {
        return $this->back;
    }
}
