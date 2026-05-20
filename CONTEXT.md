# Cursor Stats

Dashboard personnel pour visualiser la consommation de tokens Cursor, exécuté en local uniquement (Laravel Herd), alimenté par l'API dashboard non officielle de Cursor.

## Language

**Usage Event**:
Un appel facturable enregistré par Cursor (modèle, timestamp, tokens). Correspond à un élément de `usageEventsDisplay` retourné par l'API dashboard.
_Avoid_: Requête HTTP, session, conversation

**Token Breakdown**:
La ventilation d'un Usage Event en compteurs de tokens : input, output, et cache read (cache write absent sur les événements observés à ce jour).
_Avoid_: Cache (seul, ambigu)

**Context Size**:
Les tokens input d'un Usage Event — ce que le chat envoie au modèle pour cet appel. Correspond au compteur input du **Token Breakdown** ; le cache read n'en fait pas partie.
_Avoid_: Fenêtre de contexte, prompt total, input + cache

**Average Context Size**:
Somme des **Context Size** divisée par le nombre d'Usage Events token-based de la Date Range (même population que les totaux tokens). Si aucun événement token-based : **0** affiché, pas de tiret ni carte masquée. Affichage entier arrondi.
_Avoid_: Moyenne sur événements à input > 0, moyenne pondérée

**Contexte moyen**:
Libellé UI de l'**Average Context Size** — quatrième carte de l'Usage Summary (grille à 4 colonnes desktop), distincte de la carte Input (total sur la période). Sous-texte : « Moyenne des tokens envoyés au modèle, par appel. »
_Avoid_: Input moyen, taille de contexte, token-based

**Usage Summary**:
Les totaux affichés pour une Date Range : tokens par type (input, output, cache read), **Average Context Size**, plus un **Usage Cost** total pour la période. Pas de liste d'événements.
_Avoid_: Vue globale, totaux du jour

**Usage Cost**:
La somme des `chargedCents` de tous les Usage Events token-based de la Date Range, convertie en euros. Champ agrégé : `chargedCents` uniquement.
_Avoid_: totalCents, coût modèle seul

**Montant réel**:
Libellé UI du **Usage Cost** total — affiché sous les stats tokens, comme seule ligne monétaire de la période. Sous-texte : « Coût agrégé des appels inclus dans ce résumé. »
_Avoid_: Prix, facturation, spend, token-based

**Local Deployment**:
Le dashboard ne tourne que sur la machine de l'utilisateur (pas d'hébergement public). Les Session Credentials ne quittent pas le poste.
_Avoid_: Production, VPS, deploy

**Auth Failure**:
État affiché quand aucune Session Credential valide n'est disponible : page dédiée avec message explicite et instructions de correction — jamais de stats à zéro qui simulent une absence d'usage.
_Avoid_: Erreur 500, empty state

**Daily View**:
Cas particulier d'Usage Summary où la Date Range = aujourd'hui (minuit → minuit, fuseau configuré). Preset par défaut à l'ouverture.
_Avoid_: Période, fenêtre

**Date Range**:
Une plage entre deux dates (inclusives) choisie via presets (Aujourd'hui, Hier, 7 derniers jours) ou mode Personnalisé (deux dates). Remplace la Daily View comme fenêtre de filtrage.
_Avoid_: Période custom, intervalle

**Date Preset**:
Un raccourci de sélection de Date Range (aujourd'hui, hier, 7 jours). « Aujourd'hui » est le preset par défaut à l'ouverture.
_Avoid_: Filtre rapide, shortcut

**Reporting Timezone**:
Fuseau qui définit les minuits pour Daily View et Date Range. Configurable via `.env` (`CURSOR_STATS_TIMEZONE`), défaut `Europe/Paris`. Jamais codé en dur dans l'UI.
_Avoid_: TZ, locale

**Usage Data Source**:
L'API web non officielle du dashboard Cursor (`POST cursor.com/api/dashboard/get-filtered-usage-events`), authentifiée par session utilisateur.
_Avoid_: Admin API, Analytics API

**Composer Session Registry**:
Métadonnées locales des **Composer Session** (SQLite `composer.composerHeaders` : `composerId`, **Composer Session Title**, fenêtres temporelles, workspace). Ne contient pas de **Usage Events** ni de tokens — sert uniquement à l'**Estimated Attribution**.
_Avoid_: Source de facturation locale, liste d'events dans le fil

**Agent Transcript**:
Journal local JSONL (`~/.cursor/projects/.../agent-transcripts/<composerId>/`) : messages `role` user/assistant, texte et blocs `tool_use` (`name`, `input` seulement). **Sans timestamp par ligne**, sans `generationUUID` structuré, sans tokens. Le dossier identifie la **Composer Session** ; pas de jointure event API ↔ tour de chat.
_Avoid_: Source d'events facturables, clé de jointure vers l'API usage

**Workspace Generation**:
Entrée locale `aiService.generations` (par workspace) : `unixMs`, `generationUUID`, `type`, `textDescription` — **sans `composerId`**. Repère temporel projet, pas clé d'attribution par fil ni lien vers un **Usage Event** API (champs absents côté API observée).
_Avoid_: Remplacer le **Composer Session Registry**, identifiant de facturation

**Session Credential**:
Le secret qui prouve l'identité auprès de l'API dashboard. Résolu en hybride : lecture automatique depuis Cursor local (SQLite), avec repli sur cookie `WorkosCursorSessionToken` en configuration. Usage strictement personnel, jamais versionné.
_Avoid_: API key, clé Admin

**Composer Session**:
Un fil Composer ou une fenêtre Agent Cursor, identifié par `composerId` (UUID). Fenêtre temporelle locale `createdAt` → `lastUpdatedAt` (registre `composer.composerHeaders`). Distinct de la **Session Credential** (auth API).
_Avoid_: Session, conversation, chat (seul)

**Estimated Attribution**:
Affectation heuristique d'un **Usage Event** à une **Composer Session** par recoupement de timestamps en millisecondes (pas d'identifiant commun API ↔ Cursor). Non officielle ; peut laisser des événements non attribués. S'applique aussi aux events `isHeadless` — pas de règle d'attribution dédiée.
_Avoid_: Facturation par fil, jointure exacte, bucket headless séparé

**Attribution Tie-Break**:
Si plusieurs **Composer Session** ont une fenêtre qui contient le `timestamp` de l'event : retenir celle dont le `createdAt` est le **plus récent** encore ≤ timestamp (dernière session ouverte au moment de l'appel).
_Avoid_: Première ouverte, non attribué systématique en chevauchement


**Composer Session Title**:
Libellé affiché d'une **Composer Session** — champ `name` du registre Cursor (`composer.composerHeaders`). Utilisé dans le sélecteur de fils ; repli UI si absent (ex. identifiant tronqué).
_Avoid_: Titre de conversation, subject

**Composer Workspace Path**:
Chemin projet local (`workspaceIdentifier.uri.fsPath`) affiché en sous-titre sous le **Composer Session Title** dans la **Daily Composer Session List** (tronqué pour la lisibilité).
_Avoid_: Hash workspace seul, titre seul quand plusieurs projets

**Daily Composer Session List**:
Liste de sélection des **Composer Session** « du jour » : fenêtre calendaire = **Daily View** (**Reporting Timezone**, minuit → minuit), indépendante de la **Date Range** du résumé global. Critère d'inclusion : la fenêtre locale `[createdAt, lastUpdatedAt ?? now]` **intersecte** ce jour (fil créé hier mais encore actif aujourd'hui → inclus). Tri : `lastUpdatedAt` décroissant (`null` en fin de liste ou repli sur `createdAt`).
_Avoid_: Date Range sur la zone session, « créée aujourd'hui » seule, tri alphabétique par défaut

**Session Usage Breakdown**:
Zone UI juxtaposée à l'**Usage Summary** global : **50 / 50** sur viewport large (global à gauche, fil du jour à droite), empilées sur petit écran. **Daily Composer Session List** + détail au choix d'une session (même cartes métriques, **Estimated Attribution**). Le sélecteur de **Date Range** reste pleine largeur au-dessus. Sélection via `?composer=<composerId>` ; id invalide → **rediriger** sans le paramètre. Pas de bandeau « attribution estimée » (usage personnel).
_Avoid_: Zone session sous le global (desktop), colonnes asymétriques 60/40, tableau multi-jours

**Selected Session Summary**:
**Usage Summary** affiché pour une **Composer Session** choisie dans le **Session Usage Breakdown**, calculé uniquement sur les **Usage Events** du jour qui lui sont attribués.
_Avoid_: Résumé global filtré, stats lifetime du fil

**Unattributed Event Count**:
Nombre d'**Usage Events** du jour (tous types, pas seulement token-based) sans **Estimated Attribution** vers une **Composer Session**. Exclus du **Selected Session Summary** ; affiché sous le résumé du fil **uniquement lorsqu'un fil est sélectionné** (ex. « N appels non rattachés à un fil »).
_Avoid_: Ligne « Non attribué » dans le sélecteur, compteur visible sans sélection, décompte token-based seulement

**Token-Based Event Count**:
Nombre d'**Usage Events** du jour avec `isTokenBasedCall` — même population que les totaux tokens, **Contexte moyen** et **Usage Cost** d'un résumé. Exposé sous le **Selected Session Summary** ; le global conserve `eventCount` (tous types) inchangé.
_Avoid_: Remplacer eventCount partout, mélanger les deux libellés sans distinction

## Relationships

- Une **Date Range** (souvent via un **Date Preset**) détermine quels **Usage Events** sont inclus ; la **Daily View** est le cas où la plage = aujourd'hui minuit→minuit
- Une **Usage Summary** agrège les **Token Breakdown**, l'**Average Context Size** et le **Usage Cost** de tous les **Usage Events** de la Date Range (événements non token-based exclus du décompte tokens et de la moyenne)
- Chaque **Usage Event** possède au plus un **Token Breakdown** ; sa composante input est le **Context Size** de l'événement (donnée intermédiaire, jamais affichée seule)
- **Reporting Timezone** s'applique à toute **Daily View** et toute **Date Range**
- L'**Usage Summary** global suit la **Date Range** choisie en tête de page ; le **Session Usage Breakdown** utilise toujours la **Daily View** pour la **Daily Composer Session List** et le **Selected Session Summary** (découplage volontaire si la plage globale ≠ aujourd'hui)
- Les **Usage Events** (tokens, coût) proviennent uniquement de l'**Usage Data Source** ; le **Composer Session Registry** ne duplique pas ces données
- **Session Usage Breakdown** : charger les **Usage Events** du jour via l'API, charger les **Composer Session** du jour via le registre local, puis attribuer chaque event API vers au plus une session (parcours events → session, pas l'inverse)
- Une **Composer Session** du jour est incluse dans la **Daily Composer Session List** si sa fenêtre locale intersecte le jour courant en **Reporting Timezone**
- Le **Selected Session Summary** agrège uniquement les **Usage Events** du jour attribués à la session sélectionnée ; le **Token-Based Event Count** décrit cette population sous les cartes du fil ; le **Unattributed Event Count** (jour entier, tous types) n'apparaît qu'avec un fil sélectionné, sous son résumé

## Example dialogue

> **Dev:** « On affiche les stats de qui sur le dashboard ? »
> **Domain expert:** « Les miennes — l'API dashboard renvoie déjà mes événements quand je suis connecté. »
>
> **Dev:** « Et si je veux voir la semaine dernière ? »
> **Domain expert:** « Je choisis une Date Range ; par défaut c'est la Daily View d'aujourd'hui, minuit à minuit à Paris. »
>
> **Dev:** « On liste chaque requête ? »
> **Domain expert:** « Non — juste l'Usage Summary : totaux input, output et cache read, plus la taille de contexte moyenne par appel. »
>
> **Dev:** « Et l'argent ? »
> **Domain expert:** « Sous les tokens, un Montant réel : la somme de ce que Cursor a facturé sur la période. »

## Flagged ambiguities

- « Cache » : sur Pro+, les événements observés n'exposent que `cacheReadTokens` — libellé UI « cache read » retenu.
