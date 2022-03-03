<?php

class ConsignedStockMvt extends BimpObject
{

    // Droits users: 

    public function canSetAction($action)
    {
        global $user;

        switch ($action) {
            case 'cancel';
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('cancel'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        switch ($action) {
            case 'cancel':
                if ((int) $this->getData('cancelled')) {
                    $errors[] = 'Ce mouvement a déjà été annulé';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
            $buttons[] = array(
                'label'   => 'Annuler ce mouvement',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                    'confirm_msg' => 'Un mouvement inverse va être créé. Veuillez confirmer'
                ))
            );
        }

        return $buttons;
    }

    // Affichages: 

    public function displayBadgeQty()
    {
        $qty = (int) $this->getData('qty');
        return '<span class="badge badge-' . ($qty > 0 ? 'success' : 'danger') . '">' . ($qty > 0 ? '+ ' : ($qty < 0 ? '- ' : '')) . abs($qty) . '</span>';
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mouvement inverse effectué avec succès';

        $stock = $this->getChildObject('stock');

        if (BimpObject::objectLoaded($stock)) {
            $serial = $this->getData('serial');
            $qty = (int) $this->getData('qty') * -1;
            if ($qty) {
                $code_mvt = 'ANNUL_' . $this->id;
                $desc = 'Annulation mouvement #' . $this->id . BimpRender::renderObjectIcons($this, false, 'default');

                $errors = $stock->correctStock($qty, $serial, $code_mvt, $desc);

                if (!count($errors)) {
                    $this->updateField('cancelled', 1);
                }
            }
        } else {
            $errors[] = 'Le stock consigné correspondant n\'existe plus. Annulation impossible';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpapple\', \'ConsignedStock\')'
        );
    }
}
