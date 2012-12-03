<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 28 oct. 2010
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : nouveau.php
  * GLE-1.2
  */

    require_once('pre.inc.php');
    require_once(DOL_DOCUMENT_ROOT."/Synopsis_Tools/commandeGroup/commandeGroup.class.php");
    $msg =  "";
    if (!$user->rights->commande->commande->group) accessforbidden();
$js = <<<EOF
<script>
jQuery(document).ready(function(){
    jQuery('#grpForm').validate({
        rules: {
            nom: 'required'
        },
        messages: {
            nom: '<br>Ce champs est requis&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
        }
    });
});
</script>
EOF;
    llxHeader($js);
    print "<br/>";
    print "<div class='titre'>Nouveau groupe de commande";
    print "</div>";
    print "<br/>";
    print "<div class='tabs'>";
    print "<form id='grpForm' action='fiche.php?action=create' method='POST'>";
    print "<table cellpadding=15 width=500>";
    print "<tr><th class='ui-widget-header ui-state-default'>Nom du groupe</th>";
    print "    <td align=center class='ui-widget-content'><input class='required' type='text' name='nom' id='nom' size=30 ></td>";
    print "<tr><th align=right colspan=2 class='ui-widget-header' style='padding:5px;'><button class='ui-button'>Ajouter</button>";
    print "</table>";

    print "</form>";
    print "</div>";



?>