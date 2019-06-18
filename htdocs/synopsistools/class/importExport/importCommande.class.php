<?php

require_once DOL_DOCUMENT_ROOT . "/synopsistools/class/importExport/importCat.class.php";

class importCommande extends import8sens {
    var $tabCommande = array();

    public function __construct($db) {
        parent::__construct($db);
        $this->path .= "commEnCours/";
        $this->sepCollone = "	";
    }

    function traiteLn($ln) {
        $this->tabResult["total"] ++;
        
        $this->tabCommande[$ln['OpePcxCode']][] = $ln;
        
        
        
    }
    
    function go() {
        parent::go();
        
        
error_reporting(E_ERROR);
ini_set('display_errors', 1);

        $tabFinal = array();
        $i = 0;
        $errors = array();
        foreach($this->tabCommande as $ref => $tabLn){
            $numImport = "test123";
            
            //Création commande
            /*$ln1 = $tabLn[0];
            $ref= $numImport.$ref;
            $secteur = ($ln1['PcvFree24']!= "" ? $ln1['PcvFree24'] : 'C');
            $comm = BimpObject::getInstance("bimpcommercial", "Bimp_Commande");
            $tab = array("ref" => $ref, "fk_soc" => "2", "fk_cond_reglement"=>3, "date_commande"=>traiteDate($ln1['OpeDate'], "/"), "ef_type"=>$secteur, 'libelle' => $numImport);
            $errors = array_merge($errors, $comm->validateArray($tab));
            $errors = array_merge($errors, $comm->create()); */
            if(!count($errors)){
                foreach($tabLn as $dataLn){
                    //Création de la ligne 
                    /*$commLn = BimpObject::getInstance("bimpcommercial", "Bimp_CommandeLine");
                    $idProd = $this->getProdId($dataLn['ArtCode']);
                    $dataLn['OpePA'] = str_replace(",",".",$dataLn['OpePA']);
                    $dataLn['OpePUNet'] = str_replace(",",".",$dataLn['OpePUNet']);
                    if($idProd > 0){
                        $tab = array("id_obj"=>$comm->id, "type"=>1, "id_product"=>$idProd, "qty"=>$dataLn['PlvQteUS']);
                        $commLn->id_product = $idProd;
                        $qteTrans = $dataLn['PlvQteTr'];
                        $commLn->pu_ht = $dataLn['OpePUNet'];
                        $commLn->qty = $dataLn['PlvQteUS'] - $qteTrans;
                        $commLn->pa_ht = $dataLn['OpePA'];
                        $errors = array_merge($errors, $commLn->validateArray($tab));
                        $errors = array_merge($errors, $commLn->create());
//                        if($qteTrans > 0)
//                            echo "<br/>Partielle : ".$ref . "|".$qteTrans."<br/>";
                        echo "ok ".$ref."<br/>";
                    }
                    else
                        echo "<br/>Pas de prod !!! ".$dataLn['ArtCode']."<br/>";*/
                    
                    $tabFinal[$ref][] = array("ref"=>$dataLn['ArtCode'], "qty"=>$dataLn['PlvQteUS'], "qtyEnBl" =>$dataLn['PlvQteTr'], "pv" => $dataLn['OpeMontant'], "pa" => $dataLn['OpePA'], "qteBlNonFact" => 0);
                }
            }

            $i++;
            if($i > 20000){
                
            print_r($errors); echo "fin anticipé : ".$i."/".count($this->tabCommande);
            die;
            }
            
            
        }
        
        
        
        global $tempDataBl;
        
        foreach($tempDataBl as $ref => $data){
            $find = $find2= false;
            if(isset($tabFinal[$ref])){
                foreach($data['lignes'] as $ln){
                    foreach($tabFinal[$ref] as $idT => $ln2){
                        if($ln['PlvGArtCode'] == $ln2['ref']){//ligne identique
                            $find2 = true;
                            $qteTotal = $tabFinal[$ref][$idT]['qty'];
                            $qteEnBl = $tabFinal[$ref][$idT]['qtyEnBl'];
                            $qteEnBlNonFact = (isset($tabFinal[$ref][$idT]['qteBlNonFact'])? $tabFinal[$ref][$idT]['qteBlNonFact'] : 0);
                            
                            $newqteEnBlNnFact = $ln['PlvQteATran'] + $qteEnBlNonFact;
                            if(($newqteEnBlNnFact <= $qteEnBl && $qteEnBl <= $qteTotal) ||
                                   ($qteTotal < 0 && $newqteEnBlNnFact >= $qteEnBl && $qteEnBl >= $qteTotal)  ){
//                                if($ln['PlvPUNet'] == $ln2['pv'] || $ln['PlvPUNet'] == -$ln2['pv']){
                                    $find = true;
                                    $tabFinal[$ref][$idT]['qteBlNonFact'] = $newqteEnBlNnFact;
                                    $tabFinal[$ref][$idT]['pa'] = $ln['PlvPA'];
                                    $tabFinal[$ref][$idT]['pv'] = $ln['PlvPUNet'];
                                    break;
//                                }
//                                else{
//                                    echo "probléme de prix ".$ln['PlvPUNet']."¬".$ln2['pv'];
//                                }
                            }
                        }
                    }
                }
            }
            if($find2 && !$find){
                                echo "ilogic ".$ref."<br/>";
            }
            if(!$find){
                foreach($data['lignes'] as $lnT){
                    $qty = $lnT['PlvQteATran'];
                    $lnTemp = array("ref"=>$lnT['PlvGArtCode'], "qty"=>$qty, "qtyEnBl"=>$qty, "qteBlNonFact" => $qty, "pv"=>$lnT['PlvPUNet'], "pa"=>$lnT['PlvPA']);
                    $tabFinal[$ref][] = $lnTemp;
                }
                
//                echo "<pre>";print_r($tabFinal[$ref]);die;
            }
        }
        
        if (!defined('BIMP_LIB')) {
            require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
        }
        
        BimpTools::processCommandesImport($tabFinal);
        
//        echo "<pre>";
//        print_r($errors);
//        print_r($tabFinal);
//        die("fin normal");
    }
    
    function getProdId($ref){
        $sql = $this->db->query("SELECT `rowid` FROM `llx_product` WHERE `ref` LIKE '".$ref."'");
        if($this->db->num_rows($sql)>0){
            $ln = $this->db->fetch_object($sql);
            return $ln->rowid;
        }
        return "";
    }

}


function traiteDate($date, $delim= "-"){
    $tab = explode(" ", $date);
    $tab2 = explode($delim, $tab[0]);
    $date = $tab2[2] .$delim. $tab2[1] .$delim. $tab2[0] ." ". $tab[1];
    return $date;
}
