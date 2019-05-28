<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpLine.class.php';

class BF_Line extends BimpLine
{

    // Getters: 

    public function getQtyOrdered($id_commande_excluded = 0)
    {
        $qty_ordered = 0;
        $commandes_fourn = $this->getData('commandes_fourn');

        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty) {
                if ($id_commande_excluded && $id_commande === $id_commande_excluded) {
                    continue;
                }
                $qty_ordered += (float) $qty;
            }
        }

        return $qty_ordered;
    }

    public function getQtyDecimals()
    {
        return 3;
    }

    public function getSerialDesc() {
        $id_product = $this->getdata('id_product');
        $equipments = $this->getData('equipments');
        $label = $this->getData('label');
        $serials = $this->getData('extra_serials');

        if($id_product > 0) {
            $p = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            $p->find(array('rowid' => (int) $id_product), true, true);
            $label = $p->getData('label');
        }

        if($equipments) {
            foreach($equipments as $equipment) {
                $e = BimpObject::getInstance('bimpequipment', 'Equipment');
                $e->find(array('id' => (int) $equipment), true, true);
                $serials .= (!empty($serials)) ? ", " : "";
                $serials .= $e->getData('serial');
            }
        }
        return (object) Array('label' => $label, 'serials' => $serials);
        
    }

    public function getTotalLine($ttc = true)
    {
        $tot = $this->getData("pu_ht") * $this->getData("qty");
        if ($ttc)
            $tot += $tot * $this->getData("tva_tx") / 100;
        return $tot;
    }
    
    public function getInputValue($field_name)
    {
        if ($field_name === 'use_pu_for_pa') {
            if (!$this->isLoaded()) {
                return 1;
            }
            
            if (!(int) $this->getData('id_fourn_price')) {
                if ((float) $this->getData('pu_ht') === (float) $this->getData('pa_ht'))  {
                    return 1;
                }
            }
            return 0;
        }
        parent::getInputValue($field_name);
    }

    // Getters - booléens

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array('type', 'id_product', 'label', 'pu_ht', 'pa_ht', 'tva_tx', 'id_fourn_price', 'id_fournisseur', 'description', 'remisable'))) {
            return (int) $this->areAllCommandesFournEditable();
        }

        return (int) parent::isFieldEditable($field, $force_edit);
    }

    public function isCreatable($force_create = false)
    {
        $demande = $this->getParentInstance();

        if (BimpObject::objectLoaded($demande)) {
            if (!(int) $demande->getData('accepted')) {
                return 1;
            }
        }

        return 0;
    }

    public function isEditable($force_edit = false)
    {
        return $this->isCreatable($force_edit);
    }

    public function isDeletable($force_delete = false)
    {
        return (int) ($this->areAllCommandesFournEditable() && $this->isCreatable($force_delete));
    }

    public function areAllCommandesFournEditable()
    {
        $commandes_fourn = $this->getData('commandes_fourn');

        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty) {
                if ((float) $qty > 0) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    if (BimpObject::objectLoaded($commande)) {
                        if ($commande->getData('fk_statut') > 0) {
                            return 0;
                        }
                    }
                }
            }
        }

        return 1;
    }

    // Affichage: 

    public function displayQtyOrdered()
    {
        $qty_ordered = (float) $this->getQtyOrdered();
        $qty = (float) $this->getData('qty');

        $class = 'success';
        if ($qty_ordered < $qty) {
            $class = 'warning';
        } elseif ($qty_ordered > $qty) {
            $class = 'danger';
        }

        return '<span class="' . $class . '">' . $qty_ordered . '</span>';
    }

    // Traitements: 

    public function createCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!(int) $id_commande) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
            } else {
                if ((int) $commande->getData('fk_statut') !== 0) {
                    $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
                }
            }
        }

        if (!count($errors)) {
            $comm_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

            if ($comm_line->find(array(
                        'id_obj'             => (int) $id_commande,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_line',
                            ), true, true)) {
                $errors = $this->updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field);
            } elseif ((float) $qty) {
                if ((float) $qty + (float) $this->getQtyOrdered() > (float) $this->getData('qty')) {
                    $errors[] = 'Erreurs: quantités totales à commander supérieures à la quantité de la ligne à financer';
                } else {
                    $errors = $comm_line->validateArray(array(
                        'id_obj'             => (int) $id_commande,
                        'type'               => $this->getTypeForObjectLine(),
                        'editable'           => 0,
                        'deletable'          => 0,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_line'
                    ));
                }

                if (!count($errors)) {
                    $this->hydrateObjectLine($comm_line, (float) $qty);
                    $errors = $comm_line->create();
                }

                if (!count($errors)) {
                    $commandesFourn = $this->getData('commandes_fourn');
                    if (!is_array($commandesFourn)) {
                        $commandesFourn = array();
                    }
                    $commandesFourn[(int) $id_commande] = (float) $qty;
                    if ($update_commandes_fourn_field) {
                        $this->updateField('commandes_fourn', $commandesFourn);
                    } else {
                        $this->set('commandes_fourn', $commandesFourn);
                    }
                }
            }
        }
        return $errors;
    }

    public function updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!(int) $id_commande) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
            } else {
                if ((int) $commande->getData('fk_statut') !== 0) {
                    $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
                }
            }
        }

        if (!count($errors)) {
            if (!(float) $qty) {
                $errors = $this->deleteCommandeFournLine($id_commande);
            } else {
                $cf_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                if ($cf_line->find(array(
                            'id_obj'             => (int) $id_commande,
                            'linked_id_object'   => (int) $this->id,
                            'linked_object_name' => 'bf_line',
                                ), true, true)) {
                    if ((float) $qty + (float) $this->getQtyOrdered($id_commande) > (float) $this->getData('qty')) {
                        $errors[] = 'Erreurs: quantités totales à commander supérieures à la quantité de la ligne à financer';
                    } else {
                        $cf_line->set('type', $this->getTypeForObjectLine());
                        $this->hydrateObjectLine($cf_line, $qty);
                        $warnings = array();
                        $errors = $cf_line->update($warnings, true);
                        if (!count($errors)) {
                            $commandesFourn = $this->getData('commandes_fourn');
                            if (!is_array($commandesFourn)) {
                                $commandesFourn = array();
                            }
                            $commandesFourn[$id_commande] = (float) $qty;
                            if ($update_commandes_fourn_field) {
                                $this->updateField('commandes_fourn', $commandesFourn);
                            } else {
                                $this->set('commandes_fourn', $commandesFourn);
                            }
                        }
                    }
                } else {
                    $errors = $this->createCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field);
                }
            }
        }

        return $errors;
    }

    public function updateCommandesFournLines($qties = array(), $update_commandes_fourn_field = true)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn)) {
            foreach ($commandesFourn as $id_commande => $qty) {
                if (isset($qties[$id_commande])) {
                    $qty = (float) $qties[$id_commande];
                }
                $line_errors = $this->updateCommandeFournLine((int) $id_commande, (float) $qty, false);
                if (count($line_errors)) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne correpondante pour la commande "' . $comm->getRef() . '"');
                } else {
                    $commandesFourn[$id_commande] = $qty;
                }
            }

            if ($update_commandes_fourn_field) {
                $this->updateField('commandes_fourn', $commandesFourn);
            } else {
                $this->set('commandes_fourn', $commandesFourn);
            }
        }

        return $errors;
    }

    public function deleteCommandeFournLine($id_commande, $update_commandes_fourn_field = true)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_this') . ' absent';
        } elseif (!(int) $id_commande) {
            $errors[] = 'ID de la commande fournisseur absent';
        } else {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);

            if (!BimpObject::objectLoaded($commande)) {
                $errors[] = 'La commande fournisseur d\'ID ' . $id_commande . ' n\'existe pas';
            } elseif ((int) $commande->getData('fk_statut') !== 0) {
                $errors[] = 'La commande fournisseur ' . $commande->getRef() . ' n\'a plus le statut "Brouillon"';
            }
        }

        if (!count($errors)) {
            $cf_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');
            if ($cf_line->find(array(
                        'id_obj'             => (int) $id_commande,
                        'linked_id_object'   => (int) $this->id,
                        'linked_object_name' => 'bf_line',
                            ), true, true)) {
                $del_warnings = array();
                $errors = $cf_line->delete($del_warnings, true);
                if (!count($errors)) {
                    $commandesFourn = $this->getData('commandes_fourn');
                    if (isset($commandesFourn[$id_commande])) {
                        unset($commandesFourn[$id_commande]);
                        if ($update_commandes_fourn_field) {
                            $this->updateField('commandes_fourn', $commandesFourn);
                        } else {
                            $this->set('commandes_fourn', $commandesFourn);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function deleteCommandesFournLines($update_commandes_fourn_field = true)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        $errors = array();

        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn)) {
            foreach ($commandesFourn as $id_commande => $qty) {
                $line_errors = $this->deleteCommandeFournLine($id_commande, false);
                if (count($line_errors)) {
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la suppression de la ligne de commande fournisseur pour la commande "' . $comm->getRef() . '"');
                } else {
                    unset($commandesFourn[$id_commande]);
                }
            }
            if ($update_commandes_fourn_field) {
                $this->updateField('commandes_fourn', $commandesFourn);
            } else {
                $this->set('commandes_fourn', $commandesFourn);
            }
        }

        return $errors;
    }

    public function onCommandeFournCancel($id_commande)
    {
        if (!$this->isLoaded()) {
            return array('ID de la ligne de financement absent');
        }
        $errors = array();
        $commandesFourn = $this->getData('commandes_fourn');

        if (is_array($commandesFourn) && array_key_exists((int) $id_commande, $commandesFourn)) {
            unset($commandesFourn[$id_commande]);
            $errors = $this->updateField('commandes_fourn', $commandesFourn);
        }

        return $errors;
    }

    // Overrides: 

    public function validate()
    {
        $use_pu_for_pa = (int) BimpTools::getValue('use_pu_for_pa', 0);

        if ($use_pu_for_pa) {
            $this->set('id_fourn_price', 0);
            $this->set('pa_ht', (float) $this->getData('pu_ht'));
        }

        return parent::validate();
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($this->areAllCommandesFournEditable()) {
                $lines_errors = $this->updateCommandesFournLines(array(), true);
                if (count($lines_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($lines_errors, 'Des erreurs sont survenues lors de la mise à jour des commandes fournisseur correspondantes');
                }
            }
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        if (!$this->isDeletable()) {
            $errors[] = 'Cette ligne n\'est pas supprimable';
        } else {
            $this->deleteCommandesFournLines(false);

            $errors = parent::delete($warnings, $force_delete);
        }

        return $errors;
    }
}
