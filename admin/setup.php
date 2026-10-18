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
print '<p class="dolibarrfulloption-intro">Un catalogue expliqué en français, organisé par domaine et adapté à votre version de Dolibarr.</p>';
print '<div class="dolibarrfulloption-version"><span class="dolibarrfulloption-status-dot"></span><strong>Version détectée : '.dol_escape_htmltag(DOL_VERSION).'</strong>';
print '<span>Catalogue vérifié avec Dolibarr 22.0.4, 23.0.4 et 24.0.1.</span></div>';
if ($major !== 22 && $major !== 23 && $major !== 24) {
    print '<div class="dolibarrfulloption-warning">Cette version n’a pas été vérifiée. Le catalogue reste consultable, sans modification possible.</div>';
}
print '<div class="dolibarrfulloption-layout">';
print '<nav class="dolibarrfulloption-sidebar" aria-label="Domaines d’options"><h2>Domaines</h2>';
foreach ($tabs as $label => $slug) {
    print '<a class="'.($tab === $slug && $query === '' ? 'is-active' : '').'" href="'.dol_buildpath('/dolibarrfulloption/admin/setup.php', 1).'?tab='.urlencode($slug).'">';
    print '<span>'.dol_escape_htmltag($label).'</span><small>'.(int) $counts[$slug].'</small></a>';
}
print '<a class="dolibarrfulloption-archive '.($tab === 'historique' && $query === '' ? 'is-active' : '').'" href="'.dol_buildpath('/dolibarrfulloption/admin/setup.php', 1).'?tab=historique"><span>Anciennes options</span><small>'.(int) $counts['historique'].'</small></a>';
print '</nav><div class="dolibarrfulloption-content">';
print '<form method="get" class="dolibarrfulloption-search"><input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
print '<label for="dfo-search">Rechercher dans toutes les options</label><div><input id="dfo-search" type="search" name="q" value="'.dol_escape_htmltag($query).'" placeholder="Nom, constante ou effet du réglage…">';
print '<button type="submit">Rechercher</button></div></form>';
if ($query !== '') {
    print '<h2>Résultats pour « '.dol_escape_htmltag($query).' »</h2>';
} elseif ($tab === 'historique') {
    print '<h2>Anciennes options</h2><p class="dolibarrfulloption-section-desc">Ces constantes figurent dans la documentation mais ne sont pas présentes dans le code vérifié de Dolibarr '.$major.'. Elles restent visibles pour comprendre une ancienne configuration.</p>';
} else {
    $label = array_search($tab, $tabs, true);
    print '<h2>'.dol_escape_htmltag($label).'</h2><p class="dolibarrfulloption-section-desc">'.(int) $counts[$tab].' options compatibles avec Dolibarr '.$major.'.</p>';
}
print '<div class="dolibarrfulloption-result-count">'.count($filtered).' option'.(count($filtered) > 1 ? 's' : '').' trouvée'.(count($filtered) > 1 ? 's' : '').'</div>';
if (!$visible) {
    print '<div class="dolibarrfulloption-empty">Aucune option ne correspond à cette recherche.</div>';
}
foreach ($visible as $entry) {
    print DolibarrFullOptionView::option($entry, $settings, $major, $tab, $query);
}
if ($totalPages > 1) {
    print '<nav class="dolibarrfulloption-pagination" aria-label="Pages">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $url = dol_buildpath('/dolibarrfulloption/admin/setup.php', 1).'?tab='.urlencode($tab).'&page='.$i;
        if ($query !== '') {
            $url .= '&q='.urlencode($query);
        }
        print '<a class="'.($i === $page ? 'is-active' : '').'" href="'.dol_escape_htmltag($url).'">'.$i.'</a>';
    }
    print '</nav>';
}
print '</div></div>';
print '</div>';
print '<script src="'.dol_buildpath('/dolibarrfulloption/js/dolibarrfulloption.js', 1).'"></script>';
llxFooter();
$db->close();
