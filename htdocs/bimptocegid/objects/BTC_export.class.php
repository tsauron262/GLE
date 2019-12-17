<?php

class BTC_export extends BimpObject {

    private $sql_limit = 1; // Nombre de résultats dans la requete SQL: null = unlimited
    private $date_export = null;
    private $today;
    public $file;
    private $current_month;
    private $current_ref_by_get = null;
    private $current_date_by_get = null;
    private $folder_trimestre = "";
    private $export_directory = "/data/synchro/bimp/"; // Dossier d'écriture des fichiers
    //private $export_directory = '/usr/local/data2/test_alexis/synchro/'; // Chemin DATAs version de test alexis 
    private $project_directory = 'BIMPtoCEGID';
    public $type_ecriture = "S"; // S: Simulation, N: Normal
    
    public static $trimestres = [
        "T1" => ["01", "02", "03"],
        "T2" => ["04", "05", "06"],
        "T3" => ["07", "08", "09"],
        "T4" => ["10", "11", "12"]
    ];
    
    public static $month = [
        "01" => "Janvier", "02" => "Février", "03" => "Mars",
        "04" => "Avril", "05" => "Mai", "06" => "Juin",
        "07" => "Juillet", "08" => "Août", "09" => "Septembre",
        "10" => "Octobre", "11" => "Novembre", "12" => "Décembre"
    ];
    
    public function defineFolderTrimestre() {
        foreach(self::$trimestres as $T => $dates) {
            if(in_array(date('m'), $dates)) {
                switch($T) {
                    case 'T1':
                        $folder  = "_Trimestre_1";
                        break;
                    case 'T2':
                        $folder = "_Trimestre_2";
                        break;
                    case 'T3':
                        $folder = "_Trimestre_3";
                        break;
                    case 'T4':
                        $folder = "_Trimestre_4";
                        break;
                }
            }
        }
        return date('Y') .  $folder;
    }

    public function getStartTrimestreComptable() {
        foreach(self::$trimestres as $T => $dates) {
            if(in_array(date('m'), $dates)) {
                $start_trimestre = date('Y') . '-' . $dates[0] . '-01';
            }
        }
        if(BimpCore::getConf("BIMPtoCEGID_start_current_trimestre") != $start_trimestre) {
            BimpCore::setConf("BIMPtoCEGID_start_current_trimestre", $start_trimestre);
        }
        
        return $start_trimestre;

    }
    
    /**
     *  Sert à lancer l'export en fonction de l'élément demander et l'origine d'exécution de l'export
     *  @param string $element
     *  @param string $origin
     * 
     */
    
    public function export($element, $origin) {
        $since = false;
        
        if($origin == 'cronJob') {
            $this->folder_trimestre = $this->defineFolderTrimestre();
            $this->current_month = self::$month[date('m')];
            $this->today = date("Y-m-d");
            if(isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
                $this->date_export = $_REQUEST['date'];
                $this->current_date_by_get = $_REQUEST['date'];
                //$this->create_daily_file();
            } else {
                $this->date_export = $this->getStartTrimestreComptable();
                $since = true;
            }
            if(isset($_REQUEST['sql_limit'])){
                $this->sql_limit = $_REQUEST['sql_limit'];
            }
            $function_name = 'export_' . $element;
            
            if(isset($_REQUEST['ref']) && !empty($_REQUEST['ref'])) {
                $this->current_ref_by_get = $_REQUEST['ref'];
                //$this->create_daily_file('vente');
                $this->$function_name($_REQUEST['ref']);
            } else {
                //$this->create_daily_file('vente');
                $this->$function_name(null, $since);
            }
            
        } elseif($origin == 'web') {
            echo BimpRender::renderAlerts("Vous ne pouvez pas exporter d'écriture directement depuis cette page", 'danger', false);
        } else {
            echo BimpRender::renderAlerts("L'origine <b>".$origin."</b> n'existe pas", 'danger', false);
        }
    }
    
    /**
     * Créer les fichiers TRA nécéssaires à l'export du jour
     * @param int $element
     * @return string
     */
    
    protected function create_daily_file($element = null, $date = null) {
        
        $daily_files = [];
        if(isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
            $complementFileName = $_REQUEST['date'];
            $complementDirectory = 'BY_DATE';
        }elseif(isset($_REQUEST['ref']) && !empty($_REQUEST['ref'])) {
            $complementFileName = $_REQUEST['ref'];
            $complementDirectory = 'BY_REF';
        } else {
            $complementFileName = $this->today;
            $complementDirectory = $this->folder_trimestre;
        }
        
        $export_dir = $this->export_directory . $this->project_directory . '/' . $complementDirectory . '/';
        $export_dir_month = $export_dir . $this->current_month . "/";
        $export_project_dir = $this->export_directory . $this->project_directory . '/';
        
        switch($element) {
            case 'vente':
                $file = '1_BIMPtoCEGID_(VENTES)_' . $complementFileName . ".TRA";
                break;
            case 'tier':
                $file = '0_BIMPtoCEGID_(TIERS)_' . $complementFileName . ".TRA";
                break;
            case 'achat':
                $file = '3_BIMPtoCEGID_(ACHATS)_' . $complementFileName . ".TRA";
                break;
            case 'paiement':
                $file = '2_BIMPtoCEGID_(PAIEMENTS)_' . $complementFileName . ".TRA";
                break;
        }
        
        if(!is_dir($export_dir)) {
            mkdir($export_project_dir, 0777, true);
            mkdir($export_project_dir . "exported/", 0777, true);
            mkdir($export_dir, 0777, true);
            mkdir($export_dir_month, 0777, true);
            mkdir($export_dir_month . 'exported/', 0777, true);
        }
        
        if(!file_exists($export_dir_month . $file)) {
            $create_file = fopen($export_dir_month . $file, 'a+');
            fwrite($create_file, $this->head_tra());
            fclose($create_file);
        }
        
        
        //echo $export_dir . $file; die();
        $this->file = $export_dir . $file;
        return $export_dir . $file;
    }
    
    /**
     * En cas d'érreur retournée, on créer une bimptack avec l'envois d'un mail
     * @global type $conf
     * @param type $error
     * @param type $id_element
     */
    
    protected function addTaskAlert($data){
        global $conf;
        $subj = 'Compta ERP - ' . $data['ref'];
        $msg = "La pièce comptable " . $data['ref'] . ' ne c\'est pas exportée';

        if(isset($conf->global->MAIN_MODULE_BIMPTASK)){
            include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
            $task = BimpObject::getInstance("bimptask", "BIMP_Task");
            $tab = array("src"=>"al.bernard@bimp.fr", "dst"=>"task0001@bimp.fr", "subj"=> $subj, "txt"=>$msg, "prio"=>20, "id_user_owner" => 460);
            $this->errors = array_merge($this->errors, $task->validateArray($tab));
            $this->errors = array_merge($this->errors, $task->createIfNotActif());
        }
    }
    
    /**
     * Lance l'export des paiements
     * @param type $ref
     */
    
    private function export_paiement($ref = null) {
        $liste = $this->get_paiements_for_export($ref);
        $forced = (is_null($ref)) ? false : true;
        if(count($liste)) {
            $instance = $this->getInstance('bimptocegid', 'BTC_export_paiement');
            foreach ($liste as $paiement) {
                if($instance->export($paiement->rowid, $paiement->fk_paiement, $forced)) {
                    $pay = $this->getInstance('bimpcommercial', 'Bimp_Paiement', $paiement->rowid);
                    $pay->updateField('exported', 1);
                } else {
                    // Mettre task
                    $this->addTaskAlert(['ref' => $instance->getData('ref')]);
                }
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à plus de paiement à exporté", 'warning', false);
        }
    }
    
    private function export_facture_fourn($ref = null) {
        $liste = $this->get_facture_fourn_for_export($ref);
        $forced = (is_null($ref)) ? false : true;
        if(count($liste)) {
            $instance = $this->getInstance('bimptocegid', 'BTC_export_facture_fourn');
            foreach($liste as $facture_fourn) {
                $error = $instance->export($facture_fourn->rowid, $forced);
                if($error <= 0) {
                    
                }
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à plus de factures fournisseur à exporté", 'warning', false);
        }
    }
    
    private function export_facture($ref = null) {
        $liste = $this->get_facture_client_for_export($ref);
        $forced = (is_null($ref)) ? false : true;
        if(count($liste)) {
            $instance = $this->getInstance('bimptocegid', 'BTC_export_facture');
            foreach($liste as $facture) {
                $error = $instance->export($facture->rowid, $forced);
                if($error <= 0) {
                    
                }
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à plus de facture client à exporté", 'warning', false);
        }
    }
    
    protected function get_paiements_for_export($ref, $since = false) {
        if(!is_null($ref)) {
            return $this->db->getRows('paiement', 'ref = "' . $ref . '"');
        } elseif($since) {
            return $this->db->getRows('paiement', 'exported = 0 AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->today.' 23:59:59"', $this->sql_limit);
        } else {
            return $this->db->getRows('paiement', 'exported = 0 AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->date_export.' 23:59:59"', $this->sql_limit);
        }
        
    }
    
    protected function get_facture_fourn_for_export($ref, $since = false) {
        if(!is_null($ref)) {
            return $this->db->getRows('facture_fourn', 'ref="'.$ref.'"');
        } elseif($since) {
            return $this->db->getRows('facture_fourn', 'exported = 0 AND fk_statut IN(1,2) AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->today.' 23:59:59"', $this->sql_limit);
        } else {
            return $this->db->getRows('facture_fourn', 'exported = 0 AND fk_statut IN(1,2) AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->date_export.' 23:59:59"', $this->sql_limit);
        }
    }
    
    protected function get_facture_client_for_export($ref, $since = false) {
        if(!is_null($ref)) {
            return $this->db->getRows('facture', 'facnumber="'.$ref.'"');
        } elseif ($since) {
            return $this->db->getRows('facture', 'exported = 0 AND fk_statut IN(1,2) AND type != 3 AND datef BETWEEN "'.$this->date_export.'" AND "'.$this->today.'"', $this->sql_limit);            
        } else {
            return $this->db->getRows('facture', 'exported = 0 AND fk_statut IN(1,2) AND type != 3 AND datef BETWEEN "'.$this->date_export.'" AND "'.$this->date_export.'"', $this->sql_limit);
        }
    }
    
    /**
     * Dimentionne le texte pour le format de fichier TRA
     * @param string $texte
     * @param int $nombre
     * @param bool $espaceAvant
     * @param bool $zero
     * @param boll $zeroAvant
     * @return string
     */
    public function sizing($texte, $nombre, $espaceAvant = false, $zero = false, $zeroAvant = false) {
        $longeurText = strlen($texte);
        $avantTexte = "";
        $espacesRequis = $nombre - $longeurText;
        if ($espacesRequis > 0) {
            if ($zero) {
                if (!is_null($texte))
                    for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                        $texte .= "0";
                    } else
                    for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                        $texte .= " ";
                    }
            } elseif ($espaceAvant) {
                $avantTexte = "";
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $avantTexte .= " ";
                }
            } elseif ($zeroAvant) {
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $avantTexte .= "0";
                }
            } else {
                for ($compteurEspace = 0; $compteurEspace < $espacesRequis; $compteurEspace++) {
                    $texte .= " ";
                }
            }
        } elseif ($espacesRequis < 0) {
            $texte = substr($texte, 0, $nombre);
        }
        $texte = $avantTexte . $texte;
        return $texte;
    }
    
    /**
     * Supprimer les accents et les caractères spéciaux des chaines de caractère
     * @param string $str
     * @param string $encoding
     * @return string
     */
    public function suppr_accents($str, $encoding = 'utf-8') {
        $str = htmlentities($str, ENT_NOQUOTES, $encoding);
        $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace("#&[^;]+;#", '', $str);
        $str = str_replace("\n", "", $str);
        $str = str_replace("\t", "", $str);
        $str = str_replace("\r", "", $str);
        return $str;
    }
    
    public function get_sens($amount, $element, $inverse = false, $sens_parent = 'D') {
        switch($element) {
            case 'paiement':
                if($inverse){
                    return ($amount > 0) ? 'C' : 'D';
                }
                return ($amount < 0) ? 'C' : 'D';
                break;
            case 'facture_fourn':
                $sens = ($sens_parent == 'D') ? 'C' : 'D';
                if($inverse) {
                     $sens = ($sens_parent == 'D') ? 'D' : 'C';
                }
                break;
            case 'facture': 
                $sens = ($sens_parent == 'D') ? 'D' : 'C';
                if($inverse) {
                    $sens = ($sens_parent == 'D') ? 'C' : "D";
                } 
                break;
        }
        return $sens;
    }
    
    protected function convertion_to_interco_code($compte_a_convertir, $compte_interco) {
        $start_compte = substr($compte_a_convertir, 0,6);
        $end_compte =  substr($compte_interco, 6, 7);
        return $start_compte . $end_compte;
    }
    
    public function loadEntrepot($id_entrepot) {
        return $this->db->getRow('entrepot', 'rowid = ' . $id_entrepot);
    }

    protected function head_tra() {
        $head = "";
        $jump = "\n";
        $head .= $this->sizing("***", 3);
        $head .= $this->sizing("S5", 2);
        $head .= $this->sizing("CLI", 3);
        $head .= $this->sizing("JRL", 3);
        $head .= $this->sizing("ETE", 3);
        $head .= $this->sizing("", 3);
        $head .= $this->sizing("01011900", 8);
        $head .= $this->sizing("01011900", 8);
        $head .= $this->sizing("007", 3);
        $head .= $this->sizing("", 5);
        $head .= $this->sizing(date('dmYHi'), 12);
        $head .= $this->sizing("CEG", 35);
        $head .= $this->sizing("", 35);
        $head .= $this->sizing("", 4);
        $head .= $this->sizing("", 9);
        $head .= $this->sizing("01011900", 8);
        $head .= $this->sizing("001", 3);
        $head .= $jump;
        return $head;
    }
    
    protected function struct($ecriture) {
        $returned_ecriture = "";
        foreach ($ecriture as $data) {
            switch (count($data)) {
                case 2:
                    $returned_ecriture .= $this->sizing($data[0], $data[1]);
                    break;
                case 3:
                    $returned_ecriture .= $this->sizing($data[0], $data[1], $data[2]);
                    break;
            }
        }
        $returned_ecriture .= "\n";
        return $returned_ecriture;
    }
    
    protected function write_tra($ecriture, $file) {
        echo $file;
        $opened_file = fopen($file, 'a+');
        if(fwrite($opened_file, $ecriture)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isApple($nom_client) {        
        if(strstr(strtolower($nom_client), 'apple')) {
            return true;
        }
        return false;
    }
    
    protected function rectifications_ecarts($lignes_facture, $ecart, $type_ecriture) {
        $comptes_reatribuable = 
            [
                'achat' => ["607", "604"],
                'vente' => ["707","706"]
            ];
        
        $reactribution_faite = false;
        foreach($lignes_facture as $compte_comptable => $infos) {
            $compte_general = substr($compte_comptable, 0, 3);
            if(in_array($compte_general, $comptes_reatribuable[$type_ecriture]) && !$reactribution_faite) {
                echo $ecart;
                $lignes_facture[$compte_comptable]['HT'] += $ecart;
                $reactribution_faite = true;
            }           
        }
        
        return $lignes_facture;
    }
    
    protected function send_mail_module($data) {
        
        $send_user = $this->getInstance('bimpcore', "Bimp_User", 460);
        
        switch($data['cause']) {
            
            case 1:
                $element = "<b><i>Facture N° ".$data['element']."</i></b><br/>";
                $cause = "<b><i>&Eacute;cart de ".round($data['ecart'], 2)."€</i></b>";
                break;
            
        }
        $mail = "";
        $mail.= "<h3>BIMP<span class='warning'><b>to</b></span>CEGID</h3>";
        $mail.= "<i style='color: grey'>Ce mail est un mail automatique du module</i>";
        $mail.= "<br /><br />&Eacute;l&eacute;ment : " . $element;
        $mail.= "Cause : " . $cause;
        $mail.= "<br /><br />";
        $mail.= $send_user->getData('signature');
        echo $mail;
        
    }
    
// A UTILISER POUR LES EXPORTS DES FACTURES
//    if($contact = $this->db->getRow('element_contact', 'element_id = ' . $facture->getData('fk_soc') . ' AND fk_c_type_contact = 60')) {
//                $id_client_facturation = $contact->fk_socpeople;
//            } else {
//                $id_client_facturation = $facture->getData('fk_soc');
//            }
    
    
}