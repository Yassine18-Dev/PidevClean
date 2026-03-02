<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setUsername('testuser');
        $user->setRoleType('PLAYER');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('PLAYER', $user->getRoleType());
        $this->assertEquals(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserStatusTransitions(): void
    {
        $user = new User();
        $user->setStatus(User::STATUS_SUSPENDED);
        $this->assertEquals(User::STATUS_SUSPENDED, $user->getStatus());

        $user->setStatus(User::STATUS_BANNED);
        $this->assertEquals(User::STATUS_BANNED, $user->getStatus());
    }

    public function testResetToken(): void
    {
        $user = new User();
        $user->setResetToken('token123');
        $user->setResetExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->assertEquals('token123', $user->getResetToken());
        $this->assertTrue($user->isResetTokenValid('token123'));
        $this->assertFalse($user->isResetTokenValid('wrongtoken'));
    }
}
