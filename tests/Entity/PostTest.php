<?php

namespace App\Tests\Entity;

use App\Entity\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    public function testSetAndGetTitleAndContent(): void
    {
        $post = new Post();
        $post->setTitle('Hello World');
        $post->setContent('This is the post body.');

        $this->assertSame('Hello World', $post->getTitle());
        $this->assertSame('This is the post body.', $post->getContent());
    }

    public function testModerationFlagDefaults(): void
    {
        $post = new Post();

        $this->assertFalse($post->isFlagged());
        $this->assertSame('approved', $post->getModerationStatus());
        $this->assertNull($post->getFlagReason());

        $post->setIsFlagged(true);
        $post->setFlagReason('Spam content');
        $post->setModerationStatus('rejected');

        $this->assertTrue($post->isFlagged());
        $this->assertSame('Spam content', $post->getFlagReason());
        $this->assertSame('rejected', $post->getModerationStatus());
    }
}
