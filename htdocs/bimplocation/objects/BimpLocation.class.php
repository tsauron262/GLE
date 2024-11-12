<?php

class BimpLocation extends BimpObject
{

    const STATUS_CANCELED = -1;
    const STATUS_BROUILLON = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 10;

    public static $status_list = array(
        self::STATUS_CANCELED  => array('label' => 'Annulée', 'icon' => 'fas_exclamation-circle', 'classes' => array('danger')),
        self::STATUS_BROUILLON => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        self::STATUS_VALIDATED => array('label' => 'Validée', 'icon' => 'fas_check', 'classes' => array('info')),
        self::STATUS_CLOSED    => array('label' => 'Terminée', 'icon' => 'fas_times', 'classes' => array('success'))
    );

    // Getters booléens : 

    public function areLinesEditable()
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        if (in_array((int) $this->getData('status'), array(self::STATUS_CANCELED, self::STATUS_CLOSED))) {
            return 0;
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'cancel':
                if ((int) $this->getData('status') === self::STATUS_CANCELED) {
                    $errors[] = 'Cette location est déjà annulée';
                    return 0;
                }
                return 1;

            case 'reopen':
                if ((int) $this->getData('status') !== self::STATUS_CANCELED) {
                    $errors[] = 'Cette location n\'est pas annulée';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler',
                'icon'    => 'fas_times',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'confirm_msg' => 'Veuillez confirmer l\\\'annulation'
                ))
            );
        }
        
        if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
            $buttons[] = array(
                'label'   => 'Réouvrir',
                'icon'    => 'fas_undo',
                'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                    'confirm_msg' => 'Veuillez confirmer la réouverture'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBtn()
    {
        $buttons = array();

        return $buttons;
    }

    // Getters array : 

    public function getClientContactsArray($include_empty = true, $active_only = true)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
        }

        return array();
    }

    // Rendus HTML

    public function renderMontants()
    {
        $html = '';

        if ($this->isLoaded()) {
            $total_ht = 0;
            $total_tva = 0;
            $total_ttc = 0;

            $lines = $this->getChildrenObjects('lines');

            if (!empty($lines)) {
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th>Equipement</th>';
                $html .= '<th>PU HT</th>';
                $html .= '<th>TVA</th>';
                $html .= '<th>Nb jours</th>';
                $html .= '<th>Total HT</th>';
                $html .= '<th>Total TTC</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($lines as $line) {
                    $amounts = $line->getAmounts();
                    $html .= '<td>';
                    $html .= $line->displayDataDefault('id_equipment');
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($amounts['pu_ht'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayFloatValue($amounts['tva_tx'], '', false, false, false, 2, 1, ',', 1) . ' %';
                    $html .= '</td>';

                    $html .= '<td><span class="badge badge-info">' . $amounts['qty'] . '</span></td>';

                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($amounts['total_ht'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</td>';

                    $html .= '<td<b>>';
                    $html .= BimpTools::displayMoneyValue($amounts['total_ttc'], '', false, false, false, 2, 1, ',', 1);
                    $html .= '</b></td>';

                    $total_ht += $amounts['total_ht'];
                    $total_tva += $amounts['$total_tva'];
                    $total_ttc += $amounts['$total_ttc'];
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }

            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody class="headers_col">';
            $html .= '<tr>';
            $html .= '<th>Total HT</th>';
            $html .= '<td>';
            $html .= BimpTools::displayMoneyValue($total_ht, '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TVA</th>';
            $html .= '<td>';
            $html .= BimpTools::displayMoneyValue($total_tva, '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Total TTC</th>';
            $html .= '<td style="font-size: 14px; font-weight: bold">';
            $html .= BimpTools::displayMoneyValue($total_ttc, '', false, false, false, 2, 1, ',', 1);
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';
        }

        $title = BimpRender::renderIcon('fas_euro-sign', 'iconLeft') . 'Montants';
        return BimpRender::renderPanel($title, $html, '', array());
    }

    // Actions: 
    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Annulation effectuée avec succès';

        $this->set('status', self::STATUS_CANCELED);
        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        $this->set('status', self::STATUS_BROUILLON);
        $errors = $this->update($warnings, true);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    // Overrides

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $this->updateField('ref', 'LOC' . $this->id);
        }

        return $errors;
    }
}
