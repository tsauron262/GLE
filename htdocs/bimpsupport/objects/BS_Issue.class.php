<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

class BS_Issue extends BimpObject
{

    protected $issueCodes = null;
    public static $priorities = array(
        1 => array('label' => 'Haute', 'classes' => array('danger')),
        2 => array('label' => 'Moyenne', 'classes' => array('warning')),
        3 => array('label' => 'Faible', 'classes' => array('info'))
    );
    public static $types = array(
        'TECH' => 'Constaté et vérifié par le technicien',
        'CUST' => 'Rapporté par le client'
    );

    // Getters booléens: 

    public function isCartEditable()
    {
        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            return (int) $sav->isPropalEditable();
        }

        return 0;
    }

    public function canDelete()
    {
        return 1;
    }

    public function isDeletable()
    {
        $parts = $this->getChildrenList('parts', array(
            'id_issue' => (int) $this->id
        ));

        if (count($parts)) {
            return $this->isCartEditable();
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'type':
                if ($this->isLoaded()) {
                    if ($this->isTierPart()) {
                        return 0;
                    }
                }
                break;

            case 'category_code':
            case 'issue_code':
                if ($this->isLoaded()) {
                    return 0;
                }
                break;
        }
        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'addParts':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if (!$this->isCartEditable()) {
                    $errors[] = 'Le devis du SAV n\'est plus éditable';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isTierPart()
    {
        if ($this->isLoaded()) {
            if (!(string) $this->getData('category_code')) {
                return 1;
            }
        }

        return (int) BimpTools::getPostFieldValue('tier_part', 0);
    }

    // Getters array: 

    public function getReproducibilitiesArray()
    {
        return GSX_v2::$reproducibilities;
    }

    public function getIssueCodesArray()
    {
        if (is_null($this->issueCodes)) {
            $sav = $this->getParentInstance();
            if (BimpObject::objectLoaded($sav)) {
                $serial = $sav->getSerial();
                if ($serial) {
                    $cache_key = 'gsx_issue_codes_' . $serial;
                    if (BimpCache::cacheExists($cache_key)) {
                        $this->issueCodes = BimpCache::getCacheArray($cache_key);
                    } else {
                        $gsx = GSX_v2::getInstance();

                        // En principe, ici, le fait de ne pas être loggué à gsx devrait arriver très rarement
                        // (Le login est checké au chargement du formulaire. 

                        if ($gsx->logged) {
                            $result = $gsx->getIssueCodesBySerial($serial);
                            if (isset($result['componentIssues'])) {
                                $this->issueCodes = array();
                                foreach ($result['componentIssues'] as $categ) {
                                    $this->issueCodes[$categ['componentCode']] = array(
                                        'label'  => $categ['componentDescription'],
                                        'issues' => array()
                                    );
                                    foreach ($categ['issues'] as $issue) {
                                        $this->issueCodes[$categ['componentCode']]['issues'][$issue['code']] = $issue['description'];
                                    }
                                }
                                BimpCache::$cache[$cache_key] = $this->issueCodes;
                            }
                        }
                    }
                }
            }
        }

        return $this->issueCodes;
    }

    // Getters params: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        $sav = $this->getParentInstance();
        if (BimpObject::objectLoaded($sav)) {
            $issues = $sav->getChildrenList('issues');
            if (count($issues) < 6) {
                $buttons[] = array(
                    'label'       => 'Ajouter un problème composant',
                    'icon_before' => 'fas_plus-circle',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => 'gsx_loadAddIssueForm($(this), ' . (int) $sav->id . ')'
                    )
                );
            } else {
                $buttons[] = array(
                    'label'       => 'Ajouter un problème composant',
                    'icon_before' => 'fas_plus-circle',
                    'classes'     => array('btn', 'btn-default', 'disabled', 'bs-popover'),
                    'data'        => array(
                        'toggle'    => 'popover',
                        'trigger'   => 'hover',
                        'container' => 'body',
                        'html'      => 'true',
                        'placement' => 'top',
                        'content'   => '<span class="danger">Vous ne pouvez ajouter que 6 problèmes</span>'
                    )
                );
            }
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->isCartEditable()) {
                $buttons[] = array(
                    'label'   => 'Ajouter un composant',
                    'icon'    => 'fas_plus',
                    'onclick' => 'gsx_loadAddPartsForm($(this), ' . (int) $this->id . ')'
                );
            }

            $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');
            $buttons[] = array(
                'label'   => 'Liste des composants',
                'icon'    => 'fas_bars',
                'onclick' => $part->getJsLoadModalList('gsx_v2', array(
                    'extra_filters' => array(
                        'a.id_issue' => (int) $this->id
                    )
                ))
            );
        }

        return $buttons;
    }

    // Affichages: 

    public function displayIssue()
    {
        if ($this->isLoaded()) {
            $label = $this->getData('issue_code');
            if ($this->getData('issue_label')) {
                $label .= ($label ? ' - ' : '') . $this->getData('issue_label');
            }

            return $label;
        }

        return '';
    }

    public function displayParts()
    {
        $html = '';

        if ($this->isLoaded()) {
            $parts = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_ApplePart', array(
                        'id_issue' => (int) $this->id
            ));

            if (!count($parts)) {
                $html .= BimpRender::renderAlerts('Aucun composant ajouté pour ce problème', 'info');
            } else {
                $is_editable = $this->isCartEditable();

                $html .= '<table class="objectSubList">';
                $html .= '<thead>';
                $html .= '<th>Ref.</th>';
                $html .= '<th>Libellé</th>';
                $html .= '<th>Qté</th>';
                $html .= '<th>Hors garantie</th>';
                $html .= '<th>Prix</th>';
                $html .= '<th>Cmde stock</th>';
                $html .= '<th></th>';
                $html .= '</thead>';
                $html .= '<tbody>';

                foreach ($parts as $part) {
                    $html .= '<tr>';
                    $html .= '<td>' . $part->displayData('part_number') . '</td>';
                    $html .= '<td>' . $part->displayData('label') . '</td>';
                    $html .= '<td>' . $part->displayData('qty') . '</td>';
                    $html .= '<td>' . $part->displayData('out_of_warranty') . '</td>';
                    $html .= '<td>' . $part->displayData('price_type') . '</td>';
                    $html .= '<td>' . $part->displayData('no_order') . '</td>';
                    $html .= '<td>';
                    $html .= BimpRender::renderRowButton('Vue rapide', 'fas_eye', $part->getJsLoadModalView('default', 'Composant ' . $part->getData('part_number')));
                    if ($is_editable) {
                        $html .= BimpRender::renderRowButton('Supprimer', 'fas_trash-alt', 'deleteObject($(this), \'bimpsupport\', \'BS_ApplePart\', ' . (int) $part->id . ');');
                    }
                    $html .= '</td>';

                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderCategoryCodeInput()
    {
        $html = '';

        $codes = $this->getIssueCodesArray();

        if (is_null($codes) || empty($codes)) {
            $html = BimpRender::renderAlerts('Echec de la récupération des codes. Vous n\'êtes probablement plus connecté à GSX. Veuillez fermer ce formulaire et le réouvrir afin de vous réauthentifier');
            $html .= '<input type="hidden" value="" name="issue_category"/>';
        } else {
            $options = array(
                '' => ''
            );

            foreach ($codes as $categ_code => $categ) {
                $options[$categ_code] = $categ_code . ' - ' . $categ['label'];
            }

            $html .= BimpInput::renderInput('select', 'category_code', (string) $this->getData('category_code'), array(
                        'options' => $options
            ));
        }

        return $html;
    }

    public function renderIssueCodeInput()
    {
        $html = '';

        $codes = $this->getIssueCodesArray();

        if (is_null($codes) || empty($codes)) {
            $html = BimpRender::renderAlerts('Echec de la récupération des codes. Vous n\'êtes probablement plus connecté à GSX. Veuillez fermer ce formulaire et le réouvrir afin de vous réauthentifier');
            $html .= '<input type="hidden" value="" name="issue_code"/>';
        } else {
            $options = array(
                '' => ''
            );

            $categ_code = (string) $this->getData('category_code');
            if ($categ_code && isset($codes[$categ_code])) {
                foreach ($codes[$categ_code]['issues'] as $code => $label) {
                    $options[$code] = $code . ' - ' . $label;
                }
            }

            $html .= BimpInput::renderInput('select', 'issue_code', (string) $this->getData('issue_code'), array(
                        'options' => $options
            ));
        }

        return $html;
    }

    public function renderFormPartsList()
    {
        $html = '';
        $errors = array();

        $serial = BimpTools::getPostFieldValue('serial', '');
        $issue_category = BimpTools::getPostFieldValue('issue_category', '');
        $issue_code = BimpTools::getPostFieldValue('issue_code', '');

        if (!$serial) {
            $errors[] = 'N° de série absent';
        }

        if (!$issue_category) {
            $errors[] = 'Catégorie du problème absent';
        }

        if (!$issue_code) {
            $errors[] = 'Code problème absent';
        }

        if (!count($errors)) {
            if (!class_exists('GSX_v2')) {
                require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
            }

            $gsx = new GSX_v2();

            if ($gsx->logged) {
                $result = $gsx->partsSummaryBySerialAndIssueCode($serial, $issue_category, $issue_code);

                if (!$result) {
                    echo '<pre>';
                    print_r($gsx->getErrors());
                    exit;
                }
                echo '<pre>';
                print_r($result);
                exit;

                if (isset($result['parts'])) {
                    if (isset($params['partNumberAsKey']) && (int) $params['partNumberAsKey']) {
                        $parts = array();
                        foreach ($result['parts'] as $part) {
                            $parts[$part['partNumber']] = $part;
                        }
                    } else {
                        $parts = $result['parts'];
                    }
                } else {
                    $errors = $this->gsx_v2->getErrors();
                }
            } else {
                $errors[] = 'Vous n\'êtes plus connecté à GSX. Veuillez fermer ce formulaire et le réouvrir afin de vous réauthentifier';
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    // Actions: 

    public function actionAddParts($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Composant(s) ajouté(s) avec succès';

        if (!isset($data['parts']) || empty($data['parts'])) {
            $warnings[] = 'Aucun composant sélectionné';
        } else {
            $sav = $this->getParentInstance();

            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'ID du SAV absent';
            } else {
                $current_parts = $sav->getChildrenList('parts');

                if ((count($current_parts) + count($data['parts'])) > 25) {
                    $msg = 'Il n\'est possible d\'ajouter que 25 composants par SAV. ';
                    $diff = (count($current_parts) + count($data['parts'])) - 25;
                    if ($diff > 0) {
                        $msg .= 'Veuillez déselectionner ' . $diff . ' composant(s)';
                    } else {
                        $msg .= 'Vous ne pouvez plus aujouter d\'autres composants';
                    }
                    $errors[] = $msg;
                } else {
                    foreach ($data['parts'] as $part_data) {
                        $part = BimpObject::getInstance('bimpsupport', 'BS_ApplePart');

                        $part_warnings = array();

                        $part_data['stock_price'] = str_replace(",", "", $part_data['stock_price']);
                        $part_errors = $part->validateArray(array(
                            'id_sav'          => (int) $sav->id,
                            'id_issue'        => (int) $this->id,
                            'part_number'     => $part_data['part_number'],
                            'new_part_number' => (isset($part_data['new_part_number']) ? $part_data['new_part_number'] : ''),
                            'label'           => $part_data['label'],
                            'stock_price'     => $part_data['stock_price'],
                            'exchange_price'  => $part_data['exchange_price'],
                            'price_options'   => $part_data['price_options'],
                        ));

                        if (!count($part_errors)) {
                            $part_errors = $part->create($part_warnings, false);
                        }

                        if (count($part_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($part_errors, 'Echec de la création ' . $part->getLabel('of_the') . ' "' . $part_data['part_number'] . '"');
                        }
                        if (count($part_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($part_warnings, 'Erreurs suite à la création ' . $part->getLabel('of_the') . ' "' . $part_data['part_number'] . '"');
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            if (!(int) BimpTools::getPostFieldValue('tier_part', 0)) {
                if (!$this->getData('category_code')) {
                    $errors[] = 'Veuillez sélectionner un code catégorie';
                }
                if (!$this->getData('issue_code')) {
                    $errors[] = 'Veuillez sélectionner un code problème';
                }

                if (!count($errors)) {
                    if ($this->getData('category_code') !== $this->getInitData('category_code')) {
                        $codes = $this->getIssueCodesArray();
                        $cat = (string) $this->getData('category_code');
                        if ($cat && is_array($codes) && isset($codes[$cat]['label'])) {
                            $this->set('category_label', $codes[$cat]['label']);
                        } else {
                            $this->set('category_label', $cat);
                        }
                    }

                    if ($this->getData('issue_code') !== $this->getInitData('issue_code')) {
                        $codes = $this->getIssueCodesArray();
                        $cat = (string) $this->getData('category_code');
                        $code = (string) $this->getData('issue_code');
                        if ($cat && $code && is_array($codes) && isset($codes[$cat]['issues'][$code])) {
                            $this->set('issue_label', $codes[$cat]['issues'][$code]);
                        } else {
                            $this->set('issue_label', $code);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        if ((int) BimpTools::getPostFieldValue('tier_part', 0)) {
            $this->set('category_code', '');
            $this->set('category_label', 'Composants tiers');
            $this->set('issue_code', '');
            $this->set('issue_label', 'Non applicable');
            $this->set('reproducibility', 'A');
        } else {
            $sav = $this->getParentInstance();
            if (!BimpObject::objectLoaded($sav)) {
                $errors[] = 'ID du SAV absent';
                return $errors;
            }

            $issues = $sav->getChildrenList('issues');
            if (count($issues) >= 6) {
                $errors[] = 'Vous ne pouvez ajouter que 6 problèmes pour ce SAV';
                return $errors;
            }
        }

        return parent::create($warnings, $force_create);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $parts = BimpCache::getBimpObjectObjects('bimpsupport', 'BS_ApplePart', array(
                        'id_issue' => $id
            ));

            foreach ($parts as $part) {
                $del_warnings = array();
                $del_errors = $part->delete($del_warnings, true);

                if (count($del_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression du composant "' . $part->getData('part_number') . '"');
                }
            }
        }

        return $errors;
    }
}
