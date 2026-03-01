<?php

namespace App\Service;

use App\Entity\ShopProduct;
use App\Repository\ShopProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI;

class AIProductService
{
    private string $openaiApiKey;
    private ShopProductRepository $productRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(string $openaiApiKey, ShopProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        $this->openaiApiKey = $openaiApiKey;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Génère des recommandations basées sur un produit spécifique
     */
    public function getRecommendationsForProduct(ShopProduct $product, int $limit = 4): array
    {
        try {
            $client = OpenAI::client($this->openaiApiKey);
            
            $prompt = sprintf(
                "Basé sur ce produit:\n" .
                "Nom: %s\n" .
                "Type: %s\n" .
                "Prix: %.2f€\n\n" .
                "Suggère 4 produits similaires ou complémentaires qui intéresseraient un client regardant ce produit. " .
                "Retourne uniquement les noms de produits, un par ligne, sans explication. " .
                "Évite de suggérer le même produit.",
                $product->getName(),
                $product->getType(),
                $product->getPrice()
            );

            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en e-commerce. Suggère des produits pertinents et variés.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.7
            ]);

            $recommendations = array_filter(
                explode("\n", $response->choices[0]->message->content),
                function(string $value): bool {
                    return !empty(trim($value));
                }
            );

            // Rechercher les produits correspondants dans la base de données
            $recommendedProducts = [];
            foreach ($recommendations as $recommendation) {
                $products = $this->productRepository->findByNameLike($recommendation, $limit);
                foreach ($products as $recommendedProduct) {
                    // Éviter de recommander le même produit
                    if ($recommendedProduct->getId() !== $product->getId()) {
                        $recommendedProducts[] = $recommendedProduct;
                        if (count($recommendedProducts) >= $limit) {
                            break 2;
                        }
                    }
                }
            }

            // Si pas assez de recommandations, ajouter des produits du même type
            if (count($recommendedProducts) < $limit) {
                $sameTypeProducts = $this->productRepository->findByType($product->getType(), $limit - count($recommendedProducts));
                foreach ($sameTypeProducts as $sameTypeProduct) {
                    if ($sameTypeProduct->getId() !== $product->getId() && !in_array($sameTypeProduct, $recommendedProducts)) {
                        $recommendedProducts[] = $sameTypeProduct;
                    }
                }
            }

            return array_slice($recommendedProducts, 0, $limit);

        } catch (\Exception $e) {
            // En cas d'erreur, retourner des recommandations par défaut du même type
            return $this->productRepository->findByType($product->getType(), $limit);
        }
    }

    /**
     * Génère des recommandations de produits basées sur l'historique d'achat
     */
    public function getRecommendationsForUser(array $userProducts, int $limit = 5): array
    {
        try {
            $client = OpenAI::client($this->openaiApiKey);
            
            // Analyser les produits de l'utilisateur
            $productNames = array_map(function($product) {
                return $product instanceof ShopProduct ? $product->getName() : $product['name'] ?? 'Unknown';
            }, $userProducts);
            
            $productTypes = array_map(function($product) {
                return $product instanceof ShopProduct ? $product->getType() : $product['type'] ?? 'unknown';
            }, $userProducts);
            
            $prompt = sprintf(
                "Basé sur les produits suivants achetés par un client:\n" .
                "Produits: %s\n" .
                "Types: %s\n\n" .
                "Suggère 5 produits similaires ou complémentaires qui pourraient intéresser ce client. " .
                "Retourne uniquement les noms de produits, un par ligne, sans explication.",
                implode(', ', $productNames),
                implode(', ', array_unique($productTypes))
            );

            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en marketing et recommandations de produits. Sois concis et pertinent.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.7
            ]);

            $recommendations = array_filter(
                explode("\n", $response->choices[0]->message->content),
                function(string $value): bool {
                    return !empty(trim($value));
                }
            );

            // Rechercher les produits correspondants dans la base de données
            $recommendedProducts = [];
            foreach ($recommendations as $recommendation) {
                $products = $this->productRepository->findByNameLike($recommendation, $limit);
                foreach ($products as $product) {
                    if (!in_array($product->getId(), array_map(function($p) { 
                        return $p instanceof ShopProduct ? $p->getId() : $p['id'] ?? 0; 
                    }, $userProducts))) {
                        $recommendedProducts[] = $product;
                        if (count($recommendedProducts) >= $limit) {
                            break 2;
                        }
                    }
                }
            }

            return $recommendedProducts;

        } catch (\Exception $e) {
            // En cas d'erreur, retourner des recommandations par défaut
            return $this->getDefaultRecommendations($userProducts, $limit);
        }
    }

    /**
     * Génère une description de produit optimisée SEO
     */
    public function generateProductDescription(ShopProduct $product): string
    {
        try {
            $client = OpenAI::client($this->openaiApiKey);
            
            $prompt = sprintf(
                "Génère une description SEO optimisée et attractive pour ce produit:\n" .
                "Nom: %s\n" .
                "Type: %s\n" .
                "Prix: %.2f€\n\n" .
                "La description doit être:\n" .
                "- SEO optimisée (mots-clés pertinents)\n" .
                "- Engageante et persuasive\n" .
                "- Entre 100-150 mots\n" .
                "- En français\n" .
                "- Sans caractères spéciaux ni emojis",
                $product->getName(),
                $product->getType(),
                $product->getPrice()
            );

            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en e-commerce et copywriting. Génère des descriptions de produits optimisées pour la vente.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.8
            ]);

            return trim($response->choices[0]->message->content);

        } catch (\Exception $e) {
            return "Découvrez ce produit exceptionnel de qualité supérieure. " .
                   "Parfait pour répondre à vos besoins avec ses caractéristiques uniques " .
                   "et son design innovant. Une valeur inégalée pour un investissement intelligent.";
        }
    }

    /**
     * Analyse les tendances des produits et suggère des catégories populaires
     */
    public function getTrendingCategories(): array
    {
        try {
            $client = OpenAI::client($this->openaiApiKey);
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert en tendances e-commerce et gaming.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Quelles sont les catégories de produits gaming et merch les plus tendances actuellement? " .
                                     "Donne-moi 5 catégories avec une brève description. Format: Catégorie: Description"
                    ]
                ],
                'max_tokens' => 200,
                'temperature' => 0.7
            ]);

            $categories = [];
            $lines = explode("\n", $response->choices[0]->message->content);
            
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($category, $description) = explode(':', $line, 2);
                    $categories[trim($category)] = trim($description);
                }
            }

            return $categories;

        } catch (\Exception $e) {
            return [
                'Skins Gaming' => 'Personnalisation avancée pour joueurs',
                'Merch Gaming' => 'Vêtements et accessoires gaming',
                'Points & Monnaie' => 'Monnaie virtuelle pour jeux',
                'Accessoires PC' => 'Équipement pour gamers',
                'Collections Exclusives' => 'Objets rares et limités'
            ];
        }
    }

    /**
     * Recommandations par défaut si l'API OpenAI n'est pas disponible
     */
    private function getDefaultRecommendations(array $userProducts, int $limit): array
    {
        $userType = null;
        foreach ($userProducts as $product) {
            $type = $product instanceof ShopProduct ? $product->getType() : $product['type'] ?? null;
            if ($type) {
                $userType = $type;
                break;
            }
        }

        // Logique simple de recommandation par type avec eager loading pour éviter N+1
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('p', 'i')
           ->from(ShopProduct::class, 'p')
           ->leftJoin('p.images', 'i')
           ->where('p.type = :type')
           ->andWhere('p.isActive = :active')
           ->setParameter('type', $userType === 'skin' ? 'merch' : 'skin')
           ->setParameter('active', true)
           ->orderBy('p.price', 'ASC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Génère des tags pour un produit
     */
    public function generateProductTags(ShopProduct $product): array
    {
        try {
            $client = OpenAI::client($this->openaiApiKey);
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Génère des tags pertinents pour un produit e-commerce.'
                    ],
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            "Génère 5-8 tags SEO pour ce produit:\nNom: %s\nType: %s\nPrix: %.2f€\n\n" .
                            "Retourne uniquement les tags séparés par des virgules, sans autre texte.",
                            $product->getName(),
                            $product->getType(),
                            $product->getPrice()
                        )
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.5
            ]);

            $tags = explode(',', $response->choices[0]->message->content);
            return array_map('trim', array_filter($tags));

        } catch (\Exception $e) {
            return ['gaming', 'shop', 'online', 'digital', 'quality'];
        }
    }
}
