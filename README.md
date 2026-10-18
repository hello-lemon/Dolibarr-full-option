# Dolibarr Full Option

Module communautaire pour Dolibarr 22, 23 et 24. Il rassemble les options avancées dans une page d’administration en français : onglets et tableau natifs, recherche, explication de chaque constante et commande adaptée à sa valeur (interrupteur, liste, nombre, URL ou champ texte). La version de Dolibarr est détectée automatiquement. Les options absentes du code de cette version restent consultables sous **Anciennes options**, sans commande d’écriture.

## Contenu du catalogue

- 532 constantes recensées sur la page [Setup Other du wiki Dolibarr](https://wiki.dolibarr.org/index.php/Setup_Other).
- 14 options supplémentaires sélectionnées dans le code officiel.
- 546 fiches au total, comparées aux sources officielles 22.0.4, 23.0.4 et 24.0.1.
- Un [inventaire complémentaire](docs/audit-code.md) recense 2 645 autres constantes repérées dans le code 23 et 24. Ce sont des pistes d’analyse, pas des réglages automatiquement modifiables.

Une constante présente dans le code peut dépendre d’un autre module ou d’un contexte particulier. Les fiches dont le format ou l’effet est incertain restent en lecture seule. Les versions autres que 22, 23 et 24 sont consultables sans écriture. Avant toute modification, lire la description et vérifier le comportement sur une instance de test.

## Installation

1. Copier le dossier du module sous `htdocs/custom/dolibarrfulloption/`.
2. Dans **Configuration > Modules**, activer **DolibarrFullOption**.
3. Ouvrir la page de configuration du module, ou utiliser le bouton **Options expliquées** sur la page native **Configuration > Divers**. Seuls les administrateurs y ont accès.

Une modification crée ou met à jour la constante dans l’entité courante. **Revenir au défaut** retire uniquement la valeur définie pour cette entité. La désactivation du module ne supprime pas les réglages déjà enregistrés.

## Sécurité et validation

Les valeurs sont validées côté serveur. Les options sensibles ou ambiguës sont en lecture seule ; celles signalées **Attention** demandent une confirmation. Chaque écriture vérifie le jeton CSRF de Dolibarr. Les valeurs proposées pour les tailles de listes sont éditées comme des pastilles et converties au format natif de Dolibarr.

Vérifications locales : `php tests/catalogue.php`, `php -l` sur les fichiers PHP et `node --check` sur les fichiers JavaScript. L’installation a également été vérifiée sur les versions 22.0.4 et 23.0.3.

## Contribuer

Les corrections de description, de valeurs admises et de compatibilité sont bienvenues. Pour proposer une fiche, fournir la constante, la version concernée et un lien vers le code officiel qui montre son effet. Ne marquez une fiche comme modifiable qu’après vérification de son format et de son comportement.

Les modules tiers peuvent ajouter leurs propres fiches via le [point d’extension `dolibarrfulloption`](docs/provider-hook.md).

## Projet proche

[DoliConstDoc](https://github.com/nikube/doliconstdoc) propose un inventaire libre de plusieurs milliers de constantes. Notre catalogue reste plus restreint et vérifie les commandes d’édition fiche par fiche ; les deux projets peuvent partager des corrections de documentation.

## Licence

GPL-3.0-or-later. Voir [COPYING](COPYING). Dolibarr et sa documentation appartiennent à leurs contributeurs respectifs ; les liens vers les sources figurent sur les fiches.
