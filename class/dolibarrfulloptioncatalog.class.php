<?php
/* Copyright (C) 2026 Axel Piquet-Gauthier
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

class DolibarrFullOptionCatalog
{
    private static $entries;

    public static function all()
    {
        if (self::$entries !== null) {
            return self::$entries;
        }

        $path = dirname(__DIR__).'/data/options.json';
        $json = file_get_contents($path);
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Catalogue DolibarrFullOption illisible.');
        }

        self::$entries = array();
        foreach ($decoded as $entry) {
            if (!is_array($entry) || empty($entry['key']) || !preg_match('/^[A-Z][A-Z0-9_]+$/', $entry['key'])) {
                throw new RuntimeException('Clé invalide dans le catalogue DolibarrFullOption.');
            }
            if (isset(self::$entries[$entry['key']])) {
                throw new RuntimeException('Clé en double dans le catalogue DolibarrFullOption.');
            }
            self::$entries[$entry['key']] = $entry;
        }
        return self::$entries;
    }

    public static function find($key)
    {
        $entries = self::all();
        return isset($entries[$key]) ? $entries[$key] : null;
    }

    public static function majorVersion()
    {
        if (!preg_match('/^(\d+)/', DOL_VERSION, $matches)) {
            return 0;
        }
        return (int) $matches[1];
    }

    public static function supports($entry, $major)
    {
        return isset($entry['versions']) && in_array((int) $major, $entry['versions'], true);
    }

    public static function editable($entry, $major)
    {
        return !empty($entry['editable']) && self::supports($entry, $major) && $entry['risk'] !== 'bloquée';
    }

    public static function groups()
    {
        return array(
            'Général' => 'general',
            'Interface' => 'interface',
            'Ventes et achats' => 'ventes',
            'Produits et stock' => 'produits',
            'Comptabilité et banque' => 'comptabilite',
            'Documents et PDF' => 'documents',
            'Communication' => 'communication',
            'Tiers et RH' => 'tiers',
            'Projets et services' => 'projets',
            'Sécurité et système' => 'securite',
        );
    }
}
