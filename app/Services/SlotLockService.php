<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SlotLockService
{
    /**
     * Default lock duration in minutes
     */
    private const DEFAULT_LOCK_MINUTES = 10;

    /**
     * Lock a time slot temporarily
     * 
     * @param string $tenantId
     * @param string $locationId
     * @param string $date Date in Y-m-d format
     * @param string $startTime Start time in H:i format
     * @param string $sessionId Unique session identifier
     * @param int $minutes Lock duration in minutes
     * @return bool True if lock acquired, false if slot already locked
     */
    public function lockSlot(
        string $tenantId,
        string $locationId,
        string $date,
        string $startTime,
        string $sessionId,
        int $minutes = self::DEFAULT_LOCK_MINUTES
    ): bool {
        $key = $this->getLockKey($tenantId, $locationId, $date, $startTime);

        // Check if slot is already locked by someone else
        $existingLock = Cache::get($key);
        if ($existingLock && $existingLock !== $sessionId) {
            return false;
        }

        // Set or extend lock
        Cache::put($key, $sessionId, now()->addMinutes($minutes));

        return true;
    }

    /**
     * Release a slot lock
     * 
     * @param string $tenantId
     * @param string $locationId
     * @param string $date
     * @param string $startTime
     * @param string $sessionId
     * @return bool True if lock was released
     */
    public function releaseLock(
        string $tenantId,
        string $locationId,
        string $date,
        string $startTime,
        string $sessionId
    ): bool {
        $key = $this->getLockKey($tenantId, $locationId, $date, $startTime);

        $existingLock = Cache::get($key);
        if ($existingLock === $sessionId) {
            Cache::forget($key);
            return true;
        }

        return false;
    }

    /**
     * Check if a slot is locked
     * 
     * @param string $tenantId
     * @param string $locationId
     * @param string $date
     * @param string $startTime
     * @param string|null $excludeSessionId Optional session ID to exclude from lock check
     * @return bool True if slot is locked (by someone else if excludeSessionId provided)
     */
    public function isLocked(
        string $tenantId,
        string $locationId,
        string $date,
        string $startTime,
        ?string $excludeSessionId = null
    ): bool {
        $key = $this->getLockKey($tenantId, $locationId, $date, $startTime);
        $lock = Cache::get($key);

        if (!$lock) {
            return false;
        }

        // If excludeSessionId provided, don't consider it locked if it's our own lock
        if ($excludeSessionId && $lock === $excludeSessionId) {
            return false;
        }

        return true;
    }

    /**
     * Get all locked slots for a specific date
     * 
     * @param string $tenantId
     * @param string $locationId
     * @param string $date
     * @param array $timeSlots Array of time slots to check ['H:i', 'H:i', ...]
     * @param string|null $excludeSessionId
     * @return array Array of locked time slots
     */
    public function getLockedSlots(
        string $tenantId,
        string $locationId,
        string $date,
        array $timeSlots,
        ?string $excludeSessionId = null
    ): array {
        $lockedSlots = [];

        foreach ($timeSlots as $time) {
            if ($this->isLocked($tenantId, $locationId, $date, $time, $excludeSessionId)) {
                $lockedSlots[] = $time;
            }
        }

        return $lockedSlots;
    }

    /**
     * Release all locks for a session
     * 
     * @param string $sessionId
     * @param string $tenantId
     * @param string $locationId
     * @param string $date
     * @param array $timeSlots All possible time slots to check
     */
    public function releaseAllSessionLocks(
        string $sessionId,
        string $tenantId,
        string $locationId,
        string $date,
        array $timeSlots
    ): void {
        foreach ($timeSlots as $time) {
            $this->releaseLock($tenantId, $locationId, $date, $time, $sessionId);
        }
    }

    /**
     * Generate cache key for slot lock
     */
    private function getLockKey(
        string $tenantId,
        string $locationId,
        string $date,
        string $startTime
    ): string {
        return "slot_lock:{$tenantId}:{$locationId}:{$date}:{$startTime}";
    }
}
