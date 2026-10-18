<?php
/* Copyright (C) 2026 Axel Piquet-Gauthier
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

class DolibarrFullOptionView
{
    private static function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function hiddenFields($entry, $tab, $query, $action)
    {
        return '<input type="hidden" name="token" value="'.self::e(newToken()).'">'
            .'<input type="hidden" name="action" value="'.self::e($action).'">'
            .'<input type="hidden" name="key" value="'.self::e($entry['key']).'">'
            .'<input type="hidden" name="tab" value="'.self::e($tab).'">'
            .'<input type="hidden" name="q" value="'.self::e($query).'">';
    }

    private static function control($entry, $settings, $major, $tab, $query)
    {
        $key = $entry['key'];
        $value = $settings->value($key);
        $hasDefault = isset($entry['defaults'][(string) $major]);
        if ($value === '' && $hasDefault) {
            $value = (string) $entry['defaults'][(string) $major];
        }
        $type = $entry['type'];
        $html = '<div class="dolibarrfulloption-control">';
        $html .= '<form method="post" action="" class="dolibarrfulloption-value-form">';
        $html .= self::hiddenFields($entry, $tab, $query, 'set');

        if ($type === 'boolean') {
            $isUnspecified = $value === '';
            $isOn = $value !== '' && $value !== '0';
            $html .= '<input type="hidden" name="value" value="'.($isOn ? '0' : '1').'">';
            $html .= '<span class="dolibarrfulloption-switch-label">'.($isUnspecified ? 'Valeur par défaut' : ($isOn ? 'Activé' : 'Désactivé')).'</span>';
            $html .= '<button type="submit" class="dolibarrfulloption-switch'.($isOn ? ' is-on' : '').'" aria-label="'.($isOn ? 'Désactiver' : 'Activer').' '.self::e($entry['title_fr']).'" aria-pressed="'.($isOn ? 'true' : 'false').'"></button>';
        } elseif ($type === 'enum') {
            if ($value === '') {
                $value = $entry['values'][0];
            }
            $html .= '<label class="dolibarrfulloption-field-label" for="dfo-'.self::e($key).'">Valeur</label>';
            $html .= '<select id="dfo-'.self::e($key).'" name="value">';
            foreach ($entry['values'] as $choice) {
                $label = self::enumLabel($key, $choice);
                $html .= '<option value="'.self::e($choice).'"'.($value === $choice ? ' selected' : '').'>'.self::e($label).'</option>';
            }
            $html .= '</select><button type="submit" class="button">Appliquer</button>';
        } elseif ($type === 'choices') {
            $html .= '<span class="dolibarrfulloption-field-label">Choix proposés</span>';
            $html .= '<div class="dolibarrfulloption-choice-editor" data-choice-editor>';
            $html .= '<div class="dolibarrfulloption-choice-list" data-choice-list></div>';
            $html .= '<div class="dolibarrfulloption-add"><input type="number" min="1" max="5000" step="1" placeholder="Ex. 250" aria-label="Ajouter un nombre de lignes" data-choice-input>';
            $html .= '<button type="button" data-choice-add>+ Ajouter</button></div>';
            $html .= '<input type="hidden" name="value" value="'.self::e($value).'" data-choice-value>';
            $html .= '<details><summary>Valeur technique Dolibarr</summary><code data-choice-technical></code></details>';
            $html .= '<p class="dolibarrfulloption-choice-warning" data-choice-warning hidden>La valeur actuelle utilise un format personnalisé. Elle reste intacte ; utilisez la page native « Configuration &gt; Divers » de Dolibarr pour la modifier.</p>';
            $html .= '</div><button type="submit" class="button">Appliquer</button>';
        } elseif ($type === 'integer') {
            $html .= '<label class="dolibarrfulloption-field-label" for="dfo-'.self::e($key).'">Valeur</label>';
            $html .= '<input id="dfo-'.self::e($key).'" type="number" name="value" step="1" value="'.self::e($value).'" placeholder="Nombre entier">';
            $html .= '<button type="submit" class="button">Appliquer</button>';
        } elseif ($type === 'url') {
            $html .= '<label class="dolibarrfulloption-field-label" for="dfo-'.self::e($key).'">Adresse HTTPS</label>';
            $html .= '<input id="dfo-'.self::e($key).'" type="url" name="value" maxlength="500" value="'.self::e($value).'" placeholder="https://exemple.fr/page-ou-fichier">';
            $html .= '<button type="submit" class="button">Appliquer</button>';
        } else {
            $html .= '<label class="dolibarrfulloption-field-label" for="dfo-'.self::e($key).'">Valeur</label>';
            $html .= '<input id="dfo-'.self::e($key).'" type="text" name="value" maxlength="500" value="'.self::e($value).'" placeholder="Saisir une valeur">';
            $html .= '<button type="submit" class="button">Appliquer</button>';
        }

        if ($entry['risk'] === 'attention') {
            $html .= '<label class="dolibarrfulloption-confirm"><input type="checkbox" name="confirm_risk" value="1"> Je comprends l’effet de ce réglage</label>';
        }
        $html .= '</form>';

        if ($settings->hasLocalOverride($key)) {
            $html .= '<form method="post" action="" class="dolibarrfulloption-reset-form">';
            $html .= self::hiddenFields($entry, $tab, $query, 'reset');
            $html .= '<button type="submit">Revenir au défaut</button></form>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function enumLabel($key, $choice)
    {
        if ($key === 'MAIN_FEATURES_LEVEL') {
            $labels = array('-1' => 'Stables + anciennes', '0' => 'Stables uniquement', '1' => 'Avec fonctions expérimentales', '2' => 'Avec fonctions en développement');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'MAIN_SHOW_REGION_IN_STATE_SELECT') {
            $labels = array('0' => 'Ne pas afficher la région', '1' => 'Région dans la liste et les adresses', '2' => 'Région dans les adresses');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'TICKET_AUTO_ASSIGN_USER_CREATE') {
            $labels = array('0' => 'Ne pas attribuer', '1' => 'Attribuer si personne n’est choisi', '2' => 'Toujours attribuer au créateur');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'TICKET_ADD_AUTHOR_AS_CONTACT') {
            $labels = array('0' => 'Ne pas ajouter', '1' => 'Ajouter comme contact interne', '2' => 'Ajouter comme contact externe si applicable');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'MAIN_DISABLE_FORCE_SAVEAS') {
            $labels = array('0' => 'Téléchargement habituel', '1' => 'Afficher dans le navigateur', '2' => 'Ouvrir dans un nouvel onglet');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES') {
            $labels = array('0' => 'Interne puis fournisseur', '1' => 'Fournisseur uniquement', '2' => 'Fournisseur puis interne');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        if ($key === 'MAIN_WAREHOUSE_LIST_DISPLAY_MODE') {
            $labels = array('0' => 'Hiérarchie complète', '1' => 'Entrepôt seul', '2' => 'Dernier parent et entrepôt');
            return isset($labels[$choice]) ? $labels[$choice] : $choice;
        }
        return $choice;
    }

    public static function option($entry, $settings, $major, $tab, $query)
    {
        $supported = DolibarrFullOptionCatalog::supports($entry, $major);
        $editable = DolibarrFullOptionCatalog::editable($entry, $major);
        $html = '<tr class="oddeven dolibarrfulloption-option'.($supported ? '' : ' is-historical').'">';
        $html .= '<td class="dolibarrfulloption-option-main"><strong>'.self::e($entry['title_fr']).'</strong>';
        $html .= '<div class="opacitymedium dolibarrfulloption-key">'.self::e($entry['key']).'</div>';
        $inventory = in_array($entry['source'], array('doliconstdoc', 'code-index'), true);
        if ($entry['description_fr'] !== '') {
            $html .= '<div class="dolibarrfulloption-description">'.self::e($entry['description_fr']).'</div>';
        } elseif (!$inventory) {
            $html .= '<div class="dolibarrfulloption-description">Le comportement de cette constante n’est pas suffisamment décrit pour proposer une modification fiable.</div>';
        }
        if ($inventory && !empty($entry['purpose_en'])) {
            $html .= '<details class="dolibarrfulloption-original"><summary>Version originale (anglais)</summary><p>'.self::e($entry['purpose_en']).'</p></details>';
        }
        $html .= '<div class="dolibarrfulloption-meta">';
        $html .= '<span class="opacitymedium">'.($supported ? 'V'.$major : (in_array($entry['source'], array('doliconstdoc', 'code-index'), true) ? 'Non repérée en V'.$major : 'Absente en V'.$major)).'</span>';
        if ($entry['risk'] !== 'normal') {
            $html .= '<span class="opacitymedium">'.($entry['risk'] === 'bloquée' ? 'Lecture seule : sécurité' : 'Attention').'</span>';
        }
        if (!empty($entry['source_url'])) {
            $html .= '<a href="'.self::e($entry['source_url']).'" target="_blank" rel="noopener noreferrer">'.($entry['source'] === 'wiki' ? 'Wiki' : ($entry['source'] === 'module' ? 'Module' : 'Code Dolibarr')).'</a>';
        }
        $html .= '</div></td><td class="dolibarrfulloption-action">';
        if ($editable) {
            $html .= self::control($entry, $settings, $major, $tab, $query);
        } else {
            if ($entry['source'] === 'instance' || (in_array($entry['source'], array('doliconstdoc', 'code-index'), true) && $supported)) {
                $html .= '<a href="'.self::e(dol_buildpath('/admin/const.php', 1)).'">Ouvrir Divers pour modifier</a>';
            } else {
                $html .= '<div class="dolibarrfulloption-readonly">'.($supported ? 'Réglage en cours de vérification' : 'Non disponible dans cette version').'</div>';
            }
        }
        $html .= '</td></tr>';
        return $html;
    }
}
