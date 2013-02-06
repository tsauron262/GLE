<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
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
  *//*
 */

/**
        \file       htdocs/compta/deplacement/deplacement.class.php
        \ingroup    deplacement
        \brief      Fichier de la classe des deplacements
        \version    $Id: deplacement.class.php,v 1.10 2008/05/26 00:03:51 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
        \class      Deplacement
        \brief      Class to manage trips and working credit notes
*/
class Deplacement extends CommonObject
{
    var $db;
    var $errors;

    var $id;
    var $fk_user_author;
    var $fk_user;
    var $km;
    var $prix_ht;
    var $note;
    var $fk_type;
    var $type;
    var $type_libelle;
    var $date;
    var $lieu;
    var $socid;
    var $tva_refid;
    var $taux;
    var $socname;
    var $note;

    /*
    * Initialistation automatique de la classe
    */
    function Deplacement($DB)
    {
        $this->db = $DB;

        return 1;
    }

    /**
    * Create object in database
    *
    * @param unknown_type $user    User that creat
    * @param unknown_type $type    Type of record: 0=trip, 1=credit note
    * @return unknown
    */
    function create($user)
    {
        // Check parameters
        if (empty($this->type) || $this->type < 0)
        {
            $this->error='ErrorBadParameter';
            return -1;
        }
        if (empty($this->fk_user) || $this->fk_user < 0)
        {
            $this->error='ErrorBadParameter';
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."deplacement";
        $sql.= " (datec, fk_user_author, fk_user, type_refid)";
        $sql.= " VALUES (now(), ".$user->id.", ".$this->fk_user.", '".$this->fk_type."')";

        dol_syslog("Deplacement::create sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id("".MAIN_DB_PREFIX."deplacement");
            $ndf=new Ndf($this->db);
            $ndf->createAuto($user);
            $result=$this->update($user);
            if ($result > 0)
            {
                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->db->rollback();
                return $result;
            }
        } else {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }

    }

    /*
    *
    */
    function update($user)
    {
        global $langs;

        // Check parameters
        if (! is_numeric($this->km)) $this->km = 0;
        if (empty($this->fk_type) || $this->fk_type < 0)
        {
            $this->error='ErrorBadParameter';
            return -1;
        }
        if (empty($this->fk_user) || $this->fk_user < 0)
        {
            $this->error='ErrorBadParameter';
            return -1;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."deplacement ";
        $sql .= " SET km = ".$this->km;
        $sql .= " , dated = '".$this->db->idate($this->date)."'";
        $sql .= " , type = '".$this->fk_type."'";
        $sql .= " , tva_refid = '".$this->tva_refid."'";
        $sql .= " , prix_ht = '".$this->prix_ht."'";
        $sql .= " , fk_user = ".$this->fk_user;
        $sql .= " , fk_soc = ".($this->socid > 0?$this->socid:'null');
        $sql .= " WHERE rowid = ".$this->id;

        dol_syslog("Deplacement::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {//update total here
//            $requete = "SELECT SUM(prix_ht) FROM"
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
    *
    */
    function fetch($id)
    {
        $sql = "SELECT rowid,
                       fk_user,
                       ".MAIN_DB_PREFIX."c_deplacement.code as type,
                       ".MAIN_DB_PREFIX."c_deplacement.libelle as type_libelle,
                       km,
                       lieu,
                       prix_ht,
                       note,
                       ".MAIN_DB_PREFIX."societe.nom as socname ,
                       tva_refid,
                       ".MAIN_DB_PREFIX."c_tva.taux,
                       fk_soc,
                       dated as dated";
        $sql.= "  FROM ".MAIN_DB_PREFIX."deplacement,
                       ".MAIN_DB_PREFIX."c_tva,
                       ".MAIN_DB_PREFIX."c_deplacement";
        $sql.= " WHERE ".MAIN_DB_PREFIX."c_deplacement.id = ".MAIN_DB_PREFIX."deplacement.type_refid";
        $sql.= "   AND ".MAIN_DB_PREFIX."c_tva.id = ".MAIN_DB_PREFIX."deplacement.tva_refid
                   AND  rowid = ".$id;

        dol_syslog("Deplacement::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);

            $this->id       = $obj->rowid;
            $this->date     = $obj->dated;
            $this->lieu     = $obj->lieu;
            $this->prix_ht     = $obj->prix_ht;
            $this->fk_user  = $obj->fk_user;
            $this->socid    = $obj->fk_soc;
            $this->tva_refid       = $obj->tva_refid;
            $this->km       = $obj->km;
            $this->taux       = $obj->taux;
            $this->socname       = $obj->socname;
            $this->note       = $obj->note;
            $this->fk_type     = $obj->fk_type;
            $this->type     = $obj->type;
            $this->type_libelle     = $obj->type_libelle;

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /*
    *
    */
    function delete($id)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."deplacement WHERE rowid = ".$id;

        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }



}

class Ndf extends CommonObject
{
    public $id;
    public $periode; // periode de la ndf
    public $periode_month;
    public $periode_year;
    public $fk_user_author; // proprietaire de la ndf
    public $total; // total de la ndf
    public $fk_user_valid; // utilisateur qui a valide
    public $date_valid; // date de validation
    public $statut; //statut de la ndf
    public $error;
    public $db;

    public function ndf($db)
    {
        $this->db=$db;
    }

    function fetch($id)
    {
        global $user;
        $sql = "SELECT id,
                       month(periode) as mperiode,
                       year(periode) as yperiode,
                       periode as rperiode,
                       fk_user_author,
                       fk_user_valid,
                       statut,
                       total ,
                       date_valid as date_valid
                       ";
        $sql.= " FROM Babel_ndf ";
        $sql.= " WHERE rowid = ".$id . " AND fk_user_author =".$user->id;

        dol_syslog("Ndf::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);

            $this->id       = $obj->id;
            $this->periode     = $obj->rperiod;
            $this->periode_month     = $obj->mperiode;
            $this->periode_year     = $obj->yperiode;
            $this->fk_user_author  = $obj->fk_user_author;
            $this->fk_user_valid  = $obj->fk_user_valid;
            $this->total       = $obj->total;
            $this->statut     = $obj->statut;

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    public function fetch_dep($user,$month,$year)
    {
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."deplacement WHERE fk_user = ".$user->id . " AND year(dated) = $year AND month(dated)=$month";
        if ($resql  = $db->fetch_object($requete))
        {
            $arr = array();
            while ($res = $this->db->fetch_object($resql))
            {
                array_push($arr,$res->rowid);
            }
            return ($arr);
        } else {
            return false;
        }
    }
    public function getIdFromDate($month,$year,$user,$create=false)
    {
        $requete = "SELECT month(periode) as mperiode,
                           year(periode) as yperiode
                      FROM Babel_ndf
                     WHERE month(periode) = $month
                       AND year(periode) = $year
                       AND fk_user_author = ".$user->id;
        if ($resql = $this->db->query($requete))
        {
            $cnt = $this->db->num_rows();

            if ($cnt > 0)
            {
                $res = $this->db->fetch_object($resql);
                return ($res->id);
            } else {
                if ($create)
                {
                    $res = $this->db->fetch_object($resql);
                    $dateNdf = $res->yperiode;
                    $dateNdf = $res->mperiode;
                    $requete = "INSERT INTO Babel_ndf
                                            (periode, fk_user_author, total)
                                     VALUES ('".$dateNdf."',".$user->id.",0)";
                    if ($resql1 = $this->db->fetch_object($resql))
                    {
                        $res1 = $this->last_insert_id('Babel_ndf');
                        return ($res1);
                    } else {
                        return (-1);
                    }
                } else {
                    return (-1);
                }

            }

        }
    }

    public $allArray = array();
    function fetchall($userid=false)
    {
        $sql = "SELECT id,
                       month(periode) as mperiode,
                       year(periode) as yperiode,
                       periode as rperiode,
                       unix_timestamp(periode) as tsperiode,
                       fk_user_author,
                       fk_user_valid,
                       statut,
                       total ,
                       date_valid as date_valid
                       ";
        $sql.= " FROM Babel_ndf ";
        if ($userid)
        {
            $sql .= " WHERE fk_user_author = ".$userid;
        }
        dol_syslog("Ndf::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            while ($obj = $this->db->fetch_object($result))
            {
                $this->id             = $obj->id;
                $this->periode        = $obj->rperiode;
                $this->periode_month  = $obj->mperiode;
                $this->periode_year   = $obj->yperiode;
                $this->tsperiode      = $obj->tsperiode;
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_valid  = $obj->fk_user_valid;
                $this->total          = $obj->total;
                $this->statut         = $obj->statut;
                $this->date_valid         = $obj->date_valid;
                array_push($this->allArray,array( "id" => $this->id,
                                                   "periode" => $this->periode,
                                                   "tsperiode" => $this->tsperiode,
                                                   "periode_month" => $this->periode_month,
                                                   "periode_year" => $this->periode_year,
                                                   "fk_user_author" => $this->fk_user_author,
                                                   "fk_user_valid" => $this->fk_user_valid,
                                                   "total" => $this->total,
                                                   "statut" => $this->statut,
                                                   "date_valid" => $this->date_valid
                                                   ));
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    function update()
    {
        global $langs;

        $sql = "UPDATE Babel_ndf ";
        $sql .= " SET total = ".$this->total;
        $sql .= " , periode = '".$this->db->idate($this->periode)."'";
        $sql .= " WHERE rowid = ".$this->id;

        dol_syslog("Ndf::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    function setValidate()
    {
        global $langs;
        global $user;

        $sql = "UPDATE Babel_ndf ";
        $sql .= " SET fk_user_valid = ".$user->fk_user_valid;
        $sql .= " , statut = 3";
        $sql .= " WHERE rowid = ".$this->id ." AND statut = 2";

        dol_syslog("Ndf::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    function setProcessing()
    {
        global $langs;

        $sql  = "UPDATE Babel_ndf ";
        $sql .= "   SET  statut = 2";
        $sql .= " WHERE rowid = ".$this->id ." AND statut = 1";

        dol_syslog("Ndf::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    function setRefused()
    {
        global $langs;
        global $user;

        $sql  = "UPDATE Babel_ndf ";
        $sql .= "   SET  statut = 4, fk_user_valid = ".$user->id;
        $sql .= " WHERE rowid = ".$this->id ." AND statut = 1";

        dol_syslog("Ndf::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    function createAuto($user)
    {
        $db = $this->db;
        $requete = "SELECT concat(month(dated),'',year(dated)) as date_ndf,
                           count(*) as cnt,
                           year(dated) as yd,
                           month(dated) as md
                      FROM ".MAIN_DB_PREFIX."deplacement
                     WHERE fk_user = ".$user->id."
                      AND concat(month(dated),'',year(dated)) not in (SELECT concat(month(periode),'',year(periode))  FROM Babel_ndf WHERE fk_user_author = ".$user->id.")
                    GROUP BY month(dated),year(dated)";
//                    print $requete;
        if ($resql=$db->query($requete))
        {
            while ($res=$db->fetch_object($resql))
            {
//                print $res->date_ndf . " ". $langs->Trans($res->cnt)."<br>";
                //create Ndf
                $periode = $res->yd."-".$res->md."-"."01";
                $total = 0;
                $requete = "INSERT INTO Babel_ndf
                                   (periode, fk_user_author, total)
                            VALUES ('".$periode."','".$user->id."','".$total."')";
                $resql = $db->query($requete);
            }
        } else {
            $this->error=$this->db->error();
        }
    }
    function clean()
    {
        $this->createAuto();
    }

    function create()
    {
        global $user;
        $db = $this->db;
        //create Ndf
        $periode = $this->periode;
        $periode = $this->total;
        $total = 0;
        $requete = "INSERT INTO Babel_ndf
                           (periode, fk_user_author, total)
                    VALUES ('".$periode."','".$user->id."','".$total."')";
        $resql = $db->query($requete);
        if ($resql){
            return 1;
        } else {
            $this->error=$this->db->error();
            return -1;
        }
    }
    function delete($id='')
    {
        if ("x".$id =="x"){ $id = $this->id; }
        $sql = "DELETE FROM Babel_ndf WHERE id = ".$id;

        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }
    /**
    *    \brief      Retourne le libelle du statut d'une expedition
    *    \return     string      Libelle
    */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
    *        \brief      Renvoi le libelle d'un statut donne
    *        \param      statut      Id statut
    *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
    *        \return     string        Libelle
    */
    function LibStatut($statut,$mode)
    {
        global $langs;

        if ($mode==0)
        {
            if ($statut==0) return $langs->trans('StatusTripDraft'); //brouillon
            if ($statut==1) return $langs->trans('StatusTripProcessing');// demande en cours
            if ($statut==2) return $langs->trans('StatusTripValidated');// validation
            if ($statut==3) return $langs->trans('StatusTripClose');// cloture
        }
        if ($mode==1)
        {
            if ($statut==0) return $langs->trans('StatusTripDraftShort'); //brouillon
            if ($statut==1) return $langs->trans('StatusTripProcessingShort');// demande en cours
            if ($statut==2) return $langs->trans('StatusTripValidatedShort');// validation
            if ($statut==3) return $langs->trans('StatusTripCloseShort');// cloture
        }
        if ($mode==2)
        {
            if ($statut==0) return img_picto($langs->trans('StatusTripDraft'),'statut0').$langs->trans('StatusTripDraftShort'); //brouillon
            if ($statut==1) return img_picto($langs->trans('StatusTripProcessing'),'statut2').$langs->trans('StatusTripProcessingShort');// demande en cours
            if ($statut==2) return img_picto($langs->trans('StatusTripValidated'),'statut3').$langs->trans('StatusTripValidatedShort');// validation
            if ($statut==3) return img_picto($langs->trans('StatusTripClose'),'statut4').$langs->trans('StatusTripCloseShort');// cloture
        }
        if ($mode==3)
        {
            if ($statut==0) return img_picto($langs->trans('StatusTripDraftShort'),'statut0'); //brouillon
            if ($statut==1) return img_picto($langs->trans('StatusTripProcessingShort'),'statut2');// demande en cours
            if ($statut==2) return img_picto($langs->trans('StatusTripValidatedShort'),'statut3');// validation
            if ($statut==3) return img_picto($langs->trans('StatusTripCloseShort'),'statut4');// cloture
        }
        if ($mode == 4)
        {
//            print $statut;
            if ($statut==0) return img_picto($langs->trans('StatusTripDraft'),'statut5').' '.$langs->trans('StatusTripDraft');
            if ($statut==1) return img_picto($langs->trans('StatusTripProcessing'),'statut0').' '.$langs->trans('StatusTripProcessing');
            if ($statut==2) return img_picto($langs->trans('StatusTripValidated'),'statut3').' '.$langs->trans('StatusTripValidated');
            if ($statut==3) return img_picto($langs->trans('StatusTripClose'),'statut4').' '.$langs->trans('StatusTripClose');
        }
        if ($mode==5)
        {
            if ($statut==0) return $langs->trans('StatusTripDraftShort').img_picto($langs->trans('StatusTripDraft'),'statut0'); //brouillon
            if ($statut==1) return $langs->trans('StatusTripProcessingShort').img_picto($langs->trans('StatusTripProcessing'),'statut2');// demande en cours
            if ($statut==2) return $langs->trans('StatusTripValidatedShort').img_picto($langs->trans('StatusTripValidated'),'statut3');// validation
            if ($statut==3) return $langs->trans('StatusTripCloseShort').img_picto($langs->trans('StatusTripClose'),'statut4');// cloture
        }
    }

}
?>
