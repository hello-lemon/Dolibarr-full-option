# Ajouter les options d’un module tiers

Le module fournisseur déclare le contexte `dolibarrfulloption` dans son descripteur :

```php
$this->module_parts['hooks'][] = 'dolibarrfulloption';
```

Sa classe `actions_<module>.class.php` expose la méthode suivante :

```php
public function provideDolibarrFullOptionEntries($parameters, &$object, &$action, $hookmanager)
{
    $this->results = array('options' => array(
        array(
            'key' => 'EXEMPLE_OPTION_ACTIVE',
            'module' => 'Mon module',
            'title_fr' => 'Activer la fonction exemple',
            'description_fr' => 'Affiche la fonction exemple sur les fiches concernées.',
            'versions' => array(22, 23, 24),
            'type' => 'boolean',
            'risk' => 'normal',
            'editable' => true,
            'source_url' => 'https://example.org/documentation',
        ),
    ));
    return 0;
}
```

Les fiches apparaissent dans l’onglet **Modules**. Les types acceptés sont `boolean`, `integer`, `text`, `url` et `enum` (ce dernier exige un tableau `values`). Les niveaux de risque sont `normal`, `attention` et `bloquée`. Une fiche `attention` demande une confirmation avant écriture ; une fiche `bloquée` reste consultable.

La clé doit être unique et composée de lettres majuscules, chiffres et `_`. Une contribution ne peut pas remplacer une fiche du catalogue principal ou d’un autre module. Les versions doivent être choisies parmi 22, 23 et 24. Vérifiez l’effet réel et le format de la constante avant d’activer `editable`.
