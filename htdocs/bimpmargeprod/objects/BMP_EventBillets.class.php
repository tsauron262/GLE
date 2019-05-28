<?php

class BMP_EventBillets extends BimpObject
{

    // Getters: 

    public function isEventEditable()
    {
        $event = $this->getParentInstance();
        if (!is_null($event) && $event->isLoaded()) {
            return $event->isInEditableStatus();
        }

        return 0;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if ($this->isLoaded()) {
            if (in_array($field, array('quantity', 'dl_dist', 'dl_prod', 'id_coprod'))) {
                return (int) $this->isEventEditable();
            }
        }

        return 1;
    }

    public function isCreatable($force_create = false)
    {
        return (int) $this->isEventEditable();
    }

    public function isDeletable($force_delete = false)
    {
        return (int) $this->isEventEditable();
    }

    public function getCreateForm()
    {
        if ($this->isEventEditable()) {
            return 'default';
        }

        return '';
    }

    public function getTarifsArray()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            return $event->getTarifsArray();
        }

        return array();
    }

    public function getTotal()
    {
        if (!isset($this->id) || !$this->id) {
            return 0;
        }

        $tarif = $this->getChildObject('tarif');

        if (BimpObject::objectLoaded($tarif)) {
            return ((float) $tarif->getData('amount') * (int) $this->getData('quantity'));
        }

        return 0;
    }

    public function getBulkActions()
    {
        $actions = array();
        if ($this->isEventEditable()) {
            $id_event = (int) $this->getData('id_event');
            $actions[] = array(
                'label'   => 'Supprimer les lignes sélectionnées',
                'icon'    => 'fas_trash-alt',
                'onclick' => 'deleteSelectedObjects(\'list_id\', $(this))'
            );
            $actions[] = array(
                'label'   => 'Assigner les DL distributeur par défaut',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsBulkActionOnclick('setDefaultDL', array('dl_type' => 'dist', 'id_event' => $id_event), array('single_action' => 'true'))
            );
            $actions[] = array(
                'label'   => 'Assigner les DL producteur par défaut',
                'icon'    => 'fas_arrow-circle-right',
                'onclick' => $this->getJsBulkActionOnclick('setDefaultDL', array('dl_type' => 'prod', 'id_event' => $id_event), array('single_action' => 'true'))
            );
        }

        return $actions;
    }

    public function getCoprodsArray()
    {
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $coprods = $event->getCoProds(true);
            $coprods[0] = 'Le Fil';
            return $coprods;
        }

        return array(
            0 => ''
        );
    }
    
    public function getTotalDLDist()
    {
        return (float) $this->getData('dl_dist') * (int) $this->getData('quantity');
    }
    
    public function getTotalDLProd()
    {
        return (float) $this->getData('dl_prod') * (int) $this->getData('quantity');
    }

    // Affichages: 

    public function displaySeller($display_name = 'nom_url', $display_input_value = true, $no_html = false)
    {
        if ((int) $this->getData('id_soc_seller')) {
            return $this->displayData('id_soc_seller', $display_name, $display_input_value, $no_html);
        }

        return $this->displayData('seller_name', 'default', $display_input_value, $no_html);
    }

    public function displayTarif()
    {
        $tarif = $this->getChildObject('tarif');
        if (BimpObject::objectLoaded($tarif)) {
            $name = $tarif->getData('name');
            $amount = $tarif->getData('amount');
            if (!is_null($name)) {
                return $name . ' (' . BimpTools::displayMoneyValue($amount, 'EUR') . ')';
            } elseif (!is_null($amount) && $amount) {
                return $amount . ' ' . BimpTools::getCurrencyHtml('EUR');
            }
        }

        return '<span class="warning">Aucun</span>';
    }

    public function displayTotalTTC()
    {
        return BimpTools::displayMoneyValue($this->getTotal(), 'EUR');
    }

    public function displayTotalHT()
    {
        $total_ttc = $this->getTotal();
        $event = $this->getParentInstance();
        if (BimpObject::objectLoaded($event)) {
            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . $event->getBilletsIdTypeMontant());
        } else {
            $id_tax = $this->db->getValue('bmp_type_montant', 'id_taxe', '`id` = ' . BMP_Event::$id_billets_type_montant);
        }

        $total_ht = (float) BimpTools::calculatePriceTaxEx($total_ttc, BimpTools::getTaxeRateById($id_tax));
        return BimpTools::displayMoneyValue($total_ht, 'EUR');
    }

    // Actions: 

    public function actionSetDefaultDL($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Enregistrement des droits de location par défaut effectuée avec succès';

        $dl_type = isset($data['dl_type']) ? $data['dl_type'] : '';
        $id_event = isset($data['id_event']) ? (int) $data['id_event'] : 0;

        if (!$id_event) {
            $errors[] = 'ID de l\'événement absent';
        } else {
            $event = BimpCache::getBimpObjectInstance($this->module, 'BMP_Event', $id_event);
            if (!BimpObject::objectLoaded($event)) {
                $errors[] = 'L\'événement d\'ID ' . $id_event . ' n\'existe pas';
            }
        }

        if (!$dl_type) {
            $errors[] = 'Type de droits de location non spécifié';
        }

        if (!count($errors)) {
            $id_objects = isset($data['id_objects']) ? $data['id_objects'] : array();

            if (empty($id_objects)) {
                $errors[] = 'Aucune vente de billets sélectionnée';
            } else {
                $def_dl = 0;
                $field = '';
                switch ($dl_type) {
                    case 'dist':
                        $def_dl = (float) $event->getData('default_dl_dist');
                        $field = 'dl_dist';
                        break;

                    case 'prod':
                        $def_dl = (float) $event->getData('default_dl_prod');
                        $field = 'dl_prod';
                        break;
                }

                foreach ($id_objects as $id_eventBillets) {
                    $eventBillets = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_eventBillets);
                    if (BimpObject::objectLoaded($eventBillets)) {
                        $eventBillets->set($field, $def_dl);
                        $up_warnings = array();
                        $up_errors = $eventBillets->update($up_warnings);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la vente de billets d\'ID ' . $id_eventBillets);
                        }
                    } else {
                        $warnings[] = 'La vente de billets d\'ID ' . $id_eventBillets . ' n\'existe pas';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
