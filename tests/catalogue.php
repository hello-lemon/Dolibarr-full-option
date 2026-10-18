<?php
/* Vérifications sans installation Dolibarr : catalogue, versions et valeurs admises. */
define('DOL_VERSION', '24.0.1');
require __DIR__.'/../class/dolibarrfulloptioncatalog.class.php';
require __DIR__.'/../class/dolibarrfulloptionsettings.class.php';
require __DIR__.'/../class/dolibarrfulloptionview.class.php';
require __DIR__.'/../class/dolibarrfulloptiondiscovery.class.php';

function newToken()
{
    return 'jeton-test';
}

function check($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "Échec : ".$message."\n");
        exit(1);
    }
}

$entries = DolibarrFullOptionCatalog::all();
$discovery = DolibarrFullOptionDiscovery::all();
check(count($discovery) === 3291, 'L’inventaire du code doit être complet.');
check(count(array_filter($discovery, function ($row) { return $row['origin'] === 'doliconstdoc'; })) === 3219, 'Les 3 219 fiches DoliConstDoc doivent être conservées.');
check(in_array(22, DolibarrFullOptionDiscovery::toCatalogEntry($discovery['AADE_WEBSERVICE_KEY'])['versions'], true), 'L’inventaire doit repérer les clés présentes en V22.');
$untranslated = array_filter($discovery, function ($row) { return !empty($row['purpose_en']) && DolibarrFullOptionDiscovery::toCatalogEntry($row)['description_fr'] === ''; });
check(count($untranslated) === 0, 'Chaque résumé de l’inventaire doit avoir sa traduction française.');
check(!isset($entries['AADE_WEBSERVICE_KEY']), 'Les clés de l’inventaire ne doivent pas être confondues avec les fiches vérifiées.');
check(count($entries) >= 532, 'Toutes les constantes du wiki doivent figurer dans le catalogue.');
check(DolibarrFullOptionCatalog::majorVersion() === 24, 'La version majeure doit être détectée.');
check(DolibarrFullOptionCatalog::find('MAIN_PAGESIZE_CHOICES')['type'] === 'choices', 'Le sélecteur de tailles doit être spécialisé.');
check(!DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('MAIN_SECURITY_SALT'), 24), 'Une constante sensible doit rester en lecture seule.');
check(!DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('MAIN_NO_CONCAT_DESCRIPTION'), 24), 'Une ancienne constante ne doit pas être modifiable en V24.');
check(DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('FCKEDITOR_ENABLE_MAIL'), 22), 'L’éditeur de courriel doit être disponible en V22.');
check(DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('BANK_DISABLE_DIRECT_INPUT'), 24), 'Le verrou de saisie bancaire doit être modifiable.');
check(DolibarrFullOptionCatalog::find('ADD_UNSPLASH_LOGIN_BACKGROUND')['type'] === 'url', 'Le fond de connexion attend une URL.');
check(DolibarrFullOptionCatalog::find('MAIN_PASSWORD_FORGOTLINK')['type'] === 'url', 'Le lien de mot de passe oublié attend une URL vérifiée.');
check(DolibarrFullOptionCatalog::find('MEMBER_EXT_URL_SUBSCRIPTION_INFO')['type'] === 'url', 'Le lien public des adhésions attend une URL vérifiée.');
check(DolibarrFullOptionCatalog::find('API_DISABLE_LOGIN_API')['description_fr'] !== '(v14.0)', 'Le blocage des connexions API doit être expliqué.');
check(DolibarrFullOptionCatalog::find('SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD')['type'] === 'boolean', 'La navigation des fiches tiers doit utiliser un interrupteur.');
check(DolibarrFullOptionCatalog::find('MAIN_WAREHOUSE_LIST_DISPLAY_MODE')['values'] === array('0', '1', '2'), 'L’affichage des entrepôts doit proposer les trois modes du cœur.');
foreach (array('MAIN_FIRST_PING_OK_ID', 'COMPANY_AQUARIUM_CLEAN_REGEX', 'MAIN_ODT_AS_PDF') as $managedKey) {
    check(!DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find($managedKey), 24), $managedKey.' ne doit pas accepter une valeur libre.');
}
foreach ($entries as $entry) {
    check(in_array($entry['group'], array_keys(DolibarrFullOptionCatalog::groups()), true), $entry['key'].' : domaine inconnu.');
    check(in_array($entry['type'], array('boolean', 'integer', 'text', 'enum', 'choices', 'url'), true), $entry['key'].' : type inconnu.');
    check(!$entry['editable'] || trim($entry['description_fr']) !== '', $entry['key'].' : description absente.');
    check(mb_strpos($entry['title_fr'], '…') === false, $entry['key'].' : titre tronqué.');
}
$choices = DolibarrFullOptionCatalog::find('MAIN_PAGESIZE_CHOICES');
check(DolibarrFullOptionSettings::validate(DolibarrFullOptionCatalog::find('ADD_UNSPLASH_LOGIN_BACKGROUND'), 'https://example.org/image.jpg') === 'https://example.org/image.jpg', 'L’URL HTTPS doit être acceptée.');
try {
    DolibarrFullOptionSettings::validate(DolibarrFullOptionCatalog::find('ADD_UNSPLASH_LOGIN_BACKGROUND'), 'http://example.org/image.jpg');
    check(false, 'Une URL non chiffrée doit être rejetée.');
} catch (InvalidArgumentException $e) {
}
try {
    DolibarrFullOptionSettings::validate(DolibarrFullOptionCatalog::find('MAIN_PASSWORD_FORGOTLINK'), 'javascript:alert(1)');
    check(false, 'Un lien JavaScript ne doit pas être enregistré sur la page de connexion.');
} catch (InvalidArgumentException $e) {
}
check(DolibarrFullOptionCatalog::registerProviderOptions(array(array('key' => 'EXAMPLE_OPTION', 'module' => 'Exemple', 'title_fr' => 'Option exemple', 'description_fr' => 'Description en français.', 'versions' => array(22, 23, 24), 'type' => 'boolean', 'editable' => true))) === 1, 'Une option fournie par un module doit être ajoutée.');
check(DolibarrFullOptionCatalog::find('EXAMPLE_OPTION')['group'] === 'Modules', 'Les options fournies doivent être dans l’onglet Modules.');
check(DolibarrFullOptionCatalog::registerProviderOptions(array(array('key' => 'BANK_DISABLE_DIRECT_INPUT', 'module' => 'Exemple', 'title_fr' => 'Doublon', 'description_fr' => 'Description.', 'versions' => array(24), 'type' => 'boolean'))) === 0, 'Une option fournie ne doit pas remplacer une fiche officielle.');
check(DolibarrFullOptionSettings::validate($choices, '25:25,10:10') === '10:10,25:25', 'Les tailles doivent être normalisées.');
try {
    DolibarrFullOptionSettings::validate($choices, '10:25');
    check(false, 'Une paire incohérente doit être rejetée.');
} catch (InvalidArgumentException $e) {
}
try {
    DolibarrFullOptionSettings::validate(DolibarrFullOptionCatalog::find('MAIN_HIDE_POWERED_BY'), 'peut-être');
    check(false, 'Un interrupteur doit refuser une valeur libre.');
} catch (InvalidArgumentException $e) {
}
$settings = new class {
    public function value($key) { return ''; }
    public function hasLocalOverride($key) { return false; }
};
$card = DolibarrFullOptionView::option($choices, $settings, 24, 'general', '');
check(strpos($card, 'data-choice-editor') !== false, 'Les tailles de liste doivent utiliser des pastilles.');
check(strpos($card, '5000:5000') !== false, 'La valeur par défaut V24 doit être celle du cœur.');
check(substr_count($card, '<form') === substr_count($card, '</form>'), 'Les formulaires doivent être fermés.');
$oldCard = DolibarrFullOptionView::option(DolibarrFullOptionCatalog::find('MAIN_NO_CONCAT_DESCRIPTION'), $settings, 24, 'historique', '');
check(strpos($oldCard, '<form') === false, 'Une ancienne option ne doit pas avoir de formulaire.');
print "Catalogue vérifié : ".count($entries)." options.\n";
