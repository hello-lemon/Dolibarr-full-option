# Constantes du code non documentées dans DolibarrFullOption

Balayage statique des appels directs à `getDolGlobalInt`, `getDolGlobalString`, `getDolGlobalBoolean`, `getDolGlobalFloat` et `getDolGlobalArray` dans les sources officielles Dolibarr `23.0.4` et `24.0.1`. Les exemples du Module Builder, les tests et les dépendances sont exclus.

- 2635 clés uniques absentes du catalogue actuel ; 2499 repérées en V23 et 2625 en V24.
- Une clé repérée peut être une préférence déjà gérée par une page native, un réglage interne, une clé de module, un secret ou une option conditionnée par un module métier. Elle n’est pas automatiquement modifiable.
- `code-candidates.json` donne les versions, le nombre de fichiers et jusqu’à trois chemins source par clé pour permettre une revue manuelle.

## Priorité de revue

Réviser d’abord les constantes visibles dans plusieurs fichiers et absentes du wiki, puis vérifier leur effet, leurs valeurs admises et les risques avant toute édition depuis le module.
