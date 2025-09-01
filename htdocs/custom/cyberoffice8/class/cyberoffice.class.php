<?php
/**
 *  CyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2014 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
class Cyberoffice
{
	var $db;
	var $global;
	var $myurl;
	var $entity;
	
	function __construct()
	{
	}

function nbShop()
	{
		global $conf,$db;
		
		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE note = '".$this->myurl."'";
		//dol_syslog("CyberOffice_class::nbshop ".__LINE__." : ".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			$indice=$db->num_rows($resql);
		} else $indice = 0;
		
		if ($indice == 0) $indice=$this->setShop();
		
		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name LIKE 'CYBEROFFICE_SHOP%'";
		//dol_syslog("CyberOffice_class::nbshop ".__LINE__." : ".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			$indice=$db->num_rows($resql);
		} else $indice = 0;
		return $indice;
	}

function setShop()
	{
		global $conf,$db;
		$sql = "SELECT MAX(name) as maxname";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name LIKE 'CYBEROFFICE_SHOP%'";
		//dol_syslog("CyberOffice_class:: setshop ".__LINE__." : ".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$res = $db->fetch_array($resql);
				$max=$res['maxname'];
				$indice=intval(substr($max,-1 * (strlen($max)-16)));
			} else $indice=0;
		} else $indice=0;

		$indice = str_pad($indice+1,2,'0',STR_PAD_LEFT);

		//if (!$conf->global->CYBEROFFICE_SHIPPING)
		dolibarr_set_const($db, 'CYBEROFFICE_SHOP'.$indice, $indice, 'chaine', 0, $this->myurl, 0);
		return $indice;
	}
function numShop($name=0)//0=value, 1=name
	{
	global $conf,$db;
		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE note = '".$this->myurl."'";
		//dol_syslog("CyberOffice_class::".__LINE__." : ".$sql);
		$resql = $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$res = $db->fetch_array($resql);
                                if ($name==0)
                                    $num=$res['value'];
                                else
                                    $num=substr($res['name'],-2);
			}
		}
		return $num;
	}

function delShop()
	{
	global $conf,$db;
	//dol_syslog("CyberOffice_class::".__LINE__." : ".$sql);
	//dolibarr_del_const($db, $const["rowid"], -1);
	}
}

