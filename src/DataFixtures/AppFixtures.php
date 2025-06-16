<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $superAdmin = new User();
        $superAdmin->setEmail('superadmin@music-blog.com');
        $superAdmin->setUsername('SuperAdmin');
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'superadmin123'));
        $manager->persist($superAdmin);

        $admin = new User();
        $admin->setEmail('admin@music-blog.com');
        $admin->setUsername('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $user = new User();
        $user->setEmail('user@music-blog.com');
        $user->setUsername('Utilisateur');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        $artists = [
            [
                'name' => 'The Beatles',
                'spotifyId' => '3WrFJ7ztbogyGnTHbHJFl2',
                'title' => 'The Beatles : L\'héritage immortel du groupe le plus influent',
                'content' => 'Les Beatles ont révolutionné la musique populaire dans les années 60. Leur influence perdure encore aujourd\'hui...',
                'tags' => ['rock', 'classique', '60s', 'britannique'],
            ],
            [
                'name' => 'Daft Punk',
                'spotifyId' => '4tZwfgrHOc3mvqYlEYSvVi',
                'title' => 'Daft Punk : Les pionniers de l\'électro française',
                'content' => 'Le duo français a marqué l\'histoire de la musique électronique avec des albums emblématiques comme Discovery et Random Access Memories...',
                'tags' => ['électro', 'français', 'dance', 'house'],
            ],
            [
                'name' => 'Beyoncé',
                'spotifyId' => '6vWDO969PvNqNYHIOW5v0m',
                'title' => 'Beyoncé : La reine incontestée du R&B moderne',
                'content' => 'De Destiny\'s Child à sa carrière solo phénoménale, Beyoncé a redéfini ce que signifie être une superstar au 21e siècle...',
                'tags' => ['r&b', 'pop', 'américain', 'soul'],
            ],
            [
                'name' => 'Stromae',
                'spotifyId' => '3V2paBXEoZIAhfZRJmo2jL',
                'title' => 'Stromae : Le génie belge de la nouvelle chanson française',
                'content' => 'Avec ses textes profonds et ses mélodies accrocheuses, Stromae a conquis le monde francophone et au-delà...',
                'tags' => ['francophone', 'belge', 'pop', 'électro'],
            ],
            [
                'name' => 'Miles Davis',
                'spotifyId' => '0kbYTNQb4Pb1rPbbaF0pT4',
                'title' => 'Miles Davis : L\'architecte du jazz moderne',
                'content' => 'Miles Davis a constamment réinventé le jazz, du bebop au jazz fusion, laissant une empreinte indélébile sur la musique...',
                'tags' => ['jazz', 'classique', 'américain', 'instrumental'],
            ],
        ];

        foreach ($artists as $index => $artistData) {
            $article = new Article();
            $article->setTitle($artistData['title']);
            $article->setArtistName($artistData['name']);
            $article->setArtistSpotifyId($artistData['spotifyId']);
            $article->setContent($this->generateLongContent($artistData['content']));
            $article->setExcerpt(substr($artistData['content'], 0, 200) . '...');
            $article->setTags($artistData['tags']);
            $article->setAuthor($admin);
            $article->setIsPublished(true);
            $article->setPublishedAt(new \DateTimeImmutable('-' . ($index + 1) . ' days'));
            $article->setViewCount(rand(100, 5000));

            $manager->persist($article);

            for ($i = 0; $i < rand(2, 5); $i++) {
                $comment = new Comment();
                $comment->setArticle($article);
                $comment->setAuthor(rand(0, 1) ? $admin : $user);
                $comment->setContent($this->generateCommentContent());
                $comment->setCreatedAt(new \DateTimeImmutable('-' . rand(1, 10) . ' hours'));

                $manager->persist($comment);
            }
        }

        $manager->flush();
    }

    private function generateLongContent(string $intro): string
    {
        $paragraphs = [
            "L'histoire de cet artiste est fascinante et mérite d'être racontée en détail. Depuis ses débuts modestes jusqu'à la consécration internationale, le parcours est jalonné de moments clés qui ont façonné sa carrière et influencé des générations de musiciens.",
            "Les premières années ont été marquées par une recherche constante de son identité musicale. Les influences diverses, allant du jazz au rock en passant par la musique classique, ont contribué à forger un style unique et reconnaissable entre mille.",
            "L'album qui a véritablement lancé la carrière reste un monument de la musique moderne. Chaque titre raconte une histoire, chaque mélodie transporte l'auditeur dans un univers sonore riche et complexe. La production, innovante pour l'époque, continue d'inspirer les artistes contemporains.",
            "Les collaborations avec d'autres artistes majeurs ont permis d'explorer de nouveaux territoires musicaux. Ces rencontres artistiques ont donné naissance à des œuvres hybrides, mélangeant les genres et repoussant les limites de la créativité.",
            "L'impact culturel dépasse largement le cadre musical. Les textes engagés, les prises de position publiques et l'engagement social ont fait de cet artiste une véritable icône, un porte-voix pour toute une génération.",
            "Les concerts restent des moments de communion intense avec le public. L'énergie déployée sur scène, la connexion établie avec les fans et la qualité des performances live ont contribué à bâtir une réputation d'excellence.",
            "L'évolution stylistique au fil des albums témoigne d'une volonté constante de se réinventer. Chaque nouvelle sortie apporte son lot de surprises, tout en conservant cette essence qui rend la musique immédiatement identifiable.",
            "Les récompenses et distinctions accumulées ne sont que la partie visible de l'iceberg. Au-delà des Grammy Awards, des disques d'or et de platine, c'est l'influence durable sur l'industrie musicale qui constitue le véritable héritage.",
            "Les jeunes artistes citent régulièrement cette influence majeure dans leur parcours. Les techniques de production, les approches mélodiques et l'attitude artistique continuent d'inspirer les nouvelles générations.",
            "L'avenir s'annonce tout aussi prometteur. Les projets en cours, les collaborations annoncées et les nouvelles directions artistiques explorées laissent présager encore de belles surprises pour les années à venir."
        ];

        $content = $intro . "\n\n";

        $selectedParagraphs = array_rand($paragraphs, rand(5, 7));
        if (!is_array($selectedParagraphs)) {
            $selectedParagraphs = [$selectedParagraphs];
        }

        foreach ($selectedParagraphs as $index) {
            $content .= $paragraphs[$index] . "\n\n";
        }

        return $content;
    }

    private function generateCommentContent(): string
    {
        $comments = [
            "Excellent article ! J'ai appris beaucoup de choses sur cet artiste que je ne connaissais pas.",
            "Merci pour cette analyse approfondie. La partie sur les influences musicales est particulièrement intéressante.",
            "Je suis fan depuis des années et cet article capture vraiment l'essence de ce qui rend cet artiste si spécial.",
            "Très bien écrit ! J'aimerais voir plus d'articles sur les collaborations mentionnées.",
            "Superbe travail de recherche. Les anecdotes sur les coulisses des enregistrements sont fascinantes.",
            "Cet article m'a donné envie de réécouter toute la discographie !",
            "Analyse pertinente et bien documentée. Bravo pour la qualité du contenu.",
            "J'apprécie particulièrement la mise en contexte historique. Ça aide vraiment à comprendre l'évolution de l'artiste.",
            "Article passionnant ! La section sur l'impact culturel est vraiment bien développée.",
            "Merci de mettre en lumière ces artistes qui ont façonné la musique moderne.",
        ];

        return $comments[array_rand($comments)];
    }
}
