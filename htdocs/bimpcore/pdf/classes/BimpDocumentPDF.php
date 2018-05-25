<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class BimpDocumentPDF extends BimpModelPDF
{

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';
    public $thirdparty = null;
    public $contact = null;
    public $total_remises = 0;
    public $localtax1 = array();
    public $localtax2 = array();
    public $tva = array();

    public function __construct($db)
    {
        parent::__construct($db, 'P', 'A4');
    }

    // Initialisation

    protected function initData()
    {
        if (!count($this->errors)) {
            if (!is_null($this->object) && isset($this->object->id) && $this->object->id) {
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
            }
        }
    }

    protected function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;

        $logo_height = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $logo_height = pdf_getHeightForLogo($logo_file, false);
        }

        if ($logo_height > 30 || $logo_height === 22) {
            $logo_height = 30;
        }

        $this->header_vars = array(
            'logo_img'     => $logo_file,
            'logo_height'  => $logo_height * BimpPDF::$pxPerMm,
            'header_right' => ''
        );
    }

    protected function initfooter()
    {
        $line1 = '';
        $line2 = '';

        global $conf;

        if ($this->fromCompany->forme_juridique_code) {
            $line1 .= $this->langs->convToOutputCharset(getFormeJuridiqueLabel($this->fromCompany->forme_juridique_code));
        }

        if ($this->fromCompany->capital) {
            $captital = price2num($this->fromCompany->capital);
            if (is_numeric($captital) && $captital > 0) {
                $line1.=($line1 ? " - " : "") . $this->langs->transnoentities("CapitalOf", price($captital, 0, $this->langs, 0, 0, 0, $conf->currency));
            } else {
                $line1.=($line1 ? " - " : "") . $this->langs->transnoentities("CapitalOf", $captital, $this->langs);
            }
        }

        if ($this->fromCompany->idprof1 && ($this->fromCompany->country_code != 'FR' || !$this->fromCompany->idprof2)) {
            $field = $this->langs->transcountrynoentities("ProfId1", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof1);
        }

        if ($this->fromCompany->idprof2) {
            $field = $this->langs->transcountrynoentities("ProfId2", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof2);
        }

        if ($this->fromCompany->idprof3) {
//            $field = $this->langs->transcountrynoentities("ProfId3", $this->fromCompany->country_code);
            $field = 'APE';
//            if (preg_match('/\((.*)\)/i', $field, $reg)) {
//                $field = $reg[1];
//                
//            }
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof3);
        }

        if ($this->fromCompany->idprof4) {
            $field = $this->langs->transcountrynoentities("ProfId4", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof4);
        }

        if ($this->fromCompany->idprof5) {
            $field = $this->langs->transcountrynoentities("ProfId5", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof5);
        }

        if ($this->fromCompany->idprof6) {
            $field = $this->langs->transcountrynoentities("ProfId6", $this->fromCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $line2 .= ($line2 ? " - " : "") . $field . ": " . $this->langs->convToOutputCharset($this->fromCompany->idprof6);
        }
        // IntraCommunautary VAT
        if ($this->fromCompany->tva_intra != '') {
            $line2 .= ($line2 ? " - " : "") . $this->langs->transnoentities("VATIntraShort") . ": " . $this->langs->convToOutputCharset($this->fromCompany->tva_intra);
        }

        $this->footer_vars = array(
            'footer_line_1' => $line1,
            'footer_line_2' => $line2,
        );
    }

    // Rendus:

    protected function renderContent()
    {
        $this->renderAddresses($this->thirparty, $this->contact);
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

    public function getSenderInfosHtml()
    {
        $html = '<div class="bold">' . $this->langs->convToOutputCharset($this->fromCompany->name) . '</div>';
        $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty);
        $html = str_replace("\n", '<br/>', $html);
        return $html;
    }

    public function getTargetInfosHtml()
    {
        $html = '<div class="bold">' . pdfBuildThirdpartyName($this->thirdparty, $this->langs) . '</div>';
        $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contact, !is_null($this->contact) ? 1 : 0, 'target');
        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }

    public function renderAddresses()
    {
        $html = '';

//        	if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
//$thirdparty = $object->contact;
//} else {
//$thirdparty = $object->thirdparty;
//}

        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td style="width: 40%">' . $this->langs->transnoentities('BillFrom') . ' : </td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%">' . $this->langs->transnoentities('BillTo') . ' : </td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 40%">';
        $html .= $this->getSenderInfosHtml();
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%" class="border">';

        $html .= $this->getTargetInfosHtml();
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
    }

    public function renderBeforeLines()
    {
        
    }

    public function getLineDesc($line, Product $product = null)
    {
        $desc = '';
        if (!is_null($product)) {
            $desc = $product->ref;
            $desc.= ($desc ? ' - ' : '') . $product->label;
        }

        if (!is_null($line->desc) && $line->desc) {
            $line_desc = $line->desc;
            if (!is_null($product)) {
                $line_desc = str_replace($product->label, '', $line_desc);
            }
            if ($line_desc) {
                $desc .= ($desc ? '<br/>' : '') . $line_desc;
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

        if (method_exists($this, 'setAmountsTableParams')) {
            $this->setAmountsTableParams($table);
        }

        BimpTools::loadDolClass('product');

        $i = 0;
        foreach ($this->object->lines as $line) {
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
                $row['desc'] = array(
                    'colspan' => 99,
                    'content' => $desc,
                    'style'   => 'font-weight: bold; background-color: #F5F5F5;'
                );
            } else {
                $row = array(
                    'desc'      => $desc,
                    'total_ht'  => BimpTools::displayMoneyValue($line->total_ht, ''),
                    'total_ttc' => BimpTools::displayMoneyValue($line->total_ttc, '')
                );

                $row['pu_ht'] = pdf_getlineupexcltax($this->object, $i, $this->langs);
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

                if ($line->remise_percent) {
                    $row['reduc'] = str_replace('.', ',', (string) round($line->remise_percent, 4, PHP_ROUND_HALF_DOWN)) . '%';
                }

                $row['total_ht'] = pdf_getlinetotalexcltax($this->object, $i, $this->langs);
            }

            $table->rows[] = $row;
            $i++;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function renderAfterLines()
    {
        $this->pdf->addVMargin(2);

        $html = '<p style="font-size: 6px; font-weight: bold; font-style: italic">RÉSERVES DE PROPRIÉTÉ : applicables selon la loi n°80.335 du 12 mai';
        $html .= ' 1980 et de l\'article L624-16 du code de commerce. Seul le Tribunal de Lyon est compétent.</p>';

        $html .= '<p style="font-size: 6px; font-style: italic">La Société ' . $this->fromCompany->nom . ' ne peut être tenue pour responsable de la perte éventuelles de données informatiques.';
        $html .= ' Il appartient au client d’effectuer des sauvegardes régulières de ses informations. En aucun cas les soucis systèmes, logiciels, paramétrages internet';
        $html .= ' et périphériques et les déplacements ne rentrent dans le cadre de la garantie constructeur.</p>';

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
        global $conf;

        $this->total_remises = 0;

        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->tva = array();

        $i = 0;
        foreach ($this->object->lines as $line) {
            $pu_ht = (float) $line->subprice;
            if ($line->remise_percent) {
                $this->total_remises += ((float) $pu_ht * ((float) $line->remise_percent / 100)) * (int) pdf_getlineqty($this->object, $i, $this->langs);
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
                $tva_line-=($tva_line * $this->object->remise_percent) / 100;
            if ($this->object->remise_percent)
                $localtax1ligne-=($localtax1ligne * $this->object->remise_percent) / 100;
            if ($this->object->remise_percent)
                $localtax2ligne-=($localtax2ligne * $this->object->remise_percent) / 100;

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
                $vatrate.='*';

            if (!isset($this->tva[$vatrate])) {
                $this->tva[$vatrate] = 0;
            }

            $this->tva[$vatrate] += $tva_line;
            $i++;
        }
    }

    public function getTotauxRowsHtml()
    {
        global $conf;

        $this->calcTotaux();

        $html .= '<table style="width: 100%" cellpadding="5">';

        // Total remises: 
        if ($this->total_remises > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">Total remises HT</td>';
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . price($this->total_remises, 0, $this->langs) . '</td>';
            $html .= '</tr>';
        }

        // Total HT:
        $total_ht = ($conf->multicurrency->enabled && $this->object->mylticurrency_tx != 1 ? $this->object->multicurrency_total_ht : $this->object->total_ht);
        $html .= '<tr>';
        $html .= '<td style="">' . $this->langs->transnoentities("TotalHT") . '</td>';
        $html .= '<td style="text-align: right;">' . price($total_ht + (!empty($this->object->remise) ? $this->object->remise : 0), 0, $this->langs) . '</td>';
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
                $total_ttc = ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? $this->object->multicurrency_total_ttc : $this->object->total_ttc;
                $html .= '<tr>';
                $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("TotalTTC") . '</td>';
                $html .= '<td style="background-color: #DCDCDC; text-align: right;">' . price($total_ttc, 0, $this->langs) . '</td>';
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
            $resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
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
        $html .= '<table style="width: 95%" cellpadding="3">';

        /* if (!is_null($this->contact) && isset($this->contact->id) && $this->contact->id) {
          $html .= '<tr>';
          $html .= '<td style="text-align: center;">' . $this->contact->lastname . ' ' . $this->contact->firstname;
          $html .= (isset($this->contact->poste) && $this->contact->poste ? ' - ' . $this->contact->poste : '') . '</td>';
          $html .= '</tr>';
          } */

        $html .= '<tr>';
//        $html .= '<td style="text-align: center;">Cachet, Date, Signature et mention <b>"Bon pour Commande"</b></td>';
        $html .= '<td style="text-align:center;"><i><b>Bon pour Commande</b></i></td>';

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
