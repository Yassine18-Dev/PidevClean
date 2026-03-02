<?php

namespace App\Tests\Service;

use App\Entity\Post;
use App\Service\AIPostManager;
use App\Service\HuggingFaceService;
use PHPUnit\Framework\TestCase;

class AIPostManagerTest extends TestCase
{
    private $huggingFaceService;
    private $aiPostManager;
    private $uploadsDirectory = '/tmp/uploads';

    protected function setUp(): void
    {
        $this->huggingFaceService = $this->createMock(HuggingFaceService::class);
        $this->aiPostManager = new AIPostManager(
            $this->huggingFaceService,
            $this->uploadsDirectory
        );
    }

    public function testProcessPostApproved(): void
    {
        $post = new Post();
        $post->setContent('Clean content');

        $this->huggingFaceService->method('isTextToxic')->willReturn(false);
        $this->huggingFaceService->method('analyzeText')->willReturn([
            'summary' => 'This is a summary',
            'subject' => 'Clean'
        ]);

        $this->aiPostManager->processPost($post);

        $this->assertEquals('APPROVED', $post->getStatus());
        $this->assertEquals('This is a summary', $post->getSummary());
        $this->assertEquals('Clean', $post->getSubject());
    }

    public function testProcessPostToxic(): void
    {
        $post = new Post();
        $post->setContent('Toxic content');

        $this->huggingFaceService->method('isTextToxic')->willReturn(true);

        $this->aiPostManager->processPost($post);

        $this->assertEquals('PENDING', $post->getStatus());
        $this->assertNull($post->getSummary());
    }
}
