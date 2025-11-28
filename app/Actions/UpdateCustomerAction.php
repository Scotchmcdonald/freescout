<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Action class for updating customer information.
 *
 * Encapsulates the business logic for updating a customer with validation.
 */
class UpdateCustomerAction
{
    /**
     * Execute the action to update a customer.
     *
     * @param  Customer  $customer  The customer to update
     * @param  array<string, mixed>  $data  Validated data from the request
     * @return Customer The updated customer
     */
    public function execute(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->fill([
                'first_name' => $data['first_name'] ?? $customer->first_name,
                'last_name' => $data['last_name'] ?? $customer->last_name,
                'company' => $data['company'] ?? $customer->company,
                'job_title' => $data['job_title'] ?? $customer->job_title,
                'timezone' => $data['timezone'] ?? $customer->timezone,
                'address' => $data['address'] ?? $customer->address,
                'city' => $data['city'] ?? $customer->city,
                'state' => $data['state'] ?? $customer->state,
                'zip' => $data['zip'] ?? $customer->zip,
                'country' => $data['country'] ?? $customer->country,
                'notes' => $data['notes'] ?? $customer->notes,
            ]);

            // Handle phones array
            if (isset($data['phone'])) {
                $this->updatePhones($customer, $data['phone']);
            }

            // Handle emails array if provided
            if (isset($data['emails'])) {
                $this->updateEmails($customer, $data['emails']);
            }

            // Handle social profiles if provided
            if (isset($data['social_profiles'])) {
                $customer->social_profiles = $data['social_profiles'];
            }

            // Handle websites if provided
            if (isset($data['websites'])) {
                $customer->websites = $data['websites'];
            }

            $customer->save();

            // Allow modules to modify customer after update
            $customer = \Eventy::filter('customer.update', $customer);

            return $customer;
        });
    }

    /**
     * Update customer phone numbers.
     */
    private function updatePhones(Customer $customer, string $phone): void
    {
        if (empty($phone)) {
            return;
        }

        $phones = $customer->phones ?? [];

        // Check if phone already exists
        $phoneExists = false;
        foreach ($phones as $existingPhone) {
            if (($existingPhone['number'] ?? '') === $phone) {
                $phoneExists = true;
                break;
            }
        }

        // Add phone if not exists
        if (! $phoneExists) {
            $phones[] = ['number' => $phone, 'type' => 'work'];
            $customer->phones = $phones;
        }
    }

    /**
     * Update customer email addresses.
     *
     * @param  array<array{email: string, type: string}>  $emails
     */
    private function updateEmails(Customer $customer, array $emails): void
    {
        $existingEmails = $customer->emails?->pluck('email')->toArray() ?? [];

        foreach ($emails as $emailData) {
            $email = $emailData['email'] ?? '';
            if (! empty($email) && ! in_array($email, $existingEmails)) {
                // Create new email relationship
                $customer->emails()->create([
                    'email' => $email,
                    'type' => $emailData['type'] ?? 1,
                ]);
            }
        }
    }
}
