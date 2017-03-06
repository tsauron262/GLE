<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/triggers/interface_modNdfp_NdfpWorkflow.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 *	\version	$Id: interface_modNdfp_NdfpWorkflow.class.php,v 1.36 2011/07/31 23:29:46 eldy Exp $
 */


/**
 *	\class      InterfaceNdfpWorkflow
 *  \brief      Class of triggered functions for ndfp module
 */
class InterfaceNdfpWorkflow
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   Constructor.
     *   @param      DB      Database handler
     */
    function __construct($DB)
    {
        $this->db = $DB;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "agenda";
        $this->description = "Triggers of ndfp module add actions in agenda according to setup made in agenda setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'ndfp@ndfp';
    }

    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/includes/triggers
     *
     *      @param      action      Event code (COMPANY_CREATE, PROPAL_VALIDATE, ...)
     *      @param      object      Object action is done on
     *      @param      user        Object user
     *      @param      langs       Object langs
     *      @param      conf        Object conf
     *      @return     int         <0 if KO, 0 if no action are done, >0 if OK
     */
    function run_trigger($action, $object, $user, $langs, $conf)
    {

        if (empty($conf->agenda->enabled)) return 0;     // Module not active, we do nothing

		$ok = 0;
        $langs->load("other");
        $langs->load("ndfp");
        $langs->load("agenda");
            
		// Actions
		if ($action == 'NDFP_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$object->actiontypecode = 'AC_OTH';
            $object->actionmsg2 = $langs->transnoentities("NdfpValidatedInDolibarr",$object->ref);
            $object->actionmsg = $langs->transnoentities("NdfpValidatedInDolibarr",$object->ref);
            $object->actionmsg.= "\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
        elseif ($action == 'NDFP_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);


            $object->actiontypecode = 'AC_EMAIL';
            $object->actionmsg2 = $langs->transnoentities("NdfpSentByEMail",$object->ref);
            $object->actionmsg = $langs->transnoentities("NdfpSentByEMail",$object->ref);
            $object->actionmsg.= "\n".$langs->transnoentities("Author").': '.$user->login;
            $object->sendtoid=0;
            
            $ok=1;
		}
		elseif ($action == 'NDFP_PAID')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$object->actiontypecode = 'AC_OTH';
            $object->actionmsg2 = $langs->transnoentities("NdfpPaidInDolibarr",$object->ref);
            $object->actionmsg = $langs->transnoentities("NdfpPaidInDolibarr",$object->ref);
            $object->actionmsg.= "\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'NDFP_CANCEL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);


			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("NdfpCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("NdfpCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$ok=1;
		}


        // Add entry in event table
        if ($ok)
        {
			$now=dol_now();

            require_once(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
            require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			$contactforaction=new Contact($this->db);
            $societeforaction=new Societe($this->db);
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
            if ($object->fk_soc > 0)    $societeforaction->fetch($object->fk_soc);

			// Insertion action
			require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			//$actioncomm->usertodo  = $user;	// User affected to action
			$actioncomm->userdone    = $user;	// User doing action

			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;

			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				return 1;
			}
			else
			{
                $error ="Failed to insert : ".$actioncomm->error." ";
                $this->error=$error;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
                return -1;
			}
		}

		return 0;
    }

}
?>
