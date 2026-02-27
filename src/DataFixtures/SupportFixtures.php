<?php

namespace App\DataFixtures;

use App\Entity\Ticket;
use App\Entity\TicketCategory;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SupportFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // — Categories —
        $categories = [
            ['name' => 'Account', 'icon' => '🔐', 'color' => '#3b82f6', 'desc' => 'Login, password, profile issues'],
            ['name' => 'Payment', 'icon' => '💳', 'color' => '#f59e0b', 'desc' => 'Billing, refunds, transactions'],
            ['name' => 'Technical', 'icon' => '🛠️', 'color' => '#ef4444', 'desc' => 'Performance, bugs, compatibility'],
            ['name' => 'Bug Report', 'icon' => '🐛', 'color' => '#dc2626', 'desc' => 'Software bugs and glitches'],
            ['name' => 'Tournament', 'icon' => '🏆', 'color' => '#10b981', 'desc' => 'Match disputes, registrations'],
            ['name' => 'General', 'icon' => '📁', 'color' => '#a855f7', 'desc' => 'Other questions and feedback'],
        ];

        $catEntities = [];
        foreach ($categories as $c) {
            $cat = new TicketCategory();
            $cat->setName($c['name'])
                ->setIcon($c['icon'])
                ->setColor($c['color'])
                ->setDescription($c['desc']);
            $manager->persist($cat);
            $catEntities[$c['name']] = $cat;
        }

        // — Get existing users (or create mock ones) —
        $userRepo = $manager->getRepository(User::class);
        $users = $userRepo->findAll();

        if (count($users) < 2) {
            // Create mock users if not enough exist
            for ($i = 1; $i <= 3; $i++) {
                $u = new User();
                $u->setEmail("support_user{$i}@arena.gg")
                    ->setUsername("SupportUser{$i}")
                    ->setPassword('$2y$13$' . str_repeat('x', 53))
                    ->setRoles(['ROLE_USER']);
                $manager->persist($u);
                $users[] = $u;
            }
        }

        // — Tickets —
        $tickets = [
            ['subject' => 'Cannot login to my account after password reset', 'desc' => 'I requested a password reset yesterday but the link expired. Now I cannot login with either the old or new password. My email is correct. Please help me regain access to my account urgently!', 'cat' => 'Account', 'status' => 'pending', 'priority' => 'high', 'days' => 1],
            ['subject' => 'Payment declined during checkout', 'desc' => 'I tried to purchase the pro gaming headset from the shop but my payment was declined. My card works fine on other sites. The error message says "Transaction failed, please try again." I have tried 3 times.', 'cat' => 'Payment', 'status' => 'in_progress', 'priority' => 'high', 'days' => 2],
            ['subject' => 'Game crashes when joining tournament lobby', 'desc' => 'Every time I try to join the weekly tournament lobby, the game client crashes with error code 0x4F2A. This has been happening since the last update. My system meets all requirements. I have tried reinstalling.', 'cat' => 'Technical', 'status' => 'pending', 'priority' => 'critical', 'days' => 1],
            ['subject' => 'Refund request for duplicate order', 'desc' => 'I accidentally placed two orders for the same item (Order #1042 and #1043). Could you please cancel one and process a refund? Both orders show as confirmed.', 'cat' => 'Payment', 'status' => 'resolved', 'priority' => 'medium', 'days' => 5],
            ['subject' => 'Team roster not updating after changes', 'desc' => 'I am the captain of team "Shadow Legends" and I removed a player and added a new one, but the roster still shows the old lineup on the public page. It has been 24 hours.', 'cat' => 'Bug Report', 'status' => 'in_progress', 'priority' => 'medium', 'days' => 3],
            ['subject' => 'How to change my username?', 'desc' => 'I want to change my display name on the platform. I looked in profile settings but could not find the option. Can you guide me on how to update my username?', 'cat' => 'General', 'status' => 'resolved', 'priority' => 'low', 'days' => 7],
            ['subject' => 'URGENT: Account hacked and items stolen!!!', 'desc' => 'Someone gained access to my account and transferred all my inventory items! I noticed unauthorized login from a different IP. My email was also changed. This is a SECURITY BREACH! Please investigate immediately and restore my items!', 'cat' => 'Account', 'status' => 'pending', 'priority' => 'critical', 'days' => 0],
            ['subject' => 'Tournament registration deadline extension request', 'desc' => 'Our team "Nexus Gaming" missed the registration deadline for the Spring Championship by 2 hours due to timezone confusion. Is there any way to get a late registration? We have 5 members ready.', 'cat' => 'Tournament', 'status' => 'rejected', 'priority' => 'low', 'days' => 10],
            ['subject' => 'Slow loading times on match history page', 'desc' => 'The match history page takes over 30 seconds to load. Other pages work fine. I have a good internet connection. This issue started about a week ago. Very frustrating when trying to review past matches.', 'cat' => 'Technical', 'status' => 'pending', 'priority' => 'medium', 'days' => 4],
            ['subject' => 'Shop item shows wrong price', 'desc' => 'The limited edition team jersey shows $29.99 on the listing page but changes to $49.99 at checkout. This seems like a pricing error or a glitch in the system.', 'cat' => 'Bug Report', 'status' => 'pending', 'priority' => 'high', 'days' => 2],
            ['subject' => 'Feature request: Dark mode for mobile app', 'desc' => 'It would be great to have a dark mode option in the mobile application. The current bright theme is hard on the eyes during night gaming sessions. Many users in our community have been requesting this.', 'cat' => 'General', 'status' => 'resolved', 'priority' => 'low', 'days' => 15],
            ['subject' => 'Cannot upload team logo - file size error', 'desc' => 'When I try to upload our team logo (a 2MB PNG file), I get an error saying the file is too large. The help center says the limit is 5MB. There seems to be a bug with the upload validation.', 'cat' => 'Bug Report', 'status' => 'in_progress', 'priority' => 'medium', 'days' => 3],
            ['subject' => 'Missing prize money from last tournament', 'desc' => 'Our team won 2nd place in the Winter Cup two weeks ago, but we haven\'t received the prize money yet. The tournament rules state payment within 7 business days. Order reference: PRIZE-2026-0047.', 'cat' => 'Payment', 'status' => 'pending', 'priority' => 'high', 'days' => 1],
            ['subject' => 'Profile stats not syncing correctly', 'desc' => 'My win/loss ratio on my profile shows 45% but according to my match history I have won 28 out of 50 games which should be 56%. The stats seem outdated or calculated wrong.', 'cat' => 'Technical', 'status' => 'resolved', 'priority' => 'low', 'days' => 8],
            ['subject' => 'Account suspended without explanation', 'desc' => 'I logged in today and found my account suspended. I have not violated any terms of service. No email notification was sent. I have been a member for 2 years with no prior issues. Please review and restore my account.', 'cat' => 'Account', 'status' => 'in_progress', 'priority' => 'high', 'days' => 1],
            ['subject' => 'Stream integration not working', 'desc' => 'I connected my Twitch account to the platform but the stream widget on my profile shows "Stream Offline" even when I am live. I have re-linked the account twice already.', 'cat' => 'Technical', 'status' => 'pending', 'priority' => 'medium', 'days' => 5],
            ['subject' => 'Request to merge two accounts', 'desc' => 'I accidentally created two accounts with different emails. I would like to merge gamer1@email.com and gamer1_backup@email.com into one account, keeping the progress from both.', 'cat' => 'Account', 'status' => 'pending', 'priority' => 'medium', 'days' => 6],
            ['subject' => 'Tournament match result disputed', 'desc' => 'In Match #8842 of the Summer League, the result was recorded as a loss for our team but we clearly won 16-12. We have screenshots and VOD proof. Please review the match recording and correct the result.', 'cat' => 'Tournament', 'status' => 'in_progress', 'priority' => 'high', 'days' => 2],
            ['subject' => 'API rate limiting too restrictive', 'desc' => 'As a verified developer using the ArenaMind API, I am hitting rate limits after only 50 requests per minute. The documentation says the limit should be 200/min for verified accounts. Can this be checked?', 'cat' => 'Technical', 'status' => 'pending', 'priority' => 'medium', 'days' => 3],
            ['subject' => 'Thank you for the great support!', 'desc' => 'Just wanted to say that the support team has been amazing. My previous issue with the shop was resolved within an hour. Keep up the great work! Love the platform.', 'cat' => 'General', 'status' => 'resolved', 'priority' => 'low', 'days' => 12],
            ['subject' => 'DDoS attack on tournament server!!!', 'desc' => 'Our tournament match is being DDoSed! All 10 players are experiencing extreme lag and disconnections. The match server IP seems to be under attack. This is happening RIGHT NOW during a live match with prize money on the line!!!', 'cat' => 'Technical', 'status' => 'pending', 'priority' => 'critical', 'days' => 0],
            ['subject' => 'Checkout page broken on Firefox', 'desc' => 'The checkout page does not render properly on Firefox 121. The payment form fields overlap and the submit button is not clickable. Works fine on Chrome. This is a cross-browser compatibility bug.', 'cat' => 'Bug Report', 'status' => 'pending', 'priority' => 'medium', 'days' => 4],
            ['subject' => 'How to join competitive ladder?', 'desc' => 'I reached level 25 but I do not see the option to join the competitive ladder anywhere. The FAQ says it should unlock at level 20. Am I missing something or is this a known issue?', 'cat' => 'General', 'status' => 'resolved', 'priority' => 'low', 'days' => 9],
            ['subject' => 'Order shipped to wrong address', 'desc' => 'My order #2089 for the gaming mouse was shipped to my old address even though I updated my shipping address before placing the order. The package is now stuck at a location I no longer have access to.', 'cat' => 'Payment', 'status' => 'in_progress', 'priority' => 'high', 'days' => 2],
            ['subject' => 'Vulnerability found in chat system', 'desc' => 'I discovered a potential XSS vulnerability in the in-game chat system. When sending certain HTML tags in messages, they get rendered instead of escaped. This could be exploited to steal session tokens. I am reporting this responsibly.', 'cat' => 'Bug Report', 'status' => 'pending', 'priority' => 'critical', 'days' => 0],
        ];

        foreach ($tickets as $i => $t) {
            $ticket = new Ticket();
            $ticket->setSubject($t['subject'])
                ->setDescription($t['desc'])
                ->setStatus($t['status'])
                ->setPriority($t['priority'])
                ->setCategory($catEntities[$t['cat']])
                ->setSubmitter($users[$i % count($users)])
                ->setCreatedAt(new \DateTimeImmutable("-{$t['days']} days"));

            if ($t['status'] !== 'pending') {
                $ticket->setUpdatedAt(new \DateTimeImmutable("-" . max(0, $t['days'] - 1) . " days"));
            }
            if ($t['status'] === 'resolved') {
                $ticket->setAdminNotes('Issue has been investigated and resolved. User has been notified.');
            }
            if ($t['status'] === 'rejected') {
                $ticket->setAdminNotes('This request falls outside our support policy. Ticket closed.');
            }

            $manager->persist($ticket);
        }

        $manager->flush();
    }
}