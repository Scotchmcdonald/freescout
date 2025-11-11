<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $email
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer $customer
 */
class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'email',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'type' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns the email.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if this is a primary email.
     */
    public function isPrimary(): bool
    {
        return $this->type === 1;
    }

    /**
     * Check if this is a secondary email.
     */
    public function isSecondary(): bool
    {
        return $this->type === 2;
    }

    /**
     * Sanitize email address.
     */
    public static function sanitizeEmail(?string $email): string|false
    {
        // FILTER_VALIDATE_EMAIL does not work with long emails for example
        // Email validation is not recommended:
        // http://stackoverflow.com/questions/201323/using-a-regular-expression-to-validate-an-email-address/201378#201378
        // So we just check for @
        if (! preg_match('/^.+@.+$/', $email ?? '')) {
            return false;
        }
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = $email !== false ? $email : '';
        $email = mb_strtolower($email, 'UTF-8');
        // Remove trailing dots.
        $email = preg_replace("/\.+$/", '', $email) ?? '';
        // Remove dot before @
        $email = preg_replace("/\.+@/", '@', $email) ?? '';

        return $email ?: false;
    }
}
