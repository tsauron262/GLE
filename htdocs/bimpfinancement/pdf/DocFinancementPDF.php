<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';

class DocFinancementPDF extends BimpDocumentPDF
{

    public static $doc_type = '';
    public $demande = null;
    public $client_data = array();
    public $sources = array();
    public $values = array();
    public $extra_data = array();
    public $options = array();
    public $target_label = 'Destinataire';
    public $display_line_amounts = false;

    public function __construct($db, $demande, $extra_data = array(), $options = array())
    {
        parent::__construct($db);
        $this->demande = $demande;
        $this->bimpObject = $demande;
        $this->extra_data = $extra_data;
        $this->options = $options;

        if (!BimpObject::objectLoaded($this->demande)) {
            $this->errors[] = 'Demande invalide';
        } else {
            $this->values = $this->demande->getCalcValues(false, $this->errors);
        }

        $this->object_signature_params_field_name = 'signature_' . static::$doc_type . '_params';
    }

    public function initData()
    {
        if (!count($this->errors)) {
            if ((int) $this->demande->getData('id_main_source')) {
                $source = $this->demande->getSource();
                if (BimpObject::objectLoaded($source)) {
                    $this->client_data = $source->getClientPdfData();
                }
            } else {
                $client = $this->demande->getChildObject('client');

                if (BimpObject::objectLoaded($client)) {
                    $this->thirdparty = $client->dol_object;
                } else {
                    $this->errors[] = 'Aucun client';
                }
                $contact = $this->demande->getChildObject('contact_client');
                if (BimpObject::objectLoaded($contact)) {
                    $this->contact = $contact->dol_object;
                }
            }

            $this->sources = $this->demande->getChildrenObjects('sources');
        }

        parent::initData();
    }

    public function initHeader()
    {
        parent::initHeader();
        $doc_ref = '';

        if (BimpObject::objectLoaded($this->demande)) {
            $doc_ref = $this->demande->getRef();
        }
        $this->header_vars['doc_ref'] = $doc_ref;
        $this->header_vars['doc_name'] = $this->doc_name;
    }

    public function isTargetCompany()
    {
        if (isset($this->client_data['is_company'])) {
            return (int) $this->client_data['is_company'];
        }

        return parent::isTargetCompany();
    }

    public function getFromUsers()
    {
        $users = array();

        if (BimpObject::objectLoaded($this->demande)) {
            $id_user = (int) $this->demande->getData('id_user_resp');
            if ($id_user) {
                $users[$id_user] = 'Interlocuteur';
            }
        }

        return $users;
    }

    public function getDocInfosHtml()
    {
        $html = '<div>';

        // Réf. client: 
        if (isset($this->client_data['ref']) && $this->client_data['ref']) {
            $html .= '<span style="font-weight: bold;">Référence client : </span>' . $this->client_data['ref'] . '<br/>';
        } else {
            $client = $this->demande->getChildObject('client');

            if (BimpObject::objectLoaded($client)) {
                $html .= '<span style="font-weight: bold;">Référence client : </span>' . $client->getRef() . '<br/>';
            }
        }
        $html .= '</div>';

        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function getTargetInfosHtml()
    {
        if (empty($this->client_data)) {
            return parent::getTargetInfosHtml();
        }

        $html = '';

        if ($this->client_data['is_company']) {
            $html .= $this->client_data['nom'] . '<br/>';
        }

        $html .= $this->client_data['full_adress'];
        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }

    public function renderLines()
    {
        $table = new BimpPDF_Table($this->pdf);
        $table->addCol('desc', 'Désignation', 0, '', '', '');
        $table->addCol('qte', 'Quantité', 20, 'text-align: center', '', 'text-align: center');

        if ($this->display_line_amounts) {
            $table->addCol('pu_ht', 'PU HT', 20, 'text-align: center', '', 'text-align: center');
            $table->addCol('remise', 'Remise', 20, 'text-align: center', '', 'text-align: center');
            $table->addCol('tva_tx', 'TVA', 20, 'text-align: center', '', 'text-align: center');
            $table->addCol('total_ttc', 'Total TTC', 20, 'text-align: center', '', 'text-align: center');
        }

        $lines = $this->demande->getLines();

        $total_ttc = 0;

        foreach ($lines as $line) {
            $row = array();
            $desc = $line->displayDesc(false, true);
            $desc = $this->cleanHtml($desc);
            $desc = $this->replaceHtmlStyles($desc);

            if ((int) $line->getData('type') === BF_Line::TYPE_TEXT) {
                $row['desc'] = array(
                    'colspan' => 99,
                    'style'   => 'background-color: #F5F5F5;',
                    'content' => $desc
                );
            } else {
                $row['desc'] = $desc;
                $row['qte'] = $line->getData('qty');

                if ($this->display_line_amounts) {
                    $row['pu_ht'] = BimpTools::displayFloatValue($line->getData('pu_ht'));
                    $row['remise'] = BimpTools::displayFloatValue($line->getData('remise')) . ' %';
                    $row['tva_tx'] = BimpTools::displayFloatValue($line->getData('tva_tx')) . ' %';
                    $row['total_ttc'] = BimpTools::displayFloatValue($line->getData('total_ttc'));
                }

                $total_ttc += (float) $line->getData('total_ttc');
            }

            $table->rows[] = $row;
        }

        if ($this->display_line_amounts) {
            $table->rows[] = array(
                'desc'      => array(
                    'colspan' => 5,
                    'style'   => 'background-color: #F0EFEF; font-weight: bold; font-size: 8px; text-align: right',
                    'content' => 'TOTAL TTC'
                ),
                'total_ttc' => array(
                    'style'   => 'background-color: #F0EFEF; font-weight: bold; font-size: 8px',
                    'content' => BimpTools::displayFloatValue($total_ttc)
                )
            );
        }


        if (count($table->rows)) {
            $this->writeContent('<div style="font-weight: bold; font-size: 9px;">Description des équipements et quantités :</div>');
            $this->pdf->addVMargin(1);
            $table->write();
        }

        unset($table);
    }
}
