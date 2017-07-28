<?php
/* CyberOffice
*  @author 		LVSinformatique <contact@lvsinformatique.com>
*  @copyright  	2014 LVSInformatique
*  @version   	1.3.2
*/

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/nusoap/lib/nusoap.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/cyberoffice/class/cyberoffice.class.php';
dol_syslog("Call Dolibarr webservices interfaces");
set_time_limit(0);
$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.lvsinformatique.com/ns/';
$server->configureWSDL('WebServicesDolibarrCategorie',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
        'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
        'myurl' => array('name'=>'myurl','type'=>'xsd:string')
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define specific object
$server->wsdl->addComplexType(
    'params',
    'complexType',
    'struct',
    'all',
    '',
	array(
	    'id' => array('name'=>'id','type'=>'xsd:string'),
	    'id_mere' => array('name'=>'id_mere','type'=>'xsd:string'),
	    'label' => array('name'=>'label','type'=>'xsd:string'),
	    'description' => array('name'=>'description','type'=>'xsd:string'),
	    'image' => array('name'=>'image','type'=>'xsd:string'),
	    'action' => array('name'=>'action','type'=>'xsd:string'))
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped

// Register WSDL
$server->register(
    'Create',
    // Entry values
    array('authentication'=>'tns:authentication','params'=>'tns:params'),
    // Exit values
    array('result'=>'tns:result','description'=>'xsd:string'),
    $ns,
    $ns.'#Create',
    $styledoc,
    $styleuse,
    'WS to Create Category'
);

function Create($authentication,$params)
{
    global $db,$conf,$langs;
//echo "<pre>".print_r($params)."</pre>";die();
    $now=dol_now();

    dol_syslog("Function: create login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $list_ok='';
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
	$error=0;
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
        $result0=$newobject->create($user);
        $newobject->label = $cyber->myurl;
		$newobject->description = $cyber->myurl;
		$newobject->fk_parent = 0;
        $newobject->type=2;// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member, 4=Contact
        $result2=$newobject->create($user);
        $newobject->label = $cyber->myurl;
		$newobject->description = $cyber->myurl;
		$newobject->fk_parent = 0;
        $newobject->type=1;// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member, 4=Contact
        $result1=$newobject->create($user);

        $catparent0 = array();
        if (DOL_VERSION < '3.8.0')
			$catparent0 = $newobject->rechercher(null,$cyber->myurl,0);
		else
			$catparent0 = $newobject->rechercher(null,$cyber->myurl,'product');
        foreach ($catparent0 as $cat_parent0)
		{
			$idparent0 = $cat_parent0->id;
		}
        $db->begin();
        foreach ($params as $category)
		{
			//recherche de la correspondance
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE import_key = 'P".$indice.'-'.$category['id']."'";
			dol_syslog("CyberOffice_server_categorie::fetch :".$category['action']." sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$res_rowid=$res['rowid'];
					if ($category['action'] == 'delete')
					{
						$newobject->fetch($res_rowid);
						$newobject->delete($user);
						continue;
					}
				} else $res_rowid=0;
			} else $res_rowid=0;
			
			$sql = "SELECT rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
			$sql.= " WHERE import_key = 'P".$indice.'-'.$category['id_mere']."'";
			dol_syslog("CyberOffice_server_categorie::fetch sql=".$sql);
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$res = $db->fetch_array($resql);
					$res_rowid_p=$res['rowid'];
				} else $res_rowid_p=0;
			} else $res_rowid_p=0;
		if ($res_rowid_p==0) $res_rowid_p=$idparent0;
		if ($res_rowid==0) {
			//creation de la categorie			
			$newobject->fk_parent 	= 	$res_rowid_p;
			$newobject->label 		= 	$category['label'];		
			$newobject->description	= 	$category['description'];
			$newobject->import_key	=	'P'.$indice.'-'.$category['id'];
			$newobject->type		=	0;
			$newobject->visible		=	0;
			$result = $newobject->create($user);
			if ($result> 0) $list_ok.="<br/>Create Category : ".$category['id'].'->'.$result . ' : ' .$category['label'];
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
				if( !empty($category['image']) ) {
						//$newobject->id = $result;
					    $name = explode("/",$category['image']);
						$name = $name[sizeof($name)-1];
						$ext=preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$category['image'],$reg);
						$imgfonction='';
					    if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
					    if (strtolower($reg[1]) == '.png')  $ext= 'png';
					    if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
					    if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
					    if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';

						$file = array("tmp_name"=>"images_temp/temp.$ext","name"=>$name);
						
						//$img = @call_user_func_array("imagecreatefrom".$ext,array($category['image']));
						switch ($ext) { 
							case 'gif' : 
						    	$img = imagecreatefromgif($category['image']); 
						    	break; 
						    case 'png' : 
						        $img = imagecreatefrompng($category['image']); 
						        break; 
						    case 'jpeg' : 
						        if ( false !== (@$fd = fopen($category['image'], 'rb' )) )
						        {
							    	if ( fread($fd,2) == chr(255).chr(216) )
							        	$img = imagecreatefromjpeg($category['image']);
							        else
							        	$img = imagecreatefrompng($category['image']);
							    }
							    else
							        $img = imagecreatefromjpeg($category['image']);
						        break;
							case 'wbmp' : 
						        $img = imagecreatefromwbmp($category['image']); 
						        break; 
						}
						
						$upload_dir = $conf->categorie->multidir_output[$conf->entity];
						
						$sdir = $conf->categorie->multidir_output[$conf->entity];
						if (DOL_VERSION < '3.8.0')
							$dir = $sdir .'/'. get_exdir($result,2) . $result ."/";
						else $dir = $sdir .'/'. get_exdir($result,2,0,0,$newobject,'category') . $result ."/";
						$dir .= "photos/";
				
						if (! file_exists($dir)) dol_mkdir($dir);
				
						@call_user_func_array("image$ext",array($img,$dir.$file['name']));
						@imagedestroy($img);
			    }
		
			if ($result <= 0) {
				$error++;
				$list_id.= "<br/>ERROR ".$category['id']." :: ".$result." ".$db->lasterror();
				$list_ref.= ' '.$newobject->label ;
			}
		}    

        if ($error==0)
        {
            $db->commit();
            $objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>$indice.' : '.$cyber->myurl),'description'=>$list_ok);
        }
        else
        {
            $db->commit();//$db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
	
    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),'description'=>$list_id.$list_ok);
    }

    return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));