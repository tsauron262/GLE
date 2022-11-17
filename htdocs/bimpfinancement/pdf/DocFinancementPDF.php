<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpDocumentPDF.php';
require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/BF_Lib.php';

class DocFinancementPDF extends BimpDocumentPDF
{

    public static $doc_type = '';
    public $demande = null;
    public $sources = array();
    public $values = array();
    public $target_label = 'Destinataire';

    public function __construct($db, $demande)
    {
        parent::__construct($db);
        $this->demande = $demande;
        $this->bimpObject = $demande;

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
        $html = '';

        $html .= '<div>';

        // Réf. client: 
        $client = $this->demande->getChildObject('client');

        if (BimpObject::objectLoaded($client)) {
            $html .= '<span style="font-weight: bold;">Référence client : </span>' . $client->getRef() . '<br/>';
        }


        $html .= '</div>';

        $html .= parent::getDocInfosHtml();

        return $html;
    }

    public function renderLines()
    {
        $table = new BimpPDF_Table($this->pdf);
        $table->addCol('desc', 'Désignation', 0, '', '', '');
        $table->addCol('qte', 'Quantité', 25, 'text-align: center', '', 'text-align: center');

        $lines = $this->demande->getLines();

        foreach ($lines as $line) {
            $row = array();
            $desc = $line->displayDesc(false, true);

            if ((int) $line->getData('type') === BF_Line::TYPE_TEXT) {
                $row['desc'] = array(
                    'colspan' => 99,
                    'style'   => ' background-color: #F5F5F5;',
                    'content' => $desc
                );
            } else {
                $row['desc'] = $desc;
                $row['qte'] = $line->getData('qty');
            }

            $table->rows[] = $row;
        }

        if (count($table->rows)) {
            $this->writeContent('<div style="font-weight: bold; font-size: 9px;">Description des équipements et quantités :</div>');
            $this->pdf->addVMargin(1);
            $table->write();
        }

        unset($table);
    }
}
