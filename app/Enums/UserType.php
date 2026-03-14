<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * User type — controls whether a user is internal staff, an external client,
 * or an automaton (AI agent).
 *
 * Persisted as the `type` column (tinyint) on the users table.
 */
enum UserType: int
{
    /** Regular internal staff (agent, admin, finance, reporter). */
    case Internal = 1;

    /** External client-portal user. */
    case Client = 2;

    /** Automaton — a system-registered AI agent that sends on behalf of a module. */
    case Automaton = 3;

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::Client   => 'Client',
            self::Automaton => 'Automaton',
        };
    }
}
