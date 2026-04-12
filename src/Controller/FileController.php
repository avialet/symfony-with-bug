<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileController extends AbstractController
{
    #[Route('/file/download', methods: ['GET'])]
    public function downloadFile(Request $request): Response
    {
        $filename = $request->query->get('file');

        // Basic path traversal protection
        if (!$filename || str_contains($filename, '..')) {
            return new Response('Invalid filename', 400);
        }

        $filepath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $filename;

        if (!file_exists($filepath)) {
            return new Response('File not found', 404);
        }

        return new BinaryFileResponse($filepath);
    }

    #[Route('/file/upload', methods: ['POST'])]
    public function uploadFile(Request $request, SluggerInterface $slugger): Response
    {
        $file = $request->files->get('document');

        if (!$file) {
            return $this->json(['error' => 'No file provided'], 400);
        }

        // Strict validation
        $allowedExtensions = ['pdf', 'docx', 'txt'];
        $extension = $file->guessExtension();

        if (!in_array($extension, $allowedExtensions)) {
            return $this->json(['error' => 'Invalid file type'], 400);
        }

        if ($file->getSize() > 5000000) { // 5MB limit
             return $this->json(['error' => 'File too large'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/var/uploads/',
                $newFilename
            );
        } catch (FileException $e) {
            return $this->json(['error' => 'Failed to save file'], 500);
        }

        return $this->json(['status' => 'uploaded', 'filename' => $newFilename]);
    }

    #[Route('/file/info', methods: ['GET'])]
    public function fileInfo(Request $request): Response
    {
        $filename = $request->query->get('filename');
        if (!$filename || str_contains($filename, '..')) {
             return $this->json(['error' => 'Invalid filename'], 400);
        }

        $filepath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $filename;

        if (!file_exists($filepath)) {
             return $this->json(['error' => 'File not found'], 404);
        }

        // Use built-in PHP functions instead of shell_exec
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        return $this->json(['info' => $mimeType]);
    }

    #[Route('/file/fetch', methods: ['POST'])]
    public function fetchRemoteFile(Request $request): Response
    {
        $url = $request->request->get('url');

        // Basic SSRF protection - allow only specific domains or schemas
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !in_array($parsedUrl['scheme'], ['http', 'https'])) {
             return new Response('Invalid URL schema', 400);
        }

        // Additional checks should be made on host (prevent internal IPs)
        $internalIps = ['127.0.0.1', '::1', '192.168.', '10.', '172.16.'];
        foreach ($internalIps as $ip) {
            if (str_starts_with($parsedUrl['host'], $ip) || $parsedUrl['host'] === 'localhost') {
                 return new Response('Access to internal network denied', 403);
            }
        }

        $content = @file_get_contents($url);
        if ($content === false) {
             return new Response('Failed to fetch remote file', 500);
        }

        return new Response($content);
    }

    #[Route('/file/read', methods: ['GET'])]
    public function readFile(Request $request): Response
    {
        $filename = $request->query->get('file');

        if (!$filename || str_contains($filename, '..')) {
            return new Response('Invalid filename', 400);
        }

        $filepath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $filename;

        try {
            if (!file_exists($filepath)) {
                throw new \Exception('File not found');
            }
            $content = file_get_contents($filepath);
            return new Response($content);
        } catch (\Exception $e) {
            // Log the detailed error internally and return a generic message
            return $this->json(['error' => 'An error occurred while reading the file'], 500);
        }
    }
}
