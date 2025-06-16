<?php

namespace App\MessageHandler;

use App\Message\GeneratePdfMessage;
use App\Repository\ArticleRepository;
use App\Service\PdfGeneratorService;
use DateTime;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class GeneratePdfMessageHandler
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private PdfGeneratorService $pdfGenerator,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $projectDir
    ) {
    }

    public function __invoke(GeneratePdfMessage $message): void
    {
        $article = $this->articleRepository->find($message->getArticleId());

        if (!$article) {
            return;
        }

        $filename = sprintf('article_%s_%s.pdf',
            $article->getSlug(),
            (new DateTime())->format('Y-m-d_H-i-s')
        );

        $pdfPath = $this->projectDir . '/var/pdf/' . $filename;

        $dir = dirname($pdfPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->pdfGenerator->generateArticlePdf($article, $pdfPath);

        $downloadUrl = $this->urlGenerator->generate('app_download_pdf', [
            'filename' => $filename,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('noreply@music-blog.com')
            ->to($message->getUserEmail())
            ->subject('Votre PDF est prêt - ' . $article->getTitle())
            ->html($this->getEmailContent($article->getTitle(), $downloadUrl));

        $this->mailer->send($email);
    }

    private function getEmailContent(string $articleTitle, string $downloadUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fa; }
        .button { display: inline-block; padding: 10px 20px; background-color: #28a745;
            color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Votre PDF est prêt !</h1>
    </div>
    <div class="content">
        <h2>Article : {$articleTitle}</h2>
        <p>Votre export PDF a été généré avec succès. Vous pouvez le télécharger en cliquant sur le bouton ci-dessous.</p>
        <p><strong>Note :</strong> Ce lien sera valide pendant 24 heures.</p>
        <a href="{$downloadUrl}" class="button">Télécharger le PDF</a>
    </div>
    <div class="footer">
        <p>Music Blog - Votre source d'information musicale</p>
    </div>
</div>
</body>
</html>
HTML;
    }
}
