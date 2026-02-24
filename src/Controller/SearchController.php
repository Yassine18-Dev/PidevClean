<?php

namespace App\Controller;

use App\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    #[Route('/search', name: 'search_api', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $limit = (int) $request->query->get('limit', 10);
        $showSource = $request->query->getBoolean('show_source', false); // Paramètre pour voir la source

        if (strlen($query) < 2) {
            return $this->json([
                'success' => false,
                'message' => 'La recherche doit contenir au moins 2 caractères',
                'results' => []
            ]);
        }

        try {
            $results = $this->searchService->search($query, $type, $limit);
            
            // Compter les sources
            $sources = array_count_values(array_column($results, 'source'));
            $debugInfo = [
                'elasticsearch_count' => $sources['elasticsearch'] ?? 0,
                'local_count' => $sources['local'] ?? 0,
                'total_count' => count($results)
            ];
            
            // Préparer la réponse
            $response = [
                'success' => true,
                'query' => $query,
                'type' => $type,
                'count' => count($results),
                'results' => $results
            ];
            
            // Ajouter les infos de debug si demandé ou si admin
            if ($showSource || $this->isGranted('ROLE_ADMIN')) {
                $response['debug'] = $debugInfo;
            }
            
            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'results' => [],
                'debug' => ['error' => $e->getMessage()]
            ], 500);
        }
    }

    #[Route('/search/suggestions', name: 'search_suggestions_api', methods: ['GET'])]
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json(['suggestions' => []]);
        }

        try {
            $suggestions = $this->searchService->getSuggestions($query);
            
            return $this->json([
                'success' => true,
                'query' => $query,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return $this->json(['suggestions' => []]);
        }
    }

    #[Route('/search/index', name: 'search_index', methods: ['POST'])]
    public function indexProducts(): JsonResponse
    {
        try {
            $this->searchService->indexProducts();
            
            return $this->json([
                'success' => true,
                'message' => 'Produits indexés avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'indexation: ' . $e->getMessage()
            ], 500);
        }
    }
}
