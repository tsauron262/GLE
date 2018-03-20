<?php

require_once __DIR__ . '/BimpModelPDF.php';

class BimpDocumentPDF extends BimpModelPDF
{

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';

    public function __construct($db)
    {
        parent::__construct($db, 'P', 'A4');
    }

    // Initialisation

    protected function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;

        if (!file_exists($logo_file)) {
            $logo_file = '';
        }

        $this->header_vars = array(
            'logo_img'     => $logo_file,
            'logo_width'   => '120',
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

    public function renderAddresses($thirdparty, $contact = null)
    {
        $html = '';

        $sender_infos = pdf_build_address($this->langs, $this->fromCompany, $thirdparty);
        $sender_infos = str_replace("\n", '<br/>', $sender_infos);
        $target_infos = pdf_build_address($this->langs, $this->fromCompany, $thirdparty, $contact, !is_null($contact) ? 1 : 0, 'target');
        $target_infos = str_replace("\n", '<br/>', $target_infos);

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
        $html .= '<div class="bold">' . $this->langs->convToOutputCharset($this->fromCompany->name) . '</div>';
        $html .= $sender_infos;
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 55%" class="border">';
        $html .= '<div class="bold">' . pdfBuildThirdpartyName($thirdparty, $this->langs) . '</div>';
        $html .= $target_infos;
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderBank($account, $only_number = false)
    {
        global $mysoc, $conf;

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

    public function renderDocumentContent()
    {
        global $conf, $mysoc;

        $situationinvoice = false;

        if (isset($this->object->situation_cycle_ref) && $this->situation_cycle_ref) {
            $situationinvoice = true;
        }

        if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
            $this->writeContent('<p style="font-size: 10px">Objet : <strong>' . $this->object->array_options['options_libelle'] . '</strong></p>');
        }

        $table = new BimpPDF_AmountsTable($this->pdf);

        if (method_exists($this, 'setAmountsTableParams')) {
            $this->setAmountsTableParams($table);
        }

        $lines = $this->object->lines;
        $i = 0;

        $localtax1 = array();
        $localtax2 = array();
        $tva = array();

        // Traitement des lignes: 

        foreach ($lines as $line) {
            $desc = '';
            if (is_null($line->desc) || !$line->desc) {
                if (!is_null($line->fk_product) && $line->fk_product) {
                    BimpTools::loadDolClass('product');
                    $product = new Product($this->db);
                    if ($product->fetch((int) $line->fk_product) > 0) {
                        $desc = $product->ref;
                    }
                    $desc.= ($desc ? ' - ' : '') . $product->label;
                }
            } else {
                $desc = $line->desc;
            }
            $desc = str_replace("\n", '<br/>', $desc);
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

                if ($situationinvoice) {
                    $row['progress'] = pdf_getlineprogress($this->object, $i, $this->langs);
                }

                if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                    $row['tva'] = pdf_getlinevatrate($this->object, $i, $this->langs);
                }

                if ($conf->global->PRODUCT_USE_UNITS) {
                    $row['unite'] = pdf_getlineunit($this->object, $i, $this->langs);
                }

                if ($line->remise_percent) {
                    $row['reduc'] = pdf_getlineremisepercent($this->object, $i, $this->langs);
                }

                $row['total_ht'] = pdf_getlinetotalexcltax($this->object, $i, $this->langs);

                $sign = 1;
                if (isset($this->object->type) && $this->object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE))
                    $sign = -1;

                // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
                // Prise en compte si nécessaire de la progression depuis la situation précédente:
                if ($situationinvoice && method_exists($line, 'get_prev_progress')) {
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

                if (!isset($localtax1[$localtax1_type])) {
                    $localtax1[$localtax1_type] = array();
                }
                if (!isset($localtax1[$localtax1_type][$localtax1_rate])) {
                    $localtax1[$localtax1_type][$localtax1_rate] = 0;
                }

                $localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;

                if (!isset($localtax2[$localtax2_type])) {
                    $localtax2[$localtax2_type] = array();
                }
                if (!isset($localtax2[$localtax2_type][$localtax2_rate])) {
                    $localtax2[$localtax2_type][$localtax2_rate] = 0;
                }

                $localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;

                if (($line->info_bits & 0x01) == 0x01)
                    $vatrate.='*';

                if (!isset($tva[$vatrate])) {
                    $tva[$vatrate] = 0;
                }

                $tva[$vatrate] += $tva_line;
            }

            $table->rows[] = $row;
            $i++;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);

        // *** Informations:  *** 
        $info_html = '<div style="font-size: 7px; line-height: 8px;">';
        $info_html .= '<table style="width: 100%" cellpadding="5">';

        // Date de livraison
        if (!empty($this->object->date_livraison)) {
            $info_html .= '<tr><td>';
            $info_html .= dol_print_date($this->object->date_livraison, "daytext", false, $this->langs, true) . '<br/>';
            $info_html .= '</td></tr>';
        } elseif ($this->object->availability_code || (isset($this->object->availability) && $this->object->availability)) {
            $info_html .= '<tr><td>';
            $info_html .= '<strong>' . $this->langs->transnoentities("AvailabilityPeriod") . ': </strong>';
            $label = $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) != ('AvailabilityType' . $this->object->availability_code) ? $this->langs->transnoentities("AvailabilityType" . $this->object->availability_code) : $this->langs->convToOutputCharset($this->object->availability);
            $label = str_replace('\n', "\n", $label);
            $info_html .= $label;
            $info_html .= '</td></tr>';
        }

        // Conditions de paiement: 
        if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND) && ($this->object->cond_reglement_code || $this->object->cond_reglement)) {
            $info_html .= '<tr><td>';
            $info_html .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong><br/>';
            $label = $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) != ('PaymentCondition' . $this->object->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->object->cond_reglement_code) : $this->langs->convToOutputCharset($this->object->cond_reglement_doc);
            $label = str_replace('\n', "\n", $label);
            $info_html .= '</td></tr>';
        }

        // Mode de paiement: 
        if ($this->object->mode_reglement_code && $this->object->mode_reglement_code != 'CHQ' && $this->object->mode_reglement_code != 'VIR') {
            $info_html .= '<tr><td>';
            $info_html .= '<strong>' . $this->langs->transnoentities("PaymentMode") . '</strong>:<br/>';
            $info_html .= $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) != ('PaymentType' . $this->object->mode_reglement_code) ? $this->langs->transnoentities("PaymentType" . $this->object->mode_reglement_code) : $this->langs->convToOutputCharset($this->object->mode_reglement);
            $info_html .= '</td></tr>';
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'CHQ') {

            if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
                    $info_html .= '<tr><td>';
                    if (!class_exists('Account')) {
                        require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    }
                    $account = new Account($this->db);
                    $account->fetch($conf->global->FACTURE_CHQ_NUMBER);

                    $info_html .= '<span style="font-style: italic">' . $this->langs->transnoentities('PaymentByChequeOrderedTo', $account->proprio) . ':</span><br/><br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $info_html .= '<strong>' . str_replace("\n", '<br/>', $this->langs->convToOutputCharset($account->owner_address)) . '</strong>';
                    }
                    $info_html .= '</td></tr>';
                } elseif ($conf->global->FACTURE_CHQ_NUMBER == -1) {
                    $info_html .= '<tr><td>';
                    $info_html .= $this->langs->transnoentities('PaymentByChequeOrderedTo', $this->fromCompany->name) . '<br/>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $info_html .= $this->langs->convToOutputCharset($this->fromCompany->getFullAddress()) . '<br/>';
                    }
                    $info_html .= '</td></tr>';
                }
            }
        }

        if (empty($this->object->mode_reglement_code) || $this->object->mode_reglement_code == 'VIR') {
            if (!empty($this->object->fk_account) || !empty($this->object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
                $info_html .= '<tr><td>';
                $bankid = (empty($this->object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $this->object->fk_account);
                if (!empty($this->object->fk_bank)) {
                    $bankid = $this->object->fk_bank;
                }

                $account = new Account($this->db);
                $account->fetch($bankid);
                $info_html .= $this->renderBank($account);
                $info_html .= '</td></tr>';
            }
        }

        $info_html .= '</table></div>';

        // *** Totaux:  ***

        $totaux_html = '<div>';
        $totaux_html .= '<table style="width: 100%" cellpadding="5">';

        // Total HT:
        $total_ht = ($conf->multicurrency->enabled && $this->object->mylticurrency_tx != 1 ? $this->object->multicurrency_total_ht : $this->object->total_ht);
        $totaux_html .= '<tr>';
        $totaux_html .= '<td style="">' . $this->langs->transnoentities("TotalHT") . '</td>';
        $totaux_html .= '<td style="text-align: right;">' . price($total_ht + (!empty($this->object->remise) ? $this->object->remise : 0), 0, $this->langs) . '</td>';
        $totaux_html .= '</tr>';

        if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
            $tvaisnull = ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
            if (!$tvaisnull || empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL)) {
                // Taxes locales 1 avant TVA
                foreach ($localtax1 as $localtax_type => $localtax_rate) {
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

                            $totaux_html .= '<tr>';
                            $totaux_html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $totaux_html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $totaux_html .= '</tr>';
                        }
                    }
                }

                // Taxes locales 2 avant TVA
                foreach ($localtax2 as $localtax_type => $localtax_rate) {
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

                            $totaux_html .= '<tr>';
                            $totaux_html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $totaux_html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $totaux_html .= '</tr>';
                        }
                    }
                }

                // TVA
                foreach ($tva as $tvakey => $tvaval) {
                    if ($tvakey != 0) {
                        $tvacompl = '';
                        if (preg_match('/\*/', $tvakey)) {
                            $tvakey = str_replace('*', '', $tvakey);
                            $tvacompl = " (" . $this->langs->transnoentities("NonPercuRecuperable") . ")";
                        }
                        $totalvat = $this->langs->transcountrynoentities("TotalVAT", $this->fromCompany->country_code) . ' ';
                        $totalvat .= vatrate($tvakey, 1) . $tvacompl;

                        $totaux_html .= '<tr>';
                        $totaux_html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                        $totaux_html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                        $totaux_html .= '</tr>';
                    }
                }

                // Taxes locales 1 après TVA
                foreach ($localtax1 as $localtax_type => $localtax_rate) {
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

                            $totaux_html .= '<tr>';
                            $totaux_html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $totaux_html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $totaux_html .= '</tr>';
                        }
                    }
                }

                // Taxes locales 2 après TVA
                foreach ($localtax2 as $localtax_type => $localtax_rate) {
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

                            $totaux_html .= '<tr>';
                            $totaux_html .= '<td style="background-color: #F0F0F0;">' . $totalvat . '</td>';
                            $totaux_html .= '<td style="background-color: #F0F0F0; text-align: right;">' . price($tvaval, 0, $this->langs) . '</td>';
                            $totaux_html .= '</tr>';
                        }
                    }
                }

                // Total TTC
                $total_ttc = ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1) ? $this->object->multicurrency_total_ttc : $this->object->total_ttc;
                $totaux_html .= '<tr>';
                $totaux_html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("TotalTTC") . '</td>';
                $totaux_html .= '<td style="background-color: #DCDCDC; text-align: right;">' . price($total_ttc, 0, $this->langs) . '</td>';
                $totaux_html .= '</tr>';
            }
        }
        $totaux_html .= '<tr><td></td><td></td></tr>';

        $totaux_html .= '<tr>';
        $totaux_html .= '<td colspan="2" style="text-align: center;">Cachet, Date, Signature et mention "Bon pour Accord"</td>';
        $totaux_html .= '</tr>';

        $totaux_html .= '<tr>';
        $totaux_html .= '<td colspan="2" style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/></td>';
        $totaux_html .= '</tr>';
        $totaux_html .= '</table>';
        $totaux_html .= '</div>';

        $table = new BimpPDF_Table($this->pdf, false);
        $table->cellpadding = 0;
        $table->remove_empty_cols = false;
        $table->addCol('left', '', 95);
        $table->addCol('right', '', 95);

        $table->rows[] = array(
            'left'  => $info_html,
            'right' => $totaux_html
        );

        $this->writeContent('<br/><br/>');
        $table->write();
    }
}
