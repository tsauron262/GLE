<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpModelPDF.php';

class ShipmentHeaderPDF extends BimpModelPDF
{

    public $shipment = null;
    public $files_list_labels = array();

    // Format en px : 252 x 102

    public function __construct($db, $shipment)
    {
        $this->shipment = $shipment;
        self::$type = "apple_shipment_header";
        parent::__construct($db);

        $this->pdf->addCgvPages = false;
        $this->pdf->topMargin = 10;
    }

    protected function renderContent()
    {
        $html = '';

        if (!BimpObject::objectLoaded($this->shipment)) {
            $html .= 'ERREUR: ID RETOUR GROUPE ABSENT';
        } elseif (!count($this->files_list_labels)) {
            $html .= 'AUCUN DOCUMENT A INCLURE';
        } else {
            $html .= '<table style="width: 100%" cellpadding="5" cellspacing="10">';
            $html .= '<tr>';
            $html .= '<td style="border: solid 1px #333333;font-size: 14px; font-weight: bold; text-align: center">';
            $html .= 'Retour Groupé Apple #' . $this->shipment->id;
            $html .= '</td>';
            $html .= '</tr>';

            if ($this->shipment->getData('tracking_number')) {
                $html .= '<tr>';
                $html .= '<td style="font-size: 12px; text-align: center">';
                $html .= 'N° de suivi: <span style="font-weight: bold">' . $this->shipment->getData('tracking_number') . '</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
            $html .= '<br/><br/><br/>';

            $html .= '<div style="font-size: 11px">';
            $html .= 'Ce document contient: <br/>';

            foreach ($this->files_list_labels as $label) {
                $html .= ' - ' . $label . '<br/>';
            }

            $html .= '<br/>';
            $html .= 'Créé le ' . date('d / m / Y');
            $html .= '</div>';
        }

        $this->writeContent($html);
    }
}
