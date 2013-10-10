<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */

require_once('../../../main.inc.php');
require_once('../deplacement.class.php');
require_once('../../../user.class.php');

$action = $_REQUEST['action'];
$userid = $_REQUEST['userid'];

$offset =($_REQUEST['offset']?$_REQUEST['offset']:0);
$id = ($_REQUEST['id']?$_REQUEST['id']:"data_grid");
$page_size = ($_REQUEST['page_size']?$_REQUEST['page_size']:-1);

 switch ($action)
 {
    case "list":
        $ndf = new Ndf($db);
        if ($userid ."x" != "x")
        {
            $ndf->fetchAll($userid);
        } else {

            $ndf->fetchAll();
        }
        header("Content-Type: text/xml");
        $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';

        print "<ajax-response>\n";
        print "\t<response type='object' id='".$id."_updater'>\n";
        print "\t\t<rows update_ui='true' offset='".$offset."'>\n";
//var_dump($ndf->allArray);
        $tmpusr=new User($db);
        foreach($ndf->allArray as $key=>$val)
        {//1996-07-04
            $tmpusr->fetch($val['fk_user_author']);
            $ndf->statut=$val['statut'];


            print "\t\t\t<tr>
                       \t\t\t\t<td>".$val['id']."</td>\n
                       \t\t\t\t<td>".$val['periode']."</td>\n
                       \t\t\t\t<td>".$val['periode']."</td>\n
                       \t\t\t\t<td><![CDATA[". utf8_encode($ndf->getLibStatut(4))."</img>]]></td>\n
                       \t\t\t\t<td><![CDATA[".$val['fk_user_author']."]]></td>\n
                       \t\t\t\t<td><![CDATA[<img height=12pt border=0  src='".DOL_URL_ROOT.'/theme/'.$conf->theme."/img/object_user.png'</img>]]></td>\n
                       \t\t\t\t<td>". utf8_encode($tmpusr->prenom .' ' .$tmpusr->nom )."</td>\n
                       \t\t\t\t<td>". utf8_encode($val['total'])."</td>\t\t\t</tr>\n";
        }
        print "\t\t</rows>\n";
        print "\t</response>\n";
        print "</ajax-response>\n";


    break;

 }
?>
