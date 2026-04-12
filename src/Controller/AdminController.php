<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $conn = $this->entityManager->getConnection();

        $users = $conn->executeQuery('SELECT id, username, email, roles FROM users')->fetchAllAssociative();

        return $this->json([
            'total_users' => count($users),
            'users' => $users,
        ]);
    }

    #[Route('/logs', methods: ['GET'])]
    public function viewLogs(Request $request): Response
    {
        $file = $request->query->get('file');

        $allowedFiles = ['app.log', 'error.log'];
        if (!in_array($file, $allowedFiles)) {
            return new Response('Access denied', 403);
        }

        $logDir = $this->getParameter('kernel.logs_dir');
        $logFile = $logDir . '/' . $file;

        if (!file_exists($logFile)) {
             return new Response('File not found', 404);
        }

        $content = file_get_contents($logFile);

        return new Response('<pre>' . htmlspecialchars($content) . '</pre>');
    }

    #[Route('/api/config', methods: ['GET'])]
    public function getConfig(): Response
    {
        return $this->json([
            'debug' => false,
        ]);
    }
}
