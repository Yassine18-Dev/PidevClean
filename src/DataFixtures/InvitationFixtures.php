<?php

namespace App\DataFixtures;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InvitationFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. CAPTAINS
        $c1 = $this->createUser($manager, 'captain1@test.tn', 'captain1', 'CAPTAIN', ['ROLE_CAPTAIN']);
        $c2 = $this->createUser($manager, 'captain2@test.tn', 'captain2', 'CAPTAIN', ['ROLE_CAPTAIN']);

        // 2. TEAMS
        $t1 = $this->createTeam($manager, 'Team Alpha', $c1, 'lol');
        $t2 = $this->createTeam($manager, 'Team Beta', $c2, 'valorant');

        // 3. FREE PLAYERS
        $p1 = $this->createUser($manager, 'player1@test.tn', 'player1', 'PLAYER', ['ROLE_PLAYER']);
        $p2 = $this->createUser($manager, 'player2@test.tn', 'player2', 'PLAYER', ['ROLE_PLAYER']);

        // 4. TEST TEAM FULL (Team Alpha needs 4 more players)
        for ($i = 3; $i <= 6; $i++) {
            $px = $this->createUser($manager, "bot$i@test.tn", "bot$i", 'PLAYER', ['ROLE_PLAYER']);
            $px->getPlayer()->setTeam($t1);
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager, string $email, string $username, string $roleType, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoleType($roleType);
        $user->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, 'Test123!'));

        $player = new Player();
        $player->setUser($user);
        $player->setNickname($username);
        
        // Ensure bidirectional link for immediate use
        $user->setPlayer($player);
        
        $manager->persist($user);
        $manager->persist($player);

        return $user;
    }

    private function createTeam(ObjectManager $manager, string $name, User $owner, string $game): Team
    {
        $team = new Team();
        $team->setName($name);
        $team->setOwner($owner);
        $team->setGame($game);
        $team->setMaxPlayers(5);
        $team->setSlug(strtolower(str_replace(' ', '-', $name)));

        $manager->persist($team);

        // Assign captain to his team roster
        if ($owner->getPlayer()) {
            $owner->getPlayer()->setTeam($team);
        }

        return $team;
    }
}
