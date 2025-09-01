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

// This is to make Dolibarr working with Plesk
define('NOCSRFCHECK', 1);

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
dol_syslog("cyberoffice::Call Dolibarr webservices interfaces::ServerProduct_ws3");
//sleep(15);
//set_time_limit(3600);
@ini_set('default_socket_timeout', 160);
$langs->load("main");
global $db,$conf,$langs;
$authentication=array();
$params=array();
$authentication=$_POST['authentication'];
$params= (isset($_POST['params'])?$_POST['params']:array());
$now=dol_now();
dol_syslog("cyberoffice::Function: server_product_ws login=".$authentication['login']);

include("server_product.inc.php");
