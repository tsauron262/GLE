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
 *	\file       htdocs/core/boxes/box_ndfp.php
 *	\ingroup    ndfp
 *	\brief      Module de generation de l'affichage de la box de notes de frais
 */
include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_ndfp extends ModeleBoxes {

	var $boxcode = "lastndfps";
	var $boximg = "ndfp@ndfp";
	var $boxlabel;
	var $depends = array("ndfp");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_ndfp()
	{
		global $langs;
		$langs->load("boxes");
        $langs->load("ndfp");
        
		$this->boxlabel = $langs->trans("BoxLastNdfp");
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		include_once(DOL_DOCUMENT_ROOT."/ndfp/class/ndfp.class.php");
        
		$ndfpstatic = new Ndfp($db);
        $userstatic = new User($db);
        
		$text = $langs->trans("BoxTitleLastNdfp",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text)
		);

		if ($user->rights->ndfp->allactions->read || $user->rights->ndfp->myactions->read)
		{
		  
            $sql = " SELECT n.rowid, n.ref, n.tms, n.fk_user, n.statut, n.fk_soc, n.dates, "; 
            $sql.= " u.rowid as uid, u.name, u.firstname, s.nom AS soc_name, s.rowid AS soc_id, u.login, n.total_tva, n.total_ht, n.total_ttc";
            $sql.= " FROM ".MAIN_DB_PREFIX."ndfp as n";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user AS u ON n.fk_user = u.rowid";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON s.rowid = n.fk_soc";   
            $sql.= " WHERE n.entity = ".$conf->entity;
            
            if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
            {
                $sql.= " AND n.fk_user = ".$user->id; // Only get mine notes    
            }
        
            if ($user->societe_id) // Security check
            {
               $sql.= " AND n.fk_soc = ".$user->societe_id; 
            } 
    

            $sql.= " ".$db->order('n.tms', 'DESC');
            $sql.= " ".$db->plimit($max, 0);

            dol_syslog("BoxNdfp sql=".$sql, LOG_DEBUG);

			$result = $db->query($sql);
            
			if ($result)
			{
				$num = $db->num_rows($result);
				$now = dol_now();

				$i = 0;
				

				while ($i < $num)
				{
					$objp = $db->fetch_object($result);

                    $userstatic->nom  = $objp->name;
                    $userstatic->prenom = $objp->firstname;
                    $userstatic->id = $objp->uid;
            
                    $ndfpstatic->id = $objp->rowid;
                    $ndfpstatic->ref = $objp->ref;
            
                    $already_paid = $ndfpstatic->get_amount_payments_done();
					$picto = 'bill';

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $picto,
                    'url' => DOL_URL_ROOT."/ndfp/ndfp.php?id=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->ref,
                    'url' => DOL_URL_ROOT."/ndfp/ndfp.php?id=".$objp->rowid);

					$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
                    'logo' => 'user',
                    'url' => DOL_URL_ROOT."/user/fiche.php?id=".$objp->fk_user);

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
                    'text' => $userstatic->getFullName($langs),
                    'maxlength' => 40,
                    'url' => DOL_URL_ROOT."/user/fiche.php?id=".$objp->fk_user);

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
                    'text' => dol_print_date($db->jdate($objp->datec),'day'),
					);

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
                    'text' => $ndfpstatic->lib_statut($objp->statut, 3, price2num($already_paid)));

					$i++;
				}

				if ($num == 0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedNdfp"));
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}

		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}
	}

	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
