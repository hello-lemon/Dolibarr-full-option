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

    public static function registerProviderOptions($provided)
    {
        if (!is_array($provided)) {
            return 0;
        }
        self::all();
        $added = 0;
        foreach ($provided as $entry) {
            if (!is_array($entry) || !isset($entry['key'], $entry['title_fr'], $entry['description_fr'], $entry['versions'], $entry['type'], $entry['module'])) {
                continue;
            }
            $key = $entry['key'];
            if (!is_string($key) || !preg_match('/^[A-Z][A-Z0-9_]+$/', $key) || isset(self::$entries[$key])
                || !is_string($entry['title_fr']) || trim($entry['title_fr']) === ''
                || !is_string($entry['description_fr']) || trim($entry['description_fr']) === ''
                || !is_string($entry['module']) || trim($entry['module']) === ''
                || !is_array($entry['versions']) || !in_array($entry['type'], array('boolean', 'integer', 'text', 'enum', 'url'), true)) {
                continue;
            }
            $versions = array_values(array_unique(array_map('intval', $entry['versions'])));
            if (!$versions || array_diff($versions, array(22, 23, 24))) {
                continue;
            }
            if ($entry['type'] === 'enum' && (!isset($entry['values']) || !is_array($entry['values']) || !$entry['values'])) {
                continue;
            }
            $entry['versions'] = $versions;
            $entry['group'] = 'Modules';
            $entry['section'] = $entry['module'];
            $entry['risk'] = isset($entry['risk']) && in_array($entry['risk'], array('normal', 'attention', 'bloquée'), true) ? $entry['risk'] : 'attention';
            $entry['source'] = 'module';
            $entry['source_url'] = isset($entry['source_url']) && preg_match('~^https://~', $entry['source_url']) ? $entry['source_url'] : '';
            $entry['editable'] = !empty($entry['editable']) && $entry['risk'] !== 'bloquée';
            self::$entries[$key] = $entry;
            $added++;
        }
        return $added;
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
        return in_array((int) $major, array(22, 23, 24), true) && !empty($entry['editable']) && self::supports($entry, $major) && $entry['risk'] !== 'bloquée';
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
            'Modules' => 'modules',
        );
    }
}
