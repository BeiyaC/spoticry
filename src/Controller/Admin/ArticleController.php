<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
        private SpotifyService $spotifyService,
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('', name: 'app_admin_article_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/article/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_admin_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $article = new Article();
        if ($this->getUser() instanceof User) {
            $article->setAuthor($this->getUser());
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($article->getArtistName()) {
                try {
                    $searchResults = $this->spotifyService->searchArtist($article->getArtistName());
                    if (!empty($searchResults['artists']['items'])) {
                        $artist = $searchResults['artists']['items'][0];
                        $article->setArtistSpotifyId($artist['id']);
                        $article->setArtistData($artist);

                        if (isset($artist['images'][0]['url'])) {
                            $article->setArtistImage($artist['images'][0]['url']);
                        }
                    }
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Impossible de récupérer les données Spotify pour cet artiste.');
                }
            }

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'article a été créé avec succès.');

            return $this->redirectToRoute('app_admin_article_index');
        }

        return $this->render('admin/article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('admin/article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($article->getArtistName() && $form->get('updateSpotifyData')->getData()) {
                try {
                    $searchResults = $this->spotifyService->searchArtist($article->getArtistName());
                    if (!empty($searchResults['artists']['items'])) {
                        $artist = $searchResults['artists']['items'][0];
                        $article->setArtistSpotifyId($artist['id']);
                        $article->setArtistData($artist);

                        if (isset($artist['images'][0]['url'])) {
                            $article->setArtistImage($artist['images'][0]['url']);
                        }
                    }
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Impossible de récupérer les données Spotify pour cet artiste.');
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'L\'article a été modifié avec succès.');

            return $this->redirectToRoute('app_admin_article_index');
        }

        return $this->render('admin/article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($article);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_article_index');
    }

    #[Route('/{id}/toggle-publish', name: 'app_admin_article_toggle_publish', methods: ['POST'])]
    public function togglePublish(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$article->getId(), $request->request->get('_token'))) {
            $article->setIsPublished(!$article->isPublished());

            if ($article->isPublished() && !$article->getPublishedAt()) {
                $article->setPublishedAt(new \DateTimeImmutable());
            }

            $this->entityManager->flush();

            $status = $article->isPublished() ? 'publié' : 'dépublié';
            $this->addFlash('success', "L'article a été {$status} avec succès.");
        }

        return $this->redirectToRoute('app_admin_article_index');
    }

    #[Route('/search-spotify', name: 'app_admin_article_search_spotify', methods: ['GET'])]
    public function searchSpotify(Request $request): Response
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return $this->json([]);
        }

        try {
            $results = $this->spotifyService->searchArtist($query);
            $artists = [];

            if (isset($results['artists']['items'])) {
                foreach ($results['artists']['items'] as $artist) {
                    $artists[] = [
                        'id' => $artist['id'],
                        'name' => $artist['name'],
                        'image' => $artist['images'][0]['url'] ?? null,
                        'genres' => $artist['genres'] ?? [],
                        'popularity' => $artist['popularity'] ?? 0,
                    ];
                }
            }

            return $this->json($artists);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la recherche Spotify'], 500);
        }
    }
}
