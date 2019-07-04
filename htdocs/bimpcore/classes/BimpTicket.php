<?php

require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

class BimpTicket
{

    public $db;
    public $width;
    public $facture = null;
    public $entrepot = null;
    public $errors = array();
    public $vente_number = '';

    public function __construct($db, $width, $facture, $avoir, $id_entrepot = null, $vente_number = '')
    {
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        }
        BimpTools::loadDolClass('product/stock', 'entrepot');

        $this->db = $db;
        $this->width = $width;
        $this->vente_number = $vente_number;
        $this->avoir = $avoir;

        if (is_null($facture) || !isset($facture->id) || !$facture->id) {
            $this->errors[] = 'Facture invalide';
        } else {
            $this->facture = $facture;
            if (is_null($id_entrepot) || !$id_entrepot) {
                if (isset($facture->array_options['options_entrepot'])) {
                    $id_entrepot = (int) $facture->array_options['options_entrepot'];
                }
            }

            if (!isset($facture->lines) || !count($facture->lines)) {
                $this->errors[] = 'Aucun article';
            }
        }

        if (!$id_entrepot) {
            $this->errors[] = 'Aucun entrepot';
        } else {
            $this->entrepot = new Entrepot($db);
            if ($this->entrepot->fetch($id_entrepot) <= 0) {
                $this->errors[] = 'Entrepot invalide';
                BimpTools::getErrorsFromDolObject($this->entrepot, $this->errors);
            }
        }
    }

    public function renderHtml()
    {
        global $conf;
        $conf->global->MAIN_MAX_DECIMALS_SHOWN = str_replace('...', '', $conf->global->MAIN_MAX_DECIMALS_SHOWN);
        $html = '';

        global $langs, $conf, $mysoc;

        $bdb = new BimpDb($this->db);

        $langs->load("errors");
        $langs->load("main");
        $langs->load("companies");
        $langs->load("bills");
        $langs->load("products");

        if (!count($this->errors)) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';

//            $html .= '<style type="text/css">';
//            $html .= file_get_contents(DOL_DOCUMENT_ROOT . '/bimpcore/views/css/ticket.css');
//            $html .= '</style>';

            $html .= '<div id="ticket" style="width: ' . $this->width . 'px;">';

            $html .= $this->renderHeader();

            if ($this->town) {
                $html .= '<span class="bold" style="font-size: 17px">' . BimpTools::ucfirst($this->town) . ', le ' . date('d / m / Y') . '</span>';
            }

            $html .= '<table id="ticket_lines">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th width="34%" style="max-width: 30%">Article</th>';
            $html .= '<th width="10%" style="max-width: 10%">Qt</th>';
            $html .= '<th width="23%" style="max-width: 25%">PU</th>';
            $html .= '<th width="10%" style="max-width: 10%">TVA</th>';
            $html .= '<th width="23%" style="max-width: 25%">Montant</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            $html .= '<tbody>';
            BimpTools::loadDolClass('product');
            $tva = array();

            $i = 0;
            foreach ($this->facture->lines as $line) {
                $html .= '<tr class="article_desc">';
                if (is_null($line->desc) || !$line->desc) {
                    if (!is_null($line->fk_product) && $line->fk_product) {
                        $product = new Product($this->db);
                        if ($product->fetch((int) $line->fk_product) > 0) {
                            $desc = $product->label;
                        }
                    }
                } else {
                    $desc = $line->desc;
                }

//                if (strlen($desc) > 40) {
//                    $desc = substr($desc, 0, 38)."...";
//                }

                $html .= '<td colspan="5">' . $desc . '</td>';
                $html .= '</tr>';

                $html .= '<tr class="article_amounts">';
                $html .= '<td>';
                if ((float) $line->remise_percent) {
                    $html .= '<span style="font-style: italic">Remise: ' . str_replace('.', ',', (string) round($line->remise_percent, 4, PHP_ROUND_HALF_DOWN)) . ' %</span>';
                }
                $html .= '</td>';
                $html .= '<td>' . pdf_getlineqty($this->facture, $i, $langs) . '</td>';
                $html .= '<td>' . pdf_getlineupexcltax($this->facture, $i, $langs) . '</td>';
                $html .= '<td>' . pdf_getlinevatrate($this->facture, $i, $langs) . '</td>';
                $html .= '<td>' . pdf_getlinetotalwithtax($this->facture, $i, $langs) . '</td>';
                $html .= '</tr>';

                if ($conf->multicurrency->enabled && $this->facture->multicurrency_tx != 1) {
                    $tva_line = $line->multicurrency_total_tva;
                } else {
                    $tva_line = $line->total_tva;
                }

                if ($this->facture->remise_percent) {
                    $tva_line -= ($tva_line * $this->facture->remise_percent) / 100;
                }

                $vatrate = (string) $line->tva_tx;

//                if (!isset($this->tva[$vatrate])) {
//                    $tva_line = 0;
//                }

                $tva[$vatrate] += $tva_line;
                $i++;
            }
            
            $i = 0;
            if($this->avoir){
                foreach ($this->avoir->lines as $line) {
                    $html .= '<tr class="article_desc">';
                    if (is_null($line->desc) || !$line->desc) {
                        if (!is_null($line->fk_product) && $line->fk_product) {
                            $product = new Product($this->db);
                            if ($product->fetch((int) $line->fk_product) > 0) {
                                $desc = $product->label;
                            }
                        }
                    } else {
                        $desc = $line->desc;
                    }

    //                if (strlen($desc) > 40) {
    //                    $desc = substr($desc, 0, 38)."...";
    //                }

                    $html .= '<td colspan="5">' . $desc . '</td>';
                    $html .= '</tr>';

                    $html .= '<tr class="article_amounts">';
                    $html .= '<td>';
                    if ((float) $line->remise_percent) {
                        $html .= '<span style="font-style: italic">Remise: ' . str_replace('.', ',', (string) round($line->remise_percent, 4, PHP_ROUND_HALF_DOWN)) . ' %</span>';
                    }
                    $html .= '</td>';
                    $html .= '<td>' . pdf_getlineqty($this->avoir, $i, $langs) . '</td>';
                    $html .= '<td>' . pdf_getlineupexcltax($this->avoir, $i, $langs) . '</td>';
                    $html .= '<td>' . pdf_getlinevatrate($this->avoir, $i, $langs) . '</td>';
                    $html .= '<td>' . pdf_getlinetotalwithtax($this->avoir, $i, $langs) . '</td>';
                    $html .= '</tr>';

                    if ($conf->multicurrency->enabled && $this->avoir->multicurrency_tx != 1) {
                        $tva_line = $line->multicurrency_total_tva;
                    } else {
                        $tva_line = $line->total_tva;
                    }

                    if ($this->avoir->remise_percent) {
                        $tva_line -= ($tva_line * $this->avoir->remise_percent) / 100;
                    }

                    $vatrate = (string) $line->tva_tx;

    //                if (!isset($this->tva[$vatrate])) {
    //                    $tva_line = 0;
    //                }

                    $tva[$vatrate] += $tva_line;
                    $i++;
                }
            }
            
            
            
            
            
            $html .= '</tbody>';
            $html .= '</table>';

            $total_ht = ($conf->multicurrency->enabled && $this->facture->mylticurrency_tx != 1 ? $this->facture->multicurrency_total_ht : $this->facture->total_ht);
            $total_ttc = ($conf->multicurrency->enabled && $this->facture->multicurrency_tx != 1) ? $this->facture->multicurrency_total_ttc : $this->facture->total_ttc;

            if($this->avoir){
                $total_ht += ($conf->multicurrency->enabled && $this->avoir->mylticurrency_tx != 1 ? $this->avoir->multicurrency_total_ht : $this->avoir->total_ht);
                $total_ttc += ($conf->multicurrency->enabled && $this->avoir->multicurrency_tx != 1) ? $this->avoir->multicurrency_total_ttc : $this->avoir->total_ttc;
            }

            $html .= '<table id="ticket_totaux">';
            $html .= '<tbody>';
            $html .= '<tr class="bold">';
            $html .= '<td>Total HT :</td>';
//            $html .= '<td>' . price($total_ht + (!empty($this->facture->remise) ? $this->facture->remise : 0), 0, $langs) . '</td>';
            $html .= '<td>' . price($total_ht, 0, $langs) . '</td>';
            $html .= '</tr>';

            foreach ($tva as $tvakey => $tvaval) {
                if ($tvakey != 0) {
                    $html .= '<tr>';
                    $html .= '<td>' . $langs->transcountrynoentities("TotalVAT", $mysoc->country_code) . ' ' . vatrate($tvakey, 1) . '</td>';
                    $html .= '<td>' . price($tvaval, 0, $langs) . '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '<tr class="bold">';
            $html .= '<td>Total TTC :</td>';
            $html .= '<td>' . price($total_ttc, 0, $langs) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            $sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
            $sql .= " cp.code";
            $sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "paiement as p";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON p.fk_paiement = cp.id";
            $sql .= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = " . $this->facture->id;
            $sql .= " ORDER BY p.datep";

            $rows = $bdb->executeS($sql);
            if (!is_null($rows) && count($rows)) {
                $returned = 0;

                $payment_rows_html = '';

                foreach ($rows as $row) {
                    $amount = (float) (($conf->multicurrency->enabled && $this->facture->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount);
                    if ($amount <= 0) {
                        $returned_rows_html .= '<tr>';
                        $returned_rows_html .= '<td>' . $langs->transnoentitiesnoconv("PaymentTypeShort" . $row->code) . ' :</td>';
                        $returned_rows_html .= '<td>' . price(abs($amount), 0, $langs) . '</td>';
                        $returned_rows_html .= '</tr>';
                    } else {
                        $payment_rows_html .= '<tr>';
                        $payment_rows_html .= '<td>' . $langs->transnoentitiesnoconv("PaymentTypeShort" . $row->code) . ' :</td>';
                        $payment_rows_html .= '<td>' . price($amount, 0, $langs) . '</td>';
                        $payment_rows_html .= '</tr>';
                    }
                }

                if ($payment_rows_html) {
                    $html .= '<div class="bold">Réglements : </div>';
                    $html .= '<table id="ticket_payments">';
                    $html .= '<tbody>';
                    $html .= $payment_rows_html;
                    $html .= '</tbody>';
                    $html .= '</table>';
                }
                
                if ($returned_rows_html) {
                    $html .= '<div class="bold">Rendus : </div>';
                    $html .= '<table id="ticket_rendus">';
                    $html .= '<tbody>';
                    $html .= $returned_rows_html;
                    $html .= '</tbody>';
                    $html .= '</table>';
                }

//                if ($returned > 0) {
//                    $html .= '<div class="bold" style="margin-bottom: 15px">Rendu : ' . price($returned, 0, $langs) . '</div>';
//                }
            }

            $html .= '<span style="font-size: 13px;">Ticket imprimé le : ' . date('d / m / Y H:i:s') . '</span>';
            $html .= '<span style="font-size: 13px;"><br/>Devise exprimée en Euro</span>';
            $html .= $this->renderFooter();
            $html .= '</div>';
        }


        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
        }

        return $html;
    }

    protected function renderHeader()
    {
        global $mysoc, $conf;

        $html = '';

        $address = (string) $this->entrepot->address;
        $zip = (string) $this->entrepot->zip;
        $this->town = (string) $this->entrepot->town;
        $phone = (string) $this->entrepot->array_options['options_phone'];

        if ($address == "")
            $address = $mysoc->address;
        if ($zip == "")
            $zip = $mysoc->zip;
        if ($this->town == "")
            $this->town = $mysoc->town;
        if ($phone == "") {
            $phone = $mysoc->phone;
        }

        if (!$address) {
            $this->errors[] = 'Addresse absente';
        }

        if (!$zip) {
            $this->errors[] = 'Code postal absent';
        }

        if (!$this->town) {
            $this->errors[] = 'Ville absente';
        }

        if (count($this->errors)) {
            return '';
        }

        $logo = $mysoc->logo;
        $logo2 = str_replace(".jpg", "_black.jpg", $logo);
        if (is_file($conf->mycompany->dir_output . '/logos/' . $logo2))
            $logo = $logo2;
        else {
            $logo2 = str_replace(".png", "_black.png", $logo);
            if (is_file($conf->mycompany->dir_output . '/logos/' . $logo2))
                $logo = $logo2;
        }


        $url = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&file=' . $logo;

        $html .= '<div class="ticket_header">';
        $html .= '<div class="header_block">';
        $html .= '<img src="' . $url . '"/>';
        $html .= '</div>';

        $html .= '<div class="header_block">';
        $html .= $address . '<br/>';
        $html .= $zip . ' ' . strtoupper($this->town);
        if ($phone) {
            $html .= '<br/><br/><span style="font-size: 16px; font-weight: bold">Tél : ' . $phone . '</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function renderFooter()
    {
        $html = '';

        $url = DOL_URL_ROOT . '/bimpequipment/img/apple_reseller.png';
        $html .= '<div class="ticket_footer">';

        $html .= '<div class="footer_block">';
        $html .= '<img src="' . $url . '"/>';
        $html .= '</div>';

        $html .= '<div class="footer_block">';
        $html .= '<p style="font-weight: bold;">' . $this->facture->ref;
        if ($this->vente_Number) {
            $html .= ' - ' . $this->vente_number;
        }
        $html .= '</p>';
        $html .= '<p style="font-size: 20px; line-height: 22px; font-weight: bold; font-style: italic">Merci de votre fidélité</p>';

        $txt = '';

        global $mysoc, $langs;

        $langs->load("companies");

        if ($mysoc->forme_juridique_code) {
            $txt .= $langs->convToOutputCharset(getFormeJuridiqueLabel($mysoc->forme_juridique_code));
        }

        if ($mysoc->capital) {
            $captital = price2num($mysoc->capital);
            if (is_numeric($captital) && $captital > 0) {
                $txt .= ($txt ? " <br/> " : "") . $langs->transnoentities("CapitalOf", price($captital, 0, $langs, 0, 0, 0, $conf->currency)) . " €";
            } else {
                $txt .= ($txt ? " <br/> " : "") . $langs->transnoentities("CapitalOf", $captital, $langs) . " €";
            }
        }

        if ($mysoc->idprof1 && ($mysoc->country_code != 'FR' || !$mysoc->idprof2)) {
            $field = $langs->transcountrynoentities("ProfId1", $mysoc->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof1);
        }

        if ($mysoc->idprof2) {
            $field = $langs->transcountrynoentities("ProfId2", $mysoc->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof2);
        }

        if ($mysoc->idprof3) {
//            $field = $langs->transcountrynoentities("ProfId3", $mysoc->country_code);
            $field = 'APE';
//            if (preg_match('/\((.*)\)/i', $field, $reg)) {
//                $field = $reg[1];
//                
//            }
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof3);
        }

        if ($mysoc->idprof4) {
            $field = $langs->transcountrynoentities("ProfId4", $mysoc->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof4);
        }

        if ($mysoc->idprof5) {
            $field = $langs->transcountrynoentities("ProfId5", $mysoc->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof5);
        }

        if ($mysoc->idprof6) {
            $field = $langs->transcountrynoentities("ProfId6", $mysoc->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $txt .= ($txt ? " - " : "") . $field . ": " . $langs->convToOutputCharset($mysoc->idprof6);
        }
        // IntraCommunautary VAT
        if ($mysoc->tva_intra != '') {
            $txt .= ($txt ? "<br/>" : "") . 'TVA/CEE' . ": " . $langs->convToOutputCharset($mysoc->tva_intra);
        }

//        SA OLYS au capital de 954.352 &euro; - 320 387 483 R.C.S Lyon - APE 4741Z - TVA/CEE FR 34 320387 483

        $html .= '<p style="font-size: 11px; line-height: 12px; font-weight: bold;">' . $txt . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin: 10px; text-align: center; font-size: 12px">Tout article déballé ne peut être ni repris ni échangé</div>';

        return $html;
    }
}
