<?php
/* Copyright (C) 2026 Axel Piquet-Gauthier
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modDolibarrFullOption extends DolibarrModules
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 210047;
        $this->rights_class = 'dolibarrfulloption';
        $this->special = 0;
        $this->family = 'interface';
        $this->module_position = '95';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'Catalogue communautaire des options avancées de Dolibarr';
        $this->descriptionlong = 'Réglages expliqués en français, classés par domaine et filtrés selon la version de Dolibarr.';
        $this->editor_name = 'Contributeurs';
        $this->editor_url = '';
        $this->version = '0.1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'fa-sliders';
        $this->module_parts = array('js' => array('/dolibarrfulloption/js/const-link.js'));
        $this->dirs = array();
        $this->config_page_url = array('setup.php@dolibarrfulloption');
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array('dolibarrfulloption@dolibarrfulloption');
        $this->phpmin = array(7, 4);
        $this->need_dolibarr_version = array(22, 0, 0);
        $this->const = array();
        $this->rights = array();
        $this->menu = array();
    }

    public function init($options = '')
    {
        return $this->_init(array(), $options);
    }

    // La désactivation n'efface jamais les constantes que l'administrateur a réglées.
    public function remove($options = '')
    {
        return $this->_remove(array(), $options);
    }
}
