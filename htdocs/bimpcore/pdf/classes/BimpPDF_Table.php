<?php

class BimpPDF_Table
{

    public $pdf;
    protected $cols = array();
    public $rows = array();
    public $topMargin = 10; // mm
    public $botMargin = 0; // mm
    public $cellpadding = 2; //px
    public $cellspacing = 0; // px
    public $fontSize = 8; // px
    public $width = 190; //mm
    public $styles = '';
    public $table_classes = array();
    public $table_styles = array();
    public $remove_empty_cols = true;

    public function __construct($pdf, $borders = true)
    {
        $this->pdf = $pdf;
        if ($borders) {
            $this->styles = file_get_contents(BimpModelPDF::$tpl_dir . '/table/table_borders.css');
        } else {
            $this->styles = file_get_contents(BimpModelPDF::$tpl_dir . '/table/table_no_borders.css');
        }

        $primary = BimpCore::getParam('pdf/primary', '000000');
        $this->styles = str_replace('{primary}', $primary, $this->styles);
    }

    public function setMargins($top = 0, $bot = 0)
    {
        $this->topMargin = $top;
        $this->botMargin = $bot;
    }

    public function addTableClass($class)
    {
        $this->table_classes[] = $class;
    }

    public function addTableStyle($prop, $value)
    {
        $this->table_styles[$prop] = $value;
    }

    public function addCol($key, $title, $width_mm = 0, $style = '', $class = '', $head_style = '')
    {
        $this->cols[$key] = array(
            'title'    => $title,
            'width_mm' => $width_mm
        );
        if ($style) {
            $this->cols[$key]['style'] = $style;
        }
        if ($class) {
            $this->cols[$key]['class'] = $class;
        }

        if ($head_style) {
            $this->cols[$key]['head_style'] = $head_style;
        }
    }

    protected function writeHeader($cols)
    {
        $html = '';
        $html .= '<table class="header';
        foreach ($this->table_classes as $class) {
            $html .= ' ' . $class;
        }
        $html .= '" style="font-size: ' . $this->fontSize . 'px;';
        foreach ($this->table_styles as $prop => $value) {
            $html .= ' ' . $prop . ': ' . $value . ';';
        }
        $html .= '"';
        $html .= ' cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '">';
        $html .= '<tr>';

        $has_titles = false;
        foreach ($cols as $key => $col) {
            $html .= '<td style="width: ' . $col['width_px'] . 'px;';
            if (isset($col['head_style'])) {
                $html .= ' ' . $col['head_style'];
            }
            $html .= '">';
            if (isset($col['title']) && $col['title']) {
                $html .= $col['title'];
                $has_titles = true;
            }
            $html .= '</td>';
        }

        $html .= '</tr>';
        $html .= '</table>';

        if ($has_titles) {
            $this->pdf->writeHTML('<style>' . $this->styles . '</style>' . "\n" . $html, false, false, true, false, '');
        }
    }

    protected function writeRow($pdf, $cols, $row, $class = 'row')
    {
        $nbRow = count($this->rows);
        $nbRow = 1;
        $coef = (200 - ($nbRow * 10)) / 100; //A 10 lignes on est en taille normal a 20 on est a 0
        $cellpadding = $coef * $this->cellpadding;

        if ($cellpadding < 0.5)
            $cellpadding = 0.5;


        $html = '';
        $html .= '<table class="' . $class . '';
        foreach ($this->table_classes as $tableClass) {
            $html .= ' ' . $tableClass;
        }
        $html .= '" ';
        $html .= 'style="font-size: ' . $this->fontSize . 'px;';
        foreach ($this->table_styles as $prop => $value) {
            $html .= ' ' . $prop . ': ' . $value . ';';
        }
        $html .= '" ';
        $html .= 'cellspacing="' . $this->cellspacing . '" cellpadding="' . $cellpadding . '">';
        $html .= '<tr';
        if (isset($row['row_style'])) {
            $html .= ' style="' . $row['row_style'] . '"';
        }
        $html .= '>';

        $multicell = null;
        $multicell_width = 0;
        $n_continue = 0;
        $n_cols = count($cols);
        $i = 1;

        foreach ($cols as $key => $col) {
            $content = '';
            if (!is_null($multicell)) {
                $multicell_width += (int) $col['width_px'];
                if ($n_continue <= 0 || $i >= $n_cols) {
                    $col_width = $multicell_width;
                    $content = isset($multicell['content']) ? $multicell['content'] : '';
                    if (isset($multicell['style'])) {
                        $style = $multicell['style'];
                    }
                    if (isset($multicell['class'])) {
                        $class = $multicell['class'];
                    }
                    $multicell_width = 0;
                    $multicell = null;
                    $n_continue = 0;
                } else {
                    $n_continue--;
                    $i++;
                    continue;
                }
            } else {
                $style = (isset($col['style']) ? $col['style'] : '');
                $class = (isset($col['class']) ? $col['class'] : '');
                $col_width = $col['width_px'];

                if (isset($row[$key])) {
                    if (is_array($row[$key])) {
                        if (isset($row[$key]['content'])) {
                            $content = $row[$key]['content'];
                        }
                        if (isset($row[$key]['style'])) {
                            $style .= ($style ? ' ' : '') . $row[$key]['style'];
                        }
                        if (isset($row[$key]['class'])) {
                            $class .= ($class ? ' ' : '') . $row[$key]['class'];
                        }
                        if (isset($row[$key]['colspan'])) {
                            if ((int) $row[$key]['colspan'] > 1 && ($i < $n_cols)) {
                                $n_continue = (int) $row[$key]['colspan'] - 2;
                                $multicell_width = $col_width;
                                $multicell = $row[$key];
                                $i++;
                                continue;
                            }
                        }
                    } else {
                        $content = $row[$key];
                    }
                }
            }



            if (is_object($row['object'])) {
                $content .= $this->addDEEEandRPCP($key, $row['object'], $row['qte']);
            }


            $html .= '<td style="width: ' . $col_width . 'px';
            if ($style) {
                $html .= '; ' . $style;
            }
            if ($class) {
                $html .= '" class="' . $class;
            }
            $html .= '">' . $content . '</td>';
            $i++;
        }

        $html .= '</tr>';
        $html .= '</table>';

        $pdf->writeHTML('<style>' . $this->styles . '</style>' . "\n" . $html . "", false, false, true, false, '');
    }

    public function addDEEEandRPCP($key, $object, $qty)
    {
        $content = "";
        $htmlAv = '<br/><span style="text-align:right;font-style: italic; font-size: 5px; font-weight: bold;">';
        $htmlAp = '</span>';
        $eco = 0;
        if (isset($object->array_options['options_deee']) && $object->array_options['options_deee'] > 0)
            $eco = $object->array_options['options_deee'];

        if ($key == "desc" && $eco > 0)
            $content .= $htmlAv . 'Dont écotaxe' . $htmlAp;
        if ($key == "pu_ht" && $eco > 0)
            $content .= $htmlAv . price($eco) . $htmlAp;
        if ($key == "total_ht" && $eco > 0)
            $content .= $htmlAv . price($eco * $qty) . $htmlAp;
        if ($key == "total_ttc" && $eco > 0)
            $content .= $htmlAv . price($eco * $qty * 1.2) . $htmlAp;



        $rpcp = 0;
        if (isset($object->array_options['options_rpcp']) && $object->array_options['options_rpcp'] > 0)
            $rpcp = $object->array_options['options_rpcp'];
        if ($key == "desc" && $rpcp > 0)
            $content .= $htmlAv . 'Dont droit copie privé' . $htmlAp;
        if ($key == "pu_ht" && $rpcp > 0)
            $content .= $htmlAv . price($rpcp) . $htmlAp;
        if ($key == "total_ht" && $rpcp > 0)
            $content .= $htmlAv . price($rpcp * $qty) . $htmlAp;
        if ($key == "total_ttc" && $rpcp > 0)
            $content .= $htmlAv . price($rpcp * $qty * 1.2) . $htmlAp;
        return $content;
    }

    public function write()
    {
        // Vérification de l'affichage des colonnes: 
        $cols = array();

        foreach ($this->cols as $key => $col) {
            if ($this->remove_empty_cols) {
                foreach ($this->rows as $row) {
                    if (isset($row[$key])) {
                        $cols[$key] = $col;
                        break;
                    }
                }
            } else {
                $cols[$key] = $col;
            }
        }

        if (!count($cols)) {
            return;
        }

        // Calcul de la largeur des colonnes:
        $nCols = count($cols);
        $dispoWidth_mm = $this->width;
        $nRemainingCols = count($cols);

        foreach ($cols as $col) {
            if ((int) $col['width_mm']) {
                $dispoWidth_mm -= (float) $col['width_mm'];
                $nRemainingCols--;
            }
        }

        $colWidth_mm = 0;
        $colWidth_px = 0;
        $extraWidth_px = 0;

        if ($nRemainingCols > 0) {
            if ($this->cellspacing > 0) {
                $dispoWidth_mm -= ($nCols + 1) * ($this->cellspacing * BimpPDF::$mmPerPx);
            }
            $colWidth_mm = (float) ($dispoWidth_mm / $nRemainingCols);
            $colWidth_px = (int) floor($colWidth_mm * BimpPDF::$pxPerMm);
        } elseif ($nCols > 0) {
            $extraWidth_px = ((int) floor($dispoWidth_mm / $nCols) * BimpPDF::$pxPerMm);
        }

        foreach ($cols as &$col) {
            if ((int) $col['width_mm']) {
                $col['width_px'] = (int) floor($col['width_mm'] * BimpPDF::$pxPerMm) + $extraWidth_px;
            } else {
                $col['width_px'] = $colWidth_px + $extraWidth_px;
            }
        }

        $current_page = $this->pdf->getPage();

        $this->writeHeader($cols);

        $clone = clone $this->pdf;

        $i = 0;
        $nRows = count($this->rows);

        foreach ($this->rows as $row) {
            $class = 'row';
            $i++;

            if ($i >= $nRows) {
                $class = 'last';
            }
            $this->writeRow($clone, $cols, $row, $class);

            $page = $clone->getPage();

            if ($page > $current_page) {
                $this->pdf->newPage();
                $this->writeHeader($cols);
                $this->writeRow($this->pdf, $cols, $row, $class);

                unset($clone);
                $clone = clone $this->pdf;

                $current_page = $page;
            } else {
                $this->writeRow($this->pdf, $cols, $row, $class);
            }
        }
    }
}
