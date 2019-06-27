<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';

global $langs;
$langs->load('bills');
$langs->load('errors');

class Bimp_Facture extends BimpComm
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $dol_module = 'facture';
    public static $email_type = 'facture_send';
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
                if ($this->can("create")) {
                    return 1;
                }
                return 0;

            case 'reopen':
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->creer) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->invoice_advance->reopen)) {
                    return 1;
                }
                return 0;

            case 'convertToReduc':
            case 'addContact':
            case 'cancel':
                return $this->can("create");

            case 'classifyPaid':
                if ($user->rights->facture->paiement) {
                    return 1;
                }
                return 0;
//
//            case 'close':
//            case 'reopen':
//                if (!empty($user->rights->propal->cloturer)) {
//                    return 1;
//                }
//                return 0;
//
            case 'sendMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
                    return 1;
                }
                return 0;
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
        $rows = (int) $this->db->getRows('br_commande_shipment', '`id_facture` = ' . (int) $this->id, null, 'object', array('id', 'id_commande_client'));
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
        if (in_array($action, array('validate', 'modify', 'reopen', 'sendMail'))) {
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

                if (!count($this->dol_object->lines)) {
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
                if ($status !== Facture::STATUS_VALIDATED) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' n\'est pas au statut "Validé"';
                    return 0;
                }
                // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees:
                if ((float) $this->getRemainToPay() != $this->dol_object->total_ttc && empty($this->dol_object->paye)) {
                    $errors[] = 'Un paiement a été effectué';
                    return 0;
                }

                if ($this->dol_object->getVentilExportCompta() == 0) {
                    $errors[] = BimpTools::ucfirst($this->getLabel('this')) . ' a été exporté' . ($this->isLabelFemale() ? 'e' : '') . ' en compta';
                    return 0;
                }

                if (!$this->dol_object->is_last_in_cycle()) {
                    $errors[] = $langs->trans("NotLastInCycle");
                    return 0;
                }

                if ($this->dol_object->getIdReplacingInvoice()) {
                    $errors[] = $langs->trans("DisabledBecauseReplacedInvoice");
                    return 0;
                }

                return 1;

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
        }

        return (int) parent::isActionAllowed($action, $errors);
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
        $total_paid = (float) $this->dol_object->getSommePaiement();
        $total_credit_notes = (float) $this->dol_object->getSumCreditNotesUsed();
        $id_replacing_invoice = $this->dol_object->getIdReplacingInvoice();

        $soc = $this->getChildObject('client');
        $discount = new DiscountAbsolute($this->db->db);
        $discount->fetch(0, $this->id);

        // Valider:
        $error_msg = '';
        $errors = array();

        if ($this->isActionAllowed('validate', $errors)) {
            if ($this->canSetAction('validate')) {
                if (substr($ref, 1, 4) == 'PROV') {
                    $numref = $this->dol_object->getNextNumRef($soc->dol_object);

                    if (!empty($conf->global->FAC_FORCE_DATE_VALIDATION)) {
                        $this->dol_object->date = dol_now();
                        $this->dol_object->date_lim_reglement = $this->dol_object->calculate_date_lim_reglement();
                        $this->set('datef', BimpTools::getDateFromDolDate($this->dol_object->date));
                    }
                } else {
                    $numref = $ref;
                }

                $text = $langs->trans('ConfirmValidateBill', $numref);
                if (!empty($conf->notification->enabled)) {
                    if (!class_exists('Notify')) {
                        require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
                    }
                    $notify = new Notify($this->db->db);
                    $text .= "\n";
                    $text .= $notify->confirmMessage('BILL_VALIDATE', (int) $this->getData('fk_soc'), $this->dol_object);
                }
                $buttons[] = array(
                    'label'   => 'Valider',
                    'icon'    => 'check',
                    'onclick' => $this->getJsActionOnclick('validate', array('new_ref' => $numref), array(
                        'confirm_msg' => strip_tags($text)
                    ))
                );
            } else {
                $error_msg = 'Vous n\'avez pas la permission de valider cette facture';
            }
        } else {
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
                $errors = array();

                if ($this->isActionAllowed('modify', $errors)) {
                    $buttons[] = array(
                        'label'   => 'Modifier',
                        'icon'    => 'undo',
                        'onclick' => $this->getJsActionOnclick('modify', array(), array(
                            'confirm_msg' => strip_tags($langs->trans('ConfirmUnvalidateBill', $ref))
                    )));
                } else {
                    $msg = BimpTools::getMsgFromArray($errors);
                    $buttons[] = array(
                        'label'    => 'Modifier',
                        'icon'     => 'undo',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => $msg
                    );
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
            if ($type !== Facture::TYPE_CREDIT_NOTE && $status === 1 && !$paye && $user->rights->facture->paiement) {
                $paiement = BimpObject::getInstance('bimpcommercial', 'Bimp_Paiement');
                $onclick = $paiement->getJsLoadModalForm('default', 'Paiement Factures', array(
                    'fields' => array(
                        'id_client'        => (int) $this->getData('fk_soc'),
                        'id_mode_paiement' => (int) $this->getData('fk_mode_reglement')
                    )
                ));

                $buttons[] = array(
                    'label'   => 'Saisir réglement',
                    'icon'    => 'euro',
                    'onclick' => $onclick
                );
            }

            // Remboursements ou conversions en réduction
            if (in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD))) {
                // Remboursement avoirs: 
                if ($type === Facture::TYPE_CREDIT_NOTE && $status === 1 && !$paye && $user->rights->facture->paiement && $remainToPay != 0) {
                    $url = DOL_URL_ROOT . '/compta/paiement.php?facid=' . $this->id . '&action=create&accountid=' . $this->dol_object->fk_account;
                    $buttons[] = array(
                        'label'   => $langs->trans('DoPaymentBack'),
                        'icon'    => 'euro',
                        'onclick' => 'window.location = \'' . $url . '\''
                    );
                }


                if ($this->can("create") && $status == 1 && !$paye) {
                    switch ($type) {
                        case Facture::TYPE_STANDARD:
                            if ($status > 0 && $remainToPay < 0 && empty($discount->id)) {
                                // Remboursement excédant payé: 
                                $buttons[] = array(
                                    'label'   => $langs->trans('ConvertExcessReceivedToReduc'),
                                    'icon'    => 'percent',
                                    'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                        'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('ExcessReceived'))))
                                    ))
                                );
                            }
                            break;

                        case Facture::TYPE_CREDIT_NOTE:
                            if ($this->dol_object->getSommePaiement() == 0) {
                                $buttons[] = array(
                                    'label'   => $langs->trans('ConvertToReduc'),
                                    'icon'    => 'percent',
                                    'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                        'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('CreditNote'))))
                                    ))
                                );
                            }
                            break;

                        case Facture::TYPE_DEPOSIT:
                            if ($remainToPay == 0 && empty($discount->id)) {
                                $buttons[] = array(
                                    'label'   => $langs->trans('ConvertToReduc'),
                                    'icon'    => 'percent',
                                    'onclick' => $this->getJsActionOnclick('convertToReduc', array(), array(
                                        'confirm_msg' => strip_tags($langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities('Deposit'))))
                                    ))
                                );
                            }
                            break;
                    }
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
                    if ($this->canSetAction('classifyPaid') && $total_paid > 0 || $total_credit_notes > 0) {
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


            if (in_array($type, array(Facture::TYPE_DEPOSIT, Facture::TYPE_STANDARD, Facture::TYPE_PROFORMA)) && $this->can("create")) {
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

    // Getters données: 

    public function getRemainToPay()
    {
        if ($this->isLoaded()) {
            if ($this->dol_object->paye) {
                return 0;
            }

            $paid = (float) $this->dol_object->getSommePaiement();
            $paid += (float) $this->dol_object->getSumCreditNotesUsed();
            $paid += (float) $this->dol_object->getSumDepositsUsed();

            return (float) $this->dol_object->total_ttc - $paid;
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

    // Affichages: 

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            $paid = (float) $this->dol_object->getSommePaiement();
            $paid += (float) $this->dol_object->getSumCreditNotesUsed();
            $paid += (float) $this->dol_object->getSumDepositsUsed();
            return BimpTools::displayMoneyValue($paid, 'EUR');
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

    public function displayRemisesClient()
    {
        $html = '';

        if ($this->isLoaded()) {
            $soc = $this->getChildObject('client');
            $type = (int) $this->getData('type');
            $status = (int) $this->getData('fk_statut');

            if (BimpObject::objectLoaded($soc)) {
                global $langs, $conf;

                $soc = $soc->dol_object;

                if ($soc->remise_percent) {
                    $html .= $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
                } else {
                    $html .= $langs->trans("CompanyHasNoRelativeDiscount");
                }

                if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) { // Never use this
                    $filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                    $filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
                } else {
                    $filterabsolutediscount = "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND ((description LIKE '(DEPOSIT)%' OR description LIKE 'Acompte%') AND description NOT LIKE '(EXCESS RECEIVED)%'))";
                    $filtercreditnote = "fk_facture_source IS NOT NULL AND ((description NOT LIKE '(DEPOSIT)%' AND description NOT LIKE 'Acompte%') OR description LIKE '(EXCESS RECEIVED)%')";
                }

                $absolute_discount = price2num($soc->getAvailableDiscounts('', $filterabsolutediscount), 'MT');
                $absolute_creditnote = price2num($soc->getAvailableDiscounts('', $filtercreditnote), 'MT');

                $can_use_discount = false;
                $can_use_credit_note = false;
                $can_add_credit_note = false;


                if ($absolute_discount > 0) {
                    $can_view_discounts = true;
                    $html .= '<br/>';
                    if ($status > 0 || in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                        $html .= '<i class="' . BimpRender::renderIconClass('question-circle') . ' iconLeft objectIcon bs-popover" ';
                        $html .= BimpRender::renderPopoverData($langs->trans("AbsoluteDiscountUse"), 'top', 'true') . ' style="margin-left: 0"></i>';
                        $html .= $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                    } else {
                        $html .= $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                        $can_use_discount = true;
                    }
                }

                if ($absolute_creditnote > 0) {
                    $can_view_discounts = true;
                    $html .= '<br/>';
                    if ($status !== Facture::STATUS_VALIDATED || $type === Facture::TYPE_CREDIT_NOTE) {
                        $html .= '<i class="' . BimpRender::renderIconClass('question-circle') . ' iconLeft objectIcon bs-popover" ';
                        $html .= BimpRender::renderPopoverData($langs->trans("CreditNoteDepositUse"), 'top', 'true') . ' style="margin-left: 0"></i>';
                        $html .= $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
                    } else {
                        $html .= $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
                        $can_use_credit_note = true;
                        $can_add_credit_note = true;
                    }
                }

                if (!$absolute_discount && !$absolute_creditnote) {
                    $html .= '<br/>';
                    $html .= $langs->trans("CompanyHasNoAbsoluteDiscount");

                    if ($status === Facture::STATUS_DRAFT && !in_array($type, array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                        $can_add_credit_note = true;
                    }
                }

                if ($can_use_discount || $can_use_credit_note || $can_view_discounts || $can_add_credit_note) {
                    $back_url = urlencode(DOL_URL_ROOT . '/bimpcommercial/index.php?fc=facture&id=' . $this->id);

                    $html .= '<div class="buttonsContainer align-right">';
                    if ($can_use_discount || $can_use_credit_note) {
                        $onclick = $this->getJsActionOnclick('useRemise', array(
                            'discounts'    => ($can_use_discount ? 1 : 0),
                            'credit_notes' => ($can_use_credit_note ? 1 : 0)
                                ), array(
                            'form_name' => 'use_remise'
                        ));
                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= '<i class="' . BimpRender::renderIconClass('fas_file-import') . ' iconLeft"></i>Appliquer une remise disponible';
                        $html .= '</button>';
                    }

                    if ($can_view_discounts) {
                        $onclick = 'window.location = \'' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . $back_url . '\';';
                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= '<i class="' . BimpRender::renderIconClass('percent') . ' iconLeft"></i>Remises client';
                        $html .= '</button>';
                    }

                    if ($can_add_credit_note) {
                        $values = array(
                            'fields' => array(
                                'fk_soc'     => (int) $this->getData('fk_soc'),
                                'ref_client' => $this->getData('ref_client'),
                                'type'       => Facture::TYPE_CREDIT_NOTE,
                                'fk_account' => (int) $this->getData('fk_account'),
                                'entrepot'   => (int) $this->getData('entrepot'),
                                'centre'     => $this->getData('centre'),
                                'ef_type'    => $this->getData('ef_type')
                            )
                        );
                        $data = '{module: \'bimpcommercial\', object_name: \'Bimp_Facture\', ';
                        $data .= 'param_values: ' . htmlentities(json_encode($values)) . '}';
                        $onclick = 'loadModalForm($(this), ' . $data . ', \'' . $langs->trans("CreateCreditNote") . '\');';
                        $html .= '<button class="btn btn-default" onclick="' . $onclick . '">';
                        $html .= '<i class="' . BimpRender::renderIconClass('plus-circle') . ' iconLeft"></i>Créer un avoir';
                        $html .= '</button>';
                    }

                    $html .= '</div>';
                }
            }
        }

        return $html;
    }

    //Rendus HTML: 

    public function renderContentExtraLeft()
    {
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

            global $langs, $conf;

            $total_paid = (float) $this->dol_object->getSommePaiement();

            if ($type !== Facture::TYPE_CREDIT_NOTE) {
                $total_credit_note = 0;
                $total_deposit = 0;

                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>Déjà réglé</strong>';
                if ((int) $this->getData('type') !== Facture::TYPE_DEPOSIT) {
                    $html .= '<br/>(Hors avoirs et acomptes)';
                }
                $html .= ' : </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR') . '</td>';
                $html .= '<td>';
                $html .= '</td>';
                $html .= '</tr>';

                // Avoirs utilisés: 
                $rows = $this->db->getRows('societe_remise_except', '`fk_facture` = ' . (int) $this->id, null, 'array');
                if (!is_null($rows) && count($rows)) {
                    foreach ($rows as $r) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['fk_facture_source']);
                        if ($facture->isLoaded()) {
                            $html .= '<tr>';
                            $html .= '<td style="text-align: right;">';
                            if ((int) $facture->getData('type') === Facture::TYPE_CREDIT_NOTE) {
                                $html .= 'Avoir ';
                                $total_credit_note += (float) $r['amount_ttc'];
                            } elseif ((int) $facture->getData('type') === Facture::TYPE_DEPOSIT) {
                                $html .= 'Acompte ';
                                $total_deposit += (float) $r['amount_ttc'];
                            }
                            $html .= $facture->dol_object->getNomUrl(0);
                            $html .= ' : </td>';

                            $html .= '<td>' . BimpTools::displayMoneyValue((float) $r['amount_ttc'], 'EUR') . '</td>';
                            $html .= '<td class="buttons">';
                            $onclick = $this->getJsActionOnclick('removeDiscount', array('id_discount' => (int) $r['rowid']));
                            $html .= BimpRender::renderRowButton('Retirer', 'trash', $onclick);
                            $html .= '</td>';

                            $html .= '</tr>';
                        }
                    }
                }

                $remainToPay = (float) $this->getData('total_ttc') - $total_paid - $total_credit_note - $total_deposit;
                $remainToPay_final = $remainToPay;

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
                BimpTools::loadDolClass('core', 'discount');
                $discount = new DiscountAbsolute($this->db->db);
                $discount->fetch(0, $this->id);
                if (BimpObject::objectLoaded($discount)) {
                    $remainToPay_final = 0;
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right;">';
                    $html .= '<strong>Trop perçu converti en </strong>' .$discount->getNomUrl(1, 'discount');
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($discount->amount_ttc);
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>Facturé</strong> : </td>';
                $html .= '<td>' . $this->displayData('total_ttc') . '</td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                // Reste à payer: 
                if ((int) $this->getData('paye')) {
                    $remainToPay_final = 0;
                    $class = 'success';
                } else {
                    $class = ($remainToPay > 0 ? 'danger' : 'success');
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
            } else {
                // Avoirs: 
                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>' . $langs->trans('AlreadyPaidBack') . '</strong> : </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue(-$total_paid, 'EUR') . '</td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>' . $langs->trans("Billed") . '</strong> : </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($this->getData('total_ttc') * -1) . '</td>';
                $html .= '<td></td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>';

                $remainToPay = (float) $this->getRemainToPay();

                $mult = -1;
                if ($remainToPay < 0) {
                    $html .= $langs->trans('RemainderToPayBack');
                    $class = 'danger';
                } elseif ($remainToPay > 0) {
                    $html .= 'Trop remboursé';
                    $class = 'warning';
                    $mult = 1;
                } else {
                    $html .= $langs->trans('RemainderToPayBack');
                    $class = 'success';
                }
                $html .= '</strong> : </td>';
                $html .= '<td><span class="' . $class . '">' . BimpTools::displayMoneyValue($remainToPay * $mult) . '</span></td>';
                $html .= '<td></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';

            $html = BimpRender::renderPanel('Total Paiements', $html, '', array(
                        'icon' => 'euro',
                        'type' => 'secondary'
            ));
        }

        return $html;
    }

    public function renderContentExtraRight()
    {
        $return = '';

        if ($this->isLoaded()) {
            $type = (int) $this->getData('type');
            $mult = 1;
            $title = 'Paiements effectués';


            $rows = $this->db->getRows('paiement_facture', '`fk_facture` = ' . (int) $this->id, null, 'array');

            $html = '<table class="bimp_list_table">';

            $html .= '<thead>';
            if ($type === Facture::TYPE_CREDIT_NOTE) {
                $html .= '<th>Remboursement</th>';
                $mult = -1;
                $title = 'Remboursements effectués';
            } else {
                $html .= '<th>Paiement</th>';
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
                        $html .= '<td>' . BimpTools::displayMoneyValue((float) $paiement->getData('amount') * $mult, 'EUR') . '</td>';
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
            if (in_array($type, array(Facture::TYPE_STANDARD, Facture::TYPE_REPLACEMENT, Facture::TYPE_DEPOSIT)) && $user->rights->facture->paiement) {
                $object_data = '{module: \'bimpcommercial\', object_name: \'Bimp_Paiement\', id_object: 0, ';
                $object_data .= 'param_values: {fields: {id_client: ' . (int) $this->getData('fk_soc') . ', id_mode_paiement: ' . (int) $this->getData('fk_mode_reglement') . '}}}';
                $buttons[] = array(
                    'label'       => 'Saisir réglement',
                    'icon_before' => 'plus-circle',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => 'loadModalForm($(this), ' . $object_data . ', \'Paiement Factures\')'
                    )
                );
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

    // Traitements: 

    public function onValidate()
    {
        if ($this->isLoaded()) {
            $this->set('fk_statut', Facture::STATUS_VALIDATED);
            $this->majStatusOtherPiece();

            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                $line->onFactureValidate();
            }
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

    public function convertToRemise()
    {
        if (!$this->isLoaded()) {
            return array('ID de la facture absent');
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

        // Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
        $discountcheck = new DiscountAbsolute($db);
        $result = $discountcheck->fetch(0, $this->dol_object->id);

        $canconvert = 0;
        if ($this->dol_object->type == Facture::TYPE_DEPOSIT && $this->dol_object->paye == 1 && empty($discountcheck->id))
            $canconvert = 1; // we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
        if (($this->dol_object->type == Facture::TYPE_CREDIT_NOTE || $this->dol_object->type == Facture::TYPE_STANDARD) && $this->dol_object->paye == 0 && empty($discountcheck->id))
            $canconvert = 1; // we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
        if ($canconvert) {
            $db->begin();

            $amount_ht = $amount_tva = $amount_ttc = array();

            // Loop on each vat rate
            $i = 0;
            foreach ($this->dol_object->lines as $line) {
                if ($line->total_ht != 0) {  // no need to create discount if amount is null
                    $amount_ht[$line->tva_tx] += $line->total_ht;
                    $amount_tva[$line->tva_tx] += $line->total_tva;
                    $amount_ttc[$line->tva_tx] += $line->total_ttc;
                    $i ++;
                }
            }

            // Insert one discount by VAT rate category
            $discount = new DiscountAbsolute($db);
            if ($this->dol_object->type == Facture::TYPE_CREDIT_NOTE)
                $discount->description = '(CREDIT_NOTE)';
            elseif ($this->dol_object->type == Facture::TYPE_DEPOSIT)
                $discount->description = '(DEPOSIT)';
            elseif ($this->dol_object->type == Facture::TYPE_STANDARD || $this->dol_object->type == Facture::TYPE_REPLACEMENT || $this->dol_object->type == Facture::TYPE_SITUATION)
                $discount->description = '(EXCESS RECEIVED)';
            else {
                return array($langs->trans('CantConvertToReducAnInvoiceOfThisType'));
            }

            $discount->fk_soc = $this->dol_object->socid;
            $discount->fk_facture_source = $this->dol_object->id;

            if ($this->dol_object->type == Facture::TYPE_STANDARD || $this->dol_object->type == Facture::TYPE_REPLACEMENT || $this->dol_object->type == Facture::TYPE_SITUATION) {
                // If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT
                $sql = 'SELECT SUM(pf.amount) as total_paiements
						FROM llx_c_paiement as c, llx_paiement_facture as pf, llx_paiement as p
						WHERE pf.fk_facture = ' . $this->dol_object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';

                $resql = $db->query($sql);
                if (!$resql) {
                    return array($db->lasterror());
                }

                $res = $db->fetch_object($resql);
                $total_paiements = $res->total_paiements;

                $discount->amount_ht = $discount->amount_ttc = $total_paiements - $this->dol_object->total_ttc;
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
            if ($this->dol_object->type == Facture::TYPE_CREDIT_NOTE || $this->dol_object->type == Facture::TYPE_DEPOSIT) {
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
        }

        return $errors;
    }

    public function checkIsPaid()
    {
        if ($this->isLoaded() && (int) $this->getData('fk_statut') === 1) {
            $remain_to_pay = (float) $this->getRemainToPay();
            if ($remain_to_pay > -0.01 && $remain_to_pay < 0.01) {
                $this->setObjectAction('classifyPaid');
            }
        }
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
                $modal_html = BimpRender::renderAlerts($lines_errors, 'warning');
                $onclick = $this->getJsActionOnclick('validate', array(
                    'force_validate' => 1
                ));
                $modal_html .= '<div style="text-align: center">';
                $modal_html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
                $modal_html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Forcer la validation';
                $modal_html .= '</span>';
                $modal_html .= '</div>';
            }
        }

        if (!$modal_html) {
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

    public function actionUseRemise($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Remise insérée avec succès';
        global $langs;

        if (!isset($data['id_discount']) || !(int) $data['id_discount']) {
            $errors[] = 'Aucune remise ou avoir sélectionné';
        } else {
            if (!class_exists('DiscountAbsolute')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
            }
            $discount = new DiscountAbsolute($this->db->db);
            $discount->fetch((int) $data['id_discount']);

            if (!BimpObject::objectLoaded($discount)) {
                $errors[] = 'La remise d\'ID ' . $data['id_discount'] . ' n\'existe pas';
            } else {
                $error_label = 'Insertion de la remise impossible';
                if (preg_match('/^(DEPOSIT).*$/', $discount->description) || preg_match('/^Acompte.*$/', $discount->description)) {
                    if (in_array((int) $this->getData('type'), array(Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                        $errors[] = $error_label . ' - ' . 'Cette facture n\'est pas de type "standard"';
                    } elseif ((int) $this->getData('fk_statut') > 0) {
                        $errors[] = $error_label . ' - ' . $this->getData('the') . ' doit avoit le statut "Brouillon';
                    } else {
                        if ($this->dol_object->insert_discount($discount->id) <= 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de l\'insertion de la remise');
                        }
                    }
                } else {
                    if ($discount->amount_ttc > (float) $this->getRemainToPay()) {
                        $errors[] = $langs->trans("ErrorDiscountLargerThanRemainToPaySplitItBefore");
                    } elseif ($discount->link_to_invoice(0, $this->id) <= 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec de l\'application de la remise');
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

        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } else if (!$this->isReopenable()) {
            $errors[] = 'Il n\'est pas possible de réouvrir ' . $this->getLabel('this');
        }

        if (!count($errors)) {
            global $user;

            if ($this->dol_object->set_unpaid($user) <= 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Echec de la réouverture ' . $this->getLabel('of_this'));
            }
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

        global $langs, $user;
        $this->dol_object->fetch_thirdparty();


        // Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
        $discountcheck = new DiscountAbsolute($this->db->db);
        $result = $discountcheck->fetch(0, $this->id);

        $canconvert = 0;

        $type = (int) $this->getData('type');
        $paye = (int) $this->dol_object->paye;

        if ($type == Facture::TYPE_DEPOSIT && $paye == 1 && empty($discountcheck->id))
            $canconvert = 1; // we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
        if (($type == Facture::TYPE_CREDIT_NOTE || $type == Facture::TYPE_STANDARD) && $paye == 0 && empty($discountcheck->id))
            $canconvert = 1; // we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
        if ($canconvert) {

            $amount_ht = $amount_tva = $amount_ttc = array();

            // Loop on each vat rate
            $i = 0;
            foreach ($this->dol_object->lines as $line) {
                if ($line->total_ht != 0) {  // no need to create discount if amount is null
                    $amount_ht[$line->tva_tx] += $line->total_ht;
                    $amount_tva[$line->tva_tx] += $line->total_tva;
                    $amount_ttc[$line->tva_tx] += $line->total_ttc;
                    $i ++;
                }
            }

            // Insert one discount by VAT rate category
            $discount = new DiscountAbsolute($this->db->db);
            if ($type == Facture::TYPE_CREDIT_NOTE)
                $discount->description = '(CREDIT_NOTE)';
            elseif ($type == Facture::TYPE_DEPOSIT)
                $discount->description = '(DEPOSIT)';
            elseif ($type == Facture::TYPE_STANDARD || $type == Facture::TYPE_REPLACEMENT || $type == Facture::TYPE_SITUATION)
                $discount->description = '(EXCESS RECEIVED)';
            else {
                $errors[] = $langs->trans('CantConvertToReducAnInvoiceOfThisType');
            }

            if (!count($errors)) {
                $discount->fk_soc = $this->dol_object->socid;
                $discount->fk_facture_source = $this->dol_object->id;

                if ($type == Facture::TYPE_STANDARD || $type == Facture::TYPE_REPLACEMENT || $type == Facture::TYPE_SITUATION) {
                    // If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT
                    $sql = 'SELECT SUM(pf.amount) as total_paiements
						FROM llx_c_paiement as c, llx_paiement_facture as pf, llx_paiement as p
						WHERE pf.fk_facture = ' . $this->dol_object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';

                    $db = $this->db->db;

                    $resql = $db->query($sql);
                    if (!$resql)
                        dol_print_error($db);

                    $res = $db->fetch_object($resql);
                    $total_paiements = $res->total_paiements;

                    $discount->amount_ht = $discount->amount_ttc = $total_paiements - $this->dol_object->total_ttc;
                    $discount->amount_tva = 0;
                    $discount->tva_tx = 0;

                    $result = $discount->create($user);
                    if ($result < 0) {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec de la création de la remise');
                    }
                } elseif ($type == Facture::TYPE_CREDIT_NOTE || $type == Facture::TYPE_DEPOSIT) {
                    foreach ($amount_ht as $tva_tx => $xxx) {
                        $discount->error = '';
                        $discount->errors = array();

                        $discount->amount_ht = abs($amount_ht[$tva_tx]);
                        $discount->amount_tva = abs($amount_tva[$tva_tx]);
                        $discount->amount_ttc = abs($amount_ttc[$tva_tx]);
                        $discount->tva_tx = abs($tva_tx);

                        $result = $discount->create($user);
                        if ($result < 0) {
                            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'Echec de la création d\'une remise');
                            break;
                        }
                    }
                }
                if (!count($errors)) {
                    // Classe facture
                    $result = $this->dol_object->set_paid($user);
                    if ($result >= 0) {
                        $db->commit();
                    } else {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object));
                        $db->rollback();
                    }
                } else {
                    $db->rollback();
                }
            }
        }

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

    // Overrides BimpObject:

    public function duplicate($new_data = array(), &$warnings = array(), $force_create = false)
    {
        $new_data['datec'] = date('Y-m-d H:i:s');
        $new_data['fk_user_author'] = 0;
        $new_data['fk_user_valid'] = 0;

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

                $this->dol_object->fk_facture_source = (int) BimpTools::getValue('id_facture_to_correct', 0);
                $avoir_same_lines = (int) BimpTools::getValue('avoir_same_lines', 0);
                $avoir_remain_to_pay = (int) BimpTools::getValue('avoir_remain_to_pay', 0);

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
                    $fk_parent_line = 0;

                    foreach ($facture->dol_object->lines as $line) {
                        // Reset fk_parent_line for no child products and special product
                        if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
                            $fk_parent_line = 0;
                        }

                        $line->fk_facture = $this->id;
                        $line->fk_parent_line = $fk_parent_line;

                        $line->subprice = -$line->subprice; // invert price for object
                        $line->pa_ht = $line->pa_ht;       // we choosed to have buy/cost price always positive, so no revert of sign here
                        $line->total_ht = -$line->total_ht;
                        $line->total_tva = -$line->total_tva;
                        $line->total_ttc = -$line->total_ttc;
                        $line->total_localtax1 = -$line->total_localtax1;
                        $line->total_localtax2 = -$line->total_localtax2;

                        $line->multicurrency_subprice = -$line->multicurrency_subprice;
                        $line->multicurrency_total_ht = -$line->multicurrency_total_ht;
                        $line->multicurrency_total_tva = -$line->multicurrency_total_tva;
                        $line->multicurrency_total_ttc = -$line->multicurrency_total_ttc;

                        $result = $line->insert(0, 1);     // When creating credit note with same lines than source, we must ignore error if discount alreayd linked

                        $this->dol_object->lines[] = $line; // insert new line in current object
                        // Defined the new fk_parent_line
                        if ($result > 0 && $line->product_type == 9) {
                            $fk_parent_line = $result;
                        }
                    }

                    $this->dol_object->update_price(1);
                } elseif ($avoir_remain_to_pay) {
                    $this->dol_object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'), (float) $facture->getRemainToPay(), 1, 0, 0, 0, 0, 0, '', '', 'TTC');
                }

                break;

            case Facture::TYPE_STANDARD:
            case Facture::TYPE_DEPOSIT:
            case Facture::TYPE_PROFORMA:
                $errors = parent::create($warnings, true);
                break;

            default:
                $errors[] = 'Type de facture invalide';
                break;
        }

        return $errors;
    }
}
