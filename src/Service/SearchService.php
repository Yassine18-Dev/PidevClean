<?php

namespace App\Service;

use App\Entity\ShopProduct;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;

class SearchService
{
    private $elasticsearchClient;
    private EntityManagerInterface $em;
    private string $elasticsearchHost;

    public function __construct(
        EntityManagerInterface $em,
        string $elasticsearchHost = 'http://localhost:9200'
    ) {
        $this->em = $em;
        $this->elasticsearchHost = $elasticsearchHost;
        
        // Configuration du client Elasticsearch
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$this->elasticsearchHost])
            ->build();
            
        // Test de connexion
        try {
            $this->elasticsearchClient->ping();
            error_log("Connexion Elasticsearch réussie vers " . $this->elasticsearchHost);
        } catch (\Exception $e) {
            error_log("Erreur de connexion Elasticsearch: " . $e->getMessage());
        }
    }

    /**
     * Recherche intelligente avec Elasticsearch
     */
    public function search(string $query, string $type = '', int $limit = 10): array
    {
        try {
            // Essayer Elasticsearch d'abord
            error_log("Tentative de recherche Elasticsearch avec: query=$query, type=$type");
            $results = $this->searchWithElasticsearch($query, $type, $limit);
            error_log("Elasticsearch a retourné " . count($results) . " résultats");
            
            // Si Elasticsearch ne trouve rien avec un filtre, essayer le fallback local
            if (empty($results) && !empty($type)) {
                error_log("Elasticsearch n'a rien trouvé avec le filtre, essai du fallback local");
                $results = $this->searchLocal($query, $type, $limit);
                
                // Ajouter l'indicateur de source pour fallback
                foreach ($results as &$result) {
                    $result['source'] = 'local';
                    $result['source_label'] = '🗄️ Base de données locale';
                }
            } else {
                // Ajouter l'indicateur de source
                foreach ($results as &$result) {
                    $result['source'] = 'elasticsearch';
                    $result['source_label'] = '🔍 Elasticsearch';
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            // Fallback vers recherche locale si Elasticsearch échoue
            error_log("Elasticsearch a échoué: " . $e->getMessage() . ", fallback vers recherche locale");
            $results = $this->searchLocal($query, $type, $limit);
            
            // Ajouter l'indicateur de source pour fallback
            foreach ($results as &$result) {
                $result['source'] = 'local';
                $result['source_label'] = '🗄️ Base de données locale';
            }
            
            return $results;
        }
    }

    /**
     * Recherche avec Elasticsearch
     */
    private function searchWithElasticsearch(string $query, string $type, int $limit): array
    {
        $params = [
            'index' => 'shop_products',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'wildcard' => [
                                    'name' => '*' . $query . '*'
                                ]
                            ]
                        ]
                    ]
                ],
                'highlight' => [
                    'fields' => [
                        'name' => new \stdClass(),
                        'game.name' => new \stdClass()
                    ],
                    'pre_tags' => ['<mark>'],
                    'post_tags' => ['</mark>']
                ],
                'size' => $limit
            ]
        ];

        // Ajouter le filtre par type si spécifié
        if ($type && in_array($type, ['merch', 'skin'])) {
            $params['body']['query']['bool']['filter'] = [
                'term' => ['type' => $type]  // Utiliser 'type' au lieu de 'type.keyword'
            ];
        }

        // Debug : afficher la requête exacte
        error_log("Requête Elasticsearch: " . json_encode($params, JSON_PRETTY_PRINT));

        $response = $this->elasticsearchClient->search($params);
        $results = [];

        foreach ($response['hits']['hits'] as $hit) {
            $source = $hit['_source'];
            $highlight = $hit['highlight'] ?? [];

            // Filtrer par type côté PHP si nécessaire
            if ($type && strtolower($source['type']) !== strtolower($type)) {
                continue;
            }

            $results[] = [
                'id' => $source['id'],
                'name' => $source['name'],
                'type' => $source['type'],
                'price' => $source['price'],
                'game' => $source['game']['name'] ?? null,
                'image' => $source['image'] ?? null,
                'highlighted' => $highlight['name'][0] ?? $source['name'],
                'url' => '/shop/product/' . $source['id'],
                'score' => $hit['_score']
            ];
        }

        return $results;
    }

    /**
     * Recherche locale avancée avec scoring intelligent
     */
    private function searchLocal(string $query, string $type, int $limit): array
    {
        $qb = $this->em->getRepository(ShopProduct::class)->createQueryBuilder('p')
            ->leftJoin('p.game', 'g')
            ->leftJoin('p.sizes', 's')
            ->addSelect('g', 's')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        // Recherche multi-champs avec similarité
        if ($query) {
            $qb->andWhere('(
                LOWER(p.name) LIKE LOWER(:exactQuery) OR           -- Exact match (boost 3x)
                LOWER(p.name) LIKE LOWER(:startQuery) OR           -- Starts with (boost 2x)  
                LOWER(p.name) LIKE LOWER(:fuzzyQuery1) OR        -- Fuzzy 1 char
                LOWER(p.name) LIKE LOWER(:fuzzyQuery2) OR        -- Fuzzy 2 chars
                LOWER(p.name) LIKE LOWER(:containsQuery) OR        -- Contains anywhere
                LOWER(g.name) LIKE LOWER(:query) OR               -- Game name
                LOWER(s.name) LIKE LOWER(:query)                     -- Size name
            )')
            ->setParameter('exactQuery', $query)
            ->setParameter('startQuery', $query . '%')
            ->setParameter('fuzzyQuery1', substr($query, 0, -1) . '_' . substr($query, -1) . '%')
            ->setParameter('fuzzyQuery2', substr($query, 0, -2) . '_' . substr($query, -2) . '%')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('containsQuery', '%' . $query . '%');
        }

        // Filtrer par type
        if ($type && in_array($type, ['merch', 'skin'])) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        // Scoring intelligent basé sur la pertinence
        $qb->orderBy('
            CASE 
                WHEN LOWER(p.name) LIKE LOWER(:exactQuery) THEN 100        -- Exact match
                WHEN LOWER(p.name) LIKE LOWER(:startQuery) THEN 80         -- Starts with
                WHEN LOWER(p.name) LIKE LOWER(:containsQuery) THEN 40      -- Contains
                WHEN LOWER(g.name) LIKE LOWER(:query) THEN 30           -- Game name match
                ELSE 10
            END', 'DESC')
        ->setParameter('exactQuery', $query)
        ->setParameter('startQuery', $query . '%')
        ->setParameter('query', $query)
        ->setParameter('containsQuery', '%' . $query . '%');

        $products = $qb->setMaxResults($limit)
                     ->getQuery()
                     ->getResult();

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'type' => $product->getType(),
                'price' => $product->getPrice(),
                'game' => $product->getGame() ? $product->getGame()->getName() : null,
                'image' => $product->getImage(),
                'highlighted' => $this->highlightText($product->getName(), $query),
                'url' => '/shop/product/' . $product->getId(),
                'score' => $this->calculateLocalScore($product->getName(), $query)
            ];
        }

        return $results;
    }

    /**
     * Calcule le score de pertinence pour la recherche locale
     */
    private function calculateLocalScore(string $productName, string $query): float
    {
        $productName = strtolower($productName);
        $query = strtolower($query);
        
        // Exact match
        if ($productName === $query) return 100.0;
        
        // Starts with
        if (str_starts_with($productName, $query)) return 80.0;
        
        // Contains
        if (str_contains($productName, $query)) return 40.0;
        
        // Levenshtein distance (similarité)
        $distance = levenshtein($productName, $query);
        $maxLength = max(strlen($productName), strlen($query));
        $similarity = 1 - ($distance / $maxLength);
        
        if ($similarity > 0.7) return 60.0;
        if ($similarity > 0.5) return 30.0;
        
        return 10.0;
    }

    /**
     * Suggestions de recherche
     */
    public function getSuggestions(string $query): array
    {
        $suggestions = [];

        // Suggestions de produits
        $products = $this->em->getRepository(ShopProduct::class)
            ->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->andWhere('LOWER(p.name) LIKE LOWER(:query)')
            ->setParameter('active', true)
            ->setParameter('query', $query . '%')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $suggestions[] = [
                'type' => 'product',
                'text' => $product->getName(),
                'subtitle' => ucfirst($product->getType()) . ' - ' . $product->getPrice() . '€',
                'url' => '/shop/product/' . $product->getId()
            ];
        }

        // Suggestions de jeux
        $games = $this->em->getRepository(Game::class)
            ->createQueryBuilder('g')
            ->where('LOWER(g.name) LIKE LOWER(:query)')
            ->setParameter('query', $query . '%')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        foreach ($games as $game) {
            $suggestions[] = [
                'type' => 'game',
                'text' => $game->getName(),
                'subtitle' => 'Voir tous les produits',
                'url' => '/shop/merch?game=' . $game->getId()
            ];
        }

        return $suggestions;
    }

    /**
     * Met en évidence le texte recherché
     */
    private function highlightText(string $text, string $query): string
    {
        if (empty($query)) {
            return $text;
        }

        $pattern = '/' . preg_quote($query, '/') . '/i';
        return preg_replace($pattern, '<mark>$0</mark>', $text);
    }

    /**
     * Indexer les produits dans Elasticsearch
     */
    public function indexProducts(): void
    {
        try {
            // Créer l'index s'il n'existe pas
            $indexParams = [
                'index' => 'shop_products',
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'text', 'analyzer' => 'standard'],
                            'type' => ['type' => 'keyword'],
                            'price' => ['type' => 'float'],
                            'image' => ['type' => 'keyword'],
                            'isActive' => ['type' => 'boolean'],
                            'game' => [
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'text', 'analyzer' => 'standard']
                                ]
                            ],
                            'sizes' => [
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'keyword']
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            try {
                $this->elasticsearchClient->indices()->create($indexParams);
            } catch (\Exception $e) {
                // L'index existe déjà, continuer
            }

            // Récupérer tous les produits actifs
            $products = $this->em->getRepository(ShopProduct::class)
                ->createQueryBuilder('p')
                ->leftJoin('p.game', 'g')
                ->leftJoin('p.sizes', 's')
                ->addSelect('g', 's')
                ->where('p.isActive = :active')
                ->setParameter('active', true)
                ->getQuery()
                ->getResult();

            // Préparer les documents pour l'indexation en bulk
            $params = ['body' => []];
            
            foreach ($products as $product) {
                $params['body'][] = [
                    'index' => [
                        '_index' => 'shop_products',
                        '_id' => $product->getId()
                    ]
                ];
                
                $doc = [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'type' => $product->getType(),
                    'price' => $product->getPrice(),
                    'image' => $product->getImage(),
                    'isActive' => $product->isActive(),
                    'game' => $product->getGame() ? [
                        'id' => $product->getGame()->getId(),
                        'name' => $product->getGame()->getName()
                    ] : null,
                    'sizes' => $product->getSizes()->map(function($size) {
                        return [
                            'id' => $size->getId(),
                            'name' => $size->getName()
                        ];
                    })->toArray()
                ];
                
                $params['body'][] = $doc;
            }

            // Indexer en bulk
            if (!empty($params['body'])) {
                $responses = $this->elasticsearchClient->bulk($params);
                
                // Vérifier les erreurs
                if (isset($responses['errors']) && $responses['errors']) {
                    throw new \Exception('Erreur lors de l\'indexation bulk');
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'indexation Elasticsearch: ' . $e->getMessage());
        }
    }
}
