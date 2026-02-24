<?php

require_once 'vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;

// Test direct Elasticsearch
echo "Test direct Elasticsearch...\n";

try {
    $client = ClientBuilder::create()
        ->setHosts(['http://localhost:9200'])
        ->build();
    
    // Test ping
    $ping = $client->ping();
    echo "Ping Elasticsearch: " . ($ping ? "OK" : "FAIL") . "\n";
    
    // Test recherche simple
    $params = [
        'index' => 'shop_products',
        'body' => [
            'query' => [
                'wildcard' => [
                    'name' => '*valo*'
                ]
            ]
        ]
    ];
    
    $response = $client->search($params);
    
    echo "Résultats trouvés: " . $response['hits']['total']['value'] . "\n";
    
    foreach ($response['hits']['hits'] as $hit) {
        echo "- ID: {$hit['_source']['id']}, Nom: {$hit['_source']['name']}\n";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
