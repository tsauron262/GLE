<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

class TransferPDF extends BimpModelPDF
{

    public static $type = 'transfer';
    public $EntrepotSrc = null;
    public $entrepotDest = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->pdf->addCgvPages = false;
    }

    public function getFilePath()
    {
        if (!file_exists(DOL_DATA_ROOT . '/bimptransfer') || !is_dir(DOL_DATA_ROOT . '/bimptransfer')) {
            BimpTools::makeDirectories('bimptransfer', DOL_DATA_ROOT);
        }

        return DOL_DATA_ROOT . '/bimptransfer/';
    }

    public function getFileName()
    {
        if (BimpObject::objectLoaded($this->object)) {
            return 'transfer_' . $this->object->id . '.pdf';
        }

        return '';
    }

    protected function initData()
    {
        if (BimpObject::objectLoaded($this->object)) {
            BimpTools::loadDolClass('product/stock', 'entrepot');
            if ((int) $this->object->getData('id_warehouse_source')) {
                $this->EntrepotSrc = new Entrepot($this->db);
                $this->EntrepotSrc->fetch((int) $this->object->getData('id_warehouse_source'));
            }

            if ((int) $this->object->getData('id_warehouse_dest')) {
                $this->entrepotDest = new Entrepot($this->db);
                $this->entrepotDest->fetch((int) $this->object->getData('id_warehouse_dest'));
            }
        }

        parent::initData();
    }

    protected function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;


        if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('R', 'C', 'ME', 'CO'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_PRO.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }
        if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('S'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_SAV.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }
        if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('E'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_EDUC.png", $logo_file);
            if (is_file($testFile))
                $logo_file = $testFile;
        }

        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $sizes = dol_getImageSize($logo_file, false);
            $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);

            $logo_width = $tabTaille[0];
            $logo_height = $tabTaille[1];
        }

        $header_right = '';

        if (isset($this->object->socid) && (int) $this->object->socid) {
            if (isset($this->object->thirdparty->logo) && (string) $this->object->thirdparty->logo) {
                $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $this->object->thirdparty->id . '/logos/' . $this->object->thirdparty->logo;
                if (file_exists($soc_logo_file)) {
                    $sizes = dol_getImageSize($soc_logo_file, false);
                    if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {

                        $tabTaille = $this->calculeWidthHieghtLogo($sizes['width'] / 3, $sizes['height'] / 3, 200, 100);

                        $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                    }
                }
            }
        }

        $this->pdf->topMargin = 50;

        $doc_ref = '';
        if (BimpObject::objectLoaded($this->object)) {
            $doc_ref = 'Transfert #' . $this->object->id;
        }

        $this->header_vars = array(
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => $header_right,
            'primary_color' => $this->primary,
            'doc_name'      => 'Bon de transfert',
            'doc_ref'       => $doc_ref,
            'ref_extra'     => ''
        );
    }

    protected function initfooter()
    {
        $line1 = '';
        $line2 = '';

        global $conf;

        if ($this->footerCompany->name) {
            $line1 .= $this->langs->convToOutputCharset($this->footerCompany->name);
        }

        if ($this->footerCompany->forme_juridique_code) {
            $line1 .= " - " . $this->langs->convToOutputCharset(getFormeJuridiqueLabel($this->footerCompany->forme_juridique_code));
        }

        if ($this->footerCompany->capital) {
            $captital = price2num($this->footerCompany->capital);
            if (is_numeric($captital) && $captital > 0) {
                $line1 .= ($line1 ? " au " : "") . $this->langs->transnoentities("CapitalOf", price($captital, 0, $this->langs, 0, 0, 0, $conf->currency));
            } else {
                $line1 .= ($line1 ? " au " : "") . $this->langs->transnoentities("CapitalOf", $this->footerCompany->capital, $this->langs);
            }
        }

        if ($this->footerCompany->address) {
            $line1 .= " - " . $this->footerCompany->address . " - " . $this->footerCompany->zip . " " . $this->footerCompany->town . " - Tél " . $this->footerCompany->phone;
        }

        if ($this->footerCompany->idprof1 && ($this->footerCompany->country_code != 'FR' || !$this->footerCompany->idprof2)) {
            $field = $this->langs->transcountrynoentities("ProfId1", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof1);
        }

        if ($this->footerCompany->idprof2) {
            $field = $this->langs->transcountrynoentities("ProfId2", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line1 .= ($line1 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof2);
        }

        if ($this->footerCompany->idprof3) {
//            $field = $this->langs->transcountrynoentities("ProfId3", $this->footerCompany->country_code);
            $field = 'APE';
//            if (preg_match('/\((.*)\)/i', $field, $reg)) {
//                $field = $reg[1];
//                
//            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof3);
        }

        if ($this->footerCompany->idprof4) {
            $field = $this->langs->transcountrynoentities("ProfId4", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof4);
        }

        if ($this->footerCompany->idprof5) {
            $field = $this->langs->transcountrynoentities("ProfId5", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg)) {
                $field = $reg[1];
            }
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof5);
        }

        if ($this->footerCompany->idprof6) {
            $field = $this->langs->transcountrynoentities("ProfId6", $this->footerCompany->country_code);
            if (preg_match('/\((.*)\)/i', $field, $reg))
                $field = $reg[1];
            $line2 .= ($line2 ? " - " : "") . $field . " : " . $this->langs->convToOutputCharset($this->footerCompany->idprof6);
        }
        // IntraCommunautary VAT
        if ($this->footerCompany->tva_intra != '') {
            $line2 .= ($line2 ? " - " : "") . $this->langs->transnoentities("VATIntraShort") . " : " . $this->langs->convToOutputCharset($this->footerCompany->tva_intra);
        }

        $this->footer_vars = array(
            'footer_line_1' => $line1,
            'footer_line_2' => $line2,
        );
    }

    public function renderContent()
    {
        $this->renderAddresses();
        $this->renderLines();
    }

    public function renderAddresses()
    {
        $html = '';

//        if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
//            $thirdparty = $object->contact;
//        } else {
//            $thirdparty = $object->thirdparty;
//        }

        $html .= '<br/>';
        $html .= '<div class="section addresses_section">';
        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td class="section_title" style="width: 45%; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
        $html .= '<span style="color: #' . $this->primary . '">DEPART</span>';
        $html .= '</td>';

        $html .= '<td style="width: 10%"></td>';

        $html .= '<td class="section_title" style="width: 45%; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
        $html .= '<span style="color: #' . $this->primary . '">DESTINATION</span>';
        $html .= '</td>';

        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';

        $html .= '<td style="width: 45%">';
        $html .= $this->getEntrepotAddressHtml($this->EntrepotSrc);
        $html .= '</td>';

        $html .= '<td style="width: 10%"></td>';

        $html .= '<td style="width: 45%">';
        $html .= $this->getEntrepotAddressHtml($this->entrepotDest);
        $html .= '</td>';

        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        $this->writeContent($html);
    }

    public function renderLines()
    {
        if (BimpObject::objectLoaded($this->object) && is_a($this->object, 'Transfer')) {
            $lines = $this->object->getLines();

            if (count($lines)) {
                $table = new BimpPDF_Table($this->pdf);
                $table->addCol('desc', 'Désignation');
                $table->addCol('qty', 'Qté', 30);

                $i = 0;
                foreach ($lines as $line) {
                    $i++;

                    $desc = '';

                    if ((int) $line->getData('id_equipment')) {
                        $equipment = $line->getChildObject('equipment');
                        if (BimpObject::objectLoaded($equipment)) {
                            $desc = $equipment->getProductLabel(true) . '<br/>N° de série: <span style="font-weight: bold">' . $equipment->getRef() . '</span>';
                        } else {
                            $desc = '<span style="color: #A00000; font-weight: bold">L\'équipement d\'ID ' . $line->getData('id_equipment') . ' n\'existe pas</span>';
                        }
                    } elseif ((int) $line->getData('id_product')) {
                        $product = $line->getChildObject('product');
                        if (BimpObject::objectLoaded($product)) {
                            $desc = $product->getRef() . ' - ' . $product->getData('label');
                        } else {
                            $desc = '<span style="color: #A00000; font-weight: bold">Le produit d\'ID ' . $line->getData('id_product') . ' n\'existe pas</span>';
                        }
                    } else {
                        $desc = '<span style="color: #A00000; font-weight: bold">Produit absent</span>';
                    }

                    $row = array(
                        'desc' => $desc,
                        'qty'  => (int) $line->getData('quantity_sent')
                    );

                    $table->rows[] = $row;
                }

                $this->pdf->addVMargin(1);
                $table->write();
                unset($table);
            } else {
                $this->writeContent('<span style="color: #A00000; font-weight: bold">Aucun produit ajouté à ce transfert</span>');
            }
        } else {
            $this->writeContent('<span style="color: #A00000; font-weight: bold">Transfert absent ou invalide</span>');
        }
    }
}
