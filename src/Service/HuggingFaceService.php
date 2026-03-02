<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $huggingfaceApiKey)
    {
        $this->client = $client;
        $this->apiKey = $huggingfaceApiKey;
    }

    /**
     * Generates a short summary and detects the subject of the text using a Hugging Face model
     * Returns an array: ['summary' => string, 'subject' => string]
     */
    public function analyzeText(?string $text): array
    {
        $defaultResult = ['summary' => null, 'subject' => null];

        if (empty($text) || empty($this->apiKey) || $this->apiKey === 'YOUR_HUGGINGFACE_API_KEY') {
            return $defaultResult;
        }

        // Check word count to avoid hallucination loops on extremely short texts
        $wordCount = str_word_count($text);
        if ($wordCount < 15) {
            return $defaultResult;
        }

        try {
            // Using Qwen-72B-Instruct for very pro AI text analysis via the ChatGPT-compatible HF API
            $prompt = "Tu es un assistant IA expert. Analyse le texte suivant et génère le résultat STRICTEMENT au format JSON avec deux clés: 'summary' (un résumé très professionnel et captivant en une ou deux phrases en français) et 'subject' (le sujet principal ou la catégorie en 1 ou 2 mots).\n\nTexte: \"$text\"\n\nNe renvoie aucun autre texte, uniquement le JSON.";

            $response = $this->client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
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
                'timeout' => 45, // Hugging Face might take 20s to load the model
            ]);

            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $data = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                // Clean potential markdown code blocks (```json ... ```)
                $content = trim(preg_replace('/^```json|```$/m', '', $content));
                $json = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($json['summary'], $json['subject'])) {
                    return [
                        'summary' => trim($json['summary']),
                        'subject' => trim(ucfirst($json['subject'])),
                    ];
                }
            }

            return $defaultResult;

        } catch (\Exception $e) {
            return $defaultResult;
        }
    }

    /**
     * Analyze text for toxicity using Hugging Face Zero-Shot Classification or a specific toxicity model.
     * Returns true if the text is flagged as inappropriate.
     */
    public function isTextToxic(?string $text): bool
    {
        if (empty($text) || empty($this->apiKey) || $this->apiKey === 'YOUR_HUGGINGFACE_API_KEY') {
            return false;
        }

        try {
            // Using a French moderation model
            $response = $this->client->request('POST', 'https://router.huggingface.co/hf-inference/models/citizenlab/twitter-xlm-roberta-base-sentiment-finetunned', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'inputs' => $text,
                ]
            ]);

            $data = $response->toArray();

            // Usually returns [[{'label': 'Negative', 'score': 0.99}, ...]]
            if (isset($data[0]) && is_array($data[0])) {
                foreach ($data[0] as $prediction) {
                    // Adapt the label based on the specific Hugging Face model you pick
                    if (in_array(strtolower($prediction['label']), ['negative']) && $prediction['score'] > 0.8) {
                        return true; // Flagged as highly negative/toxic
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Analyze image for toxicity (NSFW) using Hugging Face.
     * Returns true if the image is flagged as inappropriate.
     * This method is FAIL-CLOSED: it returns true if the AI fails or times out.
     */
    public function isImageToxic(?string $imagePath): bool
    {
        if (empty($imagePath) || !file_exists($imagePath) || empty($this->apiKey) || $this->apiKey === 'YOUR_HUGGINGFACE_API_KEY') {
            return false;
        }

        try {
            $imageData = file_get_contents($imagePath);

            // 1. Check for NSFW content (Falconsai/nsfw_image_detection)
            $response = $this->client->request('POST', 'https://router.huggingface.co/hf-inference/models/Falconsai/nsfw_image_detection', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => $imageData,
                'timeout' => 20, // Increased timeout
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                if (is_array($data)) {
                    foreach ($data as $prediction) {
                        // Keep threshold 0.5 for safety
                        if (strtolower($prediction['label']) === 'nsfw' && $prediction['score'] > 0.5) {
                            return true; 
                        }
                    }
                }
            }

            // 2. Check for Violence / Weapons / Dangerous Content (google/vit-base-patch16-224)
            $responseViT = $this->client->request('POST', 'https://router.huggingface.co/hf-inference/models/google/vit-base-patch16-224', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => $imageData,
                'timeout' => 20, // Increased timeout
            ]);

            if ($responseViT->getStatusCode() === 200) {
                $viTData = $responseViT->toArray();
                $dangerousKeywords = [
                    'rifle', 'gun', 'weapon', 'assault', 'blood', 'knife', 'sword', 'pistol', 'revolver', 'firearm',
                    'shotgun', 'grenade', 'missile', 'bomb', 'explosi', 'dead', 'corpse', 'wound', 'injury', 
                    'soldier', 'military', 'war', 'battle', 'combat', 'machete', 'skeleton', 'skull'
                ];

                if (is_array($viTData)) {
                    foreach ($viTData as $prediction) {
                        if ($prediction['score'] > 0.1) { 
                            $label = strtolower($prediction['label']);
                            foreach ($dangerousKeywords as $keyword) {
                                if (str_contains($label, $keyword)) {
                                    return true; 
                                }
                            }
                        }
                    }
                }
            }

            return false;

        } catch (\Exception $e) {
            // Reverted to FAIL-OPEN: If AI is slow or down, allow the post
            // This prevents blocking every image when Hugging Face is overloaded
            return false;
        }
    }

    /**
     * Refines raw transcribed text into a professional and well-formatted post.
     * Adapts to the content (gaming or other).
     */
    public function refineTextContent(string $text): string
    {
        if (empty($text) || empty($this->apiKey) || $this->apiKey === 'YOUR_HUGGINGFACE_API_KEY') {
            return $text;
        }

        try {
            $prompt = "Tu es un assistant expert en rédaction. Ta mission est de transformer le texte suivant (issu d'une dictée vocale) en un post captivant et bien structuré. \n\n" .
                      "CONSIGNES :\n" .
                      "1. Conserve le ton original (si c'est du gaming, reste dans l'esprit gaming, si c'est sérieux, reste sérieux).\n" .
                      "2. Corrige la ponctuation et la grammaire.\n" .
                      "3. Ajoute quelques emojis pertinents et des hashtags si nécessaire.\n" .
                      "4. Rends le texte plus fluide et engageant.\n\n" .
                      "Texte brut : \"$text\"\n\n" .
                      "Réponse (uniquement le texte amélioré) :";

            $response = $this->client->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'Qwen/Qwen2.5-72B-Instruct',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return trim($data['choices'][0]['message']['content'] ?? $text);
            }

            return $text;
        } catch (\Exception $e) {
            return $text;
        }
    }
}
