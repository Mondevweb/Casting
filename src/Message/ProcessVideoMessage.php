<?php

namespace App\Message;

final class ProcessVideoMessage
{
    private int $mediaObjectId;

    public function __construct(int $mediaObjectId)
    {
        $this->mediaObjectId = $mediaObjectId;
    }

    public function getMediaObjectId(): int
    {
        return $this->mediaObjectId;
    }
}
