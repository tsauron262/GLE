<?php

require_once __DIR__ . '/BimpPDF_Table.php';

class BimpPDF_AmountsTable extends BimpPDF_Table
{

    public $cols_def = array(
        'desc'      => array('label' => 'Désignation', 'width' => 80, 'active' => 1),
        'tva'       => array('label' => 'TVA', 'active' => 1),
        'pu_ht'     => array('label' => 'P.U. HT', 'active' => 1),
        'pu_ttc'    => array('label' => 'P.U. HT', 'active' => 0),
        'qte'       => array('label' => 'Qté', 'active' => 1),
        'reduc'     => array('label' => 'Réduc.', 'active' => 0),
        'total_ht'  => array('label' => 'Total HT', 'active' => 1),
        'total_ttc' => array('label' => 'Total TTC', 'active' => 0)
    );
    public $cols = array();

    public function __construct($pdf)
    {
        $this->setCols(array('desc', 'tva', 'pu_ht', 'qte', 'reduc', 'total_ht'));

        parent::__construct($pdf);
    }

    public function setCols($cols)
    {
        foreach ($this->cols_def as $name => $def) {
            $this->cols_def[$name]['active'] = 0;
        }
        foreach ($cols as $col) {
            if (array_key_exists($col, $this->cols_def)) {
                $this->cols_def[$col]['active'] = 1;
            }
        }
        
        $this->cols = $cols;
    }

    public function addCol($name, $label, $width = 0)
    {
        $this->cols_def[$name] = array(
            'label'  => $label,
            'active' => 1,
            'width'  => $width
        );
    }
}
