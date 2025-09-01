<?php
/**
 *  CyberOffice
 *
*  @author 		LVSinformatique <contact@lvsinformatique.com>
*  @copyright  	2014 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
*/

define('NOCSRFCHECK', 1);

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/cyberoffice8/class/cyberoffice.class.php';
dol_syslog('Call Dolibarr webservices interfaces::category', LOG_WARNING);
set_time_limit(0);

class DataServer
{
	function create($authentication,$params)
	{
		global $db,$conf,$langs;
		$now=dol_now();

		dol_syslog("Call Dolibarr webservices interfaces::ServerCategorie",6,0, '_cyber');
		$langs->load("main");
		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled",6,0, '_cyber');
			print $langs->trans("KeyForWebServicesAccess2");
			exit;
		}
		dol_syslog("Function: create login=".$authentication['login'],6,0, '_cyber');
		if ($authentication['entity']) $conf->entity=$authentication['entity'];
        if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
            dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
            $mc = new ActionsMulticompany($db);
            $returnmc = $mc->switchEntity($authentication['entity']);
            $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
        }
		// Init and check authentication
		$objectresp=array();
		$errorcode='';$errorlabel='';
		$error=0;
		$list_ok='';
		$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
		if ($error)
		{
			$objectresp = array('result'=>array('result_code' => 'ko', 'result_label' => 'ko'),'webservice'=>'login');
			$error++;
			return $objectresp;
		}

		$error=0;
		//$this->myLog("CyberOffice_server_categorie::line=".__LINE__);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
		// raz de la base
		//$sql = "DELETE FROM llx_categorie WHERE import_key IS NOT NULL";
		//$resql = $db->query($sql);

		$user = new User($db);
		$user->fetch('', $authentication['login'],'',0);
		$user->getrights();

		$cyber = new Cyberoffice;
		$cyber->entity = 0;
		$cyber->myurl = $authentication['myurl'];
		$indice = $cyber->numShop();

		$newobject=new Categorie($db);
		$newobject->label = $cyber->myurl;
		$newobject->description = $cyber->myurl;
		$newobject->fk_parent = 0;
		$newobject->type=0;// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member, 4=Contact
		$cat0 = array();
		$cat0 = $newobject->rechercher(null,$cyber->myurl,'product');
        $idparent0 = 0;
		if (is_array($cat0) && count($cat0)>0) {
			//ras
		} else {
			$result0=$newobject->create($user);
		}
		$newobject->label = $cyber->myurl;
		$newobject->description = $cyber->myurl;
		$newobject->fk_parent = 0;
		$newobject->type=2;// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member, 4=Contact
		$cat2 = array();
		$cat2 = $newobject->rechercher(null,$cyber->myurl,'customer');
		if (is_array($cat2) && count($cat2)>0) {
			//ras
		} else {
			$result2=$newobject->create($user);
		}
		$newobject->label = $cyber->myurl;
		$newobject->description = $cyber->myurl;
		$newobject->fk_parent = 0;
		$newobject->type=1;// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member, 4=Contact
		$cat1 = array();
		$cat1 = $newobject->rechercher(null,$cyber->myurl,'supplier');
		if (is_array($cat1) && count($cat1)>0) {
			//ras
		} else {
			$result1=$newobject->create($user);
		}
		
		$catparent0 = array();
		$catparent0 = $newobject->rechercher(null, $cyber->myurl, 'product');
		foreach ($catparent0 as $cat_parent0)
		{
			$idparent0 = $cat_parent0->id;
		}
		//$this->myLog("CyberOffice_server_categorie::line=".print_r($params, true));
		$db->begin();
		foreach ($params as $category)
		{
			//recherche de la correspondance
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE import_key = 'P".$indice.'-'.$category['id']."'";
			dol_syslog("CyberOffice_server_categorie::fetch :".$category['action'],6,0, '_cyber');
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$res_rowid=$res['rowid'];
					if ($category['action'] == 'delete')
					{
						$newobject->fetch($res_rowid);
						$result = $newobject->delete($user);
						continue;
					}
				} else $res_rowid=0;
			} else $res_rowid=0;

			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE import_key = 'P".$indice.'-'.(isset($category['id_mere'])?$category['id_mere']:0)."'";
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$res_rowid_p=$res['rowid'];
				} else $res_rowid_p=0;
			} else {
                $res_rowid_p=0;
            }

			if ($res_rowid_p == 0) {
                $res_rowid_p = $idparent0;
            }
			if ($res_rowid==0) {
				//creation de la categorie			
				$newobject->fk_parent 	= 	$res_rowid_p;
				$newobject->label 		= 	(isset($category['label'])?$category['label']:'');
				$newobject->description	= 	(isset($category['description'])?$category['description']:'');
				$newobject->import_key	=	'P'.$indice.'-'.$category['id'];
				$newobject->type		=	0;
				$newobject->visible		=	0;
                $newobject->color = '';
                $newobject->ref_ext = '';
				$result = $newobject->create($user);
                //error_log('cyberoffice::creation category '.$result);
                $res_rowid = $result;
				if ($result> 0) $list_ok.="<br/>Create Category : ".$category['id'].'->'.$result . ' : ' .(isset($category['label'])?$category['label']:'');
					else $list_ok.="<br/>ERROR n° ".$result."-".$db->lasterror()." :: Create Category : ".$category['id'].'->'.$result . ' : ' .$category['label'];
			} else {
				$newobject->fetch($res_rowid);
				$newobject->fk_parent 	= 	$res_rowid_p;
				$newobject->label 		= 	$category['label'];		
				$newobject->description	= 	$category['description'];
				$newobject->import_key	=	'P'.$indice.'-'.$category['id'];
				$newobject->type		=	0;
				$newobject->visible		=	0;
				$newobject->id = $res_rowid;
				$result=$res_rowid;
				$resultU = $newobject->update($user);
				if ($resultU > 0) $list_ok.="<br/>Update Category : ".$category['id'].'->'.$res_rowid. ' : ' .$category['label'];
					else $list_ok.="<br/>ERROR n° ".$resultU ."-".$db->lasterror()." :: Update Category : ".$category['id'].'->'.$result . ' : ' .$category['label'];
			}
			//photo
            //error_log('cyberoffice::'.__LINE__);
			if( !empty($category['image']) ) {
                //error_log('cyberoffice::photo '.$res_rowid);
				$newobject->fetch($res_rowid);
				$name = explode("/",$category['image']);
				$name = $name[sizeof($name)-1];
                //error_log('cyberoffice::photo '.$name);
				$ext=preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$category['image'],$reg);
				$imgfonction='';
				if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
				if (strtolower($reg[1]) == '.png')  $ext= 'png';
				if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
				if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
				if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';

				$file = array("tmp_name"=>"images_temp/temp.$ext","name"=>$name);
                //error_log('photo '.__LINE__.'::'.$name);
				switch ($ext) { 
					case 'gif' : 
						$img = @imagecreatefromgif($category['image']);
						break; 
					case 'png' : 
						$img = @imagecreatefrompng($category['image']);
						break; 
					case 'jpeg' : 
						if ( false !== (@$fd = fopen($category['image'], 'rb' )) ) {
							if ( fread($fd,2) == chr(255).chr(216) )
								$img = @imagecreatefromjpeg($category['image']);
							else
								$img = @imagecreatefrompng($category['image']);
						} else {
							$img = @imagecreatefromjpeg($category['image']);
						}
						break;
					case 'wbmp' : 
						$img = imagecreatefromwbmp($category['image']);
						break; 
				}
				if ($img) {
					$upload_dir = $conf->categorie->multidir_output[$conf->entity];

					$sdir = $conf->categorie->multidir_output[$conf->entity];
						$dir = $sdir .'/'. get_exdir($result,2,0,0,$newobject,'category') . $result ."/";
					$dir .= "photos/";
                    //error_log('photo '.__LINE__.'::'.$dir);
					if (! file_exists($dir)) 
						dol_mkdir($dir);
					if ($img) {
						@call_user_func_array("image$ext",array($img,$dir.$file['name']));
						@imagedestroy($img);
					}
				}
			}

			if ($result <= 0) {
				$error++;
				$list_id.= "<br/>ERROR ".$category['id']." :: ".$result." ".$db->lasterror();
				$list_ref.= ' '.$newobject->label ;
			}
		}    

		if ($error == 0) {
            //$this->myLog("CyberOffice_server_categorie::line=".__LINE__);
			$db->commit();
			$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>$indice.' : '.$cyber->myurl),'description'=>json_encode($list_ok));
		} else {
            //$this->myLog("CyberOffice_server_categorie::line=".__LINE__);
			$db->commit();//$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}

		if ($error)
		{
			$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>$list_id.$list_ok);
		}
		$db->close();
		return $objectresp;
	}
    
}
$options = array('uri' => $_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();