# Provenance de l’inventaire du code

Le fichier `data/discovery.json` dérive de la base de documentation [DoliConstDoc](https://github.com/nikube/doliconstdoc), dépôt de Nikube, commit `0ec22135a80fc6e3d969114ea28e37abd3e1a204`, sous licence GPL-3.0-or-later. Les clés, domaines, courts résumés anglais, indications de type et niveaux de confiance proviennent de `data/constants.sql`. Les chemins et versions ont été recalculés par `tools/import_doliconstdoc.py` dans les sources officielles de Dolibarr 22.0.4, 23.0.4 et 24.0.1.

Cette liste est un **inventaire de recherche**, pas une liste de réglages validés. DoliConstDoc indique que ses textes ont été enrichis par un modèle de langage et peuvent extrapoler. La présence littérale d’une clé dans le code ne prouve pas qu’elle fonctionne comme option administrateur. Les clés non revues restent distinctes du catalogue éditable en français. Un chemin dans `references` signifie « repérée dans la source », pas « comportement vérifié ».

Reproduire l’import :

```sh
python3 tools/import_doliconstdoc.py /chemin/vers/doliconstdoc --dolibarr-sources /chemin/vers/sources --output data/discovery.json
```

Le dossier des sources doit contenir `dolibarr-22.0.4/htdocs`, `dolibarr-23.0.4/htdocs` et `dolibarr-24.0.1/htdocs`.
