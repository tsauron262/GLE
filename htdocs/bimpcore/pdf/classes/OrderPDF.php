<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once __DIR__ . '/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

class OrderPDF extends BimpDocumentPDF
{

    public static $type = 'order';
    public $commande = null;
    public $doc_type = 'commande';
    public static $doc_types = array(
        'commande' => 'Commande',
        'bl'       => 'Bon de livraison',
        'bl_draft' => 'Bon de préparation'
    );
    public $contact_invoice = null;
    public $contact_shipment = null;
    public $user_commercial = null;
    public $user_suivi = null;
    public $entrepot = null;

    public function __construct($db, $doc_type = 'commande')
    {
        if (!array_key_exists($doc_type, self::$doc_types)) {
            $doc_type = 'commande';
        }

        $this->doc_type = $doc_type;

        parent::__construct($db);

        $this->langs->load("orders");
        $this->langs->load("bills");
        $this->langs->load("products");
        $this->commande = new Commande($db);
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Commande')) {
            if (isset($this->object->id) && $this->object->id) {
                $this->bimpCommObject = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', (int) $this->object->id);

                if (isset($this->object->array_options['options_pdf_hide_reduc'])) {
                    $this->hideReduc = (int) $this->object->array_options['options_pdf_hide_reduc'];
                }

                $this->commande = $this->object;
                $this->commande->fetch_thirdparty();

                $this->thirdparty = $this->commande->thirdparty;

                global $user;

                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("Order"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("Invoice") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));

                if (is_null($this->contact)) {
                    $contacts = $this->commande->getIdContact('external', 'CUSTOMER');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $contact = new Contact($this->db);
                        if ($contact->fetch((int) $contacts[0]) > 0) {
                            $this->contact = $contact;
                        }
                    }
                }

                if (is_null($this->contact_invoice)) {
                    $contacts = $this->commande->getIdContact('external', 'BILLING');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $contact = new Contact($this->db);
                        if ($contact->fetch((int) $contacts[0]) > 0) {
                            $this->contact_invoice = $contact;
                        }
                    }
                }

                if (is_null($this->contact_shipment)) {
                    $contacts = $this->commande->getIdContact('external', 'SHIPPING');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $contact = new Contact($this->db);
                        if ($contact->fetch((int) $contacts[0]) > 0) {
                            $this->contact_shipment = $contact;
                        }
                    }
                }

                if (is_null($this->user_commercial)) {
                    $contacts = $this->commande->getIdContact('internal', 'SALESREPFOLL');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $new_user = new User($this->db);
                        if ($new_user->fetch((int) $contacts[0]) > 0) {
                            $this->user_commercial = $new_user;
                        }
                    }
                }

                if (is_null($this->user_suivi)) {
                    $contacts = $this->commande->getIdContact('internal', 'SALESREPFOLL');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $new_user = new User($this->db);
                        if ($new_user->fetch((int) $contacts[0]) > 0) {
                            $this->user_suivi = $new_user;
                        }
                    }
                }

                if (isset($this->commande->array_options['options_entrepot']) && (int) $this->commande->array_options['options_entrepot']) {
                    BimpTools::loadDolClass('product/stock', 'entrepot');
                    $entrepot = new Entrepot($this->db);
                    if ($entrepot->fetch((int) $this->commande->array_options['options_entrepot']) > 0) {
                        $this->entrepot = $entrepot;
                    }
                }
            } else {
                $this->errors[] = 'Commande invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune commande spécifiée';
        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        global $db, $conf;

        if (isset($this->commande->thirdparty) && !is_null($this->commande->thirdparty)) {
            $soc = $this->commande->thirdparty;
        } elseif (isset($this->commande->socid) && $this->commande->socid) {
            $soc = new Societe($db);
            $soc->fetch($this->commande->socid);
        } else {
            $soc = null;
        }

        // Titre commande:
        $docName = self::$doc_types[$this->doc_type];

        if ($this->sitationinvoice) {
            $docName = self::$doc_types[$this->doc_type];
        }

        // Réf commande: 
        switch ($this->doc_type) {
            case 'commande':
                if ($this->commande->statut == Commande::STATUS_DRAFT) {
                    $docRef = '<span style="color: #800000"> ' . $docRef . ' - ' . $this->langs->transnoentities("NotValidated") . '</span>';
                } else {
                    $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->commande->ref);
                }
                break;

            case 'bl':
            case 'bl_draft':
                $docRef = $this->langs->transnoentities("Ref") . " : " . 'LIV-' . $this->langs->convToOutputCharset($this->commande->ref) . (isset($this->num_bl) && $this->num_bl ? '-' . $this->num_bl : '');
                break;
        }

//        $this->pdf->topMargin = 40;

        $this->header_vars['doc_name'] = $docName;
        $this->header_vars['doc_ref'] = $docRef;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= '<table style="font-size: 8px" cellpadding="5px">';
        $html .= '<tr>';
        $html .= '<td>';
        $html .= '<table cellpadding="2px">';
        if (!is_null($this->user_suivi)) {
            $html .= '<tr>';
            $html .= '<td colspan="2" style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Dossier suivi par : </span>';
            $html .= $this->user_suivi->firstname . ' ' . strtoupper($this->user_suivi->lastname);
            $html .= '</td>';

//            if ($this->user_suivi->office_phone) {
//                $html .= '<td  style="font-size: 8px">';
//                $html .= '<span style="font-weight: bold; color: #EF7D00">Tél. : </span>';
//                $html .= $this->user_suivi->office_phone;
//                $html .= '</td>';
//            }

            $html .= '</tr>';
        }
        if (!is_null($this->user_commercial)) {
            $html .= '<tr>';
            $html .= '<td' . (!$this->user_commercial->office_phone ? ' colspan="2"' : '') . ' style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Commercial : </span>';
            $html .= $this->user_commercial->firstname . ' ' . strtoupper($this->user_commercial->lastname);
            $html .= '</td>';

            if ($this->user_commercial->office_phone) {
                $html .= '<td style="font-size: 7px">';
                $html .= '<span style="font-weight: bold; color: #EF7D00">Tél. : </span>';
                $html .= $this->user_commercial->office_phone;
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '<tr><td></td><td></td></tr>';

        if (!is_null($this->contact)) {
            $html .= '<tr>';
            $html .= '<td colspan="2" style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Interlocuteur : </span>';
            $html .= $this->contact->firstname . ' ' . strtoupper($this->contact->lastname);
            $html .= '</td>';
            $html .= '</tr>';

            $phone = (isset($this->contact->phone_pro) && $this->contact->phone_pro ? $this->contact->phone_pro : '');
            if (!$phone) {
                $phone = (isset($this->contact->phone_perso) && $this->contact->phone_perso ? $this->contact->phone_perso : '');
            }
            $mobile = (isset($this->contact->phone_mobile) && $this->contact->phone_mobile ? $this->contact->phone_mobile : '');
            $fax = (isset($this->contact->fax) && $this->contact->fax ? $this->contact->fax : '');
            $email = (isset($this->contact->email) && $this->contact->email ? $this->contact->email : '');

            $html .= '<tr><td style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Tél. : </span>' . $phone;
            $html .= '</td><td style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Mobile : </span>' . $mobile;
            $html .= '</td></tr>';

            $html .= '<tr><td style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">Fax : </span>' . $fax;
            $html .= '</td><td style="font-size: 7px">';
            $html .= '<span style="font-weight: bold; color: #EF7D00">E-mail : </span>' . $email;
            $html .= '</td></tr>';

            $html .= '<tr><td></td><td></td></tr>';
        }

        $html .= '<tr>';
        $html .= '<td colspan="2">';
        $html .= '<span style="font-size: 7px; font-weight: bold;">Rappel de vos références internes ' . $this->fromCompany->nom . ' : </span>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2" style="font-size: 7px">';
        $html .= '<span style="font-weight: bold; color: #EF7D00">Code client : </span>';
        $html .= isset($this->thirdparty->code_client) ? $this->thirdparty->code_client : '';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2" style="font-size: 7px">';
        $html .= '<span style="font-weight: bold; color: #EF7D00">Ref. commande : </span>';
        $html .= $this->commande->ref;
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="2" style="font-size: 7px">';
        $html .= '<span style="font-weight: bold; color: #EF7D00">Date commande : </span>';
        $html .= dol_print_date($this->commande->date);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

//        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function renderDocInfos()
    {
        $primary = BimpCore::getParam('pdf/primary', '000000');

        $html = '';

        $town = '';
        if (!is_null($this->entrepot)) {
            $town = $this->entrepot->town;
        }

        if (!$town) {
            $town = $this->fromCompany->town;
        }

        if ($town) {
            $html .= '<div style="text-align: right">';
            $html .= BimpTools::ucfirst($town) . ', le ' . dol_print_date(dol_now());
            $html .= '</div>';
        }

        $html .= '<div style="font-size: 9px;" class="section addresses_section">';

        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="0">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 50%;">';
        $html .= $this->getDocInfosHtml();
        $html .= '</td>';
        $html .= '<td style="width: 3%;"></td>';
        $html .= '<td style="width: 47%; font-size: 8px;">';
        $html .= '<div>';
        $html .= '<table style="width: 100%;" cellspacing="0" cellpadding="5px">';
        $html .= '<tr>';
        $html .= '<td colspan="2" style="border-top: solid 1px #' . $primary . '; border-bottom: solid 1px #' . $primary . '; color: #' . $primary . '">';
        $html .= 'DESTINATAIRE';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td colspan="2">';

        $html .= '<div class="bold">' . pdfBuildThirdpartyName($this->thirdparty, $this->langs) . '</div>';

        switch ($this->doc_type) {
            case 'commande':
                $address = '';
                if (!is_null($this->contact)) {
                    if ($this->contact->address && $this->contact->zip && $this->contact->town) {
                        $address = pdf_build_address($this->langs, $this->fromCompany, $this->contact, $this->contact, 1, 'target');
                    }
                }
                if (!$address) {
                    $address = pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, null, 0, 'target');
                }

                $address = str_replace("\n", '<br/>', $address);
                break;

            case 'bl':
            case 'bl_draft':
                if (!is_null($this->contact_shipment)) {
                    $address = pdf_build_address($this->langs, $this->fromCompany, $this->contact_shipment, $this->contact_shipment, 1, 'target');
                } else {
                    $address = pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contact, (!is_null($this->contact) ? 1 : 0), 'target');
                }

                $address = str_replace("\n", '<br/>', $address);
                break;
        }

        $html .= $address;
        $html .= '</td>';
        $html .= '</tr>';

        if (!is_null($this->contact_invoice)) {
            $html .= '<tr>';
            $html .= '<td style="width: 25%; color: #787878; font-size: 7px;" class="bold">Facturation à :</td>';
            $html .= '<td style="width: 75%;">';
            $html .= '<div class="bold">' . pdfBuildThirdpartyName($this->contact_invoice, $this->langs) . '</div>';
            $address = pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contact_invoice, !is_null($this->contact_invoice) ? 1 : 0, 'target');
            $address = str_replace("\n", '<br/>', $address);
            $html .= $address;
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderLines()
    {
        global $conf;

        $table = new BimpPDF_AmountsTable($this->pdf);
        $table->addColDef('code_article', 'Code article', 25);
        $table->addColDef('dl', 'DL', 10);
        $table->addColDef('ral', 'RAL', 10);
        $table->cols_def['desc']['width_mm'] = 75;
        $table->cols_def['qte']['width_mm'] = 15;
        $table->cols_def['qte']['style'] = 'text-align: center;';

        $table->setCols(array('code_article', 'desc', 'pu_ht', 'tva', 'total_ht', 'qte'));

        BimpTools::loadDolClass('product');

        $i = 0;
        foreach ($this->object->lines as &$line) {
            $product = null;
            if (!is_null($line->fk_product) && $line->fk_product) {
                $product = new Product($this->db);
                if ($product->fetch((int) $line->fk_product) <= 0) {
                    unset($product);
                    $product = null;
                }
            }

            $desc = $this->getLineDesc($line, $product);

            if ($line->total_ht == 0) {
                if (!$desc) {
                    $i++;
                    unset($product);
                    $product = null;
                    continue;
                } else {
                    $row['code_article'] = array(
                        'colspan' => 99,
                        'content' => $desc,
                        'style'   => 'font-weight: bold; background-color: #F5F5F5;'
                    );
                }
            } else {
                $row = array(
                    'code_article' => (!is_null($product) ? $product->ref : ''),
                    'desc'         => $desc
                );

                if ($this->hideReduc && $line->remise_percent) {
                    $pu_ht = (float) ($line->subprice - ($line->subprice * ($line->remise_percent / 100)));
                    $row['pu_ht'] = price($pu_ht, 0, $this->langs);
                } else {
                    $pu_ht = (float) $line->supprice;
                    $row['pu_ht'] = pdf_getlineupexcltax($this->object, $i, $this->langs);
                }

                if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                    $row['tva'] = pdf_getlinevatrate($this->object, $i, $this->langs);
                }

                $row['qte'] = pdf_getlineqty($this->object, $i, $this->langs);
                $row['total_ht'] = pdf_getlinetotalexcltax($this->object, $i, $this->langs);
            }

            $table->rows[] = $row;
            $i++;
            unset($product);
            $product = null;
        }

        $this->writeContent('<div style="text-align: right; font-size: 8px;">Montants exprimés en Euros - DL : Déjà livré, RAL : Reste à livrer</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function getPaymentInfosHtml()
    {
        global $conf;

        $html = '<div style="font-size: 7px; line-height: 8px;">';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Conditions de paiement: 
        if ($this->commande->type != 2) {
            if ($this->commande->cond_reglement_code || $this->commande->cond_reglement) {
                $html .= '<tr><td>';
                $html .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong><br/>';
                $label = $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) != ('PaymentCondition' . $this->object->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) : $this->langs->convToOutputCharset($this->object->cond_reglement_doc);
                $label = str_replace('\n', "\n", $label);
                $html .= $label;
                $html .= '</td></tr>';
            }

            $error = '';
            if (empty($this->object->mode_reglement_code) && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($conf->global->FACTURE_RIB_NUMBER)) {
                $error = $this->langs->transnoentities("ErrorNoPaiementModeConfigured");
            } elseif (($this->object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($this->object->fk_account) && empty($this->object->fk_bank)) || ($this->object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER) && empty($this->object->fk_account) && empty($this->object->fk_bank))) {
                $error = $this->langs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
            }

            if ($error) {
                $html .= '<tr><td>';
                $html .= '<p style="text-color: #C80000; font-weight: bold;">' . $error . '</p>';
                $html .= '</td></tr>';
            }
        }

        // Mode de paiement: 
        if ($this->object->mode_reglement_code && $this->object->mode_reglement_code != 'CHQ' && $this->object->mode_reglement_code != 'VIR') {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("PaymentMode") . '</strong>:<br/>';
            $html .= $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) != ('PaymentType' . $this->object->mode_reglement_code) ? $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) : $this->langs->convToOutputCharset($this->object->mode_reglement);
            $html .= '</td></tr>';
        }


        if (!class_exists('Account')) {
            require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'CHQ') {

            if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
                    $html .= '<tr><td>';
                    $account = new Account($this->db);
                    $account->fetch($conf->global->FACTURE_CHQ_NUMBER);

                    $html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByChequeOrderedTo', $account->proprio) . ':</span><br/><br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $html .= '<strong>' . str_replace("\n", '<br/>', $this->langs->convToOutputCharset($account->owner_address)) . '</strong>';
                    }
                    $html .= '</td></tr>';
                } elseif ($conf->global->FACTURE_CHQ_NUMBER == -1) {
                    $html .= '<tr><td>';
                    $html .= $this->langs->transnoentities('PaymentByChequeOrderedTo', $this->fromCompany->name) . '<br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $html .= $this->langs->convToOutputCharset($this->fromCompany->getFullAddress()) . '<br/>';
                    }
                    $html .= '</td></tr>';
                }
            }
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'VIR') {
            if (!empty($this->object->fk_account) || !empty($this->object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
                $html .= '<tr><td>';
                $bankid = (empty($this->object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $this->object->fk_account);
                if (!empty($this->object->fk_bank)) {
                    $bankid = $this->object->fk_bank;
                }

                $account = new Account($this->db);
                $account->fetch($bankid);
                $html .= $this->getBankHtml($account);
                $html .= '</td></tr>';
            }
        }

        $html .= '</table></div>';

        return $html;
    }
}

class BLPDF extends OrderPDF
{

    public $shipment = null;
    public $total_ht = 0;
    public $total_ttc = 0;
    public $num_bl = '';

    public function __construct($db, $shipment = null)
    {
        $doc_type = 'bl';

        if (BimpObject::objectLoaded($shipment)) {
            $this->shipment = $shipment;
            $this->num_bl = $shipment->getData('num_livraison');

            if ((int) $shipment->getData('status') === BL_CommandeShipment::BLCS_BROUILLON) {
                $doc_type = 'bl_draft';
            }
        }

        if (is_null($this->shipment)) {
            $this->errors[] = 'ID de l\'expédition absent';
        } else {
            $this->prefName = "BL_" . $this->shipment->getData('num_livraison') . "_";
        }

        $this->typeObject = "commande";

        parent::__construct($db, $doc_type);

        if (!is_null($id_contact_shipment) && $id_contact_shipment) {
            BimpTools::loadDolClass('contact');
            $this->contact_shipment = new Contact($this->db);
            if ($this->contact_shipment->fetch((int) $shipment->getcontact()) <= 0) {
                $this->errors[] = 'Contact pour la livraison non trouvé (ID ' . $shipment->getcontact() . ')';
                unset($this->contact_shipment);
                $this->contact_shipment = null;
            }
        }
    }

    public function renderLines()
    {
        global $conf;

        $table = new BimpPDF_AmountsTable($this->pdf);
        $table->addColDef('code_article', 'Code article', 30);
        $table->addColDef('dl', 'DL', 10, 'text-align: center;', '', 'text-align: center;');
        $table->addColDef('ral', 'RAL', 10, 'text-align: center;', '', 'text-align: center;');
        $table->cols_def['desc']['width_mm'] = 75;
        $table->cols_def['tva']['width_mm'] = 15;
        $table->cols_def['qte']['width_mm'] = 10;
        $table->cols_def['qte']['style'] = 'text-align: center;';
        $table->cols_def['qte']['head_style'] = 'text-align: center;';

        $table->setCols(array('code_article', 'desc', 'pu_ht', 'tva', 'total_ht', 'qte', 'dl', 'ral'));

        BimpTools::loadDolClass('product');

        BimpObject::loadClass('bimpreservation', 'BR_Reservation');
        $qties = $this->shipment->getPDFQtiesAndSerials();

        $i = 0;

        foreach ($this->object->lines as &$line) {
            $product = null;
            if (!is_null($line->fk_product) && $line->fk_product) {
                $product = new Product($this->db);
                if ($product->fetch((int) $line->fk_product) <= 0) {
                    unset($product);
                    $product = null;
                }
            }

            $desc = $this->getLineDesc($line, $product);

            if ($line->total_ht == 0) {
                if (!$desc) {
                    $i++;
                    unset($product);
                    $product = null;
                    continue;
                } else {
                    $row['code_article'] = array(
                        'colspan' => 99,
                        'content' => $desc,
                        'style'   => 'font-weight: bold; background-color: #F5F5F5;'
                    );
                }
            } else {
                if (!is_null($product)) {
                    if (isset($qties[(int) $line->id]['serials']) && count($qties[(int) $line->id]['serials'])) {
                        $desc .= '<br/>';
                        $desc .= '<strong>N° de série</strong>: ';
                        $first = true;
                        foreach ($qties[(int) $line->id]['serials'] as $serial) {
                            if (!$first) {
                                $desc .= ', ';
                            } else {
                                $first = false;
                            }
                            $desc .= $serial;
                        }
                    }
                }
                $row = array(
                    'code_article' => (!is_null($product) ? $product->ref : ''),
                    'desc'         => $desc,
                    'pu_ht'        => pdf_getlineupexcltax($this->object, $i, $this->langs),
                );

                if ($this->hideReduc && $line->remise_percent) {
                    $pu_ht = (float) ($line->subprice - ($line->subprice * ($line->remise_percent / 100)));

                    $row['pu_ht'] = price($pu_ht, 0, $this->langs);
                } else {
                    $pu_ht = (float) $line->subprice;
                    $row['pu_ht'] = pdf_getlineupexcltax($this->object, $i, $this->langs);
                }

                if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                    $row['tva'] = pdf_getlinevatrate($this->object, $i, $this->langs);
                }

                $qty = isset($qties[(int) $line->id]['qty']) ? $qties[(int) $line->id]['qty'] : 0;
                $row['qte'] = $qty;
                $row['dl'] = isset($qties[(int) $line->id]['shipped_qty']) ? $qties[(int) $line->id]['shipped_qty'] : 0;
                $row['ral'] = isset($qties[(int) $line->id]['to_ship_qty']) ? $qties[(int) $line->id]['to_ship_qty'] : 0;

                $total_ht = (float) $qty * (float) $pu_ht;

                $row['total_ht'] = price($total_ht);

                // Ajout aux totaux: 
                if (!$this->hideReduc && isset($line->remise_percent) && (float) $line->remise_percent) {
                    $remise = (float) ($total_ht * ((float) $line->remise_percent / 100));
                    $total_ht -= $remise;
                    $this->total_remises += $remise;
                }

                $this->total_ht += $total_ht;

                $tva_line = $total_ht * ($line->tva_tx / 100);
                $localtax1_rate = $line->localtax1_tx;
                $localtax2_rate = $line->localtax2_tx;
                $localtax1_type = $line->localtax1_type;
                $localtax2_type = $line->localtax2_type;
                $localtax1ligne = $total_ht * ($localtax1_rate / 100);
                $localtax2ligne = $total_ht * ($localtax2_rate / 100);

                $this->total_ttc += $total_ht + $tva_line + $localtax1ligne + $localtax2ligne;

                if (isset($this->commande->remise_percent) && (float) $this->commande->remise_percent) {
                    $tva_line -= ($tva_line * $this->object->remise_percent) / 100;
                    $localtax1ligne -= ($localtax1ligne * $this->object->remise_percent) / 100;
                    $localtax2ligne -= ($localtax2ligne * $this->object->remise_percent) / 100;
                }

                if (!isset($this->localtax1[$localtax1_type])) {
                    $this->localtax1[$localtax1_type] = array();
                }
                if (!isset($this->localtax1[$localtax1_type][$localtax1_rate])) {
                    $this->localtax1[$localtax1_type][$localtax1_rate] = 0;
                }

                $this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;

                if (!isset($this->localtax2[$localtax2_type])) {
                    $this->localtax2[$localtax2_type] = array();
                }
                if (!isset($this->localtax2[$localtax2_type][$localtax2_rate])) {
                    $this->localtax2[$localtax2_type][$localtax2_rate] = 0;
                }

                $this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;

                if (!isset($this->tva[$line->tva_tx])) {
                    $this->tva[$line->tva_tx] = 0;
                }

                $this->tva[$line->tva_tx] += $tva_line;
            }

            $table->rows[] = $row;

            $i++;
            unset($product);
            $product = null;
        }

        $this->writeContent('<div style="text-align: right; font-size: 8px;">Montants exprimés en Euros - DL : Déjà livré, RAL : Reste à livrer</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function calcTotaux()
    {
        
    }

    public function getTotauxRowsHtml()
    {
        global $conf;

        if ($this->hideTotal) {
            return '';
        }
        $this->calcTotaux();

        $html = '<div>';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Total remises: 
        if (!$this->hideReduc && $this->total_remises > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Total remises HT</td>';
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($this->total_remises, 0, $this->langs) . '</td>';
            $html .= '</tr>';
        }

        // Total HT:
        $html .= '<tr>';
        $html .= '<td style="">' . $this->langs->transnoentities("TotalHT") . '</td>';
        $html .= '<td style="text-align: right;">' . price($this->total_ht, 0, $this->langs) . '</td>';
        $html .= '</tr>';

        if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
            $tvaisnull = ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
            if (!$tvaisnull || empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL)) {
                // Taxes locales 1 avant TVA
                foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
                    if (in_array((string) $localtax_type, array('1', '3', '5'))) {
                        continue;
                    }

                    foreach ($localtax_rate as $tvakey => $tvaval) {
                        if ($tvakey != 0) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalLT1", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // Taxes locales 2 avant TVA
                foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
                    if (in_array((string) $localtax_type, array('1', '3', '5'))) {
                        continue;
                    }

                    foreach ($localtax_rate as $tvakey => $tvaval) {
                        if ($tvakey != 0) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalLT2", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // TVA
                foreach ($this->tva as $tvakey => $tvaval) {
                    if ($tvakey != 0) {
                        if ((float) $tvaval != 0) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalVAT", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate($tvakey, 1) . $tvacompl;

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // Taxes locales 1 après TVA
                foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
                    if (in_array((string) $localtax_type, array('2', '4', '6'))) {
                        continue;
                    }

                    foreach ($localtax_rate as $tvakey => $tvaval) {
                        if ($tvakey != 0) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalLT1", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // Taxes locales 2 après TVA
                foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
                    if (in_array((string) $localtax_type, array('2', '4', '6'))) {
                        continue;
                    }

                    foreach ($localtax_rate as $tvakey => $tvaval) {
                        if ($tvakey != 0) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalLT2", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate(abs($tvakey), 1) . $tvacompl;

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // Total TTC
                $html .= '<tr>';
                $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("TotalTTC") . '</td>';
                $html .= '<td style="background-color: #DCDCDC; text-align: right;">' . price($this->total_ttc, 0, $this->langs) . '</td>';
                $html .= '</tr>';
            }
        }

        if (method_exists($this->object, 'getSommePaiement')) {
            $deja_regle = $this->object->getSommePaiement(($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? 1 : 0);
        } else {
            $deja_regle = 0;
        }

        if (method_exists($this->object, 'getSumCreditNotesUsed')) {
            $creditnoteamount = $this->object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? 1 : 0);
        } else {
            $creditnoteamount = 0;
        }

        if (method_exists($this->object, 'getSumDepositsUsed')) {
            $depositsamount = $this->object->getSumDepositsUsed(($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? 1 : 0);
        } else {
            $depositsamount = 0;
        }

        if (isset($this->object->paye) && $this->object->paye) {
            $resteapayer = 0;
        } else {
            $resteapayer = price2num($this->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
        }

        if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) {
            $html .= '<tr>';
            $html .= '<td style="">' . $this->langs->transnoentities("Paid") . '</td>';
            $html .= '<td style="text-align: right;">' . price($deja_regle + $depositsamount, 0, $this->langs) . '</td>';
            $html .= '</tr>';

            if ($creditnoteamount) {
                $html .= '<tr>';
                $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("CreditNotes") . '</td>';
                $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($creditnoteamount, 0, $this->langs) . '</td>';
                $html .= '</tr>';
            }

            BimpTools::loadDolClass('compta/facture', 'facture');
            if (isset($this->object->close_code) && $this->object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
                $html .= '<tr>';
                $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("EscompteOfferedShort") . '</td>';
                $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($this->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $this->langs) . '</td>';
                $html .= '</tr>';
                $resteapayer = 0;
            }

            $html .= '<tr>';
            $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("RemainderToPay") . '</td>';
            $html .= '<td style="text-align: right; background-color: #DCDCDC;">' . price($resteapayer, 0, $this->langs) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</div>';
        $html .= '<br/>';

        return $html;
    }

    public function getAfterTotauxHtml()
    {
        if ($this->doc_type === 'bl_draft') {
            return '';
        }

        return parent::getAfterTotauxHtml();
    }
}
