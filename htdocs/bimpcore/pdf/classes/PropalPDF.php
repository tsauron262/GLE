<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

class PropalPDF extends BimpModelPDF
{

    public static $type = 'propal';
    public $propal = null;
    public $mode = "normal";

    public function __construct($id_propal)
    {
        parent::__construct();

        $this->langs->load("bills");
        $this->langs->load("propal");
        $this->langs->load("products");

        global $db;

        $this->propal = new Propal($db);
        if ($this->propal->fetch($id_propal) <= 0) {
            $this->errors[] = 'ID propal invalide: ' . $id_propal;
        } else {
            if ($this->propal->fetch_thirdparty($this->propal->socid) <= 0) {
                $this->errors[] = 'Tiers invalide pour cette proposition commerciale';
            }

            if ($this->propal->fetch_contact($this->propal->contactid)) {
                $this->errors[] = 'Contact invalide pour cette proposition commerciale';
            }
        }
    }

    public function initData()
    {
//        $this->typeObject = "propal";
//        $this->prefName = "loyer_";
//
//        $titre = "";
//
//        $arrayHead = array("desc" => "Description", "prix" => array("Prix", "€"), "qty" => "Quantité", "remise" => array("Remise", " %"), "total_ht" => array("Total Ht", "€"), "tva" => array("Tva", " %"), "total_ttc" => array("Total Ttc", "€"));
//        $arrayData = array();
//        $arrayTot = array();
//
//        $this->object->fetch_lines();
//        foreach ($this->object->lines as $line) {
//            $arrayData[] = array("desc"      => $line->desc,
//                "prix"      => $line->subprice,
//                "qty"       => $line->qty,
//                "remise"    => $line->remise_percent,
//                "total_ht"  => $line->total_ht,
//                "tva"       => $line->tva_tx,
//                "total_ttc" => $line->total_ttc);
//
//            $arrayTot["prix"] += $line->subprice;
//        }
//        $tabHtml = $this->renderTable($arrayHead, $arrayData);
//
//        if (isset($this->object) && is_object($this->object)) {
//            $titre .= "<h2>" . get_class($this->object) . " " . $this->object->ref . "</h2>";
//        }
//
//        $this->text .= $this->pdf->renderTemplate($this->tpl_dir . '/table.html', array("titre" => $titre, "table" => $tabHtml));
//
//        //ci apres juste pour les test
//        if ($this->mode == "loyer")
//            $this->text .= "<h3>En mode Loyer</h3>";
//
//        $espace = "";
//        $this->text .= "<br/><br/><br/>";
//        for ($i = 0; $i < 100; $i++) {
//            $espace .= " - ";
//            $this->text .= "<br/>" . $espace . "Ligne n°" . $i;
//        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        global $conf, $db;

        $docName = $this->langs->transnoentities('CommercialProposal');
        $docRef = $this->langs->transnoentities("Ref") . " : " . $this->langs->convToOutputCharset($this->propal->ref);

        $rows = '';
        $nRows = 0;

        // Réf client: 
        if ($this->propal->ref_client) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('RefCustomer') . ' : ' . $this->langs->convToOutputCharset($this->propal->ref_client) . '</div>';
            $nRows++;
        }

        // Dates: 
        if (!empty($this->propal->date)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('Date') . ' : ' . dol_print_date($this->propal->date, "day", false, $this->langs) . '</div>';
            $nRows++;
        }

        if (!empty($this->propal->fin_validite)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('DateEndPropal') . ' : ' . dol_print_date($this->propal->fin_validite, "day", false, $this->langs, true) . '</div>';
            $nRows++;
        }

        // Code client: 
        if (isset($this->propal->thirdparty->code_client)) {
            $rows .= '<div class="row">' . $this->langs->transnoentities('CustomerCode') . ' : ' . $this->langs->transnoentities($this->propal->thirdparty->code_client) . '</div>';
            $nRows++;
        }

        // Commercial: 
        if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
            $contacts = $this->propal->getIdContact('internal', 'SALESREPFOLL');
            if (count($contacts)) {
                $usertmp = new User($db);
                $usertmp->fetch($contacts[0]);
                $rows .= '<div class="row">' . $this->langs->transnoentities('SalesRepresentative') . ' : ' . $usertmp->getFullName($this->langs) . '</div>';
                $nRows++;
            }
        }

        // Objets liés:
        $linkedObjects = pdf_getLinkedObjects($this->propal, $this->langs);

        if (!empty($linkedObjects)) {
            foreach ($linkedObjects as $lo) {
                $refObject = $lo['ref_title'] . ' : ' . $lo['ref_value'];
                if (!empty($lo['date_value'])) {
                    $refObject .= ' / ' . $lo['date_value'];
                }

                $rows .= '<div class="row">' . $refObject . '</div>';
                $nRows++;
            }
        }

        $this->pdf->topMargin = 40;

        if ($nRows > 2) {
            $this->pdf->topMargin += 4 * ($nRows - 2);
        }

        $this->header_vars['header_right'] = $this->renderTemplate(self::$tpl_dir . 'header_right.html', array(
            'doc_name' => $docName,
            'doc_ref'  => $docRef,
            'rows'     => $rows
        ));
    }

    protected function renderContent()
    {
        global $conf, $mysoc, $db;

        $this->writeContent($this->renderAddresses($this->propal->thirdparty, $this->propal->contact));

        $table = new BimpPDF_AmountsTable($this->pdf);

        $lines = $this->propal->lines;
        $i = 0;

        $localtax1 = array();
        $localtax2 = array();
        $tva = array();

        // Traitement des lignes: 

        foreach ($lines as $line) {
            $row = array(
                'desc'      => $line->desc,
                'total_ht'  => BimpTools::displayMoneyValue($line->total_ht, ''),
                'total_ttc' => BimpTools::displayMoneyValue($line->total_ttc, '')
            );

            $row['pu_ht'] = pdf_getlineupexcltax($this->propal, $i, $this->langs);
            $row['qte'] = pdf_getlineqty($this->propal, $i, $this->langs);

            if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
                $row['tva'] = pdf_getlinevatrate($this->propal, $i, $this->langs);
            }

            if ($conf->global->PRODUCT_USE_UNITS) {
                $row['unite'] = pdf_getlineunit($this->propal, $i, $this->langs);
            }

            if ($line->remise_percent) {
                $row['reduc'] = pdf_getlineremisepercent($this->propal, $i, $this->langs);
            }

            $row['total_ht'] = pdf_getlinetotalexcltax($this->propal, $i, $this->langs);

            if ($conf->multicurrency->enabled && $this->propal->multicurrency_tx != 1)
                $tva_line = $line->multicurrency_total_tva;
            else
                $tva_line = $line->total_tva;

            $localtax1ligne = $line->total_localtax1;
            $localtax2ligne = $line->total_localtax2;
            $localtax1_rate = $line->localtax1_tx;
            $localtax2_rate = $line->localtax2_tx;
            $localtax1_type = $line->localtax1_type;
            $localtax2_type = $line->localtax2_type;

            if ($this->propal->remise_percent)
                $tvaligne-=($tvaligne * $this->propal->remise_percent) / 100;
            if ($this->propal->remise_percent)
                $localtax1ligne-=($localtax1ligne * $this->propal->remise_percent) / 100;
            if ($this->propal->remise_percent)
                $localtax2ligne-=($localtax2ligne * $this->propal->remise_percent) / 100;

            $vatrate = (string) $line->tva_tx;

            // Retrieve type from database for backward compatibility with old records
            if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
                    && (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
                $localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $this->propal->thirdparty, $mysoc);
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

            $table->rows[] = $row;
            $i++;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);

        // *** Informations:  *** 
        $content = '';

        // Date de livraison
        if (!empty($this->propal->date_livraison)) {
            $content .= '<p>' . dol_print_date($this->propal->date_livraison, "daytext", false, $this->langs, true) . '</p>';
            $this->renderContent($content);
        } elseif ($this->propal->availability_code || (isset($this->propal->availability) && $this->propal->availability)) {
            $content .= '<p><strong>' . $this->langs->transnoentities("AvailabilityPeriod") . ': </strong>';
            $label = $this->langs->transnoentities("AvailabilityType" . $this->propal->availability_code) != ('AvailabilityType' . $this->propal->availability_code) ? $this->langs->transnoentities("AvailabilityType" . $this->propal->availability_code) : $this->langs->convToOutputCharset($this->propal->availability);
            $label = str_replace('\n', "\n", $label);
            $content .= $label . '</p>';
        }

        // Conditions de paiement: 
        if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND) && ($this->propal->cond_reglement_code || $this->propal->cond_reglement)) {
            $content .= '<p>';
            $content .= '<strong>' . $this->langs->transnoentities("PaymentConditions") . ': </strong>';
            $label = $this->langs->transnoentities("PaymentCondition" . $this->propal->cond_reglement_code) != ('PaymentCondition' . $this->propal->cond_reglement_code) ? $this->langs->transnoentities("PaymentCondition" . $this->propal->cond_reglement_code) : $this->langs->convToOutputCharset($this->propal->cond_reglement_doc);
            $label = str_replace('\n', "\n", $label);
            $content .= $label . '</p>';
        }

        // Mode de paiement: 
        if ($this->propal->mode_reglement_code && $this->propal->mode_reglement_code != 'CHQ' && $this->propal->mode_reglement_code != 'VIR') {
            $content .= '<p><strong>' . $this->langs->transnoentities("PaymentMode") . '</strong>: ';
            $content .= $this->langs->transnoentities("PaymentType" . $this->propal->mode_reglement_code) != ('PaymentType' . $this->propal->mode_reglement_code) ? $this->langs->transnoentities("PaymentType" . $this->propal->mode_reglement_code) : $this->langs->convToOutputCharset($this->propal->mode_reglement);
            $content .= '</p>';
        }

        if (empty($this->propal->mode_reglement_code) || $this->propal->mode_reglement_code == 'CHQ') {

            if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
                    $account = new Account($db);
                    $account->fetch($conf->global->FACTURE_CHQ_NUMBER);

                    $content .= '<p>' . $this->langs->transnoentities('PaymentByChequeOrderedTo', $account->proprio) . '</p>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $content .= '<p>' . $this->langs->convToOutputCharset($account->owner_address) . '</p>';
                    }
                } elseif ($conf->global->FACTURE_CHQ_NUMBER == -1) {
                    $content .= '<p>' . $this->langs->transnoentities('PaymentByChequeOrderedTo', $this->fromCompany->name) . '</p>';

                    if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
                        $content .= '<p>' . $this->langs->convToOutputCharset($this->fromCompany->getFullAddress()) . '</p>';
                    }
                }
            }
        }

        if (empty($this->propal->mode_reglement_code) || $this->propal->mode_reglement_code == 'VIR') {
            if (!empty($this->propal->fk_account) || !empty($this->propal->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
                $bankid = (empty($this->propal->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $this->propal->fk_account);
                if (!empty($this->propal->fk_bank)) {
                    $bankid = $this->propal->fk_bank;
                }

                $account = new Account($db);
                $account->fetch($bankid);
                $content .= $this->renderBank($account);
            }
        }

        $this->writeContent($content);

        // *** Totaux:  ***

        $table = new BimpPDF_Table($this->pdf, false);
        $table->remove_empty_cols = false;
        $table->addTableClass('no_borders');
        $table->addTableStyle('margin-left', (BimpPDF::$pxPerMm * 95) . 'px');
        $table->width = 95;
        $table->addCol('margin', '', 95);
        $table->addCol('label', '', 65, 'font-weight: bold;');
        $table->addCol('value', '', null, 'text-align: right;');

        // Total HT:
        $total_ht = ($conf->multicurrency->enabled && $this->propal->mylticurrency_tx != 1 ? $this->propal->multicurrency_total_ht : $this->propal->total_ht);
        $table->rows[] = array(
            'label' => $this->langs->transnoentities("TotalHT"),
            'value' => price($total_ht + (!empty($this->propal->remise) ? $this->propal->remise : 0), 0, $this->langs)
        );

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

                            $table->rows[] = array(
                                'label' => array(
                                    'content' => $totalvat,
                                    'style'   => 'background-color: #F0F0F0;'
                                ),
                                'value' => array(
                                    'content' => price($tvaval, 0, $this->langs),
                                    'style'   => 'background-color: #F0F0F0;'
                                ),
                            );
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

                            $table->rows[] = array(
                                'label' => array('content' => $totalvat, 'style' => 'background-color: #F0F0F0;',),
                                'value' => array('content' => price($tvaval, 0, $this->langs), 'style' => 'background-color: #F0F0F0;')
                            );
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
                        $table->rows[] = array(
                            'label' => array('content' => $totalvat, 'style' => 'background-color: #F0F0F0;'),
                            'value' => array('content' => price($tvaval, 0, $this->langs), 'style' => 'background-color: #F0F0F0;')
                        );
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

                            $table->rows[] = array(
                                'label' => array('content' => $totalvat, 'style' => 'background-color: #F0F0F0;'),
                                'value' => array('content' => price($tvaval, 0, $this->langs), 'style' => 'background-color: #F0F0F0;')
                            );
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

                            $table->rows[] = array(
                                'label' => array('content' => $totalvat, 'style' => 'background-color: #F0F0F0;'),
                                'value' => array('content' => price($tvaval, 0, $this->langs), 'style' => 'background-color: #F0F0F0;')
                            );
                        }
                    }
                }

                // Total TTC
                $total_ttc = ($conf->multicurrency->enabled && $this->propal->multicurrency_tx != 1) ? $this->propal->multicurrency_total_ttc : $this->propal->total_ttc;
                $table->rows[] = array(
                    'label' => array('content' => $this->langs->transnoentities("TotalTTC"), 'style' => 'background-color: #DCDCDC;'),
                    'value' => array('content' => price($total_ttc, 0, $this->langs), 'style' => 'background-color: #DCDCDC;')
                );
            }
        }

        $table->rows[] = array();
        $table->rows[] = array(
            'label' => array(
                'colspan' => 2,
                'style'   => 'text-align: center;',
                'content' => 'Cachet, Date, Signature et mention "Bon pour Accord"'
            )
        );
        $table->rows[] = array(
            'label' => array(
                'colspan' => 2,
                'style'   => 'border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;',
                'content' => ' <br/> <br/> <br/>'
            )
        );

        $table->write();
    }
}
