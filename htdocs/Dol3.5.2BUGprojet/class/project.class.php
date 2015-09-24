<?php

/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */
/*
 */

/**
  \file       htdocs/project.class.php
  \ingroup    projet
  \brief      Fichier de la classe de gestion des projets
  \version    $Id: project.class.php,v 1.27.2.1 2008/09/10 09:47:51 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
  \class      Project
  \brief      Classe permettant la gestion des projets
 */
class Project extends CommonObject {

    public $id;
    public $db;
    public $ref;
    public $title;
    public $socid;
    public $user_resp_id;
    public $cost = array();
    public $costByDay = array();
    public $CostRHByUser = array();
    public $CostRHByTaskByUser = array();
    public $CostRHByUserByTask = array();
    public $element = 'synopsisprojet';
    public $table_element = 'Synopsis_projet_view';  //!< Name of table without prefix where object is stored
    public $table_element_line = 'projet_task';
    public $fk_element = 'fk_projet';
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;
    var $ref;
    var $description;
    var $statut;
    var $title;
    var $date_start;
    var $date_end;
    var $socid;
    var $user_author_id;    //!< Id of project creator. Not defined if shared project.
    var $public;      //!< Tell if this is a public or private project
    var $note_private;
    var $note_public;
    var $statuts_short;
    var $statuts;
    var $oldcopy;


    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->societe = new Societe($db);

        $this->statuts_short = array(0 => 'Draft', 1 => 'Validated', 2 => 'Closed');
        $this->statuts = array(0 => 'Draft', 1 => 'Validated', 2 => 'Closed');
    }

    /**
     *    Create a project into database
     *
     *    @param    User	$user       	User making creation
     *    @param	int		$notrigger		Disable triggers
     *    @return   int         			<0 if KO, id of created project if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;

        $error = 0;
        $ret = 0;

        // Check parameters
        if (!trim($this->ref))
        {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog(get_class($this)."::create error -1 ref null", LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet (";
        $sql.= "ref";
        $sql.= ", title";
        $sql.= ", description";
        $sql.= ", fk_soc";
        $sql.= ", fk_user_creat";
        $sql.= ", fk_statut";
        $sql.= ", public";
        $sql.= ", datec";
        $sql.= ", dateo";
        $sql.= ", datee";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'" . $this->db->escape($this->ref) . "'";
        $sql.= ", '" . $this->db->escape($this->title) . "'";
        $sql.= ", '" . $this->db->escape($this->description) . "'";
        $sql.= ", " . ($this->socid > 0 ? $this->socid : "null");
        $sql.= ", " . $user->id;
        $sql.= ", 0";
        $sql.= ", " . ($this->public ? 1 : 0);
        $sql.= ", " . $this->db->idate(dol_now());
        $sql.= ", " . ($this->date_start != '' ? $this->db->idate($this->date_start) : 'null');
        $sql.= ", " . ($this->date_end != '' ? $this->db->idate($this->date_end) : 'null');
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "projet");
            $ret = $this->id;

            if (!$notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CREATE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // End call triggers
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            $this->errno = $this->db->lasterrno();
            dol_syslog(get_class($this)."::create error -2 " . $this->error, LOG_ERR);
            $error++;
        }

        //Update extrafield
        if (!$error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}
        }

        if (!$error && !empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res = $this->setValid($user);
            if ($res < 0) $error++;
        }

        if (!$error)
        {
            $this->db->commit();
            return $ret;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Update a project
     *
     * @param  User		$user       User object of making update
     * @param  int		$notrigger  1=Disable all triggers
     * @return int
     */
    function update($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        // Clean parameters
        $this->title = trim($this->title);
        $this->description = trim($this->description);

        if (dol_strlen(trim($this->ref)) > 0)
        {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet SET";
            $sql.= " ref='" . $this->ref . "'";
            $sql.= ", title = '" . $this->db->escape($this->title) . "'";
            $sql.= ", description = '" . $this->db->escape($this->description) . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_statut = " . $this->statut;
            $sql.= ", public = " . ($this->public ? 1 : 0);
            $sql.= ", datec=" . ($this->date_c != '' ? $this->db->idate($this->date_c) : 'null');
            $sql.= ", dateo=" . ($this->date_start != '' ? $this->db->idate($this->date_start) : 'null');
            $sql.= ", datee=" . ($this->date_end != '' ? $this->db->idate($this->date_end) : 'null');
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog(get_class($this)."::Update sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql))
            {
                if (!$notrigger)
                {
                    // Call triggers
                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('PROJECT_MODIFY', $this, $user, $langs, $conf);
                    if ($result < 0)
                    {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // End call triggers
                }

                //Update extrafield
                if (!$error) {
                	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                	{
                		$result=$this->insertExtraFields();
                		if ($result < 0)
                		{
                			$error++;
                		}
                	}
                }

                if (! $error && (is_object($this->oldcopy) && $this->oldcopy->ref != $this->ref))
                {
                	// We remove directory
                	if ($conf->projet->dir_output)
                	{
                		$olddir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($this->oldcopy->ref);
                		$newdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($this->ref);
                		if (file_exists($olddir))
                		{
                			$res=@dol_move($olddir, $newdir);
                			if (! $res)
                			{
                				$this->error='ErrorFailToMoveDir';
                				$error++;
                			}
                		}
                	}
                }

                $result = 1;
            }
            else
            {
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this)."::Update error -2 " . $this->error, LOG_ERR);
                $result = -2;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::Update ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     * 	Get object and lines from database
     *
     * 	@param      int		$id       	Id of object to load
     * 	@param		string	$ref		Ref of project
     * 	@return     int      		   	>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($id, $ref='')
    {
        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT rowid, ref, title, description, public, datec";
        $sql.= ", tms, dateo, datee, fk_soc, fk_user_creat, fk_statut, note_private, note_public,model_pdf";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        if (! empty($id))
        {
        	$sql.= " WHERE rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " WHERE ref='".$ref."'";
        	$sql.= " AND entity IN (".getEntity('project').")";
        }

        dol_syslog(get_class($this)."::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->title = $obj->title;
                $this->titre = $obj->title; // TODO deprecated
                $this->description = $obj->description;
                $this->date_c = $this->db->jdate($obj->datec);
                $this->datec = $this->db->jdate($obj->datec); // TODO deprecated
                $this->date_m = $this->db->jdate($obj->tms);
                $this->datem = $this->db->jdate($obj->tms);  // TODO deprecated
                $this->date_start = $this->db->jdate($obj->dateo);
                $this->date_end = $this->db->jdate($obj->datee);
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->socid = $obj->fk_soc;
                $this->societe->id = $obj->fk_soc; // TODO For backward compatibility
                $this->user_author_id = $obj->fk_user_creat;
                $this->public = $obj->public;
                $this->statut = $obj->fk_statut;
                $this->modelpdf	= $obj->model_pdf;

                $this->db->free($resql);

                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this)."::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	Return list of projects
     *
     * 	@param		int		$socid		To filter on a particular third party
     * 	@return		array				List of projects
     */
    function liste_array($socid='')
    {
        global $conf;

        $projects = array();

        $sql = "SELECT rowid, title";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        $sql.= " WHERE entity = " . $conf->entity;
        if (! empty($socid)) $sql.= " AND fk_soc = " . $socid;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);

                    $projects[$obj->rowid] = $obj->title;
                    $i++;
                }
            }
            return $projects;
        }
        else
        {
            print $this->db->lasterror();
        }
    }

    /**
     * 	Return list of elements for type linked to project
     *
     * 	@param		string		$type		'propal','order','invoice','order_supplier','invoice_supplier'
     * 	@return		array					List of orders linked to project, <0 if error
     */
    function get_element_list($type)
    {
        $elements = array();

        $sql = '';
        if ($type == 'propal')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "propal WHERE fk_projet=" . $this->id;
        if ($type == 'order')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande WHERE fk_projet=" . $this->id;
        if ($type == 'invoice')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_predefined')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_rec WHERE fk_projet=" . $this->id;
        if ($type == 'order_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_fourn WHERE fk_projet=" . $this->id;
        if ($type == 'contract')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "contrat WHERE fk_projet=" . $this->id;
        if ($type == 'intervention')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "fichinter WHERE fk_projet=" . $this->id;
        if ($type == 'trip')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "deplacement WHERE fk_projet=" . $this->id;
        if ($type == 'agenda')
            $sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_project=" . $this->id;
        if (! $sql) return -1;

        //print $sql;
        dol_syslog(get_class($this)."::get_element_list sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $nump = $this->db->num_rows($result);
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($result);

                    $elements[$i] = $obj->rowid;

                    $i++;
                }
                $this->db->free($result);

                /* Return array */
                return $elements;
            }
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *    Delete a project from database
     *
     *    @param       User		$user            User
     *    @param       int		$notrigger       Disable triggers
     *    @return      int       			      <0 if KO, 0 if not possible, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
        global $langs, $conf;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        $this->db->begin();

        if (!$error)
        {
            // Delete linked contacts
            $res = $this->delete_linked_contact();
            if ($res < 0)
            {
                $this->error = 'ErrorFailToDeleteLinkedContact';
                //$error++;
                $this->db->rollback();
                return 0;
            }
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task_extrafields";
        $sql.= " WHERE fk_object IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "projet_task WHERE fk_projet=" . $this->id . ")";

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task";
        $sql.= " WHERE fk_projet=" . $this->id;

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet";
        $sql.= " WHERE rowid=" . $this->id;

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_extrafields";
        $sql.= " WHERE fk_object=" . $this->id;

        dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql)
        {
            // We remove directory
            $projectref = dol_sanitizeFileName($this->ref);
            if ($conf->projet->dir_output)
            {
                $dir = $conf->projet->dir_output . "/" . $projectref;
                if (file_exists($dir))
                {
                    $res = @dol_delete_dir_recursive($dir);
                    if (!$res)
                    {
                        $this->error = 'ErrorFailToDeleteDir';
                        $this->db->rollback();
                        return 0;
                    }
                }
            }

            if (!$notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_DELETE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // End call triggers
            }

            dol_syslog(get_class($this) . "::delete sql=" . $sql, LOG_DEBUG);
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this) . "::delete " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 		Validate a project
     *
     * 		@param		User	$user		User that validate
     * 		@return		int					<0 if KO, >0 if OK
     */
    function setValid($user)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 1)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 1";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;

            dol_syslog(get_class($this)."::setValid sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_VALIDATE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers

                if (!$error)
                {
                	$this->statut=1;
                	$this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this)."::setValid " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this)."::setValid " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     * 		Close a project
     *
     * 		@param		User	$user		User that validate
     * 		@return		int					<0 if KO, >0 if OK
     */
    function setClose($user)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 2)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 2";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;
            $sql.= " AND fk_statut = 1";

            dol_syslog(get_class($this)."::setClose sql=" . $sql);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CLOSE', $this, $user, $langs, $conf);
                if ($result < 0)
                {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers

                if (!$error)
                {
                    $this->statut = 2;
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this)."::setClose " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                dol_syslog(get_class($this)."::setClose " . $this->error, LOG_ERR);
                return -1;
            }
        }
    }

    /**
     *  Return status label of object
     *
     *  @param  int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string      			Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *  Renvoi status label for a status
     *
     *  @param	int		$statut     id statut
     *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string				Label
     */
    function LibStatut($statut, $mode=0)
    {
        global $langs;

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut == 0)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut1');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
    }

    /**
     * 	Renvoie nom clicable (avec eventuellement le picto)
     *
     * 	@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     * 	@param	string	$option			Variant ('', 'nolink')
     * 	@param	int		$addlabel		0=Default, 1=Add label into string
     * 	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0, $option='', $addlabel=0)
    {
        global $langs;

        $result = '';
        $lien = '';
        $lienfin = '';

        if ($option != 'nolink')
        {
            $lien = '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $this->id . '">';
            $lienfin = '</a>';
        }

        $picto = 'projectpub';
        if (!$this->public) $picto = 'project';

        $label = $langs->trans("ShowProject") . ': ' . $this->ref . ($this->title ? ' - ' . $this->title : '');

        if ($withpicto) $result.=($lien . img_object($label, $picto) . $lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien . $this->ref . $lienfin . (($addlabel && $this->title) ? ' - ' . $this->title : '');
        return $result;
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     * 	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user, $langs, $conf;

        $now=dol_now();

        // Charge tableau des produits prodids
        $prodids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM " . MAIN_DB_PREFIX . "product";
        $sql.= " WHERE tosell = 1";
        $sql.= " AND entity = " . $conf->entity;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods)
            {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id = 0;
        $this->ref = 'SPECIMEN';
        $this->specimen = 1;
        $this->socid = 1;
        $this->date_c = $now;
        $this->date_m = $now;
        $this->date_start = $now;
        $this->note_public = 'SPECIMEN';
        $nbp = rand(1, 9);
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line = new Task($this->db);
            $line->desc = $langs->trans("Description") . " " . $xnbp;
            $line->qty = 1;
            $prodid = rand(1, $num_prods);
            $line->fk_product = $prodids[$prodid];
            $xnbp++;
        }
    }

    /**
     * 	Check if user has permission on current project
     *
     * 	@param	User	$user		Object user to evaluate
     * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
     * 	@return	int					>0 if user has permission, <0 if user has no permission
     */
    function restrictedProjectArea($user, $mode='read')
    {
        // To verify role of users
        $userAccess = 0;
        if (($mode == 'read' && ! empty($user->rights->projet->all->lire)) || ($mode == 'write' && ! empty($user->rights->projet->all->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->all->supprimer)))
        {
            $userAccess = 1;
        }
        else if ($this->public && (($mode == 'read' && ! empty($user->rights->projet->lire)) || ($mode == 'write' && ! empty($user->rights->projet->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->supprimer))))
        {
            $userAccess = 1;
        }
        else
		{
            foreach (array('internal', 'external') as $source)
            {
                $userRole = $this->liste_contact(4, $source);
                $num = count($userRole);

                $nblinks = 0;
                while ($nblinks < $num)
                {
                    if ($source == 'internal' && preg_match('/^PROJECT/', $userRole[$nblinks]['code']) && $user->id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }
                    // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
                    /*if ($source == 'external' && preg_match('/PROJECT/', $userRole[$nblinks]['code']) && $user->contact_id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }*/
                    $nblinks++;
                }
            }
            //if (empty($nblinks))	// If nobody has permission, we grant creator
            //{
            //	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
            //	{
            //		$userAccess = 1;
            //	}
            //}
        }

        return ($userAccess?$userAccess:-1);
    }

    /**
     * Return array of projects a user has permission on, is affected to, or all projects
     *
     * @param 	User	$user			User object
     * @param 	int		$mode			0=All project I have permission on, 1=Projects affected to me only, 2=Will return list of all projects with no test on contacts
     * @param 	int		$list			0=Return array,1=Return string list
     * @param	int		$socid			0=No filter on third party, id of third party
     * @return 	array or string			Array of projects id, or string with projects id separated with ","
     */
    function getProjectsAuthorizedForUser($user, $mode=0, $list=0, $socid=0)
    {
        $projects = array();
        $temp = array();

        $sql = "SELECT DISTINCT p.rowid, p.ref";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
        if ($mode == 0 || $mode == 1)
        {
            $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ec";
            $sql.= ", " . MAIN_DB_PREFIX . "c_type_contact as ctc";
        }
        $sql.= " WHERE p.entity IN (".getEntity('project').")";
        // Internal users must see project he is contact to even if project linked to a third party he can't see.
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        if ($socid > 0) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = " . $socid . ")";

        if ($mode == 0)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ( p.public = 1";
            //$sql.= " OR p.fk_user_creat = ".$user->id;
            $sql.= " OR ( ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            //$sql.= " OR (ctc.source = 'external' AND ec.fk_socpeople = ".($user->contact_id?$user->contact_id:0).")"; // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
            $sql.= " )";
            $sql.= " ))";
        }
        if ($mode == 1)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            //$sql.= " OR (ctc.source = 'external' AND ec.fk_socpeople = ".($user->contact_id?$user->contact_id:0).")"; // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
            $sql.= " )";
        }
        if ($mode == 2)
        {
            // No filter. Use this if user has permission to see all project
        }
        //print $sql;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $projects[$row[0]] = $row[1];
                $temp[] = $row[0];
                $i++;
            }

            $this->db->free($resql);

            if ($list)
            {
                if (empty($temp)) return 0;
                $result = implode(',', $temp);
                return $result;
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        return $projects;
    }
    
    public function Project($DB) {
        $this->db = $DB;
        $this->societe = new Societe($DB);
        global $langs;
        $langs->load("project@projet");
        $this->labelstatut[0] = $langs->trans("ProjectStatusDraft");
        $this->labelstatut[5] = $langs->trans("ProjectStatusPlanning");
        $this->labelstatut[10] = $langs->trans("ProjectStatusRunning");
        $this->labelstatut[50] = $langs->trans("ProjectStatusClose");
        $this->labelstatut[999] = $langs->trans("ProjectStatusWaitingValidation");
        $this->labelstatut_short[0] = $langs->trans("ProjectStatusDraftShort");
        $this->labelstatut_short[5] = $langs->trans("ProjectStatusPlanningShort");
        $this->labelstatut_short[10] = $langs->trans("ProjectStatusRunningShort");
        $this->labelstatut_short[50] = $langs->trans("ProjectStatusCloseShort");
        $this->labelstatut_short[999] = $langs->trans("ProjectStatusWaitingValidationShort");
    }

     /**
      * Load an object from its id and create a new one in database
	  *
	  *	@param	int		$fromid     	Id of object to clone
	  *	@param	bool	$clone_contact	clone contact of project
	  *	@param	bool	$clone_task		clone task of project
	  *	@param	bool	$clone_project_file		clone file of project
	  *	@param	bool	$clone_task_file		clone file of task (if task are copied)
      *	@param	bool	$clone_note		clone note of project
      *	@param	bool	$notrigger		no trigger flag
	  * @return	int						New id of clone
	  */
	function createFromClone($fromid,$clone_contact=false,$clone_task=true,$clone_project_file=false,$clone_task_file=false,$clone_note=true,$notrigger=0)
	{
		global $user,$langs,$conf;

    public function create($user) {
        $error = 0;
        global $langs, $conf;
        // Check parameters
        if (!trim($this->ref)) {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog("Project::Create error -1 ref null");
            return -1;
        }

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_view (ref, title, fk_soc, fk_user_creat, fk_user_resp, dateo, fk_statut,fk_type_projet,date_create)";
        $sql.= " VALUES ('" . addslashes($this->ref) . "', '" . addslashes($this->title) . "',";
        $sql.= " " . ($this->socid > 0 ? $this->socid : "null") . ",";
        $sql.= " " . $user->id . ",";
        $sql.= " " . $this->user_resp_id . ", " . $this->db->idate(time()) . ", 0," . ($this->type_id ? $this->type_id : "NULL") . ",now())";

        dol_syslog("Project::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "Synopsis_projet_view");
            $result = $this->id;
            if ($result > 0) {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $resulta = $interface->run_triggers('PROJECT_CREATE', $this, $user, $langs, $conf);
                if ($resulta < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $resultb = $interface->run_triggers('PROJECT_CREATE_PROJADMIN', $this, $user, $langs, $conf);
                if ($resultb < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
            } else {
                dol_syslog("Project::Create error -2");
                $this->error = $this->db->error();
                $result = -2;
            }
        } else {
            dol_syslog("Project::Create error -2");
            $this->error = $this->db->error();
            $result = -2;
        }
        if ($error > 0) {
            return (-2);
        } else {
            return $result;
        }
    }

    public function update($user) {
        global $langs, $conf;
        if (strlen(trim($this->ref)) > 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_view";
            $sql.= " SET ref='" . $this->ref . "'";
            $sql.= ", title = '" . $this->title . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_user_resp = " . $this->user_resp_id;
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog("Project::update sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql)) {
                $result = 0;
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_UPDATE', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_MOD_PROJADMIN', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
            } else {
                dol_syslog($this->db->error());
                $result = -2;
            }
        } else {
            dol_syslog("Project::Update ref null");
            $result = -1;
        }

        return $result;
    }

    /*
     *    \brief      Charge objet projet depuis la base
     *    \param      rowid       id du projet a charger
     */

    public $task = array();
    public $dateo = '';

    public function valid() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_view SET date_valid=now(), fk_statut = 5 WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return(1);
        else {
            $this->error = $db->lastqueryerror . '<br/>' . $this->db->lasterror;
            return(-1);
        }
    }

		// Create clone
		$result=$clone_project->create($user,$notrigger);

    public function info() {
        $requete = "SELECT *  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->dateo = $res->dateo;
        $this->date_create = $res->date_create;
        $this->date_valid = $res->date_valid;
        $this->date_launch = $res->date_launch;
        $this->date_cloture = $res->date_cloture;
        $this->fk_user_resp = $res->fk_user_resp;
        $this->fk_user_creat = $res->fk_user_creat;
        $tmpUser = new User($this->db);
        $tmpUser->fetch($this->fk_user_resp);
        $this->user_resp = $tmpUser;
        $tmpUser->fetch($this->fk_user_creat);
        $this->user_creat = $tmpUser;
    }

    public function cloture() {
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_view SET date_cloture=now(),  fk_statut = 50 WHERE rowid = " . $this->id;
        $sql = $this->db->query($requete);
        if ($sql)
            return(1);
        else {
            $this->error = $db->lastqueryerror . '<br/>' . $this->db->lasterror;
            return(-1);
        }
    }

    public function fetch($rowid) {

        $sql = "SELECT title,
                       ref,
                       fk_soc,
                       fk_user_creat,
                       fk_user_resp,
                       fk_statut,
                       note,
                       dateo,
                       fk_type_projet,
                       type,
                       refAddOn,
                       hasGantt,
                       hasCout,
                       hasRH,
                       hasRessources,
                       hasRisque,
                       hasImputation,
                       hasPointage,
                       hasAgenda,
                       hasDocuments,
                       hasStats,
                       hasReferent,
                       hasTache,
                       hasTacheLight
                  FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view,
                       " . MAIN_DB_PREFIX . "Synopsis_projet_type";
        $sql.= " WHERE rowid=" . $rowid . "
                   AND " . MAIN_DB_PREFIX . "Synopsis_projet_type.id = " . MAIN_DB_PREFIX . "Synopsis_projet_view.fk_type_projet";
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $rowid;
                $this->ref = $obj->ref;
                $this->title = ($obj->title != '' ? $obj->title : $obj->ref);
                $this->titre = $obj->title;
                $this->note = $obj->note;
                $this->type_id = $obj->fk_type_projet;
                $this->type = $obj->type;
                $this->type_inRef = ($obj->refAddOn . "x" != "x" ? $obj->refAddOn : "");
                $this->socid = $obj->fk_soc;
                $tmpSoc = new Societe($this->db);
                $tmpSoc->fetch($obj->fk_soc);
                $this->societe = $tmpSoc;
                $this->user_author_id = $obj->fk_user_creat;
                $this->user_resp_id = $obj->fk_user_resp;
                $this->statut = $obj->fk_statut;
                $this->dateo = $obj->dateo;

                $this->hasTache = $obj->hasTache;
                $this->hasTacheLight = $obj->hasTacheLight;
                $this->hasGantt = $obj->hasGantt;
                $this->hasCout = $obj->hasCout;
                $this->hasRH = $obj->hasRH;
                $this->hasRessources = $obj->hasRessources;
                $this->hasRisque = $obj->hasRisque;
                $this->hasImputation = $obj->hasImputation;
                $this->hasPointage = $obj->hasPointage;
                $this->hasAgenda = $obj->hasAgenda;
                $this->hasDocuments = $obj->hasDocuments;
                $this->hasStats = $obj->hasStats;
                $this->hasReferents = (isset($obj->hasReferent) ? $obj->hasReferent : null);


                $this->tasks = array();

                $requete1 = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid as tid,
                                    task_date as dateDeb,
                                    " . MAIN_DB_PREFIX . "projet_task.fk_statut as tstatut,
                                    " . MAIN_DB_PREFIX . "projet_task.title as title,
                                    date_add(task_date , INTERVAL task_duration second) as dateFin
                               FROM " . MAIN_DB_PREFIX . "projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "projet_task_time ON " . MAIN_DB_PREFIX . "projet_task_time.fk_task = " . MAIN_DB_PREFIX . "projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                           ORDER BY " . MAIN_DB_PREFIX . "projet_task_time.task_date DESC, title ASC";
                $resql1 = $this->db->query($requete1);
                if ($resql1) {
                    if ($this->db->num_rows($resql1)) {
                        $iter = 0;
                        while ($res1 = $this->db->fetch_object($resql1)) {
                            if ($iter == 0) {
                                $this->first_task = $res1->tid;
                                $this->first_task_begin = $res1->dateDeb;
                                $this->first_task_end = $res1->dateFin;
                            }
                            $this->tasks[$res1->tid]['id'] = $res1->tid;
                            $this->tasks[$res1->tid]['dateDeb'] = $res1->dateDeb;
                            $this->tasks[$res1->tid]['dateFin'] = $res1->dateFin;
                            $this->tasks[$res1->tid]['statut'] = $res1->tstatut;
                            $this->tasks[$res1->tid]['title'] = $res1->title;


                            $iter++;
                        }
                    }
                }
                //last Task
                $requete2 = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid as tid,
                                    task_date as dateDeb,
                                    " . MAIN_DB_PREFIX . "projet_task.fk_statut as tstatut,
                                    date_add(task_date , INTERVAL task_duration second) as dateFin
                               FROM " . MAIN_DB_PREFIX . "projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "projet_task_time ON " . MAIN_DB_PREFIX . "projet_task_time.fk_task = " . MAIN_DB_PREFIX . "projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                           ORDER BY date_add(task_date , INTERVAL task_duration second) DESC LIMIT 1";
                ;
                $resql2 = $this->db->query($requete2);
                if ($resql2) {
                    if ($this->db->num_rows($resql2)) {
                        $res2 = $this->db->fetch_object($resql2);
                        $this->last_task = $res2->tid;
                        $this->last_task_begin = $res2->dateDeb;
                        $this->last_task_end = $res2->dateFin;
                    }
                }
                //Actors
                $requete3 = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid as tid,
                                    " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user as userid,
                                    role
                               FROM " . MAIN_DB_PREFIX . "user, " . MAIN_DB_PREFIX . "projet_task
                          LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task = " . MAIN_DB_PREFIX . "projet_task.rowid
                              WHERE fk_projet = " . $this->id . "
                                AND " . MAIN_DB_PREFIX . "user.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user
                           ORDER BY role";
                $resql3 = $this->db->query($requete3);
                if ($resql3) {
                    if ($this->db->num_rows($resql3)) {
                        $iter = 0;
                        while ($res3 = $this->db->fetch_object($resql3)) {
                            $tmpUsr = new User($this->db);
                            $tmpUsr->fetch($res3->userid);

                            $this->tasks[$res3->tid]['acto'][$res3->role][$iter]['userid'] = $res3->userid;
                            $this->tasks[$res3->tid]['acto'][$res3->role][$iter]['userobj'] = $tmpUsr;
                            $iter++;
                        }
                    }
                }

                $this->db->free($resql);

                return true;
            } else {
                return -1;
            }
        } else {
            print $this->db->error();
            return -2;
        }
    }

    /*
     *
     *
     *
     */

    public function liste_array($id_societe = '') {
        $projets = array();

        $sql = "SELECT rowid, title FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view";

        if (isset($id_societe)) {
            $sql .= " WHERE fk_soc = $id_societe";
        }

        if ($this->db->query($sql)) {
            $nump = $this->db->num_rows();

            if ($nump) {
                $i = 0;
                while ($i < $nump) {
                    $obj = $this->db->fetch_object();

                    $projets[$obj->rowid] = $obj->title;
                    $i++;
                }
            }
            return $projets;
        } else {
            print $this->db->error();
        }
    }

    /**
     *     \brief        Return list of elements for type linked to project
     *    \param        type        'propal','order','invoice','order_supplier','invoice_supplier'
     *    \return        array        List of orders linked to project, <0 if error
     */
    public function get_element_list($type) {
        $elements = array();

        $sql = '';
        if ($type == 'propal')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "propal WHERE fk_projet=" . $this->id;
        if ($type == 'order')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande WHERE fk_projet=" . $this->id;
        if ($type == 'invoice')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE fk_projet=" . $this->id;
        if ($type == 'order_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE fk_projet=" . $this->id;
        if ($type == 'invoice_supplier')
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture_fourn WHERE fk_projet=" . $this->id;
        if ($type == 'synopsischrono')
            $sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE projetId=" . $this->id;


        if (!$sql)
            return -1;

        dol_syslog("Project::get_element_list sql=" . $sql);
        $result = $this->db->query($sql);
        if ($result) {
            $nump = $this->db->num_rows($result);
//            if ($nump) {
                $i = 0;
                while ($i < $nump) {
                    $obj = $this->db->fetch_object($result);

                    $elements[$i] = $obj->rowid;

                    $i++;
                }
                $this->db->free($result);

                /* Return array */
                return $elements;
//            }
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     *    \brief    Supprime le projet dans la base
     *    \param    Utilisateur
     */
    public function delete($user) {
        global $langs, $conf;
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view";
        $sql .= " WHERE rowid=" . $this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('PROJECT_DELETE', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            return 0;
        } else {
            return -1;
        }
    }

    /**
     *    \brief      Cree une tache dans le projet
     *    \param      user        Id utilisateur qui cree
     *    \param     title      titre de la tache
     *    \param      parent   tache parente
     */
    public function CreateTask($user, $title, $parent = 0) {
        global $langs, $conf;
        $result = 0;
        if (trim($title)) {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet_task (fk_projet, title, fk_user_creat, fk_task_parent, duration_effective)";
            $sql.= " VALUES (" . $this->id . ",'$title', " . $user->id . "," . $parent . ", 0)";

            dol_syslog("Project::CreateTask sql=" . $sql, LOG_DEBUG);
            if ($this->db->query($sql)) {
                $task_id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "projet_task");
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_CREATE_TASK', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                $result = 0;
            } else {
                $this->error = $this->db->error();
                dol_syslog("Project::CreateTask error -2 " . $this->error, LOG_ERR);
                $result = -2;
            }

            if ($result == 0) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors (fk_projet_task, fk_user)";
                $sql.= " VALUES (" . $task_id . "," . $user->id . ")";

                dol_syslog("Project::CreateTask sql=" . $sql, LOG_DEBUG);
                if ($this->db->query($sql)) {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($this->db);
                    $result = $interface->run_triggers('PROJECT_CREATE_TASK_ACTORS', $this, $user, $langs, $conf);
                    if ($result < 0) {
                        $error++;
                        $this->errors = $interface->errors;
                    }
                    // Fin appel triggers
                    $result = 0;
                } else {
                    $this->error = $this->db->error();
                    dol_syslog("Project::CreateTask error -3 " . $this->error, LOG_ERR);
                    $result = -2;
                }
            }
        } else {
            dol_syslog("Project::CreateTask error -1 ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     *    \brief      Cree une tache dans le projet
     *    \param      user        Id utilisateur qui cree
     *    \param     title      titre de la tache
     *    \param      parent   tache parente
     */
    public function TaskAddTime($user, $task, $time, $date) {
        $result = 0;
        global $langs, $conf;

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet_task_time (fk_task, task_date, task_duration, fk_user)";
        $sql .= " VALUES (" . $task . ",'" . $this->db->idate($date) . "'," . $time . ", " . $user->id . ") ;";

        if ($this->db->query($sql)) {
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('PROJECT_CREATE_TASK_TIME', $this, $user, $langs, $conf);
            if ($result < 0) {
                $error++;
                $this->errors = $interface->errors;
            }
            // Fin appel triggers
            $task_id = $this->db->last_insert_id("" . MAIN_DB_PREFIX . "projet_task");
            $result = 0;
        } else {
            dol_syslog("Project::TaskAddTime error -2", LOG_ERR);
            $this->error = $this->db->error();
            $result = -2;
        }

        if ($result == 0) {
            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet_task";
            $sql .= " SET duration_effective = duration_effective + '" . preg_replace("/,/", ".", $time) . "'";
            $sql .= " WHERE rowid = '" . $task . "';";

            if ($this->db->query($sql)) {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('PROJECT_UPDATE_TASK', $this, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $this->errors = $interface->errors;
                }
                // Fin appel triggers
                $result = 0;
            } else {
                dol_syslog("Project::TaskAddTime error -3", LOG_ERR);
                $this->error = $this->db->error();
                $result = -2;
            }
        }

        return $result;
    }
    
    
    /**
     * 	Check if user has permission on current project
     *
     * 	@param	User	$user		Object user to evaluate
     * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
     * 	@return	int					>0 if user has permission, <0 if user has no permission
     */
    function restrictedProjectArea($user, $mode='read')
    {
        // To verify role of users
        $userAccess = 0;
        if (($mode == 'read' && ! empty($user->rights->projet->all->lire)) || ($mode == 'write' && ! empty($user->rights->projet->all->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->all->supprimer)))
        {
            $userAccess = 1;
        }
        else if ($this->public && (($mode == 'read' && ! empty($user->rights->projet->lire)) || ($mode == 'write' && ! empty($user->rights->projet->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->supprimer))))
        {
            $userAccess = 1;
        }
        else
		{
            foreach (array('internal', 'external') as $source)
            {
                $userRole = $this->liste_contact(4, $source);
                $num = count($userRole);

                $nblinks = 0;
                while ($nblinks < $num)
                {
                    if ($source == 'internal' && preg_match('/^PROJECT/', $userRole[$nblinks]['code']) && $user->id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }
                    // Permission are supported on users only. To have an external thirdparty contact to see a project, its user must allowed to contacts of projects.
                    /*if ($source == 'external' && preg_match('/PROJECT/', $userRole[$nblinks]['code']) && $user->contact_id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }*/
                    $nblinks++;
                }
            }
            //if (empty($nblinks))	// If nobody has permission, we grant creator
            //{
            //	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
            //	{
            //		$userAccess = 1;
            //	}
            //}
        }

        return ($userAccess?$userAccess:-1);
    }

    public function getTasksRoleForUser($user) {
        $tasksrole = array();

        /* Liste des taches et role sur la tache du user courant dans $tasksrole */
        $sql = "SELECT a.fk_projet_task, a.role";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a";
        $sql .= " WHERE a.fk_user = " . $user->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $row = $this->db->fetch_row($resql);
                $tasksrole[$row[0]] = $row[1];
                $i++;
            }
            $this->db->free();
        } else {
            dol_print_error($this->db);
        }

        return $tasksrole;
    }

					foreach ($tab as $contacttoadd)
					{
						$clone_project->add_contact($contacttoadd['id'], $contacttoadd['code'], $contacttoadd['source'],$notrigger);
						if ($clone_project->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
						{
							$langs->load("errors");
							$this->error.=$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
							$error++;
						}
						else
						{
							if ($clone_project->error!='')
							{
								$this->error.=$clone_project->error;
								$error++;
							}
						}
					}
				}
			}

			//Duplicate file
			if ($clone_project_file)
			{
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $sql = "SELECT t.rowid, t.title, t.fk_task_parent, t.duration_effective";
        $sql .= " FROM " . MAIN_DB_PREFIX . "projet_task as t";
        $sql .= " WHERE t.fk_projet =" . $this->id;
        $sql .= " ORDER BY t.fk_task_parent";

        $var = true;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $tasks[$i]->id = $obj->rowid;
                $tasks[$i]->title = $obj->title;
                $tasks[$i]->fk_parent = $obj->fk_task_parent;
                $tasks[$i]->duration = $obj->duration_effective;
                $i++;
            }
            $this->db->free();
        } else {
            dol_print_error($this->db);
        }

        return $tasks;
    }

    public $workedPercentTime = 0;

    public function getAllStats() {
        $this->aggregateProgressProject();
        $this->getStatsDuration();
        $this->getStatsDurationEffective();
        if ($this->totDuration > 0) {
            if (100 * $this->workedDuration / $this->totDuration < 5) {
                $this->workedPercentTime = preg_replace('/,/', '.', round(100 * $this->workedDuration / $this->totDuration, 2));
            } else {
                $this->workedPercentTime = round(100 * $this->workedDuration / $this->totDuration);
            }
        }
    }

    public $workedPercentQual = 0;
    public $statAvgProgByGroup = array();

			    foreach ($tasksarray as $tasktoclone)
			    {
					$result_clone = $taskstatic->createFromClone($tasktoclone->id,$clone_project_id,$tasktoclone->fk_parent,true,true,false,$clone_task_file,true,false);
					if ($result_clone <= 0)
				    {
				    	$this->error.=$result_clone->error;
						$error++;
				    }
				    else
				    {
				    	$new_task_id=$result_clone;
				    	$taskstatic->fetch($tasktoclone->id);

        return ($this->statAvgProgByGroup[0]);


			    //Parse all clone node to be sure to update new parent
			    $tasksarray=$taskstatic->getTasksArray(0, 0, $clone_project_id, $socid, 0);
			    foreach ($tasksarray as $task_cloned)
			    {
			    	$taskstatic->fetch($task_cloned->id);
			    	if ($taskstatic->fk_task_parent!=0)
			    	{
			    		$taskstatic->fk_task_parent=$tab_conv_child_parent[$taskstatic->fk_task_parent];
			    	}
			    	$res=$taskstatic->update($user,$notrigger);
			    	if ($result_clone <= 0)
				    {
				    	$this->error.=$taskstatic->error;
						$error++;
				    }
			    }
			}



        return ($arr['totalCostForRessources']);
    }

    private function updateArrCoutRessource($curdate, $tmpCout, $tmpCount, $title, $res) {
        $this->arrCostRessourcePerDay[$curdate]['count'] += $tmpCount;
        $this->arrCostRessourcePerDay[$curdate]['cout'] += $tmpCout;
        $this->arrCostAllRessourcePerDay[$curdate]['count'] += $tmpCount;
        $this->arrCostAllRessourcePerDay[$curdate]['cout'] += $tmpCout;
        $this->costByDay["projet"][$this->id]['Ressource'][$curdate]['totalDur']+=$tmpCount;
        $this->costByDay["projet"][$this->id]['Ressource'][$curdate]['totalCostForRessources']+=$tmpCout;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['title'] = $title;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['duration'] += $tmpCount;
        $this->CostRessourceByTask[-1][$res->fk_user_imputation]['totalCostForRessources'] += $tmpCout;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['title'] = $title;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['duration'] += $tmpCount;
        $this->CostRessourceByUser[$res->fk_user_imputation][-1]['totalCostForRessources'] += $tmpCout;
        $this->CostByRessource[$res->id]['duration']+=$tmpCount;
        $this->CostByRessource[$res->id]['totalCostForRessources']+=$tmpCout;
        $this->CostByRessource[$res->id]['nom'] = $res->nom;
    }

    public function costProject() {
        //c'est le cout de toutes les taches + le cout du projet
        //tache
        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "projet_task WHERE fk_projet = " . $this->id;
        $sql = $this->db->query($requete);
        $somme = 0;
        if ($sql) {
            while ($res = $this->db->fetch_object($sql)) {
                $somme += $this->costTask($res->rowid);
            }
        }
        $this->cost["projet"]['totalTask'] = $somme;
        //Projet = frais de projet + frais de resource
        //frais de projet
        $requete = "SELECT sum(montant_ht) as somme
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_frais
                     WHERE fk_task is null
                       AND fk_projet = " . $this->id;
        $sql = $this->db->query($requete);
        $this->cost["projet"]['fraisProjet'] = 0;
        if ($sql) {
            $somme += $res->somme;
            $this->cost["projet"]['fraisProjet'] = $res->somme;
        }
        //Ressource
        $sommeRessource = $this->costProjetRessource();
        $this->cost["projet"]['ressourceGlobale'] = $sommeRessource;
        $this->cost["projet"]['total'] = $somme + $sommeRessource;
    }

    public function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *        \brief      Renvoi le libelle d'un statut donne
     *        \param      statut        id statut
     *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *        \return     string        Libelle
     */
    private function LibStatut($statut = 0, $mode = 1) {
        global $langs;
        $langs->load("project@projet");
        if ($mode == 0) {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1) {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->labelstatut_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0');
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1');
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3');
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5');
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 4) {
            if ($statut == 0)
                return img_picto($langs->trans('ProjectStatusDraft'), 'statut0') . ' ' . $this->labelstatut[$statut];
            if ($statut == 5)
                return img_picto($langs->trans('ProjectStatusPlanning'), 'statut1') . ' ' . $this->labelstatut[$statut];
            if ($statut == 10)
                return img_picto($langs->trans('ProjectStatusRunning'), 'statut3') . ' ' . $this->labelstatut[$statut];
            if ($statut == 50)
                return img_picto($langs->trans('ProjectStatusClose'), 'statut5') . ' ' . $this->labelstatut[$statut];
            if ($statut == 999)
                return img_picto($langs->trans('ProjectStatusWaitingValid'), 'statut8', 'style="vertical-align:middle;"') . ' ' . $this->labelstatut[$statut];
        }
        if ($mode == 5) {
            if ($statut == 0)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusDraftShort'), 'statut0');
            if ($statut == 5)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusPlanningShort'), 'statut1');
            if ($statut == 10)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusRunningShort'), 'statut3');
            if ($statut == 50)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusCloseShort'), 'statut5');
            if ($statut == 999)
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('ProjectStatusWaitingValidShort'), 'statut8', 'style="vertical-align:middle;"');
        }
        if ($mode == 6) {
            if ($statut == 0)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusDraft'), 'statut0');
            if ($statut == 5)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusPlanning'), 'statut1');
            if ($statut == 10)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusRunning'), 'statut3');
            if ($statut == 50)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusClose'), 'statut5');
            if ($statut == 999)
                return $this->labelstatut[$statut] . ' ' . img_picto($langs->trans('ProjectStatusWaitingValid'), 'statut8', 'style="vertical-align:middle;"');
        }
    }

    public function initAsSpecimen() {
        
    }

    public function getNextNumRef($soc) {
        global $db, $langs, $conf;
        $langs->load("project@projet");

        $dir = DOL_DOCUMENT_ROOT . "/core/modules/synopsis_projet";

        if (!empty($conf->global->PROJET_ADDON)) {
            $file = $conf->global->PROJET_ADDON . ".php";

            // Chargement de la classe de numerotation
            $classname = $conf->global->PROJET_ADDON;
            $result = include_once($dir . '/' . $file);
            if ($result) {
                $obj = new $classname();
                $numref = "";
                $numref = $obj->getNextValue($soc, $this);

                if ($numref != "") {
                    return $numref;
                } else {
                    dol_print_error($db, "Projet::getNextNumRef " . $obj->error);
                    return "";
                }
            } else {
                print $langs->trans("Error") . " " . $langs->trans("Error_PROJET_ADDON_NotDefined");
                return "";
            }
        } else {
            print $langs->trans("Error") . " " . $langs->trans("Error_PROJET_ADDON_NotDefined");
            return "";
        }
    }

			if ($to_update)
			{
		    	$result = $task->update($user);
		    	if (!$result)
		    	{
		    		$error++;
		    		$this->error.=$task->error;
		    	}
			}
	    }
	    if ($error!=0)
	    {
	    	return -1;
	    }
	    return $result;
	}

	/**
	 * Clean tasks not linked to an existing parent
	 *
	 * @return	int				Nb of records deleted
	 */
	function clean_orphelins()
	{
		$nb=0;

		// There is orphelins. We clean that
		$listofid=array();

		// Get list of all id in array listofid
		$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task';
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num && $i < 100)
			{
				$obj = $this->db->fetch_object($resql);
				$listofid[]=$obj->rowid;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		if (count($listofid))
		{
			print 'Code asked to check and clean orphelins.';

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET fk_task_parent = 0";
			$sql.= " WHERE fk_task_parent NOT IN (".join(',',$listofid).")";	// So we update only records linked to a non existing parent

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$nb=$this->db->affected_rows($sql);

				if ($nb > 0)
				{
					// Removed orphelins records
					print 'Some orphelins were found and modified to be parent so records are visible again: ';
					print join(',',$listofid);
				}
				
				return $nb;
			}
			else
			{
				return -1;
			}
		}
	}


	 /**
	  *    Associate element to a project
	  *
	  *    @param	string	$TableName			Table of the element to update
	  *    @param	int		$ElementSelectId	Key-rowid of the line of the element to update
	  *    @return	int							1 if OK or < 0 if KO
	  */
	function update_element($TableName, $ElementSelectId)
	{
		$sql="UPDATE ".MAIN_DB_PREFIX.$TableName;

		if ($TableName=="actioncomm")
		{
			$sql.= " SET fk_project=".$this->id;
			$sql.= " WHERE id=".$ElementSelectId;
		}
		else
		{
			$sql.= " SET fk_projet=".$this->id;
			$sql.= " WHERE rowid=".$ElementSelectId;
		}

		dol_syslog(get_class($this)."::update_element sql=" . $sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (!$resql) {
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update_element error : " . $this->error, LOG_ERR);
			return -1;
		}else {
			return 1;
		}

	}
}

class ProjectTask extends commonObject {

    public $db;
    public $id;
    public $fk_projet;
    public $fk_task_parent;
    public $title;
    public $duration_effective;
    public $user_creat;
    public $statut;
    public $note;
    public $progress;
    public $description;
    public $task_type;
    public $color;
    public $url;
    public $priority;
    public $shortDesc;
    public $level;
    public $dateDeb;
    public $dateDebFR;
    public $dateDebFRFull;
    public $projet_id;
    public $labelstatut = array();
    public $labelstatut_short = array();

    public function ProjectTask($db) {
        $this->db = $db;
        global $langs;
        $langs->load("project@projet");
        $this->labelstatut['open'] = $langs->trans("Open");
        $this->labelstatut["closed"] = $langs->trans("Closed");
        $this->labelstatut_short["open"] = $langs->trans("Open");
        $this->labelstatut_short["closed"] = $langs->trans("Closed");
    }

    public function fetch($id) {
        $this->id = $id;
        $requete = "SELECT " . MAIN_DB_PREFIX . "projet_task.fk_projet,
                           " . MAIN_DB_PREFIX . "projet_task.fk_task_parent,
                           " . MAIN_DB_PREFIX . "projet_task.title,
                           " . MAIN_DB_PREFIX . "projet_task.duration_effective,
                           " . MAIN_DB_PREFIX . "projet_task.duration,
                           " . MAIN_DB_PREFIX . "projet_task.fk_user_creat,
                           " . MAIN_DB_PREFIX . "projet_task.fk_statut,
                           " . MAIN_DB_PREFIX . "projet_task.note,
                           " . MAIN_DB_PREFIX . "projet_task.progress,
                           " . MAIN_DB_PREFIX . "projet_task.description,
                           " . MAIN_DB_PREFIX . "projet_task.color,
                           " . MAIN_DB_PREFIX . "projet_task.url,
                           " . MAIN_DB_PREFIX . "projet_task.priority,
                           " . MAIN_DB_PREFIX . "projet_task.shortDesc,
                           " . MAIN_DB_PREFIX . "projet_task.level,
                           " . MAIN_DB_PREFIX . "projet_task.dateDeb,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task_type.label
                      FROM " . MAIN_DB_PREFIX . "projet_task,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task_type
                     WHERE rowid = " . $id . "
                       AND " . MAIN_DB_PREFIX . "Synopsis_projet_task_type.id = " . MAIN_DB_PREFIX . "projet_task.priority";
        $sql = $this->db->query($requete);
        if ($sql) {
            $res = $this->db->fetch_object($sql);

            $this->fk_projet = $res->fk_projet;
            $this->projet_id = $res->fk_projet;
            $tmpProj = new Project($this->db);
            $tmpProj->fetch($res->fk_projet);
            $res->project = $tmpProj;
            $this->dateDeb = strtotime($res->dateDeb);
            $this->dateDebFR = date('d/m/Y', strtotime($res->dateDeb));
            $this->dateDebFRFull = date('d/m/Y H:i', strtotime($res->dateDeb));
            $this->fk_task_parent = $res->fk_task_parent;
            $this->title = $res->title;
            $this->duration_effective = $res->duration_effective;
            $this->duration = $res->duration;
            $this->fk_user_creat = $res->fk_user_creat;
            $tmpUser = new User($this->db);
            $tmpUser->fetch($res->fk_user_creat);
            $this->user_creat = $tmpUser;
            $this->statut = $res->statut;
            $this->note = $res->note;
            $this->progress = $res->progress;
            $this->description = $res->description;
            $this->color = $res->color;
            $this->url = $res->url;
            $this->priority = $res->priority;
            $this->task_type = $res->label;
            $this->shortDesc = $res->shortDesc;
            $this->level = $res->level;
            return($this->id);
        } else {
            return -1;
        }
    }

    public $childArray = array();

    public function getChildsTree() {
        $requete = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "projet_task
                     WHERE " . MAIN_DB_PREFIX . "projet_task.fk_task_parent = " . $this->id;
        $sql = $this->db->query($requete);
        $count = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->childArray, $res->rowid);
            $this->recursTask($res->rowid);
        }
    }

    public $directChildArray = array();

    public function getDirectChildsTree() {
        $requete = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "projet_task
                     WHERE " . MAIN_DB_PREFIX . "projet_task.fk_task_parent = " . $this->id;
        $sql = $this->db->query($requete);
        $count = $this->db->num_rows($sql);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->directChildArray, $res->rowid);
        }
    }

    private function recursTask($parentId, $js) {
        $requete = "SELECT " . MAIN_DB_PREFIX . "projet_task.rowid
                      FROM " . MAIN_DB_PREFIX . "projet_task
                     WHERE " . MAIN_DB_PREFIX . "projet_task.fk_task_parent = " . $parentId;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql)) {
            array_push($this->childArray, $res->rowid);
            $this->recursTask($res->rowid);
        }
    }

    /**
     *        \brief      Renvoie nom clicable (avec eventuellement le picto)
     *        \param        withpicto        Inclut le picto dans le lien
     *        \param        option            Sur quoi pointe le lien
     *        \param        maxlen            Longueur max libelle
     *        \return        string            Chaine avec URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0) {
        global $langs;

        $result = '';
        $lien = "";
        $lien = '<a href="' . DOL_URL_ROOT . '/projet/tasks/task.php?id=' . $this->id . '">';
        $lienfin = '</a>';

        if ($withpicto)
            $result.=($lien . img_object($langs->trans("ShowProject") . ': ' . $this->title, 'task') . $lienfin . ' ');
        $result.=$lien . ($maxlen ? dol_trunc($this->title, $maxlen) : $this->title) . $lienfin;
        return $result;
    }

    public function getLibStatut($mode = 0) {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *        \brief      Renvoi le libelle d'un statut donne
     *        \param      statut        id statut
     *        \param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *        \return     string        Libelle
     */
    private function LibStatut($statut = 0, $mode = 1) {
        global $langs;
        $langs->load("project@projet");
        if ($mode == 0) {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1) {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4') . ' ' . $this->labelstatut_short[$statut];
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5') . ' ' . $this->labelstatut_short[$statut];
        }
        if ($mode == 3) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4');
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5');
        }
        if ($mode == 4) {
            if ($statut == 'open')
                return img_picto($langs->trans('Open'), 'statut4') . ' ' . $this->labelstatut[$statut];
            if ($statut == 'closed')
                return img_picto($langs->trans('Close'), 'statut5') . ' ' . $this->labelstatut[$statut];
        }
        if ($mode == 5) {
            if ($statut == 'open')
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('Open'), 'statut4');
            if ($statut == 'closed')
                return $this->labelstatut_short[$statut] . ' ' . img_picto($langs->trans('Close'), 'statut5');
        }
    }

}

class Projet extends Project {
    
}
?>
