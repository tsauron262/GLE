<?php

require_once __DIR__ . '/BimpDocumentPDF.php';

class BimpCommDocumentPDF extends BimpDocumentPDF
{
    # Constantes: 

    public static $label_prime = "Apport externe";
    public static $label_prime2 = "Apport externe2";
    public static $use_cgv = true;

    # Objets: 
    public $bimpCommObject = null;

    # Totaux: 
    public $total_remises = 0;
    public $localtax1 = array();
    public $localtax2 = array();
    public $acompteHt = 0;
    public $acompteTtc = 0;
    public $acompteTva = array();
    public $tva = array();
    public $ht = array();
    public $totals = array("DEEE" => 0, "RPCP" => 0);

    # Paramètres: 
    public $proforma = 0;
    public $periodicity = 0;
    public $nbPeriods = 0;
    public $max_line_serials = 50;

    # Options: 
    public $hide_pu = false;
    public $hideReduc = false;
    public $hideTtc = false;
    public $hideTotal = false;
    public $hideRef = false;
    public $hidePrice = false;

    public function __construct($db)
    {
        parent::__construct($db);
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
                if (isset($this->object->array_options['options_pdf_hide_pu'])) {
                    $this->hide_pu = (int) $this->object->array_options['options_pdf_hide_pu'];
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

                if (is_null($this->contact) && method_exists($this->object, 'getIdContact')) {
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
                    if (!isset($this->object->thirdparty) && method_exists($this->object, 'fetch_thirdparty')) {
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

                            if (BimpCore::isEntity('bimp')) {
                                if ($this->fromCompany->name == "Bimp Groupe Olys")
                                    $this->fromCompany->name = "Bimp Olys SAS";

                                if ($entrepot->ref == "PR") {
                                    $this->fromCompany->address = "2 rue des Erables CS 21055  ";
                                    $this->fromCompany->town = "LIMONEST";
                                    $this->fromCompany->zip = "69760";
                                }
                            }
                        }
                    }
                }

                if (isset($this->object->statut) && !(int) $this->object->statut) {
                    $this->watermark = 'BROUILLON';
                }
            }
        }

        if (!is_null($this->bimpCommObject) && is_null($this->bimpObject)) {
            $this->bimpObject = $this->bimpCommObject;
        }
    }

    protected function initHeader()
    {
        parent::initHeader();

        $doc_ref = '';
        $ref_extra = '';

        if (is_object($this->object) && isset($this->object->ref)) {
            $doc_ref = $this->object->ref;
        }

        if (BimpObject::objectLoaded($this->bimpCommObject)) {
            if (!$doc_ref) {
                $doc_ref = $this->bimpCommObject->getRef(false);
            }
            if ($this->bimpCommObject->field_exists('replaced_ref')) {
                $replaced_ref = $this->bimpCommObject->getData('replaced_ref');
                if ($replaced_ref) {
                    $ref_extra = '<br/><span style="font-weight: bold;font-size: 8px">Annule et remplace ' . $this->bimpCommObject->getLabel('the') . ' "' . $replaced_ref . '"</span>';
                    $this->pdf->topMargin += 4;
                }
            }
        }

        $this->header_vars['doc_ref'] = $doc_ref;
        $this->header_vars['ref_extra'] = $ref_extra;
    }

    // Getters: 

    public function getFromUsers()
    {
        $users = array();

        $comm1 = $comm2 = 0;
        $contacts = array();
        if (method_exists($this->object, 'getIdContact')) {
            $contacts = $this->object->getIdContact('internal', 'SALESREPFOLL');
            if (is_array($contacts) && count($contacts)) {
                $comm1 = $contacts[0];
            }

            $contacts = $this->object->getIdContact('internal', 'SALESREPSIGN');
            if (is_array($contacts) && count($contacts)) {
                $comm2 = $contacts[0];
            }
        }

        $label = 'Interlocuteur';

        if ($comm1 > 0) {
            if ($comm2 > 0 && $comm1 != $comm2) {
                $label .= ' client';
            }
            $users[$comm1] = $label;
        }

        if ($comm2 > 0) {
            if (!$comm1 || ($comm1 > 0 && $comm1 != $comm2)) {
                if ($comm1 > 0) {
                    $label = 'Emetteur';
                } else {
                    $label = 'Interlocuteur';
                }
            }
            $users[$comm2] = $label;
        }

        return $users;
    }

    public function getTargetIdSoc()
    {
        if (isset($this->object->socid)) {
            return $this->object->socid;
        }

        return parent::getTargetIdSoc();
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

//            if ($product->type == 1) {
            if ($line->date_start) {
                if (!$line->date_end) {
                    $desc .= '<br/>A partir du ';
                } else {
                    $desc .= '<br/>Du ';
                }
                $desc .= date('d/m/Y', $line->date_start);
            }
            if ($line->date_end) {
                if (!$line->date_start) {
                    $desc .= '<br/>Jusqu\'au ';
                } else {
                    $desc .= ' au ';
                }
                $desc .= date('d/m/Y', $line->date_end);
            }
//            }
        }

        if (!is_null($line->desc) && $line->desc) {
            $line_desc = $line->desc;
            if (!is_null($product)) {
                if (preg_match('/^' . preg_quote($product->label, '/') . '(.*)$/', $line_desc, $matches)) {
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

    public function getBottomLeftHtml()
    {
        return $this->getPaymentInfosHtml();
    }

    public function getPaymentInfosHtml()
    {
        return '';
    }

    public function getBottomRightHtml()
    {

        $html = $this->getTotauxRowsHtml();
        $html .= $this->getPaymentsHtml();
        $html .= $this->getAfterTotauxHtml();

        return $html;
    }

    public function getTotauxRowsHtml()
    {
        global $conf;

        $htmlInfo = $html = "";
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
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . BimpTools::displayMoneyValue($total_remises, '', 0, 0, 1);
            if ((int) $this->periodicity) {
                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
            }
            $html .= '</td>';
            $html .= '</tr>';
        }


        if ($this->object->array_options['options_pdf_nb_decimal'] > 0) {
            $modeDecimalTotal = $this->object->array_options['options_pdf_nb_decimal'];
        } else {
            $modeDecimalTotal = 2;
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
        $html .= BimpTools::displayMoneyValue($total_ht, '', 0, 0, 1, $modeDecimalTotal);

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
            $html .= '<td style="background-color: #DCDCDC;text-align: right;">' . BimpTools::displayMoneyValue($total_deee, '', 0, 0, 1, 'full');
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
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue($tvaval, '', 0, 0, 1, $modeDecimalTotal);
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
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue($tvaval, '', 0, 0, 1, $modeDecimalTotal);
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
                    $ht = $this->ht[$tvakey];
                    if (($ht != 0 && $tvaval != 0) || $ht > 0) {
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
                            $html .= '<td style="background-color: #F0F0F0;">' . $totalvat . ' (' . BimpTools::displayMoneyValue($ht, '', 0, 0, 1, $modeDecimalTotal) . ' €)</td>';
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue($tvaval, '', 0, 0, 1, $modeDecimalTotal);
                            if ((int) $this->periodicity) {
                                $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                            }
                            $html .= '</td>';
                            $html .= '</tr>';

                            //todo phrase suivant tva 
//                            $infos =  array();
//                                    $infos[] ="mmmffff30";
//                            switch ($tvakey){
//                                case 0:
//                                    $infos[] = "mmm";
//                                case 20:
//                                    $infos[] ="mmmffff30";
//                            }
//                            $htmlInfo  .= implode("<br/>", $infos);
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
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue($tvaval, '', 0, 0, 1, $modeDecimalTotal);
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
                            $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue($tvaval, '', 0, 0, 1, $modeDecimalTotal);
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
                $html .= '<td style="background-color: #DCDCDC; text-align: right;">' . BimpTools::displayMoneyValue($total_ttc, '', 0, 0, 1, $modeDecimalTotal);
                if ((int) $this->periodicity) {
                    $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                }
                $html .= '</td>';
                $html .= '</tr>';

                if (isset($this->object->array_options['options_prime']) || isset($this->object->array_options['options_prime2'])) {
                    $prime = $prime2 = 0;
                    if (isset($this->object->array_options['options_prime']))
                        $prime = $this->object->array_options['options_prime'];
                    if (isset($this->object->array_options['options_prime2']))
                        $prime2 = $this->object->array_options['options_prime2'];

                    if ($prime > 0) {
                        $html .= '<tr>';
                        $html .= '<td style="background-color: #F0F0F0;">' . static::$label_prime . '</td>';
                        $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue(-$prime, '', 0, 0, 1, $modeDecimalTotal);
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                    if ($prime2 > 0) {
                        $html .= '<tr>';
                        $html .= '<td style="background-color: #F0F0F0;">' . static::$label_prime2 . '</td>';
                        $html .= '<td style="background-color: #F0F0F0; text-align: right;">' . BimpTools::displayMoneyValue(-$prime2, '', 0, 0, 1, $modeDecimalTotal);
                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    if ($prime > 0 || $prime2 > 0) {
                        $html .= '<tr>';
                        $html .= '<td style="background-color: #DCDCDC;">Reste à charge</td>';
                        $html .= '<td style="background-color: #DCDCDC; text-align: right;">' . BimpTools::displayMoneyValue($total_ttc - $prime - $prime2, '', 0, 0, 1, $modeDecimalTotal);
                        if ((int) $this->periodicity) {
                            $html .= ' / ' . BimpComm::$pdf_periodicity_label_masc[(int) $this->periodicity];
                        }
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                }
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

        $deja_regle = round($deja_regle, 2);
        $creditnoteamount = round($creditnoteamount, 2);
        $depositsamount = round($depositsamount, 2);

        if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) {
            $html .= '<tr>';
            $html .= '<td style="">' . $this->langs->transnoentities("Paid") . '</td>';
            $html .= '<td style="text-align: right;">' . BimpTools::displayMoneyValue($deja_regle + $depositsamount, '', 0, 0, 1) . '</td>';
            $html .= '</tr>';

            if ($creditnoteamount) {
                $html .= '<tr>';
                $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("CreditNotes") . '</td>';
                $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . BimpTools::displayMoneyValue($creditnoteamount, '', 0, 0, 1) . '</td>';
                $html .= '</tr>';
            }

            BimpTools::loadDolClass('compta/facture', 'facture');
            if (isset($this->object->close_code) && $this->object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
                $html .= '<tr>';
                $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("EscompteOfferedShort") . '</td>';
                $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . BimpTools::displayMoneyValue($this->object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, '', 0, 0, 1) . '</td>';
                $html .= '</tr>';
                $resteapayer = 0;
            }
        }

        if ($this->acompteHt > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #F0F0F0;">' . $this->langs->transnoentities("Acompte") . '</td>';
            $html .= '<td style="text-align: right; background-color: #F0F0F0;">' . BimpTools::displayMoneyValue($this->acompteTtc, '', 0, 0, 1) . '</td>';
            $html .= '</tr>';
        }

        $resteapayer = round($resteapayer, 2);

        if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0 || $this->acompteHt > 0) {
            $html .= '<tr>';
            $html .= '<td style="background-color: #DCDCDC;">' . $this->langs->transnoentities("RemainderToPay") . '</td>';
            $html .= '<td style="text-align: right; background-color: #DCDCDC;">' . BimpTools::displayMoneyValue($resteapayer, '', 0, 0, 1) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '<br/>';

        return $html . $htmlInfo;
    }

    public function getPaymentsHtml()
    {
        return '';
    }

    public function getAfterTotauxHtml()
    {
        $html = '<table style="width: 95%" cellpadding="3">';

        global $mysoc, $langs;
        // If France, show VAT mention if not applicable
        if (!$mysoc->tva_assuj) {
            $html .= $langs->transnoentities("VATIsNotUsedForInvoice") . '<br/>';
        }

        /* if (!is_null($this->contact) && isset($this->contact->id) && $this->contact->id) {
          $html .= '<tr>';
          $html .= '<td style="text-align: center;">' . $this->contact->lastname . ' ' . $this->contact->firstname;
          $html .= (isset($this->contact->poste) && $this->contact->poste ? ' - ' . $this->contact->poste : '') . '</td>';
          $html .= '</tr>';
          } */

        if (is_a($this->bimpCommObject, 'BimpComm') && in_array($this->bimpCommObject->object_name, array('Bimp_Propal', 'BS_SavPropal')) &&
                $this->bimpCommObject->getData('ef_type') != 'M' && (int) BimpCore::getConf('propal_pdf_chorus_mention', null, 'bimpcommercial')) {
            $html .= '<tr>';
            $html .= '<td colspan="2">';
            $html .= '<span style="font-weight: bold; color: #' . $this->primary . '">NB : les administrations publiques doivent obligatoirement fournir les informations nécessaires au dépôt de la facture <br/>sur le portail Chorus</span>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    // Rendus: 

    public function renderTop()
    {
        if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
            $this->writeContent('<p style="font-size: 10px">Objet : <strong>' . BimpObject::replaceHastags($this->object->array_options['options_libelle'], true) . '</strong></p>');
        }

        if (isset($this->object->note_public) && $this->object->note_public) {
            $html = '<div style="font-size: 7px; line-height: 8px;">';
            $html .= BimpObject::replaceHastags($this->object->note_public, true);
            $html .= '</div>';

            if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
                $this->pdf->addVMargin(2);
            }

            $this->writeContent($html);
        }
    }

    public function renderLines()
    {
        global $conf, $user;

        $table = new BimpPDF_AmountsTable($this->pdf);

        if ($this->hidePrice)
            $table->setCols(array('desc'));

        if (method_exists($this, 'setAmountsTableParams')) {
            $this->setAmountsTableParams($table);
        }

        $remises_globales = array();
        $remises_globalesHt = array();
        $bimpLines = array();

        if (BimpObject::objectLoaded($this->bimpCommObject) && is_a($this->bimpCommObject, 'BimpComm')) {
            foreach ($this->bimpCommObject->getChildrenObjects('lines') as $bimpLine) {
                $bimpLines[(int) $bimpLine->getData('id_line')] = $bimpLine;
            }
        }

        BimpTools::loadDolClass('product');

        $i = -1;
        $total_ht_without_remises = 0;
        $total_ttc_without_remises = 0;

        $lines_remise_global_amount_ht = 0;
        $lines_remise_global_amount_ttc = 0;

        $sub_total_ht = 0;
        $sub_total_ttc = 0;

        if (is_array($this->object->lines) && !empty($this->object->lines)) {
            foreach ($this->object->lines as $line) {
                $row = array();
                $i++;

                $bimpLine = isset($bimpLines[(int) $line->id]) ? $bimpLines[(int) $line->id] : null;

                if ($this->object->type != 3 && BimpObject::objectLoaded($bimpLine) && !in_array((int) $bimpLine->getData('type'), array(ObjectLine::LINE_TEXT, ObjectLine::LINE_SUB_TOTAL)) && ($line->desc == "(DEPOSIT)" || stripos($line->desc, 'Acompte') === 0 || stripos($line->desc, 'Trop per') === 0)) {
//                $acompteHt = $line->subprice * (float) $line->qty;
//                $acompteTtc = BimpTools::calculatePriceTaxIn($acompteHt, (float) $line->tva_tx);

                    $total_ht_without_remises += $line->total_ht;
                    $total_ttc_without_remises += $line->total_ttc;

                    $this->acompteHt -= $line->total_ht;
                    $this->acompteTtc -= $line->total_ttc;

                    $this->acompteTva[$line->tva_tx] -= $line->total_tva;

                    continue;
                }

                if (BimpObject::objectLoaded($bimpLine) && $bimpLine->field_exists('hide_in_pdf')) {
                    if (in_array((int) $bimpLine->getData('type'), array(ObjectLine::LINE_TEXT, ObjectLine::LINE_SUB_TOTAL)) || ((float) $bimpLine->pu_ht * (float) $bimpLine->getFullQty() == 0)) {
                        if ((int) $bimpLine->getData('hide_in_pdf')) {
                            continue;
                        }
                    }
                }

                $product = null;
                if (!is_null($line->fk_product) && $line->fk_product) {
                    $product = new Product($this->db);
                    if ($product->fetch((int) $line->fk_product) <= 0) {
                        unset($product);
                        $product = null;
                    }
                }
                if (is_object($product) && $product->ref == "REMISECRT" && $line->total_ht == 0) {
                    continue;
                }

                $hide_product_label = isset($bimpLines[(int) $line->id]) ? (int) $bimpLines[(int) $line->id]->getData('hide_product_label') : 0;

                $desc = $this->getLineDesc($line, $product, $hide_product_label);

                if (BimpObject::objectLoaded($bimpLine)) {
                    if ($bimpLine->equipment_required && $bimpLine->isProductSerialisable()) {
                        $serials = $bimpLine->getSerials(true);

                        $desc .= '<br/>';
                        $desc .= '<span style="font-size: 9px;">N° de série: </span>';
                        $fl = true;
                        $desc .= '<span style="font-size: 9px; font-style: italic">';

                        if (count($serials) > (int) $this->max_line_serials/* && (int) $user->id === 1 */) {
                            $desc .= 'voir annexe';
                            if (!isset($this->annexe_listings['serials'])) {
                                $this->annexe_listings['serials'] = array(
                                    'title' => 'Numéros de série',
                                    'lists' => array()
                                );
                            }

                            $this->annexe_listings['serials']['lists'][] = array(
                                'title' => 'Référence "' . $product->ref . '" - ' . $product->label,
                                'cols'  => 8,
                                'items' => $serials
                            );
                        } else {
                            foreach ($serials as $serial) {
                                if (!$fl) {
                                    $desc .= ', ';
                                } else {
                                    $fl = false;
                                }
                                $desc .= $serial;
                            }
                        }
                        $desc .= '</span>';
                    }
                }

                if ((BimpObject::objectLoaded($bimpLine) && (int) $bimpLine->getData('type') === ObjectLine::LINE_TEXT) ||
                        (!BimpObject::objectLoaded($bimpLine) && $line->subprice == 0 && !(int) $line->fk_product)) {
                    $row['desc'] = array(
                        'colspan' => 99,
                        'content' => $this->cleanHtml($desc),
                        'style'   => ' background-color: #F5F5F5;'
                    );
                } elseif (BimpObject::objectLoaded($bimpLine) && (int) $bimpLine->getData('type') === ObjectLine::LINE_SUB_TOTAL) {
                    $row['desc'] = array(
                        'content' => ((string) $line->desc ? $this->cleanHtml($line->desc) : 'Sous-total'),
                        'style'   => ' font-weight: bold; background-color: #DFDFDF;'
                    );
                    $row['total_ht'] = array(
                        'content' => BimpTools::displayMoneyValue($sub_total_ht, '', 0, 0, 1),
                        'style'   => ' font-weight: bold; background-color: #DFDFDF;'
                    );

                    if (!$this->hideTtc) {
                        $row['total_ttc'] = array(
                            'content' => BimpTools::displayMoneyValue($sub_total_ttc, '', 0, 0, 1),
                            'style'   => ' font-weight: bold; background-color: #DFDFDF;'
                        );
                    }

                    $sub_total_ht = 0;
                    $sub_total_ttc = 0;
                } else {
                    $line_remise = $line->remise_percent;

                    if (BimpObject::objectLoaded($bimpLine)) {
                        if ($bimpLine->isRemisable()) {
                            $remises_infos = $bimpLine->getRemiseTotalInfos();
                            $line_remise = $remises_infos['line_percent'];
                            if (!empty($remises_infos['remises_globales'])) {
                                foreach ($remises_infos['remises_globales'] as $id_rg => $rg_data) {
                                    if (!isset($remises_globales[(int) $id_rg])) {
                                        $remises_globales[(int) $id_rg] = 0;
                                        $remises_globalesHt[(int) $id_rg] = 0;
                                    }

                                    $remises_globales[(int) $id_rg] += (float) $rg_data['amount_ttc'];
                                    $remises_globalesHt[(int) $id_rg] += (float) $rg_data['amount_ht'];
                                }
                            }
                        } else {
                            $line_remise = 0;
                        }
                    }

                    $row = array(
                        'desc' => $this->cleanHtml($desc)
                    );

                    $pu_ht_with_remise = (float) ($line->subprice - ($line->subprice * ($line_remise / 100)));

                    if ($this->hideReduc && $line_remise) {
                        $pu_ht = $pu_ht_with_remise;
                    } else {
                        $pu_ht = $line->subprice;
                    }

                    if ($this->object->array_options['options_pdf_nb_decimal'] > 0) {
                        $modeDecimal = $this->object->array_options['options_pdf_nb_decimal'];
                        $modeDecimalTotal = $this->object->array_options['options_pdf_nb_decimal'];
                    } else {
                        $nbDecimalPu = BimpTools::getDecimalesNumber($pu_ht);
                        $modeDecimal = ($nbDecimalPu > 3 ? 'full' : 2);
                        $modeDecimalTotal = 2;
                    }

                    $row['pu_ht'] = BimpTools::displayMoneyValue($pu_ht, '', 0, 0, 1, $modeDecimal);

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

                    $sub_total_ht += $row_total_ht;
                    $sub_total_ttc += $row_total_ttc;

                    $row['total_ht'] = BimpTools::displayMoneyValue($row_total_ht, '', 0, 0, 1, $modeDecimalTotal);

                    if (!$this->hideTtc) {
                        $row['total_ttc'] = BimpTools::displayMoneyValue($row_total_ttc, '', 0, 0, 1, $modeDecimalTotal);
                    }
                    if (!$this->hideReduc) {
                        $row['pu_remise'] = BimpTools::displayMoneyValue($pu_ht_with_remise, '', 0, 0, 1, $modeDecimal);
                    }

                    $total_ht_without_remises += $line->subprice * (float) $line->qty;
                    $total_ttc_without_remises += BimpTools::calculatePriceTaxIn($line->subprice * (float) $line->qty, (float) $line->tva_tx);

                    if (isset($bimpLines[$line->id])) {
                        if ($bimpLine->getData("force_qty_1")) {
                            if ($row['qte'] > 0) {
                                $row['pu_ht'] = BimpTools::displayMoneyValue($pu_ht * $row['qte'], '', 0, 0, 1, $modeDecimal);
                                $product->array_options['options_deee'] = $product->array_options['options_deee'] * $row['qte'];
                                $product->array_options['options_rpcp'] = $product->array_options['options_rpcp'] * $row['qte'];
                                if (isset($row['pu_remise'])) {
                                    $row['pu_remise'] = BimpTools::displayMoneyValue($pu_ht_with_remise * $row['qte'], "", 0, 0, 1, $modeDecimal);
                                }
                                $row['qte'] = 1;
                            } elseif ($row['qte'] < 0) {
                                $row['pu_ht'] = BimpTools::displayMoneyValue(str_replace(",", ".", $row['pu_ht']) * ($row['qte'] * -1), '', 0, 0, 1, $modeDecimal);
                                $product->array_options['options_deee'] = $product->array_options['options_deee'] * ($row['qte'] * -1);
                                $product->array_options['options_rpcp'] = $product->array_options['options_rpcp'] * ($row['qte'] * -1);
                                if (isset($row['pu_remise'])) {
                                    $row['pu_remise'] = BimpTools::displayMoneyValue($pu_ht_with_remise * ($row['qte'] * -1), "", 0, 0, 1, $modeDecimal);
                                }
                                $row['qte'] = -1;
                            }
                        } else {
                            $bimpLine = $bimpLines[$line->id];
                            if ($bimpLine->object_name === 'Bimp_FactureLine') {
                                if ($bimpLine->getData('linked_object_name') === 'commande_line' && (int) $bimpLine->getData('linked_id_object')) {
                                    $comm_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $bimpLine->getData('linked_id_object'));
                                    if (BimpObject::objectLoaded($comm_line)) {
                                        if ((int) $comm_line->getData('fac_periodicity') && $comm_line->getData('fac_nb_periods')) {
                                            $nb_periods = (float) $row['qte'];

                                            $comm_full_qty = (float) $comm_line->getFullQty();
                                            if ($comm_full_qty) {
                                                $nb_periods /= $comm_full_qty;
                                            }

                                            $nb_periods *= (int) $comm_line->getData('fac_nb_periods');
                                            $nb_month = round($nb_periods * (int) $comm_line->getData('fac_periodicity'));
                                            $row['qte'] = round((float) $row['qte'], 6);
                                            $row['qte'] .= '<br/>';
                                            $row['qte'] .= '(' . $nb_month . ' mois <br/>x ' . $comm_full_qty . ' unité' . ($comm_full_qty > 1 ? 's' : '') . ')';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                /* Pour les ecotaxe et copie privé */
                $row['object'] = $product;
                if (is_object($product) && $row['qte']) {
                    if (isset($product->array_options['options_deee']) && $product->array_options['options_deee'] > 0)
                        $this->totals['DEEE'] += $product->array_options['options_deee'] * $row['qte'];
                    if (isset($product->array_options['options_rpcp']) && $product->array_options['options_rpcp'] > 0)
                        $this->totals['RPCP'] += $product->array_options['options_rpcp'] * $row['qte'];
                }

                $row = $this->traitePeriodicity($row, array('pu_ht', 'pu_remise', 'total_ht', 'total_ttc'));

                if ($this->hide_pu) {
                    unset($row['pu_ht']);
                }

                if (isset($row['desc']) && $row['desc']) {
                    $row['desc'] = $this->replaceHtmlStyles($row['desc']);
                }

                $table->rows[] = $row;
            }
        }
        // Remise globale
        if (!empty($remises_globales)) {
            foreach ($remises_globales as $id_rg => $rg_amount_ttc) {
                if ($rg_amount_ttc != 0) {
                    $rg = BimpCache::getBimpObjectInstance('bimpcommercial', 'RemiseGlobale', (int) $id_rg);
                    if (BimpObject::objectLoaded($rg)) {
                        $remise_label = $rg->getData('label');

                        if ($rg->getData('obj_type') !== $this->bimpCommObject::$element_name ||
                                (int) $rg->getData('id_obj') !== (int) $this->bimpCommObject->id) {
                            $rg_obj = $rg->getParentObject();
                            if (BimpObject::objectLoaded($rg_obj)) {
                                $remise_label .= ' (' . BimpTools::ucfirst($rg_obj->getLabel()) . ' ' . $rg_obj->getRef() . ')';
                            }
                        }
                    }

                    if (!$remise_label) {
                        $remise_label = 'Remise exceptionnelle';
                    }

                    $row = array(
                        'desc'     => $remise_label,
                        'qte'      => '',
                        'tva'      => '',
                        'pu_ht'    => '', //BimpTools::displayMoneyValue(-$rg_amount_ttc, ''),
                        'total_ht' => '', //BimpTools::displayMoneyValue(-$rg_amount_ttc, '')
                    );
                    if (!$this->hideTtc)
                        $row['total_ttc'] = BimpTools::displayMoneyValue(-$rg_amount_ttc, '', 0, 0, 1);
                    if (isset($remises_globalesHt[$id_rg]))
                        $row['total_ht'] = BimpTools::displayMoneyValue(-$remises_globalesHt[$id_rg], '', 0, 0, 1);
                    //                if (!$this->hideReduc)
                    //                    $row['pu_remise'] = BimpTools::displayMoneyValue(-$rg_amount_ttc, '');

                    if ($this->hide_pu) {
                        unset($row['pu_ht']);
                    }

                    $table->rows[] = $row;
                }
            }
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function renderAfterLines()
    {
        if (BimpCore::getEntity() === 'bimp') {
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

            if (BimpCore::getConf('pdf_add_cgv', 0, 'bimpcommercial') && static::$use_cgv) {
                $html .= '<span style="font-weight: bold;">';
                if ($this->pdf->addCgvPages)
                    $html .= 'La signature de ce document vaut acceptation de nos Conditions Générales de Vente annexées et consultables sur le site www.bimp-pro.fr pour les professionnels et sur www.bimp.fr pour les particuliers.';
                else
                    $html .= 'Nos Conditions Générales de Vente sont consultables sur le site www.bimp-pro.fr pour les professionnels et sur www.bimp.fr pour les particuliers.';
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

                $html .= '<p style="font-size: 6px; font-style: italic">Merci de noter systématiquement le n° de facture sur votre règlement.</p>';
            }

            $this->writeContent($html);
        }
    }

    // Traitements: 

    public function traitePeriodicity($row, $champs)
    {
        if ((int) $this->periodicity && (int) $this->nbPeriods > 0) {
            foreach ($champs as $nomChamp) {
                if (isset($row[$nomChamp])) {
                    $value = '';

                    if (is_string($row[$nomChamp])) {
                        $value = $row[$nomChamp];
                    } elseif (isset($row[$nomChamp]['content'])) {
                        $value = $row[$nomChamp]['content'];
                    }

                    if ($value) {
                        $value = str_replace(' ', '', $value);
                        $value = str_replace(",", ".", $value);

                        $row[$nomChamp] = BimpTools::displayMoneyValue($value / $this->nbPeriods, '', 0, 0, 1);
                    }
                }
            }
        }
        return $row;
    }

    public function calcTotaux()
    {
        global $conf, $mysoc;

        // /!\ ATTENTION: ne surtout pas oublier de réinitialiser toutes les variables de classe définies ici
        // La fonction peut être appellée plusieurs fois dans certain cas. 

        $this->total_remises = 0;
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->tva = array();
        $this->ht = array();

        $bimpLines = array();
        if (BimpObject::objectLoaded($this->bimpCommObject) && is_a($this->bimpCommObject, 'BimpComm')) {
            foreach ($this->bimpCommObject->getChildrenObjects('lines') as $bimpLine) {
                $bimpLines[(int) $bimpLine->getData('id_line')] = $bimpLine;
            }
        }

        $i = 0;

        if (isset($this->object->lines) && is_array($this->object->lines)) {
            foreach ($this->object->lines as $line) {
                $bimpLine = isset($bimpLines[(int) $line->id]) ? $bimpLines[(int) $line->id] : null;

                if (!$this->hideReduc && $line->remise_percent) {
                    if (BimpObject::objectLoaded($bimpLine)) {
                        $remise_infos = $bimpLine->getRemiseTotalInfos();
                        $this->total_remises += (float) $remise_infos['line_amount_ht'] + (float) $remise_infos['global_amount_ht'] + $remise_infos['ext_global_amount_ht'];
                    } else {
                        $this->total_remises += ((float) $line->subprice * ((float) $line->remise_percent / 100)) * (float) $line->qty;
                    }
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

                if (!isset($this->ht[$vatrate])) {
                    $this->ht[$vatrate] = 0;
                }

                $this->tva[$vatrate] += $tva_line;
                $this->ht[$vatrate] += $line->total_ht;
                $i++;
            }
        }
        foreach ($this->acompteTva as $rate => $montant) {
            $this->tva[$rate] += $montant;
            $this->ht[$rate] += $this->acompteHt; //$montant * 100 / $rate;
        }
    }
}
