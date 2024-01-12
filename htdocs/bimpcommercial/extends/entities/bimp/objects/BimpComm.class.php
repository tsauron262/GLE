<?php

// Entité : bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/extends/entities/ldlc_filiale/objects/BimpComm.class.php';

class BimpComm_ExtEntity extends BimpComm_LdlcFiliale
{

    // Getters booléens: 

    public function isDemandeFinAllowed(&$errors = array())
    {
        $errors[] = 'Demandes de location non autorisées pour les ' . $this->getLabel('name_plur');
        return 0;
    }

    public function isDemandeFinCreatable(&$errors = array())
    {
        if (!$this->isDemandeFinAllowed($errors)) {
            return 0;
        }

        if (!$this->isLoaded($errors)) {
            return 0;
        }

        if (!$this->field_exists('id_demande_fin')) {
            $errors[] = 'Demandes de location non disponibles depuis les ' . $this->getLabel('name_plur');
            return 0;
        }

        if ((int) $this->getData('id_demande_fin')) {
            $errors[] = 'Une demande de location a déjà été faite';
            return 0;
        }
        return 1;
    }

    // Getters params: 

    public function getDemandeFinButtons()
    {
        $buttons = array();

        if ($this->field_exists('id_demande_fin')) {
            if ((int) $this->getData('id_demande_fin')) {
                $df = $this->getChildObject('demande_fin');

                if (BimpObject::objectLoaded($df)) {
                    $buttons = BimpTools::merge_array($buttons, $df->getActionsButtons());
                }
            } else {
                if ($this->isDemandeFinCreatable()) {
                    $df = BimpObject::getInstance('bimpcommercial', 'BimpCommDemandeFin');

                    if ($df->isActionAllowed('createDemandeFinancement') && $df->canSetAction('createDemandeFinancement')) {
                        $type_origine = '';
                        switch ($this->object_name) {
                            case 'Bimp_Propal':
                                $type_origine = 'propale';
                                break;
                            case 'Bimp_Commande':
                                $type_origine = 'commande';
                                break;
                        }

                        if ($type_origine) {
                            $buttons[] = array(
                                'label'   => 'Demande de location',
                                'icon'    => 'fas_comment-dollar',
                                'onclick' => $df->getJsActionOnclick('createDemandeFinancement', array(
                                    'target'       => $df::$def_target,
                                    'type_origine' => $type_origine,
                                    'id_origine'   => $this->id
                                        ), array(
                                    'form_name' => 'demande_financement'
                                ))
                            );
                        }
                    }
                }
            }
        }

        return $buttons;
    }

    // Getters Données: 

    public function getDemandeFinStatus()
    {
        if ($this->field_exists('id_demande_fin')) {
            $df = $this->getChildObject('demande_fin');
            if (BimpObject::objectLoaded($df)) {
                return (int) $df->getData('status');
            }
        }
        return 0;
    }

    public function getDefaultIdContactForDF($type = 'suivi', $all = false)
    {
        $return = 0;

        if ($all) {
            $return = array();
        }

        $types = array();

        switch ($type) {
            case 'suivi':
            case 'signature':
                $types = array('CUSTOMER', 'BILLING2', 'BILLING', 'SHIPPING', 'SITE');
                break;

            case 'livraison':
                $types = array('SHIPPING', 'SITE');
                break;
        }

        if ($this->isLoaded()) {
            foreach ($types as $type_contact) {
                $contacts = $this->dol_object->getIdContact('external', $type_contact);
                if (!empty($contacts)) {
                    if ($all) {
                        foreach ($contacts as $id_contact) {
                            if (!in_array($id_contact, $return)) {
                                $return[] = (int) $id_contact;
                            }
                        }
                    } elseif (isset($contacts[0]) && $contacts[0]) {
                        return (int) $contacts[0];
                    }
                }
            }
        }

        return $return;
    }

    // Rendus HTML

    public function renderHeaderStatusExtra()
    {
        $html = parent::renderHeaderStatusExtra();

        if ($this->field_exists('id_demande_fin') && (int) $this->getData('id_demande_fin')) {
            $df = $this->getChildObject('demande_fin');
            if (BimpObject::objectLoaded($df)) {
                $html .= $df->displayStatus();
            }
        }

        return $html;
    }

    public function renderHeaderExtraLeft()
    {
        $html = parent::renderHeaderExtraLeft();

        if ($this->field_exists('id_demande_fin') && (int) $this->getData('id_demande_fin')) {
            $df = $this->getChildObject('demande_fin');
            if (BimpObject::objectLoaded($df)) {
                $html .= $df->renderSignaturesAlertes();
            }

            if (is_a($this, 'Bimp_Commande') && !(int) $df->getData('serials_ok') && (int) $this->getData('shipment_status') === 2 && (int) $df->getData('status') < 20) {
                $data = $df->fetchDemandeFinData();

                if (isset($data['missing_serials']['total']) && (int) $data['missing_serials']['total'] > 0) {
                    if ((int) $data['missing_serials']['total'] > 1) {
                        $msg = $data['missing_serials']['total'] . ' numéros de série sont manquants sur ' . $df->displayTarget() . '<br/>';
                    } else {
                        $msg = $data['missing_serials']['total'] . ' numéro de série est manquant sur ' . $df->displayTarget() . '<br/>';
                    }

                    $onclick = $this->getJsActionOnclick('setDemandeFinSerials');
                    $msg .= '<div style="text-align: right">';
                    $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $msg .= 'Transmettre les n° de série' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                    $msg .= '</span>';
                    $msg .= '</div>';
                    $html .= BimpRender::renderAlerts($msg, 'warning');
                }
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight($no_div = false)
    {
        if ($this->field_exists('id_demande_fin') && (int) $this->getData('id_demande_fin')) {
            $df = $this->getChildObject('demande_fin');
            if (BimpObject::objectLoaded($df)) {
                $html .= $df->renderDocsButtons();
            }
        }

        $html .= parent::renderHeaderExtraRight($no_div);

        return $html;
    }

    public function renderDemandeFinancementView()
    {
        $html = '';

        $errors = array();
        if (!$this->field_exists('id_demande_fin')) {
            $errors[] = 'Demande de location non disponibles depuis les ' . $this->getLabel('name_plur');
        } elseif ($this->isDemandeFinAllowed($errors)) {
            $id_df = (int) $this->getData('id_demande_fin');
            $df = $this->getChildObject('demande_fin');

            $html .= '<div class="buttonsContainer align-right" style="margin: 0">';
            if (BimpObject::objectLoaded($df)) {
                if ($df->isActionAllowed('editClientData') && $df->canSetAction('editClientData')) {
                    $onclick = $df->getJsActionOnclick('editClientData', array(), array(
                        'form_name' => 'edit_client'
                    ));
                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                    $html .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Mettre à jour les données client';
                    $html .= '</span>';
                }
            }

            $onclick = $this->getJsLoadCustomContent('renderDemandeFinancementView', '$(this).findParentByClass(\'nav_tab_ajax_result\')');
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
            $html .= '</span>';
            $html .= '</div>';

            $html .= '<div class="row">';

            if ($id_df) {
                if (BimpObject::objectLoaded($df)) {
                    $html .= '<div class="col-xs-12 col-sm-6">';
                    $html .= $df->renderDemandeInfos();
                    $html .= '</div>';

                    $html .= '<div class="col-xs-12 col-sm-6">';
                    $fields_table = new BC_FieldsTable($df, 'contacts');
                    $html .= $fields_table->renderHtml();

                    if ((int) $df->getData('id_signature_devis_fin') || (int) $df->getData('id_signature_contrat_fin')) {
                        $fields_table = new BC_FieldsTable($df, 'signatures_fin');
                        $html .= $fields_table->renderHtml();
                    }
                    $html .= '</div>';
                } else {
                    $errors[] = 'La demande de location #' . $id_df . ' n\'existe plus';
                }
            } else {
                $errors[] = 'Aucune demande de location liée à ' . $this->getLabel('this');
            }

            $html .= '</div>';
        }

        if (count($errors)) {
            $html .= '<div class="row">';
            $html .= BimpRender::renderAlerts($errors);
            $html .= '</div>';
        }

        return $html;
    }

    // Traitements: 

    public function setDemandeFinancementStatus($status, $note = '')
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if ($this->field_exists('id_demande_fin') && (int) $this->getData('id_demande_fin')) {
                $df = $this->getChildObject('demande_fin');
                if (BimpObject::objectLoaded($df)) {
                    $errors = $df->setNewStatus($status);

                    if (!count($errors)) {
                        $msg = 'Demande de location ' . lcfirst(BimpCommDemandeFin::$status_list[$status]['label']);
                        if ($note) {
                            $msg .= '<br/><b>Note : </b>' . $note;
                        }

                        $this->addObjectLog($msg, 'NEW_DMD_FIN_STATUS_' . $status);

                        // Todo: mail commercial           
                    }
                } else {
                    $errors[] = 'La demande de location #' . $this->getData('id_demande_fin') . ' n\'existe plus';
                }
            } else {
                $errors[] = 'Aucune demande de location associée à ' . $this->getLabel('this');
            }
        }

        return $errors;
    }

    // Overrides: 

    public function duplicate($new_data = [], &$warnings = [], $force_create = false)
    {
        if ($this->field_exists('id_demande_fin')) {
            $new_data['id_demande_fin'] = 0;
        }

        return parent::duplicate($new_data, $warnings, $force_create);
    }
}
