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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
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
/*
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
    var $note;
    var $type;
    var $socname;

    var $date;
    var $dated;
    var $socid;
    var $lieu;
    var $tx;
    var $tva_taux;
    var $prix_ht;
    var $deplibelle;
    var $depcode;
    var $newNdfId = 0;
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

        $requetePre = "SELECT statut " .
        		"	     FROM Babel_ndf " .
        		"		WHERE month(periode) ='".date('m',$this->date)."' " .
        		"	      AND year(periode) ='".date('Y',$this->date)."'";
        $statut = '';
        if ($resqlPre = $this->db->query($requetePre))
        {
            $statut = $this->db->fetch_object($resqlPre)->statut;

        }
        if ("x".$statut ="x" || $statut <= 1)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."deplacement";
            $sql.= " (datec, fk_user_author, fk_user, type_refid)";
            $sql.= " VALUES (now(), ".$user->id.", ".$this->fk_user.", '".$this->type."')";

            dol_syslog("Deplacement::create sql=".$sql, LOG_DEBUG);
            $result = $this->db->query($sql);
            if ($result)
            {
                $this->id = $this->db->last_insert_id("".MAIN_DB_PREFIX."deplacement");
                $ndf=new Ndf($this->db);
                $this->newNdfId = $ndf->createAuto($user);
                if (!$this->id)
                {
                	print "'err'";
                    $this->db->rollback();
                    $this->error.= "<br>"."La creation a echou&eacute;e";
                } else if (!$this->newNdfId && $this->newNdfId != 0)
                {
                	print "'err2'";
                    $this->db->rollback();
                    $this->error.= "<br>"."La creation de la ndf a echou&eacute;e ";
                } else {
	                $result=$this->update($user);

                }
                if ($result)
                {
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                	print "'err3'";
                    $this->db->rollback();
                    $this->error .= "<br>"."La mise &agrave jour a echou&eacute;e ";
                    return $result;
                }
            } else {
                $this->error.="<br>".$this->db->error()." sql=".$sql;
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = "Ndf valid&eacute;e pour cette p&eacute;riode";
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

        $sql = "UPDATE ".MAIN_DB_PREFIX."deplacement ";
        $sql .= " SET km = ".($this->km > 0?$this->km:'NULL');
        $sql .= " , dated = '".$this->db->idate($this->date)."'";
        $sql .= " , type_refid = ".($this->type > 0?$this->type:'NULL');
        $sql .= " , fk_user = ".$this->fk_user;
        $sql .= " , fk_soc = ".($this->socid > 0?$this->socid:'null');
        $sql .= " , note = ".(trim($this->note) ."x" != "x" ?"'".trim($this->note)."'":'null');
        $sql .= " , lieu = ".($this->lieu."x" != "x"?"'".$this->lieu."'":"null");
        $sql .= " , tva_taux = ".($this->tva_taux > 0?$this->tva_taux:"null");
        $sql .= " , prix_ht = ".($this->prix_ht> 0?$this->prix_ht:"null");
        $sql .= " WHERE rowid = ".$this->id;
        dol_syslog("Deplacement::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        $res1 = $this->postUpdateKm();
        if ($result && $res1 )
        {
            if (!$this->postUpdateTotal())
            {
                $this->error.="Ne peut pas calculer le total";
                return -1;
            }

            return 1;
        }
        else
        {
            if (!$res1)
            {
                $this->error.=$this->db->error() . " <BR> Erreur dans la mise &agrave jour des KM";
            }
            if (!$result) {
                $this->error.=$this->db->error() . " <BR> Erreur dans la mise &agrave; jour";
            }

            return -1;
        }
    }
    function postUpdateTotal()
    {
        //combien de KM pour le mois, combien de Km valider
        $ndfId = "";
        if ($this->newNdfId ."x" == "x")
        {
            $ndfId = $this->getNdf();
        } else {
            $ndfId = new Ndf($this->db);
            $ndfId->ud=$this->newNdfId;
            $ndfId->fetch($this->newNdfId);
        }

        $totalMois = $this->getKm(1);
        $totalValid = $this->getKm(2);
        //bareme
        $compMois = $this->getBareme($this->fk_user,$totalMois+$totalValid,'$totalMois+$totalValid',$this->periode_year);
        $compValid = $this->getBareme($this->fk_user,$totalValid,'$totalValid',$this->periode_year);
//        print "comp1 ".$compMois." -> "."<BR>";
//        print 'comp2 '.$compValid ." -> "."<BR>";
        $AmountKm = $compMois - $compValid;

        //amount HT hors KM
        $requete = "SELECT Sum(prix_ht) as totHT , Sum(prix_ht*(1+tva_taux/100)) as totTTC
                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                     WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                       AND Babel_ndf.id = ".$ndfId->id;
        $totHT = "";
        $totTTC = "";
        if ($resql = $this->db->query($requete))
        {
            $res = $this->db->fetch_object($resql);
            $totHT = $res->totHT;
            $totTTC = $res->totTTC;
        }
        //calcul ttc et HT
        $grandTotHT = $totHT + $AmountKm;
        $grandTotTTC = $totTTC + $AmountKm;
//        print "HT ".$grandTotHT . "<BR>";
//        print "TTC ".$grandTotTTC . "<BR>";
//        print "km1 ".$AmountKm . "<BR>";
        $grandTotHT = preg_replace('/,/','.',$grandTotHT);
        $grandTotTTC = preg_replace('/,/','.',$grandTotTTC);
        $requete = "UPDATE Babel_ndf
                       SET total=".$grandTotHT.", total_ttc=".$grandTotTTC."
                     WHERE id=".$ndfId->id;
        if ($resql = $this->db->query($requete))
        {
            return true;
        } else {
            $this->error.=$this->db->error() ."<br>". $requete."<br>";
            return false;
        }
    }

    function getSeuil ($totKm)
    {
//        print $totKm;
//        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
//        $baremeID="";
//        if ($resql=$this->db->query($requete))
//        {
//            $baremeID = "";
//            while ($res=$this->db->fetch_object($resql))
//            {
//                if ($totKm < $res->seuil && $res->seuil != 0)
//                {
//                    $baremeID = $res->name;
//                } else if ($res->seuil == 0 && $totKm != 0)
//                {
//                    $baremeID = $res->id;
//                }
//            }
//        }
//        return($baremeID);
       $baremeId="";
        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
        if ($resql=$this->db->query($requete))
        {
            while ($res=$this->db->fetch_object($requete))
            {
                $expr = $res->express;
                $tot = $totKm;
                $tmpVal=0;
                eval("\$tmpVal = $expr;");
                if ($tmpVal == 1)
                {
                    $baremeId=$res->name;
//                    $baremeSeuil = $res->name;
                }
            }
        }
        return($baremeId);
    }


    function getBareme($userId,$totKm,$year=-1)
    {
        if ($year==-1) $year=date('Y');
        $totKm=intval($totKm);
        //get CV from userid //TODO
        $fuser = new User($this->db);
        $fuser->fetch($userId);
        $cv=$fuser->CV_ndf;
//        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
//        if ($resql=$this->db->query($requete))
//        {
//            $baremeID = "";
//            while ($res=$this->db->fetch_object($resql))
//            {
//                if ($totKm < $res->seuil && $res->seuil != 0)
//                {
//                    $baremeID = $res->id;
//                } else if ($res->seuil == 0 && $totKm != 0)
//                {
//                    $baremeID = $res->id;
//                }
//            }
        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
        if ($resql=$this->db->query($requete))
        {
            while ($res=$this->db->fetch_object($requete))
            {
                $expr = $res->express;
                $tot = $totKm;
                $tmpVal=0;
                $baremeID="";
                eval("\$tmpVal = $expr;");
                if ($tmpVal == 1)
                {
                    $baremeID=$res->id;
//                    $baremeSeuil = $res->name;
                }
            }
            $requete1 = "SELECT * FROM Babel_kilometrage WHERE distance_refid = ".$baremeID .' AND cv ='.$cv.' AND annee = '.$year;
            if ($resql1=$this->db->query($requete1))
            {
                $res1 = $this->db->fetch_object($resql1)->math;
                $res1 = preg_replace('/\[X\]/',$totKm,$res1);
                eval("\$comp = $res1;");
                return($comp);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function getKm($mode=2)
    {
        $requete = "";
        $ndfId = $this->getNdf();
        if ($ndfId->id > 0)
        {
            if ($mode == 1) // pour le mois
            {
                    $requete = "SELECT sum(km) as skm
                          FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                         WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                           AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                           AND Babel_ndf.id =".$ndfId->id."
                      GROUP BY month(dated), year(dated)" ;
            } else if ($mode == 3) {
                $requete = "SELECT ifnull(sum(km),0) as skm
                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                     WHERE Babel_ndf.statut = 3
                       AND Babel_ndf.fk_user_author =".$ndfId->fk_user_author."
                       AND ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                       AND Babel_ndf.id <> ".$ndfId->id."
                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                       AND year(dated) = ".$ndfId->periode_year."
                  " ;
            } else {
                $requete = "SELECT ifnull(sum(km),0) as skm
                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                     WHERE Babel_ndf.statut = 3
                       AND Babel_ndf.fk_user_author =".$ndfId->fk_user_author."
                       AND ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                       AND year(dated) = ".$ndfId->periode_year."
                  " ;
            }

//            if ($mode == 1) // pour le mois
//            {
//                    $requete = "SELECT sum(km) as skm
//                          FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
//                         WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
//                           AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
//                           AND Babel_ndf.id =".$ndfId->id."
//                      GROUP BY month(dated), year(dated)" ;
//            } else {
//                $requete = "SELECT ifnull(sum(km),0) as skm
//                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
//                     WHERE Babel_ndf.statut = 3
//                       AND Babel_ndf.fk_user_author =".$this->fk_user."
//                       AND ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
//                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
//                  " ;
//            }
//            print $requete.'<BR>';
            if ($resql = $this->db->query($requete))
            {
                $res = $this->db->fetch_object($resql)->skm;
                $ret = ($res > 0?$res:0);
                return ($ret);

            } else {
                $this->error.=$requete ." failed";
                print $this->error;
                return -1;
            }
        }

    }
    function postUpdateKm()
    {
        if ($this->newNdfId > 0)
        {
            $ndfId = new Ndf($this->db);
            $ndfId->id = $this->newNdfId;
            $ndfId->fetch($ndfId->id);
        } else {
            $ndfId = $this->getNdf();
        }

        if ($ndfId->id > 0)
        {
            $requete = "SELECT ifnull(sum(km),0) as skm
                          FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                         WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                           AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                           AND Babel_ndf.id =".$ndfId->id."
                      GROUP BY month(dated), year(dated)" ;
            if ($resql = $this->db->query($requete))
            {
                $res1=($this->db->fetch_object($resql)->skm."x" =="x"?0:$this->db->fetch_object($resql)->skm);
                $requete1 = "UPDATE Babel_ndf
                                SET total_km = ".$res1."
                              WHERE id =".$ndfId->id;
                if ($resql1 = $this->db->query($requete1))
                {
//                    print $requete1;
//                    exit;
                    return(true);
                } else {
                    $this->error .= "<br>".$this->db->error . " Erreur maj SQL des KM <BR>" ;
                    return (false);
                }
            } else {
                $this->error .= "<br>".$this->db->error . " Erreur maj SQL des Km : dans la rechearche du total <BR>" ;
                return (false);
            }
        } else {
                $this->error .= "<br> Pas d'id fournit <BR>" ;
            return (false);
        }
    }

    /**
    *
    */
    function fetch($id)
    {
        global $langs;
        $sql = "SELECT ".MAIN_DB_PREFIX."deplacement.rowid,
                       ".MAIN_DB_PREFIX."deplacement.fk_user,
                       ".MAIN_DB_PREFIX."deplacement.type_refid,
                       ".MAIN_DB_PREFIX."deplacement.km,
                       ".MAIN_DB_PREFIX."deplacement.prix_ht,
                       ".MAIN_DB_PREFIX."deplacement.lieu,
                       ".MAIN_DB_PREFIX."deplacement.tva_taux as tx,
                       ".MAIN_DB_PREFIX."c_deplacement.code as depcode,
                       ".MAIN_DB_PREFIX."c_deplacement.libelle as deplibelle,
                       ".MAIN_DB_PREFIX."deplacement.fk_soc,
                       ".MAIN_DB_PREFIX."societe.nom as socname,
                       ".MAIN_DB_PREFIX."deplacement.fk_user,
                       ".MAIN_DB_PREFIX."deplacement.fk_user_author,
                       ".MAIN_DB_PREFIX."deplacement.note,
                       ".MAIN_DB_PREFIX."deplacement.dated as dated";
        $sql.= "  FROM ".MAIN_DB_PREFIX."deplacement,
                       ".MAIN_DB_PREFIX."societe,
                       ".MAIN_DB_PREFIX."c_deplacement";
        $sql.= " WHERE ".MAIN_DB_PREFIX."deplacement.rowid = ".$id.
               "   AND ".MAIN_DB_PREFIX."c_deplacement.id = ".MAIN_DB_PREFIX."deplacement.type_refid ".
               "   AND ".MAIN_DB_PREFIX."societe.rowid = ".MAIN_DB_PREFIX."deplacement.fk_soc ";
//print $sql;
        dol_syslog("Deplacement::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);

            $this->id       = $obj->rowid;
            $this->date     = $obj->dated;
            $this->fk_user  = $obj->fk_user;
            $this->socid    = $obj->fk_soc;
            $this->socname    = $obj->socname;
            $this->km       = $obj->km;
            $this->dated     = $obj->dated;
            $this->note     = $obj->note;
            $this->lieu     = $obj->lieu;
            $this->tva_taux     = $obj->tx;
            $this->prix_ht     = $obj->prix_ht;
            $this->type     = $obj->type_refid;
            $this->depcode     = $obj->depcode;
            $this->deplibelle     = $obj->deplibelle;
            $this->type_dep = ($this->depcode  == $langs->trans($this->depcode)?$this->deplibelle:$langs->trans($this->depcode));

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
        $this->id = $id;
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."deplacement WHERE rowid = ".$id;
        $respre = $this->postUpdateKm();
        $result = $this->db->query($sql);
//        var_dump($respre);
        if ($result && $respre)
        {
            $respre1  = $this->postUpdateTotal();
            if (!$respre1)
            {
                $this->error.="Ne peut pas calculer le total";
                return -1;
            }

            return 1;
        }
        else
        {
            $this->error.=$this->db->error();
            return -1;
        }
    }
    function getNdf()
    {
    	$err = "";
        $requete = "SELECT date_format(dated,'%m') as md,
                           year(dated) as yd
                      FROM ".MAIN_DB_PREFIX."deplacement
                     WHERE rowid =".$this->id;
//print $requete."<br>";
        if ($resql=$this->db->query($requete))
        {
            $res = $this->db->fetch_object($resql);
            $periodToSeek = $res->yd . "-".$res->md . "-01";
            $requete1 = "SELECT id
                           FROM Babel_ndf
                          WHERE periode = '".$periodToSeek ."'";
            if ($resql1 = $this->db->query($requete1))
            {
                $tmp = $this->db->fetch_object($resql1);
                $res1 = $tmp->id;
//                print $res1;
                if ($res1 . "x" != "x")
                {
                    $tmpObj = new Ndf($this->db);
                    $tmpObj->fetch($res1);
                    return ($tmpObj);
                } else {
                    $err .= "Erreur dans la recherche de l'id : <BR>".$requete."<BR>".$requete1;
                    return (-1);
                }
            } else {
                    $err .= "Erreur dans la requete SELECT de recherche de la periode de getNdf";
                return (-1);
            }
        } else {
//            print $requete;
                    $err .= "Erreur dans la requete SELECT de getNdf " .$requete."<br>";
            return(-1);
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
    public $tsperiode;
    public $ref;

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
                       UNIX_TIMESTAMP(periode) as tsperiode,
                       fk_user_author,
                       fk_user_valid,
                       statut,
                       total ,
                       total_km ,
                       total_ttc ,
                       UNIX_TIMESTAMP(date_valid) as date_valid
                       ";
        $sql.= " FROM Babel_ndf ";
        $sql.= " WHERE id = ".$id  ;
//print $sql;
        dol_syslog("Ndf::fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql) ;
        if ( $result )
        {
            $obj = $this->db->fetch_object($result);

            $this->id       = $obj->id;
            $this->periode     = $obj->rperiode;
            $this->tsperiode     = $obj->tsperiode;
            $this->periode_month     = $obj->mperiode;
            $this->periode_year     = $obj->yperiode;
            $this->fk_user_author  = $obj->fk_user_author;
            $this->fk_user_valid  = $obj->fk_user_valid;
            $this->total       = $obj->total;
            $this->total_km       = $obj->total_km;
            $this->total_ttc       = $obj->total_ttc;
            $this->ref = $obj->fk_user_author."_".$obj->yperiode.$obj->mperiode;
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
        if ($resql  = $db->query($requete))
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
                       total_km ,
                       total_ttc ,
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
                $this->total_ttc          = $obj->total_ttc;
                $this->total_km          = $obj->total_km;
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
                                                   "date_valid" => $this->date_valid,
                                                   "total_ttc" => $this->total_ttc
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
        $sql .= " WHERE id = ".$this->id;

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
        $sql .= " SET fk_user_valid = ".$this->fk_user_valid;
        $sql .= " , statut = 3, date_valid=now() ";
        $sql .= " WHERE id = ".$this->id ." AND statut = 2";

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
    function kmToAmount()
    {
        // Recupere la distance parcourue jusqu'au mois valide
        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."deplacement WHERE statut > ";

        // Recupere le bareme //test sur 2007
        // Calcule le montant
    }
    function setProcessing()
    {
        global $langs;

        $sql  = "UPDATE Babel_ndf ";
        $sql .= "   SET  statut = 2";
        $sql .= " WHERE id = ".$this->id ." AND (statut = 1 OR statut =4 OR statut =0)";
//print $sql;
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
        $sql .= "   SET  statut = 4, fk_user_valid = ".$this->fk_user_valid;
        $sql .= " WHERE id = ".$this->id ." ";

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
        $this->db->begin();
        $requete = "SELECT concat(month(dated),'',year(dated)) as date_ndf,
                           ifnull(count(*),0) as cnt,
                           year(dated) as yd,
                           month(dated) as md,
                           rowid as did
                      FROM ".MAIN_DB_PREFIX."deplacement
                     WHERE fk_user = ".$user->id."
                      AND concat(month(dated),'',year(dated)) not in " .
                      		"(SELECT concat(month(periode),'',year(periode))  " .
                      		"   FROM Babel_ndf " .
                      		"  WHERE fk_user_author = ".$user->id.")
                    GROUP BY month(dated),year(dated)";
        $resql=$db->query($requete);
        $cnt=0;
        if ($resql)
        {
            $res=$db->fetch_object($resql);
            if ($res->cnt >0 || ($res->date_ndf == NULL && $res->cnt==1))
            {
                //create Ndf
                $periode = $res->yd."-".$res->md."-"."01";
                $total = 0;
                $requete = "INSERT INTO Babel_ndf
                                   (periode, fk_user_author, total)
                            VALUES ('".$periode."','".$user->id."','".$total."')";
                if ($resql = $db->query($requete))
                {
                    $newId = $db->last_insert_id("Babel_ndf");
                    $this->db->commit();
                    return ($newId);
                } else {
                    $this->error.=$this->db->error();
                    $this->db->rollback();
                    return(false);
                }

            } else {
            	return(0);
            }
        } else {
            $this->error=$this->db->error();
			return(false);
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
        $total = $this->total;
        $total = 0;
        $requete = "INSERT INTO Babel_ndf
                           (periode, fk_user_author, total, statut)
                    VALUES ('".$periode."','".$user->id."','".$total."',1)";
        $resql = $db->query($requete);
        if ($resql){
            return 1;
            $this->statut = 1;
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
        $langs->load('synopsisGene@Synopsis_Tools');

        if ($mode==0)
        {
            if ($statut==1||$statut == 0) return $langs->trans('StatusTripDraft'); //brouillon
            if ($statut==2) return $langs->trans('StatusTripProcessing');// demande en cours
            if ($statut==3) return $langs->trans('StatusTripValidated');// validation
            if ($statut==4) return $langs->trans('StatusTripCloseRefuse');// cloture
        }
        if ($mode==1)
        {
            if ($statut==1||$statut == 0) return $langs->trans('StatusTripDraftShort'); //brouillon
            if ($statut==2) return $langs->trans('StatusTripProcessingShort');// demande en cours
            if ($statut==3) return $langs->trans('StatusTripValidatedShort');// validation
            if ($statut==4) return $langs->trans('StatusTripCloseRefuseShort');// cloture
        }
        if ($mode==2)
        {
            if ($statut==1||$statut == 0) return img_picto($langs->trans('StatusTripDraft'),'statut0').$langs->trans('StatusTripDraftShort'); //brouillon
            if ($statut==2) return img_picto($langs->trans('StatusTripProcessing'),'statut3').$langs->trans('StatusTripProcessingShort');// demande en cours
            if ($statut==3) return img_picto($langs->trans('StatusTripValidated'),'statut4').$langs->trans('StatusTripValidatedShort');// validation
            if ($statut==4) return img_picto($langs->trans('StatusTripCloseRefuse'),'stcomm-1').$langs->trans('StatusTripCloseRefuseShort');// cloture
        }
        if ($mode==3)
        {
            if ($statut==1||$statut == 0) return img_picto($langs->trans('StatusTripDraftShort'),'statut0'); //brouillon
            if ($statut==2) return img_picto($langs->trans('StatusTripProcessingShort'),'statut3');// demande en cours
            if ($statut==3) return img_picto($langs->trans('StatusTripValidatedShort'),'statut4');// validation
            if ($statut==4) return img_picto($langs->trans('StatusTripCloseRefuseShort'),'stcomm-1');// cloture
        }
        if ($mode == 4)
        {
//            print $statut;
            if ($statut==1||$statut == 0) return img_picto($langs->trans('StatusTripDraft'),'statut0').' '.$langs->trans('StatusTripDraft');
            if ($statut==2) return img_picto($langs->trans('StatusTripProcessing'),'statut3').' '.$langs->trans('StatusTripProcessing');
            if ($statut==3) return img_picto($langs->trans('StatusTripValidated'),'statut4').' '.$langs->trans('StatusTripValidated');
            if ($statut==4) return img_picto($langs->trans('StatusTripCloseRefuse'),'stcomm-1').' '.$langs->trans('StatusTripCloseRefuse');
        }
        if ($mode==5)
        {
            if ($statut==1||$statut == 0) return $langs->trans('StatusTripDraftShort')." " .img_picto($langs->trans('StatusTripDraft'),'statut0'); //brouillon
            if ($statut==2) return $langs->trans('StatusTripProcessingShort')." " .img_picto($langs->trans('StatusTripProcessing'),'statut3');// demande en cours
            if ($statut==3) return $langs->trans('StatusTripValidatedShort')." " .img_picto($langs->trans('StatusTripValidated'),'statut4');// validation
            if ($statut==4) return $langs->trans('StatusTripCloseRefuse')." " .img_picto($langs->trans('StatusTripCloseRefuse'),'stcomm-1');// cloture
        }
    }


    function getBareme($userId,$totKm,$year=-1)
    {
        if ($year==-1)
        {
            $year = date('Y');
        }
        $totKm=intval($totKm);
        //get CV from userid //TODO
        $cv=3;
        $tuser = new User($this->db);
        $tuser->fetch($userId);
        $cv = $tuser->CV_ndf;
        if ("x".$cv == "x")
        {
            return(-1);
        }
        $baremeId="";
        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
        if ($resql=$this->db->query($requete))
        {
            while ($res=$this->db->fetch_object($requete))
            {
                $expr = $res->express;
                $tot = $totKm;
                $tmpVal=0;
                eval("\$tmpVal = $expr;");
                if ($tmpVal == 1)
                {
                    $baremeId=$res->id;
//                    $baremeSeuil = $res->name;
                }
            }
            $requete1 = "SELECT *
                           FROM Babel_kilometrage
                           WHERE distance_refid = ".$baremeId .' AND cv ='.$cv.' AND annee = '.$year;
            if ($resql1=$this->db->query($requete1))
            {
                $res1 = $this->db->fetch_object($resql1)->math;
                $res1 = preg_replace('/\[X\]/',$totKm,$res1);
                if ($res1.'x'=="x")
                {
                	return (0);
                } else {
	                eval("\$comp = $res1;");
    	            return($comp);
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function getSeuil ($totKm)
    {
        $baremeId="";
        $requete = "SELECT * FROM Babel_distance order by seuil + 0 desc";
        if ($resql=$this->db->query($requete))
        {
            while ($res=$this->db->fetch_object($requete))
            {
                $expr = $res->express;
                $tot = $totKm;
                $tmpVal=0;
                eval("\$tmpVal = $expr;");
                if ($tmpVal == 1)
                {
                    $baremeId=$res->name;
//                    $baremeSeuil = $res->name;
                }
            }
        }
        return($baremeId);

    }

    function getKm($mode=2)
    {
        $requete = "";
        if ($this->id > 0)
        {
            if ($mode == 1) // pour le mois
            {
                    $requete = "SELECT ifnull(sum(km),0) as skm
                          FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                         WHERE ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                           AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                           AND Babel_ndf.id =".$this->id."
                           AND Babel_ndf.fk_user_author = ".$this->fk_user_author."
                           AND Babel_ndf.fk_user_author = ".MAIN_DB_PREFIX."deplacement.fk_user_author
                      GROUP BY month(dated), year(dated)" ;
            } else if ($mode == 3) {
                $requete = "SELECT ifnull(sum(km),0) as skm
                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                     WHERE Babel_ndf.statut = 3
                       AND Babel_ndf.fk_user_author =".$this->fk_user_author."
                       AND ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                       AND Babel_ndf.id <> ".$this->id."
                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                       AND year(dated) = ".$this->periode_year."
                  " ;
            }else {
                $requete = "SELECT ifnull(sum(km),0) as skm
                      FROM ".MAIN_DB_PREFIX."deplacement, Babel_ndf
                     WHERE Babel_ndf.statut = 3
                       AND Babel_ndf.fk_user_author =".$this->fk_user_author."
                       AND Babel_ndf.fk_user_author = ".MAIN_DB_PREFIX."deplacement.fk_user_author
                       AND ".MAIN_DB_PREFIX."deplacement.dated > Babel_ndf.periode
                       AND ".MAIN_DB_PREFIX."deplacement.dated < date_add(Babel_ndf.periode, INTERVAL 1 MONTH)
                       AND year(dated) = ".$this->periode_year."
                  " ;
            }
            if ($resql = $this->db->query($requete))
            {
                $res = $this->db->fetch_object($resql)->skm;
                $ret = ($res > 0?$res:0);
                return ($ret);

            } else {
                $this->error.=$requete ." failed";
                print $this->error;
                return 0;
            }
        }

    }

}
?>
