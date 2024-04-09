<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';

class DevisFinancementPDF extends BimpDocumentPDF
{

    public $signature_bloc = true;
    public $propal = null;
    public $target_label = 'Destinataire';
    public $demande_data = array();

    public function __construct($db, $propal, $demande_data)
    {
        parent::__construct($db);
        $this->propal = $propal;
        $this->bimpObject = $propal;
        $this->demande_data = $demande_data;

        if (!BimpObject::objectLoaded($this->propal)) {
            $this->errors[] = 'Proposition commerciale invalide';
        }

        $this->doc_name = 'Devis de location';
        $this->object_signature_params_field_name = 'signature_df_params';
    }

    public function initData()
    {
        if (!count($this->errors)) {
            $client = $this->propal->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                $this->thirdparty = $client->dol_object;
            } else {
                $this->errors[] = 'Aucun client';
            }
            $contact = $this->propal->getChildObject('contact');
            if (BimpObject::objectLoaded($contact)) {
                $this->contact = $contact->dol_object;
            }
        }

        parent::initData();
    }

    public function initHeader()
    {
        parent::initHeader();
        $doc_ref = '';

        if (BimpObject::objectLoaded($this->propal)) {
            $doc_ref = $this->propal->getRef() . '-FIN';
        }
        $this->header_vars['doc_ref'] = $doc_ref;
        $this->header_vars['doc_name'] = $this->doc_name;
    }

    public function getFromUsers()
    {
        $users = array();

        $comm1 = $comm2 = 0;
        $contacts = array();
        if (method_exists($this->propal->dol_object, 'getIdContact')) {
            $contacts = $this->propal->dol_object->getIdContact('internal', 'SALESREPFOLL');
            if (is_array($contacts) && count($contacts)) {
                $comm1 = $contacts[0];
            }

            $contacts = $this->propal->dol_object->getIdContact('internal', 'SALESREPSIGN');
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

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= '<div>';

        // Réf. client: 
        $client = $this->propal->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $html .= '<span style="font-weight: bold;">Référence client : </span>' . $client->getRef() . '<br/>';
        }


        $html .= '</div>';

        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function renderLines()
    {
        $table = new BimpPDF_Table($this->pdf, true, $this->primary);
        $table->addCol('desc', 'Désignation', 0, '', '', '');
        $table->addCol('qte', 'Quantité', 25, 'text-align: center', '', 'text-align: center');

        $lines = $this->propal->getLines();

        foreach ($lines as $line) {
            $product = $line->getProduct();
            $row = array();
            $desc = '';
            if (!is_null($product)) {
                $desc .= '<b>' . $product->getRef() . '</b>';
                $desc .= ($desc ? '<br/>' : '') . $product->getName();
            }

            if (!is_null($line->desc) && $line->desc) {
                $line_desc = $line->desc;
                if (!is_null($product)) {
                    if (preg_match('/^' . preg_quote($product->label, '/') . '(.*)$/', $line_desc, $matches)) {
                        $line_desc = $matches[0];
                    }
                    $line_desc = str_replace("  ", " ", $line_desc);
                    $product->label = str_replace("  ", " ", $product->label);
                    if (stripos($line_desc, $product->label) !== false) {
                        $line_desc = str_replace($product->label, "", $line_desc);
                    }
                }
                if ($line_desc) {
                    $desc .= ($desc ? (strlen($desc) > 20 ? '<br/>' : ' - ') : '') . $line_desc;
                }
            }

            $desc = preg_replace("/(\n)?[ \s]*<[ \/]*br[ \/]*>[ \s]*(\n)?/", '<br/>', $desc);
            $desc = str_replace("\n", '<br/>', $desc);

            if ((int) $line->getData('type') === ObjectLine::LINE_TEXT) {
                $row['desc'] = array(
                    'colspan' => 99,
                    'style'   => ' background-color: #F5F5F5;',
                    'content' => $desc
                );
            } else {
                $row['desc'] = $desc;
                $row['qte'] = $line->getFullQty();
            }

            $table->rows[] = $row;
        }

        if (count($table->rows)) {
            $this->writeContent('<div style="font-weight: bold; font-size: 9px;">Eléments financés :</div>');
            $this->pdf->addVMargin(1);
            $table->write();
        }

        unset($table);
    }

    public function getBottomRightHtml()
    {
        $rows = array(
            array(
                'label' => 'Périodicité',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'periodicity_label', ''),
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Nombre de loyers',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'nb_loyers', 0),
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Durée totale',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'duration', 0) . ' mois',
                'bk'    => 'FAFAFA'
            ),
            array(
                'label' => 'Montant loyer HT',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'montants/loyer_ht', 0),
                'bk'    => 'F2F2F2',
                'money' => 1
            ),
            array(
                'label' => 'Total HT',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'montants/total_loyers_ht', 0),
                'bk'    => 'EBEBEB',
                'money' => 1
            ),
            array(
                'label' => 'Total TVA',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'montants/total_loyers_tva', 0),
                'bk'    => 'F2F2F2',
                'money' => 1
            ),
            array(
                'label' => 'Total TTC',
                'value' => BimpTools::getArrayValueFromPath($this->demande_data, 'montants/total_loyers_ttc', 0),
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
