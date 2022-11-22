<?php

require_once DOL_DOCUMENT_ROOT . '/bimpfinancement/pdf/DevisFinancementPDF.php';

class DevisFinancementProleasePDF extends DevisFinancementPDF
{

    public $client_data = array();

    public function __construct($db, $demande, $client_data = array(), $options = array())
    {
        $this->client_data = $client_data;

        if (empty($this->client_data)) {
            $this->errors[] = 'Données du client absentes';
        }

        parent::__construct($db, $demande, $options);
    }

    public function initData()
    {
        BimpDocumentPDF::initData();
    }

    public function isTargetCompany()
    {
        if (isset($this->client_data['is_company'])) {
            return (int) $this->client_data['is_company'];
        }

        return 0;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= '<div>';

        if (isset($this->client_data['ref']) && $this->client_data['ref']) {
            $html .= '<span style="font-weight: bold;">Référence client : </span>' . $this->client_data['ref'] . '<br/>';
        }

        $html .= '</div>';

        $html .= BimpDocumentPDF::getDocInfosHtml();

        return $html;
    }

    public function getTargetInfosHtml()
    {
        $html = '';

        if (!empty($this->client_data)) {
            if ($this->client_data['is_company']) {
                $html .= $this->client_data['nom'] . '<br/>';
            }

            $html .= $this->client_data['full_adress'];
        }

        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }
}
