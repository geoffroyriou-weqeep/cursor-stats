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

**Session Credential**:
Le secret qui prouve l'identité auprès de l'API dashboard. Résolu en hybride : lecture automatique depuis Cursor local (SQLite), avec repli sur cookie `WorkosCursorSessionToken` en configuration. Usage strictement personnel, jamais versionné.
_Avoid_: API key, clé Admin

## Relationships

- Une **Date Range** (souvent via un **Date Preset**) détermine quels **Usage Events** sont inclus ; la **Daily View** est le cas où la plage = aujourd'hui minuit→minuit
- Une **Usage Summary** agrège les **Token Breakdown**, l'**Average Context Size** et le **Usage Cost** de tous les **Usage Events** de la Date Range (événements non token-based exclus du décompte tokens et de la moyenne)
- Chaque **Usage Event** possède au plus un **Token Breakdown** ; sa composante input est le **Context Size** de l'événement (donnée intermédiaire, jamais affichée seule)
- **Reporting Timezone** s'applique à toute **Daily View** et toute **Date Range**

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
