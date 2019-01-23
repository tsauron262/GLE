<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpLine.class.php';

class BF_Line extends BimpLine
{
    
    public function getQtyDecimals()
    {
        return 3;
    }

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

    // Getters - Overrides

    public function isFieldEditable($field)
    {
        if (in_array($field, array('type', 'id_product', 'label', 'pu_ht', 'pa_ht', 'tva_tx', 'id_fourn_price', 'id_fournisseur', 'description', 'remisable'))) {
            if ($this->isLoaded()) {
                $commandes_fourn = $this->getData('commandes_fourn');
                if (is_array($commandes_fourn)) {
                    foreach ($commandes_fourn as $id_commande => $qty) {
                        if ((float) $qty > 0) {
                            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                            if (BimpObject::objectLoaded($commande)) {
                                if (!$commande->isEditable()) {
                                    return 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        return (int) parent::isFieldEditable($field);
    }

    public function isDeletable()
    {
        $commandes_fourn = $this->getData('commandes_fourn');
        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty) {
                if ((float) $qty > 0) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    if (BimpObject::objectLoaded($commande)) {
                        if (!$commande->isEditable()) {
                            return 0;
                        }
                    }
                }
            }
        }
        return parent::isDeletable();
    }

    public function areAllCommandesFournEditable()
    {
        $commandes_fourn = $this->getData('commandes_fourn');

        if (is_array($commandes_fourn)) {
            foreach ($commandes_fourn as $id_commande => $qty) {
                if ((float) $qty > 0) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $id_commande);
                    if (BimpObject::objectLoaded($commande)) {
                        if (!$commande->isEditable()) {
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
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!(int) $id_commande) {
            return array('ID de la commande fournisseur absent');
        }

        $errors = array();
        $comm_line = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFournLine');

        if ($comm_line->find(array(
                    'id_obj'             => (int) $id_commande,
                    'linked_id_object'   => (int) $this->id,
                    'linked_object_name' => 'bf_line',
                        ), true, true)) {
            return $this->updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field);
        } else {
            if (!(float) $qty) {
                return array();
            }

            if ((float) $qty + (float) $this->getQtyOrdered() > (float) $this->getData('qty')) {
                $errors[] = 'Erreurs: quantités totales à commander supérieures à la quantité de la ligne à financer';
            } else {
                $errors = $comm_line->validateArray(array(
                    'id_obj'             => (int) $id_commande,
                    'type'               => $this->getTypeForObjectLine(),
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
        return $errors;
    }

    public function updateCommandeFournLine($id_commande, $qty, $update_commandes_fourn_field = true)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if (!(int) $id_commande) {
            return array('ID de la commande fournisseur absent');
        }

        if (!(float) $qty) {
            return $this->deleteCommandeFournLine($id_commande);
        }

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
                $errors = $cf_line->update();
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
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_this') . ' absent');
        }

        if (!(int) $id_commande) {
            return array('ID de la commande fournisseur absent');
        }

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
