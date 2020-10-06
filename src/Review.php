<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

class Review
{
    protected int $reviewTime;
    protected int $cardId;
    protected int $usn;
    protected int $buttonPressed;
    protected int $newInterval;
    protected int $previousInterval;
    protected int $newFactor;
    protected int $reviewDuration;
    protected int $reviewType;

    public function __construct(int $reviewTime, int $cardId, int $usn, int $buttonPressed, int $newInterval, int $previousInterval, int $newFactor, int $reviewDuration, int $reviewType)
    {
        $this->reviewTime = $reviewTime;
        $this->cardId = $cardId;
        $this->usn = $usn;
        $this->buttonPressed = $buttonPressed;
        $this->newInterval = $newInterval;
        $this->previousInterval = $previousInterval;
        $this->newFactor = $newFactor;
        $this->reviewDuration = $reviewDuration;
        $this->reviewType = $reviewType;
    }

    public function toArray(): array
    {
        return \array_values((array) $this);
    }

    public function getReviewTime(): int
    {
        return $this->reviewTime;
    }

    public function getCardId(): int
    {
        return $this->cardId;
    }

    public function getButtonPressed(): int
    {
        return $this->buttonPressed;
    }
}
