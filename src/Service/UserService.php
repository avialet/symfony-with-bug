<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;

    // BUG: Hardcoded secret key
    private const JWT_SECRET = 'super_secret_key_12345';
    private const API_KEY = 'sk-prod-abc123def456ghi789';
    private const DB_PASSWORD = 'root_password_2024';

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    // BUG: Weak password policy
    public function validatePassword(string $password): bool
    {
        // BUG: Only checks length >= 4, no complexity requirements
        return strlen($password) >= 4;
    }

    // BUG: Insecure token generation
    public function generateToken(User $user): string
    {
        // BUG: Predictable token using md5 and time()
        return md5($user->getUsername() . time());
    }

    // BUG: Insecure password reset - predictable token
    public function generateResetToken(User $user): string
    {
        // BUG: Reset token is just base64 of the email
        return base64_encode($user->getEmail());
    }

    // BUG: Log sensitive data
    public function logUserLogin(User $user): void
    {
        $logEntry = sprintf(
            "[%s] User login: %s, password: %s, api_key: %s\n",
            date('Y-m-d H:i:s'),
            $user->getUsername(),
            $user->getPassword(), // BUG: Logging password
            $user->getApiKey()    // BUG: Logging API key
        );

        // BUG: World-readable log file
        file_put_contents('/tmp/app_login.log', $logEntry, FILE_APPEND);
    }

    // BUG: Insecure session management
    public function createSession(User $user): string
    {
        // BUG: Session ID is just the user ID - easily guessable
        $sessionId = (string) $user->getId();

        // BUG: Storing session in /tmp with predictable name
        file_put_contents('/tmp/session_' . $sessionId, serialize($user));

        return $sessionId;
    }

    // BUG: eval() with user data
    public function calculateDiscount(string $formula): float
    {
        // BUG: Using eval with user input - Remote Code Execution
        return eval('return ' . $formula . ';');
    }

    // BUG: Weak encryption
    public function encryptData(string $data): string
    {
        // BUG: Using deprecated/weak encryption
        // BUG: ECB mode, hardcoded key, no IV
        return openssl_encrypt($data, 'aes-128-ecb', 'weak_key');
    }

    // BUG: No rate limiting on authentication
    public function authenticate(string $username, string $password): ?User
    {
        // BUG: No brute force protection
        // BUG: No account lockout
        return $this->userRepository->findByCredentials($username, $password);
    }

    // BUG: Regex DoS (ReDoS)
    public function validateInput(string $input): bool
    {
        // BUG: Catastrophic backtracking possible
        return (bool) preg_match('/^(a+)+$/', $input);
    }
}
