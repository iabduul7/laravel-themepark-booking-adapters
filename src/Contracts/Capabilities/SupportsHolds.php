<?php

namespace Iabduul7\ThemeParkAdapters\Contracts\Capabilities;

/**
 * Hold-then-book lifecycle shared by the Redeam providers (Disney + SeaWorld/
 * United Parks). Holds and bookings are top-level Redeam resources, so these
 * signatures are identical across both park families.
 */
interface SupportsHolds
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createNewHold(array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function getHold(string $holdId): array;

    /**
     * @return array<string, mixed>
     */
    public function deleteHold(string $holdId): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createNewBooking(array $data): array;

    /**
     * @return array<string, mixed>
     */
    public function getBooking(string $bookingId): array;

    /**
     * Cancel a booking. Upstream cancels via PUT bookings/cancel/{id}, which
     * returns the raw HTTP response rather than a decoded array — hence mixed.
     */
    public function deleteBooking(string $bookingId): mixed;
}
