<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$apiKey = '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';

$model = 'Falconsai/nsfw_image_detection';
$url = 'https://router.huggingface.co/hf-inference/models/' . $model;

// Download a random image
file_put_contents('test.jpg', file_get_contents('https://picsum.photos/200'));
$image = file_get_contents('test.jpg');

echo "Testing model $model...\n";

try {
    $response = $client->request('POST', $url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/octet-stream',
        ],
        'body' => $image,
        'timeout' => 15,
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
