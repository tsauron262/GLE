<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 13 sept. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : fiche-xml_response.php
  * GLE-1.2
  */
    require_once('../../main.inc.php');
   $comId = $_REQUEST['id'];
    //Ajoute une intervention à la commande
    $requete = "SELECT rowid FROM ".MAIN_DB_PREFIX."Synopsis_fichinter WHERE fk_commande is null ";
    $sql = $db->query($requete);
    print "<table cellpadding=10 width=700>";
    print "<tr><th class='ui-widget-header ui-state-default'>Ref";
    print "    <th class='ui-widget-header ui-state-default'>Tiers";
    print "    <th class='ui-widget-header ui-state-default'>Statut";
    print "    <th class='ui-widget-header ui-state-default'>Date";
    print "    <th class='ui-widget-header ui-state-default'>Dur&eacute;e";
    if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
    {
        print "    <th class='ui-widget-header ui-state-default'>Action";
    }
    require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
    require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
    while ($res = $db->fetch_object($sql))
    {
        $finter = new FichInter($db);
        $finter->fetch($res->rowid);
        print "<tr id='FI-".$res->rowid."'><td class='ui-widget-content'>".$finter->getNomUrl(1);
        if ($finter->fk_soc >0 )
        {
            print "<td class='ui-widget-content'>".utf8_encodeRien($finter->societe->getNomUrl(1));
        } else {
            print "<td class='ui-widget-content'>-";
        }
        print "<td class='ui-widget-content'>".$finter->getLibStatut(4);
        print "<td class='ui-widget-content'>".date('d/m/Y',$finter->di);
        $durArr = convDur($finter->duree);
        $durr = $durArr['hours']['abs'].'h'.$durArr['minutes']['rel'];
        print "<td class='ui-widget-content'>".$durr;
        if ($user->rights->SynopsisPrepaCom->interventions->Modifier)
        {
            print "<td class='ui-widget-content'><button onClick='location.href=\"".DOL_URL_ROOT."/fichinter/fiche.php?id=".$res->rowid."\"' class='butAction'>Modifier</button><button onClick='associateFI(".$res->rowid.");' class='butAction'>Associ&eacute;</button>";
        }
    }
    print "</table>";

    print "<div id='AssDialog'>";
    $commande = new Synopsis_Commande($db);
    $commande->fetch($comId);
    print "&Ecirc;tes vous sur de vouloir attacher cette FI &agrave; cette commande ?<br/><em>Il faudra ensuite la modifier pour rattacher les lignes de FI aux lignes de commandes</em>";
    print "</div>";


print <<<EOF
<script>
var idLocal;
jQuery(document).ready(function(){
    jQuery('#AssDialog').dialog({
            autoOpen: false,
            hide: 'slide',
            modal: true,
            width: 550,
            show: 'slide',
            title: "Association fiche intervention",
            buttons: {
                Ok: function(){
                    var comdetId = comId;
                    var self = this;
                    jQuery.ajax({
                        url:"ajax/xml/associateFI-xml_response.php",
                        datatype:"xml",
                        data: 'id='+comdetId+"&fid="+idLocal,
                        type:"POST",
                        cache: false,
                        success: function(msg){
                                jQuery('#FI-'+idLocal).remove();
                                jQuery(self).dialog("close");
                            },
                        });
                },
                Annuler: function(){
                    jQuery(this).dialog("close");
                }
            }
    })

});
function associateFI(pId){
    idLocal = pId;
    jQuery('#AssDialog').dialog('open');
}
</script>

EOF;
function convDur($duration)
{

    // Initialisation
    $duration = abs($duration);
    $converted_duration = array();

    // Conversion en semaines
    $converted_duration['weeks']['abs'] = floor($duration / (60*60*24*7));
    $modulus = $duration % (60*60*24*7);

    // Conversion en jours
    $converted_duration['days']['abs'] = floor($duration / (60*60*24));
    $converted_duration['days']['rel'] = floor($modulus / (60*60*24));
    $modulus = $modulus % (60*60*24);

    // Conversion en heures
    $converted_duration['hours']['abs'] = floor($duration / (60*60));
    $converted_duration['hours']['rel'] = floor($modulus / (60*60));
    $modulus = $modulus % (60*60);

    // Conversion en minutes
    $converted_duration['minutes']['abs'] = floor($duration / 60);
    $converted_duration['minutes']['rel'] = floor($modulus / 60);
    if ($converted_duration['minutes']['rel'] <10){$converted_duration['minutes']['rel'] ="0".$converted_duration['minutes']['rel']; } ;
    $modulus = $modulus % 60;

    // Conversion en secondes
    $converted_duration['seconds']['abs'] = $duration;
    $converted_duration['seconds']['rel'] = $modulus;

    // Affichage
    return( $converted_duration);
}
?>