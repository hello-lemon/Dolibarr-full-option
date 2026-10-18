<?php
/* Copyright (C) 2026 Axel Piquet-Gauthier
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

class DolibarrFullOptionSettings
{
    private $db;
    private $entity;
    private $stored;

    public function __construct($db, $entity)
    {
        $this->db = $db;
        $this->entity = (int) $entity;
        $this->stored = null;
    }

    public function load()
    {
        if ($this->stored !== null) {
            return $this->stored;
        }
        $this->stored = array();
        $sql = 'SELECT name, value, entity FROM '.MAIN_DB_PREFIX.'const WHERE entity IN (0, '.$this->entity.') ORDER BY entity ASC';
        $resql = $this->db->query($sql);
        if (!$resql) {
            throw new RuntimeException('Lecture des constantes impossible : '.$this->db->lasterror());
        }
        while ($object = $this->db->fetch_object($resql)) {
            $this->stored[$object->name] = array('value' => $object->value, 'entity' => (int) $object->entity);
        }
        return $this->stored;
    }

    public function current($key)
    {
        $stored = $this->load();
        return isset($stored[$key]) ? $stored[$key] : null;
    }

    public function value($key)
    {
        $current = $this->current($key);
        if ($current !== null) {
            return (string) $current['value'];
        }
        return (string) getDolGlobalString($key);
    }

    public function hasLocalOverride($key)
    {
        $current = $this->current($key);
        return $current !== null && $current['entity'] === $this->entity;
    }

    public static function validate($entry, $value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Valeur incorrecte.');
        }
        $type = isset($entry['type']) ? $entry['type'] : 'text';
        if ($type === 'boolean') {
            if ($value !== '0' && $value !== '1') {
                throw new InvalidArgumentException('Choisir Activé ou Désactivé.');
            }
            return $value;
        }
        if ($type === 'integer') {
            if (!preg_match('/^-?\d{1,9}$/', $value) || (int) $value < -100000 || (int) $value > 100000) {
                throw new InvalidArgumentException('Saisir un nombre entier valide.');
            }
            return (string) ((int) $value);
        }
        if ($type === 'enum') {
            if (empty($entry['values']) || !in_array($value, $entry['values'], true)) {
                throw new InvalidArgumentException('Choisir une valeur de la liste.');
            }
            return $value;
        }
        if ($type === 'choices') {
            $parts = explode(',', $value);
            if (count($parts) < 1 || count($parts) > 20) {
                throw new InvalidArgumentException('Choisir entre 1 et 20 tailles de liste.');
            }
            $numbers = array();
            foreach ($parts as $part) {
                if (!preg_match('/^(\d{1,4}):\1$/', $part, $matches)) {
                    throw new InvalidArgumentException('Format de taille de liste incorrect.');
                }
                $number = (int) $matches[1];
                if ($number < 1 || $number > 5000 || isset($numbers[$number])) {
                    throw new InvalidArgumentException('Les tailles doivent être uniques et comprises entre 1 et 5 000.');
                }
                $numbers[$number] = true;
            }
            ksort($numbers, SORT_NUMERIC);
            return implode(',', array_map(function ($number) { return $number.':'.$number; }, array_keys($numbers)));
        }
        if ($type === 'url') {
            $value = trim($value);
            if ($value === '') {
                return '';
            }
            $parts = parse_url($value);
            if (strlen($value) > 500 || !filter_var($value, FILTER_VALIDATE_URL) || !is_array($parts)
                || !isset($parts['scheme'], $parts['host']) || strtolower($parts['scheme']) !== 'https'
                || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x1F\x7F<>\"\'\\\\]/', $value)) {
                throw new InvalidArgumentException('Saisir une URL HTTPS valide, sans identifiant ni caractère spécial.');
            }
            return $value;
        }
        $value = trim($value);
        if ($value === '' || strlen($value) > 500 || preg_match('/[\x00-\x1F\x7F<>]/', $value)) {
            throw new InvalidArgumentException('Saisir une valeur de 1 à 500 caractères, sans HTML ni saut de ligne.');
        }
        return $value;
    }

    public function set($key, $value)
    {
        $result = dolibarr_set_const($this->db, $key, $value, 'chaine', 0, '', $this->entity);
        if ($result <= 0) {
            throw new RuntimeException('Enregistrement impossible : '.$this->db->lasterror());
        }
        $this->stored = null;
    }

    public function reset($key)
    {
        if (!$this->hasLocalOverride($key)) {
            return;
        }
        $result = dolibarr_del_const($this->db, $key, $this->entity);
        if ($result < 0) {
            throw new RuntimeException('Réinitialisation impossible : '.$this->db->lasterror());
        }
        $this->stored = null;
    }
}
