<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 *     \class      synopsisdemandeinterv
 *    \brief      Classe des gestion des fiches interventions
 */
class Synopsispanier extends CommonObject {

    public $db;
    public $val= array();
    public $element = 'synopsispanier';
    public $table_element = 'Synopsis_Panier';
    public $id;
    public $referent;        // Id client
    public $valeur;        // Objet societe client (a charger par fetch_client)
    public $type;
    public $ref = '';
    
    function Synopsispanier($DB) {
        global $langs;

        $this->db = $DB;

        // Statut 0=brouillon, 1=valide
       
    }
    
    static function  getReferent($valeur, $type ) {
        $return = array();
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Panier WHERE type='".$type."' AND valeur=".$valeur.";";
        $res = $this->db->query($requete);
        while ($result = $this->db->fetch_object($res)) {
            $return[] = $result->referent;
        }
        return $return;
    }
    
    function getCountPanier($idReferent, $type){
        $requeteMomo = "SELECT count(valeur) as nb FROM ".MAIN_DB_PREFIX."Synopsys_Panier where type='".$type."' and referent = ".$idReferent.";";
        $result = $this->db->query($requeteMomo);
        $ligne = $this->db->fetch_object($result);
        return $ligne->nb;
        
    }
    
    function fetch($idReferent, $type) {
        $this->ref = $idReferent;
        $this->id = $idReferent;
        $this->referent = $idReferent;
        $this->type = $type;
        $requeteMomo = "SELECT valeur FROM ".MAIN_DB_PREFIX."Synopsys_Panier where type='".$type."' and referent = ".$idReferent.";";
        $result = $this->db->query($requeteMomo);
        while ($ligne = $this->db->fetch_object($result))
        {
            $societe = new Societe($this->db);
            $societe->fetch($ligne->valeur);
            $this->val[$ligne->valeur] = $societe;
        }
        $this->societe = new Societe($this->db);
        $this->societe->fetch($idReferent);
        return 1;  
    }
    
    function addElement($valeur){
       if (!isset($this->val[$valeur]) && $valeur > 0){
            $res = $this->db->query("INSERT INTO ".MAIN_DB_PREFIX."Synopsys_Panier (referent, type, valeur) VALUES(".$this->referent.",'".$this->type."',".$valeur.");"); 
            $this->val[$valeur] = new Societe($this->db);
            $this->val[$valeur]->fetch($valeur);
            return 1;
       }
    }
    
    function deleteElement ($valeur) {
        unset($this->val[$valeur]);
        $res = $this->db->query("DELETE FROM ".MAIN_DB_PREFIX."Synopsys_Panier WHERE referent = ".$this->referent." and type = '".$this->type."' and valeur = ".$valeur.";"); 
        return 2;
    }
    
    function getTabID() {
        echo "tabID = new Array();";
        foreach($this->val as $ligne)
        {
            echo "tabID.push(".$ligne->id.");";
        }
    }
    
    function getPresenceDB ($id){
        return (isset($this->val[$id]));
        
//        $requeteMomo = "SELECT valeur FROM ".MAIN_DB_PREFIX."Synopsys_Panier WHERE type='".$type."' AND referent=".$idReferent.";";
//        $result = $this->db->query($requeteMomo);
//        $present = false;
//        while ($ligne = $this->db->fetch_object($result))
//        {
//            if ($id == $ligne->valeur)
//            {
//
//                $present=true;
//                break;
//            }
//        }
//        return $present;
    }
}

    


