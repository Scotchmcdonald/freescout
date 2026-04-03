<?php

declare(strict_types=1);

namespace App\Enums;

enum WaitingReason: string
{
    case AWAITING_CLIENT_APPROVAL = 'Awaiting Client Approval';
    case AWAITING_VENDOR = 'Awaiting Vendor';
    case INTERNAL_ESCALATION = 'Internal Escalation';
    case RESEARCHING = 'Researching';
    case OTHER = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
