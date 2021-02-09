<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

global $langs;
$langs->load('bills');
$langs->load('errors');
$cacheInstance = array();

class Bimp_Facture extends BimpComm
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public $acomptes_allowed = true;
    public static $dol_module = 'facture';
    public static $email_type = 'facture_send';
    public static $element_name = 'invoice';
    public static $mail_event_code = 'BILL_SENTBYMAIL';
    public $avoir = null;
    public static $status_list = array(
        0 => array('label' => 'Brouillon', 'icon' => 'fas_file-alt', 'classes' => array('warning')),
        1 => array('label' => 'Validée', 'icon' => 'check', 'classes' => array('info')),
        2 => array('label' => 'Fermée', 'icon' => 'check', 'classes' => array('success')),
        3 => array('label' => 'Abandonnée', 'icon' => 'times-circle', 'classes' => array('danger')),
    );
    public static $types = array(
        0 => array('label' => 'Facture standard'),
        1 => array('label' => 'Facture de remplacement'),
        2 => array('label' => 'Avoir'),
        3 => array('label' => 'Facture d\'acompte'),
        4 => array('label' => 'Facture proforma'),
        5 => array('label' => 'Facture de situation')
    );
    public static $statut_export = array(
        0 => array('label' => ''),
        1 => array('label' => 'Attestation Dédouanement Reçue '),
        2 => array('label' => 'Attestation Dédouanement Non Reçue'),
        3 => array('label' => 'Non déclarable'),
    );
    public static $paiement_status = array(
        0 => array('label' => 'Aucun paiement', 'classes' => array('danger'), 'icon' => 'fas_times'),
        1 => array('label' => 'Paiement partiel', 'classes' => array('warning'), 'icon' => 'fas_exclamation-circle'),
        2 => array('label' => 'Paiement complet', 'classes' => array('success'), 'icon' => 'fas_check'),
        3 => array('label' => 'Trop perçu', 'classes' => array('important'), 'icon' => 'fas_exclamation-triangle'),
        4 => array('label' => 'Trop remboursé', 'classes' => array('important'), 'icon' => 'fas_exclamation-triangle'),
        5 => array('label' => 'Irrécouvrable', 'classes' => array('danger'), 'icon' => 'fas_times-circle')
    );

    // Gestion des droits: 

    public function canCreate()
    {
        global $user;
        return $user->rights->facture->creer;
    }

    protected function canEdit()
    {
        return $this->can("create");
    }

    public function canDelete()
    {
        global $user;
        return $user->rights->facture->supprimer;
    }

    public function canSetAction($action)
    {
        global $conf, $user;

        switch ($action) {
            case 'validate':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->facture->invoice_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'modify':
                return 0;

            case 'reopen':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->creer) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->invoice_advance->reopen)) {
                    return 1;
                }
                return 0;


            case 'addContact':
                return 1;

            case 'cancel':
                return (int) $user->admin || $user->rights->bimpcommercial->adminPaiement;

            case 'setIrrecouvrable':
                if ($user->admin || $user->rights->bimpcommercial->adminPaiement) {
                    return 1;
                }
                return 0;

            case 'classifyPaid':
                if ($user->admin || $user->rights->bimpcommercial->adminPaiement)
                    return 1;
                return 0;
            case 'convertToReduc':
            case 'payBack':
                if ($user->rights->facture->paiement) {
                    return 1;
                }
                return 0;

            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
                    return 1;
                }
                return 0;

            case 'removeFromCommission':
            case 'removeFromUserCommission':
            case 'removeFromEntrepotCommission':
            case 'addToCommission':
                $commission = BimpObject::getInstance('bimpfinanc', 'BimpCommission');
                return (int) $commission->can('create');

            case 'checkPa':
//                if (!$user->admin) {
//                    return 0; // A suppr
//                }
//                $line_instance = $this->getLineInstance();
//                if (is_a($line_instance, 'ObjectLine')) {
//                    return (int) $line_instance->canEditPrixAchat();
//                }
//                return 0;
                return 1;


            case 'generatePDFDuplicata':
                return 1;

            case 'deactivateRelancesForAMonth':
                return (int) ($user->admin || $user->rights->bimpcommercial->deactivate_relances_one_month);

            case 'checkPaiements':
                return 1;

            case 'setCommandeLinesNotBilled':
            case 'linesToFacture':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->creer) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->invoice_advance->reopen)) {
                    return 1;
                }
                return 1;
        }

        return parent::canSetAction($action);
    }

    public function canFactureAutreDate()
    {
        global $user;
        return $user->rights->bimpcommercial->edit_date_facture;
    }

    public function canAddExtraPaiement()
    {
        global $user;

        return ($user->admin || $user->rights->bimpcommercial->adminPaiement ? 1 : 0);
    }

    public function canEditField($field_name)
    {
        global $user;

        switch ($field_name) {
            case 'date_next_relance':
            case 'relance_active':
                if ($user->admin || $user->rights->bimpcommercial->admin_deactivate_relances) {
                    return 1;
                }
                return 0;

            case 'ef_type':
                if ($this->getData('fk_statut') > 0) {
                    if ($user->admin || $user->rights->bimpcommercial->admin_fact) {
                        return 1;
                    }
                    return 0;
                }
                break;

            case 'fk_mode_reglement':
                if ($this->getData('fk_statut') > 0) {
                    if ($user->admin || $user->rights->bimpcommercial->admin_recouvrement || $this->getInitData('fk_mode_reglement') < 1) {
                        return 1;
                    }
                    return 0;
                }
                break;
        }

        return parent::canEditField($field_name);
    }

    // Getters booléens:

    public function isEditable($force_edit = false, &$errors = array())
    {
        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if (!$this->isLoaded($errors)) {
            return 0;
        }

        // Suppression autorisée seulement pour les brouillons:
        if ((int) $this->getData('fk_statut') > 0) {
            $errors[] = 'Facture validée';
            return 0;
        }

        // Si facture BL, on interdit la suppression s'il existe une facture hors expédition pour la commande:
        $rows = (int) $this->db->getRows('bl_commande_shipment', '`id_facture` = ' . (int) $this->id, null, 'object', array('id', 'id_commande_client'));
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $id_facture = $this->db->getValue('commande', 'id_facture', '`rowid` = ' . (int) $row->id_commande_client);
                if (!is_null($id_facture) && (int) $id_facture) {
                    $errors[] = 'Il existe une facture hors expédition pour la commande #' . $row->id_commande_client;
                    return 0;
                }
            }
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        if (in_array($field, array(
                    'statut_export', 'douane_number',
                    'note_public', 'note_private',
                    'remain_to_pay', 'paiement_status',
                    'relance_active', 'nb_relance', 'date_relance', 'date_next_relance',
                    'close_code', 'close_note',
                    'date_irrecouvrable', 'id_user_irrecouvrable',
                    'prelevement', 'ef_type', 'fk_mode_reglement', 'pdf_nb_decimal'
                ))) {
            return 1;
        }

        if ((int) $this->getData('fk_statut') > 0 && ($field == 'datef'))
            return 0;


        if ($this->getData('exported') == 1) {
            if ($field === 'fk_cond_reglement' && !(int) $this->getData('fk_cond_reglement')) {
                return 1;
            }
            if ($field === 'fk_mode_reglement' && !(int) $this->getData('fk_mode_reglement')) {
                return 1;
            }

            return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('validate', 'modify', 'reopen', 'cancel', 'sendMail', 'addAcompte', 'useRemise', 'removeFromUserCommission', 'removeFromEntrepotCommission', 'addToCommission', 'convertToReduc', 'checkPa', 'createAcompteRemiseRbt', 'generatePDFDuplicata', 'setIrrevouvrable', 'checkPaiements', 'classifyPaid', 'setCommandeLinesNotBilled', 'linesToFacture'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID de la facture absent';
                return 0;
            }
        }

        global $conf, $langs;
        $status = (int) $this->getData('fk_statut');
        $type = (int) $this->getData('type');

        switch ($action) {
            case 'validate':
                // Vérif du statut: 
                if ($status !== Facture::STATUS_DRAFT) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est plus au statut brouillon';
                    return 0;
                }

                // Vérif du client: 
                $soc = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($soc)) {
                    $errors[] = 'Client absent ou invalide';
                }

                // Vérif des lignes: 
                $lines = $this->getLines('not_text');
                if (!count($lines)) {
                    $errors[] = 'Aucune ligne ajoutée  ' . $this->getLabel('to');
                }

                // Vérif du montant total: 
                if ((in_array($type, array(
                            Facture::TYPE_STANDARD,
                            Facture::TYPE_REPLACEMENT,
                            Facture::TYPE_DEPOSIT,
                            Facture::TYPE_PROFORMA,
                            Facture::TYPE_SITUATION
                        )) &&
                        (empty($conf->global->FACTURE_ENABLE_NEGATIVE) &&
                        (float) $this->getData('total_ttc') < 0))) {
                    if ($this->isLabelFemale()) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' négatives non autorisées';
                    } else {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' négatifs non autorisés';
                    }
                }

                if (($type == Facture::TYPE_CREDIT_NOTE && (float) $this->getData('total_ttc') > 0)) {
                    if ($this->isLabelFemale()) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' positives non autorisées';
                    } else {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' positifs non autorisés';
                    }
                }

                return (count($errors) ? 0 : 1);

            case 'modify':
                $errors[] = 'Interdiction totale de dévalider une facture';
                return 0;
//                if ($status !== Facture::STATUS_VALIDATED) {
//                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "Validé"';
//                    return 0;
//                }
//                // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees:
//                if ((float) $this->getRemainToPay() != $this->dol_object->total_ttc && empty($this->dol_object->paye)) {
//                    $errors[] = 'Un paiement a été effectué';
//                    return 0;
//                }
//
//                if ($this->dol_object->getVentilExportCompta() == 0) {
//                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a été exporté' . ($this->isLabelFemale() ? 'e' : '') . ' en compta';
//                    return 0;
//                }
//
//                if (!$this->dol_object->is_last_in_cycle()) {
//                    $errors[] = $langs->trans("NotLastInCycle");
//                    return 0;
//                }
//
//                if ($this->dol_object->getIdReplacingInvoice()) {
//                    $errors[] = $langs->trans("DisabledBecauseReplacedInvoice");
//                    return 0;
//                }
//                return 1;

            case 'reopen':
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);

                if (in_array($type, array(
                            Facture::TYPE_CREDIT_NOTE,
                            Facture::TYPE_DEPOSIT
                        )) && !empty($discount->id)) {
                    $errors[] = 'Une remise a été crée à partir de ' . $this->getLabel('this');
                    return 0;
                }

                if (in_array($type, array(
                            Facture::TYPE_STANDARD,
                            Facture::TYPE_REPLACEMENT
                        )) || (empty($discount->id))) {
                    if (!in_array($status, array(2, 3)) || ($status === 1 && !(int) $this->dol_object->paye)) {
                        $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permet pas sa réouverture';
                        return 0;
                    }
                }

                if ($this->dol_object->getIdReplacingInvoice() || $this->dol_object->close_code == 'replaced') {
                    $errors[] = $langs->trans("DisabledBecauseReplacedInvoice");
                    return 0;
                }
                return 1;

            case 'cancel':
                $remainToPay = (float) $this->getRemainToPay();
                if (!empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED)) {
                    $errors[] = 'L\'annulation des factures n\'est pas autorisée';
                    return 0;
                }

                if ((int) $this->getData('fk_statut') !== 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut Validé';
                    return 0;
                }

                if ((int) $this->dol_object->paye) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est classé' . $this->e() . ' payé' . $this->e();
                    return 0;
                }

                if (!$remainToPay) {
                    $errors[] = 'Il n\'y a pas de reste à payer';
                    return 0;
                }

                if ($this->dol_object->getIdReplacingInvoice()) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a été remplacé' . $this->e();
                    return 0;
                }
                return 1;

            case 'sendMail':
                if (empty($conf->global->FACTURE_SENDBYEMAIL_FOR_ALL_STATUS) && !in_array($status, array(
                            Facture::STATUS_VALIDATED,
                            Facture::STATUS_CLOSED))) {
                    $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permet pas l\'envoi par e-mail';
                    return 0;
                }

                if ($this->dol_object->getIdReplacingInvoice()) {
                    $errors[] = $langs->trans("DisabledBecauseReplacedInvoice");
                    return 0;
                }

                return 1;

            case 'addAcompte':
                if ($type !== Facture::TYPE_STANDARD) {
                    $errors[] = 'Ajout d\'accompte possible uniquement pour les factures standards';
                    return 0;
                }
                if ((int) $this->dol_object->paye) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est classé' . $this->e() . ' "payé' . $this->e() . '"';
                    return 0;
                }

                if (!in_array($status, array(0, 1))) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "brouillon" ou "validé' . $this->e() . '"';
                    return 0;
                }
                return (int) parent::isActionAllowed($action, $errors);

            case 'convertToReduc':
                if (!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD))) {
                    $errors[] = 'Conversion en remise non autorisée pour ce type de facture';
                    return 0;
                }

//                $discount = new DiscountAbsolute($this->db->db);
//                $discount->fetch(0, $this->id);
//
//                if (BimpObject::objectLoaded($discount)) {
//                    $errors[] = 'Une remise à déjà été créée à partir de ' . $this->getLabel('this');
//                    return 0;
//                }


                if ($type === Facture::TYPE_DEPOSIT) {
                    if (!(int) $this->dol_object->paye) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas classé' . $this->e() . ' "payé' . $this->e() . '"';
                        return 0;
                    }
                    if (!in_array($status, array(1, 2))) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé" ou "fermé"';
                        return 0;
                    }
                } else {
                    if ((int) $this->dol_object->paye) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est classé' . $this->e() . ' "payé' . $this->e() . '"';
                        return 0;
                    }
                    if ($status !== 1) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '"';
                        return 0;
                    }
                }

                $remainToPay = $this->getRemainToPay();

                switch ($type) {
                    case Facture::TYPE_STANDARD:
                        if ($remainToPay >= 0) {
                            $errors[] = 'Il n\'y a pas de trop-perçu pour cette facture';
                            return 0;
                        }
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
                        if ($remainToPay >= 0) {
                            $errors[] = 'Il n\'y a aucun montant restant à rembourser pour cet avoir';
                            return 0;
                        }
                        break;

                    case Facture::TYPE_DEPOSIT:
                        if ($remainToPay) {
                            $errors[] = 'Cet facture d\'acompte n\'est pas entièrement payée';
                            return 0;
                        }
                        break;
                }
                return 1;

            case 'payBack':
                if (!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD, Facture::TYPE_REPLACEMENT))) {
                    $errors[] = 'Remboursement du trop perçu non autorisé pour ce type de facture';
                    return 0;
                }

                if (!in_array($status, array(1, 2))) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '"';
                    return 0;
                }

                if ((int) $this->dol_object->paye) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est classé' . $this->e() . ' "payé' . $this->e() . '"';
                    return 0;
                }

                $remainToPay = $this->getRemainToPay(false, true);

                switch ($type) {
                    case Facture::TYPE_STANDARD:
                    case Facture::TYPE_REPLACEMENT:
                    case Facture::TYPE_DEPOSIT:
                        if ($remainToPay >= 0) {
                            $errors[] = 'Il n\'y a aucun trop-perçu à rembourser pour ' . $this->getLabel('this');
                            return 0;
                        }
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
                        if ($remainToPay <= 0) {
                            $errors[] = 'Il n\'y a pas de trop remboursé à payer par le client pour cet avoir';
                            return 0;
                        }
                        break;
                }
                return 1;

            case 'removeFromUserCommission':
                if (!(int) $this->getData('id_user_commission')) {
                    $errors[] = 'Cette facture n\'est associée à aucune commission utilisateur';
                    return 0;
                }
                $commission = $this->getChildObject('user_commission');
                if (!BimpObject::objectLoaded($commission)) {
                    $errors[] = 'La commission #' . $this->getData('id_user_commission') . ' n\'existe plus';
                    return 0;
                }
                if ((int) $commission->getData('status') !== 0) {
                    $errors[] = 'La commission #' . $commission->id . ' n\'est plus au statut "brouillon"';
                    return 0;
                }
                return 1;

            case 'removeFromEntrepotCommission':
                if (!(int) $this->getData('id_entrepot_commission')) {
                    $errors[] = 'Cette facture n\'est associée à aucune commission entrepôt';
                    return 0;
                }
                $commission = $this->getChildObject('entrepot_commission');
                if (!BimpObject::objectLoaded($commission)) {
                    $errors[] = 'La commission #' . $this->getData('id_entrepot_commission') . ' n\'existe plus';
                    return 0;
                }
                if ((int) $commission->getData('status') !== 0) {
                    $errors[] = 'La commission #' . $commission->id . ' n\'est plus au statut "brouillon"';
                    return 0;
                }
                return 1;

            case 'addToCommission':
                if (!in_array($type, array(0, 1, 2))) {
                    $errors[] = 'Cette facture ne peut pas être attribuée à une commission';
                    return 0;
                }
                if (!in_array($status, array(1, 2))) {
                    $errors[] = 'Cette facture n\'est pas validée';
                    return 0;
                }
                if ((int) $this->getData('id_user_commission') && (int) $this->getData('id_entrepot_commission')) {
                    $errors[] = 'Cette facture n\'est attribuable à aucune commission';
                    return 0;
                }
                return 1;

            case 'checkPa':
                if (!in_array($type, array(0, 1, 2))) {
                    $errors[] = 'Ce type de facture n\'est pas élligible pour le processus de vérification des prix d\'achat';
                    return 0;
                }
                return 1;

            case 'createAcompteRemiseRbt':
                if ($type !== Facture::TYPE_DEPOSIT) {
                    $errors[] = 'Cette facture n\'est pas de type facture d\'acompte';
                    return 0;
                }
                return 1;

            case 'generatePDFDuplicata':
                if (!defined('MOD_DEV')) {
                    $file = $this->getFilesDir() . $this->getRef() . '.pdf';
                    if (!file_exists($file)) {
                        $errors[] = 'Fichier original non généré';
                        return 0;
                    }
                }
                return 1;

            case 'setIrrecouvrable':
                if (!in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE))) {
                    $errors[] = 'Seules les factures standards et d\'acompte peuvent être déclarées "Irrévouvrables"';
                    return 0;
                }

                if (!in_array($status, array(Facture::STATUS_VALIDATED))) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'a pas le statut "Validé' . $this->e() . '"';
                    return 0;
                }

                $remainToPay = $this->getRemainToPay(false, true);
                if (!$remainToPay) {
                    $errors[] = 'Il n\'y a pas de reste à payer pour ' . $this->getLabel('this');
                    return 0;
                }

                if ((int) $this->getData('paye')) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a été classé' . $this->e() . ' "payé' . $this->e() . '"';
                    return 0;
                }
                return 1;

            case 'classifyPaid':
                if ((int) $this->getData('fk_statut') !== 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'a pas le statut "Validé' . $this->e() . '"';
                }
                if ($this->dol_object->paye) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est déjà classé' . $this->e() . ' payé' . $this->e();
                }
                return (count($errors) ? 0 : 1);

            case 'setCommandeLinesNotBilled':
                if (!in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE))) {
                    $errors[] = 'Opération non permise pour ce type de facture';
                    return 0;
                }
                if ($status < 2) {
                    $errors[] = 'Le statut actuel ' . $this->getLabel('of_the') . ' ne permet pas cette opération';
                    return 0;
                }
                $avoirs = $this->db->getRows('facture', 'fk_facture_source = ' . $this->id, null, 'array', array('rowid'));

                if (!is_array($avoirs) || empty($avoirs)) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'a pas été annulé' . $this->e();
                    return 0;
                }

                $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
                $asso = new BimpAssociation($instance, 'factures');

                $commandes = $asso->getObjectsList($this->id);

                $has_comm_lines = false;
                foreach ($commandes as $id_commande) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

                    if (BimpObject::objectLoaded($commande)) {
                        $lines = $commande->getLines('not_text');

                        foreach ($lines as $line) {
                            $factures = $line->getData('factures');

                            if (is_array($factures)) {
                                foreach ($factures as $id_facture => $fac_data) {
                                    if ((int) $id_facture === (int) $this->id) {
                                        if ((float) $fac_data['qty'] || (isset($fac_data['equipments']) && !empty($fac_data['equipments']))) {
                                            $has_comm_lines = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$has_comm_lines) {
                    $errors[] = 'Aucune ligne de commande associée à ' . $this->getLabel('this');
                    return 0;
                }
                return 1;

            case 'linesToFacture':
                if ($type !== Facture::TYPE_CREDIT_NOTE) {
                    $errors[] = ucfirst($this->getLabel('this')) . ' n\'est pas un avoir';
                    return 0;
                }
                if ($status < 1) {
                    $errors[] = 'Le statut actuel ' . $this->getLabel('of_this') . ' ne permet pas cette opération';
                    return 0;
                }
                return 1;
        }

        return (int) parent::isActionAllowed($action, $errors);
    }

    public function showUserCommission()
    {
        if ((int) $this->getData('id_user_commission') > 0) {
            return 1;
        }

        return 0;
    }

    public function showEntrepotCommission()
    {
        if ((int) $this->getData('id_entrepot_commission') > 0) {
            return 1;
        }

        return 0;
    }

    public function iAmAdminRedirect()
    {
        global $user;
        if (in_array($user->id, array(7)) || $user->admin)
            return true;
        parent::iAmAdminRedirect();
    }

    public function isIrrecouvrable()
    {
        return ((int) $this->getData('paiement_status') === 5 ? 1 : 0);
    }

    public function showPrelevement()
    {
        $id_mode_regelement = (int) $this->getData('fk_mode_reglement');

        $code = (string) $this->db->getValue('c_paiement', 'code', 'id = ' . $id_mode_regelement);

        if (in_array($code, array('PRELEV', 'PRE'))) {
            return 1;
        }

        return 0;
    }

    // Getters params:

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        if (!$this->isLoaded()) {
            return array();
        }

        $buttons = parent::getActionsButtons();
        $status = (int) $this->getData('fk_statut');
        $ref = $this->getRef();
        $type = (int) $this->getData('type');
        $paye = (int) $this->dol_object->paye;
        $remainToPay = $this->getRemainToPay();
        $total_paid = (float) $this->getTotalPaid();
        $id_replacing_invoice = $this->dol_object->getIdReplacingInvoice();

        $soc = $this->getChildObject('client');
        $discount = new DiscountAbsolute($this->db->db);
        $discount->fetch(0, $this->id);

        // Valider:
        $error_msg = '';
        $errors = array();

        if ($this->isActionAllowed('validate', $errors)) {
            if ($this->canSetAction('validate')) {
                $buttons[] = array(
                    'label'   => 'Valider',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('validate', array('new_ref' => $numref), array(
                        'form_name' => 'validate'
                    ))
                );
            } else {
                $error_msg = 'Vous n\'avez pas la permission de valider cette facture';
            }
        } elseif ($status === 0) {
            $error_msg = BimpTools::getMsgFromArray($errors);
        }

        if ($error_msg) {
            $buttons[] = array(
                'label'    => 'Valider',
                'icon'     => 'fas_check',
                'onclick'  => '',
                'disabled' => 1,
                'popover'  => $error_msg
            );
        }

        // Editer une facture deja validee, sans paiement effectue et pas exportée en compta
        if ($status === Facture::STATUS_VALIDATED) {
            if ($this->canSetAction('modify')) {
                if ($this->isActionAllowed('modify', $errors)) {
                    $buttons[] = array(
                        'label'   => 'Modifier',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('modify', array(), array(
                            'confirm_msg' => strip_tags($langs->trans('ConfirmUnvalidateBill', $ref))
                    )));
                }
            }
        }

        // Réouverture:
        if ($this->canSetAction('reopen')) {
            $errors = array();
            if ($this->isActionAllowed('reopen', $errors)) {
                $buttons[] = array(
                    'label'   => 'Réouvrir',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la réouverture de ' . $this->getLabel('this')
                    ))
                );
            } elseif (in_array($status, array(2, 3))) {
                if ($this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'    => 'Réouvrir',
                        'icon'     => 'fas_undo',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => BimpTools::getMsgFromArray($errors)
                    );
                }
            }
        }

        // Envoi mail:
        if ($this->canSetAction('sendMail')) {
            $errors = array();
            if ($this->isActionAllowed('sendMail', $errors)) {
                $buttons[] = array(
                    'label'   => 'Envoyer par e-mail',
                    'icon'    => 'fas_envelope',
                    'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ))
                );
            } elseif (in_array($status, array(1, 2))) {
                $buttons[] = array(
                    'label'    => 'Envoyer par email',
                    'icon'     => 'fas_envelope',
                    'onclick'  => '',
                    'disabled' => 1,
                    'popover'  => BimpTools::getMsgFromArray($errors)
                );
            }
        }

        if ($this->isLoaded()) {
            // Demande de prélèvement
            $langs->load("withdrawals");
            if ($status > 0 && !$paye && $user->rights->prelevement->bons->creer) {
                $url = DOL_URL_ROOT . '/compta/facture/prelevement.php?facid=' . $this->id;
                $buttons[] = array(
                    'label'   => $langs->trans("MakeWithdrawRequest"),
                    'icon'    => 'fas_money-check-alt',
                    'onclick' => 'window.location = \'' . $url . '\''
                );
            }

            // Réglement:
            if ($type !== Facture::TYPE_PROFORMA && $status === 1 && !$paye && $user->rights->facture->paiement) {
                $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');
                $id_mode_paiement = ((int) $this->getData('fk_mode_reglement') ? (int) $this->getData('fk_mode_reglement') : (int) BimpCore::getConf('default_id_mode_paiement'));
                if ($id_mode_paiement) {
                    $id_mode_paiement = dol_getIdFromCode($this->db->db, $id_mode_paiement, 'c_paiement', 'id', 'code');
                } else {
                    $id_mode_paiement = '';
                }
                $onclick = $paiement->getJsLoadModalForm('default', 'Paiement Factures', array(
                    'fields' => array(
                        'is_rbt'           => ($type === Facture::TYPE_CREDIT_NOTE ? 1 : 0),
                        'id_client'        => (int) $this->getData('fk_soc'),
                        'id_mode_paiement' => $id_mode_paiement
                    )
                ));

                $buttons[] = array(
                    'label'   => 'Saisir ' . ($type === Facture::TYPE_CREDIT_NOTE ? 'remboursement' : 'règlement'),
                    'icon'    => 'fas_euro-sign',
                    'onclick' => $onclick
                );
            }

            // Remboursement trop perçu / payé. 
            if ($this->isActionAllowed('payBack') && $this->canSetAction('payBack')) {
                if ($type === Facture::TYPE_CREDIT_NOTE) {
                    $label = 'Paiement trop remboursé';
                } else {
                    $label = 'Remboursement trop perçu';
                }

                $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');

                $onclick = $paiement->getJsLoadModalForm('single', $label, array(
                    'fields' => array(
                        'id_facture'    => (int) $this->id,
                        'single_amount' => (string) abs($this->getRemainToPay())
                    )
                ));

                $buttons[] = array(
                    'label'   => $label,
                    'icon'    => 'fas_reply',
                    'onclick' => $onclick
                );
            }

            // Conversions en réduction
            if ($this->isActionAllowed('convertToReduc') && $this->canSetAction('convertToReduc')) {
                switch ($type) {
                    case Facture::TYPE_STANDARD:
                        $buttons[] = array(
                            'label'   => 'Convertir le trop perçu en remise',
                            'icon'    => 'fas_percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('ExcessReceived'))))
                            ))
                        );
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
                        $buttons[] = array(
                            'label'   => $langs->trans('Convertir le reste à rembourser en remise'),
                            'icon'    => 'fas_percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('CreditNote'))))
                            ))
                        );
                        break;

                    case Facture::TYPE_DEPOSIT:
                        $buttons[] = array(
                            'label'   => 'Convertir en remise',
                            'icon'    => 'fas_percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('Deposit'))))
                            ))
                        );
                        break;
                }
            }

            // Classée payée: 
            if ($this->isActionAllowed('classifyPaid') && $this->canSetAction('classifyPaid')) {
                $buttons[] = array(
                    'label'   => $langs->trans('ClassifyPaid'),
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('classifyPaid', array(), array(
                        'form_name' => 'paid_partially'
                    ))
                );
            }

            // Irrécouvrable: 
            if ($this->isActionAllowed('setIrrecouvrable') && $this->canSetAction('setIrrecouvrable')) {
                $buttons[] = array(
                    'label'   => BimpTools::ucfirst($this->getLabel('')) . ' irrécouvrable',
                    'icon'    => 'fas_exclamation',
                    'onclick' => $this->getJsActionOnclick('setIrrecouvrable', array(), array(
                        'form_name' => 'irrecouvrable'
                    ))
                );
            }

            // Abandonner: 
            if (!$total_paid) {
                if ($this->canSetAction('cancel') && empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED)) {
                    $errors = array();
                    if ($this->isActionAllowed('cancel', $errors)) {
                        $buttons[] = array(
                            'label'   => $langs->trans('ClassifyCanceled'),
                            'icon'    => 'fas_times',
                            'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                                'form_name' => 'cancel'
                            ))
                        );
                    } elseif (count($errors)) {
                        $buttons[] = array(
                            'label'    => $langs->trans('ClassifyCanceled'),
                            'icon'     => 'fas_times',
                            'onclick'  => '',
                            'disabled' => 1,
                            'popover'  => BimpTools::getMsgFromArray($errors)
                        );
                    }
                }
            }

            // Cloner: 
            if ($this->can("create")) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'fas_copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(
                        'datef' => date('Y-m-d')
                            ), array(
                        'form_name' => 'duplicate'
                    ))
                );
            }

            if ($this->can("create")) {
                if (in_array($type, array(Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD, Facture::TYPE_PROFORMA))) {
                    if ($status == 0) {
                        // Convertir en facture modèle:
                        if (!$id_replacing_invoice && count($this->dol_object->lines) > 0) {
                            $url = DOL_URL_ROOT . '/compta/facture/fiche-rec.php?facid=' . $this->id . '&action=create';
                            $buttons[] = array(
                                'label'   => $langs->trans("ChangeIntoRepeatableInvoice"),
                                'icon'    => 'fas_file-export',
                                'onclick' => 'window.location = \'' . $url . '\';'
                            );
                        }
                    } elseif ($type !== Facture::TYPE_DEPOSIT) {
                        // Créer un avoir:
                        $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                        $values = array(
                            'fields' => array(
                                'fk_soc'                => (int) $this->getData('fk_soc'),
                                'ref_client'            => $this->getData('ref_client'),
                                'type'                  => Facture::TYPE_CREDIT_NOTE,
                                'id_facture_to_correct' => (int) $this->id,
                                'fk_account'            => (int) $this->getData('fk_account'),
                                'entrepot'              => (int) $this->getData('entrepot'),
                                'centre'                => $this->getData('centre'),
                                'ef_type'               => $this->getData('ef_type'),
                                'fk_cond_reglement'     => $this->getData('fk_cond_reglement'),
                                'fk_mode_reglement'     => $this->getData('fk_mode_reglement')
                            )
                        );
                        $onclick = $facture->getJsLoadModalForm('default', 'Créer un avoir', $values, null, 'redirect');
                        $buttons[] = array(
                            'label'   => $langs->trans("CreateCreditNote"),
                            'icon'    => 'fas_file-import',
                            'onclick' => $onclick
                        );
                    }
                } elseif ($type === Facture::TYPE_CREDIT_NOTE) {
                    // Refacturer: 
                    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                    $values = array(
                        'fields' => array(
                            'fk_soc'                => (int) $this->getData('fk_soc'),
                            'ref_client'            => $this->getData('ref_client'),
                            'type'                  => Facture::TYPE_STANDARD,
                            'id_avoir_to_refacture' => (int) $this->id,
                            'fk_account'            => (int) $this->getData('fk_account'),
                            'entrepot'              => (int) $this->getData('entrepot'),
                            'centre'                => $this->getData('centre'),
                            'ef_type'               => $this->getData('ef_type'),
                            'fk_cond_reglement'     => $this->getData('fk_cond_reglement'),
                            'fk_mode_reglement'     => $this->getData('fk_mode_reglement')
                        )
                    );
                    $onclick = $facture->getJsLoadModalForm('refacture', 'Refacturation de l\\\'avoir ' . $this->getRef(), $values, null, 'redirect');
                    $buttons[] = array(
                        'label'   => 'Refacturer',
                        'icon'    => 'fas_redo',
                        'onclick' => $onclick
                    );
                }
            }

            // Refacturer vers facture existante: 
            if ($this->isActionAllowed('linesToFacture') && $this->canSetAction('linesToFacture')) {
                $onclick = $this->getJsActionOnclick('linesToFacture', array(), array(
                    'form_name' => 'lines_to_facture'
                ));
                $buttons[] = array(
                    'label'   => 'Refacturer vers une facture existante',
                    'icon'    => 'fas_redo',
                    'onclick' => $onclick
                );
            }
        }

//         Ajout à une commission: 

        if ($this->isActionAllowed('addToCommission') && $this->canSetAction('addToCommission')) {
            $buttons[] = array(
                'label'   => 'Ajouter à une commission',
                'icon'    => 'fas_comment-dollar',
                'onclick' => $this->getJsActionOnclick('addToCommission', array(), array(
                    'form_name' => 'add_to_commission'
                ))
            );
        }

        // Vérifier les prix d'achat:
        if ($this->isActionAllowed('checkcPa') && $this->canSetAction('checkPa')) {
            $buttons[] = array(
                'label'   => 'Vérifier les prix d\'achat',
                'icon'    => 'fas_search-dollar',
                'onclick' => $this->getJsActionOnclick('checkPa', array(), array(
                    'form_name'      => 'check_pa',
                    'on_form_submit' => 'function($form, extra_data) { return onCheckPaFactureFormSubmit($form, extra_data); }'
                ))
            );
        }

        // Vérifier les paiements: 
        if ($this->isActionAllowed('checkPaiements') && $this->canSetAction('checkPaiements')) {
            $buttons[] = array(
                'label'   => 'Vérifier les paiements',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('checkPaiements')
            );
        }

        // Vérifier les paiements: 
        if ($this->isActionAllowed('checkMargin') && $this->canSetAction('checkMargin')) {
            $buttons[] = array(
                'label'   => 'Vérifier total achats / marges (reval OK)',
                'icon'    => 'fas_check-circle',
                'onclick' => $this->getJsActionOnclick('checkMargin')
            );
        }

        return $buttons;
    }

    public function getLogistiqueListExtraButtons()
    {
        $buttons = array();

        $errors = array();
        if ($this->isActionAllowed('setCommandeLinesNotBilled', $errors) && $this->canSetAction('setCommandeLinesNotBilled')) {
            $buttons[] = array(
                'label'   => 'Annuler la facturation des lignes de commandes associées',
                'icon'    => 'fas_times-circle',
                'onclick' => $this->getJsActionOnclick('setCommandeLinesNotBilled', array(), array(
                    'confirm_msg' => 'Veuillez confirmer'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraBulkActions()
    {
        $actions = array();

        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Fichiers PDF',
                'icon'    => 'fas_file-pdf',
                'onclick' => $this->getJsBulkActionOnclick('generateBulkPdf', array(), array('single_action' => true))
//                'onclick' => 'setSelectedObjectsAction($(this), \'list_id\', \'generateBulkPdf\', {}, null, null)'
            );
        }

        return $actions;
    }

    public function getListExtraListActions()
    {
        $actions = array();

        if ($this->canSetAction('classifyPaid')) {
            $actions[] = array(
                'label'   => 'Classer Payé',
                'icon'    => 'fas_file-pdf',
                'action'  => 'classifyPaidMasse',
                'form_name'=> 'paid_partially'
            );
        }

        return $actions;
    }

    public function getCommissionListButtons($comm_type = null)
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ((is_null($comm_type) || (int) $comm_type === 1) && (int) $this->getData('id_user_commission')) {
                if ($this->isActionAllowed('removeFromUserCommission') && $this->canSetAction('removeFromUserCommission')) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la commission utilisateur',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('removeFromUserCommission', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait de cette facture de la commission #' . (int) $this->getData('id_user_commission')
                        ))
                    );
                }
            }

            if ((is_null($comm_type) || (int) $comm_type === 2) && (int) $this->getData('id_entrepot_commission')) {
                if ($this->isActionAllowed('removeFromEntrepotCommission') && $this->canSetAction('removeFromEntrepotCommission')) {
                    $buttons[] = array(
                        'label'   => 'Retirer de la commission entrepôt',
                        'icon'    => 'fas_times',
                        'onclick' => $this->getJsActionOnclick('removeFromEntrepotCommission', array(), array(
                            'confirm_msg' => 'Veuillez confirmer le retrait de cette facture de la commission #' . (int) $this->getData('id_entrepot_commission')
                        ))
                    );
                }
            }

            if ($this->isActionAllowed('addToCommission') && $this->canSetAction('addToCommission')) {
                if (is_null($comm_type) ||
                        ($comm_type === 1 && !(int) $this->getData('id_user_commission')) ||
                        ($comm_type === 2 && !(int) $this->getData('id_entrepot_commission'))) {
                    $buttons[] = array(
                        'label'   => 'Ajouter à une commission',
                        'icon'    => 'fas_plus',
                        'onclick' => $this->getJsActionOnclick('addToCommission', array(), array(
                            'form_name' => 'add_to_commission'
                        ))
                    );
                }
            }
        }

        return $buttons;
    }

    public function getCustomFilterSqlFilters($field_name, $values, &$filters, &$joins, &$errors = array(), $excluded = false)
    {
        switch ($field_name) {
            case 'revals_brouillons':
            case 'revals_prevues':
            case 'revals_ok':
                switch ($field_name) {
                    case 'revals_brouillons': $status = '0';
                        break;
                    case 'revals_prevues': $status = '0,1';
                        break;
                    case 'revals_ok': $status = '1';
                        break;
                }
                $revals_filters = array();
                foreach ($values as $value) {
                    $revals_filters[] = BC_Filter::getRangeSqlFilter($value, $errors, false, $excluded);
                }

                if (!empty($revals_filters)) {
                    $sql = '(SELECT SUM(reval.amount * reval.qty) FROM ' . MAIN_DB_PREFIX . 'bimp_revalorisation reval';
                    $sql .= ' WHERE reval.id_facture = a.rowid';
                    $sql .= ' AND reval.status IN (' . $status . '))';

                    if ($excluded) {
                        $filters[$sql] = array(
                            'and' => $revals_filters
                        );
                    } else {
                        $filters[$sql] = array(
                            'or_field' => $revals_filters
                        );
                    }
                }
                break;

            case 'tech_sav':
                $joins['sav'] = array(
                    'table' => 'bs_sav',
                    'alias' => 'sav',
                    'on'    => '(a.rowid = sav.id_facture OR a.rowid = sav.id_facture_acompte OR a.rowid = sav.id_facture_avoir)'
                );

                $filters['sav.id_user_tech'] = array(
                    ($excluded ? 'not_' : '') . 'in' => $values
                );
                break;
        }

        return parent::getCustomFilterSqlFilters($field_name, $values, $filters, $joins, $errors, $excluded);
    }

    // Getters Array: 

    public function getSelectTypesArray()
    {
        global $langs, $conf;

        $origin = BimpTools::getValue('origin', BimpTools::getValue('fields/origin', 0));
        $id_origin = BimpTools::getValue('id_origin', BimpTools::getValue('fields/id_origin', 0));

        $id_soc = (int) $this->getData('fk_soc');

        $types = array(
            '0' => array(
                'label' => self::$types[0]['label'],
                'help'  => $langs->transnoentities("InvoiceStandardDesc")
            )
        );

        if (!$origin || (in_array($origin, array('propal', 'commande')) && $id_origin)) {
            if (empty($conf->global->INVOICE_DISABLE_DEPOSIT)) {
                $types['3'] = array(
                    'label' => self::$types[3]['label'],
                    'help'  => $langs->transnoentities("InvoiceDepositDesc")
                );
            }
        }

        if ($id_soc) {
            // todo : implémenter la prise en charge des factues de situation si rétabli dans la config. 
//            if (!empty($conf->global->INVOICE_USE_SITUATION)) {
//                $types['5'] = array(
//                    'label' => self::$types[5]['label'],
//                    'help'  => ''
//                );
//            }
            if (empty($conf->global->INVOICE_DISABLE_REPLACEMENT)) {
                $types['1'] = array(
                    'label' => self::$types[1]['label'],
                    'help'  => $langs->transnoentities("InvoiceReplacementDesc")
                );
            }
        }

        if (!$origin) {
            $types['2'] = array(
                'label' => self::$types[2]['label'],
                'help'  => $langs->transnoentities("InvoiceAvoirDesc")
            );
        }

        return $types;
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFFactures')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
        }

        return ModelePDFFactures::liste_modeles($this->db->db);
    }

    public function getCloseReasonsArray()
    {
        global $langs, $conf;

        $remainToPay = $this->getRemainToPay();
        return array(
            'discount_vat'     => array(
                'label' => $langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat", $remainToPay, $langs->trans("Currency" . $conf->currency)),
                'help'  => $langs->trans("HelpEscompte") . '<br><br>' . $langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc")
            ),
            'paiementnotsaved' => array(
                'label' => 'Le paiement a été effectué mais non enregistré'
            ),
            'inf_one_euro'     => array(
                'label' => 'Le reste à payer (' . BimpTools::displayMoneyValue($remainToPay) . ') est inférieur à 1€'
            ),
            'paid'             => array(
                'label' => 'Autre'
            )
        );
    }

    public function getCancelReasonsArray()
    {
        global $langs;

        return array(
            'badcustomer' => array(
                'label' => $langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $this->getRef()),
                'help'  => $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc")
            ),
//            'irrecouvrable' => array(
//                'label' => 'Facture irrécouvrable'
//            ),
            'abandon'     => array(
                'label' => $langs->transnoentities("ConfirmClassifyAbandonReasonOther"),
                'help'  => $langs->trans("ConfirmClassifyAbandonReasonOtherDesc")
            )
        );
    }

    public function getTypesDepositArray()
    {
        global $langs;

        return array(
            'amount'   => $langs->trans('FixAmount'),
            'variable' => $langs->trans('VarAmount')
        );
    }

    public function getReplacedInvoicesArray()
    {
        $factures = array();

        if ((int) $this->getData('fk_soc')) {
            $facture = new Facture($this->db->db);
            $list = $facture->list_replacable_invoices((int) $this->getData('fk_soc'));

            if (is_array($list)) {
                foreach ($list as $fac) {
                    $factures[(int) $fac['id']] = $fac['ref'] . ' (' . $facture->LibStatut(0, $fac['status']) . ')';
                }
            }
        }

        return $factures;
    }

    public function getFacturesToCorrectArray()
    {
        $options = array(
            0 => ''
        );

        if ((int) $this->getData('fk_soc')) {
            $facture = new Facture($this->db->db);
            $list = $facture->list_qualified_avoir_invoices((int) $this->getData('fk_soc'));


            if (is_array($list)) {
                foreach ($list as $key => $values) {
                    $facture->id = $key;
                    $facture->ref = $values['ref'];
                    $facture->statut = $values['status'];
                    $facture->type = $values['type'];
                    $facture->paye = $values['paye'];

                    $options[$key] = $values['ref'] . ' (' . $facture->getLibStatut(1, $values['paymentornot']) . ')';
                }
            }
        }

        if (count($options) === 1) {
            global $langs;
            $options[0] = $langs->trans("NoInvoiceToCorrect");
        }

        return $options;
    }

    public function getDraftCommissionsArray()
    {
        if ((int) $this->isLoaded()) {
            BimpObject::loadClass('bimpfinanc', 'BimpCommission');
            $return = array();

            $id_entrepot = (int) $this->getData('entrepot');

            if ($id_entrepot) {
                if (!(int) $this->getData('id_user_commission')) {
                    $has_user_commissions = (int) $this->db->getValue('entrepot', 'has_users_commissions', '`rowid` = ' . $id_entrepot);
                    if ($has_user_commissions) {
                        $contacts = $this->dol_object->getIdContact('internal', 'SALESREPFOLL');
                        if (isset($contacts[0]) && $contacts[0]) {
                            $commissions = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpCommission', array(
                                        'type'    => BimpCommission::TYPE_USER,
                                        'id_user' => (int) $contacts[0],
                                        'status'  => 0
                            ));

                            foreach ($commissions as $commission) {
                                $dt = new DateTime($commission->getData('date'));
                                $return[(int) $commission->id] = $commission->getName() . '  - ' . $dt->format('d / m / Y');
                            }
                        }
                    }
                }

                if (!(int) $this->getData('id_entrepot_commission')) {
                    $has_entrepot_commissions = (int) $this->db->getValue('entrepot', 'has_entrepot_commissions', '`rowid` = ' . $id_entrepot);
                    if ($has_entrepot_commissions) {
                        $commissions = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpCommission', array(
                                    'type'        => BimpCommission::TYPE_ENTREPOT,
                                    'id_entrepot' => $id_entrepot,
                                    'status'      => 0
                        ));

                        foreach ($commissions as $commission) {
                            $dt = new DateTime($commission->getData('date'));
                            $return[(int) $commission->id] = $commission->getName() . ' - ' . $dt->format('d / m / Y');
                        }
                    }
                }
            }

            return $return;
        }

        return array();
    }

    public function getDraftFacturesForRefactureArray()
    {
        $factures = array();

        $fk_soc = (int) $this->getData('fk_soc');

        if ($fk_soc) {
            $rows = $this->db->getRows('facture', 'fk_statut = 0 AND type = 0 AND fk_soc = ' . (int) $fk_soc, null, 'array', array('rowid', 'facnumber'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    $factures[(int) $r['rowid']] = $r['facnumber'];
                }
            }
        }

        return $factures;
    }

    // Getters données: 

    public function getSumDiscountsUsed()
    {
        if ($this->isLoaded()) {
            $sql = 'SELECT SUM(r.amount_ttc) as amount FROM ' . MAIN_DB_PREFIX . 'societe_remise_except as r';
            $sql .= ' WHERE r.fk_facture = ' . (int) $this->id;

            $result = $this->db->executeS($sql, 'array');

            if (isset($result[0]['amount'])) {
                return (float) $result[0]['amount'];
            }
        }

        return 0;
    }

    public function getConvertedToReducAmount()
    {
        if ($this->isLoaded()) {
            BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch(0, $this->id);
            if (BimpObject::objectLoaded($discount)) {
                return(float) $discount->amount_ttc;
            }
        }

        return 0;
    }

    public function getSumPayments()
    {
        if ($this->isLoaded()) {
            return (float) $this->dol_object->getSommePaiement();
        }

        return 0;
    }

    public function getTotalPaid()
    {
        if ($this->isLoaded()) {
            $paid = (float) $this->getSumPayments();
            $paid += (float) $this->getSumDiscountsUsed();

            // La conversion en remise n'affecte pas le total des paiements dans le cas des factures d'accomptes. 

            if (in_array((int) $this->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE))) {
                $paid -= (float) $this->getConvertedToReducAmount();
            }

            return $paid;
        }
    }

    public function getRemainToPay($true_value = false, $round = true)
    {
        // $true_value: ne pas tenir compte du statut "payé". 

        if ($this->isLoaded()) {
            if (!$true_value && $this->dol_object->paye) {
                return 0;
            }

            $rtp = (float) $this->dol_object->total_ttc - (float) $this->getTotalPaid();

            if ($round) {
                if ($rtp > -0.01 && $rtp < 0.01) {
                    $rtp = 0;
                }

                $rtp = round($rtp, 2);
            }

            return $rtp;
        }

        return 0;
    }

    public function getRef($withGeneric = true)
    {
        return $this->getData('facnumber');
    }

    public function getPotentielRemise()
    {
        $return = array();
        if ($this->isLoaded()) {
            $sql = $this->db->db->query("SELECT r.amount_ttc, cdet.fk_commande FROM `" . MAIN_DB_PREFIX . "societe_remise_except` r, " . MAIN_DB_PREFIX . "commandedet cdet, " . MAIN_DB_PREFIX . "element_element el WHERE r.`discount_type` = 0 AND r.fk_facture IS NULL AND r.fk_facture_line IS NULL AND cdet.fk_remise_except = r.rowid AND cdet.fk_commande = el.fk_source AND el.sourcetype = 'commande' AND el.targettype = 'facture' AND el.fk_target = " . $this->id);
            while ($ln = $this->db->db->fetch_object($sql)) {
                $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $ln->fk_commande);
                $return[] = array($ln->amount_ttc, $commande);
            }
        }
        return $return;
    }

    public function getRefProperty()
    {
        return 'facnumber';
    }

    public function getModelPdf()
    {
        return $this->getData('model_pdf');
    }

    public function getDirOutput()
    {
        global $conf;

        return $conf->facture->dir_output;
    }

    public function getRequestIdFactureReplaced()
    {
        return BimpTools::getValue('id_facture_replaced', BimpTools::getValue('param_values/fields/id_facture_replaced', 0));
    }

    public function getRequestIdFactureToCorrect()
    {
        return BimpTools::getValue('id_facture_to_correct', BimpTools::getValue('param_values/fields/id_facture_to_correct', 0));
    }

    public function getTotalRevalorisations($recalculate = false)
    {
        $clef = "bimp_facture_" . $this->id . '_total_revalorisations';

        if (!$recalculate && isset(BimpCache::$cache[$clef])) {
            return BimpCache::$cache[$clef];
        }

        $totals = array(
            'attente'  => 0,
            'accepted' => 0,
            'refused'  => 0
        );

        if ($this->isLoaded()) {
            $revals = BimpCache::getBimpObjectObjects('bimpfinanc', 'BimpRevalorisation', array(
                        'id_facture' => (int) $this->id
            ));

            foreach ($revals as $reval) {
                switch ((int) $reval->getData('status')) {
                    case 0:
                        $totals['attente'] += $reval->getTotal();
                        break;

                    case 1:
                        $totals['accepted'] += $reval->getTotal();
                        break;

                    case 2:
                        $totals['refused'] += $reval->getTotal();
                        break;
                }
            }
        }

        BimpCache::$cache[$clef] = $totals;

        return $totals;
    }

    public function getDefaultMailTo()
    {
        $items = array();
        if ($this->isLoaded()) {
            $contacts = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING2');
            $emails = array();
            foreach ($contacts as $item) {
                if ((string) $item['email'] && (int) $item['id'] && !in_array($item['id'], $items)) {
                    $emails[] = $item['email'];
//                    $items[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                    $items[] = $item['id'];
                }
            }
            if (count($items == 0)) {
                $contacts = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING');
                $emails = array();
                foreach ($contacts as $item) {
                    if ((string) $item['email'] && (int) $item['id'] && !in_array($item['id'], $items)) {
                        $emails[] = $item['email'];
                        //                    $items[(int) $item['id']] = $item['libelle'] . ': ' . $item['firstname'] . ' ' . $item['lastname'] . ' (' . $item['email'] . ')';
                        $items[] = $item['id'];
                    }
                }
            }
        }

        return $items;
    }

    public function getCommandesOriginList()
    {
        if ($this->isLoaded()) {
            $items = BimpTools::getDolObjectLinkedObjectsListByTypes($this->dol_object, $this->db, array('commande'));

            if (isset($items['commande'])) {
                return $items['commande'];
            }
        }

        return array();
    }

    public function getIdContactForRelance($relance_idx)
    {
        $id_contact = 0;

//        if (in_array($relance_idx, array(1, 2, 3))) {
        $contacts = $this->dol_object->getIdContact('external', 'BILLING2');
        if (isset($contacts[0]) && (int) $contacts[0]) {
            $id_contact = (int) $contacts[0];
            $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
            if (!BimpObject::objectLoaded($contact)) {
                $id_contact = 0;
            }
            if (!$contact->getData('email')) {
                $id_contact = 0;
            }
        }
//        }

        if (!$id_contact) {
            $contacts = $this->dol_object->getIdContact('external', 'BILLING');
            if (isset($contacts[0]) && (int) $contacts[0]) {
                $id_contact = (int) $contacts[0];
                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);
                if (!BimpObject::objectLoaded($contact)) {
                    $id_contact = 0;
                }
                if (in_array($relance_idx, array(1, 2, 3)) && !$contact->getData('email')) {
                    $id_contact = 0;
                }
            }
        }

        return $id_contact;
    }

    public function getRelanceDates($delay = null)
    {
        $dates = array(
            'lim'    => '',
            'last'   => '',
            'next'   => '',
            'retard' => 0
        );

        if ($this->isLoaded()) {
            $dates['lim'] = $this->getData('date_lim_reglement');
            if (!$dates['lim']) {
                $dates['lim'] = $this->getData('datef');
            }

            if ((int) $this->getData('nb_relance') > 0) {
                if ((int) $this->getData('nb_relance') === 1) {
                    $delay = 8;
                } elseif (is_null($delay)) {
                    $delay = BimpCore::getConf('relance_paiements_facture_delay_days', 15);
                }

                $dates['last'] = (string) $this->getData('date_relance');
                if ($dates['last']) {
                    $dt_relance = new DateTime($dates['last']);
                } else {
                    $dt_relance = new DateTime($dates['lim']);
                }
                $dt_relance->add(new DateInterval('P' . $delay . 'D'));
                $dates['next'] = $dt_relance->format('Y-m-d');
            } else {
                $delay = 7;

                $client = $this->getChildObject('client');
                if (BimpObject::objectLoaded($client) && in_array((int) $client->getData('fk_typent'), explode(',', BimpCore::getConf('relance_paiements_extented_delay_type_ent', '')))) {
                    $delay = 15;
                }

                $dt_relance = new DateTime($dates['lim']);
                $dt_relance->add(new DateInterval('P' . $delay . 'D'));
                $dates['next'] = $dt_relance->format('Y-m-d');
            }

            if ((string) $this->getData('date_next_relance')) {
                if ($this->getData('date_next_relance') > $dates['next']) {
                    $dates['next'] = $this->getData('date_next_relance');
                }
            }
        }

        if ($dates['lim']) {
            $dates['retard'] = floor((strtotime(date('Y-m-d')) - strtotime($dates['lim'])) / 86400);
        }
        return $dates;
    }

    public function getTx_margeListTotal($filters, $joins)
    {
        $sql = 'SELECT SUM(a.marge_finale_ok) as marge, SUM(a.total_achat_reval_ok) as achats';
        $sql .= BimpTools::getSqlFrom('facture', $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $res = $this->db->executeS($sql, 'array');

        $tx = 0;

        if (isset($res[0])) {
            $res = $res[0];
            $marge = (float) BimpTools::getArrayValueFromPath($res, 'marge', 0);
            $achats = (float) BimpTools::getArrayValueFromPath($res, 'achats', 0);

            if ($marge && $achats) {
                $tx = ($marge / $achats) * 100;
            }
        }

        return $tx;
    }

    public function getTx_marqueListTotal($filters, $joins)
    {
        $sql = 'SELECT SUM(a.marge_finale_ok) as marge, SUM(a.total) as total';
        $sql .= BimpTools::getSqlFrom('facture', $joins);
        $sql .= BimpTools::getSqlWhere($filters);

        $res = $this->db->executeS($sql, 'array');

        $tx = 0;

        if (isset($res[0])) {
            $res = $res[0];
            $marge = (float) BimpTools::getArrayValueFromPath($res, 'marge', 0);
            $total = (float) BimpTools::getArrayValueFromPath($res, 'total', 0);

            if ($marge && $total) {
                $tx = ($marge / $total) * 100;
            }
        }

        return $tx;
    }

    // Affichages: 

    public function displayPotentielRemise()
    {
        $html = '';
        $htmlTab = array();
        foreach ($this->getPotentielRemise() as $tab) {
            $htmlTab[] = BimpTools::displayMoneyValue($tab[0]) . ' dans ' . $tab[1]->getLink();
        }
        return implode("<br/>", $htmlTab);
    }

    public function displayReval($mode = "ok")
    {
        $revals = $this->getTotalRevalorisations();

        if ($mode == "ok+marge")
            return BimpTools::displayMoneyValue($this->getData("marge") + $revals['accepted'], '', 0, 0, 0, 2, 1);
        if ($mode == "prevu+marge")
            return BimpTools::displayMoneyValue($this->getData("marge") + $revals['accepted'] + $revals['attente'], '', 0, 0, 0, 2, 1);
        if ($mode == "ok")
            return BimpTools::displayMoneyValue($revals['accepted'], '', 0, 0, 0, 2, 1);
    }

    public function displayTxMarge()
    {
        $marge = (float) $this->getData('marge_finale_ok');
        $total_achats = (float) $this->getData('total_achat_reval_ok');

        $tx = 0;
        if ($marge && $total_achats) {
            $tx = ($marge / $total_achats) * 100;
        }

        return BimpTools::displayFloatValue($tx, 2, ',', 0, 0, 0, 1, 1) . ' %';
    }

    public function displayTxMarque()
    {
        $marge = (float) $this->getData('marge_finale_ok');
        $total_ht = (float) $this->getData('total');

        $tx = 0;
        if ($marge && $total_ht) {
            $tx = ($marge / $total_ht) * 100;
        }

        return BimpTools::displayFloatValue($tx, 2, ',', 0, 0, 0, 1, 1) . ' %';
    }

    public function displayInfoSav($field)
    {
        if (!isset($cacheInstance['savs'])) {
            $cacheInstance['savs'] = array();
            if ($this->isLoaded()) {
                $cacheInstance['savs'] = BimpObject::getBimpObjectObjects('bimpsupport', 'BS_SAV', array('custom' => array('custom' => '(`id_facture_acompte` = ' . $this->id . ' || `id_facture` = ' . $this->id . ' ||`id_facture_avoir` = ' . $this->id . ')')));
            }
        }

        $result = array();
        global $modeCsv;
        foreach ($cacheInstance['savs'] as $sav) {
            if ($field == 'sav') {
                if (!$modeCsv)
                    $result[] = $sav->getLink();
                else
                    $result[] = $sav->getRef();
            }
            if (in_array($field, array('equipment', 'product', 'waranty'))) {
                $equipment = $sav->getChildObject('equipment');
                if ($field == 'equipment') {
                    if (!$modeCsv)
                        $result[] = $equipment->getLink();
                    else
                        $result[] = $equipment->getData('serial');
                }
                if ($field == 'waranty') {
                    $result[] = $equipment->getData('warranty_type');
                }
                if ($field == 'product') {
                    $prod = $equipment->getChildObject('product');
                    if (BimpObject::objectLoaded($prod)) {
                        if (!$modeCsv)
                            $result[] = $prod->getNomUrl();
                        else
                            $result[] = $prod->ref;
                    }
                }
            }
            if ($field == 'apple_number') {
                if (!isset($cacheInstance['repas'])) {
                    $cacheInstance['repas'] = BimpObject::getBimpObjectObjects('bimpapple', 'GSX_Repair', array('id_sav' => $sav->id));
                }
                foreach ($cacheInstance['repas'] as $repa) {
                    $result[] = $repa->getData('repair_number');
                }
            }
        }
        return implode('<br/>', $result);
    }

    public function displayZoneVenteField()
    {
        $zone_vente = $this->getData('zone_vente');
        $popover = "";

        switch ($zone_vente) {
            case self::BC_ZONE_FR:
                $text = "France";
                break;
            case self::BC_ZONE_UE:
                $text = "Union Européenne avec TVA";
                break;
            case self::BC_ZONE_UE_SANS_TVA:
                $text = "Union Européenne sans TVA";
                $popover = "Si livraison par nos soins sur Union Européenne et que le client nous a fourni son numéro de TVA intracommunautaire";
                break;
            case self::BC_ZONE_HORS_UE:
                $text = "Hors UE";
                $popover = "Si livraison par nos soins Hors Union Européenne";
                break;
        }
        $html = "";
        $html .= "<span";
        if (empty($popover)) {
            $html .= ">";
        } else {
            $html .= " class='bs-popover' ";
            $html .= BimpRender::renderPopoverData($popover) . ">";
        }
        $html .= $text;
        $html .= "</span>";

        return $html;
    }

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            return BimpTools::displayMoneyValue((float) $this->getTotalPaid(), 'EUR');
        }

        return '';
    }

    public function displayPaidStatus($icon = true, $short_label = false)
    {
        $html = '';

        if ($this->isLoaded()) {
            global $langs;

            $label = '';
            $class = '';
            $prefix = $short_label ? 'Short' : '';

            if ($this->dol_object->paye) {
                $class = 'success';

                switch ((int) $this->getData('type')) {
                    case Facture::TYPE_CREDIT_NOTE:
                        $label = $langs->trans('Bill' . $prefix . 'StatusPaidBackOrConverted');
                        break;

                    case Facture::TYPE_DEPOSIT:
                        $label = $langs->trans('Bill' . $prefix . 'StatusConverted');
                        break;

                    default:
                        $label = $langs->trans('Bill' . $prefix . 'StatusPaid');
                        break;
                }
            } else {
                if ($this->dol_object->totalpaye > 0) {
                    $class = 'warning';
                    $label = $langs->trans('Bill' . $prefix . 'StatusStarted');
                } else {
                    $class = 'danger';
                    $label = $langs->trans('Bill' . $prefix . 'StatusNotPaid');
                }
            }

            $html = '<span class="' . $class . '">';
            if ($icon) {
                $html .= '<i class="' . BimpRender::renderIconClass('fas_hand-holding-usd') . ' iconLeft"></i>';
            }
            $html .= $label . '</span>';
        }

        global $modeCSV;
        if ($modeCSV)
            return html_entity_decode($label);
        return $html;
    }

    public function displayType()
    {
        $html = $this->displayData('type');
        $extra = $this->displayTypeExtra(true);
        if ($extra) {
            $html .= '<br/>' . $extra;
        }

        return $html;
    }

    public function displayTypeExtra($with_actions = false)
    {
        $html = '';

        if ($this->isLoaded()) {
            global $langs;

            $type = (int) $this->getData('type');

            switch ($type) {
                case Facture::TYPE_REPLACEMENT:
                    $id_fac_src = (int) $this->getData('fk_facture_source');
                    if ($id_fac_src) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac_src);
                        if (!BimpObject::objectLoaded($facture)) {
                            $html .= BimpRender::renderAlerts('La facture remplacée n\'existe plus');
                        } else {
                            $html .= 'Remplace la facture ' . $facture->getLink();
                        }
                    } else {
                        $html .= BimpRender::renderAlerts('ID de la facture remplacée absent');
                    }
                    break;

                case Facture::TYPE_CREDIT_NOTE:
                    $id_fac_src = (int) $this->getData('fk_facture_source');
                    if ((int) $id_fac_src) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac_src);
                        if (!BimpObject::objectLoaded($facture)) {
                            $html .= BimpRender::renderAlerts('La facture corrigée n\'existe plus');
                        } else {
                            $html .= 'Correction de la facture ' . $facture->getLink();
                        }
                    }
                    break;
            }

            $avoirs = $this->dol_object->getListIdAvoirFromInvoice();
            if (count($avoirs)) {
                if ($html) {
                    $html .= '<br/>';
                }

                $html .= $langs->transnoentities("InvoiceHasAvoir");
                $fl = true;
                $facavoir = new Facture($this->db->db);
                foreach ($avoirs as $id_avoir) {
                    if ($fl) {
                        $html .= '';
                        $fl = false;
                    } else {
                        $html .= ', ';
                    }

                    $facavoir = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir);
                    if (BimpObject::objectLoaded($facavoir)) {
                        $html .= $facavoir->getLink();
                    } else {
                        $html .= '<span class="danger">L\'avoir d\'ID ' . $id_avoir . ' n\'existe plus</span>';
                    }
                }
            }

            $id_next = (int) $this->dol_object->getIdReplacingInvoice();
            if ($id_next > 0) {
                $facReplacement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_next);
                if ($html) {
                    $html .= '<br/>';
                }
                $html .= $langs->transnoentities("ReplacedByInvoice", $facReplacement->getLink());
            }

            if (in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $result = $discount->fetch(0, $this->id);
                if ($result > 0) {
                    if ($html) {
                        $html .= '<br/>';
                    }
                    $html .= BimpTools::ucfirst($this->getLabel('this')) . ' a été converti' . ($this->isLabelFemale() ? 'e' : '') . ' en ';
                    $html .= $discount->getNomUrl(1, 'discount');

                    if ((int) $discount->fk_facture) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $discount->fk_facture);
                        $html .= '<br/>Remise consommée dans la facture ' . (BimpObject::objectLoaded($facture) ? $facture->getNomUrl(0, 1, 1, 'full', 'default') : '#' . $discount->fk_facture);
                    } elseif ((int) $discount->fk_facture_line) {
                        $id_facture = $this->db->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $discount->fk_facture_line);
                        $facture = null;
                        if ((int) $id_facture) {
                            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
                        }
                        $html .= '<br/>Remise ajoutée à la facture ' . (BimpObject::objectLoaded($facture) ? $facture->getNomUrl(0, 1, 1, 'full', 'default') : ((int) $id_facture ? '#' . $id_facture : ' (ligne #' . $discount->fk_facture_line . ')'));
                    } else {
                        self::loadClass('bimpcore', 'Bimp_Societe');
                        $use_label = Bimp_Societe::getDiscountUsedLabel((int) $discount->id, true);

                        if ($use_label) {
                            $html .= '<br/>Remise ' . str_replace('Ajouté', 'ajoutée', $use_label);
                        } else {
                            $html .= '<br/>Remise non consommée';

                            if ($with_actions) {
                                if ($this->isActionAllowed('createAcompteRemiseRbt') && $this->canSetAction('createAcompteRemiseRbt')) {
                                    $html .= '<div style="margin: 10px 0; text-align: center">';
                                    $onclick = $this->getJsActionOnclick('createAcompteRemiseRbt', array(), array(
                                        'confirm_msg' => 'Veuillez confirmer la création d\\\'un remboursement de cet acompte'
                                    ));
                                    $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                                    $html .= BimpRender::renderIcon('fas_hand-holding-usd', 'iconLeft') . 'Rembourser cette remise';
                                    $html .= '</span>';
                                    $html .= '</div>';
                                }
                            }
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function displayPaiementsFacturesPdfButtons($with_generate = true, $avoirs = true, $acomptes = true, $trop_percus = false)
    {
        $html = '';

        $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $this->id, null, 'array');
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                if ($facture->isLoaded()) {
                    switch ((int) $facture->getData('type')) {
                        case Facture::TYPE_CREDIT_NOTE:
                            if ($avoirs) {
                                $html .= $facture->displayPDFButton($with_generate, false, 'Avoir ' . $facture->getRef());
                            }
                            break;

                        case Facture::TYPE_DEPOSIT:
                            if ($acomptes) {
                                $html .= $facture->displayPDFButton($with_generate, false, 'Facture acompte ' . $facture->getRef());
                            }
                            break;

                        case Facture::TYPE_STANDARD:
                            if ($trop_percus) {
                                $html .= $facture->displayPDFButton($with_generate, false, 'Facture trop perçue ' . $facture->getRef());
                            }
                            break;
                    }
                }
            }
        }

        return $html;
    }

    public function displaySecteurWithCommercialCheck()
    {
        if ($this->isLoaded()) {
            $class = '';

            $user = $this->getCommercial();
            $secteur = $this->getData('ef_type');

            if (BimpObject::objectLoaded($user)) {
                $user_secteur = $user->getData('secteur');

                if ($user_secteur) {
                    if (($user_secteur != $secteur)) {
                        $class = 'danger';
                    } else {
                        $class = 'success';
                    }
                }
            }

            $html = '';

            if ($class) {
                $html .= '<span class="' . $class . '">';
            }

            $html .= $this->displayData('ef_type');

            if ($class) {
                $html .= '</span>';
            }

            return $html;
        }

        return '';
    }

    public function displayRemainToPay()
    {
        if ($this->isLoaded()) {
            return BimpTools::displayMoneyValue($this->getRemainToPay());
        }

        return '';
    }

    public function displayPDFButton($display_generate = true, $with_ref = true, $btn_label = '')
    {
        global $user;
        if ($this->getData('fk_statut') > 0 && !in_array($user->login, array('admin', 't.sauron', 'f.martinez'))) {
            $ref = dol_sanitizeFileName($this->getRef());
            if ($this->getFileUrl($ref . '.pdf') != '')
                $display_generate = false;
        }

        $html = parent::displayPDFButton($display_generate, $with_ref, $btn_label);

        if ($this->isActionAllowed('generatePDFDuplicata') && $this->canSetAction('generatePDFDuplicata')) {
            $url = DOL_URL_ROOT . '/bimpcommercial/duplicata.php?r=' . urlencode($this->getRef()) . '&i=' . $this->id;
//            $html .= '<span class="btn btn-default" onclick="' . $this->getJsActionOnclick('generatePDFDuplicata') . '">';
            $html .= '<span class="btn btn-default" onclick="window.open(\'' . $url . '\')">';
            $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Duplicata';
            $html .= '</span>';
        }

        return $html;
    }

    public function displayDateNextRelance($with_btn = true)
    {
        $html = '';

        if ($this->isIrrecouvrable()) {
            $html .= '<span class="warning">';
            $html .= 'Aucune prochaine relance (Facture irrécouvrable)';
            $html .= '</span>';
        } else {
            $nb_relances = (int) $this->getData('nb_relance');

            BimpObject::loadClass('bimpcore', 'Bimp_Client');
            if ($nb_relances >= Bimp_Client::$max_nb_relances) {
                $html .= '<span class="warning">';
                $html .= 'Aucune prochaine relance';
                $html .= '</span>';
            } else {
                $dates = $this->getRelanceDates();

                if (isset($dates['next']) && (string) $dates['next']) {
                    $dt = new Datetime($dates['next']);
                    $html .= '<span class="date">' . $dt->format('d / m / Y') . '</span>';
                } else {
                    $html .= '<span class="warning">Indéfini</span>';
                }

                if ($with_btn) {
                    $html .= '<div class="buttonsContainer align-right">';

                    if ($this->canEditField('date_next_relance')) {
                        $date_next = (string) $this->getData('date_next_relance');

                        if (isset($dates['next']) && (string) $dates['next'] > $date_next) {
                            $date_next = $dates['next'];
                        }

                        $onclick = $this->getJsLoadModalForm('date_next_relance', 'Edition de la date de prochaine relance', array(
                            'fields' => array(
                                'date_next_relance' => $date_next
                            )
                        ));

                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . 'Modifier';
                        $html .= '</button>';
                    }

                    if ($this->canSetAction('deactivateRelancesForAMonth')) {
                        $onclick = $this->getJsActionOnclick('deactivateRelancesForAMonth', array(), array(
                            'confirm_msg' => 'Veuillez confirmer'
                        ));

                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= BimpRender::renderIcon('fas_calendar-alt', 'iconLeft') . 'Désactiver les relances pendant un mois';
                        $html .= '</button>';
                    }

                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    public function displayIrrecouvrableInfos($with_note = true)
    {
        $html = '';

        if ($this->isIrrecouvrable()) {
            $date = $this->getData('date_irrecouvrable');
            $id_user = (int) $this->getData('id_user_irrecouvrable');

            if ($date || $id_user) {
                $html = 'Déclaré' . $this->e() . ' irrécouvrable';

                if ($date) {
                    $html .= ' le ' . $this->displayData('date_irrecouvrable', 'default', false);
                }

                if ($id_user) {
                    $user = $this->getChildObject('user_irrecouvrable');

                    if (BimpObject::objectLoaded($user)) {
                        $html .= ' par ' . $user->getLink();
                    }
                }

                if ($with_note) {
                    $note = (string) $this->getData('close_note');

                    if ($note) {
                        $html .= '<br/><br/>';
                        $html .= '<span class="bold">Motif: </span><br/>';
                        $html .= $note;
                    }
                }
            }
        }

        return $html;
    }

    //Rendus HTML: 

    public function renderContentExtraLeft()
    {
        // Partie "Paiements": 
        $html = '';

        if ($this->isLoaded()) {
            $html .= '<table class="bimp_fields_table">';
            $html .= '<tbody>';

            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }
            $form = new Form($this->db->db);
            $status = (int) $this->getData('fk_statut');
            $type = (int) $this->getData('type');

            global $langs;

            $total_ttc = (float) $this->getData('total_ttc');
            $total_paid = (float) $this->dol_object->getSommePaiement();
            $total_avoirs = 0;

            // *** Facturé *** 

            $html .= '<tr>';
            $html .= '<td style="text-align: right;"><strong>Facturé</strong> : </td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc, 'EUR', 0, 0, 0, 2, 1) . '</td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            // *** Déjà payé : *** 

            $html .= '<tr>';
            $html .= '<td style="text-align: right;"><strong>Paiements effectués</strong>';
//            if ((int) $this->getData('type') !== Facture::TYPE_DEPOSIT) {
//                $html .= '<br/>(Hors avoirs et acomptes)';
//            }
            $html .= ' : </td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR', 0, 0, 0, 2, 1) . '</td>';
            $html .= '<td></td>';
            $html .= '</tr>';


            // *** Avoirs utilisés: ***
            $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $this->id, null, 'array');
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    $html .= '<tr>';

                    $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                    $label = '';

                    if ($facture->isLoaded()) {
                        switch ((int) $facture->getData('type')) {
                            case Facture::TYPE_STANDARD:
                            case Facture::TYPE_REPLACEMENT:
                                $label = 'Trop perçu facture ';
                                break;
                            case Facture::TYPE_CREDIT_NOTE:
                                $label = 'Avoir ';
                                break;
                            case Facture::TYPE_DEPOSIT:
                                $label = 'Acompte ';
                                break;
                        }
                        $label .= $facture->getLink();
                    } else {
                        $label = ((string) $r['description'] ? $r['description'] : 'Remise');
                    }

                    $total_avoirs += (float) $r['amount_ttc'];

                    $html .= '<td style="text-align: right;">';
                    $html .= $label . ' : </td>';

                    $html .= '<td>' . BimpTools::displayMoneyValue((float) $r['amount_ttc'], 'EUR', 0, 0, 0, 2, 1) . '</td>';
                    $html .= '<td class="buttons">';
                    $onclick = $this->getJsActionOnclick('removeDiscount', array('id_discount' => (int) $r['rowid']));
                    $html .= BimpRender::renderRowButton('Retirer', 'fas_trash-alt', $onclick);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }

            $remainToPay = $total_ttc - $total_paid - $total_avoirs;
            $remainToPay_final = $remainToPay;

            if ($type !== Facture::TYPE_CREDIT_NOTE) {
                // Payée partiellement 'escompte': 
                if (($status == Facture::STATUS_CLOSED || $status == Facture::STATUS_ABANDONED)) {
                    $label = '';
                    switch ($this->dol_object->close_code) {
                        case 'discount_vat':
                            $label = $form->textwithpicto($langs->trans("Discount") . ':', $langs->trans("HelpEscompte"), - 1);
                            $remainToPay_final = 0;
                            break;

                        case 'paiementnotsaved':
                            $text = '';
                            if ($this->dol_object->close_note) {
                                $text .= $this->dol_object->close_note;
                            }
                            $label = $form->textwithpicto($langs->trans("Paiement effectué mais non enregistré") . ':', $text, - 1);
                            $remainToPay_final = 0;
                            break;

                        case 'badcustomer':
                            $text = $langs->trans("HelpAbandonBadCustomer");
                            if ($this->dol_object->close_note) {
                                $text .= '<br/><br/><b>' . $langs->trans("Reason") . '</b>: ' . $this->dol_object->close_note;
                            }
                            $label = $form->textwithpicto($langs->trans("Abandoned") . ':', $text, - 1);
                            $remainToPay_final = 0;
                            break;

                        case 'product_returned':
                            $label = 'Produit retourné :';
                            $remainToPay_final = 0;
                            break;

                        case 'abandon':
                            $text = $langs->trans("HelpAbandonOther");
                            if ($this->dol_object->close_note) {
                                $text .= '<br/><br/><b>' . $langs->trans("Reason") . '</b>: ' . $this->dol_object->close_note;
                            }
                            $label = $form->textwithpicto($langs->trans("Abandoned") . ':', $text, - 1);
                            $remainToPay_final = 0;
                            break;

                        case 'irrecouvrable':
                            $text = 'Facture irrécouvrable';
                            if ($this->dol_object->close_note) {
                                $text .= '<br/><br/><b>' . $langs->trans("Reason") . '</b>: ' . $this->dol_object->close_note;
                            }
                            $label = $form->textwithpicto('Irrécouvrable: ', $text, - 1);
                            $remainToPay_final = 0;
                            break;
                    }
                    if ($label) {
                        $html .= '<tr>';
                        $html .= '<td style="text-align: right">' . $label . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($remainToPay, 'EUR', 0, 0, 0, 2, 1) . '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                    }
                }

                if ($type !== Facture::TYPE_DEPOSIT) {
                    // Trop perçu converti en remise: 
                    $rows = $this->db->getRows('societe_remise_except', 'fk_facture_source = ' . (int) $this->id, null, 'array', array('rowid'), 'datec', 'asc');

                    if (is_array($rows) && !empty($rows)) {
                        BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                        foreach ($rows as $r) {
                            $discount = new DiscountAbsolute($this->db->db);
                            $discount->fetch((int) $r['rowid']);
                            if (BimpObject::objectLoaded($discount)) {
                                $remainToPay_final += (float) $discount->amount_ttc;
                                $html .= '<tr>';
                                $html .= '<td style="text-align: right;">';
                                $html .= '<strong>Trop perçu converti en </strong>' . $discount->getNomUrl(1, 'discount');
                                $html .= '</td>';
                                $html .= '<td>' . BimpTools::displayMoneyValue($discount->amount_ttc, 'EUR', 0, 0, 0, 2, 1) . '</td>';
                                $html .= '<td></td>';
                                $html .= '</tr>';
                            }
                        }
                    }
                }
            } else {
                // Converti en remise: 
                $rows = $this->db->getRows('societe_remise_except', 'fk_facture_source = ' . (int) $this->id, null, 'array', array('rowid'), 'datec', 'asc');

                if (is_array($rows) && !empty($rows)) {
                    BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                    foreach ($rows as $r) {
                        $discount = new DiscountAbsolute($this->db->db);
                        $discount->fetch((int) $r['rowid']);
                        if (BimpObject::objectLoaded($discount)) {
                            $remainToPay_final += $discount->amount_ttc;
                            $html .= '<tr>';
                            $html .= '<td style="text-align: right;">';
                            $html .= '<strong>Converti en </strong>' . $discount->getNomUrl(1, 'discount');
                            $html .= '</td>';
                            $html .= '<td>';
                            $html .= BimpTools::displayMoneyValue($discount->amount_ttc, 'EUR', 0, 0, 0, 2, 1);
                            $html .= '</td>';
                            $html .= '<td></td>';
                            $html .= '</tr>';
                        }
                    }
                }
            }

            // Reste à payer: 
            $remainToPay_final = round($remainToPay_final, 2);

            $paye = (int) $this->getData('paye');
            if ($paye) {
                $class = 'success';
            } elseif ($type !== Facture::TYPE_CREDIT_NOTE) {
                $class = ($remainToPay_final > 0 ? 'danger' : ($remainToPay_final < 0 ? 'warning' : 'success'));
            } else {
                $class = ($remainToPay_final < 0 ? 'danger' : ($remainToPay_final > 0 ? 'warning' : 'success'));
            }

            $html .= '<tr style="background-color: #F0F0F0; font-weight: bold">';
            $html .= '<td style="text-align: right;"><strong>Reste à payer</strong> : </td>';
            $html .= '<td style="font-size: 18px;" colspan="2">';
            $html .= '<span class="' . $class . '">';
            $html .= BimpTools::displayMoneyValue(($paye ? 0.00 : $remainToPay_final), 'EUR', 0, 0, 0, 2, 1);
            $html .= '</span>';
            $html .= '</td>';
            $html .= '</tr>';

            if ($paye && $remainToPay_final) {
                $html .= '<tr>';
                $html .= '<td colspan="3">';
                $html .= '<span style="font-weight: normal; font-size: 11px; font-style: italic; line-height: 12px">';
                $html .= BimpTools::ucfirst($this->getLabel('this')) . ' a été classé' . $this->e();
                $html .= ' payé' . $this->e() . ' mais possède un reste à payer réel de ';
                $html .= '<strong>' . BimpTools::displayMoneyValue($remainToPay_final, 'EUR', 0, 0, 0, 2, 1) . '</strong>';
                $html .= '</span>';
                switch ($this->dol_object->close_code) {
                    case 'inf_one_euro':
                        $html .= '<br/><span style="font-weight: bold; font-size: 11px; font-style: italic; line-height: 12px">(' . BimpTools::ucfirst($this->getLabel()) . ' classé' . $this->e() . ' payé' . $this->e() . ' car reste à payer inférieur à 1€)</span>';
                        break;
                }

                if ($this->dol_object->close_note) {
                    $html .= '<br/><br/>';
                    $html .= '<span class="bold">Note: </span>' . $this->dol_object->close_note;
                }
                $html .= '</td>';
                $html .= '</tr>';
            }

            // Acompte converti en remise: 
            if ($type === Facture::TYPE_DEPOSIT) {
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);
                if (BimpObject::objectLoaded($discount)) {
                    $html .= '<tr>';
                    $html .= '<td colspan="3" style="text-align: center; padding-top: 15px;">';
                    $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . '</span>';
                    $html .= '<strong>Cet acompte a été converti en </strong>' . $discount->getNomUrl(1, 'discount');
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';

            // Boutons 

            $html .= '<div class="buttonsContainer align-center">';
            if ($this->isActionAllowed('useRemise') && $this->canSetAction('useRemise')) {
//                $discount_amount = (float) $this->getSocAvailableDiscountsAmounts();
//                if ($discount_amount) {
                $html .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick('useRemise', array(), array(
                            'form_name' => 'use_remise'
                        )) . '">';
                $html .= BimpRender::renderIcon('fas_file-import', 'iconLeft') . 'Appliquer un avoir ou un trop perçu disponible';
                $html .= '</button>';
//                }
            }

            if ($this->isActionAllowed('convertToReduc') && $this->canSetAction('convertToReduc')) {
                $label = '';
                $confirm_msg = '';

                switch ($type) {
                    case Facture::TYPE_STANDARD:
                        $label = 'Convertir trop perçu en remise';
                        $confirm_msg = strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('ExcessReceived'))));
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
                        $label = 'Convertir le reste à rembourser en remise';
                        $confirm_msg = strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('CreditNote'))));
                        break;

                    case Facture::TYPE_DEPOSIT:
                        $label = 'Convertir en remise';
                        $confirm_msg = strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('Deposit'))));
                        break;
                }

                if ($label) {
                    $html .= '<button class="btn btn-default" onclick="' . $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => $confirm_msg
                            )) . '">';
                    $html .= BimpRender::renderIcon('fas_percent', 'iconLeft') . $label;
                    $html .= '</button>';
                }
            }

            if ($this->isActionAllowed('payBack') && $this->canSetAction('payBack')) {
                if ($type === Facture::TYPE_CREDIT_NOTE) {
                    $label = 'Paiement trop remboursé';
                } else {
                    $label = 'Rembourser trop perçu';
                }

                $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');

                $onclick = $paiement->getJsLoadModalForm('single', $label, array(
                    'fields' => array(
                        'id_facture'    => (int) $this->id,
                        'single_amount' => abs($this->getRemainToPay())
                    )
                ));

                $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_reply', 'iconLeft') . $label;
                $html .= '</button>';
            }

            $html .= '</div>';

            $html = BimpRender::renderPanel('Total Paiements', $html, '', array(
                        'icon' => 'euro',
                        'type' => 'secondary'
            ));
        }

        return $html;
    }

    public function renderContentExtraRight()
    {
        // Partie "Liste des paiements"
        $return = '';

        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');

            $rows = $this->db->getRows('paiement_facture', '`fk_facture` = ' . (int) $this->id, null, 'array');

            $html = '<table class="bimp_list_table">';

            $html .= '<thead>';
            if ($type === Facture::TYPE_CREDIT_NOTE) {
                $html .= '<th>Remboursement</th>';
                $mult = -1;
                $title = 'Remboursements effectués';
            } else {
                $html .= '<th>Paiement</th>';
                $mult = 1;
                $title = 'Paiements effectués';
            }
            $html .= '<th>Date</th>';
            $html .= '<th>Type</th>';
            $html .= '<th>Compte bancaire</th>';
            $html .= '<th>Montant</th>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Paiement', (int) $r['fk_paiement']);
                    if ($paiement->isLoaded()) {
                        $html .= '<tr>';
                        $html .= '<td>' . $paiement->dol_object->getNomUrl(1) . '</td>';
                        $html .= '<td>' . $paiement->displayData('datep') . '</td>';
                        $html .= '<td>' . $paiement->displayType() . '</td>';
                        $html .= '<td>' . $paiement->displayAccount() . '</td>';
                        $html .= '<td>' . $paiement->displayAmount($this->id, $mult) . '</td>';
                        $html .= '</tr>';
                    }
                }
            } else {
                $html .= '<tr>';
                $html .= '<td colspan="5">' . BimpRender::renderAlerts('Aucun paiement enregistré pour le moment', 'info') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $buttons = array();

            global $user;
            if (in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_REPLACEMENT, Facture::TYPE_DEPOSIT, Facture::TYPE_CREDIT_NOTE))) {
                if (!(int) $this->getData('paye')) {
                    if ($user->rights->facture->paiement && in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                        $is_avoir = ($type === Facture::TYPE_CREDIT_NOTE ? 1 : 0);

                        $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');
                        $id_mode_paiement = ((int) $this->getData('fk_mode_reglement') ? (int) $this->getData('fk_mode_reglement') : (int) BimpCore::getConf('default_id_mode_paiement'));
                        if ($id_mode_paiement) {
                            $id_mode_paiement = dol_getIdFromCode($this->db->db, $id_mode_paiement, 'c_paiement', 'id', 'code');
                        } else {
                            $id_mode_paiement = '';
                        }

                        $onclick = $paiement->getJsLoadModalForm('default', 'Paiement Factures', array(
                            'fields' => array(
                                'is_rbt'           => $is_avoir,
                                'id_client'        => (int) $this->getData('fk_soc'),
                                'id_mode_paiement' => $id_mode_paiement,
                            )
                        ));
                        $buttons[] = array(
                            'label'       => 'Saisir ' . ($is_avoir ? 'remboursement' : 'réglement'),
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $onclick
                            )
                        );
                    }
                } else {
                    if ($this->canAddExtraPaiement()) {
                        $is_avoir = ($type === Facture::TYPE_CREDIT_NOTE ? 1 : 0);

                        $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');
                        $id_mode_paiement = ((int) $this->getData('fk_mode_reglement') ? (int) $this->getData('fk_mode_reglement') : (int) BimpCore::getConf('default_id_mode_paiement'));
                        if ($id_mode_paiement) {
                            $id_mode_paiement = dol_getIdFromCode($this->db->db, $id_mode_paiement, 'c_paiement', 'id', 'code');
                        } else {
                            $id_mode_paiement = '';
                        }

                        $onclick = $paiement->getJsLoadModalForm('single', 'Paiement Facture', array(
                            'fields' => array(
                                'id_mode_paiement'     => $id_mode_paiement,
                                'id_facture'           => (int) $this->id,
                                'force_extra_paiement' => 1
                            )
                        ));
                        $buttons[] = array(
                            'label'       => 'Saisir ' . ($is_avoir ? 'remboursement' : 'réglement'),
                            'icon_before' => 'plus-circle',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => $onclick
                            )
                        );
                    }
                }
            }

            $return .= BimpRender::renderPanel($title, $html, '', array(
                        'icon'           => 'fas_money-bill',
                        'type'           => 'secondary',
                        'header_buttons' => $buttons
            ));
        }

        return $return;
    }

    public function renderHeaderStatusExtra()
    {
        $html = parent::renderHeaderStatusExtra();

        if ((int) $this->getData('fk_statut') > 0) {
            $html .= '<span style="display: inline-block; margin-left: 12px"' . $this->displayData('paiement_status') . '</span>';
        }

        return $html;
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $type_extra = $this->displayTypeExtra();

            if ($type_extra) {
                $html .= '<div style="font-size: 18px">' . $type_extra . '</div>';
            }

            $html .= '<div class="object_header_infos">';
//            $html .= 'Créée le <strong title="' . date('d/m/Y H:m:s', $this->dol_object->date_creation) . '">' . date('d / m / Y', $this->dol_object->date_creation) . '</strong>';
            $html .= 'Créée le ' . BimpTools::printDate($this->dol_object->date_creation, 'strong');

            $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_author);
            if (BimpObject::objectLoaded($user)) {
                $html .= ' par&nbsp;&nbsp;' . $user->getLink();
            }
            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->dol_object->user_valid) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le ' . BimpTools::printDate($this->dol_object->date_validation, 'strong');

                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->dol_object->user_valid);
                if (BimpObject::objectLoaded($user)) {
                    $html .= ' par&nbsp;&nbsp;' . $user->getLink();
                }
                $html .= '</div>';
            }

            if ($this->isIrrecouvrable()) {
                $html .= '<div class="object_header_infos">';
                $html .= $this->displayIrrecouvrableInfos(false);
                $html .= '</div>';
            }
        }

        $client = $this->getChildObject('client');
        if (BimpObject::objectLoaded($client)) {
            $html .= '<div style="margin-top: 10px">';
            $html .= '<strong>Client: </strong>';
            $html .= $client->getLink();
            $html .= '</div>';
        }

        return $html;
    }

    public function renderValidationFormInfos()
    {
        $validation_type = 0;

        // 0: validation standard. 
        // 1: avoir créé automatiquement avec lignes négatives si  facture/ ou facture avec lignes positives si avoir. 
        // 2: Possibilité de déplacer les lignes négatives dans un avoir. 
        // 3: Facture ou Avoir intégralement inversé (Facture -> avoir / Avoir -> facture). 

        $html = '';

        if (!$this->isLoaded()) {
            $html .= BimpRender::renderAlerts('ID ' . $this->getLabel('of_the') . ' absent');
        } else {
            // Pour être sûr d\'être à jour:
            $this->dol_object->fetch_lines();
            $this->dol_object->update_price(1);
            $this->dol_object->fetch((int) $this->id);
            $this->hydrateFromDolObject();

            $errors = array();
            if (!$this->isActionAllowed('validate', $errors)) {
                $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($errors, 'Validation ' . $this->getLabel('of_the') . ' impossible'));
            } elseif (!$this->canSetAction('validation')) {
                $html .= BimpRender::renderAlerts('Vous n\'avez pas la permission de valider ' . $this->getLabel('this'));
            } else {
                $msg = '';
                $total_ttc_wo_discounts = (float) $this->getTotalTtcWithoutDiscountsAbsolutes();
                $total_ttc = (float) $this->getTotalTtc();
                $lines = $this->getLines('not_text');

                if (!count($lines)) {
                    return BimpRender::renderAlerts('Aucune ligne ajoutée à cette facture');
                }

                switch ($this->getData('type')) {
                    case Facture::TYPE_STANDARD:
                        $has_amounts_lines = false;
                        $neg_lines = 0;
                        foreach ($lines as $line) {
                            if (round((float) $line->getTotalTTC(), 2)) {// || round((float) $line->pa_ht, 2)) {
                                $has_amounts_lines = true;
                            }
                            if (round((float) $line->getTotalTTC(), 2) < 0 && !(int) $line->id_remise_except) {
                                $neg_lines++;
                            }
                        }
                        if (!$total_ttc && !$has_amounts_lines) {
                            return BimpRender::renderAlerts('Aucune ligne avec montant non nul ajoutée à cette facture');
                        }

                        if (!round($total_ttc_wo_discounts, 2) && $neg_lines > 0) {
                            $msg = 'Le montant total de la facture (hors acomptes et avoirs) est de 0.<br/>';
                            $msg .= 'Les lignes négatives vont être automatiquement déplacées dans un avoir';
                            $validation_type = 1;
                        } elseif (round($total_ttc_wo_discounts, 2) < 0) {
                            $msg = 'Le montant total de la facture (hors acomptes et avoirs) est négatif.<br/>';
                            $msg .= 'La facture va être intégralement convertie en avoir';
                            $validation_type = 3;
                        } else {
                            if ($neg_lines > 0) {
                                $msg = 'Une ou plusieurs lignes négatives sont présentes dans la facture.<br/>';
                                $validation_type = 2;
                            }
                        }
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
//                        if (!$total_ttc && count($lines)) {
                        // todo..
//                            $msg = 'Le montant total de l\'avoir est de 0.<br/>';
//                            $msg .= 'Les lignes positives vont être automatiquement déplacées dans une facture';
//                            $validation_type = 1;
//                        } else
                        if ($total_ttc_wo_discounts > 0) {
                            $msg = 'Le montant total de l\'avoir est positif.<br/>';
                            $msg .= 'L\'avoir va être intégralement converti en facture standard';
                            $validation_type = 3;
                        }
                        break;
                }

                if (!$validation_type) {
                    $msg .= 'Veuillez confirmer la validation ' . $this->getLabel('of_this');
                }

                $html .= BimpRender::renderAlerts($msg, 'warning');
            }
        }


        $today = date('Y-m-d');
        if (!$this->canFactureAutreDate() && $this->getData('datef') != $today) {
            $html .= BimpRender::renderAlerts('Attention, la date va être modifiée à aujourd\'hui', 'warning');
        }

        $html .= '<input type="hidden" name="validation_type" value="' . $validation_type . '">';

        return $html;
    }

    public function renderForceValidationInput()
    {
        $html = '';
        $errors = array();

        if (!$this->checkEquipmentsAttribution($errors)) {
            $html .= BimpRender::renderAlerts($errors, 'warning');
            $html .= '<p>Valider quand même ?</p>';
            $html .= BimpInput::renderInput('toggle', 'force_validate', 0);
        }

        return $html;
    }

    public function renderCreateWarning()
    {
        if (!$this->isLoaded()) {
            $html = '';
            if (BimpCore::getConf('force_use_commande')) {
                $html = '<p style="font-size: 16px">';
                $html .= '<span style="font-size: 24px">';
                $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                $html .= '</span>';
                $html .= '<span class="bold">ATTENTION</span>, la création directe de facture est réservée à des cas exceptionnels et ne doit être utilisée qu\'en dernier recours.<br/>';
                $html .= 'Pour les cas ordinaires, vous devez ';
                $html .= '<span class="bold">impérativement passer par le processus de commande.</span>';
                $html .= '</p>';
            }

            if ($html != '') {
                return array(
                    array(
                        'content' => $html,
                        'type'    => 'warning'
                    )
                );
            }
        }

        return array();
    }

    public function renderRevalorisationsList()
    {
        $html = '';

        if ($this->isLoaded()) {
            $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');
            $bc_list = new BC_ListTable($reval, 'facture');
            $bc_list->addFieldFilterValue('id_facture', (int) $this->id);

            $html .= $bc_list->renderHtml();
        }

        return $html;
    }

    public function renderMarginTableExtra($marginInfo)
    {
        $html = '';

        $total_pv = (float) $marginInfo['pv_total'];
        $total_pa = (float) $marginInfo['pa_total'];

        if (!(int) $this->getData('fk_statut')) {
            $remises_crt = 0;

            $lines = $this->getLines('not_text');

            foreach ($lines as $line) {
                $remises_crt += (float) $line->getRemiseCRT() * (float) $line->qty;
            }

            if ($remises_crt) {
                $html .= '<tr>';
                $html .= '<td>Remises CRT prévues</td>';
                $html .= '<td></td>';
                $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($remises_crt, '', 0, 0, 0, 2, 1) . '</span></td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                $total_pa -= $remises_crt;
            }
        }

        $revals = $this->getTotalRevalorisations();

        if ((float) $revals['accepted']) {
            $html .= '<tr>';
            $html .= '<td>Revalorisations acceptées</td>';
            $html .= '<td></td>';
            $html .= '<td><span class="danger">' . ((float) $revals['accepted'] < 0 ? '+' : '-') . BimpTools::displayMoneyValue(abs((float) $revals['accepted']), '', 0, 0, 0, 2, 1) . '</span></td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $total_pa -= (float) $revals['accepted'];
        }

        if ((float) $revals['attente']) {
            $html .= '<tr>';
            $html .= '<td>Revalorisations en attente</td>';
            $html .= '<td></td>';
            $html .= '<td><span class="danger">' . ((float) $revals['attente'] < 0 ? '+' : '-') . BimpTools::displayMoneyValue(abs((float) $revals['attente']), '', 0, 0, 0, 2, 1) . '</span></td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $total_pa -= (float) $revals['attente'];
        }

        if ((float) $total_pa !== (float) $marginInfo['pa_total']) {
            $total_marge = $total_pv - $total_pa;
            $tx = 0;


            if (BimpCore::getConf('bimpcomm_tx_marque')) {
                if ($total_pv) {
                    $tx = ($total_marge / $total_pv) * 100;
                }
            } else {
                if ($total_pa) {
                    $tx = ($total_marge / $total_pa) * 100;
                }
            }

            $html .= '<tr>';
            $html .= '<td>Marge finale prévue</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_pv, '', 0, 0, 0, 2, 1) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_pa, '', 0, 0, 0, 2, 1) . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_marge, '', 0, 0, 0, 2, 1) . ' (' . BimpTools::displayFloatValue($tx, 4) . ' %)</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    public function renderCheckPaForm()
    {
        $html = '';

        $errors = array();
        $has_checked = false;

        if ($this->isLoaded($errors)) {
//            $type_check = BimpTools::getPostFieldValue('type_check', '');
//            if (!$type_check) {
//                $errors[] = 'Type de prix d\'achat des produits à récupérer absent';
//            } else {
            $type_check = 'create';
            $lines = $this->getLines('product');

            if (empty($lines)) {
                return BimpRender::renderAlerts('Aucune ligne produit trouvée', 'warning');
            } else {
                $date = '';
                switch ($type_check) {
                    case 'create':
                        $date = $this->getData('datec');
                        break;

                    case 'validate':
                        if ((int) $this->getData('status') < 1) {
                            $date = $this->getData('datec');
                        } else {
                            $date = $this->getData('date_valid') . ' 00:00:00';
                        }
                        break;
                }
                $html .= '<table class="bimp_list_table">';
                $html .= '<thead>';
                $html .= '<tr>';
                $html .= '<th style="text-align: center; width: 30px;"></th>';
                $html .= '<th style="text-align: center; width: 45px;">Ligne n°</th>';
                $html .= '<th>Produit</th>';
                $html .= '<th>PA enregistré</th>';
                $html .= '<th>PA attendu</th>';
                $html .= '<th>Orgine du PA attendu</th>';
                $html .= '</tr>';
                $html .= '</thead>';

                $html .= '<tbody>';

                foreach ($lines as $line) {
                    $line_pa_ht = (float) $line->getPaWithRevalorisations();
                    $infos = $line->findValidPrixAchat($date);
                    $is_ok = (round($line_pa_ht, 2) === round((float) $infos['pa_ht'], 2));
                    $check = (!$is_ok && (int) $line->getData('pa_editable'));

                    $html .= '<tr class="lineRow" data-id_line="' . $line->id . '" data-new_pa="' . $infos['pa_ht'] . '">';
                    $html .= '<td style="text-align: center; width: 30px;">';
                    if (!$is_ok) {
                        $html .= '<input type="checkbox" value="' . $line->id . '" name="lines[]"';
                        if ($check) {
                            $has_checked = true;
                            $html .= ' checked';
                        }
                        $html .= '/>';
                    }
                    $html .= '</td>';
                    $html .= '<td style="text-align: center; width: 45px;">' . $line->getData('position') . '</td>';
                    $html .= '<td>';
                    $html .= $line->displayLineData('desc_light');
                    if (!(int) $line->getData('pa_editable')) {
                        $html .= '<br/><span class="warning">Attention: la ligne est marquée "prix d\'achat non éditable"</span>';
                    }
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue((float) $line->pa_ht);
                    if ((float) $line->pa_ht !== $line_pa_ht) {
                        $html .= '<br/>';
                        $html .= 'Corrections incluses: ' . BimpTools::displayMoneyValue($line_pa_ht);
                    }
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= '<span class="' . ($is_ok ? 'success' : ($check ? 'danger' : 'warning')) . '">' . BimpTools::displayMoneyValue((float) $infos['pa_ht']) . '</span>';
                    $html .= '</td>';
                    $html .= '<td>' . $infos['origin'] . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>';
                $html .= '</table>';
            }
//            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        } elseif ($has_checked) {
            $msg = 'Veuillez sélectionner les lignes à corriger et cliquer sur "Valider" pour lancer la correction automatique';
            $html = BimpRender::renderAlerts($msg, 'info') . $html;
        } else {
            $msg = 'Il n\'y a aucun prix d\'achat à corriger';
            $html = BimpRender::renderAlerts($msg, 'success') . $html;
        }

        return $html;
    }

    // Traitements: 

    public function beforeValidate()
    {
        $errors = array();

        $type = (int) $this->getData('type');

        if (in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE))) {

            // Pour être sûr d\'être à jour:
            $this->checkLines();
            $this->dol_object->fetch_lines();
            $this->dol_object->update_price();
            $this->dol_object->fetch((int) $this->id);
            $this->hydrateFromDolObject();

            if (!$this->isValidatable($errors)) {
                return $errors;
            }

            $lines = $this->getLines('not_text');
            $total_ttc_wo_discounts = (float) $this->getTotalTtcWithoutDiscountsAbsolutes();
            $total_ttc = (float) $this->getTotalTtc();

            $has_amounts_lines = false;
            $neg_lines = 0;

            if (!count($lines)) {
                $errors[] = 'Aucune ligne ajoutée à cette facture';
                return $errors;
            }

            foreach ($lines as $line) {
                if (round((float) $line->getTotalTTC(), 2)) {// || round((float) $line->pa_ht, 2)) {
                    $has_amounts_lines = true;
                }
                if (round((float) $line->getTotalTTC(), 2) < 0 && !(int) $line->id_remise_except) {
                    $neg_lines++;
                }
            }

            if (!round($total_ttc, 2) && !$has_amounts_lines) {
                $errors[] = 'Aucune ligne avec montant non nul ajoutée à cette facture';
            }

            if (count($errors)) {
                return $errors;
            }

            // Si Total < 0 avec au moins une ligne négative hors remise absolue => conversion auto en avoir. 

            switch ($type) {
                case Facture::TYPE_STANDARD:
                    if (!round($total_ttc_wo_discounts, 2) && $neg_lines > 0) {
                        $convert_avoir = (int) BimpTools::getPostFieldValue('convert_avoir_to_reduc', 1);
                        $use_remise = (int) BimpTools::getPostFieldValue('use_discount_in_facture', 1);
                        $err = $this->createCreditNoteWithNegativesLines($lines, $convert_avoir, $use_remise);

                        if (count($err)) {
                            $errors[] = BimpTools::getMsgFromArray($err, 'Erreurs lors de la création d\'un avoir avec les lignes négatives');
                        }
                    } elseif (round($total_ttc_wo_discounts, 2) < 0) {
                        $err = $this->updateField('type', Facture::TYPE_CREDIT_NOTE);
                        if (count($err)) {
                            $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la conversion de la facture en avoir');
                        } else {
                            $this->dol_object->type = Facture::TYPE_CREDIT_NOTE;
                        }
                    } else {
                        if ($neg_lines > 0 && BimpTools::getPostFieldValue('create_avoir', 0)) {
                            $convert_avoir = (int) BimpTools::getPostFieldValue('convert_avoir_to_reduc', 1);
                            $use_remise = (int) BimpTools::getPostFieldValue('use_discount_in_facture', 1);
                            $err = $this->createCreditNoteWithNegativesLines($lines, $convert_avoir, $use_remise);

                            if (count($err)) {
                                $errors[] = BimpTools::getMsgFromArray($err, 'Erreurs lors de la création d\'un avoir avec les lignes négatives');
                            }
                        }
                    }
                    break;

                case Facture::TYPE_CREDIT_NOTE:
                    if (!round($total_ttc, 2) && count($lines)) {
                        // todo...
                    } elseif (round($total_ttc_wo_discounts) > 0) {
                        $err = $this->updateField('type', Facture::TYPE_STANDARD);

                        if (count($err)) {
                            $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la conversion de l\'avoir en facture');
                        } else {
                            $this->dol_object->type = Facture::TYPE_STANDARD;
                        }
                    }
                    break;
            }
        }


        return $errors;
    }

    public function onValidate(&$warnings = array())
    {
        global $user;

        if ($this->isLoaded($warnings)) {
            $this->majStatusOtherPiece();

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                $line->onFactureValidate();
            }

            // transformation des lignes de remise excepts en paiements: 
            if (in_array((int) $this->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE))) {
                $lines = $this->getLines();

                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');

                $done = 0;

                foreach ($lines as $line) {
                    if ((int) $line->id_remise_except) {
                        $discount = new DiscountAbsolute($this->db->db);
                        $discount->fetch((int) $line->id_remise_except);
                        if (BimpObject::objectLoaded($discount)) {
                            if ((int) $discount->fk_facture && (int) $discount->fk_facture !== (int) $this->id) {
                                // La remise except était présente en tant que ligne mais a été consommée par une autre facture
                                $this->db->delete('facturedet', '`rowid` = ' . (int) $line->getData('id_line'));
                                $this->db->delete('bimp_facture_line', '`id` = ' . (int) $line->id);
                                $done++;
                                continue;
                            }

                            if ((int) $discount->fk_facture_line === (int) $line->getData('id_line')) {
                                if ($this->db->update('societe_remise_except', array(
                                            'fk_facture_line' => null,
                                            'fk_facture'      => (int) $this->id
                                                ), '`rowid` = ' . (int) $discount->id) > 0) {
                                    $this->db->delete('facturedet', '`rowid` = ' . (int) $line->getData('id_line'));
                                    $this->db->delete('bimp_facture_line', '`id` = ' . (int) $line->id);
                                    $done++;
                                } else {
                                    BimpCore::addlog('Facture: échec conversion avoir en paiement', Bimp_Log::BIMP_LOG_URGENT, 'bimpcommercial', $this, array(
                                        'Ligne n°'   => $line->getData('position'),
                                        'Erreur SQL' => $this->db->err()
                                    ));
                                }
                            }
                        }
                    } elseif ($line->isArticleLine()) {
                        // Création des revalorisations sur remise CRT: 
                        if ((int) $line->getData('remise_crt')) {
                            $remise_pa = (float) $line->getRemiseCRT();

                            if (!$remise_pa) {
                                continue;
                            }

                            // On vérifie qu'une reval n'existe pas déjà: 
                            $reval = BimpCache::findBimpObjectInstance('bimpfinanc', 'BimpRevalorisation', array(
                                        'id_facture'      => (int) $this->id,
                                        'id_facture_line' => (int) $line->id,
                                        'type'            => 'crt'
                            ));

                            if (BimpObject::objectLoaded($reval)) {
                                continue;
                            }

                            $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');

                            $dt = new DateTime($this->getData('datec'));

                            $reval_errors = $reval->validateArray(array(
                                'id_facture'      => (int) $this->id,
                                'id_facture_line' => (int) $line->id,
                                'type'            => 'crt',
                                'date'            => $dt->format('Y-m-d'),
                                'amount'          => $remise_pa,
                                'qty'             => (float) $line->qty
                            ));

                            if (!count($reval_errors)) {
                                $reval_warnings = array();
                                $reval->create($reval_warnings, true);
                            }
                        }
                    }
                }

                if ($done > 0) {
                    $this->dol_object->fetch_lines();
                    $this->dol_object->update_price(1);
                    $this->fetch((int) $this->id);
                }
            }

            // Classemement "facturé" des commandes et propales: 
            $facturee = true;
            $this->dol_object->fetchObjectLinked();

            if (isset($this->dol_object->linkedObjects['commande'])) {
                foreach ($this->dol_object->linkedObjects['commande'] as $comm) {
                    $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $comm->id);
                    if (BimpObject::objectLoaded($commande)) {
                        $commande->checkInvoiceStatus();
                    }
                }
            }
            if (isset($this->dol_object->linkedObjects['propal']) && $facturee) {
                foreach ($this->dol_object->linkedObjects['propal'] as $prop) {
                    $prop->classifybilled($user);
                }
            }

            $this->checkIsPaid();
            $this->checkRemisesGlobales();
        }

        return array();
    }

    public function onUnValidate(&$warnings = array())
    {
        // Attention: Alimenter $errors annulera la dévalidation. 

        $errors = array();

        if ($this->isLoaded($warnings)) {
            $this->set('fk_statut', Facture::STATUS_DRAFT);
            $this->majStatusOtherPiece();
        }

        return $errors;
    }

    public function onDelete(&$warnings = array())
    {
        $errors = array();

        if ($this->isLoaded($warnings)) {
            $lines = $this->getChildrenObjects('lines', array(
                'linked_object_name' => 'commande_line'
            ));

            foreach ($lines as $line) {
                $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));

                if (BimpObject::objectLoaded($commande_line)) {
                    $commande_line->onFactureDelete($this->id);
                }
            }

            $shipments = BimpCache::getBimpObjectObjects('bimplogistique', 'BL_CommandeShipment', array(
                        'id_facture' => (int) $this->id
            ));

            foreach ($shipments as $shipment) {
                $shipment->updateField('id_facture', 0);
            }

            $tabT = getElementElement('contrat', 'facture', null, $this->id);
            foreach ($tabT as $data) {
                $echeancier = BimpCache::getBimpObjectInstance('bimpcontract', 'BContract_echeancier');
                if ($echeancier->find(['id_contrat' => $data['s']])) {
                    $dateDebutFacture = $this->dol_object->lines[0]->date_start;
                    $echeancier->onDeleteFacture($dateDebutFacture);
                }
            }
        }

        $this->majStatusOtherPiece();

        return $errors;
    }

    public function onChildSave($child)
    {
        $errors = parent::onChildSave($child);

        if (!is_array($errors)) {
            $errors = array();
        }

        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                $errors = BimpTools::merge_array($errors, $this->checkMargin());
                $errors = BimpTools::merge_array($errors, $this->checkTotalAchat());
            } elseif (is_a($child, 'BimpRevalorisation')) {
                $errors = BimpTools::merge_array($errors, $this->checkMargin(true));
                $errors = BimpTools::merge_array($errors, $this->checkTotalAchat(true));
            }
        }

        return $errors;
    }

    public function onChildDelete($child)
    {
        $errors = parent::onChildDelete($child);

        if (!is_array($errors)) {
            $errors = array();
        }

        if ($this->isLoaded()) {
            if (is_a($child, 'objectLine')) {
                $errors = BimpTools::merge_array($errors, $this->checkMargin());
                $errors = BimpTools::merge_array($errors, $this->checkTotalAchat());
            } elseif (is_a($child, 'BimpRevalorisation')) {
                $errors = BimpTools::merge_array($errors, $this->checkMargin(true));
                $errors = BimpTools::merge_array($errors, $this->checkTotalAchat(true));
            }
        }

        return $errors;
    }

    public function majStatusOtherPiece()
    {
        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $asso = new BimpAssociation($commande, 'factures');

        $list = $asso->getObjectsList((int) $this->id);

        foreach ($list as $id_commande) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);

            if (BimpObject::objectLoaded($commande)) {
                $commande->checkInvoiceStatus();
            }
        }
    }

    public function createCreditNoteWithNegativesLines($lines = null, $convertToReduc = false, $useConvertedReduc = false)
    {
        if (!$this->isLoaded()) {
            return array('ID ' . $this->getLabel('of_the') . ' absent');
        }

        if ((int) $this->getData('type') !== Facture::TYPE_STANDARD) {
            return array(BimpTools::ucfirst($this->getLabel('the')) . ' doit être de type "facture standard"');
        }

        if (is_null($lines)) {
            $lines = $this->getLines('not_text');
        }

        if (!is_array($lines) || empty($lines)) {
            return array();
        }

        $errors = array();

        $neg_lines = array();

        foreach ($lines as $line) {
            if ((float) $line->getTotalTTC() < 0 && !(int) $line->id_remise_except) {
                $neg_lines[] = $line;
            }
        }

        if (!count($neg_lines)) {
            return array();
        }

        // Création de l'avoir: 

        $avoir = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

        $avoir->validateArray(array(
            'entrepot'          => (int) $this->getData('entrepot'),
            'libelle'           => $this->getData('libelle'),
            'ef_type'           => $this->getData('ef_type'),
            'fk_soc'            => $this->getData('fk_soc'),
            'centre'            => $this->getData('centre'),
            'ref_client'        => $this->getData('ref_client'),
            'datef'             => $this->getData('datef'),
            'fk_account'        => (int) $this->getData('fk_account'),
            'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
            'fk_mode_reglement' => 1
        ));

        $avoir->dol_object->fk_delivery_address = $this->dol_object->fk_delivery_address;

        // objets liés
        $avoir->dol_object->linked_objects = $this->dol_object->linked_objects;

        $avoir_warnings = array();
        $errors = $avoir->create($avoir_warnings, true);

        if ($avoir->dol_object->copy_linked_contact($this->dol_object, 'internal') < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la copie des contacts internes');
        }
        if ($avoir->dol_object->copy_linked_contact($this->dol_object, 'external') < 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la copie des contacts externes');
        }

        if (count($errors)) {
            return $errors;
        }

        // Ajout lignes
        $this->db->db->begin();

        foreach ($neg_lines as $line) {
            $up_errors = $line->updateField('id_obj', (int) $avoir->id);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour de la ligne n°' . $line->getData('position'));
                break;
            } else {
                if ($this->db->update('facturedet', array(
                            'fk_facture' => (int) $avoir->id
                                ), '`rowid` = ' . (int) $line->getData('id_line')) <= 0) {
                    $errors[] = 'Echec de la mise à jour de la ligne n°' . $line->getData('position') . ' - ' . $this->db->db->lasterror();
                    break;
                }
            }
        }

        if (!count($errors)) {
            // Changement d'ID facture pour les lignes de commandes concernées.
            foreach ($neg_lines as $line) {
                if ($line->getData('linked_object_name') === 'commande_line') {
                    $commLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));
                    if (BimpObject::objectLoaded($commLine)) {
                        $line_errors = $commLine->changeIdFacture($this->id, $avoir->id);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la mise à jour de la ligne de commande associée (ID ' . $commLine->id . ')');
                            break;
                        }
                    }
                }
            }
        }


        if (count($errors)) {
            $this->db->db->rollback();
            $avoir->delete();
            return $errors;
        }

        $this->db->db->commit();

        $this->dol_object->fetch_lines();
        $avoir->dol_object->fetch_lines();

        // Recalcul des prix totaux.
        $this->dol_object->update_price(1);
        $avoir->dol_object->update_price(1);

        // Pour être sûr d'être à jour dans les données: 
        $this->fetch($this->id);
        $avoir->fetch($avoir->id);

        global $idAvoirFact;
        $idAvoirFact = $avoir->id;

        // Assos commande

        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $asso = new BimpAssociation($commande, 'factures');

        $list = $asso->getObjectsList((int) $this->id);

        foreach ($list as $id_commande) {
            $asso->addObjectAssociation((int) $avoir->id, (int) $id_commande);
        }

        // Ajout id_avoir pour les éventuelles expéditions liées: 
        $shipments = BimpCache::getBimpObjectObjects('bimplogistique', 'BL_CommandeShipment', array(
                    'id_facture' => (int) $this->id
        ));

        foreach ($shipments as $shipment) {
            $shipment->updateField('id_avoir', (int) $avoir->id);
        }

        // validation de l'avoir (sans trigger pour éviter les boucles infinies): 
        global $user;
        if ($avoir->dol_object->validate($user, 0, 0, 1) <= 0) {
            $msg = 'Avoir créé avec succès mais échec de la validation';
            if ($convertToReduc) {
                $msg .= '. La conversion en remise n\'a pas été effectuée';
            }
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), $msg);
            return $errors;
        } else {
            global $langs;
            $model = $avoir->getModelPdf();
            $avoir->dol_object->generateDocument($model, $langs);
        }

        // Conversion en remise: 
        if ($convertToReduc) {
            $conv_errors = $avoir->convertToRemise();

            if (count($conv_errors)) {
                $errors[] = BimpTools::getMsgFromArray($conv_errors, 'Avoir créé et validé avec succès mais échec de la conversion en remise');
                return $errors;
            }

            if ($useConvertedReduc) {
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $avoir->id);

                if (BimpObject::objectLoaded($discount)) {
                    if ($discount->link_to_invoice(0, $this->id) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Avoir créé et converti en remise avec succès mais échec de l\'application de la remise');
                    }
                }
            }
        }

        return $errors;
    }

    public function convertToRemise()
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        global $langs, $user;

        $langs->load('companies');
        $langs->load('compta');
        $langs->load('products');
        $langs->load('banks');
        $langs->load('main');

        $errors = array();
        $db = $this->db->db;

        $this->dol_object->fetch_thirdparty();
        $remain_to_pay = $this->getRemainToPay();
        $type = (int) $this->getData('type');
        $paye = (int) $this->getData('paye');

        if (!in_array($type, array(Facture::TYPE_DEPOSIT, Facture::TYPE_CREDIT_NOTE, Facture::TYPE_STANDARD))) {
            $errors[] = 'Les ' . $this->getLabel('name_plur') . ' ne peuvent pas être converti' . $this->e() . 's en remise';
        } else {
            // On vérifie si une remise n'a pas déjà été créée: 
//            $discountcheck = new DiscountAbsolute($db);
//            $result = $discountcheck->fetch(0, $this->id);
//            if (!empty($discountcheck->id)) {
//                $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a déjà été converti' . $this->e() . ' en remise';
//            } else {
            if ($type == Facture::TYPE_DEPOSIT && !(int) $this->getData('paye')) {
                $errors[] = 'Cet acompte ne peut pas être converti en remise car il n\'a pas été entièrement payé';
            }

            if (in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_STANDARD))) {
                if ($paye || !$remain_to_pay) {
                    $msg = BimpTools::ucfirst($this->getLabel('this')) . ' ne peut pas être converti' . $this->e() . ' en remise car ';
                    if ($this->isLabelFemale()) {
                        $msg .= ' elle a été entièrement payée';
                    } else {
                        $msg .= ' il a été entièrement payé';
                    }
                    $errors[] = $msg;
                } elseif ($remain_to_pay >= 0) {
                    $errors[] = 'Aucun montant disponible pour conversion en remise';
                }
            }
//            }
        }


        if (count($errors)) {
            return $errors;
        }

        $db->begin();

        $discount = new DiscountAbsolute($db);
        if ($type == Facture::TYPE_CREDIT_NOTE)
            $discount->description = '(CREDIT_NOTE)';
        elseif ($type == Facture::TYPE_DEPOSIT)
            $discount->description = '(DEPOSIT)';
        elseif ($type == Facture::TYPE_STANDARD || $type == Facture::TYPE_REPLACEMENT || $type == Facture::TYPE_SITUATION)
            $discount->description = '(EXCESS RECEIVED)';

        $discount->fk_soc = (int) $this->getData('fk_soc');
        $discount->fk_facture_source = $this->id;

        if ($type == Facture::TYPE_STANDARD || $type == Facture::TYPE_CREDIT_NOTE) {
            $discount->amount_ht = $discount->amount_ttc = abs($remain_to_pay);
            $discount->amount_tva = 0;
            $discount->tva_tx = 0;

            $result = $discount->create($user);
            if ($result < 0) {
                $msg = 'Echec de la création de la remise client';
                $sqlError = $db->lasterror();
                if ($sqlError) {
                    $msg .= ' - ' . $sqlError;
                }
                $errors[] = $msg;
            }
        }

        if ($type == Facture::TYPE_DEPOSIT) {
            // Insert one discount by VAT rate category
            $amount_ht = $amount_tva = $amount_ttc = array();

            $i = 0;
            foreach ($this->dol_object->lines as $line) {
                if ($line->total_ht != 0) {  // no need to create discount if amount is null
                    $amount_ht[$line->tva_tx] += $line->total_ht;
                    $amount_tva[$line->tva_tx] += $line->total_tva;
                    $amount_ttc[$line->tva_tx] += $line->total_ttc;
                    $i ++;
                }
            }

            foreach ($amount_ht as $tva_tx => $xxx) {
                $discount->amount_ht = abs($amount_ht[$tva_tx]);
                $discount->amount_tva = abs($amount_tva[$tva_tx]);
                $discount->amount_ttc = abs($amount_ttc[$tva_tx]);
                $discount->tva_tx = abs($tva_tx);

                $result = $discount->create($user);
                if ($result < 0) {
                    $msg = 'Echec de la création de la remise client';
                    $sqlError = $db->lasterror();
                    if ($sqlError) {
                        $msg .= ' - ' . $sqlError;
                    }
                    $errors[] = $msg;
                    break;
                }
            }
        }

        if (count($errors)) {
            $db->rollback();
            return $errors;
        }

        if ($this->dol_object->set_paid($user) >= 0) {
            $db->commit();
        } else {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'enregistrement du statut "payé" pour cette facture');
            $db->rollback();
        }

        return $errors;
    }

    public function checkIsPaid($paiement_status_only = false, $amount_removed = 0, $force_paye = null)
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') > 0 && (int) $this->getData('paiement_status') !== 5) {
            $remain_to_pay = (float) $this->getRemainToPay(true, false);
            $remain_to_pay += $amount_removed;

            if (!is_null($force_paye)) {
                $paye = (int) $force_paye;
            } else {
                $paye = (int) $this->getData('paye');
            }

            $paiement_status = 0;

            if ($remain_to_pay > -0.01 && $remain_to_pay < 0.01) {
                $remain_to_pay = 0;
            }

            $remain_to_pay = round($remain_to_pay, 2);

            $max_rtp = (float) BimpCore::getConf('bimpcommercial_max_rtp_for_classify_paid', 0);
            if (!$remain_to_pay || ($max_rtp > 0 && $remain_to_pay > 0 && $remain_to_pay < $max_rtp)) {
                $paiement_status = 2; // Entièrement payé. 
                if (!$paiement_status_only && !$paye) {
                    $success = '';
                    $close_code = '';

                    if ($remain_to_pay) {
                        $close_code = 'inf_one_euro';
                    }

                    $errors = $this->setObjectAction('classifyPaid', 0, array(
                        'close_code' => $close_code,
                            ), $success, true);

                    if (isset($errors['errors'])) {
                        $errors = $errors['errors'];
                    }

                    if (count($errors)) {
                        BimpCore::addlog('Echec facture classée payée', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcomm', $this, array(
                            'Erreurs' => $errors
                        ));
                    }
                }
            } else {
                $close_code = (string) $this->getData('close_code');

                if ($paye && $close_code) {
                    $paiement_status = 2;
                } else {
                    $diff = (float) $this->dol_object->total_ttc - $remain_to_pay;

                    $diff = round($diff, 2);
                    if ($diff > -0.01 && $diff < 0.01) {
                        $paiement_status = 0; // Aucun paiement
                    } else {
                        if ($this->dol_object->total_ttc > 0 && $remain_to_pay < 0) {
                            $paiement_status = 3; // Trop perçu
                        } elseif ($this->dol_object->total_ttc < 0 && $remain_to_pay > 0) {
                            $paiement_status = 4; // Trop remboursé
                        } else {
                            $paiement_status = 1; // Paiement partiel
                        }
                    }
                }

                if (!$paiement_status_only && $paye && !$close_code) {
                    $this->setObjectAction('reopen');
                }
            }

            if ((int) $paiement_status !== (int) $this->getInitData('paiement_status') ||
                    (int) $paiement_status !== (int) $this->getData('paiement_status')) { // Par précaution...
                $this->updateField('paiement_status', $paiement_status);
            }

            $this->checkRemainToPay($amount_removed);
        }

        $client = $this->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $client->checkSolvabiliteStatus();
        }
    }

    public function checkRemainToPay($amount_removed = 0)
    {
        if ($this->isLoaded() && (int) $this->getData('paiement_status') !== 5) {
            if ((int) $this->getData('paye') && (string) $this->getData('close_code')) {
                $remain_to_pay = 0;
            } else {
                $remain_to_pay = (float) $this->getRemainToPay(false, false);
                $remain_to_pay += $amount_removed;

                if ($remain_to_pay > -0.01 && $remain_to_pay < 0.01) {
                    $remain_to_pay = 0;
                }
                $remain_to_pay = round($remain_to_pay, 2);
            }
            if ($remain_to_pay !== (float) $this->getData('remain_to_pay')) {
                $this->updateField('remain_to_pay', $remain_to_pay, null, true);
            }
        }
    }

    public function checkPrice()
    {
        if ($this->isLoaded() && static::$dol_module) {
            $sql = 'SELECT SUML(total_ttc) FROM ' . MAIN_DB_PREFIX . 'facturedet WHERE `fk_facture` = ' . (int) $this->id;
            $total_ttc = '';
        }
    }

    public function checkSingleAmoutPaiement(&$amount, $force_extra_paiement = false)
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $amount = round((float) $amount, 2);

        if (!$amount) {
            $errors[] = 'Aucun montant spécifié';
            return $errors;
        }

        $remain_to_pay = $this->getRemainToPay();

        if (!in_array((int) $this->getData('fk_statut'), array(1, 2))) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas validé' . $this->e();
            return $errors;
        }

        if (!$force_extra_paiement) {
            if ((int) $this->getData('paye') || !$remain_to_pay) {
                $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est entièrement payé' . $this->e();
                return $errors;
            }
        }

        switch ((int) $this->getData('type')) {
            case Facture::TYPE_STANDARD:
            case Facture::TYPE_REPLACEMENT:
            case Facture::TYPE_DEPOSIT:
                if (!$force_extra_paiement && $remain_to_pay < 0) {
                    if ($amount < 0) {
                        $errors[] = 'Les factures ayant un trop-perçu ne peuvent pas être remboursées d\'un montant négatif';
                    } elseif ($amount > abs($remain_to_pay)) {
                        $errors[] = 'Le remboursement d\'un trop-perçu ne peut pas être supérieur à ce trop-perçu';
                    } else {
                        $amount *= -1;
                    }
                }
                break;

            case Facture::TYPE_CREDIT_NOTE:
                if (!$force_extra_paiement && $remain_to_pay > 0) {
                    if ($amount < 0) {
                        $errors[] = 'Les avoirs ayant un trop-payé ne peuvent pas être remboursés d\'un montant négatif';
                    } elseif ($amount > $remain_to_pay) {
                        $errors[] = 'Le remboursement d\'un trop-payé ne peut pas être supérieur à ce trop-payé';
                    }
                }
                break;

            default:
                $errors[] = 'Les paiements ne sont pas possibles pour les factures de type "' . self::$types[(int) $this->getData('type')]['label'] . '"';
                break;
        }

        return $errors;
    }

    public function checkMargin($recalculate_revals = false)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $margin = (float) $this->getSavedData('marge');
            $revals = $this->getTotalRevalorisations($recalculate_revals);

            $marge_finale = $margin + (float) $revals['accepted'];

            if ($marge_finale != (float) $this->getData('marge_finale_ok')) {
                $up_errors = $this->updateField('marge_finale_ok', $marge_finale);

                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du champ "Marge + reval OK"');
                }
            }
        }

        return $errors;
    }

    public function checkTotalAchat($recalculate_revals = false)
    {
        $errors = array();

        if ($this->isLoaded()) {
            $sql = 'SELECT SUM(a.buy_price_ht * a.qty) as total_achat';
            $sql .= BimpTools::getSqlFrom('facturedet');
            $sql .= ' WHERE a.fk_facture = ' . (int) $this->id;
            $sql .= ' GROUP BY a.fk_facture';

            $res = $this->db->executeS($sql, 'array');

            $total_achat = 0;

            if (isset($res[0]['total_achat'])) {
                $total_achat = (float) $res[0]['total_achat'];
            }

            $revals = $this->getTotalRevalorisations($recalculate_revals);
            $total_achat -= (float) $revals['accepted'];

            if ($total_achat != (float) $this->getData('total_achat_reval_ok')) {
                $up_errors = $this->updateField('total_achat_reval_ok', $total_achat);

                if (count($up_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du champ "Total achat (reval OK)"');
                }
            }
        }

        return $errors;
    }

    // Actions - Overrides BimpComm:

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';
        $modal_html = '';

        if (!isset($data['force_validate']) || !(int) $data['force_validate']) {
            $lines_errors = array();
            if (!$this->checkEquipmentsAttribution($lines_errors)) {
                $errors[] = BimpTools::getMsgFromArray($lines_errors);
            }
        }

        $contacts = $this->dol_object->liste_contact(-1, 'external', 0, 'BILLING');
        foreach ($contacts as $contact) {
            if ($contact['socid'] != $this->getData("fk_soc"))
                $errors[] = 'Validation impossible, contact client adresse de facturation différente du client de la facture';
        }

        if (!count($errors)) {
            $success = BimpTools::ucfirst($this->getLabel('')) . ' validé';
            if ($this->isLabelFemale()) {
                $success .= 'e';
            }
            $success .= ' avec succès';

            $success_callback = 'bimp_reloadPage();';

            global $conf, $langs, $user, $mysoc;
            $langs->load("errors");

            $this->dol_object->fetch_thirdparty();

            $id_entrepot = (int) $this->getData('entrepot');

            if (!$id_entrepot && $this->useEntrepot()) {
                $errors[] = 'Entrepôt absent';
            }

            if ($mysoc->country_id > 0 && $this->dol_object->thirdparty->country_id == $mysoc->country_id) {
                for ($i = 1; $i <= 6; $i++) {
                    $idprof_mandatory = 'SOCIETE_IDPROF' . ($i) . '_INVOICE_MANDATORY';
                    $idprof = 'idprof' . $i;
                    if (!$this->dol_object->thirdparty->$idprof && !empty($conf->global->$idprof_mandatory)) {
                        $errors[] = $langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId' . $i, $this->dol_object->thirdparty->country_code));
                    }
                }
            }

            if (!count($errors)) {
                //date du jour si pas le droit. Avant le validate
                $today = date('Y-m-d');
                if (!$this->canFactureAutreDate() && $this->getData('datef') != $today) {
                    $warnings[] = "Attention la date a été modifiée à la date du jour.";
                    $errors = $this->updateField('datef', $today);
                    $this->dol_object->date = strtotime($this->getData('datef'));
                    $this->updateField('date_lim_reglement', BimpTools::getDateFromDolDate($this->dol_object->calculate_date_lim_reglement((int) $this->getData('fk_cond_reglement'))));
                }



                $result = $this->dol_object->validate($user, '', $id_entrepot);
                $fac_warnings = BimpTools::getDolEventsMsgs(array('warnings'));

                if ($result >= 0) {
                    // Define output language
                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        $outputlangs = $langs;
                        $newlang = '';
                        if ($conf->global->MAIN_MULTILANGS)
                            $newlang = $this->dol_object->thirdparty->default_lang;
                        if (!empty($newlang)) {
                            $outputlangs = new Translate("", $conf);
                            $outputlangs->setDefaultLang($newlang);
                        }
                        $model = $this->getModelPdf();
                        $this->fetch($this->id);

                        $result = $this->dol_object->generateDocument($model, $outputlangs);
                        if ($result <= 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues lors de la génération du document PDF');
                        }
                    }
                } else {
                    $fac_errors = BimpTools::getDolEventsMsgs(array('errors'));

                    if (!count($fac_errors)) {
                        $fac_errors[] = BimpTools::ucfirst($this->getLabel('the')) . ' ne peut pas être validé' . $this->e();
                    }
                    $errors[] = BimpTools::getMsgFromArray($fac_errors);
                }

                if (count($fac_warnings)) {
                    $warnings[] = BimpTools::getMsgFromArray($fac_warnings);
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback,
            'modal_html'       => $modal_html
        );
    }

    public function actionModify($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise au statut "Brouillon" effectué avec succès';

        global $conf, $langs, $user;
        $langs->load('errors');

        if (!$this->can("edit")) {
            $errors[] = 'Vous n\'avez pas la permission de modifier ' . $this->getLabel('this');
        } elseif (!$this->isModifiable() || $this->dol_object->getIdReplacingInvoice() || !$this->dol_object->is_last_in_cycle()) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas modifiable';
        } else {
            $id_entrepot = (int) $this->getData('entrepot');

            $this->dol_object->fetch_thirdparty();

            $qualified_for_stock_change = 0;
            if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
                $qualified_for_stock_change = $this->dol_object->hasProductsOrServices(2);
            } else {
                $qualified_for_stock_change = $this->dol_object->hasProductsOrServices(1);
            }

            // Check parameters
            if ($this->dol_object->type != Facture::TYPE_DEPOSIT && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change) {
                if (!$id_entrepot) {
                    $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse"));
                }
            }

            if (!count($errors)) {
                // On verifie si la facture a des paiements
                $rows = $this->db->getValues('paiement_facture', 'amount', '`fk_facture` = ' . (int) $this->id);
                $totalpaye = 0;

                if (!is_null($rows)) {
                    foreach ($rows as $amount) {
                        $totalpaye += (float) $amount;
                    }
                }

                $resteapayer = $this->dol_object->total_ttc - $totalpaye;

                // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
                $ventilExportCompta = $this->dol_object->getVentilExportCompta();

                // On verifie si aucun paiement n'a ete effectue
                if ($resteapayer == $this->dol_object->total_ttc && $this->dol_object->paye == 0 && $ventilExportCompta == 0) {
                    if ($this->dol_object->set_draft($user, $id_entrepot) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la remise au statut "Brouillon"');
                    }

                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        $outputlangs = $langs;
                        $newlang = '';
                        if ($conf->global->MAIN_MULTILANGS) {
                            $newlang = $this->dol_object->thirdparty->default_lang;
                        }
                        if (!empty($newlang)) {
                            $outputlangs = new Translate("", $conf);
                            $outputlangs->setDefaultLang($newlang);
                        }
                        $this->fetch($this->id); // Reload to get new records

                        if ($this->dol_object->generateDocument($this->getModelPdf(), $outputlangs) <= 0) {
                            $warnings[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'des erreurs sont survenues lors de la génération du document PDF');
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionAddContact($data, &$success)
    {
        $errors = array();

        if (isset($data['type']) && (int) $data['type'] === 1) {
            if (isset($data['tiers_type_contact']) && (int) $data['tiers_type_contact'] &&
                    BimpTools::getTypeContactCodeById((int) $data['tiers_type_contact']) === 'BILLING' && $data['id_client'] != $this->getData('fk_soc')) {
                if ((int) $this->getIdContact('external', 'BILLING')) {
                    $errors[] = 'Une adresse de facturation a déjà été ajouté';
                } elseif ((int) $this->getData("fk_statut") != 0) {
                    $errors[] = 'Facture validée, on ne peut plus ajouter un contact externe';
                } else {
                    $this->updateField('fk_soc', $data['id_client']);
                    $success .= " Attention client Changé.";
                }
            }
        }

        if (count($errors)) {
            return array(
                'errors'   => $errors,
                'warnings' => array()
            );
        }

        return parent::actionAddContact($data, $success);
    }

    // Actions: 

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        global $user;

        if ($this->dol_object->set_unpaid($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_this'));
        } else {
            $this->set('paye', 0);
            $this->set('paiement_status', 0);
            $this->checkIsPaid(true);
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionConvertToReduc($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Conversion en remise effectuée avec succès';

        $errors = $this->convertToRemise();

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }
    
    public function actionClassifyPaidMasse($data, &$success){
        $errors = array();
        $success = 'Ok';
        
        foreach($data['id_objects'] as $idF){
            $fact = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $idF);
            if ($fact->isActionAllowed('classifyPaid') && $fact->canSetAction('classifyPaid')) {
                $succ = '';
                $ret = $fact->actionClassifyPaid(array('close_code'=>$data['close_code'], 'close_note'=>$data['close_note']), $succ);
                if(isset($ret[$errors]) && count($ret[$errors]))
                    $errors = BimpTools::merge_array($errors, $ret[$errors]); 
            }
            else {
                $errors[] = $fact->getRef(). ' n\'est pas fermable';
            }
        }
        return $errors;
    }

    public function actionClassifyPaid($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('the')) . ' ' . $this->getRef() . ' a bien été classé' . ($this->isLabelFemale() ? 'e' : '') . ' "payé' . ($this->isLabelFemale() ? 'e' : '') . '"';

        global $user;

        $remainToPay = $this->getRemainToPay();
        $close_code = (isset($data['close_code']) ? $data['close_code'] : '');
        $close_note = (isset($data['close_note']) ? $data['close_note'] : '');

        if ($remainToPay) {
            if (!$close_code) {
                $errors[] = 'Veuillez sélectionner la raison pour classer ' . $this->getLabel('this') . ' payé' . $this->e();
            }
        }

        if (!count($errors)) {
//            if (((!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT)) && $remainToPay <= 0) ||
//                    ($remainToPay != 0) ||
//                    ($type === Facture::TYPE_DEPOSIT && $this->dol_object->total_ttc > 0 && $remainToPay == 0 && empty($discount->id)))) {
            if ($this->dol_object->set_paid($user, $close_code, $close_note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues');
            } else {
                $this->updateField('paye', 1);
                $this->updateField('paiement_status', 2);
                $this->updateField('remain_to_pay', 0);
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => 'bimp_reloadPage();'
        );
    }

    public function actionCancel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('this')) . ' a bien été classée "abandonné' . ($this->isLabelFemale() ? 'e' : '') . '"';

        $close_code = (isset($data['close_code']) ? $data['close_code'] : '');
        $close_note = (isset($data['close_note']) ? $data['close_note'] : '');

        if (!$close_code) {
            global $langs;
            $errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason"));
        } else {
            global $user;
            if ($this->dol_object->set_canceled($user, $close_code, $close_note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'abandon ' . $this->getLabel('of_the'));
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveFromUserCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait de la commission effectuée avec succès';

        $commission = $this->getChildObject('user_commission');

        if (BimpObject::objectLoaded($commission)) {
            $errors = $this->updateField('id_user_commission', 0);

            if (!count($errors)) {
                $com_errors = $commission->updateAmounts();

                if (count($com_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($com_errors, 'Echec de la mise à jour des montants de la commission');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveFromEntrepotCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Retrait de la commission effectuée avec succès';

        $commission = $this->getChildObject('entrepot_commission');

        if (BimpObject::objectLoaded($commission)) {
            $errors = $this->updateField('id_entrepot_commission', 0);

            if (!count($errors)) {
                $com_errors = $commission->updateAmounts();

                if (count($com_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($com_errors, 'Echec de la mise à jour des montants de la commission');
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionAddToCommission($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $id_commission = (isset($data['id_commission']) ? (int) $data['id_commission'] : 0);

        if (!$id_commission) {
            $errors[] = 'Aucune commission sélectionnée';
        } else {
            $commission = BimpCache::getBimpObjectInstance('bimpfinanc', 'BimpCommission', $id_commission);
            if (!BimpObject::objectLoaded($commission)) {
                $errors[] = 'La commission d\'ID ' . $id_commission . ' n\'existe pas';
            } else {
                if ((int) $commission->getData('status') !== 0) {
                    $errors[] = 'La commission #' . $commission->id . ' n\'est plus au statut "brouillon"';
                } else {
                    $draftCommissions = $this->getDraftCommissionsArray();
                    if (!array_key_exists((int) $commission->id, $draftCommissions)) {
                        $errors[] = 'Cette facture n\'est pas attribuable à la commission #' . $commission->id;
                    } else {
                        $field = '';
                        switch ((int) $commission->getData('type')) {
                            case BimpCommission::TYPE_USER:
                                $field = 'id_user_commission';
                                if ((int) $this->getCommercialId() !== (int) $commission->getData('id_user')) {
                                    $errors[] = 'Le commercial de cette facture ne correspond pas à l\'utilisateur enregistré pour cette commission';
                                }
                                break;

                            case BimpCommission::TYPE_ENTREPOT:
                                $field = 'id_entrepot_commission';
                                if ((int) $this->getData('entrepot') !== (int) $commission->getData('id_entrepot')) {
                                    $errors[] = 'L\'entrepot de cette facture ne correspond pas à l\'entrepôt enregistré pour cette commission';
                                }
                                break;
                        }

                        if (!count($errors)) {
                            $success = 'Facture attribuée à la commission #' . $commission->id . ' avec succès';

                            $errors = $this->updateField($field, $commission->id);

                            if (!count($errors)) {
                                $comm_errors = $commission->updateAmounts();

                                if (count($comm_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($comm_errors, 'Echec de la mise à jour des montants de la commission');
                                }
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCheckPa($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $lines = (isset($data['lines']) ? $data['lines'] : array());

        if (empty($lines)) {
            $errors[] = 'Aucune ligne sélectionnée pour la correction du prix d\'achat';
        } else {
            foreach ($lines as $line_data) {
                $line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', (int) $line_data['id_line']);
                if (!BimpObject::objectLoaded($line)) {
                    $errors[] = 'La ligne d\'ID ' . $line_data['id_line'] . ' n\'existe pas';
                } else {
                    $line_pa_ht = $line->getPaWithRevalorisations();
                    if ((float) $line_pa_ht !== (float) $line_data['new_pa']) {
                        $line_errors = $line->updatePrixAchat((float) $line_data['new_pa']);
                        if (count($line_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne n°' . $line->getData('position'));
                        } else {
                            $success .= ($success ? '<br/>' : '') . 'PA de la ligne n°' . $line->getData('position') . ' mis à jour avec succès';
                        }
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateAcompteRemiseRbt($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $succes_callback = '';

        // Vérifications: 
        BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
        $discount = new DiscountAbsolute($this->db->db);
        $discount->fetch(0, (int) $this->id);

        if (!BimpObject::objectLoaded($discount)) {
            $errors[] = 'Aucune remise créée à partir ' . $this->getLabel('of_this');
        } elseif ((int) $discount->fk_facture) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $discount->fk_facture);
            $errors[] = 'La remise a été consommée dans la facture ' . (BimpObject::objectLoaded($facture) ? $facture->getNomUrl(0, 1, 1, 'full') : '#' . $discount->fk_facture);
        } elseif ((int) $discount->fk_facture_line) {
            $id_facture = $this->db->getValue('facturedet', 'fk_facture', 'rowid = ' . (int) $discount->fk_facture_line);
            $facture = null;
            if ((int) $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);
            }
            $errors[] = 'La remise a été ajouté à la facture ' . (BimpObject::objectLoaded($facture) ? $facture->getNomUrl(0, 1, 1, 'full') : ((int) $id_facture ? '#' . $id_facture : ' (ligne #' . $discount->fk_facture_line . ')'));
        } else {
            BimpObject::loadClass('bimpcore', 'Bimp_Societe');
            $use_label = Bimp_Societe::getDiscountUsedLabel((int) $discount->id, true);

            if ($use_label) {
                $errors[] = 'La remise a été ' . str_replace('Ajouté', 'ajoutée', $use_label);
            }
        }

        // Création de la facture:
        if (!count($errors)) {
            $facture = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                        'datef'             => date('Y-m-d'),
                        'entrepot'          => (int) $this->getData('entrepot'),
                        'libelle'           => 'Remboursement acompte ' . $this->getRef(),
                        'ef_type'           => $this->getData('ef_type'),
                        'fk_soc'            => (int) $this->getData('fk_soc'),
                        'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement'),
                        'fk_mode_reglement' => (int) $this->getData('fk_mode_reglement')
                            ), true, $errors, $warnings);

            if (!count($errors) && BimpObject::objectLoaded($facture)) {
                // Ajout de la remise. 
                $dis_errors = $facture->insertDiscount((int) $discount->id);
                if (count($dis_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($dis_errors, 'Echec de l\'ajout de la remise à la facture de remboursement');
                }

                $url = $facture->getUrl();
                if ($url) {
                    $succes_callback = 'window.open(\'' . $url . '\');';
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $succes_callback
        );
    }

    public function actionGenerateBulkPdf($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier généré avec succès';
        $success_callback = '';

        $id_factures = BimpTools::getArrayValueFromPath($data, 'id_objects', array());

        if (!is_array($id_factures) || empty($id_factures)) {
            $errors[] = 'Aucune facture sélectionnée';
        } else {
            $files = array();

            foreach ($id_factures as $id_facture) {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                if (!BimpObject::objectLoaded($facture)) {
                    $warnings[] = 'La facture d\'ID ' . $id_facture . ' n\'existe pas';
                    continue;
                }

                $dir = $facture->getFilesDir();
                $filename = $facture->getRef() . '.pdf';

                if (!file_exists($dir . $filename)) {
                    $warnings[] = 'Facture ' . $facture->getLink() . ': fichier PDF absent (' . $dir . $filename . ')';
                    continue;
                }

                $files[] = $dir . $filename;
            }

            if (!empty($files)) {
                global $user;
                require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';
                $fileName = 'bulk_factures_' . $user->id . '.pdf';
                $dir = PATH_TMP . '/bimpcore/';

                $pdf = new BimpConcatPdf();
                $pdf->concatFiles($dir . $fileName, $files, 'F');

                $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($fileName);
                $success_callback = 'window.open(\'' . $url . '\');';
            } else {
                $errors[] = 'Aucun PDF trouvé';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionGeneratePDFDuplicata($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpPDF.php';

        $dir = $this->getFilesDir();
        $srcFile = $this->getRef() . '.pdf';
        $destFile = $this->getRef() . '_duplicata.pdf';

        $pdf = new BimpConcatPdf();
        $errors = $pdf->generateDuplicata($dir . $srcFile, $dir . $destFile);

        if (!count($errors)) {
            $url = $this->getFileUrl($destFile);
            $success_callback = 'window.open(\'' . $url . '\');';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionDeactivateRelancesForAMonth($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Désactivation des relances pendant un mois effectuée';

        $dt = new DateTime();
        $dt->add(new DateInterval('P1M'));

        $errors = $this->updateField('date_next_relance', $dt->format('Y-m-d'));

        $this->addNote('Relance désactivée pour un mois');

        if (!count($errors)) {
            $to = BimpCore::getConf('email_for_relances_deactivated_notification', '');

            if ($to) {
                global $user, $langs;

                $msg = 'Les relances concernant la facture ' . $this->getLink() . ' ont été suspendues pendant un mois pas ' . $user->getFullName($langs);
                $msg .= "\n\n";

                $msg .= 'Date de prochaine relance pour cette facture : ' . $dt->format('d / m / Y');

                mailSyn2('Relances suspendues - Facture ' . $this->getRef(), $to, '', $msg);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetIrrecouvrable($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel()) . ' classé' . $this->e() . ' "Irrécouvrable" avec succès';

        global $user;

        $errors = $this->updateFields(array(
            'paiement_status'       => 5,
            'paye'                  => 0,
            'remain_to_pay'         => 0,
            'fk_statut'             => Facture::STATUS_CLOSED,
            'close_code'            => 'irrecouvrable',
            'close_note'            => BimpTools::getArrayValueFromPath($data, 'close_note', ''),
            'date_irrecouvrable'    => date('Y-m-d H:i:s'),
            'id_user_irrecouvrable' => (BimpObject::objectLoaded($user) ? (int) $user->id : 1)
                ), true, $warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCheckPaiements($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Vérification du statut de paiement effectuée';

        $this->checkIsPaid();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSetCommandeLinesNotBilled($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Opération effectuée avec succès';
        $success_callback = '';

        $instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $asso = new BimpAssociation($instance, 'factures');

        $commandes = $asso->getObjectsList($this->id);
        $w = array();

        foreach ($commandes as $id_commande) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);
            $up_comm = false;

            if (BimpObject::objectLoaded($commande)) {
                $lines = $commande->getLines('not_text');

                foreach ($lines as $line) {
                    if (!BimpObject::objectLoaded($line)) {
                        continue;
                    }

                    $up_line = false;
                    $factures = $line->getData('factures');

                    if (is_array($factures)) {
                        foreach ($factures as $id_facture => $fac_data) {
                            if ((int) $id_facture === (int) $this->id) {
                                unset($factures[$id_facture]);
                                $up_line = true;
                            }
                        }
                    }

                    if ($up_line) {
                        $line->set('factures', $factures);
                        $line_errors = $line->update($w, true);
                        if (!empty($line_errors)) {
                            $msg = 'Echec de la mise à jour de la ligne n°' . $line->getData('position') . ' (commande ' . $commande->getRef() . ')';
                            $warnings[] = BimpTools::getMsgFromArray($line_errors, $msg);
                        } else {
                            $up_comm = true;
                        }
                    }
                }

                if ($up_comm) {
                    $commande->checkInvoiceStatus();
                    $success_callback .= 'triggerObjectChange(\'bimpcommercial\', \'Bimp_Commande\', ' . $commande->id . ')';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionLinesToFacture($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $id_facture = (int) BimpTools::getArrayValueFromPath($data, 'id_facture', 0);

        if (!$id_facture) {
            $errors[] = 'Aucune facture sélectionnée';
        } else {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $id_facture);

            if (!BimpObject::objectLoaded($facture)) {
                $errors[] = 'La facture #' . $id_facture . ' n\'existe plus';
            } else {
                if ((int) $facture->getData('fk_statut') !== 0) {
                    $errors[] = 'La facture "' . $facture->getRef() . '" n\'est plus au statut "Brouillon"';
                }

                if ((int) $facture->getData('type') !== (int) Facture::TYPE_STANDARD) {
                    $errors[] = 'La facture "' . $facture->getRef() . '" n\'est pas de type "facture standard"';
                }

                if (!count($errors)) {
                    $errors = $facture->createLinesFromOrigin($this, array(
                        'inverse_qty' => true,
                        'pa_editable' => false
                    ));
                }

                if (!count($errors)) {
                    $success = 'Copie des lignes vers la facture "' . $facture->getRef() . '" effectuée avec succès';
                    $url = $facture->getUrl();
                    if ($url) {
                        $success_callback = 'window.open(\'' . $url . '\')';
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    public function actionCheckMargin($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Total achats / Marge (reval OK) vérifiés';

        $errors = $this->checkMargin(true);
        $errors = BimpTools::merge_array($errors, $this->checkTotalAchat(true));

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides BimpObject:

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['datec'] = date('Y-m-d H:i:s');
        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_valid'] = 0;
        $new_data['id_user_commission'] = 0;
        $new_data['id_entrepot_commission'] = 0;
        $new_data['exported'] = 0;
        $new_data['statut_export'] = 0;
        $new_data['douane_number'] = '';

        // Statut paiement: 
        $new_data['remain_to_pay'] = 0;
        $new_data['paiement_status'] = 0;

        // relances: 
        $new_data['nb_relance'] = 0;
        $new_data['date_relance'] = null;
        $new_data['date_next_relance'] = null;
        $new_data['relance_active'] = 1;

        // Abandon: 
        $new_data['close_code'] = null;
        $new_data['close_note'] = '';
        $new_data['date_irrecouvrable'] = null;
        $new_data['id_user_irrecouvrable'] = 0;

        // Autre: 
        $new_data['prelevement'] = 0;

        return parent::duplicate($new_data, $warnings, $force_create);
    }

    public function fetch($id, $parent = null)
    {
        $result = parent::fetch($id, $parent);

        if ($this->isLoaded()) {
            switch ((int) $this->getData('type')) {
                case Facture::TYPE_STANDARD:
                    $this->params['labels']['name'] = 'facture';
                    $this->params['labels']['name_plur'] = 'factures';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case Facture::TYPE_CREDIT_NOTE:
                    $this->params['labels']['name'] = 'avoir';
                    $this->params['labels']['name_plur'] = 'avoirs';
                    $this->params['labels']['is_female'] = 0;
                    break;

                case Facture::TYPE_DEPOSIT:
                    $this->params['labels']['name'] = 'acompte';
                    $this->params['labels']['name_plur'] = 'acomptes';
                    $this->params['labels']['is_female'] = 0;
                    break;

                case Facture::TYPE_REPLACEMENT:
                    $this->params['labels']['name'] = 'facture de remplacement';
                    $this->params['labels']['name_plur'] = 'factures de remplacement';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case Facture::TYPE_SITUATION:
                    $this->params['labels']['name'] = 'facture de situation';
                    $this->params['labels']['name_plur'] = 'factures de situation';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case Facture::TYPE_PROFORMA:
                    $this->params['labels']['name'] = 'facture proforma';
                    $this->params['labels']['name_plur'] = 'factures proforma';
                    $this->params['labels']['is_female'] = 1;
                    break;
            }
        }

        return $result;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user, $conf, $langs;

        if (!$force_create && !$this->can("create")) {
            return array('Vous n\'avez pas la permission de créer ' . $this->getLabel('a'));
        }

        $errors = $this->validate();

        if (count($errors)) {
            return $errors;
        }

        $type = (int) $this->getData('type');
        $avoir_to_refacture = null;

        $linked_objects = array();

        switch ($type) {
            case Facture::TYPE_REPLACEMENT:
                $id_facture_replaced = (int) BimpTools::getValue('id_facture_replaced', 0);

                if (!$id_facture_replaced) {
                    $errors[] = 'Facture à remplacer absente';
                } else {
                    $facture = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_facture_replaced);
                    if (!$facture->isLoaded()) {
                        $errors[] = 'Facture à remplacer invalide';
                    } else {
                        $facture->dol_object->fetch_thirdparty();
                        foreach ($this->data as $field => $data) {
                            $facture->set($field, $data);
                        }
                        $fk_account = (int) $this->getData('fk_account');

                        $facture->hydrateDolObject();

                        $facture->set('fk_facture_source', $id_facture_replaced);
                        $facture->set('type', (int) $type);

                        $id = $facture->dol_object->createFromCurrent($user);
                        if ($id <= 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($facture->dol_object), 'Des erreurs sont survenues lors de la création de la facture de remplacement');
                        } else {
                            $this->fetch($id);
                            $this->updateField('fk_account', (int) $fk_account);
                        }
                    }
                }
                return $errors;

            case Facture::TYPE_CREDIT_NOTE:
                $facture = null;

                $id_fac_src = (int) BimpTools::getPostFieldValue('id_facture_to_correct', 0);
                $avoir_same_lines = (int) BimpTools::getPostFieldValue('avoir_same_lines', 0);
                $avoir_remain_to_pay = (int) BimpTools::getPostFieldValue('avoir_remain_to_pay', 0);

                if ($avoir_same_lines && $avoir_remain_to_pay) {
                    $errors[] = 'Il n\'est pas possible de choisir l\'option "Créer l\'avoir avec les même lignes que la factures dont il est issu" et "Créer l\'avoir avec le montant restant à payer de la facture dont il est issu" en même temps. Veuillez choisir l\'une ou l\'autre';
                }

                if (!$id_fac_src && (empty($conf->global->INVOICE_CREDIT_NOTE_STANDALONE) || $avoir_same_lines || $avoir_remain_to_pay)) {
                    $errors[] = 'Facture à corriger absente';
                } elseif ($id_fac_src) {
                    $facture = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id_fac_src);
                    if (!$facture->isLoaded()) {
                        $errors[] = 'Facture à corriger invalide';
                    } else {
                        $this->set('fk_facture_source', $id_fac_src);
                    }
                }

                if (!count($errors)) {
                    $linked_objects = BimpTools::getDolObjectLinkedObjectsList($facture->dol_object, $this->db);

                    foreach ($linked_objects as $item) {
                        if (!isset($this->dol_object->linked_objects[$item['type']])) {
                            $this->dol_object->linked_objects[$item['type']] = array();
                        } elseif (!is_array($this->dol_object->linked_objects[$item['type']])) {
                            $this->dol_object->linked_objects[$item['type']] = array($this->dol_object->linked_objects[$item['type']]);
                        }

                        if (!in_array((int) $item['id_object'], $this->dol_object->linked_objects[$item['type']])) {
                            $this->dol_object->linked_objects[$item['type']] = (int) $item['id_object'];
                        }
                    }

                    $errors = parent::create($warnings, true);
                }

                if (count($errors)) {
                    return $errors;
                }

                if ($avoir_same_lines) {
                    $line_errors = $this->createLinesFromOrigin($facture, array(
                        'inverse_qty' => true,
                        'pa_editable' => false
                    ));
                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors);
                    }

                    // Copie des remises globales: 
                    $this->copyRemisesGlobalesFromOrigin($facture, $warnings, true);
                } elseif ($avoir_remain_to_pay) {
                    $this->dol_object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'), $facture->getRemainToPay() * -1, 1, 0, 0, 0, 0, 0, '', '', 'TTC');
                }

                // Copie des contacts: 
                if (BimpObject::objectLoaded($facture)) {
                    $this->copyContactsFromOrigin($facture, $warnings);
                }
                break;

            case Facture::TYPE_STANDARD:
                if (BimpTools::isSubmit('id_avoir_to_refacture')) {
                    $id_avoir_to_refacture = (int) BimpTools::getPostFieldValue('id_avoir_to_refacture', 0);
                    if (!$id_avoir_to_refacture) {
                        $errors[] = 'ID de l\'avoir à refacturer absent';
                        return $errors;
                    } else {
                        $avoir_to_refacture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_avoir_to_refacture);
                        if (!BimpObject::objectLoaded($avoir_to_refacture)) {
                            $errors[] = 'L\'avoir à refacturer d\'ID ' . $id_avoir_to_refacture . ' n\'existe pas';
                            return $errors;
                        } elseif ((int) $avoir_to_refacture->getData('type') !== Facture::TYPE_CREDIT_NOTE) {
                            $errors[] = 'Type de l\'avoir à refacturer invalide';
                            return $errors;
                        }
                        $this->dol_object->linked_objects['facture'] = array($id_avoir_to_refacture);

                        $linked_objects = BimpTools::getDolObjectLinkedObjectsList($avoir_to_refacture->dol_object, $this->db);

                        foreach ($linked_objects as $item) {
                            if (!isset($this->dol_object->linked_objects[$item['type']])) {
                                $this->dol_object->linked_objects[$item['type']] = array();
                            }

                            if (!in_array((int) $item['id_object'], $this->dol_object->linked_objects[$item['type']])) {
                                $this->dol_object->linked_objects[$item['type']] = (int) $item['id_object'];
                            }
                        }
                    }
                }
            case Facture::TYPE_DEPOSIT:
            case Facture::TYPE_PROFORMA:
                $errors = parent::create($warnings, true);

                if (!count($errors)) {
                    if (BimpObject::objectLoaded($avoir_to_refacture)) {
                        $params = array(
                            'pa_editable' => false
                        );

                        if ($avoir_to_refacture->getData('datec') < '2020-09-24 00:00:00') { // Date d'inversion des qtés au lieu des prix dans les avoirs. 
                            $params['inverse_prices'] = true;
                        } else {
                            $params['inverse_qty'] = true;
                        }

                        // copie des lignes: 
                        $lines_errors = $this->createLinesFromOrigin($avoir_to_refacture, $params);
                        if (count($lines_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($lines_errors);
                        }

                        // Copie des contacts: 
                        $this->copyContactsFromOrigin($avoir_to_refacture, $warnings);

                        // Copie des remises globales: 
                        $this->copyRemisesGlobalesFromOrigin($avoir_to_refacture, $warnings, true);
                    }
                }
                break;

            default:
                $errors[] = 'Type de facture invalide';
                break;
        }

        if (!count($errors)) {
            $this->fetch($this->id);

            // Asso avec commandes: 
            if (!empty($linked_objects)) {
                foreach ($linked_objects as $item) {
                    if ($item['type'] === 'commande') {
                        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $item['id_object']);
                        if (BimpObject::objectLoaded($commande)) {
                            $asso = new BimpAssociation($commande, 'factures');
                            $asso->addObjectAssociation((int) $this->id);
                            unset($asso);
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_fk_account = (int) $this->getInitData('fk_account');
        $fk_account = (int) $this->getData('fk_account');

        $id_cond_reglement = (int) $this->getData('fk_cond_reglement');

        $changeCondRegl = $id_cond_reglement !== (int) $this->getInitData('fk_cond_reglement');
        $changeDateF = $this->getData('datef') != $this->getInitData('datef');

        if ($changeCondRegl || $changeDateF) {
            $this->dol_object->date = strtotime($this->getData('datef'));
            $this->set('date_lim_reglement', BimpTools::getDateFromDolDate($this->dol_object->calculate_date_lim_reglement($id_cond_reglement)));
        }

        if ($this->getInitData('date_next_relance') != $this->getData('date_next_relance')) {
            $this->addNote('Date prochaine relance modfifiée ' . $this->getData('date_next_relance'));
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($fk_account !== $init_fk_account) {
                $this->updateField('fk_account', $fk_account);
            }
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function sendInvoiceDraftWhithMail()
    {
        $date = new DateTime();
        $nbDay = 5;
        $date->sub(new DateInterval('P' . $nbDay . 'D'));
        $sql = $this->db->db->query("SELECT rowid FROM `" . MAIN_DB_PREFIX . "facture` WHERE `datec` < '" . $date->format('Y-m-d') . "' AND `fk_statut` = 0");
        $i = 0;
        while ($ln = $this->db->db->fetch_object($sql)) {
            $obj = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $ln->rowid);
            $userCreate = new User($this->db->db);
            $userCreate->fetch((int) $obj->getData('fk_user_author'));

            $mail = $userCreate->email;
            if ($mail == '')
                $mail = "tommy@bimp.fr";
            require_once(DOL_DOCUMENT_ROOT . "/synopsistools/SynDiversFunction.php");
            if (mailSyn2('Facture brouillon à régulariser', $mail, 'admin@bimp.fr', 'Bonjour, vous avez laissé une facture en l’état de brouillon depuis plus de ' . $nbDay . ' jour(s) : ' . $obj->getNomUrl() . ' <br/>Merci de bien vouloir la régulariser au plus vite.'))
                $i++;
        }
        $this->resprints = "OK " . $i . ' mails';
        return "OK " . $i . ' mails';
    }

    public static function checkIsPaidAll($filters = array())
    {
        $filters['fk_statut'] = array(
            'operator' => '>',
            'value'    => 0
        );
        $filters['paye'] = 0;

        $items = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Facture', $filters);

        foreach ($items as $id_fac) {
            $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
            if (BimpObject::objectLoaded($fac)) {
                $fac->checkIsPaid();
            }
        }
    }

    public static function checkRemainToPayAll($echo = false)
    {
        $items = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Facture', array(
                    'fk_statut' => array(
                        'operator' => '>',
                        'value'    => 0
                    ),
                    'paye'      => 0
        ));

        foreach ($items as $id_fac) {
            $fac = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac); // On ne passe pas par le cache vu le nombre d'objets à instancier. 
            if (BimpObject::objectLoaded($fac)) {
                $init_rtp = (float) $fac->getData('remain_to_pay');
                $fac->checkRemainToPay();
                $new_rtp = (float) $fac->getData('remain_to_pay');

                if ($echo && $init_rtp != $new_rtp) {
                    echo 'FAC #' . $fac->id . ': ' . $init_rtp . ' => ' . $new_rtp . '<br/>';
                }
            }
        }
    }

    public static function checkRemisesGlobalesAll($echo = false, $create_avoirs = false)
    {
        $fields = array('f.rowid');
        $joins = array(
            array(
                'table' => 'bimp_remise_globale',
                'alias' => 'rg',
                'on'    => 'rg.id_obj = f.rowid'
            )
        );
        $filters = array(
            'rg.obj_type'       => 'invoice',
            'f.fk_statut'       => array(
                'operator' => '>',
                'value'    => 0
            ),
            'f.paiement_status' => array(
                'operator' => '<',
                'value'    => 2
            )
        );

        $sql .= BimpTools::getSqlSelect($fields, 'f');
        $sql .= BimpTools::getSqlFrom('facture', $joins, 'f');
        $sql .= BimpTools::getSqlWhere($filters, 'f');

        $rows = self::getBdb()->executeS($sql, 'array');

        foreach ($rows as $r) {
            $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

            if (BimpObject::objectLoaded($facture)) {
                $facture->checkIsPaid();

                if ($facture->getData('paiement_status') < 2) {
                    $facture->checkRemisesGlobales($echo, $create_avoirs);
                }
            }
        }
    }

    public static function cancelFacturesFromRefsFile($refs_file = '', $echo = false)
    {
        global $user, $db;
        $errors = array();

        if (!file_exists($refs_file)) {
            $errors[] = 'Fichier "' . $refs_file . '" absent';
        } else {
            $refs = file($refs_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (is_array($refs)) {
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');

                foreach ($refs as $ref) {
                    $fac = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array(
                                'facnumber' => $ref
                    ));

                    if (!BimpObject::objectLoaded($fac)) {
                        if ($echo) {
                            echo BimpRender::renderAlerts('Facture "' . $ref . '" non trouvée');
                        }
                        $errors[] = 'Facture "' . $ref . '" non trouvée';
                        continue;
                    }

                    if ($echo) {
                        echo $ref . ': ';
                    }

                    if ($fac->getData('fk_statut') !== 1) {
                        if ($echo) {
                            echo '<span class="danger">Statut invalide</span><br/>';
                        }
                        $errors[] = 'Fac ' . $ref . ': satatut invalide';
                        continue;
                    }

                    $fac->checkIsPaid();

                    if ($fac->getData('paiement_status') > 0 || (int) $fac->getData('paye')) {
                        if ($echo) {
                            echo '<span class="danger">Statut paiement invalide</span><br/>';
                        }
                        $errors[] = 'Fac ' . $ref . ': satatut paiement invalide';
                        continue;
                    }

                    $fac_errors = array();
                    $fac_warnings = array();
                    $new_fac = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                'fk_facture_source' => $fac->id,
                                'type'              => 0,
                                'fk_soc'            => (int) $fac->getData('fk_soc'),
                                'entrepot'          => (int) $fac->getData('entrepot'),
                                'contact_id'        => (int) $fac->getData('contact_id'),
                                'fk_account'        => (int) $fac->getData('fk_account'),
                                'ef_type'           => $fac->getData('ef_type'),
                                'datef'             => date('Y-m-d'),
                                'libelle'           => 'Annulation ' . $fac->getLabel() . ' ' . $ref,
                                'relance_active'    => 0,
                                'fk_cond_reglement' => $fac->getData('fk_cond_reglement'),
                                'fk_mode_reglement' => $fac->getData('fk_mode_reglement')
                                    ), true, $fac_errors, $fac_warnings);

                    if (!BimpObject::objectLoaded($new_fac)) {
                        if ($echo) {
                            echo '<span class="danger">ECHEC CREA FAC ANNULATION</span>';
                            if (count($fac_errors)) {
                                echo BimpRender::renderAlerts($fac_errors);
                            }
                            if (count($fac_warnings)) {
                                echo BimpRender::renderAlerts($fac_warnings, 'warning');
                            }
                        }
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Echec création fac d\'annulation');
                    } else {
                        // Copie des lignes:
                        $lines_errors = $new_fac->createLinesFromOrigin($fac, array(
                            'pa_editable' => false,
                            'inverse_qty' => true
                        ));

                        if (count($lines_errors)) {
                            if ($echo) {
                                echo BimpRender::renderAlerts($lines_errors, 'Echec copie des lignes');
                            }
                            $errors[] = BimpTools::getMsgFromArray($lines_errors, 'Fac ' . $ref . ': échec copie des lignes');
                        } else {
                            // Copie des contacts: 
                            $new_fac->copyContactsFromOrigin($fac);

                            // Copie des remises globales: 
                            $new_fac->copyRemisesGlobalesFromOrigin($fac, $fac_warnings, true);

                            // Validation: 
                            if ($new_fac->dol_object->validate($user, '', 0, 0) <= 0) {
                                if ($echo) {
                                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_fac->dol_object), 'Echec de la validation de la facture d\'annulation'), 'danger');
                                }
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_fac->dol_object), 'Echec de la validation de la facture d\'annulation');
                            } else {
                                $new_fac->fetch($new_fac->id);


                                if ($fac->dol_object->total_ttc < 0) {
                                    $facture = $new_fac;
                                    $avoir = $fac;
                                } else {
                                    $facture = $fac;
                                    $avoir = $new_fac;
                                }

                                // Conversion en remise: 
                                $conv_errors = $avoir->convertToRemise();
                                if ($conv_errors) {
                                    if ($echo) {
                                        echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($conv_errors, 'ECHEC CONVERSION EN REMISE'));
                                    }
                                    $errors[] = BimpTools::getMsgFromArray($conv_errors, 'FAC ' . $ref . ': échec conversion en remise');
                                } else {
                                    // Application de la remise: 
                                    $discount = new DiscountAbsolute($db);
                                    $discount->fetch(0, $avoir->id);

                                    if (BimpObject::objectLoaded($discount)) {
                                        if ($discount->link_to_invoice(0, $facture->id) <= 0) {
                                            if ($echo) {
                                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'ECHEC UTILISATION REMISE'));
                                            }
                                        } else {
                                            if ($echo) {
                                                echo '<span class="success">OK</span><br/>';
                                            }
                                            $facture->fetch($facture->id);
                                            $facture->checkIsPaid();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public static function checkMarginAll()
    {
        ini_set('max_execution_time', 3600);

        $errors = array();
        $rows = self::getBdb()->getRows('facture', "`datec` > '2021-01-14 00:00:00'", null, 'array', array('rowid', 'marge_finale_ok', 'total_achat_reval_ok'), 'rowid', 'desc');

        if (is_array($rows)) {
            $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
            foreach ($rows as $r) {
                if ($facture->fetch((int) $r['rowid'])) {
                    $fac_errors = array();

//                    if (!(float) $r['marge_finale_ok']) {
                        $fac_errors = $facture->checkMargin(true);
//                    }

//                    if (!(float) $r['total_achat_reval_ok']) {
                        $fac_errors = BimpTools::merge_array($fac_errors, $facture->checkTotalAchat(true));
//                    }

                    if (count($fac_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($fac_errors, 'Fac #' . $r['rowid']);
                    }
                } else {
                    $errors[] = 'Fac #' . $r['rowid'] . ' KO';
                }
            }
        }

        return $errors;
    }
}
