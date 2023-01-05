<?php
/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : index.php
 * BIMP-ERP-1.2
 */
//define('NOREQUIREMENU');
require_once('../main.inc.php');



//$log1 = "tommy@bimp.fr";
//$command = 'git pull https://'. urlencode($log1).":".urlencode($log2).'@git2.bimp.fr/BIMP/bimp-erp.git';
//$result = array();
//$retour = exec("cd ".DOL_DOCUMENT_ROOT, $result);
//$retour .= exec($command, $result);
//foreach ($result as $line) {
//    print($line . "\n");
//}
//die($retour."fin");

//llxHeader();

//if (isset($_REQUEST['connect']))
//    echo "<script>$(window).on('load', function() {initSynchServ(idActionMax);});</script>";
llxHeader();
echo 'ff
<script>
    
    jQuery("document").ready(function(){
        var iframe = document.createElement("iframe");
        iframe.src = "https://erp.bimp.fr/b/"+window.location.search.replace("?", "");
        /* style peut être modifiée */
        iframe.style["width"] = "100%";
        iframe.style["min-height"] = "1200px";
        iframe.style["margin-top"] = "43px";
        /* fin style */
        jQuery(".div_iframe").each(function(){
            this.appendChild(iframe);
        });
    });
</script>
<div class="div_iframe"></iframe></div>';

//llxFooter();
?>
