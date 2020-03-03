<?php

class BF_Refinanceur extends BimpObject
{

    // Getters: 

    public function getCoefs()
    {
        $coefs = $this->getData('coefs');

        BimpObject::loadClass($this->module, 'BF_DemandeRefinanceur');

        if (is_null($coefs) || !is_array($coefs) || empty($coefs)) {
            $coefs = array();
            foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
                $coefs[$nb_month] = array(
                    'last' => array(
                        'last' => 0
                    )
                );
            }
        }

        return $coefs;
    }

    public function getCoef($nb_month, $amount, $period)
    {
        $coefs = $this->getCoefs();

        if (!isset($coefs[(int) $nb_month])) {
            return 0;
        }

        foreach ($coefs[(int) $nb_month] as $amount_range => $periods) {
            if ($amount_range === 'last' || (float) $amount < (float) $amount_range) {
                foreach ($periods as $period_range => $coef) {
                    if ($period_range === 'last' || (int) $period < (int) $period_range) {
                        return $coef;
                    }
                }
            }
        }
        
        return 0;
    }

    // Getters overrides: 

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'coefs':
                return 1;
        }

        return parent::canEditField($field_name);
    }

    public function getName()
    {
        $soc = $this->getChildObject('societe');

        if (BimpObject::objectLoaded($soc)) {
            return $soc->getName();
        }

        return parent::getName();
    }

    public function getExtraObjectIcons()
    {
        $html = '';
        if ($this->isLoaded()) {
            $title = 'Coefficients ' . str_replace('"', '\\\'\\\'', addslashes($this->getName()));
            $onclick = 'loadModalView(\'' . $this->module . '\', \'' . $this->object_name . '\', ' . $this->id . ', \'coefs\', $(this), \'' . $title . '\')';
            $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_calculator');
            $html .= '</span>';
        }

        return $html;
    }

    // Affichages: 

    public function displayCoefs()
    {
        $tabs = array();

        BimpObject::loadClass($this->module, 'BF_DemandeRefinanceur');

        $coefs = $this->getCoefs();

        foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
            $content = '';
            $content .= '<table class="bimp_list_table" style="width: auto;">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<td style="width: 120px;"></td>';
            if (isset($coefs[$nb_month]) && !empty($coefs[$nb_month])) {
                foreach ($coefs[$nb_month] as $amount_value => $periods) {
                    $prev_value = 0;
                    foreach ($periods as $period_value => $coef) {
                        if ($period_value === 'last') {
                            $content .= '<th>&gt; ' . $prev_value . ' mois</th>';
                        } else {
                            $content .= '<th>&lt;= ' . $period_value . ' mois</th>';
                        }
                        $prev_value = $period_value;
                    }
                    break;
                }
            }
            $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';

            if (!isset($coefs[$nb_month]) || empty($coefs[$nb_month])) {
                $content .= '<tr class="no_ranges_row"><td>' . BimpRender::renderAlerts('Aucune tranche définie pour l\'instant', 'info') . '</td></tr>';
            } else {
                $prev_value = 0;
                foreach ($coefs[$nb_month] as $amount_value => $periods) {
                    $content .= '<tr>';
                    if ($amount_value === 'last') {
                        $content .= '<th style="width: 120px;">&gt; ' . BimpTools::displayMoneyValue($prev_value, 'EUR') . '</th>';
                    } else {
                        $content .= '<th style="width: 120px;">&lt;= ' . BimpTools::displayMoneyValue($amount_value, 'EUR') . '</th>';
                    }

                    foreach ($periods as $period_value => $coef) {
                        $content .= '<td style="max-width: 90px;text-align: center;">';
                        $content .= $coef;
                        $content .= '</td>';
                    }

                    $content .= '</tr>';
                    $prev_value = $amount_value;
                }
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $tabs[] = array(
                'id'      => 'periodicity_' . $nb_month,
                'title'   => BimpTools::ucfirst(BF_DemandeRefinanceur::$periodicities_masc[$nb_month]),
                'content' => $content
            );
        }

        return BimpRender::renderPanel('Coefficients', BimpRender::renderNavTabs($tabs, 'coefs'), array(), array(
                    'type' => 'secondary',
                    'icon' => 'fas_calculator'
        ));
    }

    // Traitments: 

    public function setCoefs($coefs, &$warnings = array(), $update = false)
    {
        $errors = array();

        BimpObject::loadClass($this->module, 'BF_DemandeRefinanceur');

        $periods = array();
        $amounts = array();

        foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
            if (!isset($coefs[$nb_month]) || !is_array($coefs[$nb_month])) {
                $errors[] = 'Données absentes ou invalides pour la périodicité "' . $label . "";
            } else {
                foreach ($coefs[$nb_month] as $amount => $periods_data) {
                    if ($amount !== 'last' && !in_array((float) $amount, $amounts)) {
                        $amounts[] = (float) $amount;
                    }

                    foreach ($periods_data as $period => $coef) {
                        if ($period !== 'last' && !in_array((int) $period, $periods)) {
                            $periods[] = (int) $period;
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            sort($amounts);
            sort($periods);
            $amounts[] = 'last';
            $periods[] = 'last';

            $values = array();

            foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
                $values[$nb_month] = array();
                foreach ($amounts as $amount) {
                    $values[$nb_month][$amount] = array();
                    foreach ($periods as $period) {
                        if (!isset($coefs[$nb_month][$amount][$period])) {
                            $errors[] = 'Valeur absente (Périodicité "' . $label . '", ' . $period . ' mois, ' . BimpTools::displayMoneyValue($amount) . ')';
                        } else {
                            $value = $coefs[$nb_month][$amount][$period];
                            if (!BimpTools::checkValueByType('float', $value)) {
                                $errors[] = 'Valeur invalide: ' . $value . ' (Périodicité "' . $label . '", ' . $period . ' mois, ' . BimpTools::displayMoneyValue($amount) . ')';
                            } else {
                                $values[$nb_month][$amount][$period] = $value;
                            }
                        }
                    }
                }
            }

            if (!count($errors)) {
                $this->set('coefs', $values);
                if ($update) {
                    $up_errors = $this->update($warnings);

                    if (count($up_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($up_errors, 'Des erreurs sont survenues lors de la mise à jour du refinanceur');
                    }
                }
            }
        }

        return $errors;
    }

    // rendus HTML: 

    public function renderCoefsForm($content_only)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        if (!$this->canEditField('coefs')) {
            return $this->displayCoefs();
        }

        $buttons = '';
        $ranges = '';

        BimpObject::loadClass($this->module, 'BF_DemandeRefinanceur');

        $tabs = array();
        $coefs = $this->getCoefs();

        $onclick = 'deleteCoefsPeriodRange($(this));';
        $delete_col_btn_html = BimpRender::renderRowButton('Supprimer cettre tranche de durée', 'fas_trash-alt', $onclick);

        $onclick = 'deleteCoefsAmountRange($(this));';
        $delete_row_btn_html = BimpRender::renderRowButton('Supprimer cettre tranche de montant', 'fas_trash-alt', $onclick);

        if (!$content_only) {
            $buttons .= '<div style="text-align: right">';
            $buttons .= '<button id="cancelCoefsModifsButton" class="btn btn-danger" type="button" onclick="cancelCoefsModifs($(this), ' . $this->id . ')">';
            $buttons .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler les modifications';
            $buttons .= '</button>';
            $buttons .= '<button id="saveCoefsModifsButton" class="btn btn-primary" type="button" onclick="saveCoefsModifs($(this), ' . $this->id . ')">';
            $buttons .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer';
            $buttons .= '</button>';
            $buttons .= '</div>';

            $ranges .= '<div id="coefsRangesButtons" class="buttonsContainer align-right">';
            $ranges .= '<button type="button" onclick="$(\'#newCoefsPeriodRangeForm\').stop().slideDown(250);$(\'#newCoefsAmountRangeForm\').stop().slideUp(250);" class="btn btn-default">';
            $ranges .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une tranche de durée';
            $ranges .= '</button>';

            $ranges .= '<button type="button" onclick="$(\'#newCoefsAmountRangeForm\').stop().slideDown(250);$(\'#newCoefsPeriodRangeForm\').stop().slideUp(250);" class="btn btn-default">';
            $ranges .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter une tranche de montant';
            $ranges .= '</button>';
            $ranges .= '</div>';

            $ranges .= '<div style="text-align: center">';
            $ranges .= '<div id="newCoefsPeriodRangeForm" style="display: none; margin: auto; width: 600px;">';
            $ranges .= BimpRender::renderFreeForm(array(
                        array(
                            'label' => 'Durée',
                            'input' => BimpInput::renderInput('text', 'new_period_range', '', array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 0,
                                    'min'       => 0
                                ),
                                'addon_left'  => '<=',
                                'addon_right' => 'Mois'
                            ))
                        )
                            ), array(
                        '<button type="button" class="btn btn-danger" onclick="$(\'#newCoefsPeriodRangeForm\').stop().slideUp(250);">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermer</button>',
                        '<button type="button" class="btn btn-primary" onclick="addNewCoefsPeriodRange($(this))">' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter</button>'
                            ), 'Nouvelle tranche de durée', 'fas_plus-circle');
            $ranges .= '</div>';
            $ranges .= '<div id="newCoefsAmountRangeForm" style="display: none; margin: auto; width: 600px;">';
            $ranges .= BimpRender::renderFreeForm(array(
                        array(
                            'label' => 'Montant',
                            'input' => BimpInput::renderInput('text', 'new_amount_range', '', array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'decimals'  => 2,
                                    'min'       => 0
                                ),
                                'addon_left'  => '<=',
                                'addon_right' => BimpRender::renderIcon('fas_euro-sign')
                            ))
                        )
                            ), array(
                        '<button type="button" class="btn btn-danger" onclick="$(\'#newCoefsAmountRangeForm\').stop().slideUp(250);">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermer</button>',
                        '<button type="button" class="btn btn-primary" onclick="addNewCoefsAmountRange($(this));">' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter</button>'
                            ), 'Nouvelle tranche de montant', 'fas_plus-circle');
            $ranges .= '</div>';
            $ranges .= '</div>';

            $ranges .= '<div id="coef_input_template" style="display: none">';
            $ranges .= BimpInput::renderInput('text', 'coef', 0, array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 6
                        ),
                        'extra_class' => 'coef_value',
                        'style'       => 'max-width: 80px;'
            ));
            $ranges .= '</div>';

            $ranges .= '<div id="delete_row_btn_template" style="display: none">';
            $ranges .= $delete_row_btn_html;
            $ranges .= '</div>';

            $ranges .= '<div id="delete_col_btn_template" style="display: none">';
            $ranges .= $delete_col_btn_html;
            $ranges .= '</div>';
        }

        foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
            $content = '';
            $content .= '<table class="bimp_list_table coefsRangesTable" style="width: auto;" data-periodicity="' . $nb_month . '">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<td style="width: 120px;"></td>';
            if (isset($coefs[$nb_month]) && !empty($coefs[$nb_month])) {
                foreach ($coefs[$nb_month] as $amount_value => $periods) {
                    $prev_value = 0;
                    foreach ($periods as $period_value => $coef) {
                        if ($period_value === 'last') {
                            $content .= '<th class="coefs_period_col" data-period="last">&gt; ' . $prev_value . ' mois</th>';
                        } else {
                            $content .= '<th class="coefs_period_col" data-period="' . $period_value . '">&lt;= ' . $period_value . ' mois</th>';
                        }
                        $prev_value = $period_value;
                    }
                    break;
                }
            }
            $content .= '<td></td>';
            $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';

            if (!isset($coefs[$nb_month]) || empty($coefs[$nb_month])) {
                $content .= '<tr class="no_ranges_row"><td>' . BimpRender::renderAlerts('Aucune tranche définie pour l\'instant', 'info') . '</td></tr>';
            } else {
                $prev_value = 0;
                foreach ($coefs[$nb_month] as $amount_value => $periods) {
                    if ($amount_value === 'last') {
                        $content .= '<tr class="coefs_amount_row" data-amount="last">';
                        $content .= '<th style="width: 120px;">&gt; ' . BimpTools::displayMoneyValue($prev_value, 'EUR') . '</th>';
                    } else {
                        $content .= '<tr class="coefs_amount_row" data-amount="' . $amount_value . '">';
                        $content .= '<th style="width: 120px;">&lt;= ' . BimpTools::displayMoneyValue($amount_value, 'EUR') . '</th>';
                    }

                    foreach ($periods as $period_value => $coef) {
                        $content .= '<td class="coef_value" data-period="' . $period_value . '" data-amount="' . $amount_value . '" style="max-width: 90px;">';
                        $content .= BimpInput::renderInput('text', 'coef', $coef, array(
                                    'data'        => array(
                                        'data_type' => 'number',
                                        'decimals'  => 6
                                    ),
                                    'extra_class' => 'coef_value',
                                    'style'       => 'max-width: 80px;'
                        ));
                        $content .= '</td>';
                    }

                    $content .= '<td data-amount="' . $amount_value . '" style="border: none;text-align: center;">';
                    if ($amount_value !== 'last') {
                        $content .= $delete_row_btn_html;
                    } $content .= '';
                    $content .= '</td>';

                    $content .= '</tr>';
                    $prev_value = $amount_value;
                }
            }

            $content .= '<tr class="delte_col_buttons_row">';
            $content .= '<td style="border: none;"></td>';
            foreach ($periods as $period_value => $coef) {
                $content .= '<td data-period="' . $period_value . '" style="border: none">';
                if ($period_value !== 'last') {
                    $content .= $delete_col_btn_html;
                }
                $content .= '</td>';
            }
            $content .= '<td style="border: none;"></td>';
            $content .= '</tr>';


            $content .= '</tbody>';
            $content .= '</table>';

            $tabs[] = array(
                'id'      => 'periodicity_' . $nb_month,
                'title'   => BimpTools::ucfirst(BF_DemandeRefinanceur::$periodicities_masc[$nb_month]),
                'content' => $content
            );
        }

        if ($content_only) {
            return BimpRender::renderNavTabs($tabs, 'coefs');
        }

        $html .= $ranges;
        $html .= '<div id="coefs_ranges_content">';
        $html .= BimpRender::renderNavTabs($tabs, 'coefs');
        $html .= '<div id="coefsAjaxResult" class="ajaxResultContainer" style="display: none">';
        $html .= '</div>';
        $html .= '</div>';

        return BimpRender::renderPanel('Coefficients', $html, $buttons, array(
                    'type' => 'secondary',
                    'icon' => 'fas_calculator'
        ));
    }
}
