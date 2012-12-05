<?php
require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
class Synopsis_Contrat extends Contrat{
    public function getTypeContrat_noLoad($id)
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = ".$id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        return($res->extraparams);
    }
    
    public function fetch($id){
        parent::fetch($id);
        $this->type = $this->extraparams;
    }
    public function displayExtraInfoCartouche()
    {
        return "";
    }
    public function contratCheck_link()
    {
        $this->linkedArray['co'] = array();
        $this->linkedArray['pr'] = array();
        $this->linkedArray['fa'] = array();
        $db=$this->db;
        //check si commande ou propale ou facture
        if (preg_match('/^([c|p|f]{1})([0-9]*)/',$this->linkedTo,$arr))
        {
            //si commande check si propal lie a la commande / facture etc ...
            switch($arr[1])
            {
                case "p":
                    //test si commande facture
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_propale = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['co'],$res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_propale = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['fa'],$res->fk_facture);
                        }
                    }
                break;
                case "c":
                    //test si commande propal ...
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_pr WHERE fk_commande = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['pr'],$res->fk_propale);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_fa WHERE fk_commande = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['fa'],$res->fk_facture);
                        }
                    }
                break;
                case "f":
                    //test si propal facture ...
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."co_fa WHERE fk_facture = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['co'],$res->fk_commande);
                        }
                    }
                    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."fa_pr WHERE fk_facture = ".$arr[2];
                    if ($resql = $db->query($requete))
                    {
                        while ($res = $db->fetch_object($resql))
                        {
                            array_push($this->linkedArray['pr'],$res->fk_propal);
                        }
                    }
                break;
            }
        }
        //ajoute donnees dans les tables
//        var_dump($this->linkedArray);
    }
    
    
    public function getTypeContrat()
    {
        $array[0]['type']="Simple";
        $array[0]['Nom']="Simple";
        $array[1]['type']="Service";
        $array[1]['Nom']="Service";
        $array[2]['type']="Ticket";
        $array[2]['Nom']="Au ticket";
        $array[3]['type']="Maintenance";
        $array[3]['Nom']="Maintenance";
        $array[4]['type']="SAV";
        $array[4]['Nom']="SAV";
        $array[5]['type']="Location";
        $array[5]['Nom']="Location de produits";
        $array[6]['type']="LocationFinanciere";
        $array[6]['Nom']="Location Financi&egrave;re";
        $array[7]['type']="Mixte";
        $array[7]['Nom']="Mixte";
        return ($array[$this->typeContrat]);
    }
    
    public function getExtraHeadTab($head){
        return $head;
    }
    
    
    public function list_all_valid_contacts(){
        return array(); 
    }
}
?>
