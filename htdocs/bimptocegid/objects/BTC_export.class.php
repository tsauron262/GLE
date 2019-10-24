<?php

class BTC_export extends BimpObject {
    
    const TYPE_RETURN_NE = -3; // Le fichier ne c'est pas écrit
    const TYPE_RETURN_CG = -2; // Il n'y à pas de compte générale pour le paiement
    const TYPE_RETURN_BQ = -1; // Il n'y à pas de banque associer au paiement
    const TYPE_RETURN_NO = 0; // Le retour par défault
    const TYPE_RETURN_OK = 1; // Succès de l'export

    private $sql_limit = 1; // Nombre de résultats dans la requete SQL: null = unlimited
    private $date_export = '2019-07-01'; // Date a laquel ont veux faire débuter l'export l'export : null = date du jours : 2019-07-01 => date de la bascule
    private $export_directory = "/data/synchro/bimp/"; // Dossier d'écriture des fichiers
    
    /**
     *  Sert à lancer l'export en fonction de l'élément demander et l'origine d'exécution de l'export
     *  @param string $element
     *  @param string $origin
     * 
     */
    
    public function export($element, $origin) {
        //mkdir($this->export_directory . "BIMPtoCEGID/", 0777, true);
        //rmdir($this->export_directory . "BIMPtoCEGID/");
        if($origin == 'cronJob') {
            $function_name = 'export_' . $element;
        
            if(is_null($this->date_export)) {
                $this->date_export = date('Y-m-d');
            }
            $this->create_daily_file();
            $this->$function_name();
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
    
    protected function create_daily_file($element = null) {
        $daily_files = [
            'tier' => '0_BIMPtoCEGID_(TIERS)_' . date('d|m|Y') . ".TRA",
            'vente' => '1_BIMPtoCEGID_(VENTES)_' . date('d|m|Y') . ".TRA",
            'paiement' => '2_BIMPtoCEGID_(PAIEMENTS)_' . date('d|m|Y') . ".TRA",
            'achat' => '3_BIMPtoCEGID_(ACHATS)_' . date('d|m|Y') . ".TRA",
        ];
        if(is_null($element)){
            foreach($daily_files as $element => $file) {
                if(!file_exists($this->export_directory . "BIMPtoCEGID/" . $file)) {
                    if(!file_exists($this->export_directory . "BIMPtoCEGID/")){
                        mkdir($this->export_directory . "BIMPtoCEGID/", 0777, true);
                        mkdir($this->export_directory . "exported/", 0777, true);
                    }
                    $create_file = fopen($this->export_directory . "BIMPtoCEGID/" . $file, 'a+');
                    fwrite($create_file, $this->head_tra());
                    fclose($create_file);
                }
            }
        }

        if(!is_null($element)) {
            return $daily_files[$element];
        }
    }
    
    /**
     * En cas d'érreur retournée, on créer une bimptack avec l'envois d'un mail
     * @global type $conf
     * @param type $error
     * @param type $id_element
     */
    
    protected function addTaskAlert($error, $id_element){
        global $conf;
        $msg = "";
        $subj = "";
        
        switch($error) {
            case self::TYPE_RETURN_NE:
                $subj = 'Paiement - Ecriture non inscrit dans le fichier TRA';
                $msg = "Bonjour, le paiement <b>#".$id_element."</b> à été traiter par le module mais son écriture n'à pas été inscrit dans le fichier</b>";
                break;
        }
        
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
                $error = $instance->export($paiement->rowid, $paiement->fk_paiement, $forced);
                if($error <= 0) {
                    
                } else {
                    $this->addTaskAlert($error, $paiement->rowid);
                }
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à plus de paiement à exporté", 'warning', false);
        }
    }
    
    private function export_facture_fourn($ref = "SI1909-4930") {
        $liste = $this->get_facture_fourn_for_export($ref);
        $forced = (is_null($ref)) ? false : true;
        if(count($liste)) {
            $instance = $this->getInstance('bimptocegid', 'BTC_export_facture_fourn');
            foreach($liste as $facture_fourn) {
                $error = $instance->export($facture_fourn->rowid, $forced);
            }
        } else {
            echo BimpRender::renderAlerts("Il n'y à plus de factures fournisseur à exporté", 'warning', false);
        }
    }
    
    protected function get_paiements_for_export($ref = null) {
        if(!is_null($ref)) {
            return $this->db->getRows('paiement', 'ref = "' . $ref . '"');
        }
        return $this->db->getRows('paiement', 'exported = 0 AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->date_export.' 23:59:59"', $this->sql_limit);
    }
    
    protected function get_facture_fourn_for_export($ref) {
        if(!is_null($ref)) {
            return $this->db->getRows('facture_fourn', 'ref="'.$ref.'"');
        }
        return $this->db->getRows('facture_fourn', 'exported = 0 AND fk_statut IN(1,2) AND datec BETWEEN "'.$this->date_export.' 00:00:00" AND "'.$this->date_export.' 23:59:59"', $this->sql_limit);
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
        }
        return $sens;
    }
    
    protected function convertion_to_interco_code($compte_a_convertir, $compte_interco) {
        echo substr($compte_a_convertir, 6, 7);
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
        $opened_file = fopen($this->export_directory . $file, 'a+');
        if(fwrite($opened_file, $ecriture)) {
            return 1;
        } else {
            return 0;
        }
    }
    
    public function isApple($nom_client) {        
        if(strstr(strtolower($nom_client), 'apple')) {
            return true;
        }
        return false;
    }
    
    protected function rectifications_ecarts($lignes_facture, $ecart) {
        $reactribution_faite = false;
        foreach($lignes_facture as $compte_comptable => $infos) {
            var_dump($compte_comptable);
            if(strstr($compte_comptable, 607)) {
                echo 'Trouver pour reatribution';
            }
        }
        
        return [];
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