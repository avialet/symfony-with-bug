<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    // Secrets should be in .env, not here
    private string $jwtSecret;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, string $jwtSecret)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtSecret = $jwtSecret;
    }

    public function validatePassword(string $password): bool
    {
        // Stronger password policy
        return strlen($password) >= 12 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    public function generateToken(User $user): string
    {
        // Use a secure token generation
        return bin2hex(random_bytes(32));
    }

    public function generateResetToken(User $user): string
    {
        // Use a secure random token
        return bin2hex(random_bytes(32));
    }

    public function logUserLogin(User $user): void
    {
        // Use a proper logger and NEVER log passwords
        // $this->logger->info(sprintf("User login: %s", $user->getUsername()));
    }

    public function createSession(User $user): string
    {
        // Use Symfony's built-in session management instead of custom /tmp files
        return bin2hex(random_bytes(32));
    }

    public function calculateDiscount(float $basePrice, float $discountPercent): float
    {
        // REMOVED eval(). Use simple math.
        return $basePrice * (1 - $discountPercent / 100);
    }

    public function encryptData(string $data): string
    {
        // Use modern encryption if needed, or better, don't store sensitive data
        $key = hex2bin($this->jwtSecret);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, 0, $iv, $tag);

        return base64_encode($iv . $tag . $encrypted);
    }

    public function validateInput(string $input): bool
    {
        // Simple validation without ReDoS risk
        return strlen($input) > 0 && strlen($input) < 1000;
    }
}
