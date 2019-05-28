<?php

class BF_Frais extends BimpObject
{

    // Getters:
    public function isEditable($force_edit = false)
    {
        if ((int) $this->getData('id_facture')) {
            $facture = $this->getChildObject('facture');
            if (BimpObject::objectLoaded($facture)) {
                if ((int) $facture->getData('fk_statut') === 0) {
                    return 1;
                }
                return 0;
            }
        }
        return 1;
    }

    public function isDeletable($force_delete = false)
    {
        return $this->isEditable($force_delete);
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            $this->checkFacture();

            if (!(int) $this->getData('id_facture')) {
                $buttons[] = array(
                    'label'   => 'Ajouter à une facture',
                    'icon'    => 'fas_plus',
                    'onclick' => 'addElementsToFacture(\'' . $this->object_name . '\', ' . $this->getData('id_demande') . ', [' . $this->id . '], $(this));'
                );
            } else {
                if ($this->isActionAllowed('removeFromFacture')) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la facture',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('removeFromFacture', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait ' . $this->getLabel('of_this') . ' de la facture'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getBulkActions()
    {
        return array(
            array(
                'label'     => 'Supprimer les lignes sélectionnées',
                'icon'      => 'fas_trash-alt',
                'onclick'   => 'deleteSelectedObjects(\'list_id\', $(this));',
                'btn_class' => 'deleteSelectedObjects'
            ),
            array(
                'label'   => 'Ajouter à une facture',
                'icon'    => 'fas_file-invoice-dollar',
                'onclick' => 'addSelectedElementsToFacture(\'list_id\', id_parent, $(this));'
            ),
            array(
                'label'   => 'Retirer de la facture',
                'icon'    => 'fas_times',
                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'removeFromFacture\', {}, null, \'Veuillez confirmer le retrait des lignes sélectionnées de leurs factures respectives\', false)'
            )
        );
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('removeFromFacture'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                return 0;
            }
            if (!(int) $this->getData('id_facture')) {
                $errors[] = 'Pas de facture associée';
                return 0;
            }
            $facture = $this->getChildObject('facture');
            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'La facture associée n\'existe plus';
                return 0;
            }
            if ((int) $facture->getData('fk_statut') !== 0) {
                $errors[] = 'La facture associée n\'est pas modifiable';
                return 0;
            }
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
            if (!$line->find(array(
                        'id_obj'             => (int) $this->getData('id_facture'),
                        'linked_object_name' => $this->object_name,
                        'linked_id_object'   => (int) $this->id
                            ), true, true)) {
                $errors[] = 'Aucune ligne de facture correspondante trouvée';
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Traitements: 

    public function checkFacture()
    {
        if ((int) $this->getData('id_facture')) {
            $facture = $this->getChildObject('facture');
            if (!BimpObject::objectLoaded($facture)) {
                $this->updateField('id_facture', 0);
            }
        }
    }

    public function createFactureLine($id_facture, Bimp_Facture $facture = null)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        if (is_null($facture) && (int) $id_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'BimpFacture', $id_facture);
        }

        if (!BimpObject::objectLoaded($facture)) {
            $errors[] = 'Facture invalide';
        } elseif ((int) $facture->getData('fk_statut') !== 0) {
            $errors[] = 'La facture n\'a pas le statut "Brouillon"';
        } else {
            $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

            if ($line->find(array(
                        'id_obj'             => (int) $id_facture,
                        'linked_object_name' => $this->object_name,
                        'linked_id_object'   => (int) $this->id
                            ), true, true)) {
                $this->set('id_facture', $id_facture);
                $errors = $this->updateFactureLine();
            } else {
                $errors = $line->validateArray(array(
                    'id_obj'             => (int) $facture->id,
                    'type'               => ObjectLine::LINE_FREE,
                    'deletable'          => 0,
                    'editable'           => 0,
                    'remisable'          => 0,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => $this->object_name
                ));

                if (!count($errors)) {
                    switch ($this->object_name) {
                        case 'BF_FraisDivers':
                            $line->desc = 'Frais divers: ' . $this->getData('description');
                            break;

                        case 'BF_RentExcept':
                            $line->desc = 'Loyer intercalaire à la date du ' . $this->displayData('date');
                            break;
                    }

                    $line->qty = 1;
                    $line->pu_ht = (float) $this->getData('amount');
                    $line->tva_tx = 0; // ??

                    $line_warnings = array();
                    $errors = $line->create($line_warnings, true);
                }
            }
        }

        if (!count($errors)) {
            $this->updateField('id_facture', $id_facture);
        }

        return $errors;
    }

    public function updateFactureLine()
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();
        $this->checkFacture();

        if ((int) $this->getData('id_facture')) {
            if (!$this->isEditable()) {
                $errors[] = 'La facture n\'est plus au statut "Brouillon"';
            } else {
                $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');

                if (!$line->find(array(
                            'id_obj'             => (int) $this->getData('id_facture'),
                            'linked_object_name' => $this->object_name,
                            'linked_id_object'   => (int) $this->id
                                ), true, true)) {
                    $errors = $this->createFactureLine();
                } else {
                    switch ($this->object_name) {
                        case 'BF_FraisDivers':
                            $line->desc = 'Frais divers: ' . $this->getData('description');
                            break;

                        case 'BF_RentExcept':
                            $line->desc = 'Loyer intercalaires à la date du ' . $this->displayData('date');
                            break;
                    }

                    $line->qty = 1;
                    $line->pu_ht = (float) $this->getData('amount');
                    $line->tva_tx = 0; // ??

                    $line_warnings = array();
                    $errors = $line->update($line_warnings, true);
                }
            }
        }

        return $errors;
    }

    public function deleteFactureLine()
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();
        $this->checkFacture();

        if ((int) $this->getData('id_facture')) {
            if (!$this->isEditable()) {
                $errors[] = 'La facture n\'est plus au statut "Brouillon"';
            } else {
                $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                if ($line->find(array(
                            'id_obj'             => (int) $this->getData('id_facture'),
                            'linked_object_name' => $this->object_name,
                            'linked_id_object'   => (int) $this->id
                                ), true, true)) {
                    $del_warnings = array();
                    $errors = $line->delete($del_warnings, true);
                }
            }
            if (!count($errors)) {
                $this->updateField('id_facture', 0);
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionRemoveFromFacture($data, &$success)
    {
        $warnings = array();
        $success = 'Retrait de la facture effectué avec succès';

        $errors = $this->deleteFactureLine();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, false); // le forçage de l'update n'est pas rendu possible pour cet objet. 

        if (!count($errors)) {
            $fac_line_errors = $this->updateFactureLine();
            if (count($fac_line_errors)) {
                $warnings[] = BimpTools::getMsgFromArray($fac_line_errors, 'Echec de la mise à jour de la ligne de facture correspondante');
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        $del_errors = $this->deleteFactureLine();

        if (count($del_errors)) {
            $errors = BimpTools::getMsgFromArray($del_errors, 'Des erreurs sont survenues lors de la suppression de la ligne de facture correspondante');
        } else {
            $errors = parent::delete($warnings, $force_delete);
        }

        return $errors;
    }
}
