<?php

namespace App\Service;

use App\Entity\Post;
use Symfony\Component\HttpFoundation\File\File;

class AIPostManager
{
    private HuggingFaceService $huggingFaceService;
    private string $uploadsDirectory;

    public function __construct(
        HuggingFaceService $huggingFaceService,
        string $uploadsDirectory
    ) {
        $this->huggingFaceService = $huggingFaceService;
        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * Orchestrates AI services to analyze and process a single post.
     */
    public function processPost(Post $post): void
    {
        $isToxic = false;

        // 1. Check Text Toxicity using Hugging Face
        if ($post->getContent()) {
            if ($this->huggingFaceService->isTextToxic($post->getContent())) {
                $isToxic = true;
            }
        }

        // 2. Check Image Toxicity (if any)
        if (!$isToxic && $post->getImages()) {
            foreach ($post->getImages() as $image) {
                // Assuming $image->getFilename() holds just the name
                $imagePath = $this->uploadsDirectory . '/' . $image->getFilename();
                
                if (file_exists($imagePath)) {
                    if ($this->huggingFaceService->isImageToxic($imagePath)) {
                        $isToxic = true;
                        break; // One toxic image is enough to flag the post
                    }
                }
            }
        }

        // 3. Act on Toxicity or Generate Summary
        if ($isToxic) {
            $post->setStatus('PENDING'); // Require admin validation
        } else {
            $post->setStatus('APPROVED');
            
            // 4. Generate Summary and Subject (Silently fail if AI is slow/down)
            try {
                $analysis = $this->huggingFaceService->analyzeText($post->getContent());
                if (!empty($analysis['summary'])) {
                    $post->setSummary($analysis['summary']);
                }
                if (!empty($analysis['subject'])) {
                    $post->setSubject($analysis['subject']);
                }
            } catch (\Exception $e) {
                // Keep moving even if analysis fails
            }
        }
    }
}
