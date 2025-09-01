<?php
/**
 *  MyCyberOffice
 *
 *  @author    LVSinformatique <contact@lvsinformatique.com>
 *  @copyright 2016 LVSInformatique
 *  @license   NoLicence
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
class ActionsMycyberoffice8
{
    public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;
        $langs->load("mycyberoffice@mycyberoffice8");
        $error = 0; // Error counter
        $formconfirm = null;
        if (in_array('expeditioncard', explode(':', $parameters['context'])) && $parameters['currentcontext'] == 'expeditioncard') {
            if ($action == 'UpdatePrestaShop') {
				require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
				$form = new Form($db);
				$ConfirmUpdatePrestaShop=$langs->trans('ConfirmUpdatePrestaShop');
                $values=array();
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."const WHERE name like '%CYBEROFFICE_SHOP%' ORDER BY note";
				$resql = $db->query($sql);
                if ($resql) {
                    $num = $db->num_rows($resql);
                    $i = 0;
                    while ($i < $num)
                    {
                        $res = $db->fetch_object($resql);
                        if ($res->note) $values[substr($res->name, -2)] = $res->note;
                        $value0 = $res->value;
                        $i++;
                    }
                }
                $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ConfirmUpdatePrestaShop'), 'ConfirmUpdatePrestaShop?', 'ConfirmUpdatePrestaShop',array(array('label'=> $langs->trans('SelectShop'), 'name'=>'ShopChoice', 'values'=>$values, 'type'=> 'select', 'default'=>$value0)),'',1);
            }
		}

		if (! $error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = $formconfirm ;
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
    }
	function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs, $db;
        if (in_array('expeditioncard', explode(':', $parameters['context'])) && $parameters['currentcontext'] == 'expeditioncard') {
			if ($action == 'ConfirmUpdatePrestaShop') {
				$langs->load("mycyberoffice@mycyberoffice8");
				//setEventMessages($langs->trans("UpdatePrestaShop").' '.GETPOST('ShopChoice'), $hookmanager->errors, 'mesgs');
				$result=$object->call_trigger('ADD_ORDER', $user);
				setEventMessages($langs->trans("UpdatePrestaShop").$result, $hookmanager->errors, ($result!=-1?'mesgs':'warnings'));
			}
		}
		return 0;
    }
	
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
            global $langs, $conf, $user, $db;
            
            if (!empty($conf->global->MYCYBEROFFICE_ADDORDER) && $conf->global->MYCYBEROFFICE_ADDORDER == 1 && $object->statut > 0 && in_array('expeditioncard', explode(':', $parameters['context'])) && $parameters['currentcontext'] == 'expeditioncard')
	    {
                $origin_id = ($object->origin=='commande'?$object->origin_id:0);
                //$sql = "SELECT import_key FROM ".MAIN_DB_PREFIX."expedition WHERE rowid = ".$object->id;
                $sql1 = "SELECT e.import_key FROM ".MAIN_DB_PREFIX."expedition e";
                $sql1.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element ee ON (e.rowid = ee.fk_target AND ee.targettype = 'shipping' AND ee.sourcetype = 'commande')";
		$sql1.= " WHERE ee.fk_source=".$origin_id;
                $result = $db->query($sql1);
		if ($result)
		{
                    $num = $db->num_rows($result);
                    $i = 0;
                    $test = 0;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $test=(substr($objp->import_key, 0, 1)=='P'?1:0);
                        $i++;
                    }
                }
                /*
                print 'test='.$test;
                $resql = $db->query($sql);
                if ($resql) {
                    if ($db->num_rows($resql) > 0) {
                        $res = $db->fetch_array($resql);
                        $import_key=$res['import_key'];
                    } else $import_key=0;
                } else $import_key=0;
                if (substr($import_key, 0, 1)=='P' && $user->id != 9) {
                */

                if ($test==1) {
                    $color = 'green';
                    $class = "butActionRefused";
                    $href = '#';
                } else {
                    $color = 'red';
                    $class="butAction";
                    $href = $_SERVER["PHP_SELF"].'?action=UpdatePrestaShop&amp;id='.$object->id;
                }
                print '<div class="inline-block divButAction" style="float:left;"><a class="'.$class.'" href="'.$href.'"><span class="fas fa-sync-alt" title="'.$langs->trans("UpdatePrestaShop").'" style="color:'.$color.';font-size:large"></span></a></div>';
                //print '<br>';
//print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("UpdatePrestaShop").'</a></div>';

            }
	    return 0;
	}
	
	/*function insertExtraFields($parameters, &$object, &$action, $hookmanager)
	{
	    if (in_array('productdao', explode(':', $parameters['context'])) && $parameters['currentcontext'] == productcard  && $action == 'update')
	    {
	        global $langs, $conf, $user;
	    }
	    return 0;
	}*/
	function formattachOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;
		dol_syslog("ActionsMycyberoffice::formattachOptions::".GETPOST('action'), LOG_DEBUG);
		$langs->load("mycyberoffice@mycyberoffice8");
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		/*print '
			<div class="info">
			<img src="/htdocs/theme/eldy/img/info.png" border="0" alt="" title="Information">';
		print $langs->trans("mycybercover");
		print '</div>';*/
		
		if (in_array('productdocuments', explode(':', $parameters['context'])))
		{
                    print info_admin($langs->trans("mycybercover"));
		  if(GETPOST('sendit')) {
		  	//echo '<br/> do something only for the context "somecontext" '.GETPOST('sendit');
		  	require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
			$sortorder = "ASC";
			$sortfield = "name";
		  	if (! empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    		elseif (! empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
		  	$filesarray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		  	$array_picture = array();
		  	foreach($filesarray as $filearray)
			{
			  	if( $filearray['name'] ) {
			  		$pos_point = strrpos($filearray['name'], '.');
					$nom = substr($filearray['name'], 0, $pos_point); 
					$picture = $filearray['fullname'];
					$name = explode("/",$picture);
					$name = $name[sizeof($name)-1];
					if (preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$name,$reg)) {
                        $ext = '';
                    } else {
                        $ext='nok';
                    }
					$imgfonction='';
					
                    if ($ext!= 'nok' && $reg[1]) {
					if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
					if (strtolower($reg[1]) == '.png')  $ext= 'png';
					if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
					if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
					if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';
                    }
					if ($ext != 'nok') {
						$file = array("tmp_name"=>DOL_DOCUMENT_ROOT."/custom/mycyberoffice8/images_temp/$object->id$nom.$ext","name"=>$name);
						$img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
						@call_user_func_array("image$ext",array($img,$file['tmp_name']));
						@imagedestroy($img);
						array_push($array_picture,array("name" => $nom, "url" => DOL_MAIN_URL_ROOT.'/custom/mycyberoffice8/images_temp/'.$object->id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
					}
				}
			}
			$object->mycyber = $array_picture;
			if(method_exists ($object , 'call_trigger' ))
				$result=$object->call_trigger('PICTURE_CREATE',$user);
		  }
		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = '';//'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}
    function deleteFile($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $conf, $user;
	dol_syslog("ActionsMycyberoffice::deleteFile::".$parameters['GET']['action'].'::'.$parameters['GET']['confirm'], LOG_DEBUG);
	$langs->load("mycyberoffice@mycyberoffice8");
	$error = 0; // Error counter
	$myvalue = 'test'; // A result value

	if(in_array('productdocuments', explode(':', $parameters['context']))) {
            if($parameters['GET']['action'] == 'confirm_deletefile' && $parameters['GET']['confirm'] == 'yes') {
                $name = explode("/",$parameters['GET']['urlfile']);
                /*$pos_point = strrpos($filearray['name'], '.');
		$nom = substr($filearray['name'], 0, $pos_point);
		$picture = $filearray['fullname'];
		$name = explode("/",$picture);
		$name = $name[sizeof($name)-1];*/

		$name = $name[sizeof($name)-1];
		$nameasupprimer = $name;
                $pos_point = strrpos($nameasupprimer, '.');
                $nom = substr($nameasupprimer, 0, $pos_point);
		$object->mycyberDel = $nom;
		require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
		if (! $sortorder)
                    $sortorder="ASC";
		if (! $sortfield)
                    $sortfield="name";
		if (! empty($conf->product->enabled))
                    $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	    	elseif (! empty($conf->service->enabled))
                    $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
                $filesarray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		if ( !is_array($filesarray) || empty($filesarray) ) {
                    $filesarray = array();
   		}
		$array_picture = array();
		foreach($filesarray as $filearray)
		{
                    if( $filearray['name'] && $filearray['name'] != $nameasupprimer ) {
                        $pos_point = strrpos($filearray['name'], '.');
			$nom = substr($filearray['name'], 0, $pos_point); 
			$picture = $filearray['fullname'];
			$name = explode("/",$picture);
			$name = $name[sizeof($name)-1];
                        if (preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$name,$reg)) {
                            $ext = '';
                        } else {
                            $ext='nok';
                        }
			$imgfonction='';
			//print $nom.'-'.$name;exit;
                        if ($ext!= 'nok' && $reg[1]) {
			if (strtolower($reg[1]) == '.gif')
                            $ext= 'gif';
			if (strtolower($reg[1]) == '.png')
                            $ext= 'png';
			if (strtolower($reg[1]) == '.jpg')
                            $ext= 'jpeg';
			if (strtolower($reg[1]) == '.jpeg')
                            $ext= 'jpeg';
			if (strtolower($reg[1]) == '.bmp')
                            $ext= 'wbmp';
                        }
			if ($ext != 'nok') {
                            $file = array("tmp_name"=>DOL_DOCUMENT_ROOT."/custom/mycyberoffice8/images_temp/$object->id$nom.$ext","name"=>$name);
                            $img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
                            @call_user_func_array("image$ext",array($img,$file['tmp_name']));
                            @imagedestroy($img);
                            array_push($array_picture,array("name" => $nom, "url" => DOL_MAIN_URL_ROOT.'/custom/mycyberoffice8/images_temp/'.$object->id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
                            //$object->mycyberDel = $nom.'.'.$ext;
			}
                    }
		}
		$object->mycyber = $array_picture;
		if ( !is_array($object->mycyber) || empty($object->mycyber) ) {
                    $object->mycyber = array();
   		}
		if(method_exists ($object , 'call_trigger' ))
                    $result=$object->call_trigger('PICTURE_DELETE',$user);
            }//fin get
	}//fin context
	if (! $error) {
            $this->results = array('myreturn' => $myvalue);
            $this->resprints = '';//'A text to show';
            return 0; // or return 1 to replace standard code
	} else {
            $this->errors[] = 'Error message';
            return -1;
	}		
    }
}