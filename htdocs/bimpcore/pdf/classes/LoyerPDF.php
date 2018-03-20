<?php

require_once __DIR__ . '/PropalPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

ini_set('display_errors', 1);

class LoyerPDF extends PropalPDF
{

    public function renderDocumentContent()
    {
        global $conf, $mysoc;

        if (isset($this->object->array_options['options_libelle']) && $this->object->array_options['options_libelle']) {
            $this->writeContent('<p style="font-size: 10px">Objet : <strong>' . $this->object->array_options['options_libelle'] . '</strong></p>');
        }

        $table = new BimpPDF_AmountsTable($this->pdf);
        $table->addColDef('loyer', 'Prix (mois)');
        $table->addColDef('mois', 'Engagement');

        $table->setCols(array('desc', 'loyer', 'mois', 'reduc'));

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
                $loyer = 0;
                if ((int) $line->qty > 0) {
                    $loyer = (float) $line->total_ttc / $line->qty;
                }

                $row = array(
                    'desc'  => $desc,
                    'loyer' => BimpTools::displayMoneyValue($loyer, 'EUR'),
                    'mois'  => pdf_getlineqty($this->object, $i, $this->langs) . ' mois'
                );

                if ($line->remise_percent) {
                    $row['reduc'] = pdf_getlineremisepercent($this->object, $i, $this->langs);
                }

                if ($conf->multicurrency->enabled && $this->object->multicurrency_tx != 1)
                    $tva_line = $line->multicurrency_total_tva;
                else
                    $tva_line = $line->total_tva;

                $localtax1ligne = $line->total_localtax1;
                $localtax2ligne = $line->total_localtax2;
                $localtax1_rate = $line->localtax1_tx;
                $localtax2_rate = $line->localtax2_tx;
                $localtax1_type = $line->localtax1_type;
                $localtax2_type = $line->localtax2_type;

                if ($this->object->remise_percent)
                    $tvaligne-=($tvaligne * $this->object->remise_percent) / 100;
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

        $table = new BimpPDF_Table($this->pdf, false);
        $table->cellpadding = 0;
        $table->remove_empty_cols = false;
        $table->addCol('left', '', 95);
        $table->addCol('right', '', 95);

        $table->rows[] = array(
            'left'  => $info_html,
            'right' => ''
        );

        $this->writeContent('<br/><br/>');
        $table->write();
    }
}
