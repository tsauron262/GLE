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
 *	\file       htdocs/core/modules/ndfp/modules_ndfp.php
 *	\ingroup    ndfp
 *	\brief      Fichier contenant la classe mere de generation des notes de frais en PDF
 */

require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *	\class      ModelePDFNdfp
 *	\brief      Classe mere des notes de frais
 */
class ModeleNdfp extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 * 	@param		$db		Database handler
	 */
	static function liste_modeles($db)
	{
		global $conf, $langs;

		$type = 'ndfp';

        $liste = array();
        $found = 0;
        $dirtoscan = '';
    
        $sql = "SELECT nom as id, nom as lib, libelle as label, description as description";
        $sql.= " FROM ".MAIN_DB_PREFIX."document_model";
        $sql.= " WHERE type = '".$type."'";
        $sql.= " AND entity = ".$conf->entity;
    
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $found=1;
    
                $obj = $db->fetch_object($resql);
    
                // If this generation module needs to scan a directory, then description field is filled
                // with the constant that contains list of directories to scan (COMPANY_ADDON_PDF_ODT_PATH, ...).
                if (! empty($obj->description))	// List of directories to scan is defined
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
    
                    $const=$obj->description;
                    $dirtoscan.=($dirtoscan?',':'').preg_replace('/[\r\n]+/',',',trim($conf->global->$const));
    
                    $listoffiles=array();
    
                    // Now we add models found in directories scanned
                    $listofdir=explode(',',$dirtoscan);
                    foreach($listofdir as $key=>$tmpdir)
                    {
                        $tmpdir=trim($tmpdir);
                        $tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
                        if (! $tmpdir) { unset($listofdir[$key]); continue; }
                        if (is_dir($tmpdir))
                        {
                            $tmpfiles=dol_dir_list($tmpdir,'files',0,'\.odt');
                            if (sizeof($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
                        }
                    }
    
                    if (sizeof($listoffiles))
                    {
                        foreach($listoffiles as $record)
                        {
                            $max=($maxfilenamelength ? $maxfilenamelength : 28);
                            $liste[$obj->id.':'.$record['fullname']]=dol_trunc($record['name'],$max,'middle');
                        }
                    }
                    else
                    {
                        $liste[0]=$obj->label.': '.$langs->trans("None");
                    }
                }
                else
                {
                    $liste[$obj->id]=$obj->label?$obj->label:$obj->lib;
                }
                $i++;
            }
        }
        else
        {
            dol_print_error($db);
            return -1;
        }
    
        if (!$found){
            return 0;
        }
    
  		return $liste;
   	}
}


/**
 *	\class      ModeleNumRefNdfp
 *	\brief      Classe mere des modeles de numerotation des notes de frais
 */
class ModeleNumRefNdfp
{
	var $error='';

	/**  Return if a module can be used or not
	 *   @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**	 Renvoi la description par defaut du modele de numerotation
	 *   @return    string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("ndfp");
		return $langs->trans("NoDescription");
	}

	/**  Renvoi un exemple de numerotation
	 *	 @return	string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("ndfp");
		return $langs->trans("NoExample");
	}

	/**  Test si les numeros deja en vigueur dans la base ne provoquent pas
	 *   de conflits qui empecheraient cette numerotation de fonctionner.
	 *   @return	boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**  Renvoi prochaine valeur attribuee
	 *   @param     objsoc		Objet societe
	 *   @param     facture		Objet facture
	 *   @return    string      Valeur
	 */
	function getNextValue($objsoc,$facture)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**  Renvoi version du modele de numerotation
	 *   @return    string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *	Cree une confirmation sur le disque en fonction du modele de NDFP_ADDON_PDF
 *	@param   	db  			objet base de donnees
 *	@param   	object			Object ndfp
 *	@param	    message			message
 *	@param	    modele			force le modele a utiliser ('' to not force)
 *	@param		outputlangs		objet lang a utiliser pour traduction
 *	@return  	int        		<0 if KO, >0 if OK
 */
function ndfp_pdf_create($db, $object, $message, $modele, $outputlangs)
{
	global $conf,$user,$langs;
	
	$langs->load("ndfp");

	// Increase limit for PDF build
    $err=error_reporting();
    error_reporting(0);
    @set_time_limit(120);
    error_reporting($err);

	$dir = "/core/modules/ndfp/";
    $srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->NDFP_ADDON_PDF))
		{
			$modele = $conf->global->NDFP_ADDON_PDF;
		}
		else
		{
			$modele = 'calamar';
		}
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template file
	$file=''; $classname=''; $filefound=0;
	foreach(array('doc','pdf') as $prefix)
	{
        $file = $prefix."_".$modele.".modules.php";
        
        // On verifie l'emplacement du modele
        $file = dol_buildpath($dir.'doc/'.$file);
	    
        if (file_exists($file))
	    {
	        $filefound=1;
	        $classname=$prefix.'_'.$modele;
	        break;
	    }
	}

	// Charge le modele
	if ($filefound)
	{
		require_once($file);

		$obj = new $classname($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
        //$outputlangs->charset_output = '';
        
		if ($obj->write_file($object, $outputlangs, $srctemplatepath) > 0)
		{
			// Success in building document. We build meta file.
			ndfp_meta_create($db, $object->id);
			// et on supprime l'image correspondant au preview
			ndfp_delete_preview($db, $object->id);

			$outputlangs->charset_output=$sav_charset_output;
			
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('NDFP_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
			
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"ndfp_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}
}


/**
 *	Create a meta file with document file into same directory.
 *  This should allow rgrep search.
 *	@param	    db  		Objet base de donnee
 *	@param	    facid		Id de la note de frais a creer
 *	@param      message     Message
 */
function ndfp_meta_create($db, $id, $message="")
{
	global $langs,$conf;

	$ndfp = new Ndfp($db);
	$ndfp->fetch($id);

    $meta = "";
    
	if ($conf->ndfp->dir_output)
	{
		$ndfpref = dol_sanitizeFileName($ndfp->ref);
		$dir = $conf->ndfp->dir_output . "/" . $ndfpref ;
		$file = $dir . "/" . $ndfpref . ".meta";

		if (! is_dir($dir))
		{
			create_exdir($dir);
		}

		if (is_dir($dir))
		{
            
			$meta = "REFERENCE=\"" . $ndfp->ref . "\"
			DATE=\"" . dol_print_date($ndfp->datec,'') . "\"\n";

		}
        
		$fp = fopen ($file,"w");
		fputs($fp,$meta);
		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
		@chmod($file, octdec($conf->global->MAIN_UMASK));
	}
}

/**
 *	Supprime l'image de previsualitation, pour le cas de regeneration de confirmation
 *	@param	   db  		objet base de donnee
 *	@param	   facid	id de la facture a creer
 */
function ndfp_delete_preview($db, $id)
{
	global $langs,$conf;
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	$ndfp = new Ndfp($db);
	$ndfp->fetch($id);

	if ($conf->ndfp->dir_output)
	{
		$ndfpref = dol_sanitizeFileName($ndfp->ref);
		$dir = $conf->ndfp->dir_output . "/" . $ndfpref ;
		$file = $dir . "/" . $ndfpref . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file,1) )
			{
				return 0;
			}
		}
	}

	return 1;
}

?>