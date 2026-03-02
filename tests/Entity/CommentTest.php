<?php

namespace App\Tests\Entity;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testCommentCreation(): void
    {
        $comment = new Comment();
        $author = new User();
        $post = new Post();

        $comment->setContent('Nice post!');
        $comment->setAuthor($author);
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTime());

        $this->assertEquals('Nice post!', $comment->getContent());
        $this->assertEquals($author, $comment->getAuthor());
        $this->assertEquals($post, $comment->getPost());
    }

    public function testNestedComment(): void
    {
        $parent = new Comment();
        $child = new Comment();
        $child->setParent($parent);

        $this->assertEquals($parent, $child->getParent());
    }
}
