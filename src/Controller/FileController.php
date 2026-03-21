<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    // BUG: Path traversal - user can read any file on the system
    #[Route('/file/download', methods: ['GET'])]
    public function downloadFile(Request $request): BinaryFileResponse
    {
        $filename = $request->query->get('file');
        // BUG: No sanitization, allows ../../etc/passwd
        $filepath = '/var/www/uploads/' . $filename;

        return new BinaryFileResponse($filepath);
    }

    // BUG: Unrestricted file upload - no type/size validation
    #[Route('/file/upload', methods: ['POST'])]
    public function uploadFile(Request $request): Response
    {
        $file = $request->files->get('document');

        // BUG: No file type validation - can upload .php, .exe, etc.
        // BUG: No file size check
        // BUG: Using original filename directly (could overwrite files)
        $file->move('/var/www/uploads/', $file->getClientOriginalName());

        return $this->json(['status' => 'uploaded', 'filename' => $file->getClientOriginalName()]);
    }

    // BUG: Command injection via filename
    #[Route('/file/info', methods: ['GET'])]
    public function fileInfo(Request $request): Response
    {
        $filename = $request->query->get('filename');
        // BUG: Command injection - shell_exec with user input
        $output = shell_exec('file /var/www/uploads/' . $filename);

        return $this->json(['info' => $output]);
    }

    // BUG: SSRF - server-side request forgery
    #[Route('/file/fetch', methods: ['POST'])]
    public function fetchRemoteFile(Request $request): Response
    {
        $url = $request->request->get('url');
        // BUG: No URL validation - can access internal services
        $content = file_get_contents($url);

        return new Response($content);
    }

    // BUG: Information disclosure via error messages
    #[Route('/file/read', methods: ['GET'])]
    public function readFile(Request $request): Response
    {
        $path = $request->query->get('path');

        try {
            $content = file_get_contents($path);
            return new Response($content);
        } catch (\Exception $e) {
            // BUG: Leaking full file path and internal error details
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
