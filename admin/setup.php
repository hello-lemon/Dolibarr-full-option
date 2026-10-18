<?php
/* Copyright (C) 2026 Axel Piquet-Gauthier
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// Exige un jeton pour les actions même si la protection globale a été réduite.
if (!defined('CSRFCHECK_WITH_TOKEN')) {
    define('CSRFCHECK_WITH_TOKEN', 1);
}

$res = 0;
if (!$res && file_exists(__DIR__.'/../../main.inc.php')) {
    $res = include __DIR__.'/../../main.inc.php';
}
if (!$res && file_exists(__DIR__.'/../../../main.inc.php')) {
    $res = include __DIR__.'/../../../main.inc.php';
}
if (!$res) {
    die('Chargement de Dolibarr impossible.');
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/dolibarrfulloption/class/dolibarrfulloptioncatalog.class.php');
dol_include_once('/dolibarrfulloption/class/dolibarrfulloptionsettings.class.php');
dol_include_once('/dolibarrfulloption/class/dolibarrfulloptionview.class.php');

if (!$user->admin || !isModEnabled('dolibarrfulloption')) {
    accessforbidden();
}

$langs->load('admin');
$langs->load('dolibarrfulloption@dolibarrfulloption');

$major = DolibarrFullOptionCatalog::majorVersion();
$entries = DolibarrFullOptionCatalog::all();
$hookmanager->initHooks(array('dolibarrfulloption'));
$hookAction = '';
$hookContext = new stdClass();
$hookmanager->executeHooks('provideDolibarrFullOptionEntries', array('dolibarr_version' => DOL_VERSION), $hookContext, $hookAction);
if (!empty($hookmanager->resArray['options'])) {
    DolibarrFullOptionCatalog::registerProviderOptions($hookmanager->resArray['options']);
    $entries = DolibarrFullOptionCatalog::all();
}
$settings = new DolibarrFullOptionSettings($db, $conf->entity);
$tabs = DolibarrFullOptionCatalog::groups();
$tab = GETPOST('tab', 'alpha');
$query = trim(GETPOST('q', 'alphanohtml'));
$query = mb_substr($query, 0, 100);
$page = max(1, GETPOSTINT('page'));
if ($tab !== 'historique' && !in_array($tab, array_values($tabs), true)) {
    $tab = 'general';
}

$action = GETPOST('action', 'aZ09', 2);
if ($action === 'set' || $action === 'reset') {
    $key = GETPOST('key', 'alpha', 2);
    $entry = DolibarrFullOptionCatalog::find($key);
    try {
        if (!GETPOST('token', 'alpha', 2) || !hash_equals(currentToken(), GETPOST('token', 'alpha', 2))) {
            throw new RuntimeException('Jeton de sécurité expiré. Rechargez la page.');
        }
        if (!$entry || !DolibarrFullOptionCatalog::editable($entry, $major)) {
            throw new RuntimeException('Cette option n’est pas modifiable sur Dolibarr '.$major.'.');
        }
        if ($action === 'set') {
            if ($entry['risk'] === 'attention' && (int) GETPOST('confirm_risk', 'int', 2) !== 1) {
                throw new RuntimeException('Confirmez avoir lu l’avertissement avant de modifier cette option.');
            }
            $value = GETPOST('value', 'alphanohtml', 2);
            $settings->set($key, DolibarrFullOptionSettings::validate($entry, $value));
            $message = 'Option enregistrée.';
        } else {
            $settings->reset($key);
            $message = 'Valeur personnalisée retirée : le réglage par défaut s’applique.';
        }
        dol_syslog('DolibarrFullOption : '.$action.' '.$key.' par utilisateur '.(int) $user->id, LOG_INFO);
        setEventMessages($message, null, 'mesgs');
        $returnTab = GETPOST('tab', 'alpha', 2);
        if ($returnTab !== 'historique' && !in_array($returnTab, array_values($tabs), true)) {
            $returnTab = 'general';
        }
        $returnQuery = mb_substr(trim(GETPOST('q', 'alphanohtml', 2)), 0, 100);
        $url = dol_buildpath('/dolibarrfulloption/admin/setup.php', 1).'?tab='.urlencode($returnTab);
        if ($returnQuery !== '') {
            $url .= '&q='.urlencode($returnQuery);
        }
        header('Location: '.$url);
        exit;
    } catch (Exception $e) {
        dol_syslog('DolibarrFullOption : échec '.$action.' '.$key.' : '.$e->getMessage(), LOG_WARNING);
        setEventMessages($e->getMessage(), null, 'errors');
    }
}

if ($query !== '') {
    foreach ($settings->load() as $key => $stored) {
        if (!isset($entries[$key]) && preg_match('/^[A-Z][A-Z0-9_]+$/', $key) && mb_stripos($key, $query) !== false) {
            $entries[$key] = array(
                'key' => $key, 'group' => 'Modules', 'section' => 'Instance',
                'title_fr' => 'Constante définie sur cette instance',
                'description_fr' => 'Ce réglage existe dans la base de cette instance. Son effet et son format n’ont pas encore été vérifiés ; il peut être modifié depuis la page native Divers de Dolibarr.',
                'versions' => array($major), 'type' => 'text', 'risk' => 'normal',
                'source' => 'instance', 'source_url' => '', 'editable' => false,
            );
        }
    }
}

$counts = array_fill_keys(array_values($tabs), 0);
$counts['historique'] = 0;
$filtered = array();
foreach ($entries as $entry) {
    $compatible = DolibarrFullOptionCatalog::supports($entry, $major);
    if (!$compatible) {
        $counts['historique']++;
    } elseif (isset($tabs[$entry['group']])) {
        $counts[$tabs[$entry['group']]]++;
    }

    if ($query !== '') {
        $haystack = $entry['title_fr'].' '.$entry['key'].' '.$entry['description_fr'];
        if (mb_stripos($haystack, $query) === false) {
            continue;
        }
    } elseif ($tab === 'historique' ? $compatible : (!$compatible || !isset($tabs[$entry['group']]) || $tabs[$entry['group']] !== $tab)) {
        continue;
    }
    $filtered[] = $entry;
}

usort($filtered, function ($left, $right) {
    return strcoll($left['title_fr'], $right['title_fr']);
});
$perPage = 20;
$totalPages = max(1, (int) ceil(count($filtered) / $perPage));
$page = min($page, $totalPages);
$visible = array_slice($filtered, ($page - 1) * $perPage, $perPage);

llxHeader('', 'Options avancées Dolibarr');
print '<link rel="stylesheet" href="'.dol_buildpath('/dolibarrfulloption/css/dolibarrfulloption.css', 1).'">';
print '<div id="dolibarrfulloption-admin">';
print load_fiche_titre('Options avancées', '', 'fa-sliders');
print '<div class="opacitymedium dolibarrfulloption-version">Dolibarr '.dol_escape_htmltag(DOL_VERSION).' · Catalogue vérifié sur V22, V23 et V24. Les constantes absentes de cette version figurent dans « Anciennes options ».</div>';
if ($major !== 22 && $major !== 23 && $major !== 24) {
    print '<div class="warning">Cette version n’a pas été vérifiée. Le catalogue reste consultable, sans modification possible.</div>';
}
$baseUrl = dol_buildpath('/dolibarrfulloption/admin/setup.php', 1);
$head = array();
foreach ($tabs as $label => $slug) {
    $head[] = array($baseUrl.'?tab='.urlencode($slug), $label.' ('.(int) $counts[$slug].')', $slug);
}
$head[] = array($baseUrl.'?tab=historique', 'Anciennes options ('.(int) $counts['historique'].')', 'historique');
print dol_get_fiche_head($head, $tab, '', -1, '');
print '<form method="get" class="dolibarrfulloption-search">';
print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
print '<label for="dfo-search">Rechercher une option</label> ';
print '<input id="dfo-search" class="minwidth300" type="search" name="q" value="'.dol_escape_htmltag($query).'" placeholder="Nom, constante ou effet du réglage…"> ';
print '<button type="submit" class="button">Rechercher</button>';
if ($query !== '') {
    print ' <a href="'.$baseUrl.'?tab='.urlencode($tab).'">Effacer</a>';
}
print '</form>';
if ($tab === 'historique' && $query === '') {
    print '<div class="opacitymedium dolibarrfulloption-context">Constantes documentées mais absentes du code vérifié de Dolibarr '.$major.'.</div>';
}
$options = '&tab='.urlencode($tab);
if ($query !== '') {
    $options .= '&q='.urlencode($query);
}
print_barre_liste($query !== '' ? 'Résultats pour « '.dol_escape_htmltag($query).' »' : ($tab === 'historique' ? 'Anciennes options' : dol_escape_htmltag((string) array_search($tab, $tabs, true))), $page - 1, $baseUrl, $options, '', '', '', count($visible), count($filtered), 'fa-sliders', 0, '', '', $perPage);
print '<div class="div-table-responsive-no-min"><table class="noborder centpercent dolibarrfulloption-table">';
print '<tr class="liste_titre"><th>Option et effet</th><th class="dolibarrfulloption-action">Réglage</th></tr>';
if (!$visible) {
    print '<tr class="oddeven"><td colspan="2" class="opacitymedium center">Aucune option ne correspond à cette recherche.</td></tr>';
}
foreach ($visible as $entry) {
    print DolibarrFullOptionView::option($entry, $settings, $major, $tab, $query);
}
print '</table></div>';
print dol_get_fiche_end(-1);
print '</div>';
print '<script src="'.dol_buildpath('/dolibarrfulloption/js/dolibarrfulloption.js', 1).'"></script>';
llxFooter();
$db->close();
