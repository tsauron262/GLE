<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

class BimpDocumentPDF extends BimpModelPDF
{

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';
    public $bimpCommObject = null;
    public $thirdparty = null;
    public $contact = null;
    public $total_remises = 0;
    public $localtax1 = array();
    public $localtax2 = array();
    public $acompteHt = 0;
    public $acompteTtc = 0;
    public $acompteTva20 = 0;
    public $tva = array();
    public $ht = array();
    public $hideReduc = false;
    public $hideTtc = false;
    public $hideTotal = false;
    public $hideRef = false;
    public $periodicity = 0;
    public $nbPeriods = 0;
    public $proforma = 0;
    public $maxLogoWidth = 120; // px
    public $maxLogoHeight = 65; // px
    public $totals = array("DEEE" => 0, "RPCP" => 0);
    public $target_label = '';
    public $after_totaux_label = '';

    public function __construct($db)
    {
        parent::__construct($db, 'P', 'A4');
        BimpObject::loadClass('bimpcommercial', 'BimpComm');

        $this->target_label = $this->langs->transnoentities('BillTo');
    }

    // Initialisation:

    protected function initData()
    {
        if (!count($this->errors)) {
            if (!is_null($this->object) && isset($this->object->id) && $this->object->id) {

                if (isset($this->object->array_options['options_pdf_hide_price'])) {
                    $this->hidePrice = true;
                    if ($this->typeObject != 'invoice')
                        $this->hideTotal = true;
                }
                if (isset($this->object->array_options['options_pdf_hide_reduc'])) {
                    $this->hideReduc = (int) $this->object->array_options['options_pdf_hide_reduc'];
                }
                if (isset($this->object->array_options['options_pdf_hide_ttc'])) {
                    $this->hideTtc = (int) $this->object->array_options['options_pdf_hide_ttc'];
                }
                if (isset($this->object->array_options['options_pdf_hide_total'])) {
                    $this->hideTotal = (int) $this->object->array_options['options_pdf_hide_total'];
                }
                if (isset($this->object->array_options['options_pdf_hide_ref'])) {
                    $this->hideRef = (int) $this->object->array_options['options_pdf_hide_ref'];
                }
                if (isset($this->object->array_options['options_pdf_periodicity'])) {
                    $this->periodicity = (int) $this->object->array_options['options_pdf_periodicity'];
                }
                if (isset($this->object->array_options['options_pdf_periods_number'])) {
                    $this->nbPeriods = (int) $this->object->array_options['options_pdf_periods_number'];
                }
                if (isset($this->object->array_options['options_pdf_proforma'])) {
                    $this->proforma = (int) $this->object->array_options['options_pdf_proforma'];
                }

                if (is_null($this->contact)) {
                    $contacts = $this->object->getIdContact('external', 'CUSTOMER');
                    if (isset($contacts[0]) && $contacts[0]) {
                        BimpTools::loadDolClass('contact');
                        $contact = new Contact($this->db);
                        if ($contact->fetch((int) $contacts[0]) > 0) {
                            $this->contact = $contact;
                        }
                    }
                }

                if (!is_null($this->contact)) {
                    if ((int) $this->contact->socid !== $this->object->socid) {
                        $this->thirdparty = $this->contact;
                    }
                }

                if (is_null($this->thirdparty)) {
                    if (!isset($this->object->thirdparty)) {
                        $this->object->fetch_thirdparty();
                    }
                    if (isset($this->object->thirdparty)) {
                        $this->thirdparty = $this->object->thirdparty;
                    }
                }

                if (method_exists($this->object, 'fetch_optionals')) {
                    $this->object->fetch_optionals();
                    if (isset($this->object->array_options['options_entrepot']) && $this->object->array_options['options_entrepot'] > 0) {
                        $entrepot = new Entrepot($this->db);
                        $entrepot->fetch($this->object->array_options['options_entrepot']);
                        if ($entrepot->address != "" && $entrepot->town != "") {
                            $this->fromCompany->zip = $entrepot->zip;
                            $this->fromCompany->address = $entrepot->address;
                            $this->fromCompany->town = $entrepot->town;

                            if ($this->fromCompany->name == "Bimp Groupe Olys")
                                $this->fromCompany->name = "Bimp Olys SAS";

                            if ($entrepot->ref == "PR") {//patch new adresse
                                $this->fromCompany->fromCompany->zip = "69760";
                                $this->fromCompany->address = "2 rue des Erables CS 21055  ";
                                $this->fromCompany->town = "LIMONEST";
                                $this->fromCompany->zip = "69760";
                            }
                        }
                    }
                }
            }
        }
    }

    protected function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;


        if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('R', 'C', 'ME', 'CO'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_PRO.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }
        if (
                isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('S'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_SAV.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }
        if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('E'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_EDUC.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }

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

        if (isset($this->object->socid) && (int) $this->object->socid) {
            if (isset($this->object->thirdparty->logo) && (string) $this->object->thirdparty->logo) {
                $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $this->object->thirdparty->id . '/logos/' . $this->object->thirdparty->logo;
                if (file_exists($soc_logo_file)) {
                    $sizes = dol_getImageSize($soc_logo_file, false);
                    if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {

                        $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'] / 3, $sizes['height'] / 3, 200, 100);



                        $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                    }
                }
            }
        }

        $this->pdf->topMargin = 50;

        $this->header_vars = array(
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => $header_right,
            'primary_color' => BimpCore::getParam('pdf/primary', '000000')
        );
    }

    public function calculeWidthHieghtLogo($width, $height, $maxWidth, $maxHeight)
    {
        if ($width > $maxWidth) {
            $height = round(($maxWidth / $width) * $height);
            $width = $maxWidth;
        }

        if ($height > $maxHeight) {
            $width = round(($maxHeight / $height) * $width);
            $height = $maxHeight;
        }
        return array($width, $height);
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

    // Rendus:

    protected function renderContent()
    {
        if (is_object($this->thirdparty) || is_object($this->contact))
            $this->renderDocInfos($this->thirdparty, $this->contact);
        $this->renderTop();
        $this->renderBeforeLines();
        $this->renderLines();
        $this->renderAfterLines();
        $this->renderBottom();
        $this->renderAfterBottom();

        $cur_page = (int) $this->pdf->getPage();
        $num_pages = (int) $this->pdf->getNumPages();

        if (($num_pages - $cur_page) === 1) {
            $this->pdf->deletePage($num_pages);
        }
    }

    public function getCommercialInfosHtml()
    {
        $html = '';

        global $conf, $mysoc;
        // Commercial: 
//        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
        $comm1 = $comm2 = 0;
        $contacts = array();
        if (method_exists($this->object, 'getIdContact')) {
            $contacts = $this->object->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $comm1 = $contacts[0];
            }

            $contacts = $this->object->getIdContact('internal', 'SALESREPSIGN');
            if (count($contacts)) {
                $comm2 = $contacts[0];
            }
        }

        $primary = BimpCore::getParam('pdf/primary', '000000');

        $label = 'Interlocuteur';
        $usertmp = null;

        if ($comm1 > 0) {
            if ($comm2 > 0 && $comm1 != $comm2) {
                $label .= ' client';
            }
            $usertmp = new User($this->db);
            $usertmp->fetch($comm1);

            if (BimpObject::objectLoaded($usertmp)) {
                $html .= '<div class="row" style="border-top: solid 1px #' . $primary . '">';
                $html .= '<span style="font-weight: bold; color: #' . $primary . ';">';
                $html .= $label . ' :</span>';
                $html .= '<br/>' . $usertmp->getFullName($this->langs, 0, -1, 20);
                if ($usertmp->email) {
                    $html .= '<br/><span style="font-size: 7px;">' . $usertmp->email . '</span>';
                }
                if ($usertmp->office_phone) {
                    $html .= '<span style="font-size: 7px;">' . ($usertmp->email ? ' - ' : '<br/>') . $usertmp->office_phone . '</span>';
                }
                $html .= '</div>';
            }
        }

        if ($comm2 > 0) {
            if (!$comm1 || ($comm1 > 0 && $comm1 != $comm2)) {
                if ($comm1 > 0) {
                    $label = 'Emetteur devis';
                } else {
                    $label = 'Interlocuteur';
                }

                $usertmp = new User($this->db);
                $usertmp->fetch($comm2);

                if (BimpObject::objectLoaded($usertmp)) {
                    $html .= '<div class="row" style="border-top: solid 1px #' . $primary . '">';
                    $html .= '<span style="font-weight: bold; color: #' . $primary . ';">';
                    $html .= $label . ' :</span>';
                    $html .= '<br/>' . $usertmp->getFullName($this->langs, 0, -1, 20);
                    if ($usertmp->email) {
                        $html .= '<br/><span style="font-size: 7px;">' . $usertmp->email . '</span>';
                    }
                    if ($usertmp->office_phone) {
                        $html .= '<span style="font-size: 7px;">' . ($usertmp->email ? ' - ' : '<br/>') . $usertmp->office_phone . '</span>';
                    }
                    $html .= '</div>';
                }
            }
        }


        if (isset($usertmp)) {
            // Je suis pas sûr que ça marche ça, à ce stade le fromcompany est déjà printé:
            if ($usertmp->office_phone != "")
                $this->fromCompany->phone = $usertmp->office_phone;
            if ($usertmp->email != "")
                $this->fromCompany->email = $usertmp->email;
        }

        return $html;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= $this->getCommercialInfosHtml();

        return $html;
    }

    public function getSenderInfosHtml()
    {
        $html = '<br/><span style="font-size: 16px; color: #' . BimpCore::getParam('pdf/primary', '000000') . ';">' . $this->fromCompany->name . '</span><br/>';
        $html .= '<span style="font-size: 9px">' . $this->fromCompany->address . '<br/>' . $this->fromCompany->zip . ' ' . $this->fromCompany->town . '<br/>';
        if ($this->fromCompany->phone) {
            $html .= 'Tél. : ' . $this->fromCompany->phone . '<br/>';
        }
        $html .= '</span>';
        $html .= '<span style="color: #' . BimpCore::getParam('pdf/primary', '000000') . '; font-size: 8px;">';
        if ($this->fromCompany->url) {
            $html .= $this->fromCompany->url . ($this->fromCompany->email ? ' - ' : '');
        }
        if ($this->fromCompany->email) {
            $html .= $this->fromCompany->email;
        }
        $html .= '</span>';
        return $html;
    }

    public function getTargetInfosHtml()
    {
        global $langs;

        $nomsoc = pdfBuildThirdpartyName($this->thirdparty, $this->langs);
        if (is_null($this->contact) || $this->contact->getFullName($langs) != $nomsoc)
            $html = $nomsoc . "<br/>";

//        if ($this->contact < 1)
//            $html = '<div class="bold">' . pdfBuildThirdpartyName($this->thirdparty, $this->langs) . '</div>';
//        elseif (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))
//            $this->contact->firstname = pdfBuildThirdpartyName($this->thirdparty, $this->langs) . '<br/>' . $this->contact->firstname;
//        else
//            $html = "";
//        if (strtoupper($this->thirdparty->lastname) == strtoupper($this->thirdparty->socname)) {
//            $this->thirdparty->lastname = "";
//        }

        $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contact, !is_null($this->contact) ? 1 : 0, 'target');
        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }

    public function renderDocInfos()
    {
        $html = '';

//        if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
//            $thirdparty = $object->contact;
//        } else {
//            $thirdparty = $object->thirdparty;
//        }

        $primary = BimpCore::getParam('pdf/primary', '000000');

        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td style="width: 55%"></td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td class="section_title" style="width: 40%; border-top: solid 1px #' . $primary . '; border-bottom: solid 1px #' . $primary . '">';
        $html .= '<span style="color: #' . $primary . '">' . strtoupper($this->target_label) . '</span></td>';
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

        if (isset($this->contactFinal) && is_object($this->contactFinal)) {
            $html .= '<br/><div class="section_title" style="width: 40%; border-top: solid 1px #' . $primary . '; ">';
            $html .= '<span style="color: #' . $primary . '">' . ('Client Final :') . '</span></div>';
            $html .= '';
            $html .= str_replace("\n", '<br/>', pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contactFinal, !is_null($this->contactFinal) ? 1 : 0, 'target'));
        }

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderTop()
    {
        if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
            $this->writeContent('<p style="font-size: 10px">Objet : <strong>' . $this->object->array_options['options_libelle'] . '</strong></p>');
        }

        if (isset($this->object->note_public) && $this->object->note_public) {
            $html = '<div style="font-size: 7px; line-height: 8px;">';
            $html .= $this->object->note_public;
            $html .= '</div>';

            if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
                $this->pdf->addVMargin(2);
            }

            $this->writeContent($html);
        }
    }

    public function renderBeforeLines()
    {
        
    }

    public function getLineDesc($line, Product $product = null, $hide_product_label = false)
    {
        $desc = '';
        if (!is_null($product)) {
            if (!$this->hideRef) {
                $desc .= $product->ref;
            }

            if (!$hide_product_label) {
                $desc .= ($desc ? ' - ' : '') . $product->label;
            }

            if ($product->type == 1) {
                if ($line->date_start) {
                    if (!$line->date_end) {
                        $desc .= '<br/>A partir du ';
                    } else {
                        $desc .= '<br/>Du ';
                    }
                    $desc .= date('d / m / Y', $line->date_start);
                }
                if ($line->date_end) {
                    if (!$line->date_start) {
                        $desc .= '<br/>Jusqu\'au ';
                    } else {
                        $desc .= ' au ';
                    }
                    $desc .= date('d / m / Y', $line->date_end);
                }
            }
        }

        if (!is_null($line->desc) && $line->desc) {
            $line_desc = $line->desc;
            if (!is_null($product)) {
                if (preg_match('/^' . $product->label . '(.*)$/', $line_desc, $matches)) {
                    $line_desc = $matches[0];
                }
                $line_desc = str_replace("  ", " ", $line_desc);
                $product->label = str_replace("  ", " ", $product->label);
                if (stripos($line_desc, $product->label) !== false)
                    $line_desc = str_replace($product->label, "", $line_desc);
            }
            if ($line_desc) {
                $desc .= ($desc ? (strlen($desc) > 20 ? '<br/>' : ' - ') : '') . $line_desc;
            }
        }

        $desc = preg_replace("/(\n)?[ \s]*<[ \/]*br[ \/]*>[ \s]*(\n)?/", '<br/>', $desc);
        $desc = str_replace("\n", '<br/>', $desc);
        return $desc;
    }

    public function renderLines()
    {
        global $conf;

        $table = new BimpPDF_AmountsTable($this->pdf);

        if ($this->hidePrice)
            $table->setCols(array('desc'));

        if (method_exists($this, 'setAmountsTableParams')) {
            $this->setAmountsTableParams($table);
        }

        $remise_globale = 0;
        $remise_globale_line_rate = 0;

        $bimpLines = array();

        if (BimpObject::objectLoaded($this->bimpCommObject) && is_a($this->bimpCommObject, 'BimpComm')) {
            if ($this->bimpCommObject->field_exists('remise_globale')) {
                $remise_globale = (float) $this->bimpCommObject->getData('remise_globale');
                $remise_globale_line_rate = (float) $this->bimpCommObject->getRemiseGlobaleLineRate();
            }
            foreach ($this->bimpCommObject->getChildrenObjects('lines') as $bimpLine) {
                $bimpLines[(int) $bimpLine->getData('id_line')] = $bimpLine;
            }
        }

        BimpTools::loadDolClass('product');

        $i = -1;
        $total_ht_without_remises = 0;
        $total_ttc_without_remises = 0;

        foreach ($this->object->lines as $line) {
            $i++;

            $bimpLine = isset($bimpLines[(int) $line->id]) ? $bimpLines[(int) $line->id] : null;

            if ($this->object->type != 3 && ($line->desc == "(DEPOSIT)" || stripos($line->desc, 'Acompte') === 0)) {
//                $acompteHt = $line->subprice * (float) $line->qty;
//                $acompteTtc = BimpTools::calculatePriceTaxIn($acompteHt, (float) $line->tva_tx);

                $total_ht_without_remises += $line->total_ht;
                $total_ttc_without_remises += $line->total_ttc;

                $this->acompteHt -= $line->total_ht;
                $this->acompteTtc -= $line->total_ttc;
                $this->acompteTva20 -= $line->total_tva;
                continue;
            }

            $product = null;
            if (!is_null($line->fk_product) && $line->fk_product) {
                $product = new Product($this->db);
                if ($product->fetch((int) $line->fk_product) <= 0) {
                    unset($product);
                    $product = null;
                }
            }
            if (is_object($product) && $product->ref == "REMISECRT") {
                continue;
            }

            $hide_product_label = isset($bimpLines[(int) $line->id]) ? (int) $bimpLines[(int) $line->id]->getData('hide_product_label') : 0;

            $desc = $this->getLineDesc($line, $product, $hide_product_label);

            if (!is_null($bimpLine)) {
                if ($bimpLine->equipment_required && $bimpLine->isProductSerialisable()) {
                    $equipment_lines = $bimpLine->getEquipmentLines();
                    if (count($equipment_lines)) {
                        $equipments = array();

                        foreach ($equipment_lines as $equipment_line) {
                            if ((int) $equipment_line->getData('id_equipment')) {
                                $equipments[] = (int) $equipment_line->getData('id_equipment');
                            }
                        }

                        if (count($equipments)) {
                            $desc .= '<br/>';
                            $desc .= '<span style="font-size: 6px;">N° de série: </span>';
                            $fl = true;
                            $desc .= '<span style="font-size: 6px; font-style: italic">';
                            foreach ($equipments as $id_equipment) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                                if (BimpObject::objectLoaded($equipment)) {
                                    if (!$fl) {
                                        $desc .= ', ';
                                    } else {
                                        $fl = false;
                                    }
                                    $desc .= $equipment->getData('serial');
                                }
                            }
                            $desc .= '</span>';
                        }
                    }
                }
            }

            if ((BimpObject::objectLoaded($bimpLine) && (int) $bimpLine->getData('type') === ObjectLine::LINE_TEXT) ||
                    (!BimpObject::objectLoaded($bimpLine) && $line->subprice == 0 && !(int) $line->fk_product)) {
                $row['desc'] = array(
                    'colspan' => 99,
                    'content' => $desc,
                    'style'   => ' background-color: #F5F5F5;'
                );
            } else {
                $line_remise = $line->remise_percent;

                if (!is_null($bimpLine)) {
                    if ($bimpLine->isRemisable()) {
                        $line_remise -= $remise_globale_line_rate;
                    } else {
                        $line_remise = 0;
                    }
                }

                $row = array(
                    'desc' => $desc
                );

                $pu_ht_with_remise = (float) ($line->subprice - ($line->subprice * ($line_remise / 100)));

                if ($this->hideReduc && $line_remise) {
                    $row['pu_ht'] = price($pu_ht_with_remise, 0, $this->langs);
                } else {
                    $row['pu_ht'] = pdf_getlineupexcltax($this->object, $i, $this->langs);
                }

                $row['qte'] = pdf_getlineqty($this->object, $i, $this->langs);

                if (isset($this->object->situation_cycle_ref) && $this->object->situation_cycle_ref) {
                    $row['progress'] = pdf_getlineprogress($this->object, $i, $this->langs);
                }

                if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                    $row['tva'] = pdf_getlinevatrate($this->object, $i, $this->langs);
                }

                if ($conf->global->PRODUCT_USE_UNITS) {
                    $row['unite'] = pdf_getlineunit($this->object, $i, $this->langs);
                }

                if (!$this->hideReduc && $line_remise) {
                    $line_remise = round($line_remise, 4, PHP_ROUND_HALF_DOWN);
                    if ($line_remise) {
                        $row['reduc'] = str_replace('.', ',', (string) $line_remise) . '%';
                    }
                }

                $row_total_ht = $pu_ht_with_remise * (float) $line->qty;
                $row_total_ttc = BimpTools::calculatePriceTaxIn($row_total_ht, $line->tva_tx);

                $row['total_ht'] = BimpTools::displayMoneyValue($row_total_ht, '');

                if (!$this->hideTtc) {
                    $row['total_ttc'] = BimpTools::displayMoneyValue($row_total_ttc, '');
                } elseif (!$this->hideReduc) {
                    $row['pu_remise'] = BimpTools::displayMoneyValue($pu_ht_with_remise, '');
                }

                $total_ht_without_remises += $line->subprice * (float) $line->qty;
                $total_ttc_without_remises += BimpTools::calculatePriceTaxIn($line->subprice * (float) $line->qty, (float) $line->tva_tx);
            }

            if (isset($bimpLines[$line->id])) {
                $bimpLine = $bimpLines[$line->id];
                if ($bimpLine->getData("force_qty_1")) {
                    if ($row['qte'] > 1) {
                        $row['pu_ht'] = price(str_replace(",", ".", $row['pu_ht']) * $row['qte']);
                        $product->array_options['options_deee'] = $product->array_options['options_deee'] * $row['qte'];
                        $product->array_options['options_rpcp'] = $product->array_options['options_rpcp'] * $row['qte'];
                        if ($row['pu_remise'] > 0)
                            $row['pu_remise'] = BimpTools::displayMoneyValue($row['pu_remise'] * $row['qte'], "");
                        $row['qte'] = 1;
                    } elseif ($row['qte'] < 1) {
                        $row['pu_ht'] = price(str_replace(",", ".", $row['pu_ht']) * ($row['qte'] * -1));
                        $product->array_options['options_deee'] = $product->array_options['options_deee'] * ($row['qte'] * -1);
                        $product->array_options['options_rpcp'] = $product->array_options['options_rpcp'] * ($row['qte'] * -1);
                        if ($row['pu_remise'] > 0)
                            $row['pu_remise'] = BimpTools::displayMoneyValue($row['pu_remise'] * ($row['qte'] * -1), "");
                        $row['qte'] = -1;
                    }
                }
            }


            /* Pour les ecotaxe et copie privé */
            $row['object'] = $product;
            if (is_object($product)) {
                if (isset($product->array_options['options_deee']) && $product->array_options['options_deee'] > 0)
                    $this->totals['DEEE'] += $product->array_options['options_deee'] * $row['qte'];
                if (isset($product->array_options['options_rpcp']) && $product->array_options['options_rpcp'] > 0)
                    $this->totals['RPCP'] += $product->array_options['options_rpcp'] * $row['qte'];
            }


            $row = $this->traitePeriodicity($row, array('pu_ht', 'pu_remise', 'total_ht', 'total_ttc'));

            $table->rows[] = $row;
        }

        if (/* !$this->hideReduc && */$remise_globale) {
            $remise_infos = $this->bimpCommObject->getRemisesInfos();

            $remise_label = $this->bimpCommObject->getData('remise_globale_label');

            if (!$remise_label) {
                $remise_label = 'Remise exceptionnelle sur l\'intégralité ' . $this->bimpCommObject->getLabel('of_the');
            }

            $row = array(
                'desc'     => $remise_label,
                'qte'      => 1,
                'tva'      => '',
                'pu_ht'    => BimpTools::displayMoneyValue(-$remise_infos['remise_globale_amount_ht'], ''),
                'total_ht' => BimpTools::displayMoneyValue(-$remise_infos['remise_globale_amount_ht'], '')
            );
            if (!$this->hideTtc)
                $row['total_ttc'] = BimpTools::displayMoneyValue(-$remise_infos['remise_globale_amount_ttc'], '');
            elseif (!$this->hideReduc)
                $row['pu_remise'] = BimpTools::displayMoneyValue(-$remise_infos['remise_globale_amount_ht'], '');

            $table->rows[] = $row;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function traitePeriodicity($row, $champs)
    {
        if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
            foreach ($champs as $nomChamp) {
                if (isset($row[$nomChamp]))
                    $row[$nomChamp] = BimpTools::displayMoneyValue(str_replace(",", ".", $row[$nomChamp]) / $this->nbPeriods);
            }
        }
        return $row;
    }

    public function renderAfterLines()
    {
        $this->pdf->addVMargin(2);
        $html = '';

        $html .= '<p style="font-size: 6px; font-style: italic">';
        if ($this->totals['RPCP'] > 0) {
            $html .= '<span style="font-weight: bold;">Rémunération Copie Privée : ' . price($this->totals['RPCP']) . ' € HT</span>
<br/>Notice officielle d\'information sur la copie privée à : http://www.copieprivee.culture.gouv.fr.
  Remboursement/exonération de la rémunération pour usage professionnel : http://www.copiefrance.fr<br/>';
        }



//        $html .= '<p style="font-size: 6px; font-weight: bold; font-style: italic">RÉSERVES DE PROPRIÉTÉ : applicables selon la loi n°80.335 du 12 mai';
//        $html .= ' 1980 et de l\'article L624-16 du code de commerce. Seul le Tribunal de Lyon est compétent.</p>';

        if (BimpCore::getConf("CGV_BIMP")) {
            $html .= '<span style="font-weight: bold;">';
            if ($this->pdf->addCgvPages)
                $html .= 'La signature de ce document vaut acceptation de nos Conditions Générales de Vente annexées et disponibles sur www.bimp.fr';
            else
                $html .= 'Nos Conditions Générales de Vente sont consultables sur le site www.bimp.fr';
            $html .= "</span>";
            $html .= '<br/>Les marchandises vendues sont soumises à une clause de réserve de propriété.
   En cas de retard de paiement, taux de pénalité de cinq fois le taux d’intérêt légal et indemnité forfaitaire pour frais de recouvrement de 40€ (article L.441-6 du code de commerce).';
            $html .= 'La Société ' . $this->fromCompany->name . ' ne peut être tenue pour responsable de la perte éventuelles de données informatiques. ';
            $html .= ' Il appartient au client d’effectuer des sauvegardes régulières de ses informations. En aucun cas les soucis systèmes, logiciels, paramétrages internet';
            $html .= ' et périphériques et les déplacements ne rentrent dans le cadre de la garantie constructeur.';


            $html .= '<span style="font-weight: bold;">';
            $html .= ' Aucun escompte pour paiement anticipé ne sera accordé.';
            $html .= "</span>";
            $html .= "</p>";
        }

        $html .= '<p style="font-size: 6px; font-style: italic">Merci de noter systématiquement le n° de facture sur votre règlement.</p>';

        $this->writeContent($html);
    }

    public function renderBottom()
    {
        $table = new BimpPDF_Table($this->pdf, false);
        $table->cellpadding = 0;
        $table->remove_empty_cols = false;
        $table->addCol('left', '', 95);
        $table->addCol('right', '', 95);

        $table->rows[] = array(
            'left'  => $this->getBottomLeftHtml(),
            'right' => $this->getBottomRightHtml()
        );

        $this->writeContent('<br/><br/>');
        $table->write();
    }

    public function getBottomLeftHtml()
    {
        return $this->getPaymentInfosHtml();
    }

    public function getBankHtml($account, $only_number = false)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.formbank.class.php';

        $this->langs->load('banks');

        $bickey = "BICNumber";

        if ($account->getCountryCode() == 'IN') {
            $bickey = "SWIFT";
        }

        $usedetailedbban = $account->useDetailedBBAN();

        if (!$only_number) {
            $html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByTransferOnThisBankAccount') . ':</span><br/><br/>';
        }

        if ($usedetailedbban) {
            $html .= '<strong>' . $this->langs->transnoentities("Bank") . '</strong>: ';
            $html .= $this->langs->convToOutputCharset($account->bank) . '<br/>';

            if (empty($conf->global->PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN)) {
                foreach ($account->getFieldsToShow() as $val) {
                    $content = '';

                    switch ($val) {
                        case 'BankCode':
                            $content = $account->code_banque;
                            break;
                        case 'DeskCode':
                            $content = $account->code_banque;
                            break;
                        case 'BankAccountNumber':
                            $content = $account->code_banque;
                            break;
                        case 'BankAccountNumberKey':
                            $content = $account->code_banque;
                            break;
                    }

                    if ($content) {
                        $html .= '<strong>' . $this->langs->transnoentities($val) . '</strong>: ';
                        $html .= $this->langs->convToOutputCharset($content);
                        $html .= '<br/>';
                    }
                }
            }
        } else {
            $html .= '<strong>' . $this->langs->transnoentities('Bank') . '</strong>: ' . $this->langs->convToOutputCharset($account->bank) . '<br/>';
            $html .= '<strong>' . $this->langs->transnoentities('BankAccountNumber') . '</strong>: ' . $this->langs->convToOutputCharset($account->number) . '<br/>';
        }

        if (!$only_number && !empty($account->domiciliation)) {
            $html .= '<strong>' . $this->langs->transnoentities('Residence') . '</strong>: ' . $this->langs->convToOutputCharset($account->domiciliation) . '<br/>';
        }

        if (!empty($account->proprio)) {
            $html .= '<strong>' . $this->langs->transnoentities('BankAccountOwner') . '</strong>: ' . $this->langs->convToOutputCharset($account->proprio) . '<br/>';
        }

        $ibankey = FormBank::getIBANLabel($account);

        if (!empty($account->iban)) {
            $ibanDisplay_temp = str_replace(' ', '', $this->langs->convToOutputCharset($account->iban));
            $ibanDisplay = "";

            $nbIbanDisplay_temp = dol_strlen($ibanDisplay_temp);
            for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
                $ibanDisplay .= $ibanDisplay_temp[$i];
                if ($i % 4 == 3 && $i > 0)
                    $ibanDisplay .= " ";
            }

            $html .= '<strong>' . $this->langs->transnoentities($ibankey) . '</strong>: ' . $ibanDisplay . '<br/>';
        }

        if (!empty($account->bic)) {
            $html .= '<strong>' . $this->langs->transnoentities($bickey) . '</strong>: ' . $this->langs->convToOutputCharset($account->bic) . '<br/>';
        }

        return $html;
    }

    public function getPaymentInfosHtml()
    {
        return '';
    }

    public function getBottomRightHtml()
    {

        $html .= $this->getTotauxRowsHtml();
        $html .= $this->getPaymentsHtml();
        $html .= $this->getAfterTotauxHtml();

        return $html;
    }

    public function calcTotaux()
    {
        global $conf, $mysoc;

        $this->total_remises = 0;

        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->tva = array();

        $i = 0;
        foreach ($this->object->lines as $line) {
            if (!$this->hideReduc && $line->remise_percent) {
                $this->total_remises += ((float) $line->subprice * ((float) $line->remise_percent / 100)) * (float) $line->qty;
            }

            $sign = 1;
            if (isset($this->object->type) && $this->object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE))
                $sign = -1;

            // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
            // Prise en compte si nécessaire de la progression depuis la situation précédente:
            if (isset($this->object->situation_cycle_ref) && $this->object->situation_cycle_ref && method_exists($line, 'get_prev_progress')) {
                $prev_progress = $line->get_prev_progress($this->object->id);
            } else {
                $prev_progress = 0;
            }
            if ($prev_progress > 0 && !empty($line->situation_percent)) {
                if ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) {
                    $tva_line = $sign * $line->multicurrency_total_tva * ($line->situation_percent - $prev_progress) / $line->situation_percent;
                } else {
                    $tva_line = $sign * $line->total_tva * ($line->situation_percent - $prev_progress) / $line->situation_percent;
                }
            } else {
                if ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) {
                    $tva_line = $sign * $line->multicurrency_total_tva;
                } else {
                    $tva_line = $sign * $line->total_tva;
                }
            }

            $localtax1ligne = $line->total_localtax1;
            $localtax2ligne = $line->total_localtax2;
            $localtax1_rate = $line->localtax1_tx;
            $localtax2_rate = $line->localtax2_tx;
            $localtax1_type = $line->localtax1_type;
            $localtax2_type = $line->localtax2_type;

            if ($this->object->remise_percent)
                $tva_line -= ($tva_line * $this->object->remise_percent) / 100;
            if ($this->object->remise_percent)
                $localtax1ligne -= ($localtax1ligne * $this->object->remise_percent) / 100;
            if ($this->object->remise_percent)
                $localtax2ligne -= ($localtax2ligne * $this->object->remise_percent) / 100;

            $vatrate = (string) $line->tva_tx;

            // Retrieve type from database for backward compatibility with old records
            if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
                    && (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
                $localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $this->object->thirdparty, $mysoc);
                $localtax1_type = $localtaxtmp_array[0];
                $localtax2_type = $localtaxtmp_array[2];
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

            if (($line->info_bits & 0x01) == 0x01)
                $vatrate .= '*';

            if (!isset($this->tva[$vatrate])) {
                $this->tva[$vatrate] = 0;
            }

            $this->tva[$vatrate] += $tva_line;
            $this->ht[$vatrate] += $line->total_ht;
            $i++;
        }
        $this->tva["20.000"] += $this->acompteTva20;
    }

    public function getTotauxRowsHtml()
    {
        global $conf;

        if ($this->hideTotal) {
            return '';
        }

        $this->calcTotaux();

        $html .= '<table style="width: 100%" cellpadding="5">';

        // Total remises: 
        if (!$this->hideReduc && $this->total_remises > 0) {
            $total_remises = $this->total_remises;
            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                $total_remises /= $this->nbPeriods;
            }
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Total remises HT</td>';
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($total_remises, 0, $this->langs);
            if ((int) $this->periodicity) {
                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
            }
            $html .= '</td>';
            $html .= '</tr>';
        }



        // Total HT:
        $total_ht = ($conf->multicurrency->enabled && $this->object->mylticurrency_tx != 1 ? $this->object->multicurrency_total_ht : $this->object->total_ht);
        $total_ht += (!empty($this->object->remise) ? $this->object->remise : 0) + $this->acompteHt;

        if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
            $total_ht /= $this->nbPeriods;
        }

        $html .= '<tr>';
        $html .= '<td style="">' . $this->langs->transnoentities("TotalHT") . '</td>';
        $html .= '<td style="text-align: right;">';
        $html .= price($total_ht, 0, $this->langs);

        if ((int) $this->periodicity) {
            $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
        }

        $html .= '</td>';
        $html .= '</tr>';


        // Total DEEE
        if ($this->totals['DEEE'] > 0) {
            $total_deee = $this->totals['DEEE'];

            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                $total_deee /= $this->nbPeriods;
            }

            $html .= '<tr>';
            $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("Dont éco-participation HT") . '</td>';
            $html .= '<td style="background-color: #DCDCDC;text-align: right;">' . price($total_deee, 0, $this->langs);
            if ((int) $this->periodicity) {
                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

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

                            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                                $tvaval /= $this->nbPeriods;
                            }

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
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

                            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                                $tvaval /= $this->nbPeriods;
                            }

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // TVA
                foreach ($this->tva as $tvakey => $tvaval) {
                    if (1) {
                        if (1) {
                            $tvacompl = '';
                            if (preg_match('/\*/', $tvakey)) {
                                $tvakey = str_replace('*', '', $tvakey);
                                $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                            }
                            $totalvat = $this->langs->transcountrynoentities("TotalVAT", $this->fromCompany->country_code) . ' ';
                            $totalvat .= vatrate($tvakey, 1) . $tvacompl;

                            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                                $tvaval /= $this->nbPeriods;
                            }

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . ' (' . price($this->ht[$tvakey]) . ' €)</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
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

                            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                                $tvaval /= $this->nbPeriods;
                            }

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
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

                            if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                                $tvaval /= $this->nbPeriods;
                            }

                            $html .= '<tr>';
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                }

                // Total TTC
                $total_ttc = ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? $this->object->multicurrency_total_ttc : $this->object->total_ttc;
                $total_ttc += $this->acompteTtc;

                if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
                    $total_ttc /= $this->nbPeriods;
                }

                $html .= '<tr>';
                $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("TotalTTC") . '</td>';
                $html .= '<td style="background-color: #DCDCDC; text-align: right;">' . price($total_ttc, 0, $this->langs);
                if ((int) $this->periodicity) {
                    $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                }
                $html .= '</td>';
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
            $resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount - $this->acompteTtc, 'MT');
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
                $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($this->object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $this->langs) . '</td>';
                $html .= '</tr>';
                $resteapayer = 0;
            }
        }

        if ($this->acompteHt > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("Acompte") . '</td>';
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($this->acompteTtc, 0, $this->langs) . '</td>';
            $html .= '</tr>';
        }

        if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0 || $this->acompteHt > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("RemainderToPay") . '</td>';
            $html .= '<td style="text-align: right; background-color: #DCDCDC;">' . price($resteapayer, 0, $this->langs) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '<br/>';

        return $html;
    }

    public function getPaymentsHtml()
    {
        return '';
    }

    public function getAfterTotauxHtml()
    {
        $html = '<br/>';
        $html .= '<table style="width: 95%" cellpadding="3">';

        /* if (!is_null($this->contact) && isset($this->contact->id) && $this->contact->id) {
          $html .= '<tr>';
          $html .= '<td style="text-align: center;">' . $this->contact->lastname . ' ' . $this->contact->firstname;
          $html .= (isset($this->contact->poste) && $this->contact->poste ? ' - ' . $this->contact->poste : '') . '</td>';
          $html .= '</tr>';
          } */

        $html .= '<tr>';
//        $html .= '<td style="text-align: center;">Cachet, Date, Signature et mention <b>"Bon pour Commande"</b></td>';
        $html .= '<td style="text-align:center;"><i><b>' . $this->after_totaux_label . '</b></i></td>';

        $html .= '<td>Signature + Cachet avec SIRET :</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Nom :</td>';

        $html .= '<td rowspan="4" style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/></td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Prénom :</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Fonction :</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>Date :</td>';
        $html .= '</tr>';

        $html .= '</table>';

        return $html;
    }

    public function renderAfterBottom()
    {
        
    }
}
