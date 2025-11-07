<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $company
 * @property string|null $job_title
 * @property string|null $photo_url
 * @property int|null $photo_type
 * @property int|null $channel
 * @property string|null $channel_id
 * @property array|null $phones
 * @property array|null $websites
 * @property array|null $social_profiles
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Email> $emails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'company',
        'job_title',
        'photo_url',
        'photo_type',
        'channel',
        'channel_id',
        'phones',
        'websites',
        'social_profiles',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'photo_type' => 'integer',
            'channel' => 'integer',
            'phones' => 'json',
            'websites' => 'json',
            'social_profiles' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the emails for this customer.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Get the conversations for this customer.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the threads for this customer.
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * Get the channels associated with this customer.
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'customer_channel')
            ->withTimestamps();
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the customer's full name (method version for JSON).
     */
    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the customer's first name.
     */
    public function getFirstName(): string
    {
        return $this->first_name ?? '';
    }

    /**
     * Get the customer's primary email.
     */
    public function getPrimaryEmailAttribute(): ?string
    {
        // @phpstan-ignore-next-line - HasMany returns Builder for query operations
        $email = $this->emails()->where('type', 1)->first();
        // @phpstan-ignore-next-line - Email model has email property
        return $email?->email;
    }

    /**
     * Get the customer's main email address.
     */
    public function getMainEmail(): ?string
    {
        // @phpstan-ignore-next-line - HasMany returns Builder for query operations
        $email = $this->emails()->where('type', 1)->first();

        if ($email) {
            // @phpstan-ignore-next-line - Email model has email property
            return $email->email;
        }

        /** @var \App\Models\Email|null $firstEmail */
        $firstEmail = $this->emails()->first();
        return $firstEmail ? $firstEmail->email : null;
    }

    /**
     * Create or get a customer by email address.
     * This matches the original FreeScout implementation.
     */
    public static function create(string $email, array $data = []): ?self
    {
        $new = false;

        $email = Email::sanitizeEmail($email);
        if (! $email) {
            return null;
        }

        $email_obj = Email::where('email', $email)->first();
        if ($email_obj) {
            $customer = $email_obj->customer;

            // In case somehow the email has no customer.
            if (! $customer) {
                // Customer will be saved and connected to the email later.
                $customer = new self;
            }
        } else {
            $customer = new self;
            $email_obj = new Email;
            $email_obj->email = $email;
            $email_obj->type = 1; // Primary email

            $new = true;
        }

        // Set empty fields
        if ($customer->setData($data, false) || ! $customer->id) {
            $customer->save();
        }

        if (empty($email_obj->id) || ! $email_obj->customer_id || $email_obj->customer_id != $customer->id) {
            // Email may have been set in setData().
            $save_email = true;
            if (! empty($data['emails']) && is_array($data['emails'])) {
                foreach ($data['emails'] as $data_email) {
                    if (is_string($data_email) && $data_email == $email) {
                        $save_email = false;
                        break;
                    }
                    if (is_array($data_email) && ! empty($data_email['value']) && $data_email['value'] == $email) {
                        $save_email = false;
                        break;
                    }
                }
            }
            if ($save_email) {
                $email_obj->customer()->associate($customer);
                $email_obj->save();
            }
        }

        return $customer;
    }

    /**
     * Set empty fields from data array.
     * This matches the original FreeScout implementation.
     */
    public function setData(array $data, bool $replace_data = true, bool $save = false): bool
    {
        $result = false;

        // Remove photo_url if present
        if (isset($data['photo_url'])) {
            unset($data['photo_url']);
        }

        // Use background as notes if notes is empty
        if (! empty($data['background']) && empty($data['notes'])) {
            $data['notes'] = $data['background'];
        }

        if ($replace_data) {
            // Replace data.
            $data_prepared = $data;
            foreach ($data_prepared as $i => $value) {
                if (is_array($value)) {
                    unset($data_prepared[$i]);
                }
            }
            $this->fill($data_prepared);
            $result = true;
        } else {
            // Update empty fields only.

            // Do not set last name if first name is already set (and vice versa).
            if (! empty($this->first_name) && ! empty($data['last_name'])) {
                unset($data['last_name']);
            }
            if (! empty($this->last_name) && ! empty($data['first_name'])) {
                unset($data['first_name']);
            }

            foreach ($this->fillable as $field) {
                if (empty($this->{$field}) && ! empty($data[$field])) {
                    $this->{$field} = $data[$field];
                    $result = true;
                }
            }
        }

        if ($save && $result) {
            $this->save();
        }

        return $result;
    }
}
