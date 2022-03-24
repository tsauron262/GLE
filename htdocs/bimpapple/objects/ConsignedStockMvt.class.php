<?php

class ConsignedStockMvt extends BimpObject
{

    // Droits users: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'cancel':
                return $this->isUserAdmin();
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
//        if (in_array($action, array(''))) {
//            if (!$this->isLoaded($errors)) {
//                return 0;
//            }
//        }

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

    public function getListsBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('cancel')) {
            $actions[] = array(
                'label'   => 'Annuler',
                'icon'    => 'fas_times-circle',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'cancel\', {}, null, \'Veuillez confirmer l\\\'annulation des mouvements de stock sélectionnés\', true)'
            );
        }

        return $actions;
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

        $ids = array();

        if ($this->isLoaded()) {
            $ids[] = (int) $this->id;
        } else {
            $ids = BimpTools::getArrayValueFromPath($data, 'id_objects', array());
        }

        if (empty($ids)) {
            $errors[] = 'Aucun mouvement à annuler sélectionné';
        } else {
            $nOk = 0;
            foreach ($ids as $id_mvt) {
                $mvt = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_mvt);

                if (BimpObject::objectLoaded($mvt)) {
                    $mvt_errors = array();
                    $stock = $mvt->getChildObject('stock');

                    if (BimpObject::objectLoaded($stock)) {
                        $serial = $mvt->getData('serial');
                        $qty = (int) $mvt->getData('qty') * -1;
                        if ($qty) {
                            $code_mvt = 'ANNUL_' . $mvt->id;
                            $desc = 'Annulation mouvement #' . $mvt->id . BimpRender::renderObjectIcons($mvt, false, 'default');

                            $mvt_errors = $stock->correctStock($qty, $serial, $code_mvt, $desc);

                            if (!count($errors)) {
                                $mvt->updateField('cancelled', 1);
                                $nOk++;
                            }
                        }
                    } else {
                        $mvt_errors[] = 'Le stock consigné correspondant n\'existe plus. Annulation impossible';
                    }

                    if (count($mvt_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($mvt_errors, 'Echec annulation mouvement #' . $id_mvt);
                    }
                } else {
                    $warnings[] = 'Le mouvement de stock #' . $id_mvt . ' n\'existe plus';
                }
            }

            if ($nOk > 0) {
                if ($nOk > 1) {
                    $success = $nOk . ' mouvements inverses effectués avec succès';
                } else {
                    $success = 'Mouvement inverse effectué avec succès';
                }
            } elseif (empty($errors)) {
                $errors[] = 'Aucune annulation n\'a été effectuée';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'triggerObjectChange(\'bimpapple\', \'ConsignedStock\')'
        );
    }
}
