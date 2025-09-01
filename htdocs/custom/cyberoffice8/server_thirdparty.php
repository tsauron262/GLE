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
$path = __DIR__.'/';
define('NOCSRFCHECK', 1);

require_once $path."../../master.inc.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
class DataServer
{
    public function getListOfThirdParties($authentication, $myparam, $myparam1, $myparam2)
    {
		global $db,$conf,$langs;

		dol_syslog("Call Dolibarr webservices interfaces");

		$langs->load("main");

		// Enable and test if module web services is enabled
		if (empty($conf->global->WEBSERVICES_KEY)) {
			$langs->load("cyberoffice@cyberoffice8");
			dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
			print $langs->trans("KeyForWebServicesAccess2");
			exit;
		}

		$now=dol_now();

		dol_syslog("Function: getListOfThirdParties login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity = $authentication['entity'];
    if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
        dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
        $mc = new ActionsMulticompany($db);
        $returnmc = $mc->switchEntity($authentication['entity']);
        $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
    }
    // Init and check authentication
    $objectresp=array();
    $arraythirdparties=array();
	
	$errorcode='';$errorlabel='';
    $error=0;

    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

    if (! $error)
    {
        $sql ="SELECT s.rowid as socRowid, s.nom as ref, s.ref_ext, s.address, s.zip, s.town, s.phone, s.fax, s.url, extra.*";
        $sql.=" FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as extra ON s.rowid=fk_object";

        $sql.=" WHERE entity=".$conf->entity;
        //print '********';
        //print_r($filterthirdparty) ;
        foreach($filterthirdparty as $key => $val)
        {
            if ($key == 'client'   && $val != '')  $sql.=" AND s.client = ".$db->escape($val);
            if ($key == 'supplier' && $val != '')  $sql.=" AND s.fournisseur = ".$db->escape($val);
        }
        $sql.=" LIMIT 10";
//        dol_syslog("Function: getListOfThirdParties sql=".$sql);

        $extrafields=new ExtraFields($db);
        $extralabels=$extrafields->fetch_name_optionals_label('societe',true);


        $resql=$db->query($sql);
        if ($resql)
        {
            $num=$db->num_rows($resql);

            $i=0;
            while ($i < $num)
            {
                $extrafieldsOptions=array();
                $obj=$db->fetch_object($resql);
                foreach($extrafields->attribute_label as $key=>$label)
                {
                    $extrafieldsOptions['options_'.$key] = $obj->{$key};
                }
                $arraythirdparties[]=array('id'=>$obj->socRowid.' '.$obj->ref
                );
                $arraythirdparties[$i] = array_merge($arraythirdparties[$i],$extrafieldsOptions);

                $i++;
            }
        }
        else
        {
            $error++;
            $errorcode=$db->lasterrno();
            $errorlabel=$db->lasterror();
        }
    }

    if ($error)
    {
        $objectresp = array(
            'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel,'authentication'=>$authentication),
            'thirdparties'=>htmlentities($conf->global->WEBSERVICES_KEY, ENT_COMPAT, 'UTF-8')
        );
    }
    else
    {
        $objectresp = array(
            'result'=>array('result_code' => 'OK', 'result_label' => ''),
            'thirdparties'=>$arraythirdparties
        );
    }

    return $objectresp;

	}
}
$options = array('uri' => $_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();
