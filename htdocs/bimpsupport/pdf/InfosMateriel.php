<?php

require_once __DIR__ . '/BimpModelPDF.php';

class InfosMateriel extends BimpModelPDF
{

    public $title = '';
    public $data_left = array();
    public $data_right = array();

    public function __construct($title, $data_left, $data_right)
    {
        $this->title = $title;
        $this->data_left = $data_left;
        $this->data_right = $data_right;

        global $db;
        parent::__construct($db, 'P', 'A4');
    }

    protected function renderContent()
    {
        $html = '';

        $html .= '<style>';
        $html .= 'table.border {border-collapse: collapse;}';
        $html .= 'table.border th,table.border td {padding: 5px;text-align: left;min-width: 120px;}';
        $html .= 'table.border td {border: 1px solid #DDDDDD; font-size: 9px}';
        $html .= '</style>';

        $html .= '<p stle="text-align: center; font-size: 12px; font-weight: bold">';
        $html .= $this->title;
        $html .= '</p>';

        $html .= '<table style="width: 100%">';
        $html .= '<tr>';

        if (!empty($this->data_left)) {
            $html .= '<td style="width: 50%">';

            $html .= '<table style="width: 100%">';
            $html .= '<tr>';
            $html .= '<td colspan="2" style="background-color: #DCDCDC; font-weight: bold">Infos Produits</td>';

            foreach ($this->data_left as $label => $value) {
                $html .= '<td style="background-color: #F0F0F0; font-weight: bold">' . $label . '</td>';
                $html .= '<td>' . $value . '</td>';
            }

            $html .= '</tr>';
            $html .= '</table>';

            $html .= '</td>';
        }

        if (!empty($this->data_right)) {
            $html .= '<td style="width: 50%">';

            $html .= '<table style="width: 100%">';
            $html .= '<tr>';
            $html .= '<td colspan="2" style="background-color: #DCDCDC; font-weight: bold">Couverture</td>';

            foreach ($this->data_right as $label => $value) {
                $html .= '<td style="background-color: #F0F0F0; font-weight: bold">' . $label . '</td>';
                $html .= '<td>' . $value . '</td>';
            }

            $html .= '</tr>';
            $html .= '</table>';

            $html .= '</td>';
        }

        $html .= '</tr>';
        $html .= '</table>';
    }
}
