<?php

declare(strict_types=1);

namespace App\Enums;

enum ThreadType: int
{
    case MESSAGE = 1;
    case NOTE = 2;
    case DRAFT = 3;

    public function label(): string
    {
        return match($this) {
            self::MESSAGE => 'Reply',
            self::NOTE => 'Internal Note',
            self::DRAFT => 'Draft',
        };
    }

    public function isInternal(): bool
    {
        return $this === self::NOTE || $this === self::DRAFT;
    }

    public function sendsEmail(): bool
    {
        return $this === self::MESSAGE;
    }
}
