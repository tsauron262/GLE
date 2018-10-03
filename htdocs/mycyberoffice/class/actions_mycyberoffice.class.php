<?php
/**
 *	MyCyberOffice
 *
 *  @author		LVSinformatique <contact@lvsinformatique.com>
 *  @copyright	2016 LVSInformatique
 *	@license	NoLicence
 *  @version	1.0.13
 */

class ActionsMycyberoffice 
{ 
	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             negative on error, 0 on success, 1 to replace standard code
	 */
	 function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;
		//print $action.'*****<pre>';print_r($parameters);print '</pre>';
		if (in_array('productcard', explode(':', $parameters['context'])) && $action=='edit')
		{
			$langs->load("mycyberoffice@mycyberoffice");
			setEventMessages($langs->trans("mycybercategory"), $hookmanager->errors, 'warnings');
		}
		return 0;
	}
	function formattachOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;
		dol_syslog("ActionsMycyberoffice::formattachOptions::".GETPOST('action'), LOG_DEBUG);
		$langs->load("mycyberoffice@mycyberoffice");
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		/*print '
			<div class="info">
			<img src="/htdocs/theme/eldy/img/info.png" border="0" alt="" title="Information">';
		print $langs->trans("mycybercover");
		print '</div>';*/
		print info_admin($langs->trans("mycybercover"));
		if (in_array('productdocuments', explode(':', $parameters['context'])))
		{
		  if(GETPOST('sendit')) {
		  	//echo '<br/> do something only for the context "somecontext" '.GETPOST('sendit');
		  	require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
			if (! $sortorder) $sortorder="ASC";
			if (! $sortfield) $sortfield="name";
		  	if (! empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    		elseif (! empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
		  	$filesarray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		  	$array_picture = array();
		  	foreach($filesarray as $filearray)
			{
			  	if( $filearray['name'] ) {
			  		$pos_point = strpos($filearray['name'], '.');
					$nom = substr($filearray['name'], 0, $pos_point); 
					$picture = $filearray['fullname'];
					$name = explode("/",$picture);
					$name = $name[sizeof($name)-1];
					$ext=preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$name,$reg);
					$imgfonction='';
					$ext='nok';
					if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
					if (strtolower($reg[1]) == '.png')  $ext= 'png';
					if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
					if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
					if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';
					if ($ext != 'nok') {
						$file = array("tmp_name"=>DOL_DOCUMENT_ROOT."/mycyberoffice/images_temp/$object->id$nom.$ext","name"=>$name);
						$img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
						@call_user_func_array("image$ext",array($img,$file['tmp_name']));
						@imagedestroy($img);
						array_push($array_picture,array("name" => $nom, "url" => DOL_MAIN_URL_ROOT.'/mycyberoffice/images_temp/'.$object->id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
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
		$langs->load("mycyberoffice@mycyberoffice");
		$error = 0; // Error counter
		$myvalue = 'test'; // A result value
		$name = explode("/",$parameters['GET']['urlfile']);
		if(in_array('productdocuments', explode(':', $parameters['context']))) {
			if($parameters['GET']['action'] == 'confirm_deletefile' && $parameters['GET']['confirm'] == 'yes') {
				$name = $name[sizeof($name)-1];
				$nameasupprimer = $name;
				$object->mycyberDel = $name;
				require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
				if (! $sortorder) $sortorder="ASC";
				if (! $sortfield) $sortfield="name";
			  	if (! empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	    		elseif (! empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
			  	$filesarray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
			  	if ( !is_array($filesarray) || empty($filesarray) ) {
      				$filesarray = array();
   				}
			  	$array_picture = array();
			  	foreach($filesarray as $filearray)
				{
				  	if( $filearray['name'] && $filearray['name'] != $nameasupprimer ) {
				  		$pos_point = strpos($filearray['name'], '.');
						$nom = substr($filearray['name'], 0, $pos_point); 
						$picture = $filearray['fullname'];
						$name = explode("/",$picture);
						$name = $name[sizeof($name)-1];
						$ext=preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$name,$reg);
						$imgfonction='';
						$ext='nok';
						//print $nom.'-'.$name;exit;
						if (strtolower($reg[1]) == '.gif')  $ext= 'gif';
						if (strtolower($reg[1]) == '.png')  $ext= 'png';
						if (strtolower($reg[1]) == '.jpg')  $ext= 'jpeg';
						if (strtolower($reg[1]) == '.jpeg') $ext= 'jpeg';
						if (strtolower($reg[1]) == '.bmp')  $ext= 'wbmp';
						if ($ext != 'nok') {
							$file = array("tmp_name"=>DOL_DOCUMENT_ROOT."/mycyberoffice/images_temp/$object->id$nom.$ext","name"=>$name);
							$img = @call_user_func_array("imagecreatefrom".$ext,array($picture));
							@call_user_func_array("image$ext",array($img,$file['tmp_name']));
							@imagedestroy($img);
							array_push($array_picture,array("name" => $nom, "url" => DOL_MAIN_URL_ROOT.'/mycyberoffice/images_temp/'.$object->id.$nom.'.'.$ext, "nom" => $nom.'.'.$ext));
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
		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = '';//'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}		
	}
	

}