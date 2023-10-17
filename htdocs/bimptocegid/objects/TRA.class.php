<?php

require_once DOL_DOCUMENT_ROOT . '/bimptocegid/class/controle.class.php';

class TRA extends BimpObject {
    
    public static $searchIn = Array('tiers' => "Tiers");
    public static $searchBy = Array(0 => "Tiers", 1 => "Facture");
    public static $searchAux;
    public static $versionTra;
    public static $fileEntiy;
    
    public function displayTraFile($file, $lineInBold = 0, $displayFileName = false) {
        
        $html  = '';
        
        $lines = $this->getLinesOfFile($file);
        
        $htmlPanel = '<pre id=\'fileEdit\' fichier=\''.$file.'\' style=\'display: none; padding-top:20px; padding-bottom: 30px\'>';
        
        $htmlPanel .=file_get_contents($file);
        
        $htmlPanel .= '</pre>';
        
        $htmlPanel .= '<pre original-tra=\''. file_get_contents($file).'\' id=\'file\'>'; 
        
        if($displayFileName)
            $htmlPanel .= '<u>' . basename ($file) . '</u><br />';
        else
            $htmlPanel .= '<br />';

        foreach($lines as $index => $line) {
            $htmlPanel .= '<span style=\'margin-left: 10px\'>';
            
            if(is_array($lineInBold)) {
                if(count($lineInBold) > 0) {
                    if(in_array(($index+1), $lineInBold)) {
                        $htmlPanel .= $index+1 . '.' . "\t" . '<b style=\'cursor: pointer\' class=\'btn-warning\' >' . $line . '</b>';
                    } else {
                        $htmlPanel .= $index+1 . '.' . "\t" . $line;
                    }
                } else {
                    $htmlPanel .= $index+1 . '.' . "\t" . $line;
                }
            } else {
                if($lineInBold > 0 && ($index+1) == $lineInBold) {
                    $htmlPanel .= $index+1 . '.' . "\t" . '<b style=\'cursor: pointer\' class=\'btn-warning\' >' . $line . '</b>';
                } else {
                    $htmlPanel .= $index+1 . '.' . "\t" . $line;
                }
            }
            
            
            $htmlPanel .= '</span>';
        }
        
        $htmlPanel .= '<br /></pre>';
        
        $arrayDontCanEditing = Array('imported_auto', 'imported');
        
        if(!in_array('imported_auto', explode('/', $file))) {
            $htmlPanel .= '<div id=\'button_edit\' >' . '<button onClick=\'editingClick()\' class=\'btn btn-info\' ><i class=\'fas fa5-edit\' ></i> &Eacute;diter le fichier</button>' . '</div>';
        }
                
        $htmlPanel .= '<div id=\'button_cancel\' style=\'display:none\' >' . '<button onClick=\'cancelClick()\' class=\'btn btn-danger\' ><i class=\'fas fa5-times\' ></i> Annuler les modifications</button>' . ' ' . BimpRender::renderButton(['icon' => 'fas_save', 'label' => 'Sauvegarder le fichier', 'onclick' => 'savingClick()']) . '</div>';
        
        $html .= BimpRender::renderPanel(basename($file), $htmlPanel, '', ['open' => (count($lines) > 100) ? false : true]);
        
        return $html;
    }
    
    private function getLinesOfFile($file): array {
        return file($file);
    }
    
    public function getObjectFromFile($file, $object, $module, $startChar, $strlen) {
        
        $html = '';
        $traitedObject = Array();
        
        $lines = $this->getLinesOfFile($file);
        
        if(count($lines) > 1) {
            foreach($lines as $index => $line) {
                $instance = BimpCache::getBimpObjectInstance($module, $object);
                $ref = str_replace(' ', '', substr($line, $startChar, $strlen));

                if(!in_array($ref, $traitedObject) && $index > 0 && $ref != '') {
                    $traitedObject[] = $ref;
                    $card = new BC_Card($instance);

                    if($object == 'Bimp_Societe') {
                        if($instance->find(Array('code_compta' => $ref), 1) || $instance->find(Array('code_compta_fournisseur' => $ref), 1)) {
                            $html .= BimpRender::renderPanel('<b>' . $instance->getName() . '</b>', $card->renderHtml(), '', Array('open' => 0));
                        } else {
                            $html .= BimpRender::renderAlerts('Impossible de charger la societé avec le code auxiliaire ' . $ref . ' à la ligne #' . ($index+1), 'danger', false);
                        }
                    } else {
                        $refField = 'ref';
                        if($object == 'Bimp_Facture') $refField = 'ref';

                        if($instance->find(Array($refField => $ref), 1)) {
                            $html .= BimpRender::renderPanel('<b>' . $instance->getref() . '</b>' . ' Ligne #' . ($index+1), $card->renderHtml(), '', Array('open' => 0));
                        } else {
                            $html .= BimpRender::renderAlerts('Impossible de charger la pièce avec la référence ' . $ref . ' à la ligne #' . ($index+1), 'danger', false);
                        }
                    }

                }

            }
        } else {
            $html = BimpRender::renderAlerts('Aucune ligne dans ce fichier', 'danger', false);
        }

        return $html;
        
    }

    public function getTiersFromfile($file, $type) {
        
        $html = '';
        
        $instance       = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe');
        $traiteTiers    = Array();
        
        switch($type) {
            case 'tiers': $startCodeAux = 6; break;
        }
        
        $lines = $this->getLinesOfFile($file);
        if(count($lines) > 1) {
            foreach($lines as $index => $line) {
                if($index > 0) {
                    $aux = str_replace(' ', '', substr($line, $startCodeAux, 17));

                    if(!in_array($aux, $traiteTiers)) {
                        $traiteTiers[] = $aux;
                        if($instance->find(['code_compta' => $aux], 1) || $instance->find(['code_compta_fournisseur' => $aux], 1)) {
                            $card = new BC_Card($instance);
                            $html .= BimpRender::renderPanel('<b>' . $instance->getName() . '</b>' . ' ' . $aux . ' ' . '<b>' . 'Ligne #' . ($index+1) . '</b>', $card->renderHtml(), '', Array('open' => 0));
                        } else {
                            $html .= BimpRender::renderAlerts('Impossible de charger le tiers avec le code auxiliaire ' . $aux . ' à la ligne#' . ($index+1), 'danger', false);
                        }
                    }
                }
            }
        } else {
            $html = BimpRender::renderAlerts('Le fichier ' . basename($file) . ' ne comporte pas d\'écritures', 'warning', false);
        }
        
        
        return $html;
        
    }
    
    private function getLineInFileTiers($auxiliaire, $lines, $by) {
        
        $allLines = Array();
        $facture = '';
        foreach($lines as $index => $line) {
            switch($by) {
                case 'tiers':
                    if(str_replace(' ', '', substr($line, 6, 17)) == $auxiliaire) {
                        return $index+1;
                    }
                    break;
                case 'facture':
                    if(str_replace(' ', '', substr($line, 31, 17)) == $auxiliaire) {
                        $facture = str_replace(' ', '', substr($line, 48, 35));
                    }
                    if(str_replace(' ', '', substr($line, 48, 35)) == $facture) {
                        $allLines[] = ($index+1);
                    }
                    
                    break;
            }
        }
        
        if(count($allLines)>0) return $allLines;
        
        return 0;
        
    }
        
    public function searchResultat($auxiliaire, $facture, $searchBy) {
        
        $html = '';        
        
        $pattern_tiers  = 0 . '_' . BimpCore::getExtendsEntity() . '_(TIERS)_' . '*' . '_' . BimpCore::getConf('version_tra', null, "bimptocegid") . '.tra';
        $pattern_ventes = 1 . '_' . BimpCore::getExtendsEntity() . '_(VENTES)_' . '*' . '_' . BimpCore::getConf('version_tra', null, "bimptocegid") . '.tra';
        $files = glob(PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/' . 'imported_auto' . '/' . $pattern_tiers);
        
        if($searchBy == 0) {
            foreach($files as $file) {
                $lineInBold = $this->getLineInFileTiers($auxiliaire, $this->getLinesOfFile($file), 'tiers');
                if($lineInBold > 0) {
                    $html .= $this->displayTraFile($file, $lineInBold);
                }
            }
            $files = glob(PATH_TMP . "/" . 'exportCegid' . '/' . 'BY_DATE' . '/' . 'imported_auto' . '/' . $pattern_ventes);
            foreach($files as $file) {
                $lineInBold = $this->getLineInFileTiers($auxiliaire, $this->getLinesOfFile($file), 'facture');
                if($lineInBold > 0) {
                    $html .= $this->displayTraFile($file, $lineInBold);
                }
            }
            
            if($html == "") {
                $html .= BimpRender::renderAlerts('Aucun résultats pour ' . $auxiliaire, "info", false);
            }
        }

        return $html;
        
    }
        
    public function renderNavTabVentes() {
        $html = '';
        
        $list_compte_7 = $this->db->getRows('bimpcore_conf', 'value LIKE "70%"');
        
        $headerList = [
            'name'      => 'Nom de la configation',
            'value'      => 'Valeur',
            'buttons'   => ['label' => '', 'col_style' => 'text-align: right']
        ];
        
        $rows = Array();
        
        foreach($list_compte_7 as $conf) {
            
            $rows[] = Array('name' => $conf->name, 'value' => $conf->value);
            
        }
        
        $html .= BimpRender::renderBimpListTable($rows, $headerList, Array());
        
        return Bimprender::renderPanel('Comptes 7', $html);
    }
    
    public function renderNavTabCheck() {
        
        return Bimprender::renderPanel('Comptes 7', print_r($data, 1));
        
    }
    
    public function renderNavTabAchats() {
        $html = '';
        
        $list_compte_7 = $this->db->getRows('bimpcore_conf', 'value LIKE "60%"');
        
        $headerList = [
            'name'      => 'Nom de la configation',
            'value'      => 'Valeur',
            'buttons'   => ['label' => '', 'col_style' => 'text-align: right']
        ];
        
        $rows = Array();
        
        foreach($list_compte_7 as $conf) {
            
            $rows[] = Array('name' => $conf->name, 'value' => $conf->value);
            
        }
        
        $html .= BimpRender::renderBimpListTable($rows, $headerList, Array());
        
        return Bimprender::renderPanel('Comptes 6', $html);
    }
    
    public function actionVerify($data, &$success) {
        $errors = Array();
        $warnings = Array();

        $errorsTiers = controle::tra($data['file'],  $this->getLinesOfFile($data['file']), 'tiers');
        
        if($errorsTiers['header'] != '') $errors[] = $errorsTiers['header'];
        if(count($errorsTiers['alignement']) > 0) $errors[] = $errorsTiers['alignement'];
                
        if(!count($errors))
            $success = "Le fichier " . basename ($data['file']) . ' est corriectement constitué';
        
        return Array('success' => $success, 'errors' => $errors, 'warnings' => $warnings);
    }
}