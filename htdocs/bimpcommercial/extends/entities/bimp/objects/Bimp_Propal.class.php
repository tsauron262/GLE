<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class Bimp_Propal_ExtEntity extends Bimp_Propal
{

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'close':
            case 'modify':
            case 'review':
            case 'reopen':
                if ((int) $this->getData('id_demande_fin')) {
                    $df = $this->getChildObject('demande_fin');
                    if (BimpObject::objectLoaded($df)) {
                        $df_status = (int) $df->getData('status');
                        if ($df_status > 0 && $df_status < 10) {
                            $errors[] = 'Une demande de location est en attente d\'acceptation';
                            return 0;
                        }

                        if ($df_status === BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                            if ((int) $df->getData('devis_fin_status') < 20) {
                                $errors[] = 'Devis de location non refusé ou annulé';
                                return 0;
                            }
                            if ((int) $df->getData('contrat_fin_status') < 20) {
                                $errors[] = 'Contrat de location non refusé ou annulé';
                                return 0;
                            }
                        }
                    }
                }
                break;

            case 'createOrder':
            case 'createInvoice':
            case 'classifyBilled':
            case 'createContrat':
            case 'createSignature':
            case 'addAcompte':
                if ((int) $this->getData('id_demande_fin')) {
                    $df = $this->getChildObject('demande_fin');
                    if (BimpObject::objectLoaded($df)) {
                        $df_status = (int) $df->getData('status');
                        if ($df_status > 0 && $df_status < 10) {
                            $errors[] = 'Une demande de location est en attente d\'acceptation';
                            return 0;
                        }

                        if ($df_status === BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                            if ((int) $df->getData('devis_fin_status') !== BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                                $errors[] = 'Devis de location non signé';
                                return 0;
                            }
                            if ((int) $df->getData('contrat_fin_status') !== BimpCommDemandeFin::DOC_STATUS_ACCEPTED) {
                                $errors[] = 'Contrat de location non signé';
                                return 0;
                            }
                        } elseif ($df_status < 20) {
                            $errors[] = 'Devis de location non accepté par le client';
                            return 0;
                        }
                    }
                }
                break;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isDemandeFinAllowed(&$errors = array())
    {
        if (!(int) BimpCore::getConf('allow_df_from_propal', null, 'bimpcommercial')) {
            $errors[] = 'Demandes de location à partir des devis désactivées';
            return 0;
        }

        return 1;
    }

    public function isDemandeFinCreatable(&$errors = array())
    {
        if (!parent::isDemandeFinCreatable($errors)) {
            return 0;
        }

        if (!in_array((int) $this->getData('fk_statut'), array(1, 2))) {
            $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '" ou "accepté' . $this->e() . '"';
            return 0;
        }

        if ((int) $this->getData('id_signature')) {
            $signature = $this->getChildObject('signature');

            if (BimpObject::objectLoaded($signature)) {
                if (!(int) $signature->getData('type')) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' est en attente de signature.<br/>Vous devez attendre la signature du client (ou annuler la demande de signature) pour émettre une demande de location';
                    return 0;
                }
            }
        }

        return 1;
    }

    public function isDocuSignAllowed(&$errors = array())
    {
        if (!parent::isDocuSignAllowed($errors)) {
            return 0;
        }
        
        // Ajouter conditions spécifiques à BIMP ici
        // (ne pas oublier d'alimenter $errors)

        return 1;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();
        $df_buttons = parent::getDemandeFinButtons();

        if (!empty($df_buttons)) {
            if (isset($buttons['buttons_groups'])) {
                $buttons['buttons_groups'][] = array(
                    'label'   => 'Location',
                    'icon'    => 'fas_hand-holding-usd',
                    'buttons' => $df_buttons
                );
            } else {
                return array(
                    'buttons_groups' => array(
                        array(
                            'label'   => 'Actions',
                            'icon'    => 'fas_cogs',
                            'buttons' => $buttons
                        ),
                        array(
                            'label'   => 'Location',
                            'icon'    => 'fas_hand-holding-usd',
                            'buttons' => $df_buttons
                        )
                    )
                );
            }
        }

        return $buttons;
    }

    // Rendus HTML:

    public function renderHeaderExtraRight($no_div = false)
    {
        $html = '<div class="buttonsContainer">';
        $html .= BimpComm_ExtEntity::renderHeaderExtraRight($no_div);
        $html .= parent::renderHeaderExtraRight(true);
        $html .= '</div>';

        return $html;
    }

    // Traitements: 

    public function onDocFinancementSigned($doc_type)
    {
        switch ($doc_type) {
            case 'contrat_financement':
                if ((int) $this->getData('fk_statut') !== Propal::STATUS_SIGNED) {
                    $this->updateField('fk_statut', Propal::STATUS_SIGNED);

                    // Vérification de l\'existance d'une commande: 
                    $where = '`fk_source` = ' . (int) $this->id . ' AND `sourcetype` = \'propal\'';
                    $where .= ' AND `targettype` = \'commande\'';

                    $id_commande = (int) $this->db->getValue('element_element', 'fk_target', $where, 'fk_target');
                    if ($id_commande) {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $id_commande);

                        if (BimpObject::objectLoaded($commande)) {
                            $df = $this->getChildObject('demande_fin');

                            if (BimpObject::objectLoaded($df)) {
                                $this->db->update('commande', array(
                                    'id_demande_fin'    => $df->id,
                                    'id_client_facture' => $df->getTargetIdClient()
                                        ), 'rowid = ' . $id_commande);
                            }
                        }
                    }
                }
                break;
        }
    }
}
