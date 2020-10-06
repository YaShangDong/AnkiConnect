<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

class Model
{
    protected string $name;
    protected array $fields;
    protected array $templates;
    protected ?string $css = null;

    /**
     * @param string $name
     * @param string[] $fields
     * @param Template[] $templates
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $name, array $fields, array $templates)
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->templates = $templates;

        foreach ($templates as $template) {
            if (!($template instanceof Template)) {
                throw new \InvalidArgumentException("templates must only contain Template instance");
            }
        }
    }

    public function setCss(string $css): self
    {
        $this->css = $css;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return Template[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getCss(): ?string
    {
        return $this->css;
    }
}
