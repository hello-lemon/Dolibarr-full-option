<?php
/* Vérifications sans installation Dolibarr : catalogue, versions et valeurs admises. */
define('DOL_VERSION', '24.0.1');
require __DIR__.'/../class/dolibarrfulloptioncatalog.class.php';
require __DIR__.'/../class/dolibarrfulloptionsettings.class.php';
require __DIR__.'/../class/dolibarrfulloptionview.class.php';

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
check(count($entries) >= 532, 'Toutes les constantes du wiki doivent figurer dans le catalogue.');
check(DolibarrFullOptionCatalog::majorVersion() === 24, 'La version majeure doit être détectée.');
check(DolibarrFullOptionCatalog::find('MAIN_PAGESIZE_CHOICES')['type'] === 'choices', 'Le sélecteur de tailles doit être spécialisé.');
check(!DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('MAIN_SECURITY_SALT'), 24), 'Une constante sensible doit rester en lecture seule.');
check(!DolibarrFullOptionCatalog::editable(DolibarrFullOptionCatalog::find('MAIN_NO_CONCAT_DESCRIPTION'), 24), 'Une ancienne constante ne doit pas être modifiable en V24.');
foreach ($entries as $entry) {
    check(in_array($entry['group'], array_keys(DolibarrFullOptionCatalog::groups()), true), $entry['key'].' : domaine inconnu.');
    check(in_array($entry['type'], array('boolean', 'integer', 'text', 'enum', 'choices'), true), $entry['key'].' : type inconnu.');
    check(!$entry['editable'] || trim($entry['description_fr']) !== '', $entry['key'].' : description absente.');
}
$choices = DolibarrFullOptionCatalog::find('MAIN_PAGESIZE_CHOICES');
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
