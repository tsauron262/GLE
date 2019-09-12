<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

global $langs;
$langs->load('bills');
$langs->load('errors');

class Bimp_Facture extends BimpComm
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public $acomptes_allowed = true;
    public static $dol_module = 'facture';
    public static $email_type = 'facture_send';
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

    public function iAmAdminRedirect()
    {
        global $user;
        if (in_array($user->id, array(7)) || $user->admin)
            return true;
        parent::iAmAdminRedirect();
    }

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
            case 'cancel':
                return $this->can("create");

            case 'classifyPaid':
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
            case 'addToCommission':
                $commission = BimpObject::getInstance('bimpfinanc', 'BimpCommission');
                return (int) $commission->can('create');
        }

        return parent::canSetAction($action);
    }

    // Getters booléens:

    public function isDeletable($force_delete = false)
    {
        if (!$this->isLoaded()) {
            return 0;
        }

        // Suppression autorisée seulement pour les brouillons:
        if ((int) $this->getData('fk_statut') > 0) {
            return 0;
        }

        // Si facture BL, on interdit la suppression s'il existe une facture hors expédition pour la commande:
        $rows = (int) $this->db->getRows('bl_commande_shipment', '`id_facture` = ' . (int) $this->id, null, 'object', array('id', 'id_commande_client'));
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $row) {
                $id_facture = $this->db->getValue('commande', 'id_facture', '`rowid` = ' . (int) $row->id_commande_client);
                if (!is_null($id_facture) && (int) $id_facture) {
                    return 0;
                }
            }
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('validate', 'modify', 'reopen', 'sendMail', 'addAcompte', 'removeFromUserCommission', 'removeFromEntrepotCommission', 'addToCommission', 'convertToReduc'))) {
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
                if ($status !== Facture::STATUS_DRAFT) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est plus au statut brouillon';
                    return 0;
                }

                $soc = $this->getChildObject('client');
                if (!BimpObject::objectLoaded($soc)) {
                    $errors[] = 'Client absent ou invalide';
                    return 0;
                }

                $lines = $this->getLines('not_text');
                if (!count($lines)) {
                    $errors[] = 'Aucune ligne ajoutée  ' . $this->getLabel('to');
                    return 0;
                }

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
                    return 0;
                }

                if (($type == Facture::TYPE_CREDIT_NOTE && (float) $this->getData('total_ttc') > 0)) {
                    if ($this->isLabelFemale()) {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' positives non autorisées';
                    } else {
                        $errors[] = BimpTools::ucfirst($this->getLabel('name_plur')) . ' positifs non autorisés';
                    }
                    return 0;
                }
                return 1;

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
                return (int) parent::isActionAllowed($action, $errors);

            case 'convertToReduc':
                if (!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD))) {
                    $errors[] = 'Conversion en remise non autorisée pour ce type de facture';
                    return 0;
                }

                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);

                if (BimpObject::objectLoaded($discount)) {
                    $errors[] = 'Une remise à déjà été créée à partir de ' . $this->getLabel('this');
                    return 0;
                }

                if ((int) $this->dol_object->paye) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est classé' . $this->e() . ' "payé' . $this->e() . '"';
                    return 0;
                }

                if ($status !== 1) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "validé' . $this->e() . '"';
                    return 0;
                }

                $remainToPay = (float) $this->getRemainToPay();

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
                        if ($remainToPay !== 0) {
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

                $remainToPay = (float) $this->getRemainToPay();

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
        $remainToPay = (float) $this->getRemainToPay();
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
                    'icon'    => 'check',
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
                'icon'     => 'check',
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
                        'icon'    => 'undo',
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
                    'icon'    => 'undo',
                    'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la réouverture de ' . $this->getLabel('this')
                    ))
                );
            } elseif (in_array($status, array(2, 3))) {
                if ($this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'    => 'Réouvrir',
                        'icon'     => 'undo',
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
                    'icon'    => 'envelope',
                    'onclick' => $this->getJsActionOnclick('sendEmail', array(), array(
                        'form_name' => 'email'
                    ))
                );
            } elseif (in_array($status, array(1, 2))) {
                $buttons[] = array(
                    'label'    => 'Envoyer par email',
                    'icon'     => 'envelope',
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
                    'icon'    => 'euro',
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
                        'single_amount' => (string) abs(round((float) $this->getRemainToPay(), 2))
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
                            'icon'    => 'percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('ExcessReceived'))))
                            ))
                        );
                        break;

                    case Facture::TYPE_CREDIT_NOTE:
                        $buttons[] = array(
                            'label'   => $langs->trans('Convertir le reste à rembourser en remise'),
                            'icon'    => 'percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('CreditNote'))))
                            ))
                        );
                        break;

                    case Facture::TYPE_DEPOSIT:
                        $buttons[] = array(
                            'label'   => 'Convertir en remise',
                            'icon'    => 'percent',
                            'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('Deposit'))))
                            ))
                        );
                        break;
                }
            }

            if ($status == 1 && !$paye) {
                // Classer "Payée": 
                if ((!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT) && $remainToPay <= 0)) ||
                        ($type === Facture::TYPE_CREDIT_NOTE && $remainToPay >= 0) ||
                        ($type === Facture::TYPE_DEPOSIT && $this->dol_object->total_ttc > 0 && $remainToPay == 0 && empty($discount->id))) {
                    $buttons[] = array(
                        'label'   => $langs->trans('ClassifyPaid'),
                        'icon'    => 'check',
                        'onclick' => $this->getJsActionOnclick('classifyPaid', array(), array(
                            'confirm_msg' => strip_tags($langs->trans('ConfirmClassifyPaidBill', $ref))
                        ))
                    );
                }

                // Classer "Payée partiellement": 
                if ($remainToPay > 0) {
                    if ($this->canSetAction('classifyPaid') && $total_paid > 0) {
                        $buttons[] = array(
                            'label'   => $langs->trans('ClassifyPaidPartially'),
                            'icon'    => 'exclamation',
                            'onclick' => $this->getJsActionOnclick('classifyPaid', array(), array(
                                'form_name' => 'paid_partially'
                            ))
                        );
                    } else {
                        if ($this->canSetAction('cancel') && empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED)) {
                            if (!$id_replacing_invoice) {
                                $buttons[] = array(
                                    'label'   => $langs->trans('ClassifyCanceled'),
                                    'icon'    => 'times',
                                    'onclick' => $this->getJsActionOnclick('cancel', array(), array(
                                        'form_name' => 'cancel'
                                    ))
                                );
                            } else {
                                $buttons[] = array(
                                    'label'    => $langs->trans('ClassifyCanceled'),
                                    'icon'     => 'time',
                                    'onclick'  => '',
                                    'disabled' => 1,
                                    'popover'  => $langs->trans("DisabledBecauseReplacedInvoice")
                                );
                            }
                        }
                    }
                }
            }

            // Cloner: 
            if ($this->can("create")) {
                $buttons[] = array(
                    'label'   => 'Cloner',
                    'icon'    => 'copy',
                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
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
                                'icon'    => 'fas_copy',
                                'onclick' => 'window.location = \'' . $url . '\';'
                            );
                        }
                    } else {
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
                                'ef_type'               => $this->getData('ef_type')
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
                            'ef_type'               => $this->getData('ef_type')
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

        return $buttons;
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

        $remainToPay = (float) $this->getRemainToPay();
        return array(
            'discount_vat' => array(
                'label' => $langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat", $remainToPay, $langs->trans("Currency" . $conf->currency)),
                'help'  => $langs->trans("HelpEscompte") . '<br><br>' . $langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc")
            ),
            'badcustomer'  => array(
                'label' => $langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $remainToPay, $langs->trans("Currency" . $conf->currency)),
                'help'  => $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc")
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

            return $paid;
        }
    }

    public function getRemainToPay()
    {
        if ($this->isLoaded()) {
            if ($this->dol_object->paye) {
                return 0;
            }

            return (float) $this->dol_object->total_ttc - (float) $this->getTotalPaid();
        }
        return 0;
    }

    public function getRef($withGeneric = true)
    {
        return $this->getData('facnumber');
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

    public function getTotalRevalorisations()
    {
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

        return $totals;
    }

    // Affichages: 

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
        if($modeCSV)
            return html_entity_decode ($label);
        return $html;
    }

    public function displayType()
    {
        $html = $this->displayData('type');
        $extra = $this->displayTypeExtra();
        if ($extra) {
            $html .= '<br/>' . $extra;
        }

        return $html;
    }

    public function displayTypeExtra()
    {
        $html = '';

        if ($this->isLoaded()) {
            global $langs;

            $type = (int) $this->getData('type');

            switch ($type) {
                case Facture::TYPE_REPLACEMENT:
                    if ((int) $this->dol_object->fk_facture_source) {
                        $facture = new Facture($this->db->db);
                        $facture->fetch((int) $this->dol_object->fk_facture_source);
                        if (!BimpObject::objectLoaded($facture)) {
                            $html .= BimpRender::renderAlerts('La facture remplacée n\'existe plus');
                        } else {
                            $html .= 'Remplace la facture ' . $facture->getNomUrl(1);
                        }
                    } else {
                        $html .= BimpRender::renderAlerts('ID de la facture remplacée absent');
                    }
                    break;

                case Facture::TYPE_CREDIT_NOTE:
                    if ((int) $this->dol_object->fk_facture_source) {
                        $facture = new Facture($this->db->db);
                        $facture->fetch((int) $this->dol_object->fk_facture_source);
                        if (!BimpObject::objectLoaded($facture)) {
                            $html .= BimpRender::renderAlerts('La facture corrigée n\'existe plus');
                        } else {
                            $html .= 'Correction de la facture ' . $facture->getNomUrl(1);
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
                    $facavoir->fetch((int) $id_avoir);
                    $html .= $facavoir->getNomUrl(1);
                }
            }

            $id_next = (int) $this->dol_object->getIdReplacingInvoice();
            if ($id_next > 0) {
                $facReplacement = new Facture($this->db->db);
                $facReplacement->fetch((int) $id_next);
                if ($html) {
                    $html .= '<br/>';
                }
                $html .= $langs->transnoentities("ReplacedByInvoice", $facReplacement->getNomUrl(1));
            }

            if (in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                $discount = new DiscountAbsolute($this->db->db);
                $result = $discount->fetch(0, $this->id);
                if ($result > 0) {
                    if ($html) {
                        $html .= '<br/>';
                    }
                    $html .= BimpTools::ucfirst($this->getLabel('this')) . ' a été converti' . ($this->isLabelFemale() ? 'e' : '') . ' en ';
                    $html .= $discount->getNomUrl(1, 'discount');
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
            $html .= '<td>' . BimpTools::displayMoneyValue($total_ttc) . '</td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            // *** Déjà payé : *** 

            $html .= '<tr>';
            $html .= '<td style="text-align: right;"><strong>Paiements effectués</strong>';
//            if ((int) $this->getData('type') !== Facture::TYPE_DEPOSIT) {
//                $html .= '<br/>(Hors avoirs et acomptes)';
//            }
            $html .= ' : </td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR') . '</td>';
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
                        $label .= $facture->dol_object->getNomUrl(0);
                    } else {
                        $label = ((string) $r['description'] ? $r['description'] : 'Remise');
                    }

                    $total_avoirs += (float) $r['amount_ttc'];

                    $html .= '<td style="text-align: right;">';
                    $html .= $label . ' : </td>';

                    $html .= '<td>' . BimpTools::displayMoneyValue((float) $r['amount_ttc'], 'EUR') . '</td>';
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

                        case 'badcustomer':
                            $label = $form->textwithpicto($langs->trans("Abandoned") . ':', $langs->trans("HelpAbandonBadCustomer"), - 1);
                            break;

                        case 'product_returned':
                            $label = 'Produit retourné :';
                            $remainToPay_final = 0;
                            break;

                        case 'abandon':
                            $text = $langs->trans("HelpAbandonOther");
                            if ($this->dol_object->close_note) {
                                $text .= '<br/><br/><b>' . $langs->trans("Reason") . '</b>:' . $this->dol_object->close_note;
                            }
                            $label = $form->textwithpicto($langs->trans("Abandoned") . ':', $text, - 1);
                            break;
                    }
                    if ($label) {
                        $html .= '<tr>';
                        $html .= '<td style="text-align: right">' . $label . '</td>';
                        $html .= '<td>' . BimpTools::displayMoneyValue($remainToPay, 'EUR') . '</td>';
                        $html .= '<td></td>';
                        $html .= '</tr>';
                    }
                }

                // Trop perçu converti en remise: 
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);
                if (BimpObject::objectLoaded($discount)) {
                    $remainToPay_final += (float) $discount->amount_ttc;
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right;">';
                    $html .= '<strong>Trop perçu converti en </strong>' . $discount->getNomUrl(1, 'discount');
                    $html .= '</td>';
                    $html .= '<td>' . BimpTools::displayMoneyValue($discount->amount_ttc) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                // Converti en remise: 
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);
                if (BimpObject::objectLoaded($discount)) {
                    $remainToPay_final += $discount->amount_ttc;
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right;">';
                    $html .= '<strong>Converti en </strong>' . $discount->getNomUrl(1, 'discount');
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($discount->amount_ttc);
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }

            // Reste à payer: 
            if ((int) $this->getData('paye')) {
                $remainToPay_final = 0;
                $class = 'success';
            } elseif ($type !== Facture::TYPE_CREDIT_NOTE) {
                $class = ($remainToPay_final > 0 ? 'danger' : ($remainToPay_final < 0 ? 'warning' : 'success'));
            } else {
                $class = ($remainToPay_final < 0 ? 'danger' : ($remainToPay_final > 0 ? 'warning' : 'success'));
            }

            $html .= '<tr style="background-color: #F0F0F0; font-weight: bold">';
            $html .= '<td style="text-align: right;"><strong>Reste à payer</strong> : </td>';
            $html .= '<td style="font-size: 18px;">';
            $html .= '<span class="' . $class . '">';
            $html .= BimpTools::displayMoneyValue($remainToPay_final, 'EUR');
            $html .= '</span>';
            $html .= '</td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $html .= '</tbody>';
            $html .= '</table>';

            // Boutons 

            $html .= '<div class="buttonsContainer align-center">';
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
                        'single_amount' => abs(round((float) $this->getRemainToPay(), 2))
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
            if (!(int) $this->getData('paye') && in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_REPLACEMENT, Facture::TYPE_DEPOSIT, Facture::TYPE_CREDIT_NOTE))) {
                if ($user->rights->facture->paiement && in_array((int) $this->getData('fk_statut'), array(1, 2))) {
                    $is_avoir = ($type === Facture::TYPE_CREDIT_NOTE ? 1 : 0);

                    if (!$is_avoir) {
                        $discount_amount = (float) $this->getSocAvailableDiscountsAmounts();
                        if ($discount_amount) {
                            $buttons[] = array(
                                'label'       => 'Appliquer un avoir disponible',
                                'icon_before' => 'fas_file-import',
                                'classes'     => array('btn', 'btn-default'),
                                'attr'        => array(
                                    'onclick' => $this->getJsActionOnclick('useRemise', array(), array(
                                        'form_name' => 'use_remise'
                                    ))
                                )
                            );
                        }
                    }

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
        return '<span style="display: inline-block; margin-left: 12px"' . $this->displayPaidStatus() . '</span>';
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {

            $type_extra = $this->displayTypeExtra();

            if ($type_extra) {
                $html .= '<div style="font-size: 18px">' . $type_extra . '</div>';
            }
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . date('d / m / Y', $this->dol_object->date_creation) . '</strong>';

            $user->fetch((int) $this->dol_object->user_author);
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            $status = (int) $this->getData('fk_statut');
            if ($status >= 1 && (int) $this->dol_object->user_valid) {
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée le <strong>' . date('d / m / Y', $this->dol_object->date_validation) . '</strong>';
                $user->fetch((int) $this->dol_object->user_valid_id);
                $html .= ' par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }
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
                            if (round((float) $line->getTotalTTC(), 2) || round((float) $line->pa_ht, 2)) {
                                $has_amounts_lines = true;
                            }
                            if ((float) $line->getTotalTTC() < 0 && !(int) $line->id_remise_except) {
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
            $html = '<p style="font-size: 16px">';
            $html .= '<span style="font-size: 24px">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
            $html .= '</span>';
            $html .= '<span class="bold">ATTENTION</span>, la création directe de facture est réservée à des cas exceptionnels et ne doit être utilisée qu\'en dernier recours.<br/>';
            $html .= 'Pour les cas ordinaires, vous devez ';
            $html .= '<span class="bold">impérativement passer par le processus de commande.</span>';
            $html .= '</p>';

            return array(
                array(
                    'content' => $html,
                    'type'    => 'warning'
                )
            );
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
                $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($remises_crt, '') . '</span></td>';
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
            $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($revals['accepted'], '') . '</span></td>';
            $html .= '<td></td>';
            $html .= '</tr>';

            $total_pa -= (float) $revals['accepted'];
        }

        if ((float) $revals['attente']) {
            $html .= '<tr>';
            $html .= '<td>Revalorisations en attente</td>';
            $html .= '<td></td>';
            $html .= '<td><span class="danger">-' . BimpTools::displayMoneyValue($revals['attente'], '') . '</span></td>';
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
            $html .= '<td>' . BimpTools::displayMoneyValue($total_pv, '') . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_pa, '') . '</td>';
            $html .= '<td>' . BimpTools::displayMoneyValue($total_marge, '') . ' (' . BimpTools::displayFloatValue($tx, 4) . ' %)</td>';
            $html .= '</tr>';
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
                if (round((float) $line->getTotalTTC(), 2) || round((float) $line->pa_ht, 2)) {
                    $has_amounts_lines = true;
                }
                if ((float) $line->getTotalTTC() < 0 && !(int) $line->id_remise_except) {
                    $neg_lines++;
                }
            }

            if (!$total_ttc && !$has_amounts_lines) {
                $errors[] = 'Aucune ligne avec montant non nul ajoutée à cette facture';
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

    public function onValidate()
    {
        if ($this->isLoaded()) {
            $this->set('fk_statut', Facture::STATUS_VALIDATED);
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
                                            'fk_facture_line' => 0,
                                            'fk_facture'      => (int) $this->id
                                                ), '`rowid` = ' . (int) $discount->id) > 0) {
                                    $this->db->delete('facturedet', '`rowid` = ' . (int) $line->getData('id_line'));
                                    $this->db->delete('bimp_facture_line', '`id` = ' . (int) $line->id);
                                    $done++;
                                }
                            }
                        }
                    } elseif ((int) $line->getData('type') !== ObjectLine::LINE_TEXT) {
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

            $this->checkIsPaid();
        }
    }

    public function majStatusOtherPiece()
    {
        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
        $asso = new BimpAssociation($commande, 'factures');

        $list = $asso->getObjectsList((int) $this->id);

        foreach ($list as $id_commande) {
            $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $id_commande);
            $commande->checkInvoiceStatus($this->id);
        }
    }

    public function onDelete()
    {
        if ($this->isLoaded()) {
            $lines = $this->getChildrenObjects('lines', array(
                'linked_object_name' => 'commande_line'
            ));

            foreach ($lines as $line) {
                $commande_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));

                if (BimpObject::objectLoaded($commande_line)) {
                    $commande_line->onFactureDelete($this->id);
                }
            }
        }
        $this->majStatusOtherPiece();
    }

    public function onUnValidate()
    {
        if ($this->isLoaded()) {
            $this->set('fk_statut', Facture::STATUS_DRAFT);
            $this->majStatusOtherPiece();
        }
    }

    public function createFromCommande(Commande $commande, $id_account = 0, $public_note = '', $private_note = '')
    {
        global $user, $hookmanager;
        $this->reset();
        $error = 0;
        $this->dol_object->date = dol_now();
        $this->dol_object->source = 0;
        $this->dol_object->socid = $commande->socid;
        $this->dol_object->fk_project = $commande->fk_project;
        $this->dol_object->cond_reglement_id = $commande->cond_reglement_id;
        $this->dol_object->mode_reglement_id = $commande->mode_reglement_id;
        $this->dol_object->availability_id = $commande->availability_id;
        $this->dol_object->demand_reason_id = $commande->demand_reason_id;
        $this->dol_object->date_livraison = $commande->date_livraison;
        $this->dol_object->fk_delivery_address = $commande->fk_delivery_address;
        $this->dol_object->contact_id = $commande->contactid;
        $this->dol_object->ref_client = $commande->ref_client;
        $this->dol_object->note_private = $private_note;
        $this->dol_object->note_public = $public_note;

        $this->dol_object->origin = $commande->element;
        $this->dol_object->origin_id = $commande->id;

        $this->dol_object->fk_account = (int) $id_account;

        // get extrafields from original line
//        $commande->fetch_optionals($commande->id);

        foreach ($commande->array_options as $options_key => $value)
            $this->dol_object->array_options[$options_key] = $value;

        // Possibility to add external linked objects with hooks
        $this->dol_object->linked_objects[$this->dol_object->origin] = $this->dol_object->origin_id;
        if (!empty($commande->other_linked_objects) && is_array($commande->other_linked_objects)) {
            $this->dol_object->linked_objects = array_merge($this->dol_object->linked_objects, $commande->other_linked_objects);
        }

        $ret = $this->dol_object->create($user);

        foreach ($commande->lines as $i => $line) {
            $marginInfos = getMarginInfos($line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_fournprice, $line->pa_ht);
            $tva_rate = ((float) $line->tva_tx / 100);

            $fk_product = $line->fk_product;
            $desc = $line->desc;
            $qty = $line->qty;
            $pu_ht = (float) $line->subprice;
            $txtva = $line->tva_tx;
            $remise_percent = (float) $line->remise_percent;
            $txlocaltax1 = $line->localtax1_tx;
            $txlocaltax2 = $line->localtax2_tx;
            $price_base_type = 'HT';
            $date_start = $line->date_start;
            $date_end = $line->date_end;
            $ventil = 0;
            $info_bits = $line->info_bits;
            $fk_remise_except = $line->fk_remise_except;
            $pu_ttc = (float) $line->subprice * (1 + $tva_rate);
            $type = Facture::TYPE_STANDARD;
            $rang = $line->rang;
            $special_code = 0;
            $origin = '';
            $origin_id = 0;
            $fk_parent_line = $line->fk_parent_line;
            $fk_fournprice = $line->fk_fournprice;
            $pa_ht = $marginInfos[0];
            $label = $line->label;
            $array_options = $line->array_options;
            $situation_percent = 100;
            $fk_prev_id = '';
            $fk_unit = $line->fk_unit;

            $this->dol_object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $situation_percent, $fk_prev_id, $fk_unit);
        }

        if ($ret > 0) {
            $this->fetch($this->dol_object->id);
            $this->checkLines();

            // Actions hooked (by external module)
            $hookmanager->initHooks(array('invoicedao'));

            $parameters = array('objFrom' => $commande);
            $action = '';
            $reshook = $hookmanager->executeHooks('createFrom', $parameters, $this->dol_object, $action);    // Note that $action and $commande may have been modified by some hooks
            if ($reshook < 0)
                $error++;

            if (!$error) {
                return 1;
            } else
                return -1;
        } else
            return -1;
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
            'fk_cond_reglement' => (int) $this->getData('fk_cond_reglement')
        ));

        $avoir->dol_object->fk_delivery_address = $this->dol_object->fk_delivery_address;

        // objets liés
        $avoir->dol_object->linked_objects = $this->dol_object->linked_objects;

        $errors = $avoir->create();


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
                    } else {
                        echo 'ici <br/>';
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
        $remain_to_pay = (float) $this->getRemainToPay();
        $type = (int) $this->getData('type');
        $paye = (int) $this->getData('paye');

        if (!in_array($type, array(Facture::TYPE_DEPOSIT, Facture::TYPE_CREDIT_NOTE, Facture::TYPE_STANDARD))) {
            $errors[] = 'Les ' . $this->getLabel('name_plur') . ' ne peuvent pas être converti' . $this->e() . 's en remise';
        } else {
            // On vérifie si une remise n'a pas déjà été créée: 
            $discountcheck = new DiscountAbsolute($db);
            $result = $discountcheck->fetch(0, $this->id);
            if (!empty($discountcheck->id)) {
                $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a déjà été converti' . $this->e() . ' en remise';
            } else {
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
            }
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

    public function checkIsPaid()
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') > 0) {
            $remain_to_pay = (float) $this->getRemainToPay();
            $paye = (int) $this->getData('paye');

            if ($remain_to_pay > -0.01 && $remain_to_pay < 0.01) {
                if (!$paye) {
                    $this->setObjectAction('classifyPaid');
                }
            } elseif ($paye) {
                $this->setObjectAction('reopen');
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

    public function checkSingleAmoutPaiement(&$amount)
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

        $remain_to_pay = round((float) $this->getRemainToPay(), 2);

        if (!in_array((int) $this->getData('fk_statut'), array(1, 2))) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas validé' . $this->e();
            return $errors;
        }

        if ((int) $this->getData('paye') || !$remain_to_pay) {
            $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' est entièrement payé' . $this->e();
            return $errors;
        }

        switch ((int) $this->getData('type')) {
            case Facture::TYPE_STANDARD:
            case Facture::TYPE_REPLACEMENT:
            case Facture::TYPE_DEPOSIT:
                if ($remain_to_pay < 0) {
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
                if ($remain_to_pay > 0) {
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

            // Check parameters
            // Check for mandatory prof id (but only if country is than than ours)
            if ($mysoc->country_id > 0 && $this->dol_object->thirdparty->country_id == $mysoc->country_id) {
                for ($i = 1; $i <= 6; $i++) {
                    $idprof_mandatory = 'SOCIETE_IDPROF' . ($i) . '_INVOICE_MANDATORY';
                    $idprof = 'idprof' . $i;
                    if (!$this->dol_object->thirdparty->$idprof && !empty($conf->global->$idprof_mandatory)) {
                        $errors[] = $langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId' . $i, $this->dol_object->thirdparty->country_code));
                    }
                }
            }

            $qualified_for_stock_change = 0;
            if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
                $qualified_for_stock_change = $this->dol_object->hasProductsOrServices(2);
            } else {
                $qualified_for_stock_change = $this->dol_object->hasProductsOrServices(1);
            }

            // Check for warehouse
            // todo: checker si les stocks doivent être corrigés ou non selon le type de la facture.
            if ((int) $this->getData('type') != Facture::TYPE_DEPOSIT && !empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change) {
                if (!$id_entrepot) {
                    $errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse"));
                }
            }

            if (!count($errors)) {
                $today = date('Y-m-d');
                if ($this->getData('datef') != $today) {
                    $errors = $this->updateField('datef', $today);
                }
            }

            if (!count($errors)) {
                $result = $this->dol_object->validate($user, '', $id_entrepot);
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
                    $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la validation');
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

    // Actions: 

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        global $user;

        if ($this->dol_object->set_unpaid($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_this'));
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

    public function actionClassifyPaid($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('the')) . ' ' . $this->getRef() . ' a bien été classé' . ($this->isLabelFemale() ? 'e' : '') . ' "payé' . ($this->isLabelFemale() ? 'e' : '') . '"';

        global $user;

        $type = (int) $this->getData('type');
        $remainToPay = (float) $this->getRemainToPay();
        if ($remainToPay < 0.01 && $remainToPay > -0.01) {
            $remainToPay = 0;
        }

        $discount = new DiscountAbsolute($this->db->db);
        $discount->fetch(0, $this->id);

        $close_code = (isset($data['close_code']) ? $data['close_code'] : '');
        $close_note = (isset($data['close_note']) ? $data['close_note'] : '');

        if ($this->isLoaded() && (int) $this->getData('fk_statut') == 1 && !(int) $this->dol_object->paye &&
                (($remainToPay > 0 && $close_code) ||
                (!in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT) && $remainToPay <= 0)) ||
                ($type === Facture::TYPE_CREDIT_NOTE && $remainToPay >= 0) ||
                ($type === Facture::TYPE_DEPOSIT && $this->dol_object->total_ttc > 0 && $remainToPay == 0 && empty($discount->id)))) {
            if ($this->dol_object->set_paid($user, $close_code, $close_note) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues');
            }
        } else {
            $errors[] = 'Il n\'est pas possible de classer ' . $this->getLabel('this') . ' comme "payé' . ($this->isLabelFemale() ? 'e' : '') . '"';
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

        global $conf;
        $remainToPay = (float) $this->getRemainToPay();

        if ($this->isLoaded() && (int) $this->getData('fk_statut') == 1 && !(int) $this->dol_object->paye && $remainToPay > 0 && empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED) && !$this->dol_object->getIdReplacingInvoice()) {
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
        } else {
            $errors[] = 'Il n\'est pas possible d\'abandonner ' . $this->getLabel('this');
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

    // Overrides BimpObject:

    public function validate()
    {
        $errors = parent::validate();

        if (!count($errors)) {
            
        }

        return $errors;
    }

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['datec'] = date('Y-m-d H:i:s');
        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_valid'] = 0;
        $new_data['id_user_commission'] = 0;
        $new_data['id_entrepot_commission'] = 0;

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

                        $facture->dol_object->fk_facture_source = (int) $id_facture_replaced;
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

                $this->dol_object->fk_facture_source = (int) BimpTools::getPostFieldValue('id_facture_to_correct', 0);
                $avoir_same_lines = (int) BimpTools::getPostFieldValue('avoir_same_lines', 0);
                $avoir_remain_to_pay = (int) BimpTools::getPostFieldValue('avoir_remain_to_pay', 0);

                if ($avoir_same_lines && $avoir_remain_to_pay) {
                    $errors[] = 'Il n\'est pas possible de choisir l\'option "Créer l\'avoir avec les même lignes que la factures dont il est issu" et "Créer l\'avoir avec le montant restant à payer de la facture dont il est issu" en même temps. Veuillez choisir l\'une ou l\'autre';
                }

                if (!$this->dol_object->fk_facture_source && (empty($conf->global->INVOICE_CREDIT_NOTE_STANDALONE) || $avoir_same_lines || $avoir_remain_to_pay)) {
                    $errors[] = 'Facture à corriger absente';
                } elseif ($this->dol_object->fk_facture_source) {
                    $facture = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $this->dol_object->fk_facture_source);
                    if (!$facture->isLoaded()) {
                        $errors[] = 'Facture à corriger invalide';
                    }
                }

                if (!count($errors)) {
                    $errors = parent::create($warnings, true);
                }

                if (count($errors)) {
                    return $errors;
                }

                if ($avoir_same_lines) {
                    $line_errors = $this->createLinesFromOrigin($facture, true);
                    if (count($line_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($line_errors);
                    }
                } elseif ($avoir_remain_to_pay) {
                    $this->dol_object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'), (float) $facture->getRemainToPay() * -1, 1, 0, 0, 0, 0, 0, '', '', 'TTC');
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
                    }
                }
            case Facture::TYPE_DEPOSIT:
            case Facture::TYPE_PROFORMA:
                $errors = parent::create($warnings, true);

                if (!count($errors)) {
                    if (BimpObject::objectLoaded($avoir_to_refacture)) {
                        $lines_errors = $this->createLinesFromOrigin($avoir_to_refacture, true);
                        if (count($lines_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($lines_errors);
                        }
                    }
                }
                break;

            default:
                $errors[] = 'Type de facture invalide';
                break;
        }

        if (!count($errors)) {
            $this->fetch($this->id);
        }

        return $errors;
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $init_fk_account = (int) $this->getInitData('fk_account');
        $fk_account = (int) $this->getData('fk_account');

        $id_cond_reglement = (int) $this->getData('fk_cond_reglement');

        if ($id_cond_reglement !== (int) $this->getInitData('fk_cond_reglement')) {
            $this->set('date_lim_reglement', BimpTools::getDateFromDolDate($this->dol_object->calculate_date_lim_reglement($id_cond_reglement)));
        }

        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            if ($fk_account !== $init_fk_account) {
                $this->updateField('fk_account', $fk_account);
            }
        }
    }
}
