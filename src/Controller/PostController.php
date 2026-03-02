<?php
// src/Controller/PostController.php
namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostImage;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/community')]
class PostController extends AbstractController
{
    #[Route('/', name: 'community_index')]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupère tous les posts approuvés, du plus récent au plus ancien
        $posts = $em->getRepository(Post::class)->findBy(['status' => 'APPROVED'], ['createdAt' => 'DESC']);

        return $this->render('community/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/create', name: 'community_create')]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, EntityManagerInterface $em, \App\Service\AIPostManager $aiPostManager): Response
    {
        $post = new Post();

        // Crée le formulaire via AbstractController
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $post->setAuthor($this->getUser());
            $post->setCreatedAt(new \DateTime());

            // ✅ Gestion des images uploadées
            $imageFiles = $form->get('images')->getData(); // tableau de UploadedFile
            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {

                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );

                        // Crée l'entité PostImage et lie au Post
                        $postImage = new PostImage();
                        $postImage->setFilename($newFilename);
                        $post->addImage($postImage);

                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l’upload de l’image.');
                    }
                }
            }

            // ✅ Analyse IA du post (Texte + Image)
            $aiPostManager->processPost($post);

            $em->persist($post);
            $em->flush();

            if ($post->getStatus() === 'PENDING') {
                $this->addFlash('error', '⚠️ Votre post ne respecte pas les règles de notre communauté (langage inapproprié ou image sensible) et a été bloqué par notre système de modération.');
            } else {
                $this->addFlash('success', 'Votre post a été publié avec succès !');
            }

            return $this->redirectToRoute('community_index');
        }

    return $this->render('community/create.html.twig', [
        'form' => $form->createView(),
    ]);
}



    #[Route('/{id}/delete', name: 'community_delete')]
    public function delete(Post $post, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Seul l'auteur ou un admin peut supprimer
        if ($post->getAuthor() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce post.');
        }

        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'Le post a été supprimé.');

        return $this->redirectToRoute('community_index');
    }
    #[Route('/api/ai/refine', name: 'api_ai_refine_text', methods: ['POST'])]
    public function refineText(Request $request, \App\Service\HuggingFaceService $hf): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';

        if (empty($text)) {
            return $this->json(['error' => 'Texte vide'], 400);
        }

        $refined = $hf->refineTextContent($text);

        return $this->json(['refined' => $refined]);
    }

}
