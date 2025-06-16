<?php

namespace App\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comments')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Comment $comment): Response
    {
        if ($comment->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setIsDeleted(true);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le commentaire a été supprimé.');
        }

        return $this->redirectToRoute('app_article_show', ['slug' => $comment->getArticle()->getSlug()]);
    }

    #[Route('/{id}/approve', name: 'app_comment_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('approve'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setIsApproved(true);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le commentaire a été approuvé.');
        }

        return $this->redirectToRoute('app_article_show', ['slug' => $comment->getArticle()->getSlug()]);
    }

    #[Route('/{id}/reject', name: 'app_comment_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Request $request, Comment $comment): Response
    {
        if ($this->isCsrfTokenValid('reject'.$comment->getId(), $request->request->get('_token'))) {
            $comment->setIsApproved(false);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le commentaire a été rejeté.');
        }

        return $this->redirectToRoute('app_article_show', ['slug' => $comment->getArticle()->getSlug()]);
    }
}
