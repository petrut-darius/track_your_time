<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class LiveNotificationMessage
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    public function __construct(
        private string $type,
        private int $targetId,
        private string $content,
        private int $recepientId,
    ) {
    }

    public function getType() {
        return $this->type;
    }

    public function getTargetId() {
        return $this->targetId;
    }

    public function getContent() {
        return $this->content;
    }

    public function getRecepientId() {
        return $this->recepientId;
    }
}
