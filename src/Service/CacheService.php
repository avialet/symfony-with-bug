<?php

namespace App\Service;

class CacheService
{
    // BUG: Hardcoded Redis credentials
    private string $redisHost = '10.0.1.50';
    private int $redisPort = 6379;
    private string $redisPassword = 'redis_secret_2024';

    private $connection = null;

    // BUG: Memory leak - connections never closed
    public function getConnection()
    {
        // BUG: Creating new connection every time instead of reusing
        $this->connection = new \Redis();
        $this->connection->connect($this->redisHost, $this->redisPort);
        $this->connection->auth($this->redisPassword);
        return $this->connection;
    }

    // BUG: Cache poisoning possible
    public function set(string $key, $value, int $ttl = 3600): void
    {
        $conn = $this->getConnection();
        // BUG: No key sanitization - cache injection
        // BUG: Serializing objects with serialize() instead of json_encode
        $conn->setex($key, $ttl, serialize($value));
    }

    // BUG: Insecure deserialization from cache
    public function get(string $key)
    {
        $conn = $this->getConnection();
        $data = $conn->get($key);

        if ($data === false) {
            return null;
        }

        // BUG: unserialize from potentially tampered cache
        return unserialize($data);
    }

    // BUG: No error handling
    public function delete(string $key): void
    {
        $this->getConnection()->del($key);
    }

    // BUG: Dangerous flush operation with no safeguards
    public function clearAll(): void
    {
        // BUG: Flushing entire Redis database without confirmation
        $this->getConnection()->flushDB();
    }

    // BUG: Destructor not closing connection
    public function __destruct()
    {
        // Connection leak - Redis connections not properly closed
    }
}
