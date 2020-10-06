<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

class Note
{
    protected string $deckName;
    protected string $modelName;
    protected array $fields;

    protected array $options = [];
    protected array $tags = [];
    protected array $audio = [];

    public function __construct(string $deckName, string $modelName, array $fields)
    {
        $this->deckName = $deckName;
        $this->modelName = $modelName;
        $this->fields = $fields;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    // The allowDuplicate member inside options group can be set to true to enable adding duplicate cards. Normally duplicate cards can not be added and trigger exception. The duplicateScope member inside options can be used to specify the scope for which duplicates are checked. A value of "deck" will only check for duplicates in the target deck; any other value will check the entire collection
    public function setOption(string $optionKey, $value): self
    {
        $this->options[$optionKey] = $value;
        return $this;
    }

    // AnkiConnect can download audio files and embed them in newly created notes. The corresponding audio note member is optional and can be omitted. If you choose to include it, it should contain a single object or an array of objects with mandatory url and filename fields. The skipHash field can be optionally provided to skip the inclusion of downloaded files with an MD5 hash that matches the provided value. This is useful for avoiding the saving of error pages and stub files. The fields member is a list of fields that should play audio when the card is displayed in Anki
    public function setAudios(array $audios): self
    {
        $this->audio = $audios;
        return $this;
    }

    public function toArray(): array
    {
        return \array_filter(\get_object_vars($this));
    }
}
