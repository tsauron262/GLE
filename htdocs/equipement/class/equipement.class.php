<?php
/* Copyright (C) 2012-2016	Charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file       htdocs/equipement/class/equipement.class.php
 * 	\ingroup    equipement
 * 	\brief      Fichier de la classe des gestion des �quipements
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 * 	\class      Equipement
 *	\brief      Classe des gestion des equipements
 */
class Equipement extends CommonObject
{
	public $element='equipement';
	public $table_element='equipement';
	public $fk_element='fk_equipement';
	public $table_element_line='equipementevt';

	var $id;					// ID de l'�quipement
	var $ref;					// num�ro de s�rie unique pour l'�quipement
	var $fk_product;			// ID du produit
	var $fk_product_batch;		// num�ro de lot
	var $numimmocompta;			// num�ro de compte immo pour les recherches
	var $numversion; 			// num�ro de version associ� au produit
	var $fk_soc_fourn;			// ID du fournisseur du produit
	var $fk_fact_fourn;			// ID fact fournisseur du produit
	var $ref_fourn;				// r�f�rence produit du fournisseur (non stock�e en base, juste pour la g�n�ration multiple)
	var $fk_soc_client;			// Id client du produit
	var $fk_fact_client;		// ID fact client du produit
	var $fk_contact;			// contact � qui est rattach� l'�quipement si besoin (sert accessoirement � sa localisation)
	var $client;				// Objet societe client (a charger par fetch_client)
	var $fk_etatequipement;		// �tat de l'�quipement
	var $etatequiplibelle;		// �tat de l'�quipement (lib�ll�
	var $fk_entrepot;			// entrepot de l'�quipement chez soit
	var $isentrepotmove;		// on effectue un mouvement de stock oui/non

	var $unitweight;			// poids unitaire de l'�quipement	
	var $SerialMethod;			// M�thode de g�n�ration des num�ros de s�rie
	var $quantity;				// Quantit� de produit en cas de gestion par lot
	var $nbCreateEquipement; 	// nombre d'�quipement � cr�er
	var $fk_factory;			// ID de la fabrication

	var $author;
	var $datec;		// date cr�ation de l'�quipement
	var $dateo;		// date de d�but de l'�quipement
	var $datee;		// date de fin de l'�quipement

	var $barcode;				// value
	var $barcode_type;			// id
	var $barcode_type_code;		// code (loaded by equipement_fetch_barcode)
	var $barcode_type_label;	// label (loaded by equipement_fetch_barcode)
	var $barcode_type_coder;	// coder (loaded by equipement_fetch_barcode)
	
	var $statut;				// 0=draft, 1=validated, 2=closed
	var $localisation;
	var $description;
	var $note_private;
	var $note_public;

	var $model_pdf;

	var $extraparams=array();

	var $lines = array();

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db ;
		$this->statut = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[1]='Validated';
		$this->statuts[2]='Closed';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='Validated';
		$this->statuts_short[2]='Closed';
		$this->statuts_image[0]='statut0';
		$this->statuts_image[1]='statut4';
		$this->statuts_image[2]='statut6';
	}

	/**
	 *	Create an equipement into data base
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create($notrigger=0)
	{
		global $conf;
		global $user; // todo pass $user in parameter of function
		global $soc;
		global $langs;

		dol_syslog(get_class($this)."::create ref=".$this->ref);

		// Check parameters
		if ($this->fk_product <= 0)
		{
			$this->error='ErrorBadParameterForFunc';
			dol_syslog(get_class($this)."::create ".$this->error,LOG_ERR);
			return -1;
		}

		$now=dol_now();

		$i=0;

		// en mode s�rialisation externe, on d�termine le nombre de num�ro de s�rie transmi
		if ($this->SerialMethod == 2)
		{
			$separatorlist=$conf->global->EQUIPEMENT_SEPARATORLIST;
			$separatorlist =($separatorlist ? $separatorlist : ";");
			if ($separatorlist == "__N__")
				$separatorlist="\n";
			if ($separatorlist == "__B__")
				$separatorlist="\b";

			$tblSerial=explode($separatorlist, $this->SerialFourn);
			
			$nbCreateSerial=count($tblSerial);
			// si on a des ref, on d�termine le nombre d'�quipement � cr�er
			$this->nbAddEquipement = $nbCreateSerial;
			dol_syslog(get_class($this)."::Addmethod2 nb2create =".$nbCreateSerial);
		}

		// boucle sur les num�ros de s�rie pour cr�er autant d'�quipement
		while($this->nbAddEquipement > $i)
		{
			// r�cup de la ref suivante
			$this->date = dol_now();

			// si on est en mode code fournisseur
			switch($this->SerialMethod)
			{
				case 1 : // en mode g�n�ration auto, on cr�e des num�ros s�rie interne
					$obj = $conf->global->EQUIPEMENT_ADDON;
					$modequipement = new $obj;
					$numpr = $modequipement->getNextValue($soc, $this);
					break;

				case 2 :	// on r�cup�re le num�ro de s�rie dans la liste fournis
					// attention on peut ne r�cup�rer qu'un bout du num�ro
					if($conf->global->EQUIPEMENT_BEGINKEYSERIALLIST != 0)
						$numpr=substr($tblSerial[$i],$conf->global->EQUIPEMENT_BEGINKEYSERIALLIST);
					else
						$numpr=$tblSerial[$i];
					break;

				case 3 :  // en mode s�rie par lot, on reprend le num�ro de lot comme num�ro de s�rie
					// si en mode d�coupage on r�cup�re la ref, en cr�ation on r�cup�re le num�ro de lot
					if ($this->ref)
						$numpr=$this->ref;
					else
						$numpr=$this->numversion;
					break;
			}

			$this->db->begin();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."equipement (";
			$sql.= "fk_product";
			$sql.= ", fk_soc_client";
			$sql.= ", fk_soc_fourn";
			$sql.= ", fk_facture_fourn";
			$sql.= ", datec";
			$sql.= ", datee";
			$sql.= ", dateo";
			$sql.= ", ref";
			$sql.= ", numversion";
			$sql.= ", quantity";
			$sql.= ", unitweight";
			$sql.= ", entity";
			$sql.= ", fk_user_author";
			$sql.= ", fk_entrepot";
			$sql.= ", fk_product_batch";
			$sql.= ", description";
			$sql.= ", fk_statut";
			$sql.= ", note_private";
			$sql.= ", note_public";
			$sql.= ", model_pdf";
			$sql.= ") ";
			$sql.= " VALUES (";
			$sql.= $this->fk_product;
			$sql.= ", ".($this->fk_soc_client?$this->db->escape($this->fk_soc_client):"null");
			$sql.= ", ".($this->fk_soc_fourn?$this->db->escape($this->fk_soc_fourn):"null");
			$sql.= ", ".($this->fk_facture_fourn?$this->db->escape($this->fk_facture_fourn):"null");
			$sql.= ", '".$this->db->idate($now)."'";
			$sql.= ", ".($this->datee?"'".$this->db->idate($this->datee)."'":"null");
			$sql.= ", ".($this->dateo?"'".$this->db->idate($this->dateo)."'":"null");
			$sql.= ", '".$numpr."'";
			$sql.= ", '".$this->numversion."'";
			$sql.= ", ".$this->quantity;
			$sql.= ", ".($this->unitweight?$this->unitweight:"null");
			$sql.= ", ".$conf->entity;
			$sql.= ", ".$this->author;
			$sql.= ", ".($this->fk_entrepot?$this->db->escape($this->fk_entrepot):"null");
			$sql.= ", ".($this->fk_product_batch?$this->db->escape($this->fk_product_batch):"null");
			$sql.= ", ".($this->description?"'".$this->db->escape($this->description)."'":"null");
			$sql.= ", ".($this->fk_entrepot>0?"1":"0"); // si il y a un entrepot de s�lectionn� on active ou non l'�quipement
			$sql.= ", ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
			$sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
			$sql.= ", ".($this->model_pdf?"'".$this->db->escape($this->model_pdf)."'":"null");
			$sql.= ")";
			dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
			$result=$this->db->query($sql);

			if ($result)
			{
				$i++;
				$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."equipement");

				// si on veut faire un mouvement correspondant � la cr�ation
				// et que l'on utilise pas product batch
				if ($this->isentrepotmove && $this->fk_entrepot > 0 && $this->fk_product_batch !=-1)
				{
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$mouvP = new MouvementStock($this->db);
					$mouvP->origin = new Equipement($this->db);
					$mouvP->origin->id = $this->id;

					$idmv=$mouvP->reception($user, $this->fk_product, $this->fk_entrepot, $this->quantity, 0, 
							$langs->trans("EquipementMoveIn"));
				}

				if (! $notrigger)
				{
					// Call trigger
					$result=$this->call_trigger('EQUIPEMENT_CREATE',$user);
					if ($result < 0) $error++;
					// End call triggers
				}
			}
			else
			{
				dol_print_error($this->db);
				$error++;
			}

			if (! $error)
			{
				$this->db->commit();
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				break;
			}
			
			// si factory est pr�sent on v�rifie si il est n�cessaire de cr�er la liaison
			if($conf->global->MAIN_MODULE_FACTORY && $this->fk_factory > 0 )
			{
				// on ajoute la liaison avec l'of
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."equipement_factory (fk_equipement, fk_factory) ";
				$sql.= " values (".$this->id.", ".$this->fk_factory.")";
				$result=$this->db->query($sql);
				
				// on cr�e le lien vers l'of
				$ret = $this->add_object_linked('factory', $this->fk_factory);


			}
		}

		// si on est all� jusqu'� la fin des cr�ation
		if ($this->nbAddEquipement == $i)
		{
			// on se positionne sur le dernier cr�e en modif
			return $this->id;
		}
		else
		{	// sinon on revient � la case d�part
			return -1;
		}
	}

	/**
	 *	Fetch a equipement
	 *
	 *	@param		int		$rowid		Id of equipement
	 *	@param		string	$ref		Ref of equipement
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch($rowid, $ref='')
	{
		$sql = "SELECT e.rowid, e.ref, e.description, e.fk_soc_fourn, e.fk_facture_fourn, e.fk_statut, e.fk_entrepot,";
		$sql.= " e.numversion, e.numimmocompta, e.fk_etatequipement, ee.libelle as etatequiplibelle, e.quantity,";
		$sql.= " e.datec, e.datev, e.datee, e.dateo, e.tms as datem, e.unitweight, e.fk_product_batch,";
		$sql.= " e.fk_product, e.fk_soc_client, e.fk_facture,";
		$sql.= " e.note_public, e.note_private ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
		if ($ref) $sql.= " WHERE e.ref='".$this->db->escape($ref)."'";
		else $sql.= " WHERE e.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->ref;
				$this->description  	= $obj->description;
				$this->socid			= $obj->fk_soc;
				$this->statut			= $obj->fk_statut;
				$this->numversion		= $obj->numversion;
				$this->quantity			= $obj->quantity;
				$this->unitweight		= price($obj->unitweight);
				$this->numimmocompta	= $obj->numimmocompta;
				$this->dateo			= $this->db->jdate($obj->dateo);
				$this->datee			= $this->db->jdate($obj->datee);
				$this->datec			= $this->db->jdate($obj->datec);
				$this->datev			= $this->db->jdate($obj->datev);
				$this->datem			= $this->db->jdate($obj->datem);
				$this->fk_product		= $obj->fk_product;
				$this->fk_soc_fourn		= $obj->fk_soc_fourn;
				$this->fk_fact_fourn	= $obj->fk_facture_fourn;
				$this->fk_soc_client	= $obj->fk_soc_client;
				$this->fk_fact_client	= $obj->fk_facture;
				$this->fk_entrepot		= $obj->fk_entrepot;
				$this->fk_product_batch	= $obj->fk_product_batch;
				$this->fk_etatequipement= $obj->fk_etatequipement;
				$this->etatequiplibelle	= $obj->etatequiplibelle;
				$this->note_public		= $obj->note_public;
				$this->note_private		= $obj->note_private;
				$this->model_pdf		= $obj->model_pdf;
				$this->fulldayevent 	= $obj->fulldayevent;

				$this->extraparams	= (array) json_decode($obj->extraparams, true);

				if ($this->statut == 0) $this->brouillon = 1;

				/*
				 * r�cup�ration des Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					return -3;
				}
				$this->db->free($resql);
				return 1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::fetch ".$this->error,LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setDraft($user)
	{
		global $langs, $conf;

		if ($this->statut != 0)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_statut = 0";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Equipement::setDraft sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("Equipement::setDraft ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function updateInfos($user, $bmoveentrepot=0)
	{
		global $langs, $conf;

		$updtSep=" SET";
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
		if ($this->fk_etatequipement > 0)
		{
			$sql.= $updtSep." fk_etatequipement=".$this->fk_etatequipement;
			$updtSep=" ,";
		}
		elseif ($this->fk_etatequipement == -1)
		{
			$sql.= $updtSep." fk_etatequipement=null";
			$updtSep=" ,";
		}

		if ($this->fk_soc_client > 0)
		{
			$sql.= $updtSep." fk_soc_client=".$this->fk_soc_client;
			$updtSep=" ,";
		}
		elseif ($this->fk_soc_client == -1)
		{
			$sql.= $updtSep." fk_soc_client=null";
			$updtSep=" ,";
		}

		// gestion de l'entrepot � part (inclut les mouvements)
		$this->set_entrepot($user, $this->fk_etatentrepot, $bmoveentrepot);

		if ($this->fk_statut != -1)
		{
			$sql.= $updtSep." fk_statut=".$this->fk_statut;
		}

		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;
//print $sql."<br>";
		// si on a fait une mise � jour
		if ($updtSep != " SET")
		{
			dol_syslog("Equipement::updateInfos sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
				return 1;
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("Equipement::updateInfos ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}


	/**
	 *	Validate a Equipement
	 *
	 *	@param		User		$user		User that validate
	 *	@param		string		$outputdir	Output directory
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setValid($user, $outputdir)
	{
		global $langs, $conf;

		$error=0;

		if ($this->statut != 1)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_statut = 1";
			$sql.= ", datev = '".$this->db->idate(mktime())."'";
			$sql.= ", fk_user_valid = ".$user->id;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			dol_syslog("Equipement::setValid sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('EQUIPEMENT_VALIDATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if (! $error)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					$this->error=join(',',$this->errors);
					dol_syslog("Equipement::setValid ".$this->error,LOG_ERR);
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("Equipement::setValid ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}


	/**
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Returns the label of a statut
	 *
	 *	@param      int		$statut     id statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)		return $langs->trans($this->statuts[$statut]);

		if ($mode == 1)		return $langs->trans($this->statuts_short[$statut]);

		if ($mode == 2)		return img_picto(	$langs->trans($this->statuts_short[$statut]),
								$this->statuts_image[$statut].' '.$langs->trans($this->statuts_short[$statut]));

		if ($mode == 3)		return img_picto(	$langs->trans($this->statuts_short[$statut]), 
								$this->statuts_image[$statut]);

		if ($mode == 4)		return img_picto(	$langs->trans($this->statuts_short[$statut]),
								$this->statuts_image[$statut]).' '.$langs->trans($this->statuts[$statut]);

		if ($mode == 5)		return $langs->trans($this->statuts_short[$statut]).' '.
					img_picto(	$langs->trans($this->statuts_short[$statut]), $this->statuts_image[$statut]);
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the link, 2=Picto only
	 *	@param		int			$tolink		0=to main car, 1=to Event

	 *	@return		string						String with URL
	 */
	function getNomUrl($withpicto=0,$link=0)
	{
		global $langs;

		$result='';
		if ($link==1)
			$lien = '<a href="'.dol_buildpath('/equipement/events.php?id='.$this->id,1).'"';
		else
			$lien = '<a href="'.dol_buildpath('/equipement/card.php?id='.$this->id,1).'"';
		$lienfin='</a>';

		$picto='equipement@equipement';

		$label=$langs->trans("Show").': '.$this->ref;

		$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		$linkclose.=' class="classfortooltip" >';
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('equipementdao'));
		$parameters=array('id'=>$this->id);
		$reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
		$linkclose = ($hookmanager->resPrint ? $hookmanager->resPrint : $linkclose);

		if ($withpicto) $result.=($lien.$linkclose.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$linkclose.$this->ref.$lienfin;
		if ($this->quantity > 1)
			$result.="(".$this->quantity.")";
		return $result;
	}


	/**
	 *	Returns the next non used reference of intervention
	 *	depending on the module numbering assets within EQUIPEMENT_ADDON
	 *
	 *	@param	    Societe		$soc		Object society
	 *	@return     string					Free reference for intervention
	 */
	function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("equipement@equipement");

		$dir = dol_buildpath("/core/modules/equipement/",1);

		if (! empty($conf->global->EQUIPEMENT_ADDON))
		{
			$file = $conf->global->EQUIPEMENT_ADDON.".php";
			$classname = $conf->global->EQUIPEMENT_ADDON;
			if (! file_exists($dir.$file))
			{
				$file='mod_'.$file;
				$classname='mod_'.$classname;
			}

			// Chargement de la classe de numerotation
			require_once($dir.$file);

			$obj = new $classname();

			$numref = "";
			$numref = $obj->getNumRef($soc,$this);

			if ( $numref != "")
				return $numref;
			else
			{
				dol_print_error($db,"Equipement::getNextNumRef ".$obj->error);
				return "";
			}
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_EQUIPEMENT_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * 	Information sur l'objet fiche equipement
	 *
	 *	@param	int		$id      Id de la fiche equipement
	 *	@return	void
	 */
	function info($id)
	{
		global $conf;

		$sql = "SELECT e.rowid,";
		$sql.= " datec,";
		$sql.= " datev,";
		$sql.= " fk_user_author,";
		$sql.= " fk_user_valid";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE e.rowid = ".$id;
		$sql.= " AND e.entity = ".$conf->entity;

		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id				= $obj->rowid;
				$this->date_creation	= $this->db->jdate($obj->datec);
				$this->date_validation	= $this->db->jdate($obj->datev);

				$cuser = new User($this->db);
				$cuser->fetch($obj->fk_user_author);
				$this->user_creation	= $cuser;

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation	= $vuser;
				}
			}
			$this->db->free($result);
		}
		else
			dol_print_error($this->db);
	}

	/**
	 *	Delete Equipement
	 *
	 *	@param      User	$user	Object user who delete
	 *	@return		int				<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;
		require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementevt";
		$sql.= " WHERE fk_equipement = ".$this->id;

		dol_syslog("Equipement::delete sql=".$sql);
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipement";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Equipement::delete sql=".$sql);
			if ( $this->db->query($sql) )
			{
				// Remove directory with files
				$equipementref = dol_sanitizeFileName($this->ref);
				if ($conf->equipement->dir_output)
				{
					$dir = $conf->equipement->dir_output . "/" . $equipementref ;
					$file = $conf->equipement->dir_output . "/" . $equipementref . "/" . $equipementref . ".pdf";
					if (file_exists($file))
					{
						dol_delete_preview($this);

						if (! dol_delete_file($file,0,0,0,$this)) // For triggers
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (! dol_delete_dir_recursive($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT."/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('EQUIPEMENT_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) {
					$error++; $this->errors=$interface->errors;
				}
				// Fin appel triggers

				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Defines a entrepot of the equipement
	 *
	 *	@param      User	$user				Object user who define
	 *	@param      date	$fk_entrepot   		id of the entrepot
	 *	@return     int							<0 if ko, >0 if ok
	 */
	function set_entrepot($user, $fk_entrepot, $bmoveentrepot=0)
	{
		global $conf, $langs;

		if ($user->rights->equipement->creer)
		{
			$oldentrepot= $this->fk_entrepot;

			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_entrepot = ".($fk_entrepot!=-1? $fk_entrepot:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
//print "===".$sql."<br>";
			if ($this->db->query($sql))
			{
				$this->fk_entrepot = $fk_entrepot;
				// si on a chang� d'entrepot et on veut faire un mouvement
				if ($bmoveentrepot==1 && $oldentrepot != $fk_entrepot)
				{
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$mouvP = new MouvementStock($this->db);
					$mouvP->origin = $this;
					$mouvP->origin->id = $this->id;

					if ( $oldentrepot > 0 ) // si il y avait un ancien entrepot
						$idmv=$mouvP->livraison($user, $this->fk_product, $oldentrepot, $this->quantity, 0, // le prix est � 0 pour ne pas impacter le pmp
							$langs->trans("EquipementMoveOut"));

					if ($fk_entrepot > 0 )
						$idmv=$mouvP->reception($user, $this->fk_product, $fk_entrepot, $this->quantity, 0, 
							$langs->trans("EquipementMoveIn"));
				}

				//  gestion des sous composant si il y en a
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."equipementassociation ";
				$sql.= " WHERE fk_equipement_pere=".$thid-id;

				dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i = 0;
					$tblrep=array();
					while ($i < $num)
					{
						$objp = $this->db->fetch_object($resql);

						$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
						$sql.= " SET fk_entrepot = ".($fk_entrepot!=-1? $fk_entrepot:"null");
						$sql.= " WHERE rowid = ".$objp->fk_equipement_fils;
						$sql.= " AND entity = ".$conf->entity;
						if ($this->db->query($sql))
						{
							// si on a chang� d'entrepot et on veut faire un mouvement
							if ($bmoveentrepot && $oldentrepot != $fk_entrepot)
							{
								$tmpequipement = new Equipement($this->db);
								$mouvP->origin->id = $objp->fk_equipement_fils;

								if ( $oldentrepot >0 ) // si il y avait un ancien entrepot
									$idmv=$mouvP->livraison($user, $objp->fk_product, $entrepotold, 1, 0, $langs->trans("EquipementCompMoveOut"));

								if ($fk_entrepot > 0 )
									$idmv=$mouvP->reception($user, $objp->fk_product, $fk_entrepot, 1, 0, $langs->trans("EquipementCompMoveIn"));
							}
						}
						$i++;
					}
				}
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				print $this->db->error();
				dol_syslog("Equipement::set_entrepot Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Defines a etat of the equipement
	 *
	 *	@param      User	$user				Object user who define
	 *	@param      date	$fk_etatequipement	id of the entrepot
	 *	@return     int							<0 if ko, >0 if ok
	 */
	function set_etatEquipement($user, $fk_etatequipement)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
			$sql.= " SET fk_etatequipement= ".($fk_etatequipement!=-1? $fk_etatequipement:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->fk_etatequipement = $fk_etatequipement;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_entrepot Erreur SQL");
				return -1;
			}
		}
	}
	
	function set_datee($user, $datee)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			
			$sql.= " SET datee = ".($datee?"'".$this->db->idate($datee)."'":"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->datee= $datee;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_datee Erreur SQL");
				return -1;
			}
		}
	}

	function set_unitweight($user, $unitweight)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET unitweight = ".price2num($unitweight);
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql))
			{
				$this->datee= $datee;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_unitweight Erreur SQL");
				return -1;
			}
		}
	}

	function set_dateo($user, $dateo)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET dateo = ".($dateo?"'".$this->db->idate($dateo)."'":"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->dateo = $dateo;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_dateo Erreur SQL");
				return -1;
			}
		}
	}
	
	function set_client($user, $fk_soc_client)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			// quand on change le client, on raz la facture du client aussi
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_soc_client = ".($fk_soc_client!=-1? $fk_soc_client:"null");
			$sql.= " , fk_facture=null";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->fk_soc_client = $fk_soc_client;
				// on g�re r�cursivement l'h�ritage des enfants
				$tblenfant=$this->get_Childs($fk_equipementparent);
				$i = 0;
				foreach($tblenfant as $key => $value)
				{
					$equipementChilds = new equipement($this->db);
					$equipementChilds->fetch($value);
					$equipementChilds->set_client($user, $fk_soc_client);
				}
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_client Erreur SQL");
				return -1;
			}
		}
	}
	
	function set_fact_client($user, $fk_fact_client)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_facture = ".($fk_fact_client!=-1 ? $fk_fact_client:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->fk_fact_cli = $fk_fact_client;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_fact_client Erreur SQL");
				return -1;
			}
		}
	}

	function set_fact_fourn($user, $fk_fact_fourn)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET fk_facture_fourn = ".($fk_fact_fourn!=-1? $fk_fact_fourn:"null");
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->fk_fact_fourn = $fk_fact_fourn;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_fact_client Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the label of the equipement
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_description($user, $description)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET description = '".$this->db->escape($description)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->description = $description;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_description Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numimmocompta of the intervention
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_numimmocompta($user, $numimmocompta)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET numimmocompta = '".$this->db->escape($numimmocompta)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->numimmocompta = $numimmocompta;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numimmocompta Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Change de reference of the equipement
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_numref($user, $numref)
	{
		global $conf;

		if ($user->rights->equipement->majserial)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET ref = '".$this->db->escape($numref)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql))
			{
				$this->ref = $numref;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numref Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numversion of the equipement
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_numversion($user, $numversion)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET numversion = '".$this->db->escape($numversion)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql))
			{
				$this->numversion = $numversion;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_numversion Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the numversion of the equipement
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_quantity($user, $quantity)
	{
		global $conf;

		if ($user->rights->equipement->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."equipement ";
			$sql.= " SET quantity = ".$quantity;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql))
			{
				$this->quantity = $quantity;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Equipement::set_quantity Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Adding a line of event into data base
	 *
	 *	@param    	int		$equipementid			Id of equipement
	 *	@param    	string	$desc					Line description
	 *	@param      date	$date_evenement			Intervention date
	 *	@param      int		$duration				Intervention duration
	 *	@param      int		$duration				Prix de l'�v�nement
	 *	@return    	int             				>0 if ok, <0 if ko
	 */
	function addline($equipementid, $fk_equipementevt_type, $desc, $dateo, $datee, $fulldayevent, $fk_contrat, $fk_fichinter, $fk_expedition, $fk_project, $fk_user_author, $total_ht=0, $array_option=0)
	{
		dol_syslog("Equipement::Addline $equipementid, $fk_equipementevt_type,$desc, $dateo, $datee, $fulldayEvent, $fk_contrat, $fk_fichinter, $fk_expedition, $fk_project, $fk_user_author, $total_ht ");
		$this->db->begin();

		// Insertion ligne
		$line=new Equipementevt($this->db);

		$line->fk_equipement			= $equipementid;
		$line->desc						= $desc;
		$line->dateo					= $dateo;
		$line->datee					= $datee;
		$line->fulldayevent				= $fulldayevent;
		$line->total_ht					= $total_ht;
		$line->fk_equipementevt_type	= $fk_equipementevt_type;
		$line->fk_fichinter				= $fk_fichinter;
		$line->fk_contrat				= $fk_contrat;
		$line->fk_project				= $fk_project;
		$line->fk_expedition			= $fk_expedition;
		$line->fk_user_author			= $fk_user_author;
		$line->datec					= $datec;

		if (is_array($array_option) && count($array_option)>0) 
		{
			$line->array_options=$array_option;
		}

		$result=$line->insert();
		if ($result > 0)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Error sql=$sql, error=".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
 		}
	}

	/**
	 *	Adding a line of event into data base
	 *
	 *	@param    	int		$equipementid			Id of equipement
	 *	@param    	string	$desc					Line description
	 *	@param      date	$date_evenement			Intervention date
	 *	@param      int		$duration				Intervention duration
	 *	@param      int		$duration				Prix de l'�v�nement
	 *	@return    	int             				>0 if ok, <0 if ko
	 */
	function addconsumption($equipementid, $fk_product, $desc, $datecons, $fk_entrepot, $fk_entrepotmove, $fk_user_author, $array_option=0)
	{
		dol_syslog("Equipement::addconsumption $equipementid, $fk_product,$desc, $dateo, $fk_entrepot, $fk_entrepotmove, $fk_user_author, $array_option ");

		// Insertion ligne
		$line=new Equipementconsumption($this->db);
		$product =new product($this->db);
		$product->fetch($fk_product);

		$line->fk_equipement			= $equipementid;
		$line->desc						= $desc;
		$line->datecons					= $datecons;
		$line->fk_product				= $fk_product;
		$line->price					= $product->subprice;
		$line->fk_entrepot				= $fk_entrepot;
		$line->fk_entrepotmove			= $fk_entrepotmove;
		$line->fk_entrepot_dest			= $this->fk_entrepot; // l'entrepot de l'�quipement devient l'entrepot du consomm�
		$line->fk_user_author			= $fk_user_author;


		if (is_array($array_option) && count($array_option)>0) 
		{
			$line->array_options=$array_option;
		}

		$result=$line->insert();
		if ($result > 0)
		{
			return $result;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Error sql=$sql, error=".$this->error, LOG_ERR);
			return -1;
 		}
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		$now=dol_now();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->socid = 1;
		$this->date = $now;
		$this->note_public='SPECIMEN';
		$this->duree = 0;
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$line=new Equipementevt($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->datei=($now-3600*(1+$xnbp));
			$line->duration=600;
			$line->fk_fichinter=0;
			$this->lines[$xnbp]=$line;
			$xnbp++;

			$this->duree+=$line->duration;
		}
	}

	/**
	 *	Load array of lines
	 *
	 *	@return		int		<0 if Ko,	>0 if OK
	 */
	function fetch_lines()
	{
		$sql = 'SELECT ee.rowid, ee.fk_equipement, ee.description, ee.datec, ee.fk_equipementevt_type,';
		$sql.= ' ee.datee, ee.dateo, ee.fulldayevent, ee.total_ht,';
		$sql.= ' ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition, fi.ref as reffichinter, co.ref as refcontrat, ex.ref as refexpedition ';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as ex on ee.fk_expedition = ex.rowid";
		$sql.= ' WHERE fk_equipement = '.$this->id;

		dol_syslog(get_class($this)."::fetch_lines sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);

				$line = new Equipementevt($this->db);
				$line->id 						= $objp->rowid;
				$line->fk_equipement 			= $objp->fk_equipement;
				$line->fk_equipementevt_type	= $objp->fk_equipementevt_type;
				$line->desc 					= $objp->description;
				$line->fk_fichinter				= $objp->fk_fichinter;
				$line->fk_contrat				= $objp->fk_contrat;
				$line->fk_expedition			= $objp->fk_expedition;
				$line->ref_fichinter			= $objp->reffichinter;
				$line->ref_contrat				= $objp->refcontrat;
				$line->ref_expedition			= $objp->refexpedition;
				$line->datec					= $this->db->jdate($objp->datec);
				$line->dateo					= $this->db->jdate($objp->dateo);
				$line->datee					= $this->db->jdate($objp->datee);
				$line->fulldayevent				= $objp->fulldayevent;
				$line->total_ht					= $objp->total_ht;
				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}
	
	/**
	 *	Get the id of the fisrt parent child
	 *	with recursive search
	 *	@param  	int		$fk_equipementcomponent	Id equipement component
	 *	@return 	int								id equipement main
	 */	
	function get_firstParent($fk_equipementcomponent)
	{
		$sql = "SELECT fk_equipement_pere FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_fils=".$fk_equipementcomponent;
		
		dol_syslog(get_class($this)."::get_firstParent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			if($objp->fk_equipement_pere)
				return($this->get_firstParent($objp->fk_equipement_pere));
			else
				return($fk_equipementcomponent);
		}
		else
			return($fk_equipementcomponent);
	}

	/**
	 *	Get the id of the fisrt parent child
	 *	with recursive search
	 *	@param  	int		$fk_equipementcomponent	Id equipement component
	 *	@return 	int								id equipement main
	 */	
	function get_Parent($fk_equipementcomponent)
	{
		$sql = "SELECT fk_equipement_pere, fk_product FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_fils=".$fk_equipementcomponent;

		dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			if($objp->fk_equipement_pere)
			{
				$tblrep=array();
				$tblrep[0]=$objp->fk_equipement_pere;
				$tblrep[1]=$objp->fk_product;
			return $tblrep;
			}
		}
		return array();
	}

	
	/**
	 *	Get the id of the equipement child
	 *
	 *	@param  	int		$fk_parent			Id equipement parent
	 *	@param  	int		$fk_product			Id product of component
	 *	@param  	int		$position			position of the component in the parent
	 *	@return 	string						ref equipement child
	 */
	function get_component($fk_parent, $fk_product, $position)
	{
		$sql = "SELECT fk_equipement_fils FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_pere=".$fk_parent;
		$sql.= " and fk_product=".$fk_product;
		$sql.= " and position=".$position;

		dol_syslog(get_class($this)."::get_component sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			$this->fetch($objp->fk_equipement_fils);
			return($this->ref);
		}
	}

	/**
	 *	Get the id of the childs
	 *	@return 	array				id equipement childs
	 */	
	function get_Childs()
	{
		$tblrep=array();
		$sql = "SELECT fk_equipement_fils FROM ".MAIN_DB_PREFIX."equipementassociation ";
		$sql.= " WHERE fk_equipement_pere=".$thid-id;

		dol_syslog(get_class($this)."::get_Parent sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			$tblrep=array();
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				$tblrep[$i]=$objp->fk_equipement_fils;
				$i++;
			}
		}
		return $tblrep;
	}


	/**
	 *	Get the number of events of an Equipement
	 *	@return 	array				id equipement childs
	 */	
	function get_Events()
	{
		$nbevent=0;
		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."equipementevt";
		$sql.= " WHERE fk_equipement=".$this->id;

		dol_syslog(get_class($this)."::get_Events sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			$nbevent= $objp->nb;
		}
		return $nbevent;
	}

	/**
	 *	Get the number of events of an Equipement
	 *	@return 	array				id equipement childs
	 */	
	function get_Consumptions()
	{
		$nbconsumption=0;
		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."equipementconsumption";
		$sql.= " WHERE fk_equipement=".$this->id;

		dol_syslog(get_class($this)."::get_Consumptions sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			$nbconsumption= $objp->nb;
		}
		return $nbconsumption;
	}


	/**
	 *	set the id of the equipement child
	 *
	 *	@param  	int		$fk_parent			Id equipement parent
	 *	@param  	int		$fk_product			Id product of component
	 *	@param  	int		$position			position of the component in the parent
	 *	@param  	string	$ref_child			ref equipement child
	 *  @return 	void
	 */
	function set_component($fk_parent, $fk_product, $position, $ref_child)
	{
		// on r�cup�re l'id du composant � partir de sa ref
		$this->id='';
		$this->fetch('', $ref_child);

		if($this->fk_product==$fk_product)
		{
			// si on a trouv� le composant
			$fk_equipement_fils=$this->id;

			$sql = "delete FROM ".MAIN_DB_PREFIX."equipementassociation ";
			$sql.= " WHERE fk_equipement_pere=".$fk_parent;
			$sql.= " and fk_product=".$fk_product;
			$sql.= " and position=".$position;

			dol_syslog(get_class($this)."::set_component del sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);

			$sql = "insert into ".MAIN_DB_PREFIX."equipementassociation ";
			$sql.= " (fk_equipement_fils, fk_equipement_pere, fk_product, position)";
			$sql.= " values (".$fk_equipement_fils.", ".$fk_parent.", ".$fk_product.", ".$position.")";
			dol_syslog(get_class($this)."::set_component trt sql=".$sql, LOG_DEBUG);
			$this->db->query($sql);
		}
		else
		{
			$sql = "delete FROM ".MAIN_DB_PREFIX."equipementassociation ";
			$sql.= " WHERE fk_equipement_pere=".$fk_parent;
			$sql.= " and fk_product=".$fk_product;
			$sql.= " and position=".$position;
			$this->db->query($sql);
		}
	}
	
	/**
	 *	cut an �quipement
	 *
	 *	@param  	string	$ref_new			ref�rence du nouveau lot
	 *	@param  	int		$quantiynew			quantit� du nouveau lot
	 *	@param  	boolean	$cloneevent			top pour reprendre les �v�nements du lot ou pas
	 *  @return 	int		$fk_iddest			Id de l'�v�nement cr�e
	 */
	function cut_equipement($ref_new, $quantitynew, $cloneevent)
	{
		global $conf;
		global $soc;
		global $user;
		
		$cloned = new Equipement($this->db);

		$cloned->nbAddEquipement 	= 1;
		$cloned->SerialMethod 		= 3;
		//$cloned->id				= $this->id;
		$cloned->ref				= $ref_new;
		$cloned->description  		= $this->description;
		$cloned->socid				= $this->fk_soc;
		$cloned->statut				= $this->fk_statut;
		$cloned->numversion			= $this->numversion;
		$cloned->quantity			= $quantitynew;
		$cloned->author				= $user->id;
		$cloned->numimmocompta		= $this->numimmocompta;
		$cloned->dateo				= $this->db->jdate($this->dateo);
		$cloned->datee				= $this->db->jdate($this->datee);
		$cloned->datec				= $this->db->jdate($this->datec);
		$cloned->datev				= $this->db->jdate($this->datev);
		$cloned->datem				= $this->db->jdate($this->datem);
		$cloned->fk_product			= $this->fk_product;
		$cloned->fk_soc_fourn		= $this->fk_soc_fourn;
		$cloned->fk_fact_fourn		= $this->fk_facture_fourn;
		$cloned->fk_soc_client		= $this->fk_soc_client;
		$cloned->fk_fact_client		= $this->fk_facture;
		$cloned->fk_entrepot		= $this->fk_entrepot;
		$cloned->fk_etatequipement	= $this->fk_etatequipement;
		$cloned->etatequiplibelle	= $this->etatequiplibelle;
		$cloned->note_public		= $this->note_public;
		$cloned->note_private		= $this->note_private;
		$cloned->model_pdf			= $this->model_pdf;
		$cloned->fulldayevent 		= $this->fulldayevent;

		// pas de mouvement de stock sur le d�coupage (les quantit� restent les m�mes)
		$cloned->isentrepotmove=0;

		$fk_iddest=$cloned->create();

		// si la cr�ation c'est bien pass� on met � jour la quantit� d'origine
		if ($fk_iddest > 0 ) 
		{
			//print "cloned";
			$this->set_quantity($user, $this->quantity - $quantitynew);

			// TODO clone des extrafields

			// pas de clonage des compositions, aucune utilit� sur un lot


			// ensuite on clone les �v�nements de l'�quipement
			if ($cloneevent)
			{
				$sql = 'SELECT ee.rowid, ee.description, ee.fk_equipement, ee.fk_equipementevt_type, ee.total_ht, ee.fulldayevent,';
				$sql.= ' ee.datec, ee.fk_user_author, ee.dateo, ee.datee, ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
				$sql.= ' WHERE ee.fk_equipement = '.$this->id;
				$result = $this->db->query($sql);
				if ($result)
				{
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num)
					{
						$objp = $this->db->fetch_object($resql);
						$line = new Equipementevt($this->db);

						$line->fk_equipement			= $fk_iddest;
						$line->desc						= $objp->desc;
						$line->dateo					= $objp->dateo;
						$line->datee					= $objp->datee;
						$line->fulldayevent				= $objp->fulldayevent;
						$line->total_ht					= $objp->total_ht;
						$line->fk_equipementevt_type	= $objp->fk_equipementevt_type;
						$line->fk_fichinter				= $objp->fk_fichinter;
						$line->fk_contrat				= $objp->fk_contrat;
						$line->fk_expedition			= $objp->fk_expedition;
						$line->datec					= $objp->datec;

						$result=$line->insert();
						
						$i++;
					}
					
				// on ajoute un �v�nement de clonage???
					
				}
			}
		}
		
		return $fk_iddest;
	}


	function fillinvoice($numfacture)
	{
		global $langs;
		// on r�cup�re les num�ro �quipements associ� � la facture, pour les afficher dans le d�tails de la facture
		$sql = "SELECT e.rowid, e.ref, e.description, e.fk_soc_fourn, e.fk_facture_fourn, e.fk_statut, e.fk_entrepot,";
		$sql.= " e.numversion, e.numimmocompta, e.fk_etatequipement, e.quantity,";
		$sql.= " e.datec, e.datev, e.datee, e.dateo, e.tms as datem,";
		$sql.= " e.fk_product, e.fk_soc_client, e.fk_facture,";
		$sql.= " e.note_public, e.note_private ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " WHERE e.fk_facture=".$numfacture;
		$sql.= " ORDER BY fk_product";
		dol_syslog(get_class($this)."::fillinvoice sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{	// on boucle sur les �quipements
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$SerialLineInInvoice=$langs->trans("SerialLineInInvoice",$obj->ref);

				// on ajoute les num�ros d'�quipements � la suite
				$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet";
				$sql.= " SET description =concat(description,'".$SerialLineInInvoice."')";
				$sql.= " WHERE fk_facture=".$numfacture;
				$sql.= " AND fk_product=".$obj->fk_product;
				$res=$this->db->query($sql);
				$i++;
			}
		}
	}

	function fillintervention($numintervention)
	{
		global $langs;
		// on r�cup�re les num�ro �quipements associ� � la facture, pour les afficher dans le d�tails de la facture
		$sql = "SELECT e.ref as refequipement, p.ref as refproduct";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e, ".MAIN_DB_PREFIX."equipementevt as ee, ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE  e.rowid = ee.fk_equipement ";
		$sql.= " AND p.rowid = e.fk_product";
		$sql.= " AND ee.fk_fichinter=".$numintervention;
		$sql.= " ORDER BY e.fk_product";
		dol_syslog(get_class($this)."::fillinvoice sql=".$sql, LOG_DEBUG);


		$resql=$this->db->query($sql);
		$SerialLineInIntervention="";
		if ($resql)
		{	// on boucle sur les �quipements
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$SerialLineInIntervention=$langs->trans("SerialLineInIntervention",$obj->refproduct, $obj->refequipement);

				// on ajoute les num�ros d'�quipements � la suite
				$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
				$sql.= " SET note_public = concat_ws('<br>',note_public,'".$SerialLineInIntervention."')";
				$sql.= " WHERE rowid=".$numintervention;

				$res=$this->db->query($sql);
				$i++;
			}
		}
	}


	// d�termination du nombre d'�quipement d�j� associ� � l'exp�dition
	function get_nbEquipementProductExpedition($fk_product, $fk_expedition)
	{
		$sql = "SELECT sum(e.quantity) as nb ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee on e.rowid = ee.fk_equipement ";
		$sql.= " WHERE e.fk_product=".$fk_product;
		$sql.= " AND ee.fk_expedition=".$fk_expedition;

		dol_syslog(get_class($this)."::get_nbEquipementProductExpedition sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				return $obj->nb;
			}
		}
		return 0;
	}
	
	function GetEquipementFromShipping($fk_facture, $fk_expedition)
	{
		global $langs;

		$sql = "SELECT e.rowid ";
		$sql.= " FROM ".MAIN_DB_PREFIX."equipement as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ee on e.rowid = ee.fk_equipement ";
		$sql.= " WHERE ee.fk_expedition=".$fk_expedition;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
		dol_syslog(get_class($this)."::GetEquipementFromShipping sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{	// on boucle sur les �quipements
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$SerialLineInInvoice=$langs->trans("SerialLineInInvoice",$obj->ref);
				$obj = $this->db->fetch_object($resql);
				// on ajoute les num�ros d'�quipements � la suite
				$sql = "UPDATE ".MAIN_DB_PREFIX."equipement";
				$sql.= " SET fk_facture =".$fk_facture;
				$sql.= " WHERE rowid=".$obj->rowid;

				$res=$this->db->query($sql);
				$i++;
			}
		}
		}
		return 0;
	}
}

/**
 *	\class      EquipementLigne
 *	\brief      Classe permettant la gestion des lignes d'�v�nement intervention
 */
class Equipementevt extends CommonObject
{
	var $db;
	var $error;

	public $element='equipementevt';
	public $table_element='equipementevt';

	// From llx_equipementevt
	var $rowid;
	var $fk_equipement;
	var $fk_equipementevt_type;
	var $equipeventlib;
	var $fk_fichinter;
	var $fk_contrat;
	var $fk_project;
	var $fk_expedition;
	var $fk_user_author;
	// pour �viter de se taper une recherche pour chaque ligne
	var $ref_fichinter;
	var $ref_contrat;
	var $ref_expedition;
	var $array_options;

	var $desc;					// Description de la ligne
	var $datec;					// Date creation l'evenement
	var $dateo;					// Date debut de l'evenement
	var $datee;					// Date fin de l'evenement
	var $fulldayevent; 			
	var $total_ht=0;			//montant total de l'�v�n�ment (pour information)
	
	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of equipement event
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ee.rowid, ee.fk_equipement, ee.description, ee.datec, ee.fk_equipementevt_type, eet.libelle as equipeventlib,';
		$sql.= ' ee.datee, ee.dateo, ee.fulldayevent, ee.total_ht, ee.fk_user_author,';
		$sql.= ' ee.fk_fichinter, ee.fk_contrat, ee.fk_expedition, ee.fk_project ';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementevt as ee';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
		$sql.= ' WHERE ee.rowid = '.$rowid;

		dol_syslog("EquipementEvt::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp 	= $this->db->fetch_object($result);

			$this->rowid					= $objp->rowid;
			$this->fk_equipement			= $objp->fk_equipement;
			$this->fk_equipementevt_type	= $objp->fk_equipementevt_type;
			$this->equipeventlib			= $objp->equipeventlib;
			$this->datec					= $this->db->jdate($objp->datec);
			$this->datee					= $this->db->jdate($objp->datee);
			$this->dateo					= $this->db->jdate($objp->dateo);
			$this->total_ht					= price2num($objp->total_ht);
			$this->fulldayevent				= $objp->fulldayevent;
			$this->desc						= $objp->description;
			$this->fk_fichinter				= $objp->fk_fichinter;
			$this->fk_contrat				= $objp->fk_contrat;
			$this->fk_expedition			= $objp->fk_expedition;
			$this->fk_project				= $objp->fk_project;
			$this->fk_user_author			= $objp->fk_user_author;

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_print_error($this->db,$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert()
	{
		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		$now=dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'equipementevt';
		$sql.= ' (fk_equipement, fk_equipementevt_type, description,';
		$sql.= ' fulldayevent, fk_fichinter, fk_contrat, fk_expedition, fk_project,';
		$sql.= ' datec, dateo, datee, total_ht, fk_user_author)';
		$sql.= " VALUES (".$this->fk_equipement.",";
		$sql.= " ".($this->fk_equipementevt_type?$this->fk_equipementevt_type:"null").",";
		$sql.= " '".($this->desc?$this->db->escape($this->desc):"non saisie")."',";
		$sql.= " ".($this->fulldayevent?1:"null").",";
		$sql.= " ".($this->fk_fichinter?$this->fk_fichinter:"null").",";
		$sql.= " ".($this->fk_contrat?$this->fk_contrat:"null").",";
		$sql.= " ".($this->fk_expedition?$this->fk_expedition:"null").",";
		$sql.= " ".($this->fk_project?$this->fk_project:"null").",";
		$sql.= " '".$this->db->idate($now)."',"; // date de cr�ation aliment� automatiquement
		$sql.= " '".$this->db->idate($this->dateo)."',";
		$sql.= " '".$this->db->idate($this->datee)."',";
		$sql.= ' '.($this->total_ht?price2num($this->total_ht):"null").",";
		$sql.= ' '.($this->fk_user_author?$this->fk_user_author:"null");
		$sql.= ')';
//print $sql.'<br>';
		dol_syslog(get_class($this)."::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// on g�re les extra fields
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'equipementevt');

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			return $resql;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Delete a intervention line
	 *
	 *	@return     int		>0 if ok, <0 if ko
	 */
	function deleteline()
	{
		if ($this->statut == 0)
		{
			dol_syslog(get_class($this)."::deleteline lineid=".$this->rowid);
			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementevt WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);
			dol_syslog(get_class($this)."::deleteline sql=".$sql);

			if ($resql)
			{
				// Remove extrafields
				if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
				{
					$this->id=$this->rowid;
					$result=$this->deleteExtraFields();
					if ($result < 0)
					{
						$error++;
						dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
					}
				}

				$this->db->commit();
				return $resql;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog(get_class($this)."::deleteline Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipementevt ";
		$sql.= "SET description='".$this->db->escape($this->desc)."'";
		$sql.= ",	fk_equipementevt_type=".($this->fk_equipementevt_type?$this->fk_equipementevt_type:"null");
		$sql.= ",	datee=".($this->datee?"'".$this->db->idate($this->datee)."'":"null");
		$sql.= ",	dateo=".($this->dateo?"'".$this->db->idate($this->dateo)."'":"null");
		$sql.= ",	fulldayevent=".($this->fulldayevent?1:"null");
		$sql.= ",	total_ht=".($this->total_ht?price2num($this->total_ht):"null");
		$sql.= ", 	fk_fichinter=".($this->fk_fichinter?$this->fk_fichinter:"null");
		$sql.= ", 	fk_contrat=".($this->fk_contrat?$this->fk_contrat:"null");
		$sql.= ", 	fk_expedition=".($this->fk_expedition?$this->fk_expedition:"null");
		$sql.= ", 	fk_project=".($this->fk_project?$this->fk_project:"null");
		$sql.= ", 	fk_user_author=".($this->fk_user_author?$this->fk_user_author:"null");

		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}
			$this->db->commit();
			return $resql;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}
}

/**
 *	\class      EquipementLigne
 *	\brief      Classe permettant la gestion des lignes d'�v�nement intervention
 */
class Equipementconsumption extends CommonObject
{
	var $db;
	var $error;

	public $element='equipementconsumption';
	public $table_element='equipementconsumption';

	// From llx_equipementevt
	var $rowid;
	var $fk_equipement;
	var $fk_product;
	var $fk_equipementcons;
	var $fk_equipementevt;
	var $fk_entrepot;
	var $fk_entrepot_dest;
	var $price;
	var $fk_user_author;
	var $array_options;

	var $desc;					// Description de la ligne
	var $datecons;					// Date de consommation
	
	
	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of equipement consumption
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ec.rowid, ec.fk_equipement, ec.fk_equipementcons, fk_equipementevt, price';
		$sql.= ' , ec.datecons, ec.description, ec.fk_product, ec.fk_entrepot, ec.fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'equipementconsumption as ec';
		$sql.= ' WHERE ec.rowid = '.$rowid;

		dol_syslog("EquipementEvt::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp 	= $this->db->fetch_object($result);

			$this->rowid				= $objp->rowid;
			$this->fk_equipement		= $objp->fk_equipement;
			$this->fk_equipementcons	= $objp->fk_equipementcons;
			$this->fk_equipementevt		= $objp->fk_equipementevt;
			$this->datecons				= $this->db->jdate($objp->datecons);
			$this->desc					= $objp->description;
			$this->fk_product			= $objp->fk_product;
			$this->price				= $objp->price;
			$this->fk_entrepot			= $objp->fk_entrepot;
			$this->fk_user_author		= $objp->fk_user_author;

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_print_error($this->db,$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert()
	{
		global $user, $langs;
		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		$now=dol_now();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'equipementconsumption';
		$sql.= ' (fk_equipement, fk_equipementcons, description,';
		$sql.= ' datecons, fk_product, price, fk_entrepot, fk_user_author)';
		$sql.= " VALUES (".$this->fk_equipement.",";
		$sql.= " ".($this->fk_equipementcons?$this->fk_equipementcons:"null").",";
		$sql.= " '".($this->desc?$this->db->escape($this->desc):"")."',";
		$sql.= " '".$this->db->idate($this->datecons)."',";
		$sql.= ' '.($this->fk_product?$this->fk_product:"null").",";
		$sql.= ' '.($this->price?$this->price:0).",";
		$sql.= ' '.($this->fk_entrepot?$this->fk_entrepot:"null").",";
		$sql.= ' '.($this->fk_user_author?$this->fk_user_author:"null");
		$sql.= ')';
//print $sql.'<br>';
		dol_syslog(get_class($this)."::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$newconso = $this->db->last_insert_id(MAIN_DB_PREFIX.'equipementconsumption');
			// si les entrepots sont diff�rent on cr�e un mouvement 
			if ($this->fk_entrepotmove==1 && $this->fk_entrepot_dest != $this->fk_entrepot)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$EquipementConsommant = new Equipement($this->db);
				$EquipementConsommant->fetch($this->fk_equipementcons);
				$mouvP = new MouvementStock($this->db);
				$mouvP->origin = $EquipementConsommant;
				$mouvP->origin->id = $this->fk_equipementcons;

				if ( $this->fk_entrepot > 0 ) // si il y avait un ancien entrepot
					$idmv=$mouvP->livraison($user, $this->fk_product, $this->fk_entrepot, 1, 0, // le prix est � 0 pour ne pas impacter le pmp
						$langs->trans("EquipementConsumptionOut"));

				if ($this->fk_entrepot_dest > 0 )
					$idmv=$mouvP->reception($user, $this->fk_product, $this->fk_entrepot_dest, 1, 0, 
							$langs->trans("EquipementConsumptionIn"));
			}

			return $newconso;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog(get_class($this)."::insert Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Delete a intervention line
	 *
	 *	@return     int		>0 if ok, <0 if ko
	 */
	function deleteline()
	{
		if ($this->statut == 0)
		{
			dol_syslog(get_class($this)."::deleteline lineid=".$this->rowid);
			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."equipementconsumption WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);
			dol_syslog(get_class($this)."::deleteline sql=".$sql);

			if ($resql)
			{
				$this->db->commit();
				return $resql;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog(get_class($this)."::deleteline Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."equipementconsumption ";
		$sql.= "SET description='".$this->db->escape($this->desc)."'";
		$sql.= ",	datecons=".($this->datecons?"'".$this->db->idate($this->datecons)."'":"null");
		$sql.= ",	fk_equipementcons=".($this->fk_equipementcons?$this->fk_equipementcons:"null");
		$sql.= ", 	price=".($this->price?$this->price:"null");
		$sql.= ", 	fk_entrepot=".($this->fk_entrepot_dest?$this->fk_entrepot_dest:"null");
		$sql.= ", 	fk_user_author=".($this->fk_user_author?$this->fk_user_author:"null");

		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->fk_entrepotmove==1 && $this->fk_entrepot_dest != $this->fk_entrepot)
			{
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$EquipementConsommant = new Equipement($this->db);
				$EquipementConsommant->fetch($this->fk_equipementcons);
				$mouvP = new MouvementStock($this->db);
				$mouvP->origin = $EquipementConsommant;
				$mouvP->origin->id = $this->fk_equipementcons;

				if ( $this->fk_entrepot > 0 ) // si il y avait un ancien entrepot
					$idmv=$mouvP->livraison($user, $this->fk_product, $this->fk_entrepot, 1, 0, // le prix est � 0 pour ne pas impacter le pmp
						$langs->trans("EquipementConsumptionOut"));

				if ($this->fk_entrepot_dest > 0 )
					$idmv=$mouvP->reception($user, $this->fk_product, $this->fk_entrepot_dest, 1, 0, 
							$langs->trans("EquipementConsumptionIn"));
			}

			$this->db->commit();
			return $resql;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}
}
?>