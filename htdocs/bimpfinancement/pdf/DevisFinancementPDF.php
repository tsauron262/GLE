<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DocFinancementPDF.php';

class DevisFinancementPDF extends DocFinancementPDF
{

    public static $doc_type = 'devis';

    public function getBottomRightHtml()
    {
        $rows = array(
            array(
                'label' => 'Périodicité',
                'value' => $this->demande->displayData('periodicity', 'default', false, true),
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Nombre de loyers',
                'value' => $this->demande->getNbLoyers(),
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Durée totale',
                'value' => $this->demande->displayDuration(),
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Montant loyer HT',
                'value' => $this->demande->getLoyerAmountHT(),
                'bk'    => 'F2F2F2',
                'money' => 1
            ),
            array(
                'label' => 'Total HT',
                'value' => $this->demande->getTotalLoyersHT(),
                'bk'    => 'EBEBEB',
                'money' => 1
            ),
            array(
                'label' => 'Total TVA',
                'value' => $this->demande->getTotalLoyersTVA(),
                'bk'    => 'F2F2F2',
                'money' => 1
            ),
            array(
                'label' => 'Total TTC',
                'value' => $this->demande->getTotalLoyersTTC(),
                'bk'    => 'E3E3E3',
                'bold'  => 1,
                'money' => 1
            )
        );

        $html .= '<table style="width: 100%" cellpadding="5">';

        foreach ($rows as $r) {
            $bold = (int) BimpTools::getArrayValueFromPath($r, 'bold', 0);

            $html .= '<tr>';
            $html .= '<td style="background-color: #' . $r['bk'] . ';' . ($bold ? ' font-weight: bold;' : '') . '">' . $r['label'] . '</td>';
            $html .= '<td style="text-align: right; background-color: #' . $r['bk'] . ';' . ($bold ? ' font-weight: bold;' : '') . '">';

            if (BimpTools::getArrayValueFromPath($r, 'money', 0)) {
                $html .= BimpTools::displayMoneyValue($r['value'], '', 0, 0, 1) . '';
            } else {
                $html .= $r['value'];
            }

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '<br/>';

        return $html;
    }
}
