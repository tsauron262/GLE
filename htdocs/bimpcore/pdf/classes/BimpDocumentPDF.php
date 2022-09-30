<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class BimpDocumentPDF extends BimpModelPDF
{
    # Constantes: 

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';
    public static $use_cgv = true;

    # Objets liés: 
    public $bimpObject = null;
    public $thirdparty = null;
    public $contact = null;
    public $contactFinal = null;

    # Données: 
    public $target_label = '';
    public $next_annexe_idx = 1;
    public $annexe_listings = array();
    public $file_logo = '';
    public $signature_params = array();
    public $signature_bloc = true;
    public $signature_bloc_label = '';

    public function __construct($db)
    {
        parent::__construct($db, 'P', 'A4');
        $this->target_label = $this->langs->transnoentities('BillTo');
    }

    // Initialisation:

    protected function initHeader()
    {
        global $conf;

        $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->fromCompany->logo;
        if ($this->file_logo != '' && is_file($conf->mycompany->dir_output . '/logos/' . $this->file_logo)) {
            $logo_file = $conf->mycompany->dir_output . '/logos/' . $this->file_logo;
        } else {
            if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('R', 'C', 'ME', 'CO', 'CTC', 'CTE'))) {
                $testFile = str_replace(array(".jpg", ".png"), "_PRO.png", $logo_file);
                if (is_file($testFile))
                    $logo_file = $testFile;
            }
            if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('BP'))) {
                $testFile = str_replace(array(".jpg", ".png"), "_BP.png", $logo_file);
                if (is_file($testFile))
                    $logo_file = $testFile;
            }
            if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('S'))) {
                $testFile = str_replace(array(".jpg", "_RESEAUNANCE.png"), "_SAV.png", $logo_file);
                if (is_file($testFile))
                    $logo_file = $testFile;
            }
            if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('E'))) {
                $testFile = str_replace(array(".jpg", ".png"), "_EDUC.png", $logo_file);
                if (is_file($testFile))
                    $logo_file = $testFile;
            }
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

        $this->pdf->topMargin = 44;

        $this->header_vars = array(
            'primary_color' => $this->primary,
            'logo_img'      => $logo_file,
            'logo_width'    => $logo_width,
            'logo_height'   => $logo_height,
            'doc_ref'       => '',
            'ref_extra'     => '',
            'header_infos'  => $this->getSenderInfosHtml(),
            'header_right'  => '',
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

    // Getters: 

    public function getFromUsers()
    {
        // Renvoyer sous la forme id_user => label (label = désignation utilisateur, ex: commercial, interlocuteur, emetteur, etc.) 
        return array();
    }

    public function getTargetIdSoc()
    {
        if (isset($this->thirdparty->id)) {
            return $this->thirdparty->id;
        }

        return 0;
    }

    // Rendus:

    protected function renderContent()
    {
        if (is_object($this->thirdparty) || is_object($this->contact)) {
            $this->renderDocInfos($this->thirdparty, $this->contact);
        }

        $this->renderTop();
        $this->renderBeforeLines();
        $this->renderLines();
        $this->renderFullBlock('renderAfterLines');
        $this->renderFullBlock('renderBottom');
        $this->renderFullBlock('renderAfterBottom');
        $this->renderSignatureBloc();
        $this->renderFullBlock('renderAnnexes');
        $this->renderAnnexeListings();

        $cur_page = (int) $this->pdf->getPage();
        $num_pages = (int) $this->pdf->getNumPages();
        if (($num_pages - $cur_page) === 1) {
            $this->pdf->deletePage($num_pages);
        }
    }

    public function getFromUsersInfosHtml()
    {
        $html = '';

        $users = $this->getFromUsers();

        $usertmp = null;

        if (is_array($users) && !empty($users)) {
            foreach ($users as $id_user => $label) {
                $usertmp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $id_user);
                if (BimpObject::objectLoaded($usertmp)) {
                    $html .= '<div class="row" style="border-top: solid 1px #' . $this->primary . '">';
                    $html .= '<span style="font-weight: bold; color: #' . $this->primary . ';">';
                    $html .= $label . ' :</span>';
                    $html .= '<br/>' . $usertmp->dol_object->getFullName($this->langs, 0, -1, 20);
                    if ($usertmp->dol_object->email) {
                        $html .= '<br/><span style="font-size: 7px;">' . $usertmp->dol_object->email . '</span>';
                    }
                    if ($usertmp->dol_object->office_phone) {
                        $html .= '<span style="font-size: 7px;">' . ($usertmp->dol_object->email ? ' - ' : '<br/>') . $usertmp->dol_object->office_phone . '</span>';
                    }
                    $html .= '</div>';
                }
            }
        }


        if (isset($usertmp)) {
            // Je suis pas sûr que ça marche ça, à ce stade le fromcompany est déjà printé:
            if ($usertmp->dol_object->office_phone != "")
                $this->fromCompany->phone = $usertmp->dol_object->office_phone;
            if ($usertmp->dol_object->email != "")
                $this->fromCompany->email = $usertmp->dol_object->email;
        }

        return $html;
    }

    public function getDocInfosHtml()
    {
        $html = '';

        $html .= $this->getFromUsersInfosHtml();

        return $html;
    }

    public function getTargetInfosHtml()
    {
        global $langs;

        $html = "";
        $nomsoc = pdfBuildThirdpartyName($this->thirdparty, $this->langs);
        if (is_null($this->contact) || $this->contact->getFullName($langs) != $nomsoc) {
            $html .= $nomsoc . "<br/>";
            if (!is_null($this->contact) && is_object($this->object) && is_object($this->object->thirdparty) && $this->object->thirdparty->name_alias != "") {
                $html .= $this->object->thirdparty->name_alias . "<br/>";
            }
        }

        $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contact, !is_null($this->contact) ? 1 : 0, 'target');

        if (isset($this->contactFinal) && is_object($this->contactFinal)) {
            $html .= '<br/><div class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; ">';
            $html .= '<span style="color: #' . $this->primary . '">' . ('Client Final :') . '</span></div>';
            $html .= '';
            $html .= pdf_build_address($this->langs, $this->fromCompany, $this->thirdparty, $this->contactFinal, !is_null($this->contactFinal) ? 1 : 0, 'target');
        }

        $html = str_replace("\n", '<br/>', $html);

        return $html;
    }

    public function renderDocInfos()
    {
        $html = '';

        $html .= '<table class="section addresses_section" style="width: 100%" cellspacing="0" cellpadding="3px">';
        $html .= '<tr>';
        $html .= '<td style="width: 55%"></td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td class="section_title" style="width: 40%; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
        $html .= '<span style="color: #' . $this->primary . '">' . strtoupper($this->target_label) . '</span></td>';
        $html .= '</tr>';
        $html .= '</table>';

        $html .= '<table class="section addresses_section" style="width: 100%" cellspacing="0" cellpadding="10px">';
        $html .= '<tr>';
        $html .= '<td class="sender_address" style="width: 55%">';
        $html .= $this->getDocInfosHtml();
        $html .= '</td>';
        $html .= '<td style="width: 5%"></td>';
        $html .= '<td style="width: 40%">';

        $html .= $this->getTargetInfosHtml();

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $this->writeContent($html);
    }

    public function renderTop()
    {
        
    }

    public function renderBeforeLines()
    {
        
    }

    public function renderLines()
    {
        
    }

    public function renderAfterLines()
    {
        
    }

    public function renderBottom()
    {
        $table = new BimpPDF_Table($this->pdf, false);
        $table->cellpadding = 0;
        $table->remove_empty_cols = false;
        $table->addCol('left', '', 95);
        $table->addCol('vide', '', 10);
        $table->addCol('right', '', 80);

        $table->rows[] = array(
            'left'  => $this->getBottomLeftHtml(),
            'right' => $this->getBottomRightHtml()
        );

        $this->writeContent('<br/><br/>');
        $table->write();
    }

    public function getBottomLeftHtml()
    {
        return '';
    }

    public function getPaymentInfosHtml()
    {
        return '';
    }

    public function getBottomRightHtml()
    {

        return '';
    }

    public function getTotauxRowsHtml()
    {
        return '';
    }

    public function getPaymentsHtml()
    {
        return '';
    }

    public function getAfterTotauxHtml()
    {
        return '';
    }

    public function renderAfterBottom()
    {
        
    }

    public function renderSignatureBloc()
    {
        // /!\ !!!!! Ne pas modifier ce bloc : réglé précisément pour incrustation signature électronique. 

        if ($this->signature_bloc) {
            $yPosOffset = 0;

            $id_soc = $this->getTargetIdSoc();
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_soc);
            }

            $html = '<table style="width: 95%;font-size: 7px;" cellpadding="3">';
            $html .= '<tr>';
            $html .= '<td style="width: 50%"></td>';
            $html .= '<td style="width: 50%">';

            $html .= '<table cellpadding="3">';
            $html .= '<tr>';
            if (BimpObject::objectLoaded($soc) && $soc->isCompany()) {
                $html .= '<td style="text-align:center;"><i><b>' . $this->signature_bloc_label . '</b></i></td>';

                $html .= '<td style="font-size: 6px">Signature + Cachet avec SIRET :</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td>Nom :</td>';

                $html .= '<td rowspan="4" style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/></td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td>Prénom :</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td>Fonction :</td>';
                $html .= '</tr>';

                $html .= '<tr>';
                $html .= '<td>Date :</td>';

                $yPosOffset = 7;
                $this->signature_params = array(
                    'x_pos'             => 146,
                    'width'             => 43,
                    'nom_x_offset'      => -32,
                    'nom_y_offset'      => 0,
                    'nom_width'         => 30,
                    'fonction_x_offset' => -32,
                    'fonction_y_offset' => 11,
                    'fonction_width'    => 30,
                    'date_x_offset'     => -32,
                    'date_y_offset'     => 16,
                );
            } else {
                $html .= '<td style="text-align: right">Signature :<br/>Date :</td>';
                $html .= '<td style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/><br/></td>';

                $yPosOffset = 2;
                $this->signature_params = array(
                    'x_pos'         => 146,
                    'width'         => 43,
                    'date_x_offset' => -16,
                    'date_y_offset' => 7,
                );
            }

            $html .= '</tr>';
            $html .= '</table>';

            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            $page = 0;
            $yPos = 0;

            $this->writeFullBlock($html, $page, $yPos);

            $this->signature_params['y_pos'] = $yPos + $yPosOffset;
            $this->signature_params['page'] = $page;

            if (is_a($this->bimpObject, 'BimpObject')) {
                if ($this->bimpObject->field_exists('signature_params')) {
                    $this->bimpObject->updateField('signature_params', $this->signature_params);
                }
            }
        }
    }

    public function renderAnnexes()
    {
        
    }

    public function renderAnnexeListings()
    {
        if (!empty($this->annexe_listings)) {
            foreach ($this->annexe_listings as $annexe_type => $annexe) {

                if (!empty($annexe['lists'])) {
                    $annexe_title = 'Annexe ' . $this->next_annexe_idx . ' - ' . $annexe['title'];
                    $html = '';
                    $html .= '<table style="width: 100%" cellspacing="0" cellpadding="3px">';
                    $html .= '<tr>';
                    $html .= '<td class="section_title" style="font-weight: bold; font-size: 8px; border-top: solid 1px #' . $this->primary . '; border-bottom: solid 1px #' . $this->primary . '">';
                    $html .= '<span style="color: #' . $this->primary . '">' . $annexe_title . '</span></td>';
                    $html .= '</tr>';
                    $html .= '</table>';

                    $this->writeContent($html);

                    foreach ($annexe['lists'] as $list) {
                        $table = new BimpPDF_Table($this->pdf, false);
                        $table->new_page_title = '<div style="font-weight: bold;font-size: 9px;">' . $annexe_title . ' - ' . $list['title'] . ' (suite)</div>';
                        $table->cellpadding = 1;

                        for ($i = 0; $i < $list['cols']; $i++) {
                            $table->addCol($i, '', 0, 'font-size: 6px');
                        }

                        $rows = array();
                        $row = array();
                        $i = 0;
                        foreach ($list['items'] as $item) {
                            $row[$i] = $item;

                            $i++;
                            if ($i >= $list['cols']) {
                                $i = 0;
                                $rows[] = $row;
                                $row = array();
                            }
                        }

                        if (!empty($row)) {
                            $rows[] = $row;
                        }

                        if (!empty($rows)) {
                            $html = '';
                            $html .= '<table style="width: 100%" cellspacing="0" cellpadding="6px">';
                            $html .= '<tr>';
                            $html .= '<td style="font-weight: bold;font-size: 8px;">' . $list['title'] . '</td>';
                            $html .= '</tr>';
                            $html .= '</table>';
                            $this->writeContent($html);

                            $table->rows = $rows;
                            $table->write();
                        }

                        unset($table);
                    }
                }

                $this->next_annexe_idx++;
            }
        }
    }
}
