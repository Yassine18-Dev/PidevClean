<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$apiKey = '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';

$text = "League of Legends est un jeu de stratégie en équipe où deux équipes de cinq champions puissants s'affrontent pour détruire la base adverse. Choisissez parmi plus de 140 champions, réalisez des actions épiques, tuez vos ennemis et abattez les tourelles pour décrocher la victoire.";

echo "Testing text analysis...\n";
$prompt = "Tu es un assistant IA expert. Analyse le texte suivant et génère le résultat STRICTEMENT au format JSON avec deux clés: 'summary' (un résumé très professionnel et captivant en une ou deux phrases en français) et 'subject' (le sujet principal ou la catégorie en 1 ou 2 mots).\n\nTexte: \"$text\"\n\nNe renvoie aucun autre texte, uniquement le JSON.";

try {
    $response = $client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'Qwen/Qwen2.5-72B-Instruct',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 250,
            'temperature' => 0.1,
        ],
        'timeout' => 45,
    ]);

    $statusCode = $response->getStatusCode();
    echo "Status Code: $statusCode\n";

    if ($statusCode === 200) {
        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? '';
        echo "Response Content: $content\n";
        
        $content = trim(preg_replace('/^```json|```$/m', '', $content));
        $json = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            print_r($json);
        } else {
            echo "JSON Decode Error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "Error: " . $response->getContent(false) . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
