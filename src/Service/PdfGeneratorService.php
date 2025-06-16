<?php

namespace App\Service;

use App\Entity\Article;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(
        private Environment $twig,
        private SpotifyService $spotifyService
    ) {
    }

    public function generateArticlePdf(Article $article, string $outputPath): void
    {
        $artistData = null;
        if ($article->getArtistSpotifyId()) {
            try {
                $artistData = $this->spotifyService->getFullArtistData($article->getArtistSpotifyId());
            } catch (\Exception $e) {
            }
        }

        $html = $this->twig->render('pdf/article.html.twig', [
            'article' => $article,
            'artistData' => $artistData,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($outputPath, $dompdf->output());
    }

    public function generateArtistCardPdf(Article $article, string $outputPath): void
    {
        $artistData = null;
        if ($article->getArtistSpotifyId()) {
            try {
                $artistData = $this->spotifyService->getFullArtistData($article->getArtistSpotifyId());
            } catch (\Exception $e) {
            }
        }

        $html = $this->twig->render('pdf/article.html.twig', [
            'article' => $article,
            'artistData' => $artistData,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($outputPath, $dompdf->output());
    }
}
