<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Commande.class.php';

class Bimp_Commande_ExtEntity extends Bimp_Commande
{

    public function isDemandeFinAllowed(&$errors = array())
    {
        if (BimpCore::isUserDev()) {
            return 1;
        }

        if (!(int) BimpCore::getConf('allow_df_from_commande', null, 'bimpcommercial')) {
            $errors[] = 'Demandes de location à partir des commandes désactivées';
            return 0;
        }

        return 1;
    }

    public function isDemandeFinCreatable(&$errors = array())
    {
        if (!parent::isDemandeFinCreatable($errors)) {
            return 0;
        }

        if ($this->getData('fk_statut') != 1) {
            $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '"';
            return 0;
        }

        $invoice_status = (int) $this->getData('invoice_status');
        if ($invoice_status > 0) {
            $errors[] = ucfirst($this->getLabel('this')) . ' est ' . ($invoice_status === 1 ? 'partiellement ' : '') . 'facturé' . $this->e();
            return 0;
        }

        return 1;
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();
        $df_buttons = parent::getDemandeFinButtons();

        if (!empty($df_buttons)) {
            return array(
                'buttons_groups' => array(
                    array(
                        'label'   => 'Location',
                        'icon'    => 'fas_hand-holding-usd',
                        'buttons' => $df_buttons
                    ),
                    array(
                        'label'   => 'Actions',
                        'icon'    => 'fas_cogs',
                        'buttons' => $buttons
                    )
                )
            );
        }

        return $buttons;
    }

    // Traitements: 

    public function onDocFinancementSigned($doc_type)
    {
        switch ($doc_type) {
            case 'contrat_financement':
                if ((int) $this->getData('id_demande_fin')) {
                    $demande_fin = $this->getChildObject('demande_fin');

                    if (BimpObject::objectLoaded($demande_fin)) {
                        $id_client = (int) $demande_fin->getTargetIdClient();
                        if ($id_client) {
                            $this->updateField('id_client_facture', $id_client);
                        }
                    }
                }
                break;
        }
    }
}
