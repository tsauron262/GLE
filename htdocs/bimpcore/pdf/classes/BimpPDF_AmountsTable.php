<?php

require_once __DIR__ . '/BimpPDF_Table.php';

class BimpPDF_AmountsTable extends BimpPDF_Table
{

    public $colsToSet = array();
    public $cols_def = array(
        'desc'      => array('label' => 'Désignation', 'width_mm' => 80, 'active' => 0),
        'pu_ht'     => array('label' => 'P.U. HT', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'pu_remise' => array('label' => 'P.U remisé', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'qte'       => array('label' => 'Qté', 'width_mm' => 15, 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'progress'  => array('label' => 'Progression', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'reduc'     => array('label' => 'Remise', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'total_ht'  => array('label' => 'Total HT', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'tva'       => array('label' => 'TVA', 'width_mm' => 12, 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'total_ttc' => array('label' => 'Total TTC', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;'),
        'pu_remise' => array('label' => 'P.U remisé', 'active' => 0, 'style' => 'text-align: right;', 'head_style' => 'text-align: center;')
    );

    public function __construct($pdf)
    {
        $this->setCols(array('desc', 'pu_ht', 'reduc', 'pu_remise', 'qte', 'progress', 'total_ht', 'tva', 'total_ttc'));

        parent::__construct($pdf);
    }

    public function setCols($cols)
    {
        $this->colsToSet = array();

        foreach ($this->cols_def as $name => $def) {
            $this->cols_def[$name]['active'] = 0;
        }
        foreach ($cols as $col) {
            if (array_key_exists($col, $this->cols_def)) {
                $this->cols_def[$col]['active'] = 1;
                $this->colsToSet[] = $col;
            }
        }
    }

    public function addColDef($name, $label, $width = 0, $style = '', $class = '', $head_style = '')
    {
        $this->cols_def[$name] = array(
            'label'      => $label,
            'active'     => 1,
            'width_mm'   => $width,
            'style'      => $style,
            'class'      => $class,
            'head_style' => $head_style
        );
    }

    public function write()
    {
        foreach ($this->colsToSet as $col_name) {
            if (isset($this->cols_def[$col_name])) {
                $col = $this->cols_def[$col_name];
                if ($col['active']) {
                    $width = isset($col['width_mm']) ? $col['width_mm'] : '';
                    $style = isset($col['style']) ? $col['style'] : '';
                    $head_style = isset($col['head_style']) ? $col['head_style'] : '';
                    $class = isset($col['class']) ? $col['class'] : '';
                    $this->addCol($col_name, $col['label'], $width, $style, $class, $head_style);
                }
            }
        }

        parent::write();
    }
}
