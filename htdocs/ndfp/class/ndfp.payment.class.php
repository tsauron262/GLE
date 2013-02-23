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
 *      \file       htdocs/ndfp/class/ndfp.payment.class.php
 *      \ingroup    ndfp
 *      \brief      File of class to manage trips and working credit notes payments
 */

require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/ndfp/class/ndfp.class.php');
/**
 *      \class      NdfpPayment
 *      \brief      Class to manage trips and working credit notes payments
 */
class NdfpPayment extends Paiement
{
	var $db;
	var $error;
	var $element = 'ndfp_payment';
	var $table_element = 'ndfp_pay';
	var $table_element_line = 'ndfp_pay_det';
	var $fk_element = 'fk_ndfp_payment';
	var $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
 
    
	var $id;
    
    var $fk_payment = 0;
    var $fk_account = 0;

    
    var $amounts = array();
    
    var $fk_user_author; 
    var $fk_user;     
    var $payment_number = ''; 
    var $payment_code = '';
    var $payment_label = '';      
    var $note = '';
    var $fk_bank;
    
    var $datec;          
    var $datep;         
    var $tms;
    
    var $lines = array();
   /**
	*  \brief  Constructeur de la classe
	*  @param  DB          handler acces base de donnees
	*/
	function NdfpPayment($DB)
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
            return call_user_func_array(array($this, $action), $args); 
        }
        else
        { 
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
        if ($this->fk_payment <= 0){
			$this->error = $langs->trans('ErrorBadParameterPayment');
			return -1;            
        }

        // Check parameters
        if ($this->fk_user <= 0){
			$this->error = $langs->trans('ErrorBadParameterUser');
			return -1;            
        }
                
        if ($conf->banque->enabled)
        {        
            if ($this->fk_account <= 0){
    			$this->error = $langs->trans('ErrorBadParameterAccount');
    			return -1;            
            }
        }             

        if (empty($this->datep)){
			$this->error = $langs->trans('ErrorBadParameterDatePayment');
			return -1;            
        }
        
        if (sizeof($this->amounts) == 0){
			$this->error = $langs->trans('ErrorBadParameterAmounts');
			return -1;             
        }

        foreach($this->amounts AS $fk_ndfp => $amount){
            if (!is_numeric($amount) && !empty($amount)){
    			$this->error = $langs->trans('ErrorBadParameterOneAmount');
    			return -1;            
            }
        } 
                
        return 1;               
     }
     
	/**
	 * \brief Check user permissions
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function check_user_rights($user, $action = 'payment')
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
	 * @param 	user	      User that creates
     * @param 	close_ndfp	  Automatically close notes which are completely paid
	 * @return 	int		      <0 if KO, >0 if OK
	 */
	function create($user, $close_ndfp = 1)
	{
		global $conf, $langs;
        
        $result = $this->check_parameters();
        
        if ($result < 0)
        {
            return -1;
        }             

        $result = $this->check_user_rights($user, 'payment');
        if ($result < 0)
        {
            return -1;
        }
                           
        
        $this->datec = dol_now();
        $this->tms = dol_now();
        $this->fk_user_author = $user->id;
        $this->amount = 0;
        $this->fk_bank = 0;
        
        $ndfp = new Ndfp($this->db);
        
        foreach($this->amounts AS $fk_ndfp => $amount)
        {
            if ($amount > 0)
            {
                $ndfp->fetch($fk_ndfp);
                
                $already_paid  = $ndfp->get_amount_payments_done();
                $remain_to_pay = price2num($ndfp->total_ttc - $already_paid,'MT'); 
                
                if ($remain_to_pay == 0)
                {
                    $this->error = $langs->trans('NdfpAlreadyPaid');
                    return -1;
                }
                
                if ($amount > $remain_to_pay)
                {
                    $this->error = $langs->trans('AmountTooLarge');
                    return -1;
                }   
            }
            
            $this->amount += $amount;
        }
          
        $this->amount = price2num($this->amount);
                      
        $this->db->begin();
        
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ndfp_pay (";
        $sql.= "`fk_payment`";       
        $sql.= ", `amount`";
        $sql.= ", `fk_user`";
        $sql.= ", `fk_user_author`";
        $sql.= ", `payment_number`";
        $sql.= ", `note`";        
        $sql.= ", `fk_bank`";
        $sql.= ", `datec`";
		$sql.= ", `datep`";
        $sql.= ", `tms`";       
        $sql.= ") ";
        $sql.= " VALUES (";
		$sql.= " ".$this->fk_payment." ";;        
		$sql.= ", ".$this->amount." ";
		$sql.= ", ".$this->fk_user." ";
		$sql.= ", ".$this->fk_user_author." "; 
		$sql.= ", ".($this->payment_number ? "'".$this->db->escape($this->payment_number)."'" : "''");                
		$sql.= ", ".($this->note ? "'".$this->db->escape($this->note)."'" : "''");
		$sql.= ", ".$this->fk_bank." ";                                         
        $sql.= ", '".$this->db->idate($this->datec)."'";
        $sql.= ", '".$this->db->idate($this->datep)."'";
        $sql.= ", '".$this->db->idate($this->tms)."'";                
		$sql.= ")";

		dol_syslog("NdfpPayment::create sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ndfp");

            foreach ($this->amounts AS $fk_ndfp => $amount)
            {
                if (empty($amount))
                {
                    continue;
                }
                
                $payment_line = new NdfpPaymentLine($this->db);
                
                $payment_line->amount = price2num($amount);
                $payment_line->fk_ndfp_payment = $this->id;
                $payment_line->fk_ndfp = $fk_ndfp;
                
                $result = $payment_line->insert($user);
                
                if ($result < 0)
                {
                    $this->error = $payment_line->error;
                    
                    $this->db->rollback();
                    return -1;
                }
                
  		        $ndfp = new Ndfp($this->db);
  		        $ndfp->id = $fk_ndfp;
                                           
    		    if ($close_ndfp)
    		    {   
                    $ndfp->fetch($fk_ndfp);               
                    $already_paid = $ndfp->get_amount_payments_done();
                    $already_paid = price2num($already_paid, 'MT');
                    $remain_to_pay = price2num($ndfp->total_ttc - $already_paid, 'MT');
                    
                    if ($remain_to_pay == 0)
                    {
    			        $ndfp->set_paid($user);
                    }
    			}
                
                // Create PDF
                $ndfp->generate_pdf($user);
                                
            }
                            			
            $this->db->commit();
            
			return $this->id;
		}
		else
		{
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
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
        
        if ($result < 0){
            return -1;
        }

        if (!$this->id){
			$this->error = $langs->trans('NdfpPaymentIdIsMissing');
			return -1;             
        }

        $result = $this->check_user_rights($user, 'payment');
        if ($result < 0)
        {
            return -1;
        }
                        
		$this->db->begin();
        
        $this->tms = dol_now();
        
		$sql  = "UPDATE ".MAIN_DB_PREFIX."ndfp_pay ";	 
		$sql .= " SET `datep` = '".$this->db->idate($this->datep)."'";
        $sql .= ", `payment_number` = ".($this->payment_number ? "'".$this->db->escape($this->payment_number)."'" : "''");                           
		$sql .= ", `note` = ".($this->note ? "'".$this->db->escape(trim($this->note))."'" : "''");
		$sql .= ", `fk_bank` = ".$this->fk_bank;
        $sql .= ", `tms` = '".$this->db->idate($this->tms)."'";        
        $sql .= " WHERE rowid = ".$this->id;

		dol_syslog("NdfpPayment::update sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result)
		{
			$this->db->commit();
            $this->error = $langs->trans('NdfpPaymentBasBeenUpdated');
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
	 * Fetch object from database
	 *
	 * @param 	id	    Id of the note
     * @param 	ref  	Reference of the note
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
	    global $conf, $langs;
       
        if (!$id){
			$this->error = $langs->trans('NdfpPaymentIdIsMissing');
			return -1;             
        }
        
		$sql = "SELECT p.rowid, p.fk_payment, p.amount, p.fk_user, p.fk_user_author, p.payment_number, p.note";
        $sql.= ", p.fk_bank, p.datec, p.datep, p.tms, c.code as payment_code, c.libelle as payment_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp_pay AS p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_payment = c.id ";        
        $sql.= " WHERE rowid = ".$id;

        
		dol_syslog("NdfpPayment::fetch sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result > 0)
		{
            
			$obj = $this->db->fetch_object($result);

			$this->id                = $obj->rowid;
			$this->fk_payment        = $obj->fk_payment;
			$this->amount            = $obj->amount;         
			$this->fk_user_author    = $obj->fk_user_author;
            $this->fk_user           = $obj->fk_user;
			$this->payment_number    = $obj->payment_number;
            $this->payment_code      = $obj->payment_code;
            $this->payment_label      = $obj->payment_label;
            
            $this->note              = trim($obj->note);
            $this->fk_bank           = $obj->fk_bank;
                      
			$this->datec             = $this->db->jdate($obj->datec);
			$this->datep             = $this->db->jdate($obj->datep);                      
           
            $this->tms               = $this->db->jdate($obj->tms);
            
            if ($this->id)
            {
                $result = $this->fetch_lines();
                
                if ($result > 0)
                {
                    return 1;
                }
                else
                {
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
	 * \brief Fetch object lines from database
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function fetch_lines()
    {
	    global $conf, $langs;
       
        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpPaymentIdIsMissing');
			return -1;             
        }
        
		$sql = " SELECT `rowid`, `fk_ndfp`, `amount`, `tms`";
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp_pay_det WHERE fk_ndfp_payment = ".$this->id;

        
		dol_syslog("NdfpPayment::fetch_lines sql=".$sql, LOG_DEBUG);
        
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
	 * \brief Delete object from database
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

		if ($this->fk_bank)
		{
			$account = new AccountLine($this->db, $this->fk_bank);
			$result = $account->fetch($this->fk_bank);

            if ($result < 0)
            {
    			$this->error = $langs->trans('AccountDoesNotExist');
    			return -3;                
            }
                        
			if ($account->rappro)
			{
				$this->error = $langs->trans('CanNotDeleteBankEntry');
				return -3;
			}
		}
                               	   
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ndfp_pay WHERE rowid = ".$this->id;

		dol_syslog("NdfpPayment::delete sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
		if ($result)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ndfp_pay_det WHERE fk_ndfp_payment = ".$this->id;
    
    		dol_syslog("NdfpPayment::delete sql=".$sql, LOG_DEBUG);
            
    		$result = $this->db->query($sql);

    		if ($result)
    		{
        		if ($this->fk_bank)
        		{
        			$result = $account->delete();
                    
        			if ($result < 0)
            		{
            			$this->error = $this->db->error()." sql=".$sql;
            			return -1;
            		}
        		}
            	
                $this->error = $langs->trans('NdfpPaymentBasBeenDeleted');
                	  
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
     *		\brief		Confirm delete
     *		@param		user		Object user that confirms
     *		@return		int			<0 if KO, >0 if OK
     */
    function confirm_delete($user)
    {
        
        global $conf, $langs;

        $confirm = GETPOST('confirm');

        $result = $this->check_user_rights($user, 'payment');
        
        if ($result < 0)
        {
            return -1;
        }
        
        if ($confirm == 'yes')
        {
            $result = $this->delete($user);
            
            return $result;          
        }
        else
        {
            $this->error = $langs->trans('ActionCanceled');
            return -1;
        }

                
    }
        
    /**
     *		\brief		Add payment to database
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
     
    function confirm_add($user)
    {
        global $conf, $langs;
        
        $amounts = array();
        $total = 0;
        
        $fk_payment     = GETPOST('fk_payment');
        $fk_user        = GETPOST('fk_user');
        $note           = GETPOST('note');
        $payment_number = GETPOST('payment_number');
        $fk_account     = GETPOST('fk_account');
        $datep          = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        $close_ndfp     = (GETPOST('closepaidndfp') == 'on' ? 1 : 0);
        
        foreach ($_POST as $key => $value)
        {
            if (substr($key,0,7) == 'amount_')
            {
                $fk_ndfp = substr($key, 7);
                $amounts[$fk_ndfp] = $_POST[$key];            
            }
        }
               
    
        $this->fk_payment = $fk_payment; 
        $this->fk_account = $fk_account;
        $this->fk_user = $fk_user;      
        $this->amounts = $amounts;
        $this->payment_number = $payment_number;
        $this->note =  $note;       
        $this->fk_bank = 0;
    	$this->datep = $datep; 
                
        $this->id = $this->create($user, $close_ndfp);
        
        if ($this->id < 0)
        {
            return -1;
        }
    
        if ($conf->banque->enabled)
        {    
            $result = $this->add_payment_to_bank($user);
            
            if ($result < 0)
            {
                return -1;
            }
        }

        $this->fetch($this->id);
        $this->error = $langs->trans('NdfpPaymentBasBeenCreated');
                    
        return 1;               
                  
    }
    /**
     *		\brief		Set payment date
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function set_datep($user)
    {
        global $conf, $langs;

        $datep = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);


        $result = $this->check_user_rights($user, 'payment');
        if ($result < 0)
        {
            return -1;
        }
        
        if (empty($datep))
        {
			$this->error = $langs->trans('ErrorBadParameterDatePayment');
			return -1;            
        }
        
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp_pay SET datep = '".$this->db->jdate($datep)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;
        
        dol_syslog("NdfpPayment::set_datep sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->datep = $this->db->jdate($datep);
            $this->error = $langs->trans('NdfpPaymentBasBeenUpdated');
            
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
     *		\brief		Set payment number
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function set_payment_number($user)
    {
        global $conf, $langs;

        $payment_number = GETPOST('payment_number');


        $result = $this->check_user_rights($user, 'payment');
        if ($result < 0)
        {
            return -1;
        }
        
        $this->tms = dol_now();
                
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp_pay SET payment_number = '".$this->db->escape($payment_number)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;    
        dol_syslog("NdfpPayment::set_datep sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->payment_number = $payment_number;
            $this->error = $langs->trans('NdfpPaymentBasBeenUpdated');
            
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
     *		\brief		Set note
     *		@param		user		Object user that modify
     *		@return		int			<0 if KO, >0 if OK
     */
    function set_note($user)
    {
        global $conf, $langs;
        
        $note = trim(GETPOST('note', 'chaine'));


        $result = $this->check_user_rights($user, 'payment');
        if ($result < 0)
        {
            return -1;
        }
                
        $this->tms = dol_now();
        
        $sql = " UPDATE ".MAIN_DB_PREFIX."ndfp_pay SET note = '".$this->db->escape($note)."', tms = '".$this->db->idate($this->tms)."' WHERE rowid = ".$this->id;    
        dol_syslog("NdfpPayment::set_datep sql=".$sql, LOG_DEBUG);
        
        $result = $this->db->query($sql);
                
        if ($result > 0)
        {
            $this->note = $note;
            $this->error = $langs->trans('NdfpPaymentBasBeenUpdated');
            
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
     *		\brief		Add payment to bank
     *		@param		user		Object user that adds
     *		@return		int			<0 if KO, >0 if OK
     */    
    function add_payment_to_bank($user)
    {
        global $conf, $langs;
        
        if ($conf->banque->enabled)
        {        
            if ($this->fk_account <= 0)
            {
    			$this->error = $langs->trans('ErrorBadParameterAccount');
    			return -1;            
            }
            
            $this->fk_bank = 0;

            $acc = new Account($this->db);
            $result = $acc->fetch($this->fk_account);

            if ($result < 0)
            {
    			$this->error = $langs->trans('AccountDoesNotExist');
    			return -1;                
            }
            
            $totalamount = 0;
            foreach($this->amounts AS $fk_ndfp => $amount){
                $totalamount += $amount;
            }  
        
            $totalamount = -$totalamount;

            // Insert payment into llx_bank
            $this->fk_bank = $acc->addline($this->datep, $this->fk_payment,  $langs->trans('UserInvoicePayment'),  $totalamount, $this->payment_number, '', $user, '', '');

            // Update fk_bank 
            if ($this->fk_bank > 0)
            {
                $result = $this->update($user);

                if ($result < 0)
                {
                    return -1;
                }
                // Can not add links to account as specific modes are not dealt with 
                // So return bank_line_id
                       
                return $this->fk_bank;                           
            }
            else
            {
                $this->error = $acc->db->error();
                return -1;
            }
        }
        else
        {
            $this->error = $langs->trans('BankIsDisabled');
            return -1;             
        }
               
    }

    /**
     *      \brief Return clicable link of object (with eventually picto)
     *      @param      withpicto       Add picto into link
     *      @param      option          Where point the link
     *      @param      max             Maxlength of ref
     *      @return     string          String with URL
     */
    function get_clickable_name($withpicto = 0, $option = '', $max = 0, $short = 0)
    {
        global $langs;

        $result = '';

        $url = DOL_URL_ROOT.'/ndfp/payment.php?id='.$this->id;

        if ($short) return $url;

        $picto = 'payment';

        $label = $langs->trans("ShowPayment").': '.$this->ref;

        if ($withpicto) $result.= ('<a href="'.$url.'">'.img_object($label, $picto).'</a>');
        if ($withpicto && $withpicto != 2) $result.= ' ';
        if ($withpicto != 2) $result.= '<a href="'.$url.'">'.($max ? dol_trunc($this->ref, $max) : $this->ref).'</a>';
        
        return $result;
    }
        
	/**
	 * \brief Get notes for this payment
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
    function get_ndfps()
    {
	    global $conf, $langs;
       
        $ndfps = array();
       
        if (!$this->id)
        {
			$this->error = $langs->trans('NdfpPaymentIdIsMissing');
			return -1;             
        }
        
		$sql = " SELECT n.rowid, p.fk_ndfp, n.statut, n.ref, n.fk_user, n.total_ttc, p.amount,";
        $sql.= " u.rowid as uid, u.name, u.firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp_pay_det AS p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."ndfp AS n ON n.rowid = p.fk_ndfp";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON n.fk_user = u.rowid";
        $sql.= " WHERE p.fk_ndfp_payment = ".$this->id;

        
		dol_syslog("NdfpPayment::get_ndfps sql=".$sql, LOG_DEBUG);
        
		$result = $this->db->query($sql);
        
        $ndfpstatic = new Ndfp($this->db);
        $userstatic = new User($this->db);
        
		if ($result > 0)
		{
            
            $num = $this->db->num_rows($result);
            $i = 0;
            
            if ($num)
            {
                while ($i < $num)
                {
        			$obj = $this->db->fetch_object($result);                
                    
                    $ndfpstatic->id = $obj->fk_ndfp;
                    $ndfpstatic->ref = $obj->ref;
                    $ndfpstatic->statut = $obj->statut;
                    
                    $userstatic->nom  = $obj->name;
                    $userstatic->prenom = $obj->firstname;
                    $userstatic->id = $obj->uid;
                    
                    $already_paid = $ndfpstatic->get_amount_payments_done();
                    
                    $obj->ndfp = $ndfpstatic->get_clickable_name(1);
                    $obj->user = $userstatic->getNomUrl(1);
                    $obj->remain_to_pay = $obj->total_ttc - $already_paid;      
                    $obj->statut = $ndfpstatic->get_lib_statut(5, $already_paid);
                    
        			$ndfps[$i] = $obj;
                    
                    $i++;                     
                }      
            }       			
		}
        else
        {
            $this->error = $this->db->error()." sql=".$sql;
            
            return -1;            
        }
        
        return $ndfps;
		       
    } 
                                            
}

/**
 *	\class      	NdfpPaymentLine
 *	\brief      	Class to manage payments of a credit note
 *	\remarks		Uses lines of llx_ndfp_pay_det tables
 */
class NdfpPaymentLine
{
    var $db;
    var $error;

    var $oldline;

    //! From llx_ndfp_pay_det
    var $rowid;
    var $fk_ndfp_payment;     
    var $fk_ndfp;	
      
    var $amount;			 		  
                        		       

    var $tms;

   /**
	*  \brief  Constructeur de la classe
	*  @param  DB          handler acces base de donnees
	*/
    function NdfpPaymentLine($DB)
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
        if ($this->fk_ndfp_payment <= 0){
			$this->error = $langs->trans('ErrorBadParameterNdfpPayment');
			return -1;            
        }
        
        if ($this->fk_ndfp <= 0){
			$this->error = $langs->trans('ErrorBadParameterNdfp');
			return -1;            
        }

        if (!is_numeric($this->amount) || $this->amount < 0){
			$this->error = $langs->trans('ErrorBadParameterAmount');
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
                
        $sql = 'SELECT np.rowid, np.fk_ndfp, np.fk_ndfp_payment, np.amount, np.tms';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'ndfp_pay_det as np';
        $sql.= ' WHERE np.rowid = '.$rowid;

        dol_syslog("NdfpPaymentLine::fetch sql=".$sql);
        
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
                $this->fk_ndfp_payment		= $obj->fk_ndfp_payment;               
                
                $this->amount		        = $obj->amount;
                               
                $this->tms               = $this->db->jdate($obj->tms);
    
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
    function insert($user)
    {
        global $langs, $conf;

        $result = $this->check_parameters();
        
        if ($result < 0)
        {
            return -1;
        }
        
        // Clean parameters
        $this->amount = price2num($this->amount, 'MT');                        

                            
        $this->db->begin();
        $this->tms = dol_now();
        
        // 
        $sql = " INSERT INTO ".MAIN_DB_PREFIX."ndfp_pay_det";
        $sql.= " (fk_ndfp_payment, fk_ndfp, amount, tms)";
        $sql.= " VALUES (".$this->fk_ndfp_payment.", ".$this->fk_ndfp.", ".$this->amount.", '".$this->db->idate($this->tms)."' )";

        dol_syslog("NdfpPaymentLine::insert sql=".$sql);
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX.'ndfp_pay_det');

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
	 * 	\brief Delete line in database
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function delete($user)
	{
		global $conf,$langs;

        if (!$this->rowid){
			$this->error = $langs->trans('PaymentIdIsMissing');
			return -1;             
        }
        
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ndfp_pay_det WHERE rowid = ".$this->rowid;
        
		dol_syslog("NdfpPaymentLine::delete sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        
		if ($result)
		{
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
}
?>
