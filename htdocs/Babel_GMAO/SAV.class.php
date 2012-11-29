<?php

class SAV {

    public $db;
    public $id;
    public $societe_refid;
    public $fk_soc;
    public $datec;
    public $datecEpoch;
    public $dateeEpoch;
    public $ref;
    public $date_end;
    public $serial;
    public $statut;
    public $status;
    public $laststatus;
    public $laststatut;
    public $fk_product;
    public $product;
    public $lastMessage;
    public $descriptif_produit;
    public $descriptif_probleme;
    public $histo = array();
    public $error;


    public function SAV($DB) {
        $this->db = $DB;
    }
    public function fetch($id)
    {
        $this->id = $id;
        $requete= "SELECT societe_refid,
                          date_create,
                          UNIX_TIMESTAMP(date_create) as datecEpoch,
                          date_end,
                          UNIX_TIMESTAMP(date_end) as dateeEpoch,
                          UNIX_TIMESTAMP(tms) as dateModif,
                          statut,
                          serial,
                          fk_product,
                          ref,
                          lastMessage, descriptif_probleme, descriptif_produit
                     FROM Babel_GMAO_SAV_client
                    WHERE id = ".$id;
//print $requete;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->societe_refid = $res->societe_refid;
        $this->fk_soc = $res->societe_refid;
        $this->datec = $res->date_create;
        $this->datecEpoch = $res->datecEpoch;
        $this->datee = $res->date_end;
        $this->dateeEpoch = $res->dateeEpoch;
        $this->status = $res->statut;
        $this->statut = $res->statut;
        $this->dateModif = $res->dateModif;
        $this->serial = $res->serial;
        $this->ref = $res->ref;
        $this->fk_product = $res->fk_product;
        if ($this->fk_product > 0)
        {
            require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
            $prod = new Product($this->db);
            $prod->fetch($this->fk_product);
            $this->product = $prod;
        }
        $this->lastMessage = $res->lastMessage;
        $this->descriptif_probleme = $res->descriptif_probleme;
        $this->descriptif_produit = $res->descriptif_produit;
    }
    public function fetchHisto(){
        $requete = "SELECT UNIX_TIMESTAMP(date_message) as date_messageEpoch,
                           message,
                           current_statut,
                           last_statut,
                           id,
                           user_author
                      FROM Babel_GMAO_SAV_message
                     WHERE SAV_client_refid = ".$this->id . "
                  ORDER BY date_message DESC";
        $sql = $this->db->query($requete);
        $i=0;
        while ($res = $this->db->fetch_object($sql))
        {
            $this->histo[$i]['message']= $res->message;
            $this->histo[$i]['date']= $res->date_messageEpoch;
            $this->histo[$i]['last_statut']= $res->last_statut;
            $this->histo[$i]['current_statut']= $res->current_statut;
            $this->histo[$i]['id']= $res->id;
            $this->histo[$i]['user_author'] = $res->user_author;
            $i++;
        }

    }
    public function create()
    {
        //        $objsav->descriptif_produit=$_REQUEST["Description"];
        //    $objsav->descriptif_probleme=$_REQUEST["probleme"];
        //    $objsav->fk_soc=$_REQUEST["socid"];
        //    $objsav->fk_product=$_REQUEST["idprod"];
        //    $objsav->serial=$_REQUEST["serial"];
        if (! $this->fk_soc > 0)
        {
            return -1;
        }
        if (strlen(descriptif_probleme) < 1 || strlen(descriptif_produit) < 1)
        {
            return -2;
        }
        $serialnumber = "";
        $elementId = false;
        $elementType = false;
        if (preg_match('/^([\w]*)-([0-9]*)$/',$this->serial,$arrTmp))
        {
            $elementType = $arrTmp[1];
            $elementId = $arrTmp[2];
            $requete = "SELECT * FROM llx_product_serial_view WHERE element_id = ".$arrTmp[1]. " AND element_type like '".$arrTmp[2]."%' ORDER BY tms DESC LIMIT 1";
            $sql1 = $this->db->query($requete);
            $res1 = $this->db->fetch_object($sql1);
            $serialnumber = $res1->serial_number;
        }
        $requete = "INSERT INTO `Babel_GMAO_SAV_client`
                        (`societe_refid`,`date_create`,`statut`,`serial`,`fk_product`,`descriptif_produit`,`descriptif_probleme`, `element_id`, `element_type`)
                    VALUES
                        (".$this->fk_soc.", now(),  0, '".$serialnumber."', ".$this->fk_product.",'".$this->descriptif_produit."', '".$this->descriptif_probleme."',$elementId,'".$elementType."');
        ";
        $sql = $this->db->query($requete);
//        print $requete;
        if ($sql)
        {
            $newId = $this->db->last_insert_id('Babel_GMAO_SAV_client');
            $this->id=$newId;
            return ($newId);
        } else{
            $this->error = "SQL Insert error";
            return -3;
        }
    }
    public function getNextNumRef($soc)
    {
        global $db, $langs;
        $langs->load("synopsisGene@Synopsis_Tools");

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/sav/";
        if (defined("SAV_ADDON") && SAV_ADDON)
        {
            $file = SAV_ADDON.".php";

            // Chargement de la classe de numerotation
            $classname = SAV_ADDON;
            require_once($dir.$file);

            $obj = new $classname();

            $numref = "";
            $numref = $obj->getNextValue($soc,$this);

            if ( $numref ."x" != "x")
            {
            return $numref;
            }
            else
            {
            dol_print_error($db,"SAV::getNextNumRef ".$obj->error);
            return "";
            }
         } else {
            print $langs->trans("Error")." ".$langs->trans("Error_SAV_ADDON_NotDefined");
            return "";
         }
  }
    function getNomUrl($withpicto=0,$option='',$maxlen=0)
    {
        global $langs;

        $result='';

        $lien = '<a title="'.$this->nom.'" href="'.DOL_URL_ROOT.'/Babel_GMAO/SAV/fiche.php?id='.$this->id.'">';
        $lienfin='</a>';

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowSav").': '.$this->ref,'sav').$lienfin.' ');
        $result.=$lien.($maxlen?dol_trunc($this->descriptif_probleme,$maxlen):$this->descriptif_probleme).$lienfin;
        return $result;
    }


    public function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->status,$mode);
    }

    /**
    *        \brief      Renvoi le libelle d'un statut donne
    *        \param      status      Statut
    *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
    *        \return     string      Libelle du statut
    */
    public function LibStatut($status,$mode=0)
    {
        global $langs;

        $langs->load('products');
        $langs->load('sav');
        if ($mode == 0)
        {
            if ($status == 0) return $langs->trans('SAVbrouillon');
            if ($status == 10) return $langs->trans('SAVenTraitement');
            if ($status == 20) return $langs->trans('SAVenTraitementExterne');
            if ($status == 50) return $langs->trans('SAVRepare');
            if ($status == 80) return $langs->trans('SAVenAttenteClient');
            if ($status == 100) return $langs->trans('SAVtermine');
        }
        if ($mode == 1)
        {
            if ($status == 0) return $langs->trans('SAVbrouillon');
            if ($status == 10) return $langs->trans('SAVenTraitement');
            if ($status == 20) return $langs->trans('SAVenTraitementExterne');
            if ($status == 50) return $langs->trans('SAVRepare');
            if ($status == 80) return $langs->trans('SAVenAttenteClient');
            if ($status == 100) return $langs->trans('SAVtermine');
        }
        if ($mode == 2)
        {
            if ($status == 0) return img_picto($langs->trans('SAVbrouillon'),'statut0').' '.$langs->trans('SAVbrouillon');
            if ($status == 10) return img_picto($langs->trans('SAVenTraitement'),'statut1').' '.$langs->trans('SAVenTraitement');
            if ($status == 20) return img_picto($langs->trans('SAVenTraitementExterne'),'statut2').' '.$langs->trans('SAVenTraitementExterne');
            if ($status == 50) return img_picto($langs->trans('SAVRepare'),'statut3').' '.$langs->trans('SAVRepare');
            if ($status == 80) return img_picto($langs->trans('SAVenAttenteClient'),'statut4').' '.$langs->trans('SAVenAttenteClient');
            if ($status == 100) return img_picto($langs->trans('SAVtermine'),'statut5').' '.$langs->trans('SAVtermine');
        }
        if ($mode == 3)
        {
            if ($status == 0) return img_picto($langs->trans('SAVbrouillon'),'statut0');
            if ($status == 10) return img_picto($langs->trans('SAVenTraitement'),'statut1');
            if ($status == 20) return img_picto($langs->trans('SAVenTraitementExterne'),'statut2');
            if ($status == 50) return img_picto($langs->trans('SAVRepare'),'statut3');
            if ($status == 80) return img_picto($langs->trans('SAVenAttenteClient'),'statut4');
            if ($status == 100) return img_picto($langs->trans('SAVtermine'),'statut5');
        }
        if ($mode == 4)
        {
            if ($status == 0) return img_picto($langs->trans('SAVbrouillon'),'statut0').' '.$langs->trans('SAVbrouillon');
            if ($status == 10) return img_picto($langs->trans('SAVenTraitement'),'statut1').' '.$langs->trans('SAVenTraitement');
            if ($status == 20) return img_picto($langs->trans('SAVenTraitementExterne'),'statut2').' '.$langs->trans('SAVenTraitementExterne');
            if ($status == 50) return img_picto($langs->trans('SAVRepare'),'statut3').' '.$langs->trans('SAVRepare');
            if ($status == 80) return img_picto($langs->trans('SAVenAttenteClient'),'statut4').' '.$langs->trans('SAVenAttenteClient');
            if ($status == 100) return img_picto($langs->trans('SAVtermine'),'statut5').' '.$langs->trans('SAVtermine');
        }
        if ($mode == 5)
        {
            if ($status == 0) return $langs->trans('SAVbrouillon')." ".img_picto($langs->trans('SAVbrouillon'),'statut0');
            if ($status == 10) return $langs->trans('SAVenTraitement')." ".img_picto($langs->trans('SAVenTraitement'),'statut1');
            if ($status == 20) return $langs->trans('SAVenTraitementExterne')." ".img_picto($langs->trans('SAVenTraitementExterne'),'statut2');
            if ($status == 50) return $langs->trans('SAVRepare')." ".img_picto($langs->trans('SAVRepare'),'statut3');
            if ($status == 80) return $langs->trans('SAVenAttenteClient')." ".img_picto($langs->trans('SAVenAttenteClient'),'statut4');
            if ($status == 100) return $langs->trans('SAVtermine')." ".img_picto($langs->trans('SAVtermine'),'statut5');
        }
        return $langs->trans('Unknown');
    }
    public function setPriseEnCharge()
    {
        $this->laststatut = $this->statut;
        $this->setStatut(10);
        $this->statut = 10;
        $this->newMessage("Prise en charge du produit");
        //ajoute histo pris en charge => msg
        //Babel_GMAO_SAV_message

    }
    public function setTraitementExterne()
    {
        $this->laststatut = $this->statut;
        $this->setStatut(20);
        $this->statut = 20;
        $this->newMessage("R&eacute;paration externe");
    }
    public function setRepare()
    {
        $this->laststatut = $this->statut;
        $this->setStatut(50);
        $this->statut = 50;
        $this->newMessage("Produit r&eacute;par&eacute;");
    }
    public function setAttenteClient()
    {
        $this->laststatut = $this->statut;
        $this->setStatut(80);
        $this->statut = 80;
        $this->newMessage("En attente du produit");
    }
    public function setCloture()
    {
        $this->laststatut = $this->statut;
        $this->setStatut(100);
        $this->statut = 100;
        $this->newMessage("Action clotur&eacute;e");
    }
    public function setStatut($newStatut)
    {
        $requete = "UPDATE Babel_GMAO_SAV_client SET statut = ".$newStatut . " WHERE id = ".$this->id;
        $sql = $this->db->query($requete);
    }
    public function newMessage($pMsg)
    {
        global $user;
        $laststatut = $this->laststatut;
        if ($laststatut ."x" == "x")
        {
            $laststatut = $this->statut;
        }
        $requete = "INSERT Babel_GMAO_SAV_message
                           (tms, SAV_client_refid, current_statut, last_statut,date_message,message,user_author)
                    VALUES (now(),".$this->id.",".$this->statut.",".$laststatut.",now(),'".addslashes($pMsg)."',".$user->id." ) ";
        $sql = $this->db->query($requete);
        return($sql);
    }
}
?>