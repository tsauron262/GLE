<?php
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

class CommandeGroup extends CommonObject{

    public $db;
    public $id;
    public $commandes =array();
    public $nom;
    public $qteInGrp;

    public function CommandeGroup($db) {
        $this->db = $db;
        $this->qteInGrp = 0;
    }
    public function fetch($id)
    {
        $this->id = $id;
        $requete = "SELECT Babel_commande_grp.nom, Babel_commande_grpdet.command_refid
                      FROM Babel_commande_grp
                 LEFT JOIN Babel_commande_grpdet ON Babel_commande_grp.id = Babel_commande_grpdet.commande_group_refid
                     WHERE Babel_commande_grp.id = ".$this->id;
        $sql = $this->db->query($requete);
        if ($sql && $this->db->num_rows($sql)>0)
        {
            $this->qteInGrp=0;
            $this->commandes=array();
            while ($res = $this->db->fetch_object($sql))
            {
                $this->nom = $res->nom;
                $this->qteInGrp++;
                if ($res->command_refid > 0)
                {
                    $comTmp = new Commande($this->db);
                    $comTmp->fetch($res->command_refid);
                    $this->commandes[$res->command_refid] = $comTmp;
                }
            }
            return $this->id;
        } else {
            return -1;
        }
    }
    public function delete()
    {
        $requete = "DELETE FROM Babel_commande_grp WHERE id = ".$this->id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            return 1;
        } else {
            return -1;
        }

    }

    public function add($name)
    {
        $name = addslashes($name);
        $requete = "INSERT INTO Babel_commande_grp (datec,nom) VALUES (now(),'".$name."')";
        $sql = $this->db->query($requete);
        $this->id = $this->db->last_insert_id('Babel_commande_grp');
        if ($this->id > 0)
        {
            return ($this->id);
        } else {
            return -1;
        }
    }

    public function getNomUrl($withpicto=0,$option=0)
    {
        global $langs;
        $result='';
        $urlOption='';

        $lien = '<a href="'.DOL_URL_ROOT.$urlOption.'/commande/group/fiche.php?id='.$this->id.'">';
        $lienfin='</a>';

        if ($option == 6) $lien = '<a href="'.GLE_FULL_ROOT.'/commande/group/fiche.php?id='.$this->id.'">';
        $picto='orderGroup';
        $label=$langs->trans("ShowOrderGroup").': '.$this->nom;

        //function img_object($alt, $object,$width = false, $height=false,$align=false,$fullWebPath=false)
        if ($option == 6){
            if ($withpicto) $result.=($lien.img_object($label,$picto,false,false,false,true).$lienfin);
        } else {
            if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        }
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.$this->nom.$lienfin;
        return $result;
    }
    public function update()
    {
        if ($this->nom."x" != "x")
        {
            $this->nom = addslashes($this->nom);
            $requete = "UPDATE Babel_commande_grp SET nom = '".$this->nom."' WHERE id = ".$this->id;
            $sql = $this->db->query($requete);
            if ($sql)
            {
                return 1;
            } else {
                return -1;
            }
        }
    }
    public function addCom($comId){
        if ($this->nom."x"=="x") $this->fetch($comId);
        $com = new Commande($this->db);
        $com->fetch($comId);
        $requete1 = "DELETE FROM Babel_commande_grpdet WHERE command_refid = ".$com->id;
        $sql = $this->db->query($requete1);
        $requete = "INSERT INTO Babel_commande_grpdet
                                (commande_group_refid,command_refid, refCommande)
                         VALUES (".$this->id.",".$com->id.",'".$com->ref."')";
        $sql = $this->db->query($requete);
        if ($sql){
            return(1);
        } else {
            return(-1);
        }
    }
    public function delCom($comId)
    {
        $requete1 = "DELETE FROM Babel_commande_grpdet WHERE command_refid = ".$comId;
        $sql = $this->db->query($requete1);
        if ($sql){
            return 1;
        } else {
            return -1;
        }
    }


}
?>