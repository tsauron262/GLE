<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 29 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : nouveau.php
  * GLE-1.2
  */

  //Creer une Affaire
require_once('pre.inc.php');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

$js = '<script>var DOL_URL_ROOT="'.DOL_URL_ROOT.'";</script>';
$js = '<script>var DOL_DOCUMENT_ROOT="'.DOL_DOCUMENT_ROOT.'";</script>';
$js .= '<script>';
$js .= <<<EOF
    jQuery(document).ready(function(){
                        jQuery('#form').validate({
                                                    errorElement: "em",
                                                rules: {
                                                    nom: "required",
                                                    description: {
                                                        required: true,
                                                        minLength: 5
                                                    }
                                                },
                                                messages: {
                                                    nom: "<br/>Ce champs est requis",
                                                    description:  "<br/>Ce champs est requis",
                                                }
                                                });
                        });
EOF;
$js .= '</script>';
$msg = "";
if ($_REQUEST['action'] == 'nouveau')
{
    require_once(DOL_DOCUMENT_ROOT.'/Babel_Affaire/Affaire.class.php');
    $affaire = new Affaire($db);
    $ref = $affaire->getNextNumRef();
    $affaire->ref= $ref;
    $affaire->nom =addslashes($_REQUEST['nom']);
    $affaire->description =addslashes($_REQUEST['nom']);
    $ret = $affaire->create();
    if ($ret > 0)
    {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('AFFAIRE_NEW',$user,$user,$langs,$conf);
        if ($result < 0) { $error++; }
        // Fin appel triggers
        header('location: fiche.php?id='.$ret);
    } else {
        $msg = "Erreur d'insertion";
    }


}

llxHeader($js,'Nouvelle affaire',"","1");
if (strlen($msg) > 0 ) print '<div class="ui-error">'.$msg.'</div>';

print "<br/>";
print "<div class='titre'>Nouvelle affaire</div>";
print "<br/>";
print "<br/>";
print "<form id='form' action='nouveau.php' method='post'>";
print "<input type='hidden' type='text' name='action' value='nouveau'>";
print "<table id='mainTable' cellpadding=15 width=45%>";
print "<tr><th class='ui-widget-header ui-state-default'>Nom";
print "    <td class='ui-widget-content'><input type='text' name='nom' id='nom'>";
print "<tr><th class='ui-widget-header ui-state-default'>Ref.";
print "    <td class='ui-widget-content'>(PROV-";

require_once(DOL_DOCUMENT_ROOT.'/Babel_Affaire/Affaire.class.php');
$affaire = new Affaire($db);
print $affaire->getNextNumRef();
print ")";
print "<tr><th class='ui-widget-header ui-state-default'>Description";
print "    <td class='ui-widget-content'><textarea name='description' id='description'></textarea>";
print "<tr><th colspan='2' class='ui-widget-header ui-state-default'><button class='butAction ui-corner-all ui-state-default ui-widget-header'>Ajouter</button>";
print "       <button class='butAction ui-corner-all ui-state-default ui-widget-header' onClick=\"location.href='index.php'; return(false);\">Annuler</button>";
print "</table>";
print "</form>";

?>