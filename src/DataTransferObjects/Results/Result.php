<?php

namespace Iabduul7\ThemeParkAdapters\DataTransferObjects\Results;

use Illuminate\Support\Arr;

/**
 * Base wrapper over a raw provider API response payload.
 *
 * Ported from the production CodeCreatives\LaravelRedeam\Result\Result so the
 * package's read methods are drop-in compatible with the backend's clients
 * (same accessor names, same dot-path semantics via Arr::get).
 */
abstract class Result
{
    protected array $data;

    /**
     * Optional back-reference to the adapter that produced this result. Lets
     * relation-style accessors (e.g. Product::getRates()) lazily call back into
     * the provider without coupling to a global facade.
     */
    protected ?object $adapter = null;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function withAdapter(?object $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Exclude the adapter back-reference from serialization. Result DTOs get
     * queued directly by consuming apps (e.g. a sync job type-hinting Product),
     * and the adapter's config array carries provider credentials — letting it
     * ride along would leak those credentials into the queue payload. Adapter-
     * backed accessors (e.g. Product::getRates()) degrade gracefully once the
     * DTO comes back from unserialize() with no adapter attached.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return ['data' => $this->data];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function __unserialize(array $data): void
    {
        $this->data = $data['data'] ?? [];
        $this->adapter = null;
    }
}
