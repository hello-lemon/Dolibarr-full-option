<?php
/* Inventaire technique dérivé de DoliConstDoc (GPL-3.0-or-later).
 * Provenance et limites : docs/inventaire-doliconstdoc.md.
 */

class DolibarrFullOptionDiscovery
{
    private static $entries;

    public static function all()
    {
        if (self::$entries !== null) {
            return self::$entries;
        }
        $path = dirname(__DIR__).'/data/discovery.json';
        $decoded = json_decode(file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Inventaire technique illisible.');
        }
        self::$entries = array();
        foreach ($decoded as $row) {
            if (!is_array($row) || empty($row['key']) || !preg_match('/^[A-Z][A-Z0-9_]+$/', $row['key']) || isset(self::$entries[$row['key']])) {
                throw new RuntimeException('Clé invalide ou répétée dans l’inventaire technique.');
            }
            self::$entries[$row['key']] = $row;
        }
        return self::$entries;
    }

    public static function toCatalogEntry($row)
    {
        $versions = array_map('intval', array_keys($row['references']));
        $sourceVersion = $versions ? max($versions) : 24;
        $sourcePath = isset($row['references'][(string) $sourceVersion][0]) ? $row['references'][(string) $sourceVersion][0] : '';
        return array(
            'key' => $row['key'],
            'group' => 'Inventaire du code',
            'section' => $row['module'],
            'title_fr' => $row['key'],
            'description_fr' => 'Constante repérée dans le code de Dolibarr. Son effet et ses valeurs attendues n’ont pas encore été vérifiés pour une fiche française éditable.',
            'purpose_en' => $row['purpose_en'],
            'versions' => $versions,
            'type' => 'text',
            'risk' => 'attention',
            'source' => 'doliconstdoc',
            'source_url' => $sourcePath ? 'https://github.com/Dolibarr/dolibarr/blob/'.$sourceVersion.'.0.'.($sourceVersion === 22 ? '4' : ($sourceVersion === 23 ? '4' : '1')).'/htdocs/'.$sourcePath : '',
            'editable' => false,
            'confidence' => $row['confidence'],
            'hidden' => $row['hidden'],
        );
    }
}
