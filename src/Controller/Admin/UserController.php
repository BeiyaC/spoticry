<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private PaginatorInterface $paginator
    ) {
    }

    #[Route('', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/user/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/create-admin', name: 'app_create_admin', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function createAdmin(Request $request): Response
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'administrateur a été créé avec succès.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/create_admin.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-admin', name: 'app_admin_user_toggle_admin', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleAdmin(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier vos propres droits.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles)) {
                $roles = array_diff($roles, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
                $user->setRoles($roles);
                $this->addFlash('success', 'Les droits administrateur ont été retirés.');
            } else {
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
                $this->addFlash('success', 'Les droits administrateur ont été accordés.');
            }

            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
