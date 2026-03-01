# Team/Player Module – v2 (Invitations + Discord Avatar + Team Banner)

## 1) Prérequis
- PHP 8.1+
- Symfony 6.x
- DB à jour (voir `database/update-v2.sql`)

## 2) Mise à jour DB
Exécute le script SQL :
- `database/update-v2.sql`

Puis (si tu utilises Doctrine migrations) génère/valide :
```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 3) Invitations – Comportement attendu (implémenté)
### Côté joueur
- Sur `/profile` :
  - Bloc **Mes invitations** (max 3 affichées)
  - Boutons **Accepter / Refuser / Voir équipe**
- Sur `/invitations` :
  - Liste complète + actions

**Règles**
- Une invitation expire automatiquement après 7 jours (champ `expiresAt`)
- Lors de l’affichage, les invitations expirées passent en statut `expired`
- Lors d’un accept :
  - le joueur rejoint l’équipe
  - toutes les autres invitations pending du joueur passent en `declined`

### Côté équipe (capitaine)
- Page : `/team/{id}/invitations`
  - Liste des invitations en attente + bouton **Annuler**
  - Recherche des joueurs **disponibles** (même jeu, sans équipe LoL/Valorant, non déjà invités)
  - Anti-spam : **max 5 invitations / 24h / équipe**
  - Blocage si équipe full (`maxPlayers`)

## 4) Discord Avatar – Synchronisation
- Au callback Discord (`/connect/discord/check`) :
  - `player.discord_id`
  - `player.discord_username`
  - `player.discord_avatar` (hash)
  - `player.discord_avatar_url` (fallback)
- Sur le profil (`/profile`) : priorité à l’avatar Discord si présent.

## 5) Team Banner (photo de couverture)
### Upload
- Formulaire Team new/edit : champ **Bannière**
- Stockage : `public/uploads/teams/banners/`
- DB : `team.banner_name`

**Contraintes**
- JPG / PNG / WEBP
- Taille max 2 Mo
- Recommandé 1200x300

### Affichage
- Dans `team/show.html.twig` :
  - Bandeau pleine largeur si `bannerName` existe
  - Sinon bandeau dégradé (fallback)

## 6) Fichiers clés modifiés
- `src/Entity/Invitation.php` (status `expired`)
- `src/Service/InvitationService.php` (règles + accept/decline/cancel)
- `src/Controller/InvitationController.php` (routes, cancel, filtrage)
- `src/Repository/InvitationRepository.php` (anti-spam team + helpers)
- `src/Repository/PlayerRepository.php` (recherche joueurs disponibles)
- `src/Entity/Player.php` (champ `discord_avatar`)
- `src/Controller/DiscordController.php` (stockage hash avatar)
- `src/Service/DiscordAvatarService.php` (URL avatar)
- `src/Controller/UserUiController.php` (passe `discordAvatar` au template)
- `src/Entity/Team.php` (champ `banner_name`)
- `src/Form/TeamType.php` + `src/Controller/TeamController.php` (upload banner)
- `templates/front/profile.html.twig` (bloc invitations + avatar Discord)
- `templates/team/invitations.html.twig` (page complète)
- `templates/invitation/index.html.twig` (page dédiée)
- `templates/front/base_front.html.twig` (palette eSports)

## 7) Checklist de test (comme ton jury)
- [ ] Joueur voit ses invitations sur `/profile` + accepte/refuse
- [ ] Le bouton "Voir équipe" fonctionne
- [ ] Accept : joueur rejoint team + autres invitations refusées
- [ ] Capitaine voit `/team/{id}/invitations`
- [ ] Recherche ne retourne que joueurs sans équipe + non déjà invités
- [ ] Anti-spam : après 5 invitations sur 24h => blocage
- [ ] Banner : upload OK + affichage dans page team
- [ ] Avatar Discord affiché si compte lié
