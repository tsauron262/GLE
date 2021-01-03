<?php 
//ini_set('display_errors', '1');
//  ini_set('display_startup_errors', '1');
//  error_reporting(E_ALL);
 require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
 require_once DOL_DOCUMENT_ROOT . '/synopsistools/SynDiversFunction.php';
 
 class tacite {
     
    public $output = "";
     
    CONST CONTRAT_STATUT_ABORT = -1;
    CONST CONTRAT_STATUS_BROUILLON = 0;
    CONST CONTRAT_STATUS_VALIDE = 1;
    CONST CONTRAT_STATUS_CLOS = 2;
    CONST CONTRAT_STATUS_WAIT = 10;
    CONST CONTRAT_STATUS_ACTIVER = 11;
    
    CONST CONTRAT_RENOUVELLEMENT_NON = 0;
    CONST CONTRAT_RENOUVELLEMENT_1_FOIS = 1;
    CONST CONTRAT_RENOUVELLEMENT_2_FOIS = 3;
    CONST CONTRAT_RENOUVELLEMENT_3_FOIS = 6;
    CONST CONTRAT_RENOUVELLEMENT_4_FOIS = 4;
    CONST CONTRAT_RENOUVELLEMENT_5_FOIS = 5;
    CONST CONTRAT_RENOUVELLEMENT_6_FOIS = 7;
    CONST CONTRAT_RENOUVELLEMENT_SUR_PROPOSITION = 12;
    
    public function renouvellement() {
        
        global $db;
        
        $bdd = new BimpDb($db);
        $have_au_moin_un_renouvellement = false;
        $sql = "SELECT fk_object, end_date_contrat FROM " . MAIN_DB_PREFIX . "contrat_extrafields as ce WHERE (";
        $sql.= "tacite = " . self::CONTRAT_RENOUVELLEMENT_1_FOIS . " OR tacite = " . self::CONTRAT_RENOUVELLEMENT_2_FOIS . " OR ";
        $sql.= "tacite = " . self::CONTRAT_RENOUVELLEMENT_3_FOIS . " OR tacite = " . self::CONTRAT_RENOUVELLEMENT_4_FOIS . " OR ";
        $sql.= "tacite = " . self::CONTRAT_RENOUVELLEMENT_5_FOIS . " OR tacite = " . self::CONTRAT_RENOUVELLEMENT_6_FOIS . ") ";
        $sql.= "AND (end_date_contrat = '".date('Y-m-d')."' OR end_date_contrat < '".date('Y-m-d')."') AND end_date_contrat <> '0000-00-00'";
        
        $res = $bdd->executeS($sql);
        $message = "<strong>BIMP</strong><strong style='color:#EF7D00'>contrat</strong><br /><br />";
        $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat');
        foreach($res as $index => $infos) {
            $contrat->fetch($infos->fk_object);
            if($contrat->getData('statut') == self::CONTRAT_STATUS_ACTIVER) {
                $id_new_contrat = $contrat->renouvellementTaciteCron();
                if($id_new_contrat > 0) {
                    $have_au_moin_un_renouvellement = true;
                    $message.= "Renouvellement automatique du contrat: " . $contrat->getRef() . " (new: $id_new_contrat)<br />";
                }
                
            }
        }
        if($have_au_moin_un_renouvellement) {
            $this->output .= $message;
            //mailSyn2("[Contrats] - Renouvellement automatique", BimpCore::getConf('bimpcontract_email_groupe'), "admin@bimp.fr", $message);
        }
    } 
 }