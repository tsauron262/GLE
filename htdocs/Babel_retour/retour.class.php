<?php
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

class retour extends CommonObject{

    public $db;
    public $fk_soc;
    public $element_id;
    public $element_type;
    public $error;
    public $id;
    public $tms;
    public $date_creation;
    public $user_author;
    public $user_author_refid;
    public $soc;
    public $user_resp_refid;
    public $user_resp;
    public $date_retour;
    public $extraSerialInfo=array();
    public $ProdList=array();



    public function retour($DB) {
        $this->db = $DB;
    }
    public function create(){
        global $user;
        $requete = "INSERT INTO Babel_retour
                                (user_author_refid,societe_refid, element_refid, element_type,date_create )
                         VALUES (".$user->id.",".$this->fk_soc.",".$this->element_id.",'".$this->element_type."',now())";

                         print $requete;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            //Appel trigger
            return ($this->db->last_insert_id('Babel_retour'));
        } else {
            $this->error = "Erreur de cr&eacute;ation : ".$this->db->lasterrno." ".$this->db->lasterror. "<br/>".$this->db->lastqueryerror;
            return(false);
        }
    }
    public function fetch($id){
        $this->id = $id;
        $requete = "SELECT UNIX_TIMESTAMP(date_retour) as date_retourU,
                           UNIX_TIMESTAMP(date_create) as date_createU,
                           user_author_refid,
                           societe_refid,
                           element_refid,
                           element_type,
                           user_resp_refid,
                           cause,
                           tms
                      FROM Babel_retour
                     WHERE id=".$id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            if ($this->db->num_rows($sql) > 0)
            {
                $res=$this->db->fetch_object($sql);
                $this->element_id = $res->element_refid;
                $this->element_type = $res->element_type;
                $this->fk_soc = $res->societe_refid;
                $this->cause =$res->cause;
                $tmpSoc = new Societe($this->db);
                $tmpSoc->fetch($res->societe_refid);
                $this->soc =$tmpSoc;
                $this->user_author_refid = $res->user_author_refid;
                $tmpUser = new User($this->db);
                $tmpUser->id = $res->user_author_refid;
                $tmpUser->fetch();
                $this->user_author = $tmpUser;

                $this->user_resp_refid = $res->user_resp_refid;
                $tmpUser = new User($this->db);
                $tmpUser->id = $res->user_resp_refid;
                $tmpUser->fetch();
                $this->user_resp = $tmpUser;

                $this->date_creation = $res->date_createU;
                $this->date_retour = $res->date_retourU;
                $this->tms = $res->tms;
                return(true);
            } else {
                $this->error = "Erreur de lecture : ".$this->db->lasterrno." ".$this->db->lasterror. "<br/>".$this->db->lastqueryerror;
                return(false);
            }

        } else {
            $this->error = "Erreur de lecture : ".$this->db->lasterrno." ".$this->db->lasterror. "<br/>".$this->db->lastqueryerror;
            return(false);
        }
    }
    public function update(){
        $requete = "UPDATE Babel_retour
                       SET societe_refid = ".$this->fk_soc . ",
                           element_refid = " . $this->element_id.",
                           element_type = '".$this->element_type."',
                           user_author_refid = ".$this->user_author_id.",
                           user_resp_refid = ".$this->user_resp_id.",
                           cause = '".$this->cause."',
                           date_retour = ".$this->date_retour."
                     WHERE id = ".$this->id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            //Trigger
            $this->error = "Erreur de lecture : ".$this->db->lasterrno." ".$this->db->lasterror. "<br/>".$this->db->lastqueryerror;
            return(true);
        } else {
            return(false);
        }
    }
    public function validate($id)
    {
        $this->id=$id;
        $requete = "UPDATE Babel_retour SET fk_statut = 3 WHERE id =".$id;
        $sql = $this->db->query($requete);
        if ($sql)
        {
            //Trigger
            $this->error = "Erreur de validation : ".$this->db->lasterrno." ".$this->db->lasterror. "<br/>".$this->db->lastqueryerror;
            return(true);
        } else {
            return(false);
        }
    }

    public function validateProdList($retourId,$typeRef)
    {
        if (count($this->ProdList)>1)
        {
            if (($this->cause) == "SAV")
            {
                //$retour->ProdList[$arrTmp[1]]=$val;
                foreach($this->ProdList as $key=>$val)
                {
                    if ($val!= -1)
                    {
                        $clause = "";
                        $serial = "";
                        $requete = "SELECT * FROM llx_product_serial_view WHERE element_type like '".$typeRef."%' AND element_id =".$key. " ORDER BY tms DESC";
                        $sql = $this->db->query($requete);
                        $res = $this->db->fetch_object($sql);
                        $serial = $res->serial_number;
                        $requete = "DELETE FROM Babel_retourdet WHERE retour_refid = " . $this->id;
                        $sql = $this->db->query($requete);
                        $requete = "INSERT INTO Babel_retourdet (element_type,element_id,serial,retour_refid, fk_statut, clause)
                                         VALUES ('".$typeRef."',".$key.",'".$serial."',".$this->id.",1,'".$clause."')";
                        $this->db->query($requete);
                    }
                }
            } else {
                //$retour->ProdList[$arrTmp[1]]=$val;
            }

        }
        return $retourId;
    }

}
class retourLignes {
    public $db;
    public $id;
    public function retourLignes($db)
    {
        $this->db=$db;
    }
    public function fetch($id){
        $this->id = $id;
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."";
    }
}
?>