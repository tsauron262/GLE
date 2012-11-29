<?php
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

class Affaire extends CommonObject {
    public $db;
    public $id;
    public $nom;
    public $description;
    public $date_creation;
    public $tms;
    public $element;
    public $fk_user_author;
    public $user_author;
    private $labelstatut = array();
    private $labelstatut_short = array();
    public $date;
    public $extra;

    public function Affaire($DB) {
        $this->db=$DB;
        global $langs;
        $langs->load("affaire");
        $this->labelstatut[0]=$langs->trans("AffaireStatusDraft");
        $this->labelstatut[1]=$langs->trans("AffaireStatusValidated");
        $this->labelstatut[2]=$langs->trans("AffaireStatusSigned");
        $this->labelstatut[3]=$langs->trans("AffaireStatusNotSigned");
        $this->labelstatut[4]=$langs->trans("AffaireStatusBilled");
        $this->labelstatut[5]=$langs->trans("AffaireStatusWaitingValidation");
        $this->labelstatut_short[0]=$langs->trans("AffaireStatusDraftShort");
        $this->labelstatut_short[1]=$langs->trans("Opened");
        $this->labelstatut_short[2]=$langs->trans("AffaireStatusSignedShort");
        $this->labelstatut_short[3]=$langs->trans("AffaireStatusNotSignedShort");
        $this->labelstatut_short[4]=$langs->trans("AffaireStatusBilledShort");
        $this->labelstatut_short[5]=$langs->trans("AffaireStatusWaitingValidationShort");
        $this->element="affaire";

    }
    public function create(){
        global $user;
        $requete = "INSERT INTO `Babel_Affaire`
                                (`nom`,`description`,`date_creation`,`fk_user_create`,`statut`,`ref`)
                         VALUES
                                ('".$this->nom."', '".$this->description."', now(),  ".$user->id.", 0, '".$this->ref."') ";
        $sql = $this->db->query($requete);
        if ($sql)
        {
            $lastId = $this->db->last_insert_id('Babel_Affaire');
            return($lastId);
        } else {
            return -1;
        }

    }
    public $extraArr = array();
    public $extraArrByName = array();
    public function fetch_extra()
    {
        if ($this->id > 0)
        {
            $requete = "SELECT * FROM babel_affaire_template_value_view WHERE affaire_id =".$this->id;
            $sql = $this->db->query($requete);
            while($res = $this->db->fetch_object($sql))
            {
                $this->extraArr[$res->id]=array("nom" => $res->nom,
                                                "description" => $res->description,
                                                "type_affaire" => $res->type_affaire,
                                                "value_affaire" => $res->value_affaire,
                                                "value" => $res->value_affaire,
                                                "template_key_id" => $res->template_key_id,
                                                "affaire_id" => $res->affaire_id,
                                          );
                $this->extraArrByName[$res->nom]=array("id" => $res->id,
                                                "description" => $res->description,
                                                "type_affaire" => $res->type_affaire,
                                                "value_affaire" => $res->value_affaire,
                                                "value" => $res->value_affaire,
                                                "template_key_id" => $res->template_key_id,
                                                "affaire_id" => $res->affaire_id,
                                          );
            }
            return ($this->extraArr);
        }
    }

    public function fetch($pId){
        $this->id = $pId;
        $requete = "SELECT UNIX_TIMESTAMP(date_creation) as date_creation,
                           nom,
                           description,
                           tms,
                           ref,
                           fk_user_create,
                           modelContactPDF_refid,
                           modelPNPDF_refid,
                           statut
                      FROM Babel_Affaire
                     WHERE id = ".$this->id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            $tmpUser = new User($this->db);
            $res = $this->db->fetch_object($sql);
            $this->nom = $res->nom;
            $this->description = $res->description;
            $this->date_creation = $res->date_creation;
            $this->date = $res->date_creation;
            $this->tms = $res->tms;
            $this->ref = $res->ref;
            $this->fk_user_author = $res->fk_user_create;
            $this->user_author_id = $res->fk_user_create;
            $this->modelContactPDF_refid = $res->modelContactPDF_refid;
            $this->modelPNPDF_refid = $res->modelPNPDF_refid;
            $tmpUser->id = $res->fk_user_create;
            $tmpUser->fetch();
            $this->user_author = $tmpUser;
            $this->statut = $res->statut;
        } else {
            return ($this->db->lasterrorno);
        }
        return($this->id);
    }
    public function listExtraKey($by_name=false){
        $requete= "SELECT * FROM babel_affaire_template_value_view WHERE affaire_id = ".$this->id;
        if ($by_name) $requete .= " ORDER BY nom";
        $sql = $this->db->query($requete);
        $this->extra=array();
        while($res = $this->db->fetch_object($sql))
        {
            if ($by_name)
            {
                $this->extra[$res->nom]=$res;
            }
            $this->extra[$res->id]=$res;
        }
    }
    public function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
    *        \brief      Renvoi le libelle d'un statut donne
    *        \param      statut        id statut
    *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
    *        \return     string        Libelle
    */
    private function LibStatut($statut=0,$mode=1)
    {
        global $langs;
        $langs->load("affaire");
        if ($mode == 0)
        {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1)
        {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraftShort'),'statut0').' '.$this->labelstatut_short[$statut];
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpenedShort'),'statut1').' '.$this->labelstatut_short[$statut];
            if ($statut==2) return img_picto($langs->trans('AffaireStatusSignedShort'),'statut3').' '.$this->labelstatut_short[$statut];
            if ($statut==3) return img_picto($langs->trans('AffaireStatusNotSignedShort'),'statut5').' '.$this->labelstatut_short[$statut];
            if ($statut==4) return img_picto($langs->trans('AffaireStatusBilledShort'),'statut6').' '.$this->labelstatut_short[$statut];
            if ($statut==5) return img_picto($langs->trans('AffaireStatusWaitingValid'),'statut8','style="vertical-align:middle;"').' '.$this->labelstatut_short[$statut];
        }
        if ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraftShort'),'statut0');
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpenedShort'),'statut1');
            if ($statut==2) return img_picto($langs->trans('AffaireStatusSignedShort'),'statut3');
            if ($statut==3) return img_picto($langs->trans('AffaireStatusNotSignedShort'),'statut5');
            if ($statut==4) return img_picto($langs->trans('AffaireStatusBilledShort'),'statut6');
            if ($statut==5) return img_picto($langs->trans('AffaireStatusWaitingValid'),'statut8','style="vertical-align:middle;"');
        }
        if ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraft'),'statut0').' '.$this->labelstatut[$statut];
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpened'),'statut1').' '.$this->labelstatut[$statut];
            if ($statut==2) return img_picto($langs->trans('AffaireStatusSigned'),'statut3').' '.$this->labelstatut[$statut];
            if ($statut==3) return img_picto($langs->trans('AffaireStatusNotSigned'),'statut5').' '.$this->labelstatut[$statut];
            if ($statut==4) return img_picto($langs->trans('AffaireStatusBilled'),'statut6').' '.$this->labelstatut[$statut];
            if ($statut==5) return img_picto($langs->trans('AffaireStatusWaitingValid'),'statut8','style="vertical-align:middle;"').' '.$this->labelstatut[$statut];
        }
        if ($mode == 5)
        {
            if ($statut==0) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusDraftShort'),'statut0');
            if ($statut==1) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusOpenedShort'),'statut1');
            if ($statut==2) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusSignedShort'),'statut3');
            if ($statut==3) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusNotSignedShort'),'statut5');
            if ($statut==4) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusBilledShort'),'statut6');
            if ($statut==5) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusWaitingValid'),'statut8','style="vertical-align:middle;"');
        }
    }

    public function get_element_list($type){
        $array=array();
        $requete = "SELECT element_id FROM Babel_Affaire_Element WHERE type='".$type."'";
        $sql = $this->db->query($requete);
        if ($sql){
            while($res=$this->db->fetch_object($sql))
            {
                $array[]=$res->element_id;
            }
        }
        return ($array);
    }

    public function getNomUrl($withpicto=0,$option='', $get_params='')
    {
        global $langs;

        $result='';
        $lien = '<a href="'.DOL_URL_ROOT.'/Babel_Affaire/fiche.php?id='.$this->id. $get_params .'">';
        $lienfin='</a>';

        $picto='affaire';
        $label=$langs->trans("ShowAffaire").': '.$this->nom;

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.$this->nom.$lienfin;
        return $result;
    }

    public function verifyNumRef()
    {
        $sql = "SELECT rowid FROM Babel_Affaire";
        $sql.= " WHERE ref = '".$this->ref."'";

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num > 0)
            {
                $this->ref = $this->getNextNumRef();
            }
        }
    }


 /**
   *      \brief           Renvoie la reference de propale suivante non utilisee
   * en fonction du module                  de numerotation actif defini dans
   * PROPALE_ADDON      \param        soc                      objet societe
   * \return     string              reference libre pour la propale
   */
    function getNextNumRef()
    {
        global $db, $langs,$mysoc;
        $langs->load("Affaire");

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/Affaire/";

        if (defined("AFFAIRE_ADDON") && AFFAIRE_ADDON)
        {
            $file = AFFAIRE_ADDON.".php";

            //  Chargement de la classe de numerotation
            $classname = AFFAIRE_ADDON;
            require_once($dir.$file);

            $obj = new $classname();

            $numref = "";
            $numref = $obj->getNextValue($mysoc,$this);

            if ( $numref != "")
            {
                return $numref;
            } else {
                dol_print_error($db,"Affaire::getNextNumRef ".$obj->error);
                return "";
            }
        } else {
            print $langs->trans("Error")." ".$langs->trans("Error_AFFAIRE_ADDON_NotDefined");
            return "";
        }
    }
    public function isMemberOfGroup($id,$type)
    {
        $arr=array();
        $requete = "SELECT * FROM Babel_Affaire_Element WHERE element_id=".$id." AND type LIKE '".$type."'";
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)){
            $arr[]=$res->affaire_refid;
        }
        return($arr);
    }
}
?>