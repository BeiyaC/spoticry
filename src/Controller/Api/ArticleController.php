<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/articles')]
class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_article_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $published = $request->query->get('published');

        $criteria = [];
        if (null !== $published) {
            $criteria['isPublished'] = ('true' === $published);
        }

        $articles = $this->articleRepository->findBy(
            $criteria,
            ['publishedAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        $total = $this->articleRepository->count($criteria);
        $data = [
            'articles' => $articles,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ];

        $json = $this->serializer->serialize(
            $data,
            'json',
            ['groups' => ['article:list']]
        );

        return new JsonResponse(
            $json,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        if (!$article->isPublished() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize(
            $article,
            'json',
            ['groups' => ['article:read']]
        );

        return new JsonResponse(
            $json,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('', name: 'api_article_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): Response
    {
        $content = $request->getContent();
        $article = $this->serializer->deserialize(
            $content,
            Article::class,
            'json'
        );

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            $errs = [];
            foreach ($errors as $e) {
                $errs[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $errs], Response::HTTP_BAD_REQUEST);
        }

        if ($this->getUser()) {
            $article->setAuthor($this->getUser());
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $json = $this->serializer->serialize(
            $article,
            'json',
            ['groups' => ['article:read']]
        );

        return new JsonResponse(
            $json,
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_article_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Article $article): Response
    {
        $content = $request->getContent();
        $article = $this->serializer->deserialize(
            $content,
            Article::class,
            'json',
            ['object_to_populate' => $article]
        );

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            $errs = [];
            foreach ($errors as $e) {
                $errs[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $errs], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $json = $this->serializer->serialize(
            $article,
            'json',
            ['groups' => ['article:read']]
        );

        return new JsonResponse(
            $json,
            Response::HTTP_OK,
            [],
            true
        );
    }

    #[Route('/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Article $article): Response
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/search', name: 'api_article_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $q = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        if ('' === trim($q)) {
            $data = ['articles' => [], 'meta' => ['total' => 0]];
            $json = $this->serializer->serialize($data, 'json', ['groups' => ['article:list']]);
            return new JsonResponse($json, Response::HTTP_OK, [], true);
        }

        $qb = $this->articleRepository->createQueryBuilder('a')
            ->where('a.isPublished = :pub')
            ->andWhere('a.title LIKE :s OR a.content LIKE :s OR a.artistName LIKE :s')
            ->setParameter('pub', true)
            ->setParameter('s', "%{$q}%")
            ->orderBy('a.publishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $articles = $qb->getQuery()->getResult();
        $total = (int) $this->articleRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.isPublished = :pub')
            ->andWhere('a.title LIKE :s OR a.content LIKE :s OR a.artistName LIKE :s')
            ->setParameter('pub', true)
            ->setParameter('s', "%{$q}%")
            ->getQuery()
            ->getSingleScalarResult();

        $data = [
            'articles' => $articles,
            'meta' => [
                'query' => $q,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ];

        $json = $this->serializer->serialize($data, 'json', ['groups' => ['article:list']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
