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
 *      \file       htdocs/ndfp/class/ndfp.class.php
 *      \ingroup    ndfp
 *      \brief      File of class to manage trips and working credit notes
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/ndfp/modules_ndfp.php');
/**
 *      \class      Ndfp
 *      \brief      Class to manage trips and working credit notes
 */
class Ndfp extends CommonObject
{
	var $db;
	var $error;
	var $element = 'ndfp';
	var $table_element = 'ndfp';
	var $table_element_line = 'ndfp_det';
	var $fk_element = 'fk_ndfp';
	var $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    
	var $id;
    
    var $cur_iso = 'EUR';
    var $ref;
    var $entity;
	var $datec;        
    var $tms;			
    var $dates = 0;	
    var $datee = 0;	    		
    var $datef = 0;			
	var $fk_user_author;
	var $fk_user = 0;
    //! 0=draft,
    //! 1=validated (need to be paid),
    //! 2=classified paid completely,
    //! 3=classified abandoned and no payment done
    var $statut;
    
    var $fk_soc = 0;
    var $fk_project = 0;
    
    var $fk_cat = 0;

    var $total_tva = 0;	
    var $total_ht = 0;	    		
    var $total_ttc = 0;	
    
    var $description;        
	var $comment_user;
	var $comment_admin;

	var $model_pdf;

    
    var $specimen = 0;
    var $lines = array();
    var $line;

   /**
	*  \brief  Constructeur de la classe
	*  @param  DB          handler acces base de donnees
	*/
	function Ndfp($DB)
	{
		$this->db = $DB;
	}

   /**
	*  \brief  Execute action
	*  @param  action      action to execute
    *  @param  args        arguments array
    *  @return int         <0 if KO, >0 if OK
	*/
    function call($action, $args) 
    { 
        global $langs;
        
        if (empty($action))
        {
            return 0;
        }
        
        if(method_exists($this, $action)) 
        { 
            $result = call_user_func_array(array($this, $action), $args);

            return $result;            
        }
        else
        { 
            //$this->error = $langs->trans('ActionDoesNotExist');
            return 0;
        } 
    }
     
	/**
	 * \brief Check parameters prior inserting or updating the DB
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
     function check_parameters()
     {
		global $conf, $langs;
        
        // Check parameters
        if (empty($this->ref)){
			$this->error = $langs->trans('ErrorBadParameterRef');
			return -1;            
        }
                        
        if (empty($this->datee) || empty($this->dates)){
			$this->error = $langs->trans('ErrorBadParameterDates');
			return -1;            
        }
                
        if ($this->datee < $this->dates){
			$this->error = $langs->trans('ErrorBadParameterDateDoNotMatch');
			return -1;            
        }
        
        if ($this->fk_user <= 0){
			$this->error = $langs->trans('ErrorBadParameterUser');
			return -1;            
        }

        if ($this->fk_cat <= 0){
			$this->error = $langs->trans('ErrorBadParameterCat');
			return -1;            
        }
        

        if ($this->fk_project < 0){
			$this->error = $langs->trans('ErrorBadParameterProject');
			return -1;            
        }
                

        if ($this->total_ttc < 0){
			$this->error = $langs->trans('ErrorBadParameterTTCTotal');
			return -1;            
        }
        
        if ($this->total_tva < 0){
			$this->error = $langs->trans('ErrorBadParameterTVATotal');
			return -1;            
        } 
        
        if ($this->total_ht < 0){
			$this->error = $langs->trans('ErrorBadParameterHTTotal');
			return -1;            
        } 

        if (empty($this->cur_iso)){
			$this->error = $langs->trans('ErrorBadParameterCurrency');
			return -1;            
        } 
                
        return 1;               
     }
     
	/**
	 * \brief Check user permissions
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function check_user_rights($user, $action = 'create')
    {
        global $langs;

                
        if ($this->id > 0)
        {
            if ($this->fk_user_author == $user->id)
            {
                if ($user->rights->ndfp->myactions->$action)
                {
                    return 1;            
                }                
            }
            else
            {
                if (!$user->rights->ndfp->allactions->$action)
                {
                    $this->error = $langs->trans('NotEnoughPermissions');
                    return -1;           
                }                 
            }                        
        }
        else
        {
            if ($user->rights->ndfp->myactions->$action)
            {
                return 1;            
            }             
        }
        

        if ($user->rights->ndfp->allactions->$action)
        {
            return 1;            
        }            
        
        
        $this->error = $langs->trans('NotEnoughPermissions');
        return -1;
    }     
	/**
	 * Create object in database
	 *
	 * @param 	$user	User that creates
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf, $langs;
        
        $result = $this->check_parameters();
        
        if ($result < 0){
            return -1;
        }             

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
                           
        $this->comment_admin = ($user->admin ? $this->comment_admin : '');
        $this->statut = 0;//Enforce draft
        $this->datec = dol_now();
        $this->fk_user_author = $user->id;   
        $this->datef = dol_now();
        $this->tms = dol_now();
                      
        $this->db->begin();
        
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ndfp (";
        $sql.= "`ref`";
        $sql.= ", `cur_iso`";        
        $sql.= ", `entity`";
        $sql.= ", `datec`";
        $sql.= ", `dates`";
        $sql.= ", `datee`";        
        $sql.= ", `datef`";
        $sql.= ", `fk_user_author`";
		$sql.= ", `fk_user`";
        $sql.= ", `statut`";
        $sql.= ", `fk_soc`";
        $sql.= ", `fk_project`";        
        $sql.= ", `fk_cat`";
        $sql.= ", `total_tva`";
        $sql.= ", `total_ht`";
        $sql.= ", `total_ttc`";                        
        $sql.= ", `description`";
        $sql.= ", `comment_user`";
        $sql.= ", `comment_admin`";
        $sql.= ", `model_pdf`";
        $sql.= ", `tms`";         
        $sql.= ") ";
        $sql.= " VALUES (";
		$sql.= " '".$this->ref."' ";
		$sql.= ", '".$this->cur_iso."' ";        
		$sql.= ", '".$conf->entity."' ";        
        $sql.= ", '".$this->db->idate($this->datec)."'";
        $sql.= ", '".$this->db->idate($this->dates)."'";
        $sql.= ", '".$this->db->idate($this->datee)."'";
        $sql.= ", '".$this->db->idate($this->datef)."'";         
		$sql.= ", ".$this->fk_user_author;
		$sql.= ", ".$this->fk_user;
		$sql.= ", ".$this->statut;
		$sql.= ", ".$this->fk_soc;
        $sql.= ", ".$this->fk_project;         
		$sql.= ", ".$this->fk_cat;
		$sql.= ", ".$this->total_tva; 
		$sql.= ", ".$this->total_ht; 
		$sql.= ", ".$this->total_ttc;                                             
		$sql.= ", ".($this->description ? "'".$this->db->escape($this->description)."'" : "''");
		$sql.= ", ".($this->comment_user ? "'".$this->db->escape($this->comment_user)."'" : "''");
		$sql.= ", ".($this->comment_admin ? "'".$this->db->escape($this->comment_admin)."'" : "''");
		$sql.= ", '".$this->db->escape($this->model_pdf)."'";
        $sql.= ", '".$this->db->idate($this->tms)."'";                  
		$sql.= ")";

		dol_syslog("Ndfp::create sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ndfp");
            $this->ref = '(PROV'.$this->id.')';
            
			$result = $this->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}

	}

    /**
     *		\brief Load an object from its id and create a new one in database
     *		@param      fromid     		Id of object to clone
     *      @param      user     		User who clones
     * 	 	@return		int				New id of clone
     */
    function create_from_clone($fromid, $user)
    {
        global $conf, $langs;

        // Load new object
        $object = new Ndfp($this->db);
        $object->fetch($fromid);

        $object->id = 0;

        // Clear fields
        $object->ref = 'PROV';			
    	$object->fk_user_author = $user->id;
        
    

        $object->lines = array();

        // Create clone
        $result = $object->create($user);

        if ($result < 0)
        {
            return -1;
        }

        // Add lines
        foreach($this->lines AS $line)
        {           
            // Insert line
            $this->line  = new NdfpLine($this->db);
            
            $this->line->fk_ndfp        = $object->id;
            
            $this->line->label          = $line->label;
            $this->line->qty            = $line->qty; 
            $this->line->dated          = $line->dated;
            $this->line->datef          = $line->datef;
            
            $this->line->fk_user_author = $user->id;
            $this->line->fk_tva         = $line->fk_tva;
            $this->line->fk_exp         = $line->fk_exp;

            $this->line->total_ht       = $line->total_ht;  
            $this->line->total_ttc      = $line->total_ttc;


            $result = $this->line->insert();
            
            if ($result < 0)
            {
                $this->error = $this->line->error;
                
                return -2;
            }          
        }

        $result = $this->fetch($object->id);
        
        if ($result > 0)
        {
            $this->generate_pdf($user);
            
            // Triggers call
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('NDFP_CLONE', $this, $user, $langs, $conf);
            
            if ($result < 0) 
            { 
                $this->error = $langs->trans('ErrorCallingTrigger');
                return -1;
            }
                    
       	    $this->error = $langs->trans('ExpAdded');
                
            return $object->id;
                                
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            
            return -1;                
        }
    }    
    
	/**
	 * Add object in database
	 *
	 * @param 	$user	User that adds
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function add($user)
	{
	    global $conf, $langs;

        $fk_user = GETPOST('fk_user');
        $fk_soc = GETPOST('fk_soc');
        $fk_cat = GETPOST('fk_cat');
        $fk_project = ($conf->projet->enabled ? GETPOST('fk_project') : 0);    
        
        $model = GETPOST('model');
        $currency = GETPOST('currency');
        $description = trim(GETPOST('description', 'chaine'));
        $note_public = trim(GETPOST('comment_user', 'chaine'));
        $note = trim(GETPOST('comment_admin', 'chaine'));
               
        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
               
        $start_date = dol_mktime(12, 0 , 0, $_POST['dmonth'], $_POST['dday'], $_POST['dyear']);
        $end_date = dol_mktime(12, 0 , 0, $_POST['fmonth'], $_POST['fday'], $_POST['fyear']);
        
        $this->ref            = '(PROV)';
        $this->cur_iso        = $currency;
        
        $this->entity         = $conf->entity;
        $this->dates          = $start_date;
        $this->datee          = $end_date;
        
        $this->fk_user        = $fk_user;
        $this->fk_soc         = $fk_soc;
        $this->fk_project     = $fk_project;
        
        $this->fk_cat        = $fk_cat;
        
        $this->total_tva      = 0;
        $this->total_ht      = 0;
        $this->total_ttc      = 0;
        
        $this->description      = $description;               
        $this->comment_user	    = $note_public;
        $this->comment_admin    = $note;
        
        $this->model_pdf        = $model;
        $this->statut      = 0;  
        
        $result = $this->check_parameters();          

    
        if ($result < 0)
        {
            return -1;
        }
            
        
        $id = $this->create($user);     
        
        if ($id > 0)
        {
            $this->fetch($id);
            $this->error = $langs->trans('NdfpHasBeenAdded');
            return 1;
        }
        else
        {
            //$this->error = $langs->trans('ErrorAddingNdfp');
            return -1;
        }	   
    }
    
	/**
	 * Update object in database
	 *
	 * @param 	$user	User that updates
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function update($user)
	{
		global $langs;
       
		// Check parameters
        $result = $this->check_parameters(); 
        
        if ($result < 0)
        {
            return -1;
        }

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        }

        $result = $this->check_user_rights($user, 'create');
        
        if ($result < 0)
        {
            return -1;
        }
                        
		$this->db->begin();
        $this->tms = dol_now();
        
		$sql = "UPDATE ".MAIN_DB_PREFIX."ndfp ";
		$sql .= "SET `ref` = '".$this->ref."'";		 
		$sql .= ", `dates` = '".$this->db->idate($this->dates)."'";
		$sql .= ", `datee` = '".$this->db->idate($this->datee)."'";                      
		$sql .= ", `fk_user` = ".$this->fk_user;     
        $sql .= ", `statut` = ".$this->statut;
        $sql .= ", `fk_soc` = ".$this->fk_soc;
        $sql .= ", `fk_project` = ".$this->fk_project;
		$sql .= ", `fk_cat` = ".$this->fk_cat;
		$sql .= ", `total_tva` = ".$this->total_tva;
        $sql .= ", `total_ht` = ".$this->total_ht;
        $sql .= ", `total_ttc` = ".$this->total_ttc;                            
		$sql .= ", `description` = ".($this->description ? "'".$this->db->escape($this->description)."'" : "''");
		$sql .= ", `comment_user` = ".($this->comment_user ? "'".$this->db->escape($this->comment_user)."'" : "''");
		$sql .= ", `comment_admin` = ".($this->comment_admin ? "'".$this->db->escape($this->comment_admin)."'" : "''");        
        $sql .= ", `tms` = '".$this->db->idate($this->tms)."'";   
        $sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Ndfp::update sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->db->commit();
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	/**
	 * 	\brief Update timestamp of note
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function update_tms()
	{
		global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        }
        

        // Update timestamp of ndfp
        $tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET tms = '".$this->db->idate($tms)."' WHERE rowid = ".$this->id;       
        dol_syslog("Ndfp::update_tms sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result > 0)
        {
            $this->tms = $tms;
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;            
            return -1;
        }
                
	}
    
	/**
	 * Fetch object from database
	 *
	 * @param 	id	    Id of the note
     * @param 	ref  	Reference of the note
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function fetch($id, $ref = '')
	{
	    global $conf, $langs;
       
        if (!$id && empty($ref)){
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        }
        
		$sql = "SELECT `rowid`, `cur_iso`, `ref`, `entity`, `datec`, `dates`, `datee`, `datef`, `fk_user_author`, `fk_user`, `fk_soc`, `fk_project`, `statut`";
        $sql.= ", `fk_cat`, `total_tva`, `total_ht`, `total_ttc`, `description`, `comment_user`, `comment_admin`, `model_pdf`, `tms` ";
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp";
        $sql.= " WHERE entity = ".$conf->entity;
        if ($id)   $sql.= " AND rowid = ".$id;
        if ($ref)  $sql.= " AND ref = '".$this->db->escape($ref)."'";
        
		dol_syslog("Ndfp::fetch sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
		if ($result > 0)
		{
            
			$obj = $this->db->fetch_object($result);

			$this->id                = $obj->rowid;
			$this->ref               = $obj->ref;
            $this->cur_iso           = $obj->cur_iso;
			$this->entity            = $obj->entity;            
			$this->datec             = $this->db->jdate($obj->datec);
			$this->dates             = $this->db->jdate($obj->dates);
            $this->datee             = $this->db->jdate($obj->datee);
			$this->datef             = $this->db->jdate($obj->datef);                        
			$this->fk_user_author    = $obj->fk_user_author;
			$this->fk_user           = $obj->fk_user;
            
            $this->fk_soc            = $obj->fk_soc;
            $this->fk_project        = $obj->fk_project;
            
			$this->statut            = $obj->statut;
			$this->fk_cat            = $obj->fk_cat;
            
            $this->total_tva           = $obj->total_tva;
            $this->total_ht           = $obj->total_ht;
            $this->total_ttc           = $obj->total_ttc;
            
			$this->description       = trim($obj->description);            
			$this->comment_user      = $obj->comment_user;
			$this->comment_admin     = $obj->comment_admin;
            
            $this->tms               = $this->db->jdate($obj->tms);
            
            if ($this->id){
                $result = $this->fetch_lines();
                
                if ($result > 0){
                    return 1;
                }else{
                    return -1;
                }                
            }            			
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	/**
	 * Fetch object lines from database
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function fetch_lines()
	{
	    global $conf, $langs;

        if (!$this->id){
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        }
         
        $this->lines = array();
               
		$sql = "SELECT  nd.rowid, nd.fk_ndfp, nd.label, nd.datec, nd.dated, nd.datef, nd.fk_user_author, nd.fk_exp,";
        $sql.= " nd.fk_tva, nd.qty, nd.total_ht, nd.total_ttc, nd.total_tva, nd.tms, e.label, e.code, t.taux";
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp_det AS nd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp AS e ON e.rowid = nd.fk_exp";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva AS t ON t.rowid = nd.fk_tva";                        
        $sql.= " WHERE nd.fk_ndfp = ".$this->id;
        
		dol_syslog("Ndfp::fetch_lines sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
		if ($result > 0)
		{
            $num = $this->db->num_rows($result);
            $i = 0;
            
            if ($num){
                while ($i < $num){
        			$obj = $this->db->fetch_object($result);
        			$this->lines[$i]           = $obj;

                    $this->lines[$i]->dated	    = $this->db->jdate($obj->dated);
                    $this->lines[$i]->datef		= $this->db->jdate($obj->datef);
                    $this->lines[$i]->datec		= $this->db->jdate($obj->datec);
                
                    $this->lines[$i]->tms		= $this->db->jdate($obj->tms);
                                    
                    $i++;                     
                }      
            }
          
			return 1;
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

        
	/**
	 * Delete object from database
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function delete($user)
	{
	   
	    global $conf, $langs;

        $result = $this->check_user_rights($user, 'delete');
        if ($result < 0)
        {
            return -1;
        }

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        }          
                     	   
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ndfp WHERE rowid = ".$this->id;

		dol_syslog("Ndfp::delete sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result)
		{
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm WHERE fk_element = ".$this->id." AND elementtype = 'ndfp'";
            
    		dol_syslog("Ndfp::delete sql=".$sql, LOG_DEBUG);
            
    		$result = $this->db->query($sql);
    		if ($result)
    		{
                $this->error = $langs->trans('NdfpHasBeenDeleted');
    			return 1;
    		}
    		else
    		{
    			$this->error = $this->db->error()." sql=".$sql;
    			return -1;
    		}
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}
    
	/**
	 * Validate object 
	 *
	 * @param 	user	User who validates
     * @param 	force_number	Set the new reference
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function validate($user, $force_number='')
    {
        global $conf,$langs;
        
        $result = $this->check_user_rights($user, 'validate');
        if ($result < 0)
        {
            return -1;
        }
 
        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        // Protection
        if ($this->statut != 0){
            $this->error = $langs->trans('NotADraft');
            return -1;
        }

        $this->db->begin();

        // Define new ref
        if ($force_number)
        {
            $num = $force_number;
        }
        else if (preg_match('/^[\(]?PROV/i', $this->ref))
        {           
            $num = $this->get_next_num_ref($this->fk_soc);
        }
        else
        {
            $num = $this->ref;
        }

        if ($num)
        {
            // Validate
            $this->tms = dol_now();
            
            $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp";
            $sql.= " SET ref='".$num."', statut = 1, tms = '".$this->db->idate($this->tms)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("Ndfp::validate sql=".$sql);
            $resql = $this->db->query($sql);
            if (!$resql)
            {
                $this->error = $this->db->error()." sql=".$sql;
                $this->db->rollback();
                return -1;
            }


        	$this->oldref = '';


            if (preg_match('/^[\(]?PROV/i', $this->ref))
            {

                $ndfpref = dol_sanitizeFileName($this->ref);
                $snumndfp = dol_sanitizeFileName($num);
                
                $dirsource = $conf->ndfp->dir_output.'/'.$ndfpref;
                $dirdest = $conf->ndfp->dir_output.'/'.$snumndfp;
                
                if (file_exists($dirsource))
                {
                    dol_syslog("Ndfp::validate rename dir ".$dirsource." into ".$dirdest);

                    if (@rename($dirsource, $dirdest))
                    {
                    	$this->oldref = $ndfpref;

                        dol_syslog("Rename ok");
                        dol_delete_file($conf->ndfp->dir_output.'/'.$snumndfp.'/'.$ndfpref.'.*');
                    }
                }
            }

        	$this->ref = $num;
            $this->statut = 1;

            // Trigger calls
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('NDFP_VALIDATE',$this,$user,$langs,$conf);
            if ($result < 0) { 
                $this->error = $langs->trans('ErrorCallingTrigger');
                $this->db->rollback();
                return -1;
            }

        }
        else
        {
            $this->error = $langs->trans('CanNotDetermineNewReference');
            $this->db->rollback();
            return -1;
        }


        $this->db->commit();
        $this->error = $langs->trans('NdfpHasBeenValidated');
        return 1;
    }

    /**
     *	\brief      Close the note
     *	@param      user        User who closes
     *	@return     int         <0 si ok, >0 si ok
     */
    function set_canceled($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $this->db->begin();

        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET statut = 3, tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;
        
        dol_syslog("Ndfp::set_canceled sql=".$sql, LOG_DEBUG);
        
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->statut = 3;
            
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('NDFP_CANCEL',$this,$user,$langs,$conf);
            
            if ($result < 0) 
            { 
                $this->error = $langs->trans('ErrorCallingTrigger');
                $this->db->rollback();
                return -1;
            }

            $this->db->commit();
            $this->error = $langs->trans('NdfpHasBeenCanceled');
            return 1;

        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -2;
        }
    }
    
    /**
     *		\brief		Set draft status
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function set_draft($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        if ($this->statut == 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'unvalidate');
        if ($result < 0)
        {
            return -1;
        }
        
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET statut = 0,  tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;
        
        dol_syslog("Ndfp::set_draft sql=".$sql, LOG_DEBUG);
        
        $resql = $this->db->query($sql);
        
        if ($resql > 0)
        {
            $this->statut = 0;
            $this->error = $langs->trans('NdfpHasBeenModified');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }

    }

    /**
     *		\brief		Set vehicule category
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setcat($user)
    {
        global $conf, $langs;

        $fk_cat = GETPOST('fk_cat');

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
        
        if ($fk_cat < 0)
        {
			$this->error = $langs->trans('ErrorBadParameterCat');
			return -1;            
        }
        
        $this->tms = dol_now();
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET fk_cat = ".$this->db->escape($fk_cat).", tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;
        
        dol_syslog("Ndfp::setcat sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->fk_cat = $fk_cat;
            
            // Update line for KM expense (if exists)
            foreach($this->lines AS $line)
            {
                if ($line->code == 'EX_KME')
                {  
                    $tvaobj = $this->get_tva($line->fk_tva);
                    $tva = price2num($tvaobj->taux);
                    
                    $total_ttc = $this->compute_total_km($this->fk_cat, $line->qty, $tva);
                     // Compute total HT
                    $total_ht = $total_ttc/(1 + $tva/100);  
                    
                    // Insert line
                    $this->line  = new NdfpLine($this->db);
                    
                    $this->line->rowid          = $line->rowid;
                    $this->line->fk_ndfp        = $line->fk_ndfp;
                    $this->line->label          = $line->label;
                    $this->line->qty            = $line->qty; 
                    $this->line->dated          = $line->dated;
                    $this->line->datef          = $line->datef;
                    
                    $this->line->fk_user_author = $line->fk_user_author;
                    $this->line->fk_tva         = $line->fk_tva;
                    $this->line->fk_exp         = $line->fk_exp;
        
                    $this->line->total_ht       = $total_ht;  
                    $this->line->total_ttc      = $total_ttc;
        
        
                    $result = $this->line->update();
                    
                    if ($result > 0)
                    {
        
                        $result = $this->update_totals();
                                        
                        if ($result < 0)
                        {
                            $this->error = $this->line->error;                           
                            return -1;
                        }
                    }
                    else
                    {
                        $this->error = $this->line->error;                        
                        return -2;
                    }                                                                       
                }
            }
            
            $this->generate_pdf($user);
            
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }
    
    /**
     *		\brief		Set client
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function set_thirdparty($user)
    {
        global $conf, $langs;

        $fk_soc = GETPOST('fk_soc');

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
        
        if ($fk_soc < 0){
			$this->error = $langs->trans('ErrorBadParameterCat');
			return -1;            
        }
        
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET fk_soc = ".$this->db->escape($fk_soc).", tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;
        
        dol_syslog("Ndfp::set_thirdparty sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->fk_soc = $fk_soc;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }    

    /**
     *		\brief		Set user
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setfk_user($user)
    {
        global $conf, $langs;
        
        $fk_user = GETPOST('fk_user');

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('NotADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        
        if ($fk_user < 0){
			$this->error = $langs->trans('ErrorBadParameterUser');
			return -1;            
        }
        
        $this->tms = dol_now();
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET fk_user = ".$this->db->escape($fk_user).", tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        
        dol_syslog("Ndfp::setfk_user sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result > 0)
        {
            $this->fk_user = $fk_user;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }

    /**
     *		\brief		Set comments (user and admin if enough rights)
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setcomments($user)
    {
        global $conf, $langs;

        $comment_user = trim(GETPOST('comment_user', 'chaine'));
        $comment_admin = trim(GETPOST('comment_admin', 'chaine'));

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('NotADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET comment_user = '".$this->db->escape($comment_user)."', ";
        $sql.= ($user->admin ? "comment_admin = '".$this->db->escape($comment_admin)."', " : "")." tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        
        dol_syslog("Ndfp::setcomments sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);        
        
        if ($result > 0)
        {
            $this->comment_user = $comment_user;
            $this->comment_admin = $comment_admin;
            
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }
    
    /**
     *		\brief		Set description
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setdesc($user)
    {
        global $conf, $langs;

        $description = trim(GETPOST('description', 'chaine'));

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('NotADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET description = '".$this->db->escape($description)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        dol_syslog("Ndfp::setdesc sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);        
        
        if ($result > 0)
        {
            $this->description = $description;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }

    /**
     *		\brief		Set project
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function classin($user)
    {
        global $conf, $langs;

        $fk_project = ($conf->projet->enabled ? GETPOST('fk_project') : 0);

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        $this->tms = dol_now();
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET fk_project = ".$this->db->escape($fk_project).", tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        dol_syslog("Ndfp::classin sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->fk_project = $fk_project;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }


    /**
     *		\brief		Set date start
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setdates($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        $dates = dol_mktime(12,0,0, $_POST['datesmonth'], $_POST['datesday'], $_POST['datesyear']);
        if ($this->datee < $dates)
        {
			$this->error = $langs->trans('ErrorBadParameterDateDoNotMatch');
			return -1;            
        }
                
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET `dates` = '".$this->db->idate($dates)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        dol_syslog("Ndfp::setdates sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result > 0)
        {
            $this->dates = $dates;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->db->rollback();
            
            return -1;
        }
    }
    
    /**
     *		\brief		Set date end
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function setdatee($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut != 0)
        {
            $this->error = $langs->trans('AlreadyADraft');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }

        $datee = dol_mktime(12,0,0,$_POST['dateemonth'],$_POST['dateeday'],$_POST['dateeyear']);
        
        if ($datee < $this->dates)
        {
			$this->error = $langs->trans('ErrorBadParameterDateDoNotMatch');
			return -1;            
        }
                
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET `datee` = '".$this->db->idate($datee)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;       
        dol_syslog("Ndfp::setdates sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result > 0)
        {
            $this->datee = $datee;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            return 1;
        }
        else
        {
            $this->db->rollback();
            
            return -1;
        }
    }

    /**
     *      \brief                  Check if the note can be changed to unpaid and do it if so
     *      @param      user        Object user
     *      @return     int         <0 si ko, >0 si ok
     */
    function reopen($user)
    {
        global $conf, $langs;


        $result = $this->set_unpaid($user);
            
        return $result;
    }
    
    /**
     *      \brief                  Check if the note can be changed to paid and do it if so
     *      @param      user        Object user
     *      @return     int         <0 si ko, >0 si ok
     */
    function confirm_paid($user)
    {
        global $conf, $langs;

        $confirm = GETPOST('confirm');
        
        if ($confirm == 'yes')
        {
            $already_paid  = $this->get_amount_payments_done();
            $remain_to_pay = price2num($this->total_ttc - $already_paid, 'MT'); 
            
            if ($remain_to_pay == 0)
            {
                $result = $this->set_paid($user);
                
                if ($result > 0)
                {
                    $this->error = $langs->trans('NdfpHasBeenUpdated');
                }
                
                return $result;
            }
            else
            {
                $this->error = $langs->trans('CanNotSetToPaid');
                return -1;  
            }  
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }
        

    }
    /**
     *      \brief                  Modify note to paid
     *      @param      user        Object user
     *      @return     int         <0 si ko, >0 si ok
     */
    function set_paid($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
        
        $this->db->begin();

        $this->tms = dol_now();
        $sql = "UPDATE ".MAIN_DB_PREFIX."ndfp SET statut = 2, tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;

        dol_syslog("Ndfp::set_paid sql=".$sql);
        $result = $this->db->query($sql);
        
        if ($result)
        {
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('NDFP_PAID',$this, $user, $langs, $conf);
            if ($result < 0) 
            { 
                $this->error = $langs->trans('ErrorCallingTrigger');
                $this->db->rollback();
                return -1;
            }
            
            $this->db->commit();
            
            $this->statut = 2;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }

    /**
     *      \brief                  Modify note to unpaid
     *      @param      user        Object user
     *      @return     int         <0 si ko, >0 si ok
     */
    function set_unpaid($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }
        
        if ($this->statut == 0)
        {
            $this->error = $langs->trans('NdfpIsDraftAndCanNotBeUnpaid');
            return -1;
        }
        
        $this->db->begin();
        $this->tms = dol_now();
        
        $sql = "UPDATE ".MAIN_DB_PREFIX."ndfp SET statut = 1, tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;

        dol_syslog("Ndfp::set_unpaid sql=".$sql);
        $result = $this->db->query($sql);
        
        if ($result)
        {
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface = new Interfaces($this->db);
            $result = $interface->run_triggers('NDFP_UNPAID',$this, $user, $langs, $conf);
            if ($result < 0) 
            { 
                $this->error = $langs->trans('ErrorCallingTrigger');
                $this->db->rollback();
                return -1;
            }
            
            $this->db->commit();
            
            $this->statut = 1;
            $this->error = $langs->trans('NdfpHasBeenUpdated');
            
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -1;
        }
    }
        
    /**
     *		\brief		Build PDF
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function builddoc($user)
    {
        global $conf, $langs;

        if (GETPOST('model'))
        {
            $this->setDocModel($user, GETPOST('model'));
        }
        
        $result = $this->generate_pdf($user);
        
        return $result;
    }

    /**
     *		\brief		Confirm delete line
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_deleteline($user)
    {
        
        global $conf, $langs;

        $lineid = GETPOST('lineid');
        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'delete');
        
        if ($result < 0)
        {
            return -1;
        }
        
        if ($confirm == 'yes')
        {
            $result = $this->deleteline($lineid, $user);
            
            if ($result > 0)
            {   
                $this->generate_pdf($user);
                $this->error = $langs->trans('ExpDeleted');
                
                return 1;
            }
            else
            {
                return -1;
            } 
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }
                                       
    }


    /**
     *		\brief		Confirm validation
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_valid($user)
    {
        
        global $conf, $langs;

        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'validate');
        
        if ($result < 0)
        {
            return -1;
        }
        
        if ($confirm == 'yes')
        {
            $result = $this->validate($user);
            
            if ($result > 0)
            {   
                $this->generate_pdf($user);               
                $this->error = $langs->trans('NdfpHasBeenValidated');
                
                return 1;
                      
            }
            else
            {
               $this->error = $langs->trans('ErrorDeletingNdfp'); 
               return -1;
            }
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }
                
    }

    /**
     *		\brief		Confirm delete
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_delete($user)
    {
        
        global $conf, $langs;

        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'delete');
        
        if ($result < 0)
        {
            return -1;
        }
        
        if ($confirm == 'yes')
        {
            $result = $this->delete($user);
            
            if ($result > 0)
            {
                $this->error = $langs->trans('NdfpHasBeenDeleted');
                return 1;
            }
            else
            {
               $this->error = $langs->trans('ErrorDeletingNdfp');
               return -1;
                 
            }         
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }

                
    }
    
    /**
     *		\brief		Confirm canceled
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_canceled($user)
    {
        
        global $conf, $langs;

        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'create');
        
        if ($result < 0)
        {
            return -1;
        }
                
        if ($confirm == 'yes')
        {
            $result = $this->set_canceled($user);
            
            if ($result > 0)
            {
                $this->error = $langs->trans('NdfpHasBeenCanceled');
                return 1;
            }
            else
            {
               $this->error = $langs->trans('ErrorCancelingNdfp');
               return -1;
                 
            }           
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }
               
    }
        
    /**
     *		\brief		Confirm clone
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_clone($user)
    {
        
        global $conf, $langs;

        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'create');
        
        if ($result < 0)
        {
            return -1;
        }
                
        if ($confirm == 'yes')
        {
            $newid = $this->create_from_clone($this->id, $user);
            
            if ($newid > 0)
            {
                $this->error = $langs->trans('NdfpHasBeenAdded');
                $this->fetch($newid);
                
                return $newid;
            }
            else
            {
                $this->error = $langs->trans('ErrorAddingNdfp');
                return -1;
            }       
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }
                
    }    

    /**
     *		\brief		Modify note
     *		@param		user		Object user that modifies
     *		@return		int			<0 if KO, >0 if OK
     */
    function modif($user)
    {
        
        global $conf, $langs;

        $result = $this->check_user_rights($user, 'unvalidate');
        
        $totalPaid = 0;
        $payments = $this->get_payments();
        foreach ($payments AS $payment)
        {
            $totalPaid += $payment->amount;
        }
        
        if ($totalPaid == 0)
        {
            $result = $this->set_draft($user);
            
            if ($result > 0)
            {
    
                $result = $this->generate_pdf($user);
                
                return $result;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            $this->error = $langs->trans('NdfpCanNotBeSetToDraft');
        }        
                
    }

   /**
     *		\brief		Send note
     *		@param		user		Object user that sents
     *		@return		int			<0 if KO, >0 if OK
     */
    function send($user)
    {
        
        global $conf, $langs;

        if ($_POST['addfile'] || $_POST['removedfile'] || $_POST['cancel'])
        {
            return 0;
        }
        
        $result = $this->check_user_rights($user, 'send');
        if ($result < 0)
        {
            return -1;
        }
        
        if (!$this->id)
        {
            $this->error = $langs->trans('NdfpIdIsMissing');
            return -1;
        }
        
        $langs->load('mails');
    
        $subject = '';
  
        $this->generate_pdf($user);
        
        $ref = dol_sanitizeFileName($this->ref);
        $file = $conf->ndfp->dir_output . '/' . $ref . '/' . $ref . '.pdf';

        if (is_readable($file))
        {
            if ($_POST['sendto'])
            {
                $sendto = $_POST['sendto'];
                $sendtoid = 0;
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';

                $message = $_POST['message'];

				
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];


                if (dol_strlen($_POST['subject']))
                {
                   $subject = $_POST['subject']; 
                } 
                else
                {
                   $subject = $langs->transnoentities('NdfpSing').' '.$this->ref;
                } 
                

                // Create form object
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                
                $formmail = new FormMail($db);

                $attachedfiles = $formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
                
                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1);
                
                if ($mailfile->error)
                {
                    $this->error = $mailfile->error;
                    return -1;
                }
                else
                {
                    $result = $mailfile->sendfile();
                    if ($result)
                    {
                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface = new Interfaces($this->db);
                        $result = $interface->run_triggers('NDFP_SENTBYMAIL',$this, $user, $langs, $conf);

                        if ($result < 0) 
                        { 
                            $this->error = $langs->trans('ErrorCallingTrigger');
                            return -1;
                        }

                        $this->error = $langs->trans('NdfpHasBeenSent');
                        return 1;
                    }
                    else
                    {
                        $this->error = $mailfile->error;
                        return -1;
                    }
                }
            }
            else
            {
                $langs->load("other");
                $this->error = $langs->trans('ErrorMailRecipientIsEmpty');
                
                return -1;
            }
        }
        else
        {
            $langs->load("other");
            $this->error = $langs->trans('ErrorCantReadFile',$file);
            
            return -1;            
        }                
    }

    /**
     *		\brief		Generate PDF
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function generate_pdf($user)
    {
        global $conf, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
            
        $outputlangs = $langs;
        $newlang = '';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang = $_REQUEST['lang_id'];
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $user->lang;
        
        if (! empty($newlang))
        {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
        }
        
    	$result = $this->fetch($this->id);
        if ($result < 0)
        {
            return -1;
        }
        
        $result = ndfp_pdf_create($this->db, $this, '', $this->modelpdf, $outputlangs);
        
        return $result;
    }    
    /**
     *		\brief		Initialize an example of note with random values
     */    
    function init_as_specimen()
    {
        global $user, $langs, $conf, $mysoc;

        // Initialize parameters
        $now = dol_now();
                            
        $this->id = 0;
        $this->ref = 'SPECIMEN';
        $this->specimen = 1; 
        
        $this->entity       = $conf->entity;    
        $this->datec = $now; // Confirmation date               
        $this->datef = $now; // Creation date for ref numbering 
                          
		$this->tms = $now;
        
        $this->cur_iso           = 'EUR';
		$this->entity            = $conf->entity;            
		$this->datec             = $now;
		$this->dates             = $now;
        $this->datee             = $now;
		$this->datef             = $now;                        
		$this->fk_user_author    = $user->id;
		$this->fk_user           = $user->id;
        
        $this->fk_soc            = 0;
        $this->fk_project        = 0;
        
		$this->statut            = 1;
		$this->fk_cat            = 6;
        
        $this->total_tva           = 0;
        $this->total_ht           = 0;
        $this->total_ttc           = 0;        
        
		$this->description       = $langs->transnoentities('SpecimenDescription');            
		$this->comment_user      = '';
		$this->comment_admin     = '';
        
         // Get lines
        $sql = " SELECT e.rowid, e.code, e.label, e.fk_tva, t.taux";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_exp AS e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva AS t ON t.rowid = e.fk_tva";                        
        $sql.= " WHERE e.code <> 'EX_KME' ";
        
		dol_syslog("Ndfp::init_as_specimen sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
		if ($result > 0)
		{
            $num = $this->db->num_rows($result);
            $i = 0;
            
            if ($num)
            {
                while ($i < $num)
                {
        			$obj = $this->db->fetch_object($result);
        			$this->lines[$i]           = $obj;
                    
                    $this->lines[$i]->dated			    = $now;
                    $this->lines[$i]->datef				= $now;
                    $this->lines[$i]->datec				= $now;           
                    $this->lines[$i]->fk_user_author		= $user->id;
                    $this->lines[$i]->fk_exp		       = $obj->rowid;
                    $this->lines[$i]->fk_tva		       = $obj->fk_tva;                         
                    $this->lines[$i]->qty			        = 1;

                    $this->lines[$i]->total_ttc          = rand(1, 1000);
                    $this->lines[$i]->total_ht           = price2num($this->lines[$i]->total_ttc/(1 + $obj->taux/100), 'MT');
                    $this->lines[$i]->total_tva          = price2num($this->lines[$i]->total_ttc - $this->lines[$i]->total_ht);
                                        
                    $this->lines[$i]->tms					= $now;
                    
                    $this->total_tva   += $this->lines[$i]->total_tva;
                    $this->total_ht    += $this->lines[$i]->total_ht;
                    $this->total_ttc   += $this->lines[$i]->total_ttc;
        
                    $i++;                     
                }      
            }
          
			return 1;
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}                                     
    }
    
     /**
     *      \brief Return next reference of confirmation not already used (or last reference)
     *      @param	   soc  		           objet company
     *      @param     mode                    'next' for next value or 'last' for last value
     *      @return    string                  free ref or last ref
     */
    function get_next_num_ref($soc, $mode = 'next')
    {
        global $conf, $langs;
        
        $langs->load("nfdp");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->NDFP_ADDON)){
            $conf->global->NDFP_ADDON = 'mod_ndfp_saturne';
        }else if ($conf->global->NDFP_ADDON == 'saturne'){
            $conf->global->NDFP_ADDON = 'mod_ndfp_saturne';
        }else if ($conf->global->NDFP_ADDON == 'uranus'){
            $conf->global->NDFP_ADDON = 'mod_ndfp_uranus';
        } 

        $included = false;

        $file = $conf->global->NDFP_ADDON.".php";
        $classname = $conf->global->NDFP_ADDON;
        // Include file with class
        foreach ($conf->file->dol_document_root as $dirroot)
        {
            $dir = $dirroot."/core/modules/ndfp/";
                   
            $included|=@include_once($dir.$file);
        }

        // For compatibility
        if (! $included)
        {
            $file = $conf->global->NDFP_ADDON."/".$conf->global->NDFP_ADDON.".modules.php";
            $classname = "mod_ndfp_".$conf->global->NDFP_ADDON;
            // Include file with class
            foreach ($conf->file->dol_document_root as $dirroot)
            {
                $dir = $dirroot."/core/modules/ndfp/";
                
                // Load file with numbering class (if found)
                $included|=@include_once($dir.$file);
            }
        }

        if (! $included)
        {
            $this->error = $langs->trans('FailedToIncludeNumberingFile');
            return -1;
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "")
        {
            return $numref;
        }
        else
        {
            return -1;
        }
    }
    

    /**
     *  \brief Return label of object status
     *  @param      mode            0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
     *  @param      alreadypaid     0=No payment already done, 1=Some payments already done
     *  @return     string          Label
     */
    function get_lib_statut($mode = 0, $alreadypaid = -1)
    {
        return $this->lib_statut($this->statut, $mode, $alreadypaid);
    }

    /**
     *  \brief Return category name
     *  @return     string          Label
     */
    function get_cat_name()
    {
        global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        $sql  = " SELECT c.rowid, p.label AS plabel, c.label AS clabel";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat AS c";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_cat AS p ON p.rowid = c.fk_parent";
        $sql .= " WHERE c.rowid = ".$this->fk_cat;
        
        dol_syslog("Ndfp::get_cat_name sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        
        $name = '';
        
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            
            if ($num)
            {
                $obj = $this->db->fetch_object($resql);
                
                $name = $langs->trans($obj->plabel) .' - '. $langs->trans($obj->clabel); 
            }
        }
        
        return $name;
        
    }


        
    /**
     *    	\brief      Renvoi le libelle d'un statut donne
     *    	\param      statut        	Id statut
     *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *		\param		alreadypaid	    Montant deja paye
     *    	\return     string        	Libelle du statut
     */
    function lib_statut($statut, $mode = 0,$alreadypaid = -1)
    {
        global $langs;

        if ($mode == 0){
            
            if ($statut == 0) return $langs->trans('NdfpStatusDraft');
            if ($statut == 2) return $langs->trans('NdfpStatusPaid');
            if ($statut == 3 && $alreadypaid <= 0) return $langs->trans('NdfpStatusClosedUnpaid');
            if ($statut == 3 && $alreadypaid > 0) return $langs->trans('NdfpStatusClosedPaidPartially');
            if ($alreadypaid <= 0) return $langs->trans('NdfpStatusNotPaid');
            
            return $langs->trans('NdfpStatusStarted');
        }
        
        if ($mode == 1)
        {
            if ($statut == 0) return $langs->trans('NdfpShortStatusDraft');
            if ($statut == 2) return $langs->trans('NdfpShortStatusPaid');
            if ($statut == 3 && $alreadypaid <= 0) return $langs->trans('NdfpShortStatusClosedUnpaid');
            if ($statut == 3 && $alreadypaid > 0) return $langs->trans('NdfpShortStatusClosedPaidPartially');
            if ($alreadypaid <= 0) return $langs->trans('NdfpShortStatusNotPaid');
            
            return $langs->trans('NdfpShortStatusStarted');
        }
        
        if ($mode == 2)
        {
            if ($statut == 0) return img_picto($langs->trans('NdfpStatusDraft'),'statut0').' '.$langs->trans('NdfpShortStatusDraft');
            if ($statut == 2) return img_picto($langs->trans('NdfpStatusPaid'),'statut6').' '.$langs->trans('NdfpShortStatusPaid');
            if ($statut == 3 && $alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusCanceled'),'statut5').' '.$langs->trans('NdfpShortStatusCanceled');
            if ($statut == 3 && $alreadypaid > 0) return img_picto($langs->trans('NdfpStatusClosedPaidPartially'),'statut7').' '.$langs->trans('NdfpShortStatusClosedPaidPartially');
            if ($alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusNotPaid'),'statut1').' '.$langs->trans('NdfpShortStatusNotPaid');
            
            return img_picto($langs->trans('NdfpStatusStarted'),'statut3').' '.$langs->trans('NdfpShortStatusStarted');
        }
        
        if ($mode == 3)
        {
            if ($statut == 0) return img_picto($langs->trans('NdfpStatusDraft'),'statut0');
            if ($statut == 2) return img_picto($langs->trans('NdfpStatusPaid'),'statut6');
            if ($statut == 3 && $alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusCanceled'),'statut5');
            if ($statut == 3 && $alreadypaid > 0) return img_picto($langs->trans('NdfpStatusClosedPaidPartially'),'statut7');
            if ($alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusNotPaid'),'statut1');
            
            return img_picto($langs->trans('NdfpStatusStarted'),'statut3');
 
        }
        
        if ($mode == 4)
        {
            if ($statut == 0) return img_picto($langs->trans('NdfpStatusDraft'),'statut0').' '.$langs->trans('NdfpStatusDraft');
            if ($statut == 2) return img_picto($langs->trans('NdfpStatusPaid'),'statut6').' '.$langs->trans('NdfpStatusPaid');
            if ($statut == 3 && $alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusCanceled'),'statut5').' '.$langs->trans('NdfpStatusCanceled');
            if ($statut == 3 && $alreadypaid > 0) return img_picto($langs->trans('NdfpStatusClosedPaidPartially'),'statut7').' '.$langs->trans('NdfpStatusClosedPaidPartially');
            if ($alreadypaid <= 0) return img_picto($langs->trans('NdfpStatusNotPaid'),'statut1').' '.$langs->trans('NdfpStatusNotPaid');
            
            return img_picto($langs->trans('NdfpStatusStarted'),'statut3').' '.$langs->trans('NdfpStatusStarted');

        }
        
        if ($mode == 5)
        {
            if ($statut == 0) return $langs->trans('NdfpShortStatusDraft').' '.img_picto($langs->trans('NdfpStatusDraft'),'statut0');
            if ($statut == 2) return $langs->trans('NdfpShortStatusPaid').' '.img_picto($langs->trans('NdfpStatusPaid'),'statut6');
            if ($statut == 3 && $alreadypaid <= 0) return $langs->trans('NdfpShortStatusCanceled').' '.img_picto($langs->trans('NdfpStatusCanceled'),'statut5');
            if ($statut == 3 && $alreadypaid > 0) return $langs->trans('NdfpShortStatusClosedPaidPartially').' '.img_picto($langs->trans('NdfpStatusClosedPaidPartially'),'statut7');
            if ($alreadypaid <= 0) return $langs->trans('NdfpShortStatusNotPaid').' '.img_picto($langs->trans('NdfpStatusNotPaid'),'statut1');
            
            return $langs->trans('NdfpShortStatusStarted').' '.img_picto($langs->trans('NdfpStatusStarted'),'statut3');
        }
    }
    
    /**
     *      \brief Return clicable link of object (with eventually picto)
     *      @param      withpicto       Add picto into link
     *      @param      option          Where point the link
     *      @param      max             Maxlength of ref
     *      @return     string          String with URL
     */
    function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0){
        return $this->get_clickable_name($withpicto, $option, $max, $short);
    }
    
    function get_clickable_name($withpicto = 0, $option = '', $max = 0, $short = 0)
    {
        global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $result = '';

        $url = DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id;

        if ($short) return $url;

        $picto = 'bill';

        $label = $langs->trans("ShowNdfp").': '.$this->ref;

        if ($withpicto) $result.= ('<a href="'.$url.'">'.img_object($label, $picto).'</a>');
        if ($withpicto && $withpicto != 2) $result.= ' ';
        if ($withpicto != 2) $result.= '<a href="'.$url.'">'.($max ? dol_trunc($this->ref, $max) : $this->ref).'</a>';
        
        return $result;
    }

    /**
     *      \brief Return tax rating label
     *      @return     string          String 
     */
    function get_tax_rating_label($langs)
    {
        global $langs;
        
        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        if ($this->fk_cat > 0)
        {
            $result = '';
            
            $sql  = " SELECT p.label AS parent_label, c.label AS child_label";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax_cat AS p";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_cat AS c ON c.fk_parent = p.rowid";        
            $sql .= " WHERE c.rowid = ".$this->fk_cat;
            
            dol_syslog("Ndfp::get_tax_rating_label sql=".$sql, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $nump = $this->db->num_rows($resql);
                if ($nump)
                {
                    $obj = $this->db->fetch_object($resql);
                    $result = $langs->trans($obj->parent_label)." - ".$langs->trans($obj->child_label);
                }
            }
            
            return $result;            
        }
        else
        {
            return '';
        }

    }
        
    /**
     *      \brief Return payments of note 
     *      @return     array    List of payments
     */
	function get_payments()
	{
	    global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        $payments = array();

    
       // Payments already done
        $sql = 'SELECT np.datep as dp, np.amount as total, np.payment_number, np.rowid,';
        $sql.= ' c.code as payment_code, c.libelle as payment_label, nd.amount';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'ndfp_pay_det as nd';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'ndfp_pay as np ON np.rowid = nd.fk_ndfp_payment';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON np.fk_payment = c.id';
        $sql.= ' WHERE nd.fk_ndfp = '.$this->id;
        $sql.= ' ORDER BY dp';

        dol_syslog("Ndfp::get_payments sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $payments[$i] = $this->db->fetch_object($result);
                
                if ($langs->trans("PaymentType".$payments[$i]->payment_code) != ("PaymentType".$payments[$i]->payment_code)){
                    $payments[$i]->label = $langs->trans("PaymentType".$payments[$i]->payment_code);
                }else{
                    $payments[$i]->label = $payments[$i]->payment_label;
                }
                
                $i++;
            }
            
            $this->db->free($result);
            
            return $payments;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            return -1;
        }
	}
    
    /**
     * 	\brief Return amount of payments already done
     *	@return		int		Amount of payment already done, <0 if KO
     */
    function get_amount_payments_done()
    {
        global $langs;
        
        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        $sql = 'SELECT SUM(amount) AS amount';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'ndfp_pay_det';
        $sql.= ' WHERE fk_ndfp = '.$this->id;

        dol_syslog("Ndfp::get_amount_payments_done sql=".$sql, LOG_DEBUG);
        
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $this->db->free($resql);
            
            return $obj->amount;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            return -1;
        }
    }
        
    
     /**
     *  \brief Return if a note can be deleted
     *	Rule is:
     *  If note a definitive ref, is last, without payment -> yes
     *  If note is draft and has a temporary ref -> yes
     *  @return    int         <0 if KO, 0=no, 1=yes
     */
    function is_erasable()
    {
        global $conf;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        // on verifie si la note est en numerotation provisoire
        $ref = substr($this->ref, 1, 4);

        // If not a draft note and not temporary invoice
        if ($ref != 'PROV')
        {
            $maxref = $this->get_next_num_ref($this->fk_soc, 'last');
            $payment = $this->get_amount_payments_done();
            
            if ($maxref == $this->ref && $payment == 0)
            {
                return 1;
            }
        }
        else if ($this->statut == 0 && $ref == 'PROV') 
        {
            return 1;
        }

        return 0;
    }

     /**
     *  \brief Return list of available actions (displayed as buttons)
     * 
     *  @param     action       Current action
     *  @return    array        List of buttons
     */    
    function get_available_actions($action)
    {
        global $user, $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $alreadyPaid  = $this->get_amount_payments_done();
        $remainToPay = price2num($this->total_ttc - $alreadyPaid, 'MT');
        
            
        $buttons = array();
        if ($user->societe_id != 0 && ($action == 'presend' || $action == 'valid' || $action == 'editline')){
            return $buttons;
        }  
             
        // Edit a credit note already validated
        if ($this->statut == 1 && $remainToPay == $this->total_ttc){
            if ($this->check_user_rights($user, 'create') > 0){
                $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
            }else{
                $buttons[] = '<span class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Modify').'</span>';
            }
        }
    
        // Reopen a standard paid expense
        if (($this->statut == 2 || $this->statut == 3)){				// A paid expense
            if ($this->check_user_rights($user, 'create') > 0){
                $buttons[] =  '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
            }else{
                $buttons[] = '<span class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ReOpen').'</a>';
            }
            
        }
    
        // Validate
        if ($this->statut == 0 && sizeof($this->lines) > 0 && $this->total_ttc >= 0 && $user->rights->ndfp->myactions->validate){
            if ($this->check_user_rights($user, 'validate') > 0){
                $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=valid">'.$langs->trans('Validate').'</a>';
            }else{
                $buttons[] = '<span class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('Validate').'</a>';
            }
            
        }
    
        // Send by mail
        if ($this->statut == 1 || $this->statut == 2){
            if ($this->check_user_rights($user, 'send') > 0){
                $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
            }else{
                $buttons[] = '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
            }     
        }
        
    
        // Create payment
        if ($this->statut == 1)
        {
            if ($this->check_user_rights($user, 'payment') > 0){
                if ($remainToPay == 0)
                {
                  $buttons[] = '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span>';
                }
                else
                {
                  $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/payment.php?fk_user='.$this->fk_user.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
                }                
            }else{
                $buttons[] = '<a class="butActionRefused" href="#">'.$langs->trans('DoPayment').'</a>';
            }

        }
        
        
        // Classify paid 
        if ($this->statut == 1 && $remainToPay == 0)
        {
            if ($this->check_user_rights($user, 'create') > 0){
                $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
            }else{
                $buttons[] = '<a class="butActionRefused" href="#">'.$langs->trans('ClassifyPaid').'</a>';
            }       
        }
        
        // Classify 'closed not completely paid' (possible si validee et pas encore classee payee)
        if ($this->statut == 1 && $alreadyPaid == 0)
        {
            if ($this->check_user_rights($user, 'create') > 0){
                if ($alreadyPaid > 0){
                    $buttons[] =  '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=canceled">'.$langs->trans('ClassifyPaidPartially').'</a>';
                }else{
                    $buttons[] =  '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
                }                
            }else{
                if ($alreadyPaid > 0){
                    $buttons[] =  '<a class="butActionRefused" href="#">'.$langs->trans('ClassifyPaidPartially').'</a>';
                }else{
                    $buttons[] =  '<a class="butActionRefused" href="#">'.$langs->trans('ClassifyCanceled').'</a>';
                }                 
            }
        }


        // Clone
        if ($this->check_user_rights($user, 'create') > 0){
            $buttons[] = '<a class="butAction" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=clone">'.$langs->trans("ToClone").'</a>';
        }else{
            $buttons[] = '<a class="butActionRefused" href="#">'.$langs->trans('ToClone').'</a>';
        }  
                
        // Delete
        if ($this->check_user_rights($user, 'delete') > 0){
            if (!$this->is_erasable())
            {
              $buttons[] =  '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecauseNotErasable").'">'.$langs->trans('Delete').'</a>';
            }
            elseif ($alreadyPaid > 0)
            {
              $buttons[] =  '<a class="butActionRefused" href="#" title="'.$langs->trans("DisabledBecausePayments").'">'.$langs->trans('Delete').'</a>';
            }
            else
            {
              $buttons[] =  '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/ndfp/ndfp.php?id='.$this->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
            } 
        }else{
            $buttons[] = '<a class="butActionRefused" href="#">'.$langs->trans('Delete').'</a>';
        }            
        
        return $buttons;
    }

    /**
     *		\brief		Determine if expenses can be added to the note
     *		\param		action		Current action
     *		\param		int			0 if KO, 1 if OK
     */   
    function can_add_expenses($action)
    {
        global $langs;
        
        $add = 0;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
                
        if ($this->statut == 0 && $action <> 'valid' && $action <> 'editline'){
            $add = 1;
        }
        
        return $add;
    }

     /**
     *  \brief Get TVA
     * 
     *  @param     fk_exp            Id of the TVA
     *  @return    object          Object of the TVA
     */
    function get_tva($fk_tva)
    {
        global $langs;


        // Check parameters 
        if ($fk_tva < 0)
        {
			$this->error = $langs->trans('ErrorBadParameterTVA');
			return -1;             
        } 
                
        $sql  = " SELECT t.rowid, t.taux FROM ".MAIN_DB_PREFIX."c_tva AS t";
        $sql .= " WHERE t.rowid = ".$fk_tva;
    
        dol_syslog("Ndfp::get_tva sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num)
            {
                    $obj = $this->db->fetch_object($result);
                    $this->db->free($result);
                    
                    return $obj;

            }
            else
            {
                $this->error = $langs->trans('ErrorBadParameterTVA');
                return -1;                
            }
                        
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            return -1;            
        }        
    }  

             
     /**
     *  \brief Get expense 
     * 
     *  @param     fk_exp            Id of the expense
     *  @return    object          Object of the expense
     */
    function get_expense($fk_exp, $fk_tva = 0)
    {
        global $langs;

        // Check parameters 
        if ($fk_exp < 0)
        {
			$this->error = $langs->trans('ErrorBadParameterExpense');
			return -1;             
        } 
                
        $sql  = "SELECT e.code, e.fk_tva, t.rowid, t.taux FROM ".MAIN_DB_PREFIX."c_exp AS e";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tva AS t ON t.rowid = e.fk_tva";
        $sql.= " WHERE e.rowid = ".$fk_exp;
    
        dol_syslog("Ndfp::get_expense sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num)
            {
                    $obj = $this->db->fetch_object($result);
                    $this->db->free($result);
                    
                    return $obj;

            }
            else
            {
                $this->error = $langs->trans('ErrorBadParameterExpense');
                return -1;                
            }
                        
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            return -1;            
        }        
    }    
     /**
     *  \brief Add an expense to the note
     * 
     *  @param     user             User who adds
     *  @return    int              <0 if KO, id the line added if OK
     */     
    function addline($user)
    {       
        global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $dated = dol_mktime($_POST['eshour'], $_POST['esmin'], $_POST['essec'], $_POST['esmonth'], $_POST['esday'], $_POST['esyear']);
        $datef = dol_mktime($_POST['eehour'], $_POST['eemin'], $_POST['eesec'], $_POST['eemonth'], $_POST['eeday'], $_POST['eeyear']);
        
        $qty        = GETPOST('qty');
        $fk_exp     = GETPOST('fk_exp');
        $fk_tva     = GETPOST('fk_tva');
        $label      = GETPOST('label');
        $total_ttc  = price2num(GETPOST('total_ttc'));

        $fk_user_author = $user->id;
        $fk_ndfp = $this->id;
                    
        if ($this->statut != 0)
        {
            $this->error = $langs('CanNotModifyLinesOnNonDraftNote');
            return -1;
        }
        
        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }               


        if ($this->statut == 0)
        {
            $expobj = $this->get_expense($fk_exp);            
            $tvaobj = $this->get_tva($fk_tva);
            
            if ($tvaobj < 0)
            {
                return -1;
            }

            if ($expobj < 0)
            {
                return -1;
            }
                        
            $tva  = price2num($tvaobj->taux);
            
            
            $km_exp = ($expobj->code == 'EX_KME') ? true : false;
                                   
            if ($km_exp)
            {   
                $total_ttc = $this->compute_total_km($this->fk_cat, $qty, $tva);                               
            }
            
            
             // Compute total HT
            $total_ht = $total_ttc/(1 + $tva/100);  
            // Compute total TVA                    
            $total_tva = $total_ttc - $total_ht;
            
            // Insert line
            $this->line  = new NdfpLine($this->db);
            $this->line->fk_ndfp = $this->id;
            $this->line->label = $label;
            $this->line->qty = $qty; 
            $this->line->dated = $dated;
            $this->line->datef = $datef;
            
            $this->line->fk_user_author = $fk_user_author;
            $this->line->fk_tva = $fk_tva;
            $this->line->fk_exp = $fk_exp;


            $this->line->total_ht = $total_ht;  
            $this->line->total_tva = $total_tva;
            $this->line->total_ttc = $total_ttc;


            $result = $this->line->insert();
            
            if ($result > 0)
            {

                $result = $this->update_totals();
                                
                if ($result > 0)
                {
                    $this->fetch_lines();
                    
                    $this->generate_pdf($user);
               	    $this->error = $langs->trans('ExpAdded');
                    
                    return $this->line->rowid;
                }
                else
                {
                    $this->error = $this->db->error()." sql=".$sql;
                    
                    return -1;
                }
            }
            else
            {
                $this->error = $this->line->error;
                
                return -2;
            }
        }
    }
     /**
     *  \brief Update an expense of a note
     * 
     *  @param     user           User who updates
     *  @return    int              <0 if KO, id the line added if OK
     */ 
    function updateline($user)
    {
        global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $dated = dol_mktime($_POST['eshour'], $_POST['esmin'], $_POST['essec'], $_POST['esmonth'], $_POST['esday'], $_POST['esyear']);
        $datef = dol_mktime($_POST['eehour'], $_POST['eemin'], $_POST['eesec'], $_POST['eemonth'], $_POST['eeday'], $_POST['eeyear']);
    
        $fk_ndfp    = $this->id;
        
        $lineid     = GETPOST('lineid');
        $qty        = GETPOST('qty');
        $fk_exp     = GETPOST('fk_exp');
        $fk_tva     = GETPOST('fk_tva');
        $label      = GETPOST('label');
        $total_ttc  = GETPOST('total_ttc');
            
        if ($this->statut != 0)
        {
            $this->error = $langs('CanNotModifyLinesOnNonDraftNote');
            return -1;
        }

        $result = $this->check_user_rights($user, 'create');
        if ($result < 0)
        {
            return -1;
        }                      

        //Fetch line
        $this->line  = new NdfpLine($this->db);

        
        $result = $this->line->fetch($lineid);
        
        if ($result < 0)
        {
            $this->error = $langs->trans('LineDoesNotExist');
            return -1;                
        }


        if ($this->statut == 0)
        {

            $expobj = $this->get_expense($fk_exp);         
            $tvaobj = $this->get_tva($fk_tva);
            
            if ($tvaobj < 0)
            {
                return -1;
            }

            if ($expobj < 0)
            {
                return -1;
            }
                        
            $tva  = price2num($tvaobj->taux);
            
            
            $km_exp = ($expobj->code == 'EX_KME') ? true : false;
                                   
            if ($km_exp)
            {   
                $total_ttc = $this->compute_total_km($this->fk_cat, $qty, $tva);                               
            }
            
            
             // Compute total HT
            $total_ht = $total_ttc/(1 + $tva/100);  
                                 
            $total_tva = $total_ttc - $total_ht; 
                                 

            // Update line
            $this->line->label = $label;
            $this->line->qty = $qty; 
            $this->line->dated = $dated;
            $this->line->datef = $datef;
            
            $this->line->fk_tva = $fk_tva;
            $this->line->fk_exp = $fk_exp;

            $this->line->total_ht = $total_ht;  
            $this->line->total_tva = $total_tva;
            $this->line->total_ttc = $total_ttc;

            $result = $this->line->update();
            
            if ($result > 0)
            {
                $result = $this->update_totals();
                                
                if ($result > 0)
                {
                    $this->fetch_lines();
                    $this->generate_pdf($user);
                    
           	        $this->error = $langs->trans('ExpUpdated');
                    return $this->line->rowid;
                }
                else
                {
                    $this->error = $this->db->error()." sql=".$sql;
                    
                    return -1;
                }
            }
            else
            {
                $this->error = $this->line->error;
                
                return -2;
            }
        }
    }
    
     /**
     *  \brief Delete an expense from a note
     * 
     *  @param     user            User who creates
     *  @return    int              <0 if KO, id the line added if OK
     */         
    function deleteline($lineid, $user)
    {
        global $langs, $conf;
        
        if ($this->statut != 0)
        {
            $this->error = $langs('CanNotModifyLinesOnNonDraftNote');
            return -1;
        }

        $line  = new NdfpLine($this->db);


        $result = $line->fetch($lineid);
        
        if ($result < 0)
        {
            $this->error = $langs->trans('LineDoesNotExist');
            return -1;                
        }
        
        if ($line->delete() > 0)
        {
            $this->fetch_lines();
    		$result = $this->update_totals();

        	if ($result > 0)
        	{
                
                $this->generate_pdf($user);
                            	   
        		return 1;
        	}
        	else
        	{
        		$this->error = $this->db->error()." sql=".$sql;
        		return -1;
        	}
        }
        else
        {
        	$this->error = $line->error;
        	return -1;
        }
    }
    
     /**
     *  \brief Update the totals of the note based on the lines added
     * 
     *  @return    int              <0 if KO, >0 if OK
     */ 
    function update_totals()
    {
        global $langs;

        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpIdIsMissing');
			return -1;             
        } 
        
        $sql = 'SELECT SUM(total_tva) AS tot_tva, SUM(total_ht) AS tot_ht, SUM(total_ttc) AS tot_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'ndfp_det';
        $sql.= ' WHERE fk_ndfp = '.$this->id;
        $sql.= ' GROUP BY fk_ndfp';

        dol_syslog("Ndfp::update_totals sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            
            if ($num > 0)
            {
                $obj = $this->db->fetch_object($result);
                

                
                $this->total_ht = $obj->tot_ht;
                $this->total_ttc = $obj->tot_ttc;
                $this->total_tva = $obj->tot_tva;              
            }
            else // all expenses have been removed
            {
                $this->total_ht = 0;
                $this->total_ttc = 0;
                $this->total_tva = 0;
                                            
            }

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'ndfp SET total_ttc = '.$this->total_ttc.','; 
            $sql.= ' total_ht = '.$this->total_ht.', total_tva = '.$this->total_tva;
            $sql.= ' WHERE rowid = '.$this->id;
                            
            $result = $this->db->query($sql);
            if ($result)
            {
                return 1;
            }
            else
            {
                $this->error = $this->db->error()." sql=".$sql;
                return -1;
            }  
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            return -1;
        }        
    }

     /**
     *  \brief Compute the cost of the kilometers expense based on the number of kilometers and the vehicule category
     * 
     *  @param     fk_cat           Category of the vehicule used
     *  @param     qty              Number of kilometers
     *  @param     tva              VAT rate
     *  @return    int              <0 if KO, total ttc if OK
     */     
    function compute_total_km($fk_cat, $qty, $tva)
    {
        global $langs;
        
        $total_ttc = 0;
        $ranges = array();
        $coef = 0;
        $offset = 0;
        
        if ($fk_cat < 0){
			$this->error = $langs->trans('ErrorBadParameterCat');
			return -1;              
        }
        
        if ($qty < 0){
			$this->error = $langs->trans('ErrorBadParameterQty');
			return -1;              
        }
        
        //Clean
        $qty = price2num($qty);
                        
        $sql  = " SELECT r.range, t.offset, t.coef";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_exp_tax t";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_exp_tax_range r ON r.rowid = t.fk_range";
        $sql .= " WHERE t.fk_cat = ".$fk_cat;                        
        $sql .= " ORDER BY r.range ASC";
    
        dol_syslog("Ndfp::compute_total_km sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $this->db->fetch_object($result);
                    
                    $ranges[$i] = $obj;                          
                }
                
                for ($i = 0; $i < $num; $i++)
                {
                    if ($i < ($num - 1))
                    {
                        if ($qty > $ranges[$i]->range && $qty < $ranges[$i+1]->range)
                        {
                            $coef = $ranges[$i]->coef;
                            $offset = $ranges[$i]->offset;
                        }                                
                    }
                    else
                    {
                        if ($qty > $ranges[$i]->range)
                        {
                            $coef = $ranges[$i]->coef;
                            $offset = $ranges[$i]->offset;
                        }                                
                    }
                }
                
                $total_ht = $offset + $coef * $qty;
                $total_ttc = $total_ht + $total_ht * $tva;
                
                return $total_ttc;
            }
            else
            {
                $this->error = $langs->trans('TaxUndefinedForThisCategory');
                
                return -1;            
            }           
            
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            
            return -1;            
        }                         
    }
                                          
}


/**
 *	\class      	NdfpLine
 *	\brief      	Class to manage expenses of a credit note
 *	\remarks		Uses lines of llx_ndfp_det tables
 */
class NdfpLine
{
    var $db;
    var $error;

    var $oldline;

    //! From llx_ndfp_det
    var $rowid;
    var $fk_ndfp;     
    var $label;	
      
    var $datec;			 		  
    var $dated;			   
    var $datef;	
    		   		  
    var $fk_user_author;
    	          
    var $fk_exp;			
    var $fk_tva;			     
    var $qty;   			  

                
    var $total_ht;            
    var $total_ttc;          
    var $total_tva; 
                        		       

    var $tms;

   /**
	*  \brief  Constructeur de la classe
	*  @param  DB          handler acces base de donnees
	*/
    function NdfpLine($DB)
    {
        $this->db = $DB;
    }

	/**
	 * \brief Check parameters prior inserting or updating the DB
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
     function check_parameters()
     {
		global $conf, $langs;

                
        // Check parameters
        if (empty($this->fk_exp)){
			$this->error = $langs->trans('ErrorBadParameterExp');
			return -1;            
        }

        
        if ($this->datef < $this->dated){
			$this->error = $langs->trans('ErrorBadParameterDateDoNotMatch');
			return -1;            
        }

 
         if ($this->qty <= 0){
			$this->error = $langs->trans('ErrorBadParameterQty');
			return -1;            
        }
                               
        if ($this->fk_tva < 0){
			$this->error = $langs->trans('ErrorBadParameterTva');
			return -1;            
        }
        
        if ($this->fk_ndfp < 0){
			$this->error = $langs->trans('ErrorBadParameterNdfp');
			return -1;            
        }

        if ($this->total_ttc < 0){
			$this->error = $langs->trans('ErrorBadParameterTTCTotal');
			return -1;            
        }
        
        if ($this->total_tva < 0){
			$this->error = $langs->trans('ErrorBadParameterTVATotal');
			return -1;            
        } 
        
        if ($this->total_ht < 0){
			$this->error = $langs->trans('ErrorBadParameterHTTotal');
			return -1;            
        } 
        
        return 1;               
     }
     
	/**
	 * \brief Fetch object from database
	 *
	 * @param 	rowid	Id of the line
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function fetch($rowid)
    {
        
	    global $langs;
       
        if (!$rowid){
			$this->error = $langs->trans('LineIdIsMissing');
			return -1;             
        }
                
        $sql = 'SELECT nd.rowid, nd.fk_ndfp, nd.label, nd.dated, nd.datef, nd.datec, nd.fk_user_author,';
        $sql.= ' nd.fk_exp, nd.fk_tva, nd.qty, nd.total_ht, nd.total_ttc, nd.total_tva, nd.tms';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'ndfp_det as nd';
        $sql.= ' WHERE nd.rowid = '.$rowid;

        dol_syslog("NdfpLine::fetch sql=".$sql);
        
        $result = $this->db->query($sql);
        $num = 0;
        
        if ($result)
        {
            $num = $this->db->num_rows($result);
            
            if ($num)
            {            
                $obj = $this->db->fetch_object($result);
    
                $this->rowid				= $obj->rowid;
                $this->fk_ndfp			    = $obj->fk_ndfp;
                $this->label		        = $obj->label;
                
                $this->dated			    = $this->db->jdate($obj->dated);
                $this->datef				= $this->db->jdate($obj->datef);
                $this->datec				= $this->db->jdate($obj->datec);
                
                $this->fk_user_author		= $obj->fk_user_author;
                            
                $this->fk_exp				= $obj->fk_exp;
                $this->fk_tva			    = $obj->fk_tva;
                $this->qty			        = $obj->qty;
                $this->total_ht				= $obj->total_ht;
                $this->total_tva			= $obj->total_tva;
                $this->total_ttc			= $obj->total_ttc;
    
                $this->tms					= $this->db->jdate($obj->tms);
    
                $this->db->free($result);
            }
            else
            {
                $this->error = $langs->trans('LineDoesNotExist');
                return -1;                 
            }
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            
            return -1;     
        }
    }

    /**
     *	\brief     	Insert line in database
     *	@param      notrigger		1 no triggers
     *	@return		int				<0 if KO, >0 if OK
     */
    function insert($notrigger = 0)
    {
        global $langs, $user, $conf;

        $result = $this->check_parameters();
        
        if ($result < 0)
        {
            return -1;
        }
        
        // Clean parameters
        $this->label = trim($this->label);
        $this->tms = dol_now();
        $this->datec = dol_now();

        $this->total_ttc = price2num($this->total_ttc, 'MT');                                               
        $this->total_ht = price2num($this->total_ht, 'MT');
        $this->total_tva = price2num($this->total_ttc - $this->total_ht); 
                           
        $this->db->begin();

        // 
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'ndfp_det';
        $sql.= ' (fk_ndfp, label, dated, datef, datec, fk_user_author,';
        $sql.= ' fk_exp, fk_tva, qty, total_ht, total_ttc, total_tva, tms)';
        $sql.= " VALUES (".$this->fk_ndfp.", '".$this->db->escape($this->label)."',";
        $sql.= $this->dated ? "'".$this->db->idate($this->dated)."'," : 'null,';
        $sql.= $this->datef ? "'".$this->db->idate($this->datef)."'," : 'null,';
        $sql.= $this->datec ? "'".$this->db->idate($this->datec)."'," : 'null,';
        $sql.= " ".$user->id.", ";
        $sql.= " ".$this->fk_exp.", ";
        $sql.= " ".$this->fk_tva.",";
        $sql.= " ".$this->qty.",";
        $sql.= " ".$this->total_ht.",";
		$sql.= " ".$this->total_ttc.",";
		$sql.= " ".$this->total_tva.",";
        $sql.= " '".$this->db->idate($this->tms)."'"; 
        $sql.= ')';

        dol_syslog("NdfpLine::insert sql=".$sql);
        
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX.'ndfp_det');

            $this->update_ndfp_tms();
            
            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('LINENDFP_INSERT', $this, $user ,$langs, $conf);
                if ($result < 0) { 
                    $this->error = $langs->trans('ErrorCallingTrigger');
                    $this->db->rollback();
                    return -1;
                }
                // Fin appel triggers
            }

            $this->db->commit();
            
            return $this->rowid;

        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -2;
        }
    }

    /**
     *  	\brief Update line into database
     *		@return		int		<0 if KO, >0 if OK
     */
    function update($notrigger = 0)
    {
        global $langs, $conf;

        $result = $this->check_parameters();
        
        if ($result < 0)
        {
            return -1;
        }

        if (!$this->rowid){
			$this->error = $langs->trans('LineIdIsMissing');
			return -1;             
        }
                
        // Clean parameters
        $this->label = trim($this->label);
        $this->total_ttc = price2num($this->total_ttc, 'MT');                                               
        $this->total_ht = price2num($this->total_ht, 'MT');
        $this->total_tva = price2num($this->total_ttc - $this->total_ht); 
        $this->tms = dol_now();
        

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."ndfp_det SET";
        $sql.= " `label` = '".$this->db->escape($this->label)."',";
        $sql.= " `dated` = ".($this->dated ? "'".$this->db->idate($this->dated)."'," : 'null,');
        $sql.= " `datef` = ".($this->datef ? "'".$this->db->idate($this->datef)."'," : 'null,');
        $sql.= " `fk_exp` = ".$this->fk_exp.",";
        $sql.= " `fk_tva` = ".$this->fk_tva.",";
        $sql.= " `qty` = ".$this->qty.",";
        $sql.= " `total_ht` = ".$this->total_ht.",";
		$sql.= " `total_ttc` = ".$this->total_ttc.",";
		$sql.= " `total_tva` = ".$this->total_tva.",";
        $sql.= " `tms` = '".$this->db->idate($this->tms)."'";        
        $sql.= " WHERE `rowid` = ".$this->rowid;

        dol_syslog("NdfpLine::update sql=".$sql);

        $result = $this->db->query($sql);
        if ($result)
        {
            $this->update_ndfp_tms();
            
            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($this->db);
                $result = $interface->run_triggers('LINENDFP_UPDATE', $this, $user, $langs, $conf);
                if ($result < 0) { 
                    $this->error = $langs->trans('ErrorCallingTrigger');
                    $this->db->rollback();
                    return -1;
                }
                // Fin appel triggers
            }
            $this->db->commit();
            
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -2;
        }
    }

	/**
	 * 	\brief Delete line in database
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function delete($notrigger = 0)
	{
		global $conf,$langs,$user;

        if (!$this->rowid){
			$this->error = $langs->trans('LineIdIsMissing');
			return -1;             
        }
        
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ndfp_det WHERE rowid = ".$this->rowid;
        
		dol_syslog("NdfpLine::delete sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
                
		if ($result)
		{
		    $this->update_ndfp_tms();

            if (! $notrigger)
            {           
    			// Calling triggers
    			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
    			$interface = new Interfaces($this->db);
    			$result = $interface->run_triggers('LINENDFP_DELETE',$this,$user,$langs,$conf);
    			if ($result < 0) 
                { 
                    $this->error = $langs->trans('ErrorCallingTrigger');
                    $this->db->rollback();
                    return -1; 
                }
            }
			// Fin appel triggers

			$this->db->commit();

			return 1;
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
            
			return -1;
		}
	}

	/**
	 * 	\brief Update timestamp of note
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function update_ndfp_tms()
	{
		global $conf, $langs, $user;

        if (!$this->rowid)
        {
			$this->error = $langs->trans('LineIdIsMissing');
			return -1;             
        }
        

        // Update timestamp of ndfp
        $tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp SET tms = '".$this->db->idate($tms)."' WHERE rowid = ".$this->fk_ndfp;       
        dol_syslog("NdfpLine::update_ndfp_tims sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
        
        if ($result > 0)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;            
            return -1;
        }
                
	}
    
    /**
     *      \brief     	Update totals of the line
     *		@return		int		<0 si ko, >0 si ok
     */
    function update_total()
    {
        
        if (!$this->rowid){
			$this->error = $langs->trans('LineIdIsMissing');
			return -1;             
        }
        
        dol_syslog("NdfpLine::update_total", LOG_DEBUG); 
        $this->tms = dol_now();       
         
        $this->db->begin();

        $this->total_ttc = price2num($this->total_ttc, 'MT');                                               
        $this->total_ht = price2num($this->total_ht, 'MT');
        $this->total_tva = price2num($this->total_ttc - $this->total_ht);          
        //
        $sql = "UPDATE ".MAIN_DB_PREFIX."ndfp_det SET";
        $sql.= " `total_ht` = ".$this->total_ht.",";
        $sql.= " `total_tva` = ".$this->total_tva.",";
        $sql.= " `total_ttc` = ".$this->total_ttc.",";
        $sql.= " `tms` = '".$this->db->idate($this->tms)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            $this->db->rollback();
            
            return -2;
        }
    }
}
?>
