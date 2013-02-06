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




 $GLOBALS["webservices_uri"] = "http://10.91.130.6:8280/jasperserver/services/ReportScheduler?wsdl";
//$webservices_uri = $proto . "://".$host . ":".$port . $path;
//Connect to webServices
require_once('ReportSchedulerService.php');
$attachment = array();
$someInputControlUri="";
$req = new ReportSchedulerService($GLOBALS["webservices_uri"], "eos", "redalert");
$ret = $req->getAllJobs();
echo "<TABLE>";
foreach($ret as $key => $val)
{
    print "<TR><TD>".$key . "</TR>";
    foreach($val as $key1 => $val1)
    {
        print "<TR><TD>".$key1 . "<TD>".$val1;
    }
}

?>
