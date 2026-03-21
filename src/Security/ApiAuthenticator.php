<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $apiKey = $request->headers->get('X-API-KEY');

        if (null === $apiKey) {
            throw new AuthenticationException('No API key provided');
        }

        // BUG: API key comparison vulnerable to timing attack
        // BUG: No rate limiting on API key attempts
        return new SelfValidatingPassport(
            new UserBadge($apiKey, function ($apiKey) {
                $user = $this->userRepository->findOneBy(['apiKey' => $apiKey]);

                if (!$user) {
                    throw new AuthenticationException('Invalid API key');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // BUG: Returning too much info on success
        return new JsonResponse([
            'status' => 'authenticated',
            'user_id' => $token->getUser()->getId(),
            'roles' => $token->getUser()->getRoles(),
            'api_key' => $token->getUser()->getApiKey(), // BUG: Echoing API key back
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // BUG: Leaking whether the API key exists or not (enumeration)
        return new JsonResponse([
            'error' => $exception->getMessage(),
            'attempted_key' => $request->headers->get('X-API-KEY'), // BUG: Echoing failed key
        ], Response::HTTP_UNAUTHORIZED);
    }
}
