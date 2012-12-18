<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
class Ressource
{
    public $db;
    public function Ressource($db)
    {
        $this->db = $db;
    }
    public $id;
    public $nom;
    public $fk_user_resp;
    public $user_resp;
    public $description;
    public $fk_parent_ressource;
    public $parent_ressource;
    public $date_achat;
    public $date_achat_epoch;
    public $isGroup;
    public $valeur;
    public $cout;
    public $zimbra_id;

    public function fetch($id)
    {
        $this->id = $id;
        $requete = "SELECT id,
                           nom,
                           fk_user_resp,
                           description,
                           fk_parent_ressource,
                           unix_timestamp(date_achat) as date_achatF,
                           isGroup,
                           valeur,
                           cout,
                           zimbra_id
                      FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources
                     WHERE id = ".$this->id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            $res = $this->db->fetch_object($sql);
            $this->nom = $res->nom;
            $this->fk_user_resp = $res->fk_user_resp;
            $tmpUser = new User($this->db);
            $tmpUser->fetch($res->fk_user_resp);
            $this->user_resp = $tmpUser;
            $this->description = $res->decription;
            $this->fk_parent_ressource = $res->fk_parent_ressource;
            $tmpRes = new Ressource($this->db);
            $tmpRes->fetch($res->fk_parent_ressource);
            $this->parent_ressource = $tmpRes;
            $this->date_achat = date('d/m/Y',$res->date_achatF);
            $this->date_achat_epoch = $res->date_achatF;
            $this->isGroup = $res->isGroup;
            $this->valeur = $res->valeur;
            $this->cout = $res->cout;
            $this->zimbra_id = $res->zimbra_id;
            return($this);
        } else {
            return false;
        }


    }
    public function parseRecursiveCat()
    {
        $db = $this->db;
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE fk_parent_ressource is null and isGroup = 1";
        $sql = $db->query($requete);
        print '<ul style="display: block;">';
        $iter = 0;
        while ($res = $db->fetch_object($sql)){
            print '<input type="hidden" id="resParent'.$res->id.'" value="-1"></input>';
            $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE fk_parent_ressource = $res->id AND isGroup = 1";
            $sql1 = $db->query($requete1);
            if ($iter == $db->num_rows($sql1))
            {
                print "<li class='expandable lastExpandable'> <div class='hitarea lastExpandable-hitarea'></div><strong><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.")'>".$res->nom."</a></strong>";
                $this->parseRecursiveSubCat($res->id);
                print "</li>";
            } else {
                print "<li class='expandable'> <div class='hitarea expandable-hitarea'></div><strong><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.")'>".$res->nom."</a></strong>";
                $this->parseRecursiveSubCat($res->id);
                print "</li>";
            }
            $iter++;
        }
        print "</ul>";
    }

    private function parseRecursiveSubCat($id)
    {
        $db = $this->db;
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE fk_parent_ressource =".$id." and isGroup = 1";
        $sql = $db->query($requete);
        print '<ul style="display: none;">';
        $iter=0;
        while ($res = $db->fetch_object($sql)){
        print '<input type="hidden" id="resParent'.$res->id.'" value="'.$id.'"></input>';
            $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE fk_parent_ressource = $res->id AND isGroup = 1";
            $sql1 = $db->query($requete1);
            $iter++;
            if ($db->num_rows($sql1) > 0)
            {
                if ( $db->num_rows($sql) ==$iter)
                {
                    print "<li class='lastExpandable'><div class='hitarea lastExpandable-hitarea'></div><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.");'><strong>".$res->nom."</strong></a>";
                    $this->parseRecursiveSubCat($res->id);
                    print "</li>";
                } else {
                    print "<li class='expandable'><div class='hitarea expandable-hitarea'></div><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.");'><strong>".$res->nom."</strong></a>";
                    $this->parseRecursiveSubCat($res->id);
                    print "</li>";
                }
            } else {
                if ($db->num_rows($sql)==$iter)
                {
                    print "<li class='last'><div class='last'></div><strong><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.");'>".$res->nom."</a></strong>";
                    print "</li>";
                } else {
                    print "<li class=''><div class=''></div><strong><a id='catRes".$res->id."' href='javascript:showRessource(".$res->id.");'>".$res->nom."</a></strong>";
                    print "</li>";
                }
            }
        }
        print "</ul>";

    }
}



?>