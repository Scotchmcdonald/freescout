<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property array<string, mixed>|null $phones
 * @property array<string, mixed>|null $websites
 * @property array<string, mixed>|null $social_profiles
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $notes
 * @property bool $is_non_profit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Email> $emails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversations
 *
 * @method static \Illuminate\Database\Eloquent\Builder<Customer>|Customer findOrFail(int $id, array<int, string> $columns = ['*'])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder<Customer>
 */
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'company',
        'company_id',
        'is_non_profit',
        'job_title',
        'photo_url',
        'photo_type',
        'gender',
        'age',
        'background',
        'channel',
        'channel_id',
        'phones',
        'websites',
        'social_profiles',
        'chats',
        'meta',
        'websites',
        'social_profiles',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'notes',
        'default_hourly_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'photo_type' => 'integer',
            'channel' => 'integer',
            'phones' => 'json',
            'websites' => 'json',
            'social_profiles' => 'json',
            'is_non_profit' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\Crm\Models\Company, $this>
     */
    public function companyRel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Crm\Models\Company::class, 'company_id');
    }

    /**
     * Get the emails for this customer.
     *
     * @return HasMany<Email, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Get the conversations for this customer.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the threads for this customer.
     *
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * Get the channels associated with this customer.
     *
     * @return BelongsToMany<Channel, $this>
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_customer')
            ->withTimestamps();
    }

    /**
     * Get the customer channel records.
     *
     * @return HasMany<CustomerChannel, $this>
     */
    public function customerChannels(): HasMany
    {
        return $this->hasMany(CustomerChannel::class);
    }

    /**
     * Get the customer's full name.
     * 
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} {$this->last_name}")
        );
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
     *
     * @param  bool  $ucfirst  Whether to uppercase the first letter
     * @return string
     */
    public function getFirstName(bool $ucfirst = false): string
    {
        $firstName = $this->first_name ?? '';
        
        return $ucfirst && $firstName ? ucfirst($firstName) : $firstName;
    }

    /**
     * Get the customer's primary email.
     * 
     * @return Attribute<string|null, never>
     */
    protected function primaryEmail(): Attribute
    {
        return Attribute::make(
            get: function(): ?string {
                /** @var \App\Models\Email|null $email */
                $email = $this->emails()->where('type', 1)->first();
                return $email?->email;
            }
        );
    }

    /**
     * Get the customer's main email address.
     */
    public function getMainEmail(): ?string
    {
        /** @var \App\Models\Email|null $email */
        $email = $this->emails()->where('type', 1)->first();

        if ($email) {
            return $email->email;
        }

        /** @var \App\Models\Email|null $firstEmail */
        $firstEmail = $this->emails()->first();

        return $firstEmail ? $firstEmail->email : null;
    }

    /**
     * Create or get a customer by email address.
     * This matches the original FreeScout implementation.
     *
     * @param array<string, mixed> $data
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
            /** @var \App\Models\Customer|null $customer */
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
     *
     * @param array<string, mixed> $data
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

    /**
     * Sync customer emails from array.
     *
     * @param array<int, string> $emails
     */
    public function syncEmails(array $emails): void
    {
        // Remove empty emails
        $emails = array_filter($emails, fn($email) => !empty($email));
        
        // Get existing emails
        $existing = $this->emails->pluck('email')->toArray();
        
        // Add new emails
        foreach ($emails as $index => $email) {
            $sanitized = Email::sanitizeEmail($email);
            if ($sanitized && !in_array($sanitized, $existing)) {
                Email::create([
                    'customer_id' => $this->id,
                    'email' => $sanitized,
                    'type' => $index === 0 ? Email::TYPE_PRIMARY : Email::TYPE_SECONDARY,
                ]);
            }
        }
    }

    /**
     * Find customer by email address.
     */
    public static function getByEmail(string $email): ?Customer
    {
        $sanitized = Email::sanitizeEmail($email);
        if (!$sanitized) {
            return null;
        }

        $emailModel = Email::where('email', $sanitized)->first();
        if ($emailModel) {
            return $emailModel->customer;
        }

        return null;
    }

    /**
     * Create customer without email.
     *
     * @param array<string, mixed> $data
     */
    public static function createWithoutEmail(array $data): ?Customer
    {
        $customer = new Customer();
        $customer->fill($data);
        $customer->save();

        return $customer;
    }
}

