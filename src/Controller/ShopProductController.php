<?php

namespace App\Controller;

use App\Entity\ShopProduct;
use App\Form\ShopProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shop/product')]
final class ShopProductController extends AbstractController
{
    #[Route(name: 'app_shop_product_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('shop_product/index.html.twig', [
            'shop_products' => $entityManager->getRepository(ShopProduct::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_shop_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]

public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger
): Response {
    $shopProduct = new ShopProduct();
    $form = $this->createForm(ShopProductType::class, $shopProduct);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 IMAGE PRINCIPALE
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('shop_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’upload de l’image principale');
            }

            $shopProduct->setImage($newFilename);
        }

        // 🔥 IMAGES MULTIPLES
        $imagesFiles = $form->get('imagesFiles')->getData();
        if ($imagesFiles) {
            foreach ($imagesFiles as $imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                 try {
            $imageFile->move($this->getParameter('shop_images_directory'), $newFilename);
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l’upload d’une image secondaire');
        }

                $shopImage = new \App\Entity\ShopProductImage();
                $shopImage->setFilename($newFilename);
                $shopProduct->addImage($shopImage);
            }
        }

        $entityManager->persist($shopProduct);
        $entityManager->flush();

        return $this->redirectToRoute('app_shop_product_index');
    }

   return $this->render('shop_product/new.html.twig', [
    'form' => $form->createView(),
    'shopProduct' => $shopProduct, // pour prévisualisation si nécessaire
]);
}

    #[Route('/{id}', name: 'app_shop_product_show', methods: ['GET'])]
    public function show(ShopProduct $shopProduct): Response
    {
        return $this->render('shop_product/show.html.twig', [
            'shop_product' => $shopProduct,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_shop_product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]

public function edit(
    Request $request,
    ShopProduct $shopProduct,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger
): Response {
    $form = $this->createForm(ShopProductType::class, $shopProduct);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 IMAGE PRINCIPALE (si nouvelle image)
        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('shop_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l’upload de l’image principale');
            }

            $shopProduct->setImage($newFilename);
        }

        // 🔥 IMAGES MULTIPLES (ajout sans supprimer les anciennes)
        $imagesFiles = $form->get('imagesFiles')->getData();
        if ($imagesFiles) {
            foreach ($imagesFiles as $imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('shop_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l’upload d’une image secondaire');
                }

                $shopImage = new \App\Entity\ShopProductImage();
                $shopImage->setFilename($newFilename);
                $shopProduct->addImage($shopImage);
                $entityManager->persist($shopImage);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Produit mis à jour avec succès !');

        return $this->redirectToRoute('app_shop_product_index');
    }

    return $this->render('shop_product/edit.html.twig', [
        'shop_product' => $shopProduct,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}', name: 'app_shop_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]

    public function delete(Request $request,
    ShopProduct $shopProduct,
    EntityManagerInterface $entityManager
): Response {
    if ($this->isCsrfTokenValid('delete'.$shopProduct->getId(), $request->request->get('_token'))) {

        // ❌ suppression physique
        // $entityManager->remove($shopProduct);

        // ✅ désactivation
        $shopProduct->setIsActive(false);
        $entityManager->flush();

        $this->addFlash('success', 'Produit désactivé avec succès');
    }

    return $this->redirectToRoute('app_shop_product_index');
}
}
