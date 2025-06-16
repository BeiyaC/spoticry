<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Message\GeneratePdfMessage;
use App\Repository\ArticleRepository;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/articles')]
class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
        private SpotifyService $spotifyService,
        private PaginatorInterface $paginator,
        private MessageBusInterface $messageBus
    ) {
    }

    #[Route('', name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->articleRepository->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('article/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(
        '/article/{slug}',
        name: 'app_article_show',
        requirements: ['slug' => '[^/]++'],
        methods: ['GET', 'POST']
    )]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Article $article,
        Request $request
    ): Response
    {
        if (!$article->isPublished() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $article->incrementViewCount();
        $this->entityManager->flush();

        if ($article->getArtistSpotifyId() && empty($article->getArtistData())) {
            try {
                $artistData = $this->spotifyService->getArtistInfo($article->getArtistSpotifyId());
                $article->setArtistData($artistData);

                if (isset($artistData['images'][0]['url'])) {
                    $article->setArtistImage($artistData['images'][0]['url']);
                }

                $this->entityManager->flush();
            } catch (\Exception $e) {
            }
        }

        $comment = new Comment();
        $comment->setArticle($article);

        $form = null;
        if ($this->getUser()) {
            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $comment->setAuthor($this->getUser());
                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');

                return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
            }
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comment_form' => $form,
        ]);
    }

    #[Route('/search', name: 'app_article_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');

        if (empty($searchTerm)) {
            return $this->redirectToRoute('app_article_index');
        }

        $query = $this->articleRepository->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->andWhere('a.title LIKE :search OR a.content LIKE :search OR a.artistName LIKE :search')
            ->setParameter('published', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('article/search.html.twig', [
            'pagination' => $pagination,
            'search_term' => $searchTerm,
        ]);
    }

    #[Route('/tag/{tag}', name: 'app_article_by_tag', methods: ['GET'])]
    public function byTag(Request $request, string $tag): Response
    {
        $query = $this->articleRepository->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->andWhere('JSON_CONTAINS(a.tags, :tag) = 1')
            ->setParameter('published', true)
            ->setParameter('tag', json_encode($tag))
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('article/by_tag.html.twig', [
            'pagination' => $pagination,
            'tag' => $tag,
        ]);
    }

    #[Route('/{slug}/export-pdf', name: 'app_article_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportPdf(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Article $article
    ): Response
    {
        if (!$article->isPublished() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $this->messageBus->dispatch(new GeneratePdfMessage(
            $article->getId(),
            $this->getUser()->getEmail()
        ));

        $this->addFlash('success', 'La génération du PDF a été lancée. Vous recevrez un email avec le lien de téléchargement.');

        return $this->redirectToRoute('app_article_show', ['slug' => $article->getSlug()]);
    }
}
