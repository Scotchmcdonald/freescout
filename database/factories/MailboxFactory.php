<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mailbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mailbox>
 */
class MailboxFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => substr(fake()->company(), 0, 20).' Support',
            'email' => fake()->unique()->companyEmail(),
            'aliases' => null,
            'from_name' => 1, // 1=mailbox, 2=user, 3=custom
            'from_name_custom' => null,
            'ticket_status' => 1, // Active
            'ticket_assignee' => 1, // Unassigned
            // 'template' removed - will use default value from migration
            'signature' => fake()->paragraph(),
            'out_method' => 3, // 1=PHP mail, 2=Sendmail, 3=SMTP
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => fake()->email(),
            'out_password' => encrypt('password'),
            'out_encryption' => 2, // 0=none, 1=SSL, 2=TLS
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => fake()->email(),
            'in_password' => encrypt('password'),
            'in_encryption' => 1, // 0=none, 1=SSL, 2=TLS
            'auto_reply_enabled' => false,
            'auto_reply_subject' => null,
            'auto_reply_message' => null,
        ];
    }

    public function withAutoReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'We received your message',
            'auto_reply_message' => fake()->paragraph(),
        ]);
    }
}
