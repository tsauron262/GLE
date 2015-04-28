<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  *//*
 *
 * $Id: interface_modOrangeHrm_OrangeHrmSync.class.php,
 * v 1.1 2008/01/06 20:33:49
 * hregis Exp $
 */

/**
        \file       htdocs/includes/triggers/interface_modPhenix_OrangeHrmSync.class.php
        \ingroup    phenix
        \brief      Fichier de gestion des triggers OrangeHrm
*/


/**
        \class      InterfaceOrangeHrmSync
        \brief      Classe des fonctions triggers des actions OrangeHrm
*/

class InterfaceHRMSync
{
    public $db;
    public $error;

    public $date;
    public $duree;
    public $texte;
    public $desc;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceHRMSync($DB)
    {
        $this->db = $DB ;

        $this->name = "OrangeHrmSync";
        $this->family = "OldGleModule";
        $this->description = "Les triggers de ce composant permettent de synchroniser OrangeHrm et GLE";
        $this->version = '0.1';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return GLE_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {

        //init nécéssaire à l'activation et à la descativaton de OrangeHrm
        //dans init, faire un populate du calendar

        // Mettre ici le code e executer en reaction de l'action

        $langs->load("synopsisGene@Synopsis_Tools");
        $db=$this->db;
        require_once(DOL_DOCUMENT_ROOT.'/hrm/hrm.class.php');
        $hrm = new hrm($db);

        switch ($action)
        {
            case 'COMPANY_CREATE':
            //add into hs_hr_customer
            $hrm->hrmdb->begin();
            $requete = "SELECT max(customer_id) + 1 as hrmCustId FROM hs_hr_customer";
            $sql = $hrm->hrmdb->query($requete);
            $res =$hrm->hrmdb->fetch_object($sql);
            $hrmCustId = $res->hrmCustId;
            if ($hrmCustId ."x" == "x")
            {
                $hrmCustId = 1;
            }

            $requete = "INSERT INTO hs_hr_customer (customer_id,name,description) VALUES (".$hrmCustId.", '".$object->nom."', '".$object->note."')";
            $sql = $hrm->hrmdb->query($requete);
            if ($sql)
            {
                $requete = "INSERT INTO Babel_hrm_customer (GLEId,HRMId) VALUES (".$object->id.",".$hrmCustId.")";
                $sql1 = $object->db->query($requete);
                if ($sql1)
                {
                    $hrm->hrmdb->commit();
                    $object->db->commit();
                } else {
                    $hrm->hrmdb->rollback();
                    $object->db->rollback();
                }

            } else {
                $hrm->hrmdb->rollback();
                $object->db->rollback();
            }            //add into Babel_hrm_customer
            break;
            case 'COMPANY_DELETE'://faux !! passe en deleted
            //del into hs_hr_customer
                $hrmCustId = $hrm->customer_GLEId2HRMId($object->id);

                if ($hrmCustId)
                {
                    $requete = "DELETE FROM hs_hr_customer WHERE customer_id = " . $hrmCustId;
                    $sql = $hrm->hrmdb->query($requete);
                    if ($sql)
                    {
                        $requete = "DELETE FROM Babel_hrm_customer WHERE GLEid = ".$object->id;
                        $sql1 = $srm->hrmdb->query($requete);
                        if ($sql1)
                        {
                            $hrm->hrmdb->commit();
                            $object->db->commit();
                        } else {
                            $hrm->hrmdb->rollback();
                            $object->db->rollback();
                        }
                    } else {
                        $hrm->hrmdb->rollback();
                        $object->db->rollback();
                    }
                }
            //del into Babel_hrm_customer
            break;
            case 'COMPANY_MODIFY':
                //if name change => mod js_hr_customer
                $hrmCustId = $hrm->customer_GLEId2HRMId($object->id);

                $sql = $object->db->query($requete);
                if ($sql)
                {
                    $requete1 = "SELECT *  FROM hs_hr_customer WHERE customer_id = ".$hrmCustId;
                    $sql1 = $hrm->hrmdb->query($requete);
                    if ($sql1)
                    {
                        $res1 = $hrm->hrmdb->fetch_object($sql1);
                        if ($res1->description != $object->note || $res1->name != $object->nom)
                        {
                            $requete = 'UPDATE hs_hr_customer SET description='.$object->note.' , name="'.$object->nom.'"  WHERE customer_id = '.$hrmCustId;
                        }
                    }
                }
            break;

            case 'PROJECT_CREATE':
                $hrm->hrmdb->begin();
                $requete = "SELECT max(project_id) + 1 as hrmProjId FROM hs_hr_project";
                $sql = $hrm->hrmdb->query($requete);
                $res =$hrm->hrmdb->fetch_object($sql);
                $hrmProjId = $res->hrmProjId;
                if ($hrmProjId ."x" == "x")
                {
                    $hrmProjId = 1;
                }
                $hrmCustId = $hrm->customer_GLEId2HRMId($object->socid);
                $requete = "INSERT INTO hs_hr_project (project_id,customer_id,name,description) VALUES (".$hrmProjId.", ".$hrmCustId.", '".$object->title."', '".$object->note."')";
                $sql = $hrm->hrmdb->query($requete);
                if ($sql)
                {
                    $requete = "INSERT INTO Babel_hrm_project (GLEId,HRMId) VALUES (".$object->id.",".$hrmProjId.")";
                    $sql1 = $object->db->query($requete);
                    if ($sql1)
                    {
                        $hrm->hrmdb->commit();
                        $object->db->commit();
                    } else {
                        $hrm->hrmdb->rollback();
                        $object->db->rollback();
                    }

                } else {
                    $hrm->hrmdb->rollback();
                    $object->db->rollback();
                }
            break;
            case 'PROJECT_UPDATE':
                $hrmTaskId = $hrm->project_GLEId2HRMId($object->fk_projet);
                if ($hrmTaskId)
                {
                    $requete1 = "SELECT *  FROM hs_hr_project WHERE project_id = ".$hrmTaskId;
                    $sql1 = $hrm->hrmdb->query($requete);
                    if ($sql1)
                    {
                        $res1 = $hrm->hrmdb->fetch_object($sql1);
                        if ($res1->description != $object->note || $res1->name != $object->nom)
                        {
                            $requete = 'UPDATE hs_hr_project SET description='.$object->note.' , name="'.$object->nom.'"  WHERE project_id = '.$hrmTaskId;
                        }
                    }
                }

            break;
            case 'PROJECT_DELETE':
                $hrmTaskId = $hrm->project_GLEId2HRMId($object->fk_projet);
                if ($hrmTaskId)
                {
                    $requete = "UPDATE hs_hr_project set deleted = 1 WHERE project_id = " . $hrmTaskId;
                    $sql = $hrm->hrmdb->query($requete);
                    if ($sql)
                    {
                        $requete = "DELETE FROM Babel_hrm_project WHERE GLEid = ".$object->id;
                        $sql1 = $hrm->hrmdb->query($requete);
                        if ($sql1)
                        {
                            $hrm->hrmdb->commit();
                            $object->db->commit();
                        } else {
                            $hrm->hrmdb->rollback();
                            $object->db->rollback();
                        }
                    } else {
                        $hrm->hrmdb->rollback();
                        $object->db->rollback();
                    }
                }

            break;
            case 'PROJECT_MOD_PROJADMIN':
                $hrmid = $hrm->GleId2HrmId($object->user_resp_id);
                $projId = $hrm->project_GLEId2HRMId($object->id);
                if ($projId && $hrmid)
                {
                    $res = $hrm->hrmdb->fetch($requete);
                    $requete = "UPDATE hs_hr_project_admin SET emp_number = ".$hrmid . ", project_id = ".$projId;
                    $hrm->hrmdb->query($requete);
                }
            break;
            case 'PROJECT_CREATE_PROJADMIN':
                $hrmid = $hrm->GleId2HrmId($object->user_resp_id);
                $projId = $hrm->project_GLEId2HRMId($object->id);
                if ($projId && $projId)
                {
                    $requete = "INSERT INTO hs_hr_project_admin (emp_number, project_id) VALUES (".$hrmid . ", ".$projId.")";
                    $hrm->hrmdb->query($requete);
                }
            break;
            case 'PROJECT_CREATE_TASK':
                $hrm->hrmdb->begin();
                $requete = "SELECT max(project_id) + 1 as hrmProjId FROM hs_hr_project_activity";
                $sql = $hrm->hrmdb->query($requete);
                $res =$hrm->hrmdb->fetch_object($sql);
                $hrmTaskId = $res->hrmProjId;
                if ($hrmTaskId ."x" == "x")
                {
                    $hrmTaskId = 1;
                }
                $hrm->customer_GLEId2HRMId($object->socid);
                $hrmTaskId = $hrm->project_GLEId2HRMId($object->fk_projet);

                $requete = "INSERT INTO hs_hr_project_activity (activity_id,project_id,name) VALUES (".$hrmTaskId.", ".$hrmProjId .", '".$object->nom."')";
                $sql = $hrm->hrmdb->query($requete);
                if ($sql)
                {
                    $requete = "INSERT INTO Babel_hrm_project_task (GLEId,HRMId) VALUES (".$object->id.",".$hrmTaskId.")";
                    $sql1 = $object->db->query($requete);
                    if ($sql1)
                    {
                        $hrm->hrmdb->commit();
                        $object->db->commit();
                    } else {
                        $hrm->hrmdb->rollback();
                        $object->db->rollback();
                    }

                } else {
                    $hrm->hrmdb->rollback();
                    $object->db->rollback();
                }
            break;
            case 'PROJECT_UPDATE_TASK':
            //UPDATE dans orangeHRM
                $hrmTaskId = $hrm->projectTask_GLEId2HRMId($object->id);
                $requete = "UPDATE hs_hr_project_activity SET name = '".$object->nom."' WHERE activity_id = ". $hrmTaskId;
                $hrm->hrmdb->query($requete);
            break;
            case 'PROJECT_DELETE_TASK':
                //DELETE dans orangeHRM
                $hrmTaskId = $hrm->projectTask_GLEId2HRMId($object->id);
                $requete = "UPDATE hs_hr_project_activity SET deleted = 1 WHERE activity_id = ".$hrmTaskId;
                $hrm->hrmdb->query($requete);
            break;


/***** Admin *******/
            case 'USER_CREATE':
            //si dans OrangeHrm => ajoute un utilisateur OrangeHrm (back end) + ESS
                //Get Database Name
                $lastid = $hrm->create($object);
                //Connect to mysql
                    //
                //Insert into hs_hr_users
                    // => get Id
                //Insert into gle => Babel_hrm_user

            break;
            case 'USER_DELETE':
            //si dans OrangeHrm => efface un utilisateur OrangeHrm (back end)
                $hrmid = $hrm->GleId2HrmId($object->id);
                $hrm->hrmdb->begin();
                $requete = "DELETE FROM hs_hr_employee WHERE emp_number = ".$hrmid;
                $sql = $hrm->hrmdb->query($requete);
                $requete = "UPDATE hs_hr_users SET status = 'Disabled', deleted=1 WHERE emp_number =".$hrmid;
                $sql1=$db->hrm->hrmdb->query($requete);
                if ($sql && $sql1)
                {
                    $hrm->hrmdb->commit();
                } else {
                    $hrm->hrmdb->rollback();
                }
            break;
            case 'USER_DISABLE':
            //si dans OrangeHrm => efface compte ESS
                $hrmid = $hrm->GleId2HrmId($object->id);
                $requete = "UPDATE hs_hr_users SET status = 'Disabled' WHERE emp_number =".$hrmid;
                $db->hrm->hrmdb->query($requete);
            break;
            case 'USER_ENABLE':
            //si dans OrangeHrm => active compte ESS
                //new ID ESS
                $hrmid = $hrm->GleId2HrmId($object->id);
                $requete = "UPDATE hs_hr_users SET status = 'Enabled' WHERE emp_number =".$hrmid;
                $hrm->hrmdb->query($requete);
            break;
            case 'USER_MODIFY':
            //si dans OrangeHrm => modify l utilisateur OrangeHrm (back end)
            //trier les parametres
                $hrm->update($object);
            break;
            case 'USER_ENABLEDISABLE':
            //cf enable et disable
                $hrmid = $hrm->GleId2HrmId($object->id);
                $requete="SELECT status FROM hs_hr_users WHERE emp_number =".$hrmid;
                $sql = $db->hrm->hrmdb->query($requete);
                $res = $db->hrm->hrmdb->fetch_obeject($sql);
                if ($res->status == "Enabled")
                {
                    $requete = "UPDATE hs_hr_users SET status = 'Disabled' WHERE emp_number =".$hrmid;
                    $db->hrm->hrmdb->query($requete);
                } else if ($res->status == "Disabled")
                {
                    $requete = "UPDATE hs_hr_users SET status = 'Enabled' WHERE emp_number =".$hrmid;
                    $db->hrm->hrmdb->query($requete);
                }
            break;
            case 'USER_LOGIN':
            break;
            case 'USER_LOGIN_FAILED':
            break;
            case 'USER_CHANGERIGHT':
            break;
            case 'USER_NEW_PASSWORD':
                // on modifie le password OrangeHrm
                //md5
                $hrmid = $hrm->GleId2HrmId($object->id);
                $requete = "UPDATE hs_hr_users SET user_password = '".$object->pass_indatabase_crypted."' WHERE emp_number=".$hrmid;
                $sql = $hrm->hrmdb->query($requete);
            break;
        }
        return 0;
    }
}
?>
