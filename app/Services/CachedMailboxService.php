<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mailbox;
use Illuminate\Support\Facades\Cache;

/**
 * Cached Mailbox Service
 * 
 * Provides cached access to mailbox data to reduce database queries.
 * Uses cache tagging for efficient invalidation.
 */
class CachedMailboxService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TAG = 'mailboxes';

    /**
     * Get a mailbox by ID with caching.
     */
    public function get(int $id): ?Mailbox
    {
        return Cache::tags([self::CACHE_TAG, "mailbox:{$id}"])
            ->remember(
                key: "mailbox:{$id}",
                ttl: self::CACHE_TTL,
                callback: fn() => Mailbox::with(['folders', 'users'])->find($id)
            );
    }

    /**
     * Get all mailboxes with caching.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Mailbox>
     */
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::tags([self::CACHE_TAG])
            ->remember(
                key: 'mailboxes:all',
                ttl: self::CACHE_TTL,
                callback: fn() => Mailbox::with(['folders'])->get()
            );
    }

    /**
     * Get mailboxes for a specific user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Mailbox>
     */
    public function forUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::tags([self::CACHE_TAG, "user:{$userId}"])
            ->remember(
                key: "mailboxes:user:{$userId}",
                ttl: self::CACHE_TTL,
                callback: fn() => Mailbox::whereHas('users', function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                })->with(['folders'])->get()
            );
    }

    /**
     * Invalidate cache for a specific mailbox.
     */
    public function invalidate(Mailbox $mailbox): void
    {
        Cache::tags(["mailbox:{$mailbox->id}"])->flush();
        Cache::tags([self::CACHE_TAG])->forget('mailboxes:all');
    }

    /**
     * Invalidate all mailbox caches.
     */
    public function invalidateAll(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }

    /**
     * Invalidate caches for a specific user.
     */
    public function invalidateUser(int $userId): void
    {
        Cache::tags(["user:{$userId}"])->flush();
    }

    /**
     * Warm up the cache by preloading mailboxes.
     */
    public function warmUp(): void
    {
        $this->all();
        
        $mailboxes = Mailbox::all();
        foreach ($mailboxes as $mailbox) {
            $this->get($mailbox->id);
        }
    }
}
