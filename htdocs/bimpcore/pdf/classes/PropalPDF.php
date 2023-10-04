<?php

require_once __DIR__ . '/BimpCommDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

ini_set('display_errors', 1);

class PropalPDF extends BimpCommDocumentPDF
{

    public static $type = 'propal';
    public $propal = null;
    public $mode = "normal";
    public $signature_bloc = true;
    public $use_docsign = true;
    public $signature_bloc_label = 'Bon pour commande';

    public function __construct($db)
    {
        parent::__construct($db);

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        $this->typeObject = 'propal';

        $this->propal = new Propal($db);
    }

    protected function initData()
    {
        if (isset($this->object) && is_a($this->object, 'Propal')) {
            $this->bimpCommObject = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal', (int) $this->object->id);

            if (isset($this->object->id) && $this->object->id) {
                $this->propal = $this->object;
                if (isset($this->propal->socid) && $this->propal->socid) {
                    if (!isset($this->propal->thirdparty)) {
                        $this->propal->fetch_thirdparty();
                    }
                }

                global $user;

                $this->pdf->SetTitle($this->langs->convToOutputCharset($this->object->ref));
                $this->pdf->SetSubject($this->langs->transnoentities("CommercialProposal"));
                $this->pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $this->pdf->SetAuthor($this->langs->convToOutputCharset($user->getFullName($this->langs)));
                $this->pdf->SetKeyWords($this->langs->convToOutputCharset($this->object->ref) . " " . $this->langs->transnoentities("CommercialProposal") . " " . $this->langs->convToOutputCharset($this->object->thirdparty->name));

                if (is_null($this->propal->thirdparty) || !isset($this->propal->thirdparty->id) || !$this->propal->thirdparty->id) {
                    $this->errors[] = 'Aucun client renseigné pour cette proposition commerciale';
                }
            } else {
                $this->errors[] = 'Proposition commerciale invalide (ID absent)';
            }
        } else {
            $this->errors[] = 'Aucune proposition commerciale spécifiée';
        }

        parent::initData();
    }

    protected function initHeader()
    {
        parent::initHeader();

        if ($this->proforma) {
            $docName = 'Facture Proforma';
        } elseif (isset($this->restitution_sav) && $this->restitution_sav) {
            $docName = 'Bon de restitution';
        } else {
            $docName = $this->langs->transnoentities('CommercialProposal');
        }
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->propal->ref);

        $this->header_vars['doc_name'] = $docName;
        $this->header_vars['doc_ref'] = $docRef;
    }

    public function getDocInfosHtml()
    {
        global $conf, $db;

        $html = '';

        $html .= '<div>';

        // Réf client: 
        if ($this->propal->ref_client) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('RefCustomer') . ' : </span>' . $this->langs->convToOutputCharset($this->propal->ref_client) . '<br/>';
        }

        // Dates: 
        if (!empty($this->propal->date)) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('Date') . ' : </span>' . dol_print_date($this->propal->date, "day", false, $this->langs) . '<br/>';
        }

        if (!empty($this->propal->fin_validite)) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('DateEndPropal') . ' : </span>' . dol_print_date($this->propal->fin_validite, "day", false, $this->langs, true) . '<br/>';
        }

        // Code client: 
        if (isset($this->propal->thirdparty->code_client)) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('CustomerCode') . ' : </span>' . $this->langs->transnoentities($this->propal->thirdparty->code_client) . '<br/>';
        }

        // Objets liés:
        $linkedObjects = pdf_getLinkedObjects($this->propal, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                $refObject = '<span style="font-weight: bold;">' . $lo['ref_title'] . '</span> : ' . $lo['ref_value'];
                if (!empty($lo['date_value'])) {
                    $refObject .= ' / ' . $lo['date_value'];
                }

                $html .= $refObject . '<br/>';
            }
        }

        $html .= '</div>';

        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function getPaymentInfosHtml()
    {
        global $conf;

        $html = '<div style="font-size: 7px; line-height: 8px;">';
        $html .= '<table style="width: 100%" cellpadding="5">';

        // Date de livraison
        if (!empty($this->object->date_livraison)) {
            $html .= '<tr><td>';
            $html .= dol_print_date($this->object->date_livraison, "daytext", false, $this->langs, true) . '<br/>';
            $html .= '</td></tr>';
        } elseif ($this->object->availability_code || (isset($this->object->availability) && $this->object->availability)) {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("AvailabilityPeriod") . ': </strong>';
            $label = $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) != ('AvailabilityType' . $this->object->availability_code) ? $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) : $this->langs->convToOutputCharset($this->object->availability);
            $label = str_replace('\n', "\n", $label);
            $html .= $label;
            $html .= '</td></tr>';
        }

        // Conditions de paiement: 
        if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND) && ($this->object->cond_reglement_code || $this->object->cond_reglement)) {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong><br/>';
            $label = $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) != ('PaymentCondition' . $this->object->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) : $this->langs->convToOutputCharset($this->object->cond_reglement_doc);
            $label = str_replace('\n', "\n", $label);
            $html .= $label;
            $html .= '</td></tr>';
        }

        // Mode de paiement: 
        if ($this->object->mode_reglement_code && $this->object->mode_reglement_code != 'CHQ' && $this->object->mode_reglement_code != 'VIR') {
            $html .= '<tr><td>';
            $html .= '<strong>' . $this->langs->transnoentities("PaymentMode") . '</strong>:<br/>';
            $html .= $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) != ('PaymentType' . $this->object->mode_reglement_code) ? $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) : $this->langs->convToOutputCharset($this->object->mode_reglement);
            $html .= '</td></tr>';
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'CHQ') {

            if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
                    $html .= '<tr><td>';
                    if (!class_exists('Account')) {
                        require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    }
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

        $id_default_account = BimpCore::getConf('id_default_bank_account', (!empty($conf->global->FACTURE_RIB_NUMBER) ? $conf->global->FACTURE_RIB_NUMBER : 0));
        if (!empty($this->object->fk_account) || !empty($this->object->fk_bank) || $id_default_account) {
            $html .= '<tr><td>';
            $bankid = (!empty($this->object->fk_account) ? $this->object->fk_account : (!empty($this->object->fk_bank) ? $this->object->fk_bank : $id_default_account));

            $only_number = false;
            if (!empty($this->object->mode_reglement_code) && $this->object->mode_reglement_code !== 'VIR') {
                $only_number = true;
            }

            require_once(DOL_DOCUMENT_ROOT . "/compta/bank/class/account.class.php");
            $account = new Account($this->db);
            $account->fetch($bankid);
            $html .= $this->getBankHtml($account, $only_number);
            $html .= '</td></tr>';
        }

        $html .= '</table></div>';

        return $html;
    }

    public function getTargetInfosHtml()
    {
        $html = parent::getTargetInfosHtml();

        $contacts = $this->object->getIdContact('external', 'SHIPPING');

        if (is_array($contacts) && !empty($contacts)) {
            if (count($contacts) > 1) {
                $html .= '<br/><div class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; ">';
                $html .= '<span style="color: #' . $this->primary . '">' . 'Livraisons à :' . '</span></div>';
                $html .= 'Voir annexe';
            } else {
                $id_contact = (isset($contacts[0]) ? (int) $contacts[0] : 0);

                if ($id_contact) {
                    $html .= '<br/><div class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; ">';
                    $html .= '<span style="color: #' . $this->primary . '">' . 'Livraison à :' . '</span></div>';

                    $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', $id_contact);

                    if (BimpObject::objectLoaded($contact)) {
                        $html .= str_replace("\n", "<br/>", $this->thirdparty->nom . '<br/>' . pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $contact->dol_object, 1, 'target'));
                    } else {
                        $html .= '<span class="danger">Erreur: le contact #' . $id_contact . ' n\'existe plus</span>';
                    }
                }
            }
        }

        return $html;
    }

    public function renderAnnexes()
    {
        $contacts = $this->object->getIdContact('external', 'SHIPPING');

        if (is_array($contacts) && count($contacts) > 1) {
            $html = '';

            $html .= '<div style="font-size: 8px">';
            $html .= '<table cellspacing="10px">';

            $html .= '<tr>';
            $html .= '<td style="width: 50%">';

            $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
            $html .= '<tr>';
            $html .= '<td class="section_title" style="font-weight: bold; color: #' . $this->primary . '; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">Annexe : adresses de livraison</td>';
            $html .= '</tr>';

            foreach ($contacts as $id_contact) {
                $html .= '<tr>';
                $html .= '<td style="border-bottom: solid 1px #3D3D3D">';

                $contact = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $id_contact);
                if (!BimpObject::objectLoaded($contact)) {
                    $html .= '<span class="danger">Erreur: le contact #' . $id_contact . ' n\'existe plus</span>';
                    continue;
                } else {
                    $html .= '<span style="font-weight: bold">';
                    $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $contact->dol_object, 1, 'target');
                    $html .= '</span>';
                }

                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';

            $html .= '</td>';
            $html .= '</tr>';

            $html .= '</table>';
            $html .= '</div>';

            $this->writeContent($html);
        }
    }

    public function getBottomLeftHtml()
    {
        $html = '';
        $html .= parent::getBottomLeftHtml();

        if (BimpCore::isEntity('bimp') && $this->object->array_options['options_type'] != 'S') {
            $html .= '<table style="width: 100%" cellpadding="5"><tr><td style="width: 60%; border: solid 6px black; font-size: 7px;">Les produits éligibles* bénéficient gratuitement d\'une garantie commerciale de 3 ans chez OLYS<br/>*Voir les conditions en annexe.</td></tr></table>';
        }
        return $html;
    }
}
