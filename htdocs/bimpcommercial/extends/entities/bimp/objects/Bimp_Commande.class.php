<?php

//Entity: bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Commande.class.php';

class Bimp_Commande_ExtEntity extends Bimp_Commande
{

    public function isDemandeFinAllowed(&$errors = array())
    {
        if (!(int) BimpCore::getConf('allow_df_from_commande', null, 'bimpcommercial')) {
            $errors[] = 'Demandes de location à partir des commandes désactivées';
            return 0;
        }

        return 1;
    }

    public function isDemandeFinCreatable(&$errors = array())
    {
//        global $user;
//        if ($user->admin || $user->id == 1896 || $user->id == 1017)
//            return 1;

        if (!parent::isDemandeFinCreatable($errors)) {
            return 0;
        }

//        if ($this->getData('fk_statut') != 1) {
//            $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '"';
//            return 0;
//        }
//
//        $invoice_status = (int) $this->getData('invoice_status');
//        if ($invoice_status > 0) {
//            $errors[] = ucfirst($this->getLabel('this')) . ' est ' . ($invoice_status === 1 ? 'partiellement ' : '') . 'facturé' . $this->e();
//            return 0;
//        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = [])
    {
        switch ($action) {
            case 'setDemandeFinSerials':
                if (!(int) $this->getData('id_demande_fin')) {
                    $errors[] = 'Il n\'y as aucune demande de location pour cette commande';
                    return 0;
                }

                $bcdf = $this->getChildObject('demande_fin');
                if (!BimpObject::objectLoaded($bcdf)) {
                    $errors[] = 'La demande de location associée n\'existe plus (ID ' . $this->getData('id_demande_fin') . ')';
                    return 0;
                } else {
                    $data = $bcdf->fetchDemandeFinData(false);

                    if (!isset($data['missing_serials']['total']) || !(int) $data['missing_serials']['total']) {
                        $errors[] = 'Il ne reste aucun n° de série à transmettre';
                        return 0;
                    }
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
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

    // Actions: 

    public function actionSetDemandeFinSerials($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'N° de série transmis avec succès';
        $sc = 'bimp_reloadPage();';

        $bcdf = $this->getChildObject('demande_fin');
        $errors = $bcdf->setSerialsToTarget($this->id);

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }
}
