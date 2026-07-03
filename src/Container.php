<?php
declare(strict_types=1);

namespace R2Uploader;

/**
 * Simple service container with lazy instantiation.
 *
 * Services are registered as factory closures and instantiated on first access.
 * Once resolved, the same instance is returned on subsequent calls (singleton).
 */
class Container
{
    /** @var array<string, callable> Factory closures */
    private array $factories = [];

    /** @var array<string, mixed> Resolved singleton instances */
    private array $instances = [];

    /**
     * Register a service factory.
     *
     * @param string   $id      Service identifier
     * @param callable $factory Factory closure: fn(Container): mixed
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        // Clear cached instance if re-registering
        unset($this->instances[$id]);
    }

    /**
     * Resolve a service by ID.
     *
     * The factory is called on first access; the result is cached as a singleton.
     *
     * @throws \RuntimeException If the service ID is not registered
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new \RuntimeException("Service '{$id}' is not registered in the container.");
        }

        $this->instances[$id] = ($this->factories[$id])($this);
        return $this->instances[$id];
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }
}
