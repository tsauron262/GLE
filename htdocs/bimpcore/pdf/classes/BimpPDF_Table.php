<?php

class BimpPDF_Table
{

    public $pdf;
    protected $cols = array();
    public $rows = array();
    public $topMargin = 10; // mm
    public $botMargin = 0; // mm
    public $cellpadding = 5; //px
    public $cellspacing = 0; // px
    public $fontSize = 8; // px
    public $width = 190; //mm
    public $styles = '';

    public function __construct($pdf)
    {
        $this->pdf = $pdf;
        $this->styles = file_get_contents(BimpModelPDF::$tpl_dir . '/table.css');
    }

    public function setMargins($top = 0, $bot = 0)
    {
        $this->topMargin = $top;
        $this->botMargin = $bot;
    }

    public function addCol($key, $title, $width_mm = 0, $style = '', $class = '')
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
    }

    protected function writeHeader($cols)
    {
        $html = '';
        $html .= '<table class="header" style="font-size: ' . $this->fontSize . 'px"';
        $html .= ' cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '">';
        $html .= '<tr>';

        foreach ($cols as $key => $col) {
            $html .= '<td style="width: ' . $col['width_px'] . 'px">' . $col['title'] . '</td>';
        }

        $html .= '</tr>';
        $html .= '</table>';

        $this->pdf->writeHTML('<style>' . $this->styles . '</style>' . "\n" . $html, false, false, true, false, '');
    }

    protected function writeRow($pdf, $cols, $row, $class = 'row')
    {
        $html = '';
        $html .= '<table class="' . $class . '"';
        $html .= 'style="font-size: ' . $this->fontSize . 'px" ';
        $html .= 'cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '">';
        $html .= '<tr>';

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
                            $style = $row[$key]['style'];
                        }
                        if (isset($row[$key]['class'])) {
                            $class = $row[$key]['class'];
                        }
                        if (isset($row[$key]['colspan'])) {
                            if ((int) $row[$key]['colspan'] > 1) {
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

        $pdf->writeHTML('<style>' . $this->styles . '</style>' . "\n" . $html, false, false, true, false, '');
    }

    public function write()
    {
        // Vérification de l'affichage des colonnes: 
        $cols = array();
        foreach ($this->cols as $key => $col) {
            foreach ($this->rows as $row) {
                if (isset($row[$key])) {
                    $cols[$key] = $col;
                    break;
                }
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
        if ($nRemainingCols > 0) {
            if ($this->cellspacing > 0) {
                $dispoWidth_mm -= ($nCols + 1) * ($this->cellspacing * BimpPDF::$mmPerPx);
            }
            $colWidth_mm = (float) ($dispoWidth_mm / $nRemainingCols);
            $colWidth_px = (int) floor($colWidth_mm * BimpPDF::$pxPerMm);
        }

        foreach ($cols as &$col) {
            if ((int) $col['width_mm']) {
                $col['width_px'] = (int) floor($col['width_mm'] * BimpPDF::$pxPerMm);
            } else {
                $col['width_px'] = $colWidth_px;
            }
        }

        $current_page = $this->pdf->getPage();

        $this->writeHeader($cols);

        $clone = clone $this->pdf;

        foreach ($this->rows as $row) {
            $this->writeRow($clone, $cols, $row);

            $page = $clone->getPage();

            if ($page > $current_page) {
                $this->pdf->newPage();
                $this->writeHeader($cols);
                $this->writeRow($this->pdf, $cols, $row);

                unset($clone);
                $clone = clone $this->pdf;

                $current_page = $page;
            } else {
                $this->writeRow($this->pdf, $cols, $row);
            }
        }
    }
}
