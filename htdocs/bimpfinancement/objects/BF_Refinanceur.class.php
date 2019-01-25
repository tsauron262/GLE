<?php

class BF_Refinanceur extends BimpObject
{

    // Getters overrides: 

    public function getName()
    {
        $soc = $this->getChildObject('societe');

        if (BimpObject::objectLoaded($soc)) {
            return $soc->getName();
        }

        return parent::getName();
    }

    // rendus HTML: 

    public function renderCoefsForm($content_only)
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $buttons = '';
        $ranges = '';

        if (!$content_only) {
            $buttons .= '<div style="text-align: right">';
            $buttons .= '<button id="cancelCoefsModifsButton" class="btn btn-danger" type="button" onclick="cancelCoefModifs($(this))">';
            $buttons .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler les modifications';
            $buttons .= '</button>';
            $buttons .= '<button id="saveCoefsModifsButton" class="btn btn-primary" type="button" onclick="saveCoefModifs($(this))">';
            $buttons .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer';
            $buttons .= '</button>';
            $buttons .= '</div>';

            BimpObject::loadClass($this->module, 'BF_DemandeRefinanceur');

            $tabs = array();
            $coefs = $this->getData('coefs');
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
                        '<button type="button" class="btn btn-danger" onclick="$(\'#newCoefsPeriodRangeForm\').stop().slideUp(250);">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler</button>',
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
                                    'decimals'  => 0,
                                    'min'       => 0
                                ),
                                'addon_left'  => '<=',
                                'addon_right' => BimpRender::renderIcon('fas_euro-sign')
                            ))
                        )
                            ), array(
                        '<button type="button" class="btn btn-danger" onclick="$(\'#newCoefsAmountRangeForm\').stop().slideUp(250);">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Annuler</button>',
                        '<button type="button" class="btn btn-primary" onclick="addNewCoefsAmountRange($(this));">' . BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter</button>'
                            ), 'Nouvelle tranche de montant', 'fas_plus-circle');
            $ranges .= '</div>';
            $ranges .= '</div>';
        }

        foreach (BF_DemandeRefinanceur::$periodicities as $nb_month => $label) {
            $content = '';
            $content .= '<table class="bimp_list_table coefsRangesTable" style="width: auto;">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<td style="width: 120px;"></td>';
            if (isset($coefs[$nb_month]) && !empty($coefs[$nb_month])) {
                foreach ($coefs[$nb_month] as $amount_value => $periods) {
                    $prev_value = 0;
                    foreach ($periods as $period_value => $coef) {
                        if ($period_value === 'last') {
                            $content .= '<th data-period="last">&gt; ' . $prev_value . ' mois</th>';
                        } else {
                            $content .= '<th data-period="' . $period_value . '">&lt;= ' . $period_value . ' mois</th>';
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
                    if ($amount_value === 'last') {
                        $content .= '<tr class="coefs_amount_row" data-amount="last">';
                        $content .= '<th style="width: 120px;">&gt; ' . BimpTools::displayMoneyValue($prev_value, 'EUR') . '</th>';
                    } else {
                        $content .= '<tr class="coefs_amount_row" data-amount="' . $amount_value . '">';
                        $content .= '<th style="width: 120px;">&lt;= ' . BimpTools::displayMoneyValue($amount_value, 'EUR') . '</th>';
                    }

                    foreach ($periods as $period_value => $coef) {
                        $content .= '<td>';
                        $content .= BimpInput::renderInput('text', 'coef', $coef, array(
                                    'data'        => array(
                                        'data-type' => 'number',
                                        'decimals'  => 6
                                    ),
                                    'extra_class' => 'coef_value'
                        ));
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
        
        if ($content_only) {
            return BimpRender::renderNavTabs($tabs, 'coefs');
        }
        
        $html .= '<div id="coefs_ranges_content">';
        $html .= $ranges . BimpRender::renderNavTabs($tabs, 'coefs');
        $html .= '</div>';

        return BimpRender::renderPanel('Coefficients', $html, $buttons, array(
                    'type' => 'secondary',
                    'icon' => 'fas_calculator'
        ));
    }
}
