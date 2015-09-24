<?php

/*

 */
/**
 *
 * Name : nouveau.php
 * GLE-1.2
 */
require_once('pre.inc.php');
$msg = "";
$js = "<script>";
if ($conf->global->COMPANY_USE_SEARCH_TO_SELECT) {
    $js .= <<< EOF
      function ajax_updater_postFct(socid)
      {
          if (socid > 0)
          {
                jQuery.ajax({
                    url:"ajax/contactSoc-xml_response.php",
                  type:"POST",
                  datatype:"xml",
                  data:"socid="+socid,
                  success: function(msg){
                        jQuery('#contactSociete').replaceWith("<div id='contactSociete'>"+jQuery(msg).find('contactsList').text()+"</div>");
                        jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                  }
                });
          } else {
            jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
          }
      }
EOF;
} else {
    $js .= <<< EOF
      jQuery(document).ready(function(){
        jQuery('#socid').change(function(){
          var socid = jQuery(this).find(':selected').val();
          if (socid > 0)
          {
                jQuery.ajax({
                  url:"ajax/contactSoc-xml_response.php",
                  type:"POST",
                  datatype:"xml",
                  data:"socid="+socid,
                  success: function(msg){
                      jQuery('#contactSociete').replaceWith("<div id='contactSociete'>"+jQuery(msg).find('contactsList').text()+"</div>");
                      jQuery('#contactSociete').find('select').selectmenu({style: 'dropdown', maxHeight: 300 });
                  }
                });
          } else {
            jQuery('#contactSociete').replaceWith("<div id='contactSociete'></div>")
          }
        });
      });
EOF;
}

$js .= <<< EOF
  jQuery(document).ready(function(){
    jQuery.validator.addMethod(
        'required',
        function(value, element) {
            return (value+"x"!="x");
        },
        '<br/>Ce champs est requis'
    );

    jQuery('#form').validate();
  });


EOF;

if ($_REQUEST['action'] == 'add') {
    require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
    $chrono = new Chrono($db);
    $chrono->description = addslashes($_REQUEST['description']);
    $chrono->socid = addslashes($_REQUEST['socid']);
    $chrono->model_refid = addslashes($_REQUEST['type']);
    $chrono->contactid = addslashes($_REQUEST['contactid']);
    $chrono->propalid = addslashes($_REQUEST['prop']);
    $chrono->projetid = addslashes($_REQUEST['projet']);
    $res = $chrono->create();
    if ($res > 0) {
        header('Location: card.php?action=Modify&id=' . $res);
    } else {
        $msg = "Erreur !";
    }
}

$js .="</script>";
llxHeader($js, 'Nouveau chrono');
print "<div class='titre'>Nouveau Chrono</div><br/>";
if (!$user->rights->synopsischrono->Generer == 1) {
    accessforbidden("Ce module ne vous est pas accessible", 0);
    exit;
}
if ($msg . "x" != 'x') {
    print "<div style='padding: 3px;'><span class='ui-icon ui-icon-info' style='float: left;'></span>" . $msg . "</div>";
}
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
$html = new Form($db);
print "<form action='nouveau.php' id='form' METHOD='POST'>";
print "<input type='hidden' name='action' value='add'>";
print "<table style='border-collapse: collapse; border-right: 1px Solid #ddd' cellpadding=15>";

//1 On selectionne le type
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_conf WHERE active = 1 ORDER BY titre";
$sql = $db->query($requete);




print "<tr><th class='ui-widget-header ui-state-default' width=150>Type de chrono";
print "    <th class='ui-widget-header ui-state-default' width=300>Description";
if (isset($conf->global->MAIN_MODULE_PROPALE))
    print "    <th class='ui-widget-header ui-state-default' width=300>Proposition comm.";
if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET))
    print "    <th class='ui-widget-header ui-state-default' width=300>Projet";
//2 On mets la description + le fichier + societe + contact
print "<tr><td class='ui-widget-content'><select name='type'>";
while ($res = $db->fetch_object($sql)) {
    $selected = (isset($_GET['typeid']) && $_GET['typeid'] == $res->id) ? " selected=\"selected\"" : "";
    print "<option value='" . $res->id . "'" . $selected . ">" . $res->titre . "</option>";
}
print "</select>";
print "    <td class='ui-widget-content'><textarea class='required' style='width: 100%;' name='description'></textarea>";


if (isset($conf->global->MAIN_MODULE_PROPALE)) {
    print "     <td class='ui-widget-content'><select name='prop'>";
    print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
    $requete2 = "SELECT * FROM " . MAIN_DB_PREFIX . "propal ";
    $sql2 = $db->query($requete2);
    while ($res = $db->fetch_object($sql2)) {
        $selected = (isset($_GET['propalid']) && $_GET['propalid'] == $res->rowid) ? " selected=\"selected\"" : "";
        print "<option value='" . $res->rowid . "'" . $selected . ">" . $res->ref . "</option>";
    }
}

if (isset($conf->global->MAIN_MODULE_SYNOPSISPROJET)) {
    print "     <td class='ui-widget-content'><select name='projet'>";
    print "<OPTION value=''>S&eacute;lectionner-></OPTION>";
    $requete3 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view ";
    $sql3 = $db->query($requete3);
    while ($res = $db->fetch_object($sql3)) {
        $selected = (isset($_GET['projetid']) && $_GET['projetid'] == $res->rowid) ? " selected=\"selected\"" : "";
        print "<option value='" . $res->rowid . "'" . $selected . ">" . $res->ref . " : " . $res->title . "</option>";
    }
}

print "<tr><th style='padding:5px;' class='ui-widget-header' colspan=4 align=right><button class='butAction'>Ajouter</button>";

print "</table>";
print "</form>";
?>
