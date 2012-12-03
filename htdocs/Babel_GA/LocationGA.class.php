<?php

class LocationGA {
    public $db;
    public $serial;
    public $cession;
    public $fourn;
    public $client;
    public $id;
    public $product;
    public $fk_product;
    public $dateDeb;
    public $dateFin;
    public $user_resp_id;
    public $user_resp;
    public $statut;
    public $libelle;
    public $dateDeSortieDefinitive;
    public $PrixCessionALaSortie;
    public $entrepot_refid;


    public function LocationGA($db) {
        $this->db=$db;
    }
    public function fetch($id) {
        $this->id=$id;

        $sql = "SELECT *";
        $sql .= " FROM Babel_GA_entrepotdet";
        $sql.= " WHERE id= ".$this->id;

        $result = $this->db->query( $sql ) or die("Couldn t execute query : ".$sql." ".mysql_error());

        require_once(DOL_DOCUMENT_ROOT.'/societe.class.php');
        $this->cession=new Societe($this->db);
        $this->fourn=new Societe($this->db);
        $this->client=new Societe($this->db);
        require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
        $this->product=new Product($this->db);

        $obj = $this->db->fetch_object($result);

        $this->cession->fetch($obj->cessionnaire_refid);
        $this->fourn->fetch($obj->fournisseur_refid);
        $this->client->fetch($obj->client_refid);
        $this->product->fetch($obj->fk_product);
        $this->serial= $obj->serial;
        $this->ref= $obj->serial;
        $this->fk_product=$obj->fk_product;
        $this->libelle=$obj->libelle;
        $this->dateDeb = $obj->dateDeb;
        $this->dateFin = $obj->dateFin;
        $this->dateFin = $obj->dateFin;
        $this->user_resp_id = $obj->user_resp_refid;
        $tmpUser = new User($this->db);
        $tmpUser->id = $obj->user_resp_refid;
        $tmpUser->fetch();
        $this->user_resp = $tmpUser;
        $this->statut = $obj->statut;
        $this->dateDeSortieDefinitive = $obj->dateDeSortieDefinitive;
        $this->PrixCessionALaSortie = $obj->PrixCessionALaSortie;
        $this->entrepot_refid = $obj->entrepot_refid;

    }

    function getNomUrl($withpicto=0,$option='',$maxlength=0)
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/Babel_GA/fiche-location.php?id='.$this->id.'">';
        $lienfin='</a>';
        $newref=$this->ref;
        if ($maxlength) $newref=dol_trunc($newref,$maxlength);

        if ($withpicto) {
            $result.=($lien.img_object($this->ref,'location').$lienfin.' ');
        }
        $result.=$lien.$newref.$lienfin;
        return $result;
    }

    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    function LibStatut($status,$mode=0)
    {
        global $langs;
        $langs->load('location');
        if ($mode == 0)
        {
            if ($status == 0) return $langs->trans('ProductInGAStockShort');
            if ($status == 1) return $langs->trans('ProductInLocationShort');
            if ($status == 2) return $langs->trans('ProductReSellL');
        }
        if ($mode == 1)
        {
            if ($status == 0) return $langs->trans('ProductInGAStock');
            if ($status == 1) return $langs->trans('ProductInLocation');
            if ($status == 2) return $langs->trans('ProductReSellL');
        }
        if ($mode == 2)
        {
            if ($status == 0) return img_picto($langs->trans('ProductInGAStock'),'statut4').' '.$langs->trans('ProductInGAStock');
            if ($status == 1) return img_picto($langs->trans('ProductInLocation'),'statut1').' '.$langs->trans('ProductInLocation');
            if ($status == 2) return img_picto($langs->trans('ProductReSellL'),'statut3').' '.$langs->trans('ProductReSellL');
        }
        if ($mode == 3)
        {
            if ($status == 0) return img_picto($langs->trans('ProductInGAStock'),'statut4');
            if ($status == 1) return img_picto($langs->trans('ProductInLocation'),'statut3');
            if ($status == 2) return img_picto($langs->trans('ProductReSellL'),'statut5');
        }
        if ($mode == 4)
        {
            if ($status == 0) return img_picto($langs->trans('ProductInGAStock'),'statut4').' '.$langs->trans('ProductInGAStock');
            if ($status == 1) return img_picto($langs->trans('ProductInLocation'),'statut3').' '.$langs->trans('ProductInLocation');
            if ($status == 2) return img_picto($langs->trans('ProductReSellL'),'statut5').' '.$langs->trans('ProductReSellL');
        }
        if ($mode == 5)
        {
            if ($status == 0) return $langs->trans('ProductInGAStock').' '.img_picto($langs->trans('ProductInGAStock'),'statut4');
            if ($status == 1) return $langs->trans('ProductInLocation').' '.img_picto($langs->trans('ProductInLocation'),'statut1');
            if ($status == 2) return $langs->trans('ProductReSellL').' '.img_picto($langs->trans('ProductReSellL'),'statut3');
        }
        return $langs->trans('Unknown');
    }
    public function getAddress($idLigne=false)
    {
        if (!$idLigne)
        {
            $idLigne=$this->id;
        }
        if ($this->statut == 0)
        {
            //recupere l'adresse du stock
            $requete = "SELECT concat(".MAIN_DB_PREFIX."entrepot.address,' ',".MAIN_DB_PREFIX."entrepot.ville) as addr, ".MAIN_DB_PREFIX."entrepot.rowid as eid
                          FROM ".MAIN_DB_PREFIX."entrepot, Babel_GA_entrepot_location, Babel_GA_entrepotdet
                         WHERE ".MAIN_DB_PREFIX."entrepot.rowid = Babel_GA_entrepot_location.entrepot_location_refid
                           AND Babel_GA_entrepotdet.GA_entrepot_refid = Babel_GA_entrepot_location.id
                           AND Babel_GA_entrepotdet.id =  ".$idLigne;
            $sql = $this->db->query($requete);
            $res=$this->db->fetch_object($sql);
            require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
            $ent = new Entrepot($this->db);
            $ent->fetch($res->eid);
            return ($ent->getNomUrl(1)." - ".$res->addr);
        } else if ($this->statut == 1)
        {
            //Addresse societe
            $requete = "SELECT concat(".MAIN_DB_PREFIX."societe.address,' ',".MAIN_DB_PREFIX."societe.ville) as addr
                          FROM ".MAIN_DB_PREFIX."societe, Babel_GA_entrepotdet
                         WHERE ".MAIN_DB_PREFIX."societe.client_refid = ".MAIN_DB_PREFIX."societe.refid
                           AND Babel_GA_entrepotdet.id =  ".$idLigne;
            $sql = $this->db->query($requete);
            $res=$this->db->fetch_object($sql);
            if ($res->addr."x"=="x")
            {
                return ("Aucune adresse cliente");
            } else {
                return ($res->addr);
            }

        } else {
            return ('-');
        }
    }

}
?>