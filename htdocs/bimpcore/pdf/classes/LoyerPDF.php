<?php

require_once __DIR__ . '/PropalPDF.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

ini_set('display_errors', 1);

class LoyerPDF extends PropalPDF
{

    public function renderLines()
    {
        $table = new BimpPDF_AmountsTable($this->pdf);
        $table->addColDef('loyer', 'Prix (mois)');
        $table->addColDef('mois', 'Engagement');

        $table->setCols(array('desc', 'loyer', 'mois', 'reduc'));

        $i = 0;

        // Traitement des lignes: 
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
                $loyer = 0;
                if ((int) $line->qty > 0) {
                    $loyer = (float) $line->total_ttc / $line->qty;
                }

                $row = array(
                    'desc'  => $desc,
                    'loyer' => BimpTools::displayMoneyValue($loyer, 'EUR'),
                    'mois'  => pdf_getlineqty($this->object, $i, $this->langs) . ' mois'
                );

                if (!$this->hideReduc && $line->remise_percent) {
                    $row['reduc'] = pdf_getlineremisepercent($this->object, $i, $this->langs);
                }
            }

            $table->rows[] = $row;
            $i++;
        }

        $this->writeContent('<div style="text-align: right; font-size: 6px;">Montants exprimés en Euros</div>');
        $this->pdf->addVMargin(1);
        $table->write();
        unset($table);
    }

    public function getTotauxRowsHtml()
    {
        return '';
    }
}
