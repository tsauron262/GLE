<?php

class BF_Refinanceur extends BimpObject
{

    public function canDelete()
    {
        global $user;
        return $user->admin;
    }

    public function getName($withGeneric = true)
    {
        $soc = $this->getChildObject('societe');

        if (BimpObject::objectLoaded($soc)) {
            return $soc->getName();
        }

        return parent::getName($withGeneric);
    }

    public function getTaux($amount_ht)
    {
        $taux = $this->getData('taux');

        if (!empty($taux)) {
            $return = 0;

            foreach ($taux as $min_amount_ht => $percent) {
                if ($amount_ht < $min_amount_ht) {
                    break;
                }

                $return = $percent;
            }

            return $return;
        }

        return 0;
    }

    public function displayTaux()
    {
        $html = '';

        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody class="headers_col">';

        $demande_class = '';
        BimpObject::loadClass('bimpfinancement', 'BF_Demande', $demande_class);
        $prev_amount = 0;
        $taux = $this->getData('taux');
        foreach ($demande_class::$marges as $amount => $marge) {
            if (!$amount) {
                continue;
            }

            $html .= '<tr>';
            $html .= '<th><b>De ' . $prev_amount . ' € à ' . $amount . ' € :</th>';
            $html .= '<td>' . (isset($taux[$prev_amount]) ? BimpTools::displayFloatValue($taux[$prev_amount]) : '0,00') . ' %</td>';
            $html .= '</tr>';

            $prev_amount = $amount;
        }

        $html .= '<tr>';
        $html .= '<th><b>&gt; ' . $prev_amount . ' € :</th>';
        $html .= '<td>' . (isset($taux[$prev_amount]) ? BimpTools::displayFloatValue($taux[$prev_amount]) : '0,00') . ' %</td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderTauxInputs()
    {
        $html = '';

        $demande_class = '';
        BimpObject::loadClass('bimpfinancement', 'BF_Demande', $demande_class);
        $prev_amount = 0;
        $taux = $this->getData('taux');
        foreach ($demande_class::$marges as $amount => $marge) {
            if (!$amount) {
                continue;
            }

            $html .= ($html ? '<br/><br/>' : '') . '<b>De ' . $prev_amount . ' € à ' . $amount . ' € :</b><br/>';
            $html .= BimpInput::renderInput('text', 'taux_' . $prev_amount, (isset($taux[$prev_amount]) ? $taux[$prev_amount] : 0), array(
                        'data'        => array(
                            'data_type' => 'number',
                            'decimals'  => 2,
                            'min'       => 0,
                            'max'       => 100
                        ),
                        'addon_right' => BimpRender::renderIcon('fas_percent')
            ));

            $prev_amount = $amount;
        }

        $html .= ($html ? '<br/><br/>' : '') . '<b>&gt; ' . $prev_amount . ' € :</b><br/>';
        $html .= BimpInput::renderInput('text', 'taux_' . $prev_amount, (isset($taux[$prev_amount]) ? $taux[$prev_amount] : 0), array(
                    'data'        => array(
                        'data_type' => 'number',
                        'decimals'  => 2,
                        'min'       => 0,
                        'max'       => 100
                    ),
                    'addon_right' => BimpRender::renderIcon('fas_percent')
        ));

        return $html;
    }

    public function validatePost()
    {
        $errors = parent::validatePost();

        if (!count($errors)) {
            $demande_class = '';
            BimpObject::loadClass('bimpfinancement', 'BF_Demande', $demande_class);

            $taux = array();
            foreach ($demande_class::$marges as $amount => $marge) {
                $taux[$amount] = (float) BimpTools::getPostFieldValue('taux_' . $amount, 0, 'float');
            }

            $this->set('taux', $taux);
        }

        return $errors;
    }

    public static function getTauxMoyen($total_demande_ht)
    {
        $total_tx = 0;
        $nb_refin = 0;

        $filters = array();

        foreach (BimpCache::getBimpObjectObjects('bimpfinancement', 'BF_Refinanceur', $filters) as $refin) {
            if (BimpObject::objectLoaded($refin)) {
                $tx = $refin->getTaux($total_demande_ht);

                if ($tx) {
                    $total_tx += $tx;
                    $nb_refin++;
                }
            }
        }

        if ($nb_refin > 0) {
            return $total_tx / $nb_refin;
        }

        return 0;
    }
}
