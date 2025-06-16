<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    #[Route('/download/pdf/{filename}', name: 'app_download_pdf')]
    #[IsGranted('ROLE_USER')]
    public function downloadPdf(string $filename): Response
    {
        if (!preg_match('/^article_[a-z0-9\-_]+_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.pdf$/i', $filename)) {
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/pdf/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier PDF n\'existe pas');
        }

        if (filemtime($filePath) < time() - 86400) {
            unlink($filePath);
            throw $this->createNotFoundException('Le lien de téléchargement a expiré');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        return $response;
    }
}
