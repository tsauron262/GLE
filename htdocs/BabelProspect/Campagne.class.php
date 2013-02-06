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
class Campagne {
    public $db;
    public $dateDebut="";
    public $dateFin ="";
    public  $comm=array(); //array
    public  $Responsable=array();//array
    public  $nom ='';
    public  $notePublic = '';
    public $id;
    public  $dateDebutday = "";
    public  $dateDebutmonth = "";
    public  $dateDebutyear =  "";
    public  $dateDebuthour =  "";
    public  $dateDebutmin =  "";

    public  $dateFinday =  "";
    public  $dateFinmonth =  "";
    public  $dateFinyear =  "";
    public  $dateFinhour =  "";
    public  $dateFinmin =  "";

    public $statutLibelle="";
    public $statutLabel="";

    public $campagneArray = array();

    public $labelstatut_short = array(1 => 'CampagneStatusDraftShort',
                                      2 => 'CampagneStatusOpenedShort',
                                      3 => 'CampagneStatusSignedShort',
                                      4 => 'CampagneStatusNotSignedShort',
                                      5 => 'CampagneStatusBilledShort');

    public function Campagne($db)
    {
        $this->db=$db;
    }
    public function create()
    {
        global $user;
        global $langs;
        global $conf;
        $dateDebut = $this->dateDebutyear ."-".$this->dateDebutmonth."-"."-".$this->dateDebutday." ".$this->dateDebuthour.":".$this->dateDebutmin;
        $dateFin = $this->dateFinyear ."-".$this->dateFinmonth."-"."-".$this->dateFinday." ".$this->dateFinhour.":".$this->dateFinmin;
        $requete = "INSERT INTO Babel_campagne
                        (`dateDebut`, `dateFin`, `nom`, `note_public`,`fk_user_create`,`datec`)
                         VALUES
                        ('".$dateDebut."','".$dateFin."','".$this->nom."','".$this->notePublic."',".$user->id.",now())
                    ";
        $resql = $this->db->query($requete);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id("Babel_campagne");
            //ajoute dans la table Babel_campagne_people
            $id = $this->id;
            foreach($this->Responsable as $key=>$val)
            {
                $requete = "INSERT INTO Babel_campagne_people
                                        (user_refid,campagne_refid,isResponsable )
                                 VALUES (".$val.",".$id.",1)";
                $this->db->query($requete);
            }
            foreach($this->comm as $key=>$val)
            {
                $requete = "INSERT INTO Babel_campagne_people
                                        (user_refid,campagne_refid )
                                 VALUES (".$val.",".$id.")";
                $this->db->query($requete);
            }


            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CAMPAGNEPROSPECT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }

    }
    public function delete($id)
    {
        $this->db->begin();
        $requete = "DELETE FROM Babel_campagne WHERE id =".$id;
        $res = $this->db->query($requete);
        $requete = "DElETE FROM Babel_campagne_people WHERE campagne_refid = ".$id;
        $res1 = $this->db->query($requete);
        if ($res && $res1)
        {
            $this->db->commit();
        } else {
            $this->db->rollback();
        }
    }
    public function fetch($id="")
    {
          $requete = "SELECT Babel_campagne.dateDebut,
                             year(Babel_campagne.dateDebut) as  dateDebutyear,
                             month(Babel_campagne.dateDebut) as  dateDebutmonth,
                             day(Babel_campagne.dateDebut) as  dateDebutday,
                             minute(Babel_campagne.dateDebut) as  dateDebutmin,
                             hour(Babel_campagne.dateDebut) as  dateDebuthour,
                             year(Babel_campagne.dateFin) as  dateFinyear,
                             month(Babel_campagne.dateFin) as  dateFinmonth,
                             day(Babel_campagne.dateFin) as  dateFinday,
                             hour(Babel_campagne.dateFin) as  dateFinhour,
                             minute(Babel_campagne.dateFin) as  dateFinmin,
                             Babel_campagne.dateFin,
                             year(Babel_campagne.date_valid) as  date_validyear,
                             month(Babel_campagne.date_valid) as  date_validmonth,
                             day(Babel_campagne.date_valid) as  date_validday,
                             hour(Babel_campagne.date_valid) as  date_validhour,
                             minute(Babel_campagne.date_valid) as  date_validmin,
                             Babel_campagne.date_valid,
                             year(Babel_campagne.dateDebutEffective) as  dateDebutEffectiveyear,
                             month(Babel_campagne.dateDebutEffective) as  dateDebutEffectivemonth,
                             day(Babel_campagne.dateDebutEffective) as  dateDebutEffectiveday,
                             hour(Babel_campagne.dateDebutEffective) as  dateDebutEffectivehour,
                             minute(Babel_campagne.dateDebutEffective) as  dateDebutEffectivemin,
                             Babel_campagne.dateDebutEffective,
                             year(Babel_campagne.dateFinEffective) as  dateFinEffectiveyear,
                             month(Babel_campagne.dateFinEffective) as  dateFinEffectivemonth,
                             day(Babel_campagne.dateFinEffective) as  dateFinEffectiveday,
                             hour(Babel_campagne.dateFinEffective) as  dateFinEffectivehour,
                             minute(Babel_campagne.dateFinEffective) as  dateFinEffectivemin,
                             Babel_campagne.dateFinEffective,
                             Babel_campagne.datec,
                             Babel_campagne.datem,
                             Babel_campagne.nom,
                             Babel_campagne.id as bcid,
                             Babel_campagne.fk_user_create,
                             Babel_campagne.fk_statut,
                             Babel_campagne_c_statut.libelle,
                             Babel_campagne_c_statut.label,
                             Babel_campagne.fk_user_modif,
                             Babel_campagne.note_public as notePublic
                        FROM Babel_campagne
                   LEFT JOIN Babel_campagne_c_statut on Babel_campagne_c_statut.id = Babel_campagne.fk_statut
                    ";
          if ($id . "x" != 'x')
          {
            $requete .=  "WHERE Babel_campagne.id =".$id;
            $this->id = $id;
          }
//                print $requete;

          if ($resql = $this->db->query($requete))
          {
            while ($res=$this->db->fetch_object($resql))
            {
                $this->id = $res->bcid;
                $this->dateDebut = $res->dateDebut;
                $this->dateFin = $res->dateFin;
                $this->nom = $res->nom;
                $this->ref = $res->nom;
                //var_dump($this->nom);
                $this->notePublic = $res->notePublic;

                $this->datec = $res->datec;
                $this->datem = $res->datem;


                $this->dateDebutday = $res->dateDebutday;
                $this->dateDebutmonth = $res->dateDebutmonth;
                $this->dateDebutyear = $res->dateDebutyear;
                $this->dateDebuthour = $res->dateDebuthour;
                $this->dateDebutmin = $res->dateDebutmin;

                $this->dateFinday = $res->dateFinday;
                $this->dateFinmonth = $res->dateFinmonth;
                $this->dateFinyear = $res->dateFinyear;
                $this->dateFinhour = $res->dateFinhour;
                $this->dateFinmin = $res->dateFinmin;

                $this->date_validday = $res->date_validday;
                $this->date_validmonth = $res->date_validmonth;
                $this->date_validyear = $res->date_validyear;
                $this->date_validhour = $res->date_validhour;
                $this->date_validmin = $res->date_validmin;

                $this->dateDebutEffective = $res->dateDebutEffective;
                $this->dateDebutEffectiveday = $res->dateDebutEffectiveday;
                $this->dateDebutEffectivemonth = $res->dateDebutEffectivemonth;
                $this->dateDebutEffectiveyear = $res->dateDebutEffectiveyear;
                $this->dateDebutEffectivehour = $res->dateDebutEffectivehour;
                $this->dateDebutEffectivemin = $res->dateDebutEffectivemin;

                $this->dateFinEffective = $res->dateFinEffective;
                $this->dateFinEffectiveday = $res->dateFinEffectiveday;
                $this->dateFinEffectivemonth = $res->dateFinEffectivemonth;
                $this->dateFinEffectiveyear = $res->dateFinEffectiveyear;
                $this->dateFinEffectivehour = $res->dateFinEffectivehour;
                $this->dateFinEffectivemin = $res->dateFinEffectivemin;

                $this->statutLibelle = $res->libelle;
                $this->statutLabel = $res->label;
                $this->statut = $res->fk_statut;
                $this->fk_user_create=$res->fk_user_create;
                $this->user_author_id=$res->fk_user_create;

                $this->fk_user_modif=$res->fk_user_modif;
                $this->comm = array();
                $this->Responsable = array();


                $requeteResp = "SELECT Babel_campagne_people.isResponsable, user_refid
                                  FROM Babel_campagne_people
                                 WHERE campagne_refid = ".$this->id;
                if ($resqlResp = $this->db->query($requeteResp))
                {
                    while ($resResp = $this->db->fetch_object($resqlResp))
                    {
                        if ($resResp->isResponsable == 1)
                        {
                            array_push($this->Responsable,$resResp->user_refid);
                        } else {
                            array_push($this->comm,$resResp->user_refid);
                        }
                    }
                }
                if ("x".$id == "x")
                {
                    //print "toto".$requete ."<BR>";
//                    print "r".$res->nom."<BR>";
                    array_push($this->campagneArray ,array(
                                                        'dateDebut' => $res->dateDebut,
                                                        'dateFin' => $res->dateFin,
                                                        'nom' => $res->nom,
                                                        'notePublic' => $res->desc,
                                                        'id' => $res->bcid,

                                                        'dateDebutday' => $res->dateDebutday,
                                                        'dateDebutmonth' => $res->dateDebutmonth,
                                                        'dateDebutyear' => $res->dateDebutyear,
                                                        'dateDebuthour' => $res->dateDebuthour,
                                                        'dateDebutmin' => $res->dateDebutmin,

                                                        'dateFinday' => $res->dateFinday,
                                                        'dateFinmonth' => $res->dateFinmonth,
                                                        'dateFinyear' => $res->dateFinyear,
                                                        'dateFinhour' => $res->dateFinhour,
                                                        'dateFinmin' => $res->dateFinmin,

                                                        'date_validday' => $res->date_validday,
                                                        'date_validmonth' => $res->date_validmonth,
                                                        'date_validyear' => $res->date_validyear,
                                                        'date_validhour' => $res->date_validhour,
                                                        'date_validmin' => $res->date_validmin,
                                                        'statut' => $res->fk_statut,
                                                        'statutLibelle' => $res->statutLibelle,
                                                        'statutLabel' => $res->statutLabel,

                                                        'dateDebutEffective' => $res->dateDebutEffective,
                                                        'dateDebutEffectiveday' => $res->dateDebutEffectiveday,
                                                        'dateDebutEffectivemonth' => $res->dateDebutEffectivemonth,
                                                        'dateDebutEffectiveyear' => $res->dateDebutEffectiveyear,
                                                        'dateDebutEffectivehour' => $res->dateDebutEffectivehour,
                                                        'dateDebutEffectivemin' => $res->dateDebutEffectivemin,

                                                        'dateFinEffective' => $res->dateFinEffective,
                                                        'dateFinEffectiveday' => $res->dateFinEffectiveday,
                                                        'dateFinEffectivemonth' => $res->dateFinEffectivemonth,
                                                        'dateFinEffectiveyear' => $res->dateFinEffectiveyear,
                                                        'dateFinEffectivehour' => $res->dateFinEffectivehour,
                                                        'dateFinEffectivemin' => $res->dateFinEffectivemin,

                                                        'fk_user_create' => $res->fk_user_create,
                                                        'fk_user_modif' => $res->fk_user_modif,
                                                        'com' => $this->comm,
                                                        'Responsable' => $this->Responsable
                    ));
                }
             }
             return ($this->id);
          } else {
            if($this->id > 0)
            {
                return $this->id;
            } else {
                return (-1);
            }
          }
    }
    public $statCampagne = array();
    public function stats()
    {
        //compbien de societe
        //rythme de la campagne
        $requete = "SELECT count(*) as cnt
                      FROM Babel_campagne_societe
                     WHERE campagne_refid = ".$this->id;
        if ($resql = $this->db->query($requete))
        {
            $this->statCampagne['qty'] = $this->db->fetch_object($resql)->cnt;
        }
        $requete = "SELECT count(*) as cnt
                      FROM Babel_campagne_societe
                     WHERE fk_statut <> 1 AND fk_statut <> 4
                      AND  campagne_refid = ".$this->id."
                 GROUP BY day(date_prisecharge),month(date_prisecharge),year(date_prisecharge)";
         $a = array();
         if ($resql = $this->db->query($requete))
         {
            while($resTmp = $this->db->fetch_object($resql))
            {
                array_push($a,($resTmp->cnt?$resTmp->cnt:0));
            }
         }
            $result=0;
            if (count($a)>0)
            {
                $result =  array_sum($a)/count($a);
            }
            $this->statCampagne['avg_day'] = round($result,2);
            $this->statCampagne['avancement'] = preg_replace('/,/','.',round(100 * array_sum($a) / $this->statCampagne['qty'],2));


    }
    public function lancer($id)
    {
        $requete = "UPDATE Babel_campagne SET dateDebutEffective=now(), fk_statut=3 WHERE id = ".$id;
        if ($resql = $this->db->query($requete))
        {
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CAMPAGNEPROSPECT_LANCER',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }

            return 1;
        } else {
            reutrn -1;
        }
    }
    public function cloturer($id)
    {
        $requete = "UPDATE Babel_campagne SET dateFinEffective=now(), fk_statut=5 WHERE id = ".$id;
        if ($resql = $this->db->query($requete))
        {
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CAMPAGNEPROSPECT_CLOTURE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }

            return 1;
        } else {
            reutrn -1;
        }
    }

    public function update($id)
    {
        global $user,$langs,$conf;
        $dateDebut = false;
        if ($this->dateDebut)
        {
            $dateDebut = $this->detaDebut;
        } else if ($this->dateDebutday && $this->dateDebutmonth && $this->dateDebutyear)
        {
            $dateDebut = $this->dateDebutyear . "-".$this->dateDebutmonth."-".$this->dateDebutday;
            $heureDebut = ($this->dateDebuthour?$this->dateDebuthour:00);
            $minDebut = ($this->dateDebutmin?$this->dateDebutmin:00);
            $dateDebut .= " ".$heureDebut.":".$minDebut;
        }
        $dateFin= false;
        if ($this->dateFin)
        {
            $dateFin = $this->detaFin;
        } else if ($this->dateFinday && $this->dateFinmonth && $this->dateFinyear)
        {
            $dateFin = $this->dateFinyear . "-".$this->dateFinmonth."-".$this->dateFinday;
            $heureFin = ($this->dateFinhour?$this->dateFinhour:00);
            $minFin = ($this->dateFinmin?$this->dateFinmin:00);
            $dateFin .= " ".$heureFin.":".$minFin;
        }
        $date_valid= false;
        if ($this->date_valid)
        {
            $date_valid = $this->detaFin;
        } else if ($this->date_validday && $this->date_validmonth && $this->date_validyear)
        {
            $date_valid = $this->date_validyear . "-".$this->date_validmonth."-".$this->date_validday;
            $heureFin = ($this->date_validhour?$this->date_validhour:00);
            $minFin = ($this->date_validmin?$this->date_validmin:00);
            $date_valid .= " ".$heureFin.":".$minFin;
        }
        $dateDebutEffective=false;
        if ($this->dateDebutEffective)
        {
            $dateDebutEffective = $this->dateDebutEffective;
        } else if ($this->dateDebutEffectiveday && $this->dateDebutEffectivemonth && $this->dateDebutEffectiveyear)
        {
            $dateDebutEffective = $this->dateDebutEffectiveyear . "-".$this->dateDebutEffectivemonth."-".$this->dateDebutEffectiveday;
            $heureFin = ($this->dateDebutEffectivehour?$this->dateDebutEffectivehour:00);
            $minFin = ($this->dateDebutEffectivemin?$this->dateDebutEffectivemin:00);
            $dateDebutEffective .= " ".$heureFin.":".$minFin;
        }
        $dateFinEffective=false;
        if ($this->dateFinEffective)
        {
            $dateFinEffective = $this->detaFin;
        } else if ($this->dateFinEffectiveday && $this->dateFinEffectivemonth && $this->dateFinEffectiveyear)
        {
            $dateFinEffective = $this->dateFinEffectiveyear . "-".$this->dateFinEffectivemonth."-".$this->dateFinEffectiveday;
            $heureFin = ($this->dateFinEffectivehour?$this->dateFinEffectivehour:00);
            $minFin = ($this->dateFinEffectivemin?$this->dateFinEffectivemin:00);
            $dateFinEffective .= " ".$heureFin.":".$minFin;
        }

        $nom = $this->nom;
        $desc = $this->notePublic;

        $statut = $this->statut;
        $user_modif = $this->fk_user_modif;

        $requete = "UPDATE Babel_campagne";
        if ($dateDebut){
            $requete .= "               SET Babel_campagne.dateDebut='',";
        }
        if ($dateFin){
            $requete .= "               SET Babel_campagne.dateFin='',";
        }
        if ($dateDebutEffective) {
            $requete .= "               SET Babel_campagne.dateDebutEffective='',";
        }
        if ($dateFinEffective) {
            $requete .= "               SET Babel_campagne.dateFinEffective='',";
        }
        if ($dateFinEffective) {
            $requete .= "               SET Babel_campagne.date_valid='',";
        }
        if ($nom) {
            $requete .= "               SET Babel_campagne.nom='',";
        }
        if ($statut){
            $requete .= "               SET Babel_campagne.fk_statut='',";
        }
        if ($desc){
            $requete .= "               SET Babel_campagne.note_public='',";
        }
        $requete .= "               SET Babel_campagne.fk_user_modif='',";
        $requete .= "               SET Babel_campagne.date_m='',";
        $requete .=  "WHERE id =".$id;
        $this->db->begin();
        if ($resql = $this->db->query($requete))
        {
            $this->id = $id;
            //efface dans la base
            //ajoute dans la table Babel_campagne_people
            $id = $this->id;
            $requete1 = "DELETE FROM Babel_campagne_people WHERE campagne_id = ".$id;
            $res=$this->db->query($requete1);
            $res1=false;
            foreach($this->Responsable as $key=>$val)
            {
                $requete = "INSERT INTO Babel_campagne_people
                                        (user_refid,campagne_refid,isResponsable )
                                 VALUES (".$val.",".$id.",1)";
                $res1=$this->db->query($requete);
            }
            foreach($this->comm as $key=>$val)
            {
                $requete = "INSERT INTO Babel_campagne_people
                                        (user_refid,campagne_refid )
                                 VALUES (".$val.",".$id.")";
                $res2=$this->db->query($requete);
            }

            if ($res1 && $res1)
            {
                $this->db->commit();
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('CAMPAGNEPROSPECT_UPDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
                return 1;
            } else {
                dol_print_error($this->db,$this->db->error);
                return -1;
            }
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }
    }

    function validate($id)
    {
        global $user;
        $requete = "UPDATE Babel_campagne
                       SET date_valid = now(),
                           fk_user_valid = ".$user->id . " ,
                           fk_statut = 2
                     WHERE id =  ". $id . "
                       AND fk_statut < 2 ";
        $res = $this->db->query($requete);
        if ($res)
        {

            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('CAMPAGNEPROSPECT_UPDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }

    }

    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }
    function LibStatut($statut,$mode=1)
    {
        global $langs;
        $langs->load("synopsisGene@Synopsis_Tools");

        if ($mode == 0)
        {
            return $langs->trans($this->labelstatut[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->labelstatut_short[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut==1) return img_picto($langs->trans('CampagneStatusDraftShort'),'statut0').' '.$langs->trans($this->labelstatut_short[$statut]);
            if ($statut==2) return img_picto($langs->trans('CampagneStatusOpenedShort'),'statut1').' '.$langs->trans($this->labelstatut_short[$statut]);
            if ($statut==3) return img_picto($langs->trans('CampagneStatusSignedShort'),'statut3').' '.$langs->trans($this->labelstatut_short[$statut]);
            if ($statut==4) return img_picto($langs->trans('CampagneStatusNotSignedShort'),'statut4').' '.$langs->trans($this->labelstatut_short[$statut]);
            if ($statut==5) return img_picto($langs->trans('CampagneStatusBilledShort'),'statut6').' '.$langs->trans($this->labelstatut_short[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut==1) return img_picto($langs->trans('CampagneStatusDraftShort'),'statut0');
            if ($statut==2) return img_picto($langs->trans('CampagneStatusOpenedShort'),'statut1');
            if ($statut==3) return img_picto($langs->trans('CampagneStatusSignedShort'),'statut3');
            if ($statut==4) return img_picto($langs->trans('CampagneStatusNotSignedShort'),'statut4');
            if ($statut==5) return img_picto($langs->trans('CampagneStatusBilledShort'),'statut6');
        }
        if ($mode == 4)
        {
            if ($statut==1) return img_picto($langs->trans('CampagneStatusDraft'),'statut0').' '.$langs->trans($this->labelstatut[$statut]);
            if ($statut==2) return img_picto($langs->trans('CampagneStatusOpened'),'statut1').' '.$langs->trans($this->labelstatut[$statut]);
            if ($statut==3) return img_picto($langs->trans('CampagneStatusSigned'),'statut3').' '.$langs->trans($this->labelstatut[$statut]);
            if ($statut==4) return img_picto($langs->trans('CampagneStatusNotSigned'),'statut4').' '.$langs->trans($this->labelstatut[$statut]);
            if ($statut==5) return img_picto($langs->trans('CampagneStatusBilled'),'statut6').' '.$langs->trans($this->labelstatut[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut==1) return $langs->trans($this->labelstatut_short[$statut]).' '.img_picto($langs->trans('CampagneStatusDraftShort'),'statut0');
            if ($statut==2) return $langs->trans($this->labelstatut_short[$statut]).' '.img_picto($langs->trans('CampagneStatusOpenedShort'),'statut1');
            if ($statut==3) return $langs->trans($this->labelstatut_short[$statut]).' '.img_picto($langs->trans('CampagneStatusSignedShort'),'statut3');
            if ($statut==4) return $langs->trans($this->labelstatut_short[$statut]).' '.img_picto($langs->trans('CampagneStatusNotSignedShort'),'statut4');
            if ($statut==5) return $langs->trans($this->labelstatut_short[$statut]).' '.img_picto($langs->trans('CampagneStatusBilledShort'),'statut6');
        }
        if ($mode == 6)
        {
            if ($statut==1) return DOL_URL_ROOT.'/theme/auguria/img/statut0.png__'.$langs->trans('CampagneStatusDraftShort');
            if ($statut==2) return DOL_URL_ROOT.'/theme/auguria/img/statut1.png__'.$langs->trans('CampagneStatusOpenedShort');
            if ($statut==3) return DOL_URL_ROOT.'/theme/auguria/img/statut3.png__'.$langs->trans('CampagneStatusSignedShort');
            if ($statut==4) return DOL_URL_ROOT.'/theme/auguria/img/statut4.png__'.$langs->trans('CampagneStatusNotSignedShort');
            if ($statut==5) return DOL_URL_ROOT.'/theme/auguria/img/statut6.png__'.$langs->trans('CampagneStatusBilledShort');
        }
    }

    function getNomUrl($withpicto=0,$maxlen=0)
    {
        global $langs;

        $result='';

            $lien = '<a href="'.DOL_URL_ROOT.'/BabelProspect/nouvelleProspection.php?action=config&id='.$this->id.'">';
            $lienfin='</a>';


        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowCampagne").': '.$this->nom,'PROSPECTIONBABEL',16,16, "absmiddle").$lienfin.' ');
        $result.=$lien.($maxlen?dol_trunc($this->nom,$maxlen):$this->nom).$lienfin;
        return $result;
    }
    public function getNextSoc()
    {
        global $user;
        //on regarde l'heure courante
        $date =  time();
        //TODO grace time dans la conf
        $datePrev = $date - 5 * 60;

        //Lock table

        //il y a quelqu'un a recontacter grace a dateRecontact et contact en cours (statut = 2)
        $requete = "SELECT *
                      FROM Babel_campagne_societe
                     WHERE fk_statut = 4
                       AND UNIX_TIMESTAMP(dateRecontact) < " . $datePrev. "
                       AND campagne_refid = ".$this->id."
                       AND user_id = ".$user->id."
                     LIMIT 1";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);

        if ($this->db->num_rows($sql) > 0)
        {
            $this->nextSoc = $res->societe_refid;
            //sinon, on prend le prochain a prendre en charge
            $this->setProcessing();
            return ($this->nextSoc);
        } else {
            $requete1 = "SELECT *
                           FROM Babel_campagne_societe
                          WHERE fk_statut = 1
                            AND campagne_refid = ".$this->id."
                          LIMIT 1";
            $sql1 = $this->db->query($requete1);
            if ($this->db->num_rows($sql1) > 0)
            {
                $res1 = $this->db->fetch_object($sql1);
                $this->nextSoc = $res1->societe_refid;
                $this->setProcessing();
                return ($this->nextSoc);
            } else {
                $requete2 = "SELECT *
                               FROM Babel_campagne_societe
                              WHERE fk_statut = 4
                                AND campagne_refid = ".$this->id."
                                AND user_id = ".$user->id."
                              LIMIT 1";
                $sql2 = $this->db->query($requete2);
                if ($this->db->num_rows($sql2) > 0)
                {
                    $res2 = $this->db->fetch_object($sql2);
                    $this->nextSoc = $res2->societe_refid;
                    $this->nextSocDate = $res2->dateRecontact;
                    $this->setProcessing();
                    return ($this->nextSoc);
                } else {
                    $this->nextSoc = false;
                    return (-2);
                }

            }
        }
        //si plus personne => return -2
        // si plus personne pour maintenant => return -3 + rappel


    }
    public function setProcessing()
    {
        global $user;
        //Si pour l'utilisateur, il y a une soc en mode 2
        $requete = "SELECT *
                      FROM Babel_campagne_societe
                     WHERE fk_statut=2
                       AND user_id = ".$user->id."
                       AND campagne_refid = " . $this->id  . "
                       AND societe_refid <> ".$this->nextSoc;
        $sql = $this->db->query($requete);
        if ($this->db->num_rows($sql) > 0)
        {
            while ($res = $this->db->fetch_object($sql))
            {
                $requete1 = "UPDATE Babel_campagne_societe
                                SET fk_statut = 4 ,
                                    dateRecontact = date_sub(now(), interval 10 minute)
                              WHERE id = ".$res->id;
                $this->db->query($requete1);
            }
        }
        $requete = "UPDATE Babel_campagne_societe
                       SET fk_statut = 2,
                           date_prisecharge = now(),
                           user_id = ".$user->id."
                     WHERE societe_refid =".$this->nextSoc."
                       AND campagne_refid = " . $this->id;
         $this->db->query($requete);
         include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('CAMPAGNEPROSPECT_NEWPRISECHARGE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
    }
    public $SocNote="";
    public $SocAvanc="";
    public function getSocNoteAvanc($socId)
    {
        $requete = "SELECT note,
                           avancement
                      FROM Babel_campagne_avancement
                     WHERE societe_refid = ".$socId . " ORDER by dateModif DESC LIMIT 1";
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->SocNote = $res->note;
        $this->SocAvanc = $res->avancement;
    }
}

class CampagneSoc {
    public $db;
    public $socid;
    public $campagne_id;
    public $id;
    public $user_id;
    public $date_prisecharge;
    public $fk_statut;
    public $user_reprise_refid;
    public $actioncomm_refid;
    public $date_cloture;
    public $resultat; // 0 ou 1


    public function CampagneSoc($db)
    {
        $this->db=$db;
    }
    public function create($socid,$campagne_id)
    {
        $this->socid = $socid;
        $this->campagne_id = $campagne_id;

        $requete = "INSERT INTO Babel_campagne_societe
                                (societe_refid, campagne_refid, fk_statut)
                         VALUES ($socid, $campagne_id,1 )";

        $res=$this->db->query($requete);
        if ($res)
        {
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }
    }
    public function fetch($id)
    {
        $requete = "SELECT Babel_campagne_societe.societe_refid,
                           Babel_campagne_societe.campagne_refid,
                           Babel_campagne_societe.user_id,
                           Babel_campagne_societe.date_prisecharge,
                           Babel_campagne_societe.datem,
                           Babel_campagne_societe.date_cloture,
                           Babel_campagne_societe.user_modification_refid,
                           Babel_campagne_societe.fk_statut,
                           Babel_campagne_societe.user_reprise_refid,
                           Babel_campagne_societe.actioncomm_refid,
                           Babel_campagne_societe.resultat_refid,
                           Babel_campagne_societe.chancedeWin
                      FROM Babel_campagne,
                           Babel_campagne_societe
                 LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ON  Babel_campagne_societe.actioncomm_refid = ".MAIN_DB_PREFIX."c_actioncomm.id
                                            AND ".MAIN_DB_PREFIX."c_actioncomm.module is null
                 LEFT JOIN ".MAIN_DB_PREFIX."societe      ON  Babel_campagne_societe.".MAIN_DB_PREFIX."societe.rowid
                 LEFT JOIN Babel_campagne_societe_c_statut ON Babel_campagne_societe_c_statut.id = Babel_campagne_societe.societe_refid
                     WHERE Babel_campagne.id = Babel_campagne_societe.campagne_refid
                   ";
         if ($id != 0) $requete .= " WHERE Babel_campagne_societe.id = ".$id;
        $res=$this->db->query($requete);
        if ($res)
        {
            $this->socid = $res->societe_refid;
            $this->campagne_refid = $res->campagne_refid;
            $this->user_id = $res->user_id;
            $this->date_prisecharge = $res->date_prisecharge;
            $this->statut = $res->fk_statut;
            $this->user_reprise_refid = $res->user_reprise_refid;
            $this->actioncomm_refid = $res->actioncomm_refid;
            $this->resultat_refid = $res->resultat_refid;
            $this->chancedeWin = $res->chancedeWin;
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }
    }
    public function fetch_per_campagne($id)
    {
        $requete = "SELECT Babel_campagne_societe.societe_refid,
                           Babel_campagne_societe.campagne_refid,
                           Babel_campagne_societe.user_id,
                           Babel_campagne_societe.date_prisecharge,
                           Babel_campagne_societe.datem,
                           Babel_campagne_societe.date_cloture,
                           Babel_campagne_societe.user_modification_refid,
                           Babel_campagne_societe.fk_statut,
                           Babel_campagne_societe.user_reprise_refid,
                           Babel_campagne_societe.actioncomm_refid,
                           Babel_campagne_societe.resultat_refid
                      FROM Babel_campagne,
                           Babel_campagne_societe
                 LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ON  Babel_campagne_societe.actioncomm_refid = ".MAIN_DB_PREFIX."c_actioncomm.id
                                            AND ".MAIN_DB_PREFIX."c_actioncomm.module is null
                 LEFT JOIN ".MAIN_DB_PREFIX."societe ON  Babel_campagne_societe.".MAIN_DB_PREFIX."societe.rowid
                 LEFT JOIN Babel_campagne_societe_c_statut ON Babel_campagne_societe_c_statut.id = Babel_campagne_societe.societe_refid
                     WHERE Babel_campagne.id = Babel_campagne_societe.campagne_refid
                   ";
         if ($id != 0) $requete .= " WHERE Babel_campagne_societe.campagne_refid = ".$id;
        $res=$this->db->query($requete);
        if ($res)
        {
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }

    }

    public function update($id)
    {
        global $user,$langs,$conf;
        $requete ="UPDATE Babel_campagne_societe ";
        $requete .= " SET user_modification_refid = ".$user->id;
        $requete .= " SET datem = now()";
        $requeteArr = array();
        if ($this->user_id){
            array_push($requeteArr , " SET user_id = ".$this->user_id);
        }
        if ($this->dateDebutEffective){
            array_push($requeteArr , " SET dateDebutEffective = ".$this->dateDebutEffective);
        }
        if ($this->dateFinEffective){
            array_push($requeteArr , " SET dateFinEffective = ".$this->dateFinEffective);
        }
        if ($this->chancedeWin)
        {
            array_push($requeteArr , " SET chancedeWin = ".$this->chancedeWin);
        }
        if ($this->socid){
            array_push($requeteArr , " SET societe_refid = ".$this->socid);
        }
        if ($this->resultat_refid){
            array_push($requeteArr , " SET resultat_refid = ".$this->resultat);
        }
        if ($this->fk_statut){
            array_push($requeteArr , " SET fk_statut = ".$this->fk_statut);
        }
        if ($this->date_prisecharge){
            array_push($requeteArr , " SET date_prisecharge = ".$this->date_prisecharge);
        }
        if ($this->user_reprise_refid){
            array_push($requeteArr , " SET user_reprise_refid = ".$this->user_reprise_refid);
        }
        if ($this->actioncomm_refid){
            array_push($requeteArr , " SET actioncomm_refid = ".$this->actioncomm_refid);
        }
        if ($this->date_cloture){
            array_push($requeteArr , " SET date_cloture = ".$this->date_cloture);
        }
        $res=$this->db->query($requete);
        if ($res)
        {
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }
    }
    public function delete($id,$campagne_id=0)
    {
        if ($campagne_id != 0)
        {
            $this->campagne_id = $campagne_id;
        }
        $requete = "DELETE FROM Babel_campagne_societe WHERE societe_refid=".$id . " AND campagne_refid=".$this->campagne_id;
        $res=$this->db->query($requete);
        if ($res)
        {
            return 1;
        } else {
            dol_print_error($this->db,$this->db->error);
            return -1;
        }
    }



}









?>