<?php

require_once __DIR__ . '/BimpModelPDF.php';

class RelancePaiementPDF extends BimpModelPDF
{

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';
    public static $use_cgv = false;
    public $data = null;
    public $client = null;
    public $contact = null;
    public $content_html = '';
    public $extra_html = '';

    public function initData()
    {
        if (is_null($this->data)) {
            $this->errors[] = 'Données absentes';
        }

        if (!BimpObject::objectLoaded($this->client)) {
            $this->errors[] = 'Client absent';
        }
    }

    public function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;

        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $sizes = dol_getImageSize($logo_file, false);

            $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);

            $logo_width = $tabTaille[0];
            $logo_height = $tabTaille[1];
        }

        $header_right = '';

        if (BimpObject::objectLoaded($this->client)) {
            $logo = $this->client->getData('logo');
            $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $this->client->id . '/logos/' . $logo;
            if (file_exists($soc_logo_file)) {
                $sizes = dol_getImageSize($soc_logo_file, false);
                if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                    $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'] / 3, $sizes['height'] / 3, 200, 100);

                    $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                }
            }
        }

        $this->pdf->topMargin = 53;

        $this->header_vars = array(
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => $header_right,
            'primary_color' => $this->primary,
            'doc_ref'       => '',
            'doc_name'      => ''
        );
    }

    protected function initfooter()
    {
        $line1 = '';
        $line2 = '';

        global $conf;

        if ($this->footerCompany->name) {
            $line1 .= $this->langs->convToOutputCharset($this->footerCompany->name);
        }

        if ($this->footerCompany->forme_juridique_code) {
            $line1 .= " - " . $this->langs->convToOutputCharset(getFormeJuridiqueLabel($this->footerCompany->forme_juridique_code));
        }

        if ($this->footerCompany->capital) {
            $captital = price2num($this->footerCompany->capital);
            if (is_numeric($captital) && $captital > 0) {
                $line1 .= ($line1 ? " au " : "") . $this->langs->transnoentities("CapitalOf", price($captital, 0, $this->langs, 0, 0, 0, $conf->currency));
            } else {
                $line1 .= ($line1 ? " au " : "") . $this->langs->transnoentities("CapitalOf", $this->footerCompany->capital, $this->langs);
            }
        }

        if ($this->footerCompany->address) {
            $line1 .= " - " . $this->footerCompany->address . " - " . $this->footerCompany->zip . " " . $this->footerCompany->town . " - Tél " . $this->footerCompany->phone;
        }

        if ($this->footerCompany->idprof1 && ($this->footerCompany->country_code != 'FR' || !$this->footerCompany->idprof2)) {
            $field = $this->langs->transcountrynoentities("ProfId1", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof1);
        }

        if ($this->footerCompany->idprof2) {
            $field = $this->langs->transcountrynoentities("ProfId2", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof2);
        }

        if ($this->footerCompany->idprof3) {
//            $field = $this->langs->transcountrynoentities("ProfId3", $this->footerCompany->country_code);
            $field = 'APE';
//            if (preg_match('/\((.*)\)/i', $field, $reg)) {
//                $field = $reg[1];
//                
//            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof3);
        }

        if ($this->footerCompany->idprof4) {
            $field = $this->langs->transcountrynoentities("ProfId4", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof4);
        }

        if ($this->footerCompany->idprof5) {
            $field = $this->langs->transcountrynoentities("ProfId5", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof5);
        }

        if ($this->footerCompany->idprof6) {
            $field = $this->langs->transcountrynoentities("ProfId6", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof6);
        }
        // IntraCommunautary VAT
        if ($this->footerCompany->tva_intra != '') {
            $line2 .= ($line2 ? " - " : "") . $this->langs->transnoentities("VATIntraShort") . " : " . $this->langs->convToOutputCharset($this->footerCompany->tva_intra);
        }

        $this->footer_vars = array(
            'footer_line_1' => $line1,
            'footer_line_2' => $line2,
        );
    }

    public function renderContent()
    {
        $this->renderDocInfos();

        $relanceIdx = (int) BimpTools::getArrayValueFromPath($this->data, 'relance_idx', 0);
        $total_debit = BimpTools::getArrayValueFromPath($this->data, 'total_debit', 0);
        $total_credit = BimpTools::getArrayValueFromPath($this->data, 'total_credit', 0);
        $solde_ttc = $total_debit - $total_credit;

        if (!$relanceIdx) {
            $this->errors[] = 'Numéro de relance non spécifié';
        } else {
            $top = '';
            $bottom = '';
            $extra = '';

            $commercial = null;
            if (BimpObject::objectLoaded($this->client)) {
                $commerciaux = $this->client->getCommerciauxArray();

                foreach ($commerciaux as $id_commercial => $comm_label) {
                    $commercial = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_commercial);
                    if (BimpObject::objectLoaded($commercial)) {
                        break;
                    }
                }
            }

            $signature = '<table><tr><td style="width: 50%"></td><td>';
//            if (BimpObject::objectLoaded($commercial)) {
//                $signature .= $commercial->getName() . '<br/>';
//                $phone = ($commercial->getData('office_phone') ? $commercial->getData('office_phone') : $commercial->getData('user_mobile'));
//                if ($phone) {
//                    $signature .= 'Tél : ' . $phone . '<br/>';
//                }
//                if ($commercial->getData('email')) {
//                    $signature .= 'E-mail: ' . $commercial->getData('email');
//                }
//            } else {
            // Todo utiliser config en base.
            $signature .= 'Le service recouvrement <br/>';
//                $signature .= 'Tél : 04 75 81 46 48 (taper 5) <br/>';
//                $signature .= 'E-mail : recouvrementolys@bimp.fr<br/>';
//            }
            $signature .= '</td></tr></table>';

//            $extra = '<br/>Merci de joindre ce document à votre règlement.<br/>';

            $penalites = '<div style="font-size: 6px;font-style: italic">';
            $penalites .= 'Des pénalités de retard sont dues à défaut de règlement le jour suivant la date de paiement qui figure sur la facture. ';
            $penalites .= 'Le taux d’intérêt de ces pénalités de retard est de cinq fois le taux d’intérêt légal. ';
            $penalites .= 'Conformément aux dispositions de l\'article L.441-6 du code de commerce, tout professionnel en situation de retard de paiement ';
            $penalites .= 'est de plein droit débiteur, à l\'égard du créancier, d\'une indemnité forfaitaire pour frais de recouvrement de 40 euros, ';
            $penalites .= 'sans écarter la possibilité d’appliquer une indemnisation complémentaire.';
            $penalites .= '</div>';

            $id_account = (int) BimpCore::getConf('relance_paiements_id_bank_account', 0);

            $paiement_infos = '';
            if ($id_account) {
                require_once(DOL_DOCUMENT_ROOT . "/compta/bank/class/account.class.php");
                $account = new Account($this->db);
                $account->fetch($id_account);

                if (BimpObject::objectLoaded($account)) {
                    $paiement_infos .= '<div style="font-size: 6px;">';
                    $paiement_infos .= '<span style="font-style: italic;">Si règlement par virement merci d’utiliser le compte bancaire suivant:</span><br/>';
                    $paiement_infos .= $this->getBankHtml($account, true);
                    $paiement_infos .= '</div>';
                }
            }

            $top .= '<table>';
            $top .= '<tr>';
            $top .= '<td style="width: 70%"></td>';
            $top .= '<td style="width: 30%">Le ' . date('d / m / Y') . '</td>';
            $top .= '</tr>';
            $top .= '</table>';
            switch ($relanceIdx) {
                case 1:
                    $top .= '<span style="font-weight: bold;font-size: 9px">LETTRE DE RAPPEL</span><br/>';
                    $top .= '<span style="font-weight: bold">Client: </span>' . $this->client->getRef() . ' - ' . $this->client->getName() . '<br/>';
                    if ((string) $this->client->getData('code_compta')) {
                        $top .= '<span style="font-weight: bold">Code compta: </span>' . $this->client->getData('code_compta') . '<br/><br/>';
                    }
                    $top .= '<br/>';
                    $top .= 'Cher client, <br/><br/>';
                    $top .= 'Sauf erreur de notre part, l\'examen de votre compte fait apparaître un solde débiteur dont détail ci-après: <br/><br/>';

                    $bottom .= 'Sans doute s\'agit-il d\'un simple oubli de votre part.<br/><br/>';
                    $bottom .= 'Nous vous remercions de bien vouloir régulariser cette situation dans les meilleurs délais.<br/><br/>';
                    $bottom .= 'Pour tout retour ou question à ce sujet, merci de bien vouloir contacter votre interlocuteur dont les coordonnées sont indiquées en en-tête.<br/><br/>';
                    $bottom .= 'Si toutefois votre règlement s\'est croisé avec cette relance, merci de ne pas tenir compte du présent rappel.<br/><br/>';
                    $bottom .= 'Dans l\'attente, veuillez agréer, cher client, l\'assurance de nos sincères salutations.<br/><br/>';
                    break;

                case 2:
                    $top .= '<span style="font-weight: bold;">2<sup>ème</sup> LETTRE DE RAPPEL</span><br/><br/>';
                    $top .= '<span style="font-weight: bold">Client: </span>' . $this->client->getRef() . ' - ' . $this->client->getName() . '<br/>';
                    if ((string) $this->client->getData('code_compta')) {
                        $top .= '<span style="font-weight: bold">Code compta: </span>' . $this->client->getData('code_compta') . '<br/>';
                    }
                    $top .= '<br/>';
                    $top .= 'Cher client, <br/><br/>';
                    $top .= 'Malgré notre 1<sup>er</sup> rappel, nous sommes toujours dans l\'attente de votre règlement dont détail ci-après : <br/><br/>';

                    $bottom .= 'Nous vous prions de bien vouloir régulariser cette situation dans les meilleurs délais ou nous communiquer les raisons qui s\'y opposent.<br/><br/>';
                    $bottom .= 'Pour tout retour ou question à ce sujet, merci de bien vouloir contacter votre interlocuteur dont les coordonnées sont indiquées en en-tête.<br/><br/>';
                    $bottom .= 'Si toutefois votre règlement s\'est croisé avec cette relance, merci de ne pas tenir compte du présent rappel.<br/><br/>';
                    $bottom .= 'Dans l\'attente, veuillez agréer, cher client, l\'assurance de nos sincères salutations.<br/><br/>';
                    break;


                case 3:
                    $top .= '<span style="font-weight: bold;">3<sup>ème</sup> LETTRE DE RAPPEL</span><br/><br/>';
                    $top .= '<span style="font-weight: bold">Client: </span>' . $this->client->getRef() . ' - ' . $this->client->getName() . '<br/>';
                    if ((string) $this->client->getData('code_compta')) {
                        $top .= '<span style="font-weight: bold">Code compta: </span>' . $this->client->getData('code_compta') . '<br/>';
                    }
                    $top .= '<br/>';
                    $top .= 'Madame, Monsieur<br/><br/>';
                    $top .= 'Malgré l\'envoi de nos deux lettres de rappel, vous semblez nous être toujours redevable des sommes dont détail ci-après :';

                    $bottom .= 'Le total représente à ce jour un solde débiteur de <span style="font-weight: bold">' . BimpTools::displayMoneyValue($solde_ttc, '') . ' € TTC</span>.<br/><br/>';
                    $bottom .= 'En conséquence, nous vous mettons en demeure de bien vouloir régulariser cette situation dès réception du présent courrier.<br/><br/>';
                    $bottom .= 'Pour tout retour ou question à ce sujet, merci de bien vouloir contacter votre interlocuteur dont les coordonnées sont indiquées en en-tête.<br/><br/>';
                    $bottom .= 'Dans l\'attente, veuillez agréer, Madame, Monsieur, l\'assurance de nos sincères salutations.<br/><br/>';
                    break;

                case 4:
                    $dt = new DateTime();
                    $dt->add(new DateInterval('P5D'));

                    $top .= '<span style="font-weight: bold;">Lettre recommandée avec AR</span><br/><br/>';
                    $top .= '<span style="font-weight: bold;">Objet: mise en demeure de payer</span><br/><br/>';
                    $top .= '<span style="font-weight: bold">Client: </span>' . $this->client->getRef() . ' - ' . $this->client->getName() . '<br/>';
                    if ((string) $this->client->getData('code_compta')) {
                        $top .= '<span style="font-weight: bold">Code compta: </span>' . $this->client->getData('code_compta') . '<br/>';
                    }
                    $top .= '<br/>';
                    $top .= 'Madame, Monsieur<br/><br/>';
                    $top .= 'Nous faisons suite à plusieurs relances concernant le règlement de commande(s) passée(s) auprès de notre société. ';
                    $top .= 'Nous constatons malheureusement que vous n\'avez toujours pas procédé au règlement des pièce(s) comptable(s) suivantes(s) :<br/><br/>';

                    $bottom .= 'Le total représente à ce jour un solde débiteur de <span style="font-weight: bold">' . BimpTools::displayMoneyValue($solde_ttc, '') . ' € TTC</span>.<br/><br/>';
                    $bottom .= 'Aussi, par la présente, nous vous mettons en demeure de nous verser à titre principal, la somme de <span style="font-weight: bold">' . BimpTools::displayMoneyValue($solde_ttc, '') . ' € TTC</span>.<br/><br/>';
                    $bottom .= 'Cette somme sera majorée des intérêts de retard applicables selon nos conditions générales de vente et des frais de recouvrement engagé par Olys.<br/><br/>';
                    $bottom .= 'Si dans un délai de 5 jours à compter de la réception de ce courrier recommandé, ';
                    $bottom .= 'vous ne vous êtes toujours pas acquitté de votre obligation, nous saisirons la juridiction compétente afin d\'obtenir le paiement des sommes susvisées.<br/><br/>';
                    $bottom .= 'Pour tout retour ou question à ce sujet, merci de bien vouloir contacter votre interlocuteur dont les coordonnées sont indiquées en en-tête.<br/><br/>';
                    $bottom .= 'Veuillez agréer, Madame, Monsieur, nos salutations distinguées.<br/><br/>';
                    break;

                case 5:
                    $paiement_infos = '';
                    break;
            }

            $html = '<div style="font-size: 7px;">';
            $html .= $top;
            $html .= '</div>';

            $this->writeContent($html);

            $this->content_html = $this->getCommercialInfosHtml(false) . '<br/><br/>';
            $this->content_html .= $html;

            $this->renderDataTable();

            $html = '<br/><div style="font-size: 7px;">';
            $html .= $bottom . '<br/>' . $signature;

            $this->content_html .= $html;

            if (in_array($relanceIdx, array(3, 4))) {
                $html .= '<br/><br/>' . $extra . $penalites;
                $this->extra_html .= $extra . $penalites;
            }

            $html .= '</div>';
            $this->content_html . '</div>';
            
            $this->writeContent($html);

            if ($paiement_infos) {
                $this->extra_html .= '<br/>' . $paiement_infos;
                $this->writeFullBlock($paiement_infos);
            }
        }
    }

    public function renderDocInfos()
    {
        $html = '';

        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td style="width: 55%"></td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
        $html .= '<span style="color: #' . $this->primary . '">DESTINATAIRE</span></td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 55%">';
        $html .= $this->getDocInfosHtml();
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 40%">';

        $html .= $this->getTargetInfosHtml();

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function getDocInfosHtml()
    {
        $html = '';

        global $conf, $mysoc;

        // Code client: 
        if ($this->client->getData('code_client')) {
            $html .= '<span style="font-weight: bold;">' . $this->langs->transnoentities('CustomerCode') . ' : </span>' . $this->client->getData('code_client') . '<br/>';
        }

        // Num TVA Client: 
        if ($this->client->getData('tva_intra')) {
            $html .= '<span style="font-weight: bold;">N° TVA client: </span>' . $this->client->getData('tva_intra') . '<br/>';
        }

        $html .= $this->getCommercialInfosHtml();

        return $html;
    }

    public function getCommercialInfosHtml($with_border = true)
    {
        $html = '';

        // Commercial:         
        $commerciaux = array();
        $signataires = array();

        foreach ($this->data['factures'] as $id_fac => $fac) {
            $contacts = $fac->dol_object->getIdContact('internal', 'SALESREPFOLL');
            foreach ($contacts as $id_contact) {
                if (!in_array($id_contact, $commerciaux)) {
                    $commerciaux[] = (int) $id_contact;
                }
            }

            $contacts = $fac->dol_object->getIdContact('internal', 'SALESREPSIGN');
            foreach ($contacts as $id_contact) {
                if (!in_array($id_contact, $signataires)) {
                    $signataires[] = (int) $id_contact;
                }
            }
        }

        $relanceIdx = (int) BimpTools::getArrayValueFromPath($this->data, 'relance_idx', 0);
        $users = (!empty($commerciaux) ? $commerciaux : $signataires);

        if (!empty($users) || $relanceIdx == 4) {
            $nUsers = count($users);
//            if ($relanceIdx == 4) {
                $nUsers++;
//            }
            $label = 'Interlocuteur' . ($nUsers > 1 ? 's' : '');

            $html .= '<div class="row" style="' . ($with_border ? ' border-top: solid 1px #' . $this->primary : '') . '">';
            $html .= '<span style="font-weight: bold; color: #' . $this->primary . ';">';
            $html .= $label . ' :</span>';

            foreach ($users as $id_user) {
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                if (BimpObject::objectLoaded($user)) {
                    $html .= '<br/>' . $user->getName();
                    if ($user->getData('email')) {
                        $html .= '<br/><span style="font-size: 6px;">' . $user->getData('email') . '</span>';
                    }
                    if ($user->getData('office_phone')) {
                        $html .= '<span style="font-size: 6px;">' . ($user->getData('email') ? ' - ' : '<br/>') . $user->getData('office_phone') . '</span>';
                    }
                }
            }
            
//            if ($relanceIdx == 4) {
                if (!empty($users)) {
                    $html .= '<br/>et';
                }
                $html .= '<br/>Service Recouvrement<br/>recouvrementolys@bimp.fr - 04 82 90 20 29';
//            }

            $html .= '</div>';
        }

        return $html;
    }

    public function getTargetInfosHtml()
    {
        if (count($this->errors)) {
            return '';
        }

        global $langs;

        $html = "";
        $nomsoc = pdfBuildThirdpartyName($this->client->dol_object, $this->langs);
        if (is_null($this->contact) || $this->contact->dol_object->getFullName($langs) != $nomsoc) {
            $html .= $nomsoc . "<br/>";
            if (!is_null($this->contact) && $this->client->dol_object->name_alias != "")
                $html .= $this->client->dol_object->name_alias . "<br/>";
        }

        $html .= pdf_build_address($this->langs, $this->fromCompany, $this->client->dol_object, (!is_null($this->contact) ? $this->contact->dol_object : ''), !is_null($this->contact) ? 1 : 0, 'target');

        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }

    public function renderDataTable()
    {
        $rows = BimpTools::getArrayValueFromPath($this->data, 'rows', array());
        $total_debit = BimpTools::getArrayValueFromPath($this->data, 'total_debit', 0);
        $total_credit = BimpTools::getArrayValueFromPath($this->data, 'total_credit', 0);
        $solde = $total_debit - $total_credit;

        $table = new BimpPDF_Table($this->pdf);

        $table->addCol('date', 'Date', 18);
        $table->addCol('fac', 'N° Facture', 25);
//        $table->addCol('comm', 'N° Commande', 22);
        $table->addCol('fac_ref_client', 'Reférences', 22);
        $table->addCol('lib', 'Libellé');
        $table->addCol('debit', 'Débit', 22);
        $table->addCol('credit', 'Crédit', 22);
        $table->addCol('echeance', 'Echéance', 18);
        $table->addCol('retard', 'JR', 10, 'text-align: center', '', 'text-align: center');

        $table->rows = $rows;

        $table->rows[] = array(
            'date'   => array('content' => 'Total', 'colspan' => 4, 'style' => 'text-align: right;font-weight: bold;'),
            'debit'  => array('content' => ($total_debit ? BimpTools::displayMoneyValue($total_debit, '') . ' €' : ''), 'style' => 'font-weight: bold;'),
            'credit' => array('content' => ($total_credit ? BimpTools::displayMoneyValue($total_credit, '') . ' €' : ''), 'style' => 'font-weight: bold;')
        );

        $table->rows[] = array(
            'date'  => array('content' => 'Solde', 'colspan' => 4, 'style' => 'text-align: right;font-weight: bold;'),
            'debit' => array('content' => ($solde ? BimpTools::displayMoneyValue($solde, '') . ' €' : ''), 'colspan' => 2, 'style' => 'font-weight: bold;')
        );

        $before_html .= '<div style="text-align: right; font-size: 6px; font-style: italic">';
        $before_html .= 'JR: Jours de retard';
        $before_html .= '</div>';

        $this->writeContent($before_html);
        $table->write();

        $html = '';

        $html .= '<style>';
        $html .= 'table.border {border-collapse: collapse;}';
        $html .= 'table.border th,table.border td {padding: 5px;text-align: left;min-width: 120px;}';
        $html .= 'table.border td {border: 1px solid #DDDDDD;}';
        $html .= '</style>';
        $html .= '<table class="border">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #fff">';
        $html .= '<th colspan="8" style="border: none; text-align: right;">' . $before_html . '</th>';
//        $html .= '<th style="border: none">' . $before_html . '</th>';
        $html .= '</tr>';
        $html .= '<tr style="color: #fff; font-weight: bold; background-color: #' . $this->primary . '">';
        $html .= '<th>Date</th>';
        $html .= '<th>N° Facture</th>';
        $html .= '<th>Références</th>';
        $html .= '<th>Libellé</th>';
        $html .= '<th>Débit</th>';
        $html .= '<th>Crédit</th>';
        $html .= '<th>Echéance</th>';
        $html .= '<th>JR</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($table->getCols() as $key => $col_data) {
                $html .= '<td>';
                if (isset($row[$key])) {
                    $html .= $row[$key];
                }
                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '<tr>';
        $html .= '<td colspan="4" style="font-weight: bold;text-align: right">Total</td>';
        $html .= '<td style="font-weight: bold;">' . ($total_debit ? BimpTools::displayMoneyValue($total_debit, '') . ' €' : '') . '</td>';
        $html .= '<td style="font-weight: bold;">' . ($total_credit ? BimpTools::displayMoneyValue($total_credit, '') . ' €' : '') . '</td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td colspan="4" style="font-weight: bold;text-align: right">Solde</td>';
        $html .= '<td colspan="2" style="font-weight: bold;">' . ($solde ? BimpTools::displayMoneyValue($solde, '') . ' €' : '') . '</td>';
        $html .= '<td></td>';
        $html .= '<td></td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';

        $this->content_html .= $html;
    }
}
