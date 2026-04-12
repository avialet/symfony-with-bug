<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private UserService $userService;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, UserService $userService, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->entityManager = $entityManager;
    }

    #[Route('/user/search', methods: ['GET'])]
    public function searchUser(Request $request): Response
    {
        $username = $request->query->get('username');
        $users = $this->userRepository->findBy(['username' => $username]);

        return $this->json($users);
    }

    #[Route('/user/profile/{id}', methods: ['GET'])]
    public function showProfile(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/update', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }
        if (isset($data['bio'])) {
            $user->setBio($data['bio']);
        }
        if (isset($data['email'])) {
             $user->setEmail($data['email']);
        }

        $this->entityManager->flush();

        return $this->json(['status' => 'updated']);
    }

    #[Route('/user/delete/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['status' => 'deleted']);
    }

    #[Route('/user/{id}', methods: ['GET'])]
    public function getUserDetails(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/user/login-redirect', methods: ['GET'])]
    public function loginRedirect(Request $request): Response
    {
        $redirectUrl = $request->query->get('redirect_url');

        if ($redirectUrl && str_starts_with($redirectUrl, '/')) {
            return $this->redirect($redirectUrl);
        }

        return $this->redirectToRoute('app_home');
    }
}
