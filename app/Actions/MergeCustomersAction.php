<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Action class for merging two customers.
 *
 * Moves all data from source customer to target customer and deletes source.
 */
class MergeCustomersAction
{
    /**
     * Execute the customer merge operation.
     *
     * @param  Customer  $source  The customer to merge from (will be deleted)
     * @param  Customer  $target  The customer to merge into
     * @return bool True if merge succeeded
     *
     * @throws \Exception If merge fails
     */
    public function execute(Customer $source, Customer $target): bool
    {
        return DB::transaction(function () use ($source, $target) {
            $this->moveConversations($source, $target);
            $this->mergeEmails($source, $target);
            $this->mergePhones($source, $target);
            $this->mergeNotes($source, $target);

            // Allow modules to handle additional merge logic
            \Eventy::action('customer.merge', $source, $target);

            // Delete the source customer
            $source->delete();

            return true;
        });
    }

    /**
     * Move all conversations from source to target customer.
     */
    private function moveConversations(Customer $source, Customer $target): void
    {
        Conversation::where('customer_id', $source->id)
            ->update(['customer_id' => $target->id]);
    }

    /**
     * Merge emails avoiding duplicates.
     */
    private function mergeEmails(Customer $source, Customer $target): void
    {
        $targetEmailAddresses = $target->emails->pluck('email')->toArray();

        foreach ($source->emails as $email) {
            if (! in_array($email->email, $targetEmailAddresses)) {
                $email->update(['customer_id' => $target->id]);
            }
        }
    }

    /**
     * Merge phone numbers avoiding duplicates.
     */
    private function mergePhones(Customer $source, Customer $target): void
    {
        $sourcePhones = $source->phones ?? [];
        $targetPhones = $target->phones ?? [];

        if (empty($sourcePhones)) {
            return;
        }

        $targetPhoneNumbers = collect($targetPhones)->pluck('number')->toArray();

        foreach ($sourcePhones as $phone) {
            if (! in_array($phone['number'] ?? '', $targetPhoneNumbers)) {
                $targetPhones[] = $phone;
            }
        }

        $target->phones = $targetPhones;
        $target->save();
    }

    /**
     * Merge notes from source into target.
     */
    private function mergeNotes(Customer $source, Customer $target): void
    {
        if (! $source->notes) {
            return;
        }

        if ($target->notes) {
            $target->notes = $target->notes."\n\n---\nMerged from customer #{$source->id}:\n".$source->notes;
        } else {
            $target->notes = $source->notes;
        }

        $target->save();
    }
}
