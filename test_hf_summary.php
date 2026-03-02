<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$apiKey = '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';
$text = "Salut à tous ! Pour célébrer les 1 an de notre équipe, on organise un enorme tournoi sur League of Legends le week-end prochain. Il y aura des cashprizes à gagner pour le top 3 et on diffusera la finale en direct sur Twitch avec des casters pros. Les inscriptions se clôturent mercredi, alors dépêchez-vous de monter votre roster et de vous inscrire via le lien sur notre profil ! Hâte de vous y retrouver.";

$models = [
    'facebook/bart-large-cnn',
    'plguillou/t5-base-fr-sum-cnndm' // Modèle français
];

foreach ($models as $model) {
    echo "Testing summarization model $model...\n";
    $url = 'https://router.huggingface.co/hf-inference/models/' . $model;

    try {
        $response = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inputs' => $text,
                'parameters' => [
                    'max_length' => 60,
                ]
            ],
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        echo "Status Code: $statusCode\n";

        if ($statusCode === 200) {
            $data = $response->toArray();
            print_r($data);
        } else {
            echo "Error: " . $response->getContent(false) . "\n";
        }
    } catch (\Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    echo "--------------------------\n";
}
