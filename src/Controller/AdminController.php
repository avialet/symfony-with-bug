<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // BUG: No authentication/authorization check on admin routes
    #[Route('/dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $conn = $this->getDoctrine()->getManager()->getConnection();

        // BUG: Exposing all user data including passwords on admin dashboard
        $users = $conn->executeQuery('SELECT * FROM users')->fetchAllAssociative();

        return $this->json([
            'total_users' => count($users),
            'users' => $users, // BUG: Includes passwords, API keys, credit cards
        ]);
    }

    // BUG: Arbitrary code execution via admin panel
    #[Route('/execute', methods: ['POST'])]
    public function executeCommand(Request $request): Response
    {
        $command = $request->request->get('command');

        // BUG: Executing arbitrary system commands
        $output = [];
        exec($command, $output, $returnCode);

        return $this->json([
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
        ]);
    }

    // BUG: Arbitrary SQL execution
    #[Route('/query', methods: ['POST'])]
    public function executeQuery(Request $request): Response
    {
        $sql = $request->request->get('sql');
        $conn = $this->getDoctrine()->getManager()->getConnection();

        // BUG: Running any SQL query from user input
        $result = $conn->executeQuery($sql)->fetchAllAssociative();

        return $this->json($result);
    }

    // BUG: Log file exposure
    #[Route('/logs', methods: ['GET'])]
    public function viewLogs(Request $request): Response
    {
        $logFile = $request->query->get('file', '/var/log/app.log');
        // BUG: Path traversal in log viewer
        $content = file_get_contents($logFile);

        return new Response('<pre>' . $content . '</pre>');
    }

    // BUG: Backup download without auth
    #[Route('/backup', methods: ['GET'])]
    public function downloadBackup(): Response
    {
        // BUG: Generating database dump accessible without authentication
        $dumpFile = '/tmp/db_backup_' . date('Y-m-d') . '.sql';
        exec('mysqldump -u root -proot_password myapp > ' . $dumpFile);

        return $this->file($dumpFile);
    }

    // BUG: CORS misconfiguration
    #[Route('/api/config', methods: ['GET', 'OPTIONS'])]
    public function getConfig(Request $request): Response
    {
        $response = $this->json([
            'db_host' => '127.0.0.1',
            'db_name' => 'myapp',
            'redis_host' => '10.0.1.50',
            'app_secret' => 'thisismysecretchangeit', // BUG: Exposing app secret
            'debug' => true,
        ]);

        // BUG: Wildcard CORS - allows any origin
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');

        return $response;
    }
}
