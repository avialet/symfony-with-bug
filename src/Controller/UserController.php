<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $userRepository;
    private $userService;

    public function __construct(UserRepository $userRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    // BUG: SQL Injection - user input directly concatenated into query
    #[Route('/user/search', methods: ['GET'])]
    public function searchUser(Request $request): Response
    {
        $username = $request->query->get('username');
        $conn = $this->userRepository->getEntityManager()->getConnection();
        $sql = "SELECT * FROM users WHERE username = '" . $username . "'";
        $result = $conn->executeQuery($sql);

        return $this->json($result->fetchAllAssociative());
    }

    // BUG: XSS - rendering raw user input without escaping
    #[Route('/user/profile/{id}', methods: ['GET'])]
    public function showProfile(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        $bio = $request->query->get('bio', $user->getBio());

        // BUG: Directly outputting user-controlled HTML
        return new Response('<html><body><h1>Profile</h1><div>' . $bio . '</div></body></html>');
    }

    // BUG: Mass assignment - no validation of fields being updated
    #[Route('/user/update', methods: ['POST'])]
    public function updateUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->find($data['id']);

        // BUG: Updating all fields from user input including role/admin flags
        foreach ($data as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            }
        }

        $this->userRepository->getEntityManager()->flush();

        return $this->json(['status' => 'updated']);
    }

    // BUG: IDOR - no authorization check, any user can delete any other user
    #[Route('/user/delete/{id}', methods: ['DELETE'])]
    public function deleteUser(int $id): Response
    {
        $user = $this->userRepository->find($id);
        $this->userRepository->getEntityManager()->remove($user);
        $this->userRepository->getEntityManager()->flush();

        return $this->json(['status' => 'deleted']);
    }

    // BUG: Null pointer - no null check on find() result
    #[Route('/user/{id}', methods: ['GET'])]
    public function getUser(int $id): Response
    {
        $user = $this->userRepository->find($id);
        // BUG: $user could be null, calling method on null
        return $this->json([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
    }

    // BUG: Open redirect
    #[Route('/user/login-redirect', methods: ['GET'])]
    public function loginRedirect(Request $request): Response
    {
        $redirectUrl = $request->query->get('redirect_url');
        // BUG: No validation of redirect URL - allows redirect to external malicious sites
        return $this->redirect($redirectUrl);
    }

    // BUG: Hardcoded credentials
    #[Route('/user/admin-login', methods: ['POST'])]
    public function adminLogin(Request $request): Response
    {
        $password = $request->request->get('password');

        // BUG: Hardcoded password, plain text comparison
        if ($password === 'admin123!') {
            return $this->json(['token' => md5(time())]);
        }

        return $this->json(['error' => 'Unauthorized'], 401);
    }
}
