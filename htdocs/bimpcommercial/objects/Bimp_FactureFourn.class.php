<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';

class Bimp_FactureFourn extends BimpComm
{

    public $redirectMode = 4; //5;//1 btn dans les deux cas   2// btn old vers new   3//btn new vers old   //4 auto old vers new //5 auto new vers old
    public static $dol_module = 'facture_fourn';
    public static $files_module_part = 'facture_fournisseur';
    public static $email_type = 'invoice_supplier_send'; // A checker
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
        3 => array('label' => 'Facture d\'acompte')
    );

    // Gestion des autorisations objet: 

    public function isDeletable($force_delete = false)
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('fk_statut') === 0) {
                return 1;
            }

            return 0;
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        return parent::isFieldEditable($field, $force_edit);
    }

    public function isActionAllowed($action, &$errors = array())
    {
        $errors = array();
        $status = $this->getData('fk_statut');

        if (in_array($action, array('validate', 'modify', 'reopen', 'sendEMail', 'makePayment', 'classifyPaid', 'duplicate', 'create_credit_note'))) {
            if (!$this->isLoaded()) {
                $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
                return 0;
            }
            if (is_null($status)) {
                $errors[] = 'Statut absent';
                return 0;
            }
        }

        $status = (int) $status;
        $type = (int) $this->getData('type');

        global $conf;

        $objLabel = BimpTools::ucfirst($this->getLabel('this'));
        switch ($action) {
            case 'validate':
                if ($status !== FactureFournisseur::STATUS_DRAFT) {
                    $errors[] = 'Statut actuel invalide';
                }
                if (!count($this->dol_object->lines)) {
                    $errors[] = 'Aucune ligne enregistrée pour ' . $this->getLabel('this');
                }
                break;

            case 'modify':
                if ($status !== FactureFournisseur::STATUS_VALIDATED) {
                    $errors[] = 'Statut actuel invalide';
                }
                if ($this->dol_object->getSommePaiement() != 0) {
                    $errors[] = 'Un paiement a déjà été effectué';
                }
                break;

            case 'reopen':
                if (!in_array($type, array(FactureFournisseur::TYPE_STANDARD, FactureFournisseur::TYPE_REPLACEMENT))) {
                    $errors[] = 'Type invalide';
                }
                if (!in_array($status, array(2, 3))) {
                    $errors[] = 'Statut actuel invalide';
                }
                if ((int) $this->dol_object->getIdReplacingInvoice() || $this->getData('close_code') === 'replaced') {
                    $errors[] = $objLabel . ' a été remplacé' . $this->e() . ' par un' . ($this->isLabelFemale() ? 'e' : '') . ' autre';
                }

            case 'sendEMail':
                if (!in_array($status, array(FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_CLOSED))) {
                    $errors[] = 'Statut actuel invalide';
                }
                break;

            case 'makePayment':
                if ($type === FactureFournisseur::TYPE_CREDIT_NOTE) {
                    $errors[] = 'Paiements non autorisés pour les avoirs';
                }
                if ($status !== FactureFournisseur::STATUS_VALIDATED) {
                    $errors[] = $objLabel . ' doit être au statut "Validé' . $this->e() . '"';
                }
                if ((int) $this->getData('paye')) {
                    $errors[] = $objLabel . ' est déjà payé' . $this->e();
                }
                break;


            case 'classifyPaid':
                if ($status !== FactureFournisseur::STATUS_VALIDATED) {
                    $errors[] = $objLabel . ' doit être au statut "Validé' . $this->e() . '"';
                }
                if ((int) $this->getData('paye')) {
                    $errors[] = $objLabel . ' est déjà payé' . $this->e();
                }
                break;

            case 'convertToReduc':
                BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');
                $discountcheck = new DiscountAbsolute($this->db->db);
                $discountcheck->fetch(0, 0, $this->id);

                if (BimpObject::objectLoaded($discountcheck)) {
                    $errors[] = $objLabel . ' a déjà été converti' . $this->e() . ' en remise';
                    return 0;
                }

                switch ($type) {
                    case FactureFournisseur::TYPE_STANDARD:
                        if ($status === 0) {
                            $errors[] = $objLabel . ' est au statut "brouillon"';
                        }
                        if ((int) $this->getData('paye')) {
                            $errors[] = $objLabel . ' est au statut "payée"';
                        }
                        if ($this->getRemainToPay() >= 0) {
                            $errors[] = 'Le montant restant à payer est supérieur ou égale à 0';
                        }
                        break;

                    case FactureFournisseur::TYPE_CREDIT_NOTE:
                        if ($status !== 1) {
                            $errors[] = 'Statut actuel invalide';
                        }
                        if ((int) $this->getData('paye')) {
                            $errors[] = $objLabel . ' est marqué comme "payé"';
                        }
                        if ((float) $this->dol_object->getSommePaiement()) {
                            $errors[] = $objLabel . ' a déjà fait l\'objet d\'un ou plusieurs paiements';
                        }
                        break;

                    case FactureFournisseur::TYPE_DEPOSIT:
                        if (!$this->getData('paye') || (float) $this->getRemainToPay()) {
                            $errors[] = $objLabel . ' n\'est pas entièrement payé';
                        }
                        break;

                    default:
                        $errors[] = 'Action non autorisée pour ce type de facture';
                        break;
                }
                break;

            case 'duplicate':
            case 'create_credit_note':
                return 0;

            default:
                return parent::isActionAllowed($action, $errors);
        }

        if (count($errors)) {
            return 0;
        }
        return 1;
    }

    // Gestion des droits - overrides BimpObject: 

    public function canCreate()
    {
        return 1;
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
                if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->facture->creer)) ||
                        (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->validate))) {
                    return 1;
                }
                return 0;

            case 'modify':
            case 'reopen':
                if ($user->rights->fournisseur->facture->creer) {
                    return 1;
                }
                return 0;

            case 'sendEMail':
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send) {
                    return 1;
                }
                return 0;

            case 'makePayment':
                return 1;

            case 'classifyPaid':
                if ((int) $user->societe_id) {
                    return 0;
                }
                if ($user->rights->fournisseur->facture->creer) {
                    return 1;
                }
                return 0;

            case 'convertToReduc':
                if ($user->rights->fournisseur->facture->creer) {
                    return 1;
                }
                return 0;

            case 'duplicate':
                return 1;

            case 'create_credit_note':
                return 1;
        }

        return parent::canSetAction($action);
    }

    public function canEditField($field)
    {
        return (int) parent::canEditField($field);
    }

    // Getters - overrides BimpComm

    public function getActionsButtons()
    {
        global $conf, $langs, $user;

        $langs->load('bills');
        $type = $this->getData('type');

        $buttons = array();

        if ($this->isLoaded()) {
            // Valider: 
            if ($this->isActionAllowed('validate')) {
                if ($this->canSetAction('validate')) {
                    $buttons[] = array(
                        'label'   => 'Valider',
                        'icon'    => 'fas_check',
                        'onclick' => $this->getJsActionOnclick('validate', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la validation ' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Valider',
                        'icon'     => 'fas_check',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de valider ' . $this->getLabel('this')
                    );
                }
            }

            // Modifier (si aucun paiement): 
            if ($this->isActionAllowed('modify') && $this->canSetAction('modify')) {
                $buttons[] = array(
                    'label'   => 'Modifier',
                    'icon'    => 'fas_undo',
                    'onclick' => $this->getJsActionOnclick('modify', array(), array(
                        'confirm_msg' => 'Veuillez confirmer la remise au statut brouillon ' . $this->getLabel('of_this')
                    )),
                );
            }

            // Réouvrir une facture payée:
            if ($this->isActionAllowed('reopen')) {
                if ($this->canSetAction('reopen')) {
                    $buttons[] = array(
                        'label'   => 'Réouvrir',
                        'icon'    => 'fas_undo',
                        'onclick' => $this->getJsActionOnclick('reopen', array(), array(
                            'confirm_msg' => 'Veuillez confirmer la réouverture' . $this->getLabel('of_this')
                        ))
                    );
                } else {
                    $buttons[] = array(
                        'label'    => 'Réouvrir',
                        'icon'     => 'fas_undo',
                        'onclick'  => '',
                        'disabled' => 1,
                        'popover'  => 'Vous n\'avez pas la permission de réouvrir ' . $this->getLabel('this')
                    );
                }
            }

            // Envoyer par email: 
            if ($this->isActionAllowed('sendEMail') && $this->canSetAction('sendEMail')) {
                $onclick = $this->getJsActionOnclick('sendEmail', array(), array(
                    'form_name' => 'email'
                ));
                $buttons[] = array(
                    'label'   => 'Envoyer par email',
                    'icon'    => 'fas_envelope',
                    'onclick' => $onclick,
                );
            }

            // Réglement: 
            if ($this->isActionAllowed('makePayment') && $this->canSetAction('makePayment')) {
                $is_rbt = ((int) $this->getData('type') === FactureFournisseur::TYPE_CREDIT_NOTE);

                BimpObject::loadClass('bimpcommercial', 'Bimp_PaiementFourn');
//                $pf = BimpObject::getInstance('bimpcommercial', 'Bimp_PaiementFourn');
                $buttons[] = array(
                    'label'   => 'Saisir ' . ($is_rbt ? 'remboursement' : 'réglement'),
                    'icon'    => 'fas_euro-sign',
                    'onclick' => 'window.location = \'' . DOL_URL_ROOT . '/fourn/facture/paiement.php?facid=' . $this->id . '&action=create\';'
//                    'onclick' => $pf->getJsLoadModalForm('default', 'Paiement factures fournisseur', array(
//                        'fields' => array(
//                            'id_fourn' => (int) $this->getData('fk_soc')
//                        )
//                    )),
                );
            }

            // Classer payée: 
            if ($this->isActionAllowed('classifyPaid') && $this->canSetAction('classifyPaid')) {
                $onclick = $this->getJsActionOnclick('classifyPaid', array(), array(
                    'confirm_msg' => 'Êtes-vous sûr de vouloir modifier ' . $this->getLabel('this') . ' au statut payé' . $this->e() . '?'
                ));
                $buttons[] = array(
                    'label'   => 'Classer payé' . $this->e(),
                    'icon'    => 'fas_check',
                    'onclick' => $onclick,
                );
            }

            // Convertir en remise: 
            if ($this->isActionAllowed('convertToReduc') && $this->canSetAction('convertToReduc')) {
                $confirm_msg = 'Veuillez confirmer la conversion en remise';

                if ($type === FactureFournisseur::TYPE_STANDARD) {
                    $label = 'Convertir en remise';
                    $confirm_msg .= ' du trop perçu ' . $this->getLabel('of_this');
                } else {
                    $label = 'Convertir en remise';
                    $confirm_msg .= ' du montant restant à rembourser ' . $this->getLabel('of_this');
                }

                $onclick = $this->getJsActionOnclick('convertToReduc', array(), array(
                    'confirm_msg' => $confirm_msg
                ));

                $buttons[] = array(
                    'label'   => $label,
                    'icon'    => 'fas_percent',
                    'onclick' => $onclick,
                );
            }

            // Cloner: 
//            if ($this->isActionAllowed('duplicate') && $this->canSetAction('duplicate')) {
//                $buttons[] = array(
//                    'label'   => 'Cloner',
//                    'icon'    => 'fas_copy',
//                    'onclick' => ''
//                    'onclick' => $this->getJsActionOnclick('duplicate', array(), array(
//                        'confirm_msg' => 'Etes-vous sûr de vouloir cloner ' . $this->getLabel('this')
//                        'form_name' => 'duplicate_propal'
//                    ))
//                );
//            }
            // Créer un avoir: 
//            if ($this->isActionAllowed('create_credit_note') && $this->canSetAction('create_credit_note')) {
//                $buttons[] = array(
//                    'label'   => 'Créer un avoir',
//                    'icon'    => 'far_file-alt',
//                    'onclick' => ''
//                );
//            }
        }

        return $buttons;
    }

    public function getModelsPdfArray()
    {
        if (!class_exists('ModelePDFSuppliersInvoices')) {
            require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php';
        }

        return ModelePDFSuppliersInvoices::liste_modeles($this->db->db);
    }

    public function getFilesDir()
    {
        if ($this->isLoaded()) {
            return $this->getDirOutput() . '/';
        }

        return '';
    }

    public function getFileUrl($file_name)
    {
        $dir = $this->getFilesDir();
        if ($dir) {
            if (file_exists($dir . $file_name)) {
                $subdir = get_exdir($this->id, 2, 0, 0, $this->dol_object, 'invoice_supplier') . $this->getData('ref');
                return DOL_URL_ROOT . '/document.php?modulepart=' . static::$files_module_part . '&file=' . htmlentities($subdir . '/' . $file_name);
            }
        }

        return '';
    }

    public function getDirOutput()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        global $conf;
        $ref = dol_sanitizeFileName($this->dol_object->ref);
        $subdir = get_exdir($this->id, 2, 0, 0, $this->dol_object, 'invoice_supplier') . $ref;
        return $conf->fournisseur->facture->dir_output . '/' . $subdir;
    }

    public function getTotalPaid()
    {
        $alreadypaid += $this->dol_object->getSommePaiement();
        $alreadypaid += $this->dol_object->getSumDepositsUsed();
        $alreadypaid += $this->dol_object->getSumCreditNotesUsed();
        return $alreadypaid;
    }

    public function getRemainToPay()
    {
        return (float) $this->getData('total_ttc') - (float) $this->getTotalPaid();
    }

    // Getters callbacks: 

    public function getCreateFromOriginCheckMsg()
    {
        if (BimpTools::getPostFieldValue('origin', '') === 'commande_fournisseur') {
            return array(array(
                    'content' => 'Attention: après la création de la facture, les quantités de la commande fournisseurs ne seront plus modifiables dans la logistique',
                    'type'    => 'warning'
            ));
        }
    }

    // Affichages: 

    public function displayPaid()
    {
        if ($this->isLoaded()) {
            $paid = $this->getTotalPaid();
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
                    case FactureFournisseur::TYPE_CREDIT_NOTE:
                        $label = $langs->trans('Bill' . $prefix . 'StatusPaidBackOrConverted');
                        break;

                    case FactureFournisseur::TYPE_DEPOSIT:
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

    // Rendus HTML - overrides BimpObject:

    public function renderHeaderStatusExtra()
    {
        return '<span style="display: inline-block; margin-left: 12px"' . $this->displayPaidStatus() . '</span>';
    }

    public function renderHeaderExtraLeft()
    {
        $html = '';

        if ($this->isLoaded()) {
            $user = new User($this->db->db);

            $html .= '<div class="object_header_infos">';
            $html .= 'Créée le <strong>' . $this->displayData('datec', 'default', false, true) . '</strong>';

            $user->fetch((int) $this->getData('fk_user_author'));
            $html .= ' par ' . $user->getNomUrl(1);
            $html .= '</div>';

            if ((int) $this->getData('fk_user_valid')) {
                $user->fetch((int) $this->getData('fk_user_valid'));
                $html .= '<div class="object_header_infos">';
                $html .= 'Validée par ' . $user->getNomUrl(1);
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function renderHeaderExtraRight()
    {
        $html = '';

//        $html .= '<div class="buttonsContainer">';
//
//        $pdf_dir = $this->getDirOutput();
//        $ref = dol_sanitizeFileName($this->getRef());
//        $pdf_file = $pdf_dir . '/' . $ref . '/' . $ref . '.pdf';
//        if (file_exists($pdf_file)) {
//            $url = DOL_URL_ROOT . '/document.php?modulepart=' . static::$dol_module . '&file=' . htmlentities($ref . '/' . $ref . '.pdf');
//            $onclick = 'window.open(\'' . $url . '\');';
//
//            $html .= BimpRender::renderButton(array(
//                        'classes'     => array('btn', 'btn-default'),
//                        'label'       => $ref . '.pdf',
//                        'icon_before' => 'fas_file-pdf',
//                        'attr'        => array(
//                            'onclick' => $onclick
//                        )
//            ));
//        }
//
//        $html .= "<a class='btn btn-default' href='../comm/propal/card.php?id=" . $this->id . "'><i class='fa fa-file iconLeft'></i>Ancienne version</a>";
//
//        $html .= '</div>';

        return $html;
    }

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

            if ($type !== FactureFournisseur::TYPE_CREDIT_NOTE) {
                $total_credit_note = 0;
                $total_deposit = 0;

                $html .= '<tr>';
                $html .= '<td style="text-align: right;"><strong>Déjà réglé</strong>';
                if ($type !== FactureFournisseur::TYPE_DEPOSIT) {
                    $html .= '<br/>(Hors avoirs et acomptes)';
                }
                $html .= ' : </td>';
                $html .= '<td>' . BimpTools::displayMoneyValue($total_paid, 'EUR') . '</td>';
                $html .= '<td>';
                $html .= '</td>';
                $html .= '</tr>';

                // Avoirs utilisés: 
                $rows = $this->db->getRows('societe_remise_except', '`fk_invoice_supplier` = ' . (int) $this->id, null, 'array');
                if (!is_null($rows) && count($rows)) {
                    foreach ($rows as $r) {
                        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $r['fk_invoice_supplier_source']);
                        if ($facture->isLoaded()) {
                            $html .= '<tr>';
                            $html .= '<td style="text-align: right;">';
                            if ((int) $facture->getData('type') === FactureFournisseur::TYPE_CREDIT_NOTE) {
                                $html .= 'Avoir ';
                                $total_credit_note += (float) $r['amount_ttc'];
                            } elseif ((int) $facture->getData('type') === FactureFournisseur::TYPE_DEPOSIT) {
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
                if (($status == FactureFournisseur::STATUS_CLOSED || $status == FactureFournisseur::STATUS_ABANDONED)) {
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
                $discount->fetch(0, 0, $this->id);
                if (BimpObject::objectLoaded($discount)) {
                    $remainToPay_final = 0;
                    $html .= '<tr>';
                    $html .= '<td style="text-align: right;">';
                    $html .= '<strong>Trop perçu converti en </strong>' . $discount->getNomUrl(1, 'discount');
                    $html .= '</td>';
                    $html .= '<td>';
                    $html .= BimpTools::displayMoneyValue($discount->amount_ttc);
                    $html .= '</td>';
                    $html .= '</tr>';
                }

                // total facturé: 
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
                $html .= '<td style="font-size: 18px">';
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


            $rows = $this->db->getRows('paiementfourn_facturefourn', '`fk_facturefourn` = ' . (int) $this->id, null, 'array');

            $html = '<table class="bimp_list_table">';

            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Paiement</th>';
            $html .= '<th>Date</th>';
            $html .= '<th>Type</th>';
            $html .= '<th>Compte bancaire</th>';
            $html .= '<th>Montant</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';

            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    $paiement = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_PaiementFourn', (int) $r['fk_paiementfourn']);
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
            if (in_array($type, array(FactureFournisseur::TYPE_STANDARD, FactureFournisseur::TYPE_REPLACEMENT, FactureFournisseur::TYPE_DEPOSIT)) && $user->rights->facture->paiement) {
                $paiement_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_PaiementFourn');

                $object_data = '{module: \'bimpcommercial\', object_name: \'Bimp_Paiement\', id_object: 0, ';
                $object_data .= 'param_values: {fields: {id_client: ' . (int) $this->getData('fk_soc') . ', id_mode_paiement: ' . (int) $this->getData('fk_mode_reglement') . '}}}';

                $onclick = $paiement_instance->getJsLoadModalForm('default', 'Paiements factures fournisseurs', array(
                    'fields' => array(
                        'id_fourn'         => (int) $this->getData('fk_soc'),
                        'id_mode_paiement' => (int) $this->getData('fk_mode_reglement')
                    )
                ));

                $buttons[] = array(
                    'label'       => 'Saisir réglement',
                    'icon_before' => 'plus-circle',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => $onclick
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

    public function renderMarginsTable()
    {
        return '';
    }

    // Traitements: 

    public function onCreate()
    {
        if (!$this->isLoaded()) {
            return;
        }

        foreach (BimpTools::getDolObjectLinkedObjectsList($this->dol_object, $this->db) as $item) {
            if ($item['type'] === 'order_supplier') {
                $commande_fourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $item['id_object']);
                if (BimpObject::objectLoaded($commande_fourn)) {
                    $commande_fourn->checkInvoiceStatus();
                }
            }
        }
    }

    public function onValidate()
    {
        if ($this->isLoaded()) {
            $lines = $this->getLines('not_text');
            foreach ($lines as $line) {
                $line->onFactureValidate();
            }
        }
    }

    public function convertToReduc($validate = true)
    {
        $errors = array();

        if (!$validate) {
            if (!$this->canSetAction('convertToReduc')) {
                $errors[] = 'Vous n\'avez pas la permission';
            }

            if (!$this->isActionAllowed('convertToReduc', $errors)) {
                return $errors;
            }
        }

        if (!count($errors)) {
            global $langs, $user;
            $db = $this->db->db;
            $object = $this->dol_object;

            $db->begin();

            $amount_ht = $amount_tva = $amount_ttc = array();

            // Loop on each vat rate
            $i = 0;
            foreach ($object->lines as $line) {
                if ($line->product_type < 9 && $line->total_ht != 0) { // Remove lines with product_type greater than or equal to 9  // no need to create discount if amount is null
                    $amount_ht[$line->tva_tx] += $line->total_ht;
                    $amount_tva[$line->tva_tx] += $line->total_tva;
                    $amount_ttc[$line->tva_tx] += $line->total_ttc;
                    $i ++;
                }
            }

            // Insert one discount by VAT rate category
            $discount = new DiscountAbsolute($db);
            if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE)
                $discount->description = '(CREDIT_NOTE)';
            elseif ($object->type == FactureFournisseur::TYPE_DEPOSIT)
                $discount->description = '(DEPOSIT)';
            elseif ($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT || $object->type == FactureFournisseur::TYPE_SITUATION)
                $discount->description = '(EXCESS PAID)';
            else {
                setEventMessages($langs->trans('CantConvertToReducAnInvoiceOfThisType'), null, 'errors');
            }
            $discount->discount_type = 1; // Supplier discount
            $discount->fk_soc = $object->socid;
            $discount->fk_invoice_supplier_source = $object->id;

            $error = 0;

            if ($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT || $object->type == FactureFournisseur::TYPE_SITUATION) {
                // If we're on a standard invoice, we have to get excess paid to create a discount in TTC without VAT

                $sql = 'SELECT SUM(pf.amount) as total_paiements';
                $sql.= ' FROM ' . MAIN_DB_PREFIX . 'paiementfourn_facturefourn as pf, ' . MAIN_DB_PREFIX . 'paiementfourn as p';
                $sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_paiement as c ON p.fk_paiement = c.id AND c.entity IN (' . getEntity('c_paiement') . ')';
                $sql.= ' WHERE pf.fk_facturefourn = ' . $object->id;
                $sql.= ' AND pf.fk_paiementfourn = p.rowid';
                $sql.= ' AND p.entity IN (' . getEntity('facture') . ')';

                $resql = $db->query($sql);
                if (!$resql)
                    dol_print_error($db);

                $res = $db->fetch_object($resql);
                $total_paiements = $res->total_paiements;

                $discount->amount_ht = $discount->amount_ttc = $total_paiements - $object->total_ttc;
                $discount->amount_tva = 0;
                $discount->tva_tx = 0;

                $result = $discount->create($user);
                if ($result < 0) {
                    $error++;
                }
            }
            if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE || $object->type == FactureFournisseur::TYPE_DEPOSIT) {
                foreach ($amount_ht as $tva_tx => $xxx) {
                    $discount->amount_ht = abs($amount_ht[$tva_tx]);
                    $discount->amount_tva = abs($amount_tva[$tva_tx]);
                    $discount->amount_ttc = abs($amount_ttc[$tva_tx]);
                    $discount->tva_tx = abs($tva_tx);

                    $result = $discount->create($user);
                    if ($result < 0) {
                        $error++;
                        break;
                    }
                }
            }

            if (empty($error)) {
                if ($object->type != FactureFournisseur::TYPE_DEPOSIT) {
                    // Classe facture
                    $result = $object->set_paid($user);
                    if ($result >= 0) {
                        $db->commit();
                    } else {
                        $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($object));
                        $db->rollback();
                    }
                } else {
                    $db->commit();
                }
            } else {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount));
                $db->rollback();
            }

            $this->fetch($this->id);
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

    // Actions: 

    public function actionValidate($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Facture fournisseur validée avec succès';

        if (!(int) $this->getData('entrepot')) {
            $errors[] = 'Entrepôt absent. Veuillez sélectionner un entrepôt avant de valider';
        } else {
            BimpTools::resetDolObjectErrors($this->dol_object);
            global $user, $conf, $langs;

            if ($this->dol_object->validate($user, '', (int) $this->getData('entrepot')) < 0) {
                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Des erreurs sont survenues lors de la validation ' . $this->getLabel('of_the'));
            } else {
                if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                    $this->fetch($this->id);
                    $this->dol_object->generateDocument($this->getModelPdf(), $langs);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionReopen($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Réouverture effectuée avec succès';

        global $user;
        BimpTools::resetDolObjectErrors($this->dol_object);
        if ($this->dol_object->set_unpaid($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object, null, null, $warnings), 'Echec de la réouverture');
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionClassifyPaid($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = BimpTools::ucfirst($this->getLabel('the')) . ' ' . $this->getRef() . ' a bien été classé' . ($this->isLabelFemale() ? 'e' : '') . ' "payé' . ($this->isLabelFemale() ? 'e' : '') . '"';

        global $user;
        if ($this->dol_object->set_paid($user) <= 0) {
            $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($this->dol_object), 'Des erreurs sont survenues');
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
        $success = BimpTools::ucfirst($this->getLabel('the')) . ' a été converti' . $this->e() . ' en remise avec succès';

        $errors = $this->convertToReduc();

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides - BimpComm: 

    public function getDbData($fields)
    {
        $final_fields = array();

        foreach ($fields as $field) {
            if ($field === 'fk_user_valid') {
                continue;
            }

            $final_fields[] = $field;
        }
        return parent::getDbData($final_fields);
    }

    public function createLinesFromOrigin($origin)
    {
        $errors = array();

        if (!$this->isLoaded()) {
            return array('ID de la facture fournisseur absent');
        }

        if (!BimpObject::objectLoaded($origin) || !is_a($origin, 'BimpComm')) {
            return array('Element d\'origine absent ou invalide');
        }

        if (!is_a($origin, 'Bimp_CommandeFourn')) {
            return parent::createLinesFromOrigin($origin);
        }

        $lines = $origin->getChildrenObjects('lines', array(), 'position', 'asc');

        $warnings = array();
        $i = 0;

        // Création des lignes: 
        $lines_new = array();

        foreach ($lines as $line) {

            $lines_data = $line->getLinesDataByUnitPriceAndTva();

            $isSerialisable = $line->isProductSerialisable();
            $isReturn = ((float) $line->getFullQty());

            foreach ($lines_data as $pu_ht => $pu_data) {
                foreach ($pu_data as $tva_tx => $line_data) {
                    $i++;
                    if ($isSerialisable) {
                        if (!$isReturn) {
                            $qty = count($line_data['equipments']);
                        } else {
                            $qty = count($line_data['equipments']) * -1;
                        }
                    } else {
                        $qty = (float) $line_data;
                    }
                    $pu_ht = (float) $pu_ht;
                    $tva_tx = (float) $tva_tx;

                    $line_instance = BimpObject::getInstance($this->module, $this->object_name . 'Line');
                    $line_instance->validateArray(array(
                        'id_obj'    => (int) $this->id,
                        'type'      => $line->getData('type'),
                        'deletable' => 1,
                        'editable'  => 1,
                        'remisable' => $line->getData('remisable')
                    ));

                    $line_instance->desc = $line->desc;
                    $line_instance->tva_tx = $tva_tx;
                    $line_instance->id_product = $line->id_product;
                    $line_instance->qty = $qty;
                    $line_instance->pu_ht = $pu_ht;
                    $line_instance->pa_ht = $line->pa_ht;
                    $line_instance->id_fourn_price = $line->id_fourn_price;
                    $line_instance->date_from = $line->date_from;
                    $line_instance->date_to = $line->date_to;
                    $line_instance->id_remise_except = $line->id_remise_except;

                    $line_errors = $line_instance->create($warnings, true);
                    if (count($line_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($line_errors, 'Echec de la création de la ligne n°' . $i);
                        continue;
                    }

                    $lines_new[(int) $line->id] = (int) $line_instance->id;

                    // NOTE: on intègre pas les remises de la ligne de commande: celles-ci sont déjà déduites dans le pu_ht.
                    // Création des remises pour la ligne en cours:
//                    $remises = $line->getRemises();
//                    if (!is_null($remises) && count($remises)) {
//                        $j = 0;
//                        foreach ($remises as $r) {
//                            $j++;
//                            $remise = BimpObject::getInstance('bimpcommercial', 'ObjectLineRemise');
//                            $remise->validateArray(array(
//                                'id_object_line' => (int) $line_instance->id,
//                                'object_type'    => $line_instance::$parent_comm_type,
//                                'label'          => $r->getData('label'),
//                                'type'           => (int) $r->getData('type'),
//                                'percent'        => (float) $r->getData('percent'),
//                                'montant'        => (float) $r->getData('montant'),
//                                'per_unit'       => (int) $r->getData('per_unit')
//                            ));
//                            $remise_errors = $remise->create($warnings, true);
//                            if (count($remise_errors)) {
//                                $errors[] = BimpTools::getMsgFromArray($remise_errors, 'Echec de la création de la remise n°' . $j . ' pour la ligne n°' . $i);
//                            }
//                        }
//                    }
                    // Ajout des équipements: 
                    if ($isSerialisable) {
                        $equipments = (isset($line_data['equipments']) ? $line_data['equipments'] : array());
                        foreach ($equipments as $id_equipment) {
                            $eq_errors = $line_instance->attributeEquipment((int) $id_equipment);
                            if (count($eq_errors)) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    $eq_label = '"' . $equipment->getData('serial') . '"';
                                } else {
                                    $eq_label = 'd\'ID ' . $id_equipment;
                                }
                                $errors[] = BimpTools::getMsgFromArray($eq_errors, 'Ligne n°' . $i . ': échec de l\'attribution de l\'équipement ' . $eq_label);
                            }
                        }
                    }
                }
            }
        }

        // Attribution des lignes parentes: 
        foreach ($lines as $line) {
            $id_parent_line = (int) $line->getData('id_parent_line');

            if ($id_parent_line) {
                if (isset($lines_new[(int) $line->id]) && (int) $lines_new[(int) $line->id] && isset($lines_new[$id_parent_line]) && (int) $lines_new[$id_parent_line]) {
                    $line_instance = BimpCache::getBimpObjectInstance($this->module, $this->object_name . 'Line', (int) $lines_new[(int) $line->id]);
                    if (BimpObject::objectLoaded($line_instance)) {
                        $line_instance->updateField('id_parent_line', (int) $lines_new[(int) $id_parent_line]);
                    }
                }
            }
        }

        $asso = new BimpAssociation($origin, 'factures');
        $asso->addObjectAssociation($this->id);

        return $errors;
    }

    protected function updateDolObject(&$errors)
    {
        return parent::updateDolObject($errors);
    }

    // Overrides BimpObject

    public function fetch($id, $parent = null)
    {
        $result = parent::fetch($id, $parent);

        if ($this->isLoaded()) {
            switch ((int) $this->getData('type')) {
                case FactureFournisseur::TYPE_STANDARD:
                    $this->params['labels']['name'] = 'facture fournisseur';
                    $this->params['labels']['name_plur'] = 'factures fournisseurs';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case FactureFournisseur::TYPE_CREDIT_NOTE:
                    $this->params['labels']['name'] = 'avoir fournisseur';
                    $this->params['labels']['name_plur'] = 'avoirs fournisseurs';
                    $this->params['labels']['is_female'] = 0;
                    break;

                case FactureFournisseur::TYPE_DEPOSIT:
                    $this->params['labels']['name'] = 'acompte fournisseur';
                    $this->params['labels']['name_plur'] = 'acomptes fournisseurs';
                    $this->params['labels']['is_female'] = 0;
                    break;

                case FactureFournisseur::TYPE_REPLACEMENT:
                    $this->params['labels']['name'] = 'facture fournisseur de remplacement';
                    $this->params['labels']['name_plur'] = 'factures fournisseurs de remplacement';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case FactureFournisseur::TYPE_SITUATION:
                    $this->params['labels']['name'] = 'facture fournisseur de situation';
                    $this->params['labels']['name_plur'] = 'factures fournisseurs de situation';
                    $this->params['labels']['is_female'] = 1;
                    break;

                case FactureFournisseur::TYPE_PROFORMA:
                    $this->params['labels']['name'] = 'facture fournisseur proforma';
                    $this->params['labels']['name_plur'] = 'factures fournisseurs proforma';
                    $this->params['labels']['is_female'] = 1;
                    break;
            }
        }

        return $result;
    }
}
