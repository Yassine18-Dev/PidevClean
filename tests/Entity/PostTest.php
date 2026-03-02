<?php

namespace App\Tests\Entity;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\PostImage;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class PostTest extends TestCase
{
    /**
     * Test logic: Ensure a new Post is initialized with correct default values.
     */
    public function testInitialState(): void
    {
        $post = new Post();

        $this->assertNull($post->getId());
        $this->assertNull($post->getAuthor());
        $this->assertNull($post->getContent());
        $this->assertNull($post->getCreatedAt());
        $this->assertEquals('APPROVED', $post->getStatus());
        
        $this->assertInstanceOf(Collection::class, $post->getImages());
        $this->assertCount(0, $post->getImages());

        $this->assertInstanceOf(Collection::class, $post->getLikes());
        $this->assertCount(0, $post->getLikes());

        $this->assertInstanceOf(Collection::class, $post->getComments());
        $this->assertCount(0, $post->getComments());

        $this->assertNull($post->getSummary());
        $this->assertNull($post->getSubject());
    }

    /**
     * Test logic: Verify basic getter/setter for content and author.
     */
    public function testPostBasicProperties(): void
    {
        $post = new Post();
        $author = new User();
        $author->setUsername('testuser');
        $author->setEmail('test@example.com');

        $now = new \DateTime();
        $content = "This is a detailed test content for the post.";

        $post->setContent($content);
        $post->setAuthor($author);
        $post->setCreatedAt($now);
        $post->setStatus('PENDING');

        $this->assertEquals($content, $post->getContent());
        $this->assertSame($author, $post->getAuthor());
        $this->assertEquals($now, $post->getCreatedAt());
        $this->assertEquals('PENDING', $post->getStatus());
    }

    /**
     * Test logic: Verify adding images and ensuring bi-directional relationship works.
     */
    public function testImageCollection(): void
    {
        $post = new Post();
        $image1 = new PostImage();
        $image1->setFilename('image1.jpg');
        
        $image2 = new PostImage();
        $image2->setFilename('image2.png');

        $post->addImage($image1);
        $post->addImage($image2);

        $this->assertCount(2, $post->getImages());
        $this->assertTrue($post->getImages()->contains($image1));
        $this->assertTrue($post->getImages()->contains($image2));

        // Test bi-directional relationship (addImage should call setPost)
        $this->assertSame($post, $image1->getPost());
        $this->assertSame($post, $image2->getPost());

        // Test adding the same image twice (should not add it twice)
        $post->addImage($image1);
        $this->assertCount(2, $post->getImages());
    }

    /**
     * Test logic: Verify management of likes (ManyToMany with User).
     */
    public function testLikeCollection(): void
    {
        $post = new Post();
        $user1 = new User();
        $user1->setUsername('liker1');
        
        $user2 = new User();
        $user2->setUsername('liker2');

        $post->addLike($user1);
        $post->addLike($user2);

        $this->assertCount(2, $post->getLikes());
        $this->assertTrue($post->getLikes()->contains($user1));
        $this->assertTrue($post->getLikes()->contains($user2));

        // Adding same user twice should not change count (based on addLike logic)
        $post->addLike($user1);
        $this->assertCount(2, $post->getLikes());
    }

    /**
     * Test logic: Verify AI analysis fields.
     */
    public function testAIAnalysisFields(): void
    {
        $post = new Post();
        $summary = "AI generated summary of the gaming post.";
        $subject = "Gaming/Strategy";

        $post->setSummary($summary);
        $post->setSubject($subject);

        $this->assertEquals($summary, $post->getSummary());
        $this->assertEquals($subject, $post->getSubject());

        // Test nullable
        $post->setSummary(null);
        $post->setSubject(null);
        $this->assertNull($post->getSummary());
        $this->assertNull($post->getSubject());
    }

    /**
     * Test logic: Fluent interface check (setters returning $this).
     */
    public function testFluentInterface(): void
    {
        $post = new Post();
        $this->assertSame($post, $post->setContent('Content'));
        $this->assertSame($post, $post->setStatus('PUBLISHED'));
        $this->assertSame($post, $post->setSummary('Summary'));
        $this->assertSame($post, $post->setSubject('Subject'));
        $this->assertSame($post, $post->setCreatedAt(new \DateTime()));
        $this->assertSame($post, $post->setAuthor(new User()));
    }
}
