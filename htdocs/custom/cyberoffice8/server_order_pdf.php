<?php
/*
 *  @author 	LVSinformatique <contact@lvsinformatique.com>
 *  @copyright  2021 LVSInformatique
 *  This source file is subject to a commercial license from LVSInformatique
 *  Use, copy or distribution of this source file without written
 *  license agreement from LVSInformatique is strictly forbidden.
 */
define('NOCSRFCHECK', 1);

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/cyberoffice8/class/cyberoffice.class.php';
include_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

class DataServer
{
    public function read($authentication, $myparam, $myparam1, $myparam2)
    {
        global $db, $conf, $langs, $dolibarr_main_url_root;
		dol_syslog("Call Dolibarr webservices interfaces::ServerOrderPdf ".$conf->entity);
        $now=dol_now();
        if ($authentication['entity']) {
            $conf->entity=$authentication['entity'];
        }
        if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
            dol_include_once(DOL_DOCUMENT_ROOT . '/custom/multicompany/class/actions_multicompany.class.php');
            $mc = new ActionsMulticompany($db);
            $returnmc = $mc->switchEntity($authentication['entity']);
            $conf->global->WEBSERVICES_KEY=dolibarr_get_const($db, 'WEBSERVICES_KEY', $authentication['entity']);
        }
        $objectresp=array();
        $resultparam=array();
        $errorcode='';
        $errorlabel='';
        $error=0;
        $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
        if ($error) {
            $objectresp = array('result'=>array(
                'error'         => $error,
                'errorcode'     => $errorcode,
                'errorlabel'    => $errorlabel)
            );
        } else {
            $fulllink = null;
            $object = new Facture($db);
            $cyber_current = new Cyberoffice;
            $cyber_current->entity = 0;
            $cyber_current->myurl = $authentication['myurl'];
            $indice_current = $cyber_current->numShop();
            $sql = "SELECT f.rowid as frowid, f.ref as fref, c.rowid as crowid, c.ref as cref, ef.share"
                . " FROM ".MAIN_DB_PREFIX."commande c"
                . " LEFT JOIN ".MAIN_DB_PREFIX."element_element ee ON (ee.fk_source=c.rowid AND ee.sourcetype='commande' AND ee.targettype='facture')"
                . " LEFT JOIN ".MAIN_DB_PREFIX."facture f ON (ee.fk_target = f.rowid)"
                . " LEFT JOIN ".MAIN_DB_PREFIX."ecm_files ef ON (ef.src_object_type='facture' AND ef.src_object_id=f.rowid)"
                . " WHERE c.import_key = 'P".$indice_current."-".$myparam['id_order']."'";
            $resql = $db->query($sql);
            if ($resql && $conf->global->CYBEROFFICE_invoicelink) {
                if ($db->num_rows($resql) > 0) {
                    $res = $db->fetch_array($resql);
                    $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
                    $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;
                    $forcedownload = 0;
                    $paramlink = '';
                    if (!empty($res['share'])) {
                        $paramlink .= ($paramlink ? '&' : '').'hashp='.$res['share'];
                        $fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');
                    } else {
                        if (!empty($res['frowid']) && $res['frowid']>0) {
                            $object->fetch($res['frowid']);
                            $ecmfile = new EcmFiles($db);
                            $result = $ecmfile->fetch(0, '', $object->last_main_doc);
							if ($result==1) {
								$ecmfile->share = getRandomPassword(true);
								$ecmfile->update($fuser);
								$paramlink .= ($paramlink ? '&' : '').'hashp='.$ecmfile->share;
								$fulllink = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');
							}
                        }
                    }

                    $objectresp['data'] = array(
                        'commande'  => $res['crowid'].'/'.$res['cref'],
                        'facture'   => $res['frowid'].'/'.$res['fref'],
                        'link'      => $fulllink,
                    );
                }
            }
            $objectresp['result'] = array(
                'error'         => "OK",
                'errorcode'     => "OK",
                'errorlabel'    => "OK",
            );
        }
        return $objectresp;
    }
}

$options = array('uri'=>$_SERVER['SERVER_NAME']);

$server = new SoapServer(null, $options);

$server->setClass('DataServer');

$server->handle();