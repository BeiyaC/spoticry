<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository,
        UserRepository $userRepository
    ): Response {
        $stats = [
            'totalArticles' => $articleRepository->count([]),
            'publishedArticles' => $articleRepository->count(['isPublished' => true]),
            'draftArticles' => $articleRepository->count(['isPublished' => false]),
            'totalComments' => $commentRepository->count([]),
            'pendingComments' => $commentRepository->countPending(),
            'totalUsers' => $userRepository->count([]),
            'totalAdmins' => $userRepository->countByRole('ROLE_ADMIN'),
        ];

        $recentArticles = $articleRepository->findBy([], ['createdAt' => 'DESC'], 5);

        $popularArticles = $articleRepository->findMostViewed(5);

        $recentComments = $commentRepository->findRecent(5);

        $allTags = $articleRepository->findAllTags();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentArticles' => $recentArticles,
            'popularArticles' => $popularArticles,
            'recentComments' => $recentComments,
            'popularTags' => array_slice($allTags, 0, 10),
        ]);
    }
}
