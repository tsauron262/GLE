<?php

require_once __DIR__ . '/BimpModelPDF.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class BimpDocumentPDF extends BimpModelPDF
{
    # Constantes: 

    public static $tpl_dir = DOL_DOCUMENT_ROOT . '/bimpcore/pdf/templates/document/';
    public static $use_cgv = false;

    # Params: 
    public static $full_blocs = array(
        'renderAfterLines'  => 1,
        'renderBottom'      => 1,
        'renderAfterBottom' => 1,
        'renderAnnexes'     => 1
    );

    # Objets liés: 
    public $bimpObject = null;
    public $thirdparty = null;
    public $contact = null;
    public $contactFinal = null;
    public $targetBimpSoc = null;

    # Données: 
    public $target_label = 'Destinataire';
    public $next_annexe_idx = 1;
    public $annexe_listings = array();
    public $file_logo = '';

    # Paramètres signature: 
    public $signature_bloc = false;
    public $use_docsign = false;
    public $object_signature_params_field_name = 'signature_params';
    public $signature_params = array();
    public $signature_bloc_label = '';
    public $signature_title = 'Signature';
    public $signature_pro_title = 'Signature + Cachet avec SIRET';
    public $signature_mentions = '';
    public $signature_file_idx = 0;

    public function __construct($db)
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        parent::__construct($db, 'P', 'A4');
    }

    // Initialisation:

    protected function initHeader()
    {
        global $conf;

        // Todo : réporganiser tout ça... 
//        if (BimpCore::isEntity('actimac')) {
//            // Temporaire...
//            $logo_file = DOL_DOCUMENT_ROOT . '/bimpcore/extends/entities/actimac/logo_actimag_sav.png';
//        } else {
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
                    $testFile = str_replace(array(".jpg", "_RESEAUNANCE.png", ".png"), "_SAV.png", $logo_file);
                    if (is_file($testFile))
                        $logo_file = $testFile;
                }
                if (isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('E'))) {
                    $testFile = str_replace(array(".jpg", ".png"), "_EDUC.png", $logo_file);
                    if (is_file($testFile))
                        $logo_file = $testFile;
                }
            }
//        }


        $logo_width = 0;
        if (!file_exists($logo_file)) {
            $logo_file = '';
        } else {
            $sizes = dol_getImageSize($logo_file, false);
            $tabTaille = $this->calculeWidthHeightLogo($sizes['width'], $sizes['height'], $this->maxLogoWidth, $this->maxLogoHeight);
            $logo_width = $tabTaille[0];
            $logo_height = $tabTaille[1];
        }

        $header_right = '';
        $soc = $this->getTargetBimpSociete();
        if (BimpObject::objectLoaded($soc)) {
            if (isset($soc->dol_object->logo) && (string) $soc->dol_object->logo) {
                $soc_logo_file = DOL_DATA_ROOT . '/societe/' . $soc->dol_object->id . '/logos/' . $soc->dol_object->logo;
                if (file_exists($soc_logo_file)) {
                    $sizes = dol_getImageSize($soc_logo_file, false);
                    if (isset($sizes['width']) && (int) $sizes['width'] && isset($sizes['height']) && $sizes['height']) {
                        $tabTaille = $this->calculeWidthHeightLogo($sizes['width'], $sizes['height'], 80, 80);
                        $header_right = '<img src="' . $soc_logo_file . '" width="' . $tabTaille[0] . 'px" height="' . $tabTaille[1] . 'px"/>';
                    }
                }
            }
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
            'header_right'  => $header_right,
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
            if (BimpCore::isEntity('prolease')) {
                $line2 .= $this->footerCompany->address . " - " . $this->footerCompany->zip . " " . $this->footerCompany->town;
            } else {
                $line1 .= " - " . $this->footerCompany->address . " - " . $this->footerCompany->zip . " " . $this->footerCompany->town . " - Tél " . $this->footerCompany->phone;
            }
        }

        if (BimpCore::isEntity('prolease')) {
            $line2 .= ' - RCS : ' . $this->langs->convToOutputCharset($this->footerCompany->idprof4);
        } else {
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

    public function getTargetBimpSociete()
    {
        if (is_null($this->targetBimpSoc)) {
            $id_soc = (int) $this->getTargetIdSoc();
            if ($id_soc) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', $id_soc);
                if (BimpObject::objectLoaded($soc)) {
                    $this->targetBimpSoc = $soc;
                }
            }
        }

        return $this->targetBimpSoc;
    }

    public function isTargetCompany()
    {
        $id_soc = $this->getTargetIdSoc();
        if ($id_soc) {
            $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $id_soc);

            if (BimpObject::objectLoaded($soc)) {
                return (int) $soc->isCompany();
            }
        }

        return 0;
    }

    // Rendus:

    protected function renderContent()
    {
//        if (is_object($this->thirdparty) || is_object($this->contact)) {
//            $this->renderDocInfos($this->thirdparty, $this->contact);
//        }

        $this->renderDocInfos();
        $this->renderTop();
        $this->renderBeforeLines();
        $this->renderLines();

        if ((int) BimpTools::getArrayValueFromPath(static::$full_blocs, 'renderAfterLines', 0)) {
            $this->renderFullBlock('renderAfterLines');
        } else {
            $this->renderAfterLines();
        }

        if ((int) BimpTools::getArrayValueFromPath(static::$full_blocs, 'renderBottom', 0)) {
            $this->renderFullBlock('renderBottom');
        } else {
            $this->renderBottom();
        }


        if ((int) BimpTools::getArrayValueFromPath(static::$full_blocs, 'renderAfterBottom', 0)) {
            $this->renderFullBlock('renderAfterBottom');
        } else {
            $this->renderAfterBottom();
        }

        $this->renderSignatureBloc();

        if ((int) BimpTools::getArrayValueFromPath(static::$full_blocs, 'renderAnnexes', 0)) {
            $this->renderFullBlock('renderAnnexes');
        } else {
            $this->renderAnnexes();
        }

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
                    $html .= '<br/>' . $usertmp->dol_object->getFullName($this->langs, 0, -1, 40);
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
            $html .= pdfBuildThirdpartyName($this->contactFinal, $this->langs).'<br/>';
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

    public function getBottomRightHtml()
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
            $this->signature_params['default'] = array();
            $yPosOffset = 0;

            $html = '<table style="width: 95%;font-size: 7px;" cellpadding="3">';
            $html .= '<tr>';
            $html .= '<td style="width: 50%"></td>';
            $html .= '<td style="width: 50%">';

            $html .= '<table cellpadding="3">';
            if ($this->signature_mentions) {
                $html .= '<tr>';
                $html .= '<td colspan="2" style="text-align: center">' . $this->signature_mentions . '</td>';
                $html .= '</tr>';
            }
            $html .= '<tr>';
            if ($this->isTargetCompany()) {
                $html .= '<td style="text-align:center;"><i><b>' . $this->signature_bloc_label . '</b></i></td>';

                $html .= '<td style="font-size: 6px">' . ($this->signature_pro_title ? $this->signature_pro_title . ' :' : '') . '</td>';
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
                $this->signature_params['default']['elec'] = array(
                    'x_pos'             => 146,
                    'width'             => 43,
                    'nom_x_offset'      => -32,
                    'nom_y_offset'      => 0,
                    'nom_width'         => 30,
                    'fonction_x_offset' => -32,
                    'fonction_y_offset' => 11,
                    'fonction_width'    => 30,
                    'date_x_offset'     => -32,
                    'date_y_offset'     => 16
                );

                if ($this->use_docsign) {
                    $base_x = -110; // x du champ le plus à gauche (Nom)
                    $base_y = 15; // y du champ le plus haut (Nom)

                    $this->signature_params['default']['docusign'] = array(
                        'anch'  => $this->signature_pro_title . ' :',
                        'fs'    => 'Size8',
                        'x'     => $base_x + 120,
                        'y'     => $base_y + 30,
                        'texts' => array(
                            'nom'      => array(
                                'label' => 'Nom',
                                'x'     => $base_x,
                                'y'     => $base_y,
                            ),
                            'fonction' => array(
                                'label' => 'Fonction',
                                'x'     => $base_x + 12,
                                'y'     => $base_y + 30
                            )
                        ),
                        'date'  => array(
                            'x' => $base_x + 1,
                            'y' => $base_y + 45
                        )
//                            'prenom_x_offset'   => $base_x + 10,
//                            'prenom_y_offset'   => $base_y + 14,
                    );
                }
            } else {
                $html .= '<td style="text-align: right">' . ($this->signature_title ? $this->signature_title . ' :' : '') . '<br/>Date :</td>';
                $html .= '<td style="border-top-color: #505050; border-left-color: #505050; border-right-color: #505050; border-bottom-color: #505050;"><br/><br/><br/><br/><br/><br/></td>';

                $yPosOffset = 2;
                $this->signature_params['default']['elec'] = array(
                    'x_pos'         => 146,
                    'width'         => 43,
                    'date_x_offset' => -16,
                    'date_y_offset' => 7,
                );

                if ($this->use_docsign) {
                    $base_x = -110; // x du champ le plus à gauche (Nom)
                    $base_y = 14; // y du champ le plus haut (Nom)

                    $this->signature_params['default']['docusign'] = array(
                        'anch'  => $this->signature_title . ' :',
                        'x'     => $base_x + 130,
                        'y'     => $base_y + 5,
                        'texts' => array(
                            'nom' => array(
                                'label' => 'Nom',
                                'x'     => $base_x,
                                'y'     => $base_y,
                            )
                        ),
                        'date'  => array(
                            'x' => $base_x + 1,
                            'y' => $base_y + 47
                        )
//                            'prenom_x_offset'   => $base_x + 10,
//                            'prenom_y_offset'   => $base_y + 14,
                    );
                }
            }

            $html .= '</tr>';
            $html .= '</table>';

            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            $page = 0;
            $yPos = 0;

            $this->writeFullBlock($html, $page, $yPos);

            $this->signature_params['default']['elec']['y_pos'] = $yPos + $yPosOffset;
            $this->signature_params['default']['elec']['page'] = $page;

            if (is_a($this->bimpObject, 'BimpObject')) {
                if ($this->bimpObject->field_exists($this->object_signature_params_field_name)) {
                    if ((int) $this->signature_file_idx) {
                        $signature_params = $this->bimpObject->getData($this->object_signature_params_field_name);

                        if (!is_array($signature_params)) {
                            $signature_params = array();
                        }
                        $signature_params[$this->signature_file_idx] = $this->signature_params;
                    } else {
                        $signature_params = $this->signature_params;
                    }
                    $this->bimpObject->updateField($this->object_signature_params_field_name, $signature_params);
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
