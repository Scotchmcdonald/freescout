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
        /** @var Customer $updatedCustomer */
        $updatedCustomer = DB::transaction(function () use ($customer, $data): Customer {
            $customer->fill([
                'first_name' => $data['first_name'] ?? $customer->first_name,
                'last_name' => $data['last_name'] ?? $customer->last_name,
                'company' => $data['company'] ?? $customer->company,
                'job_title' => $data['job_title'] ?? $customer->job_title,
                'address' => $data['address'] ?? $customer->address,
                'city' => $data['city'] ?? $customer->city,
                'state' => $data['state'] ?? $customer->state,
                'zip' => $data['zip'] ?? $customer->zip,
                'country' => $data['country'] ?? $customer->country,
                'notes' => $data['notes'] ?? $customer->notes,
            ]);

            // Handle phones array
            if (isset($data['phone']) && is_string($data['phone'])) {
                $this->updatePhones($customer, $data['phone']);
            }

            // Handle emails array if provided
            if (isset($data['emails']) && is_array($data['emails'])) {
                /** @var array<array{email: string, type: string}> $emails */
                $emails = $data['emails'];
                $this->updateEmails($customer, $emails);
            }

            // Handle social profiles if provided
            if (isset($data['social_profiles']) && is_array($data['social_profiles'])) {
                /** @var array<string, mixed> $socialProfiles */
                $socialProfiles = $data['social_profiles'];
                $customer->social_profiles = $socialProfiles;
            }

            // Handle websites if provided
            if (isset($data['websites']) && is_array($data['websites'])) {
                /** @var array<string, mixed> $websites */
                $websites = $data['websites'];
                $customer->websites = $websites;
            }

            $customer->save();

            // Allow modules to modify customer after update
            \Eventy::filter('customer.update', $customer);

            return $customer;
        });

        return $updatedCustomer;
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
            if (is_array($existingPhone) && isset($existingPhone['number']) && $existingPhone['number'] === $phone) {
                $phoneExists = true;
                break;
            }
        }

        // Add phone if not exists
        if (! $phoneExists) {
            $phones[] = ['number' => $phone, 'type' => 'work'];
            /** @var array<int, array<string, mixed>> $phones */
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
        $emailsRelation = $customer->emails;
        $existingEmails = $emailsRelation->pluck('email')->toArray();

        foreach ($emails as $emailData) {
            $email = $emailData['email'];
            if (! empty($email) && ! in_array($email, $existingEmails)) {
                // Create new email relationship
                $customer->emails()->create([
                    'email' => $email,
                    'type' => $emailData['type'],
                ]);
            }
        }
    }
}
