<?php

namespace App\Service;

class CacheService
{
    private string $redisHost;
    private int $redisPort;
    private string $redisPassword;

    private $connection = null;

    public function __construct(string $redisHost, int $redisPort, string $redisPassword)
    {
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
        $this->redisPassword = $redisPassword;
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = new \Redis();
            $this->connection->connect($this->redisHost, $this->redisPort);
            if ($this->redisPassword) {
                $this->connection->auth($this->redisPassword);
            }
        }
        return $this->connection;
    }

    public function set(string $key, $value, int $ttl = 3600): void
    {
        $conn = $this->getConnection();
        // Sanitize key
        $safeKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $key);
        // Use JSON instead of serialize
        $conn->setex($safeKey, $ttl, json_encode($value));
    }

    public function get(string $key)
    {
        $conn = $this->getConnection();
        $safeKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $key);
        $data = $conn->get($safeKey);

        if ($data === false) {
            return null;
        }

        // Use JSON instead of unserialize
        return json_decode($data, true);
    }

    public function delete(string $key): void
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_.]/', '', $key);
        $this->getConnection()->del($safeKey);
    }

    // REMOVED clearAll() - Too dangerous to expose as a public service method without strict protection.

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
