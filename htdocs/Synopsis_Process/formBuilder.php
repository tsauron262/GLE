<?php
/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 dec. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : formBuilder.php
 * GLE-1.2
 */
require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');
global $langs;

$langs->load("process@Synopsis_Process");

if (!$user->rights->process->configurer) {
    accessforbidden();
}


$id = $_REQUEST['id'];
$msg = false;
$forceCreate = false;


//$js = '<script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/ui/ui.selectmenu.js" type="text/javascript"></script>';
$js .= '<script language="javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.js"></script>' . "\n";

if ($_REQUEST['action'] == "toggleActive") {
    $form = new formulaire($db);
    $form->fetch($id);
    $res = $form->toggleActive();
    if ($res > 0) {
        header('Location: formBuilder.php?id=' . $id);
    } else {
        $msg = "Erreur : " . $form->error;
    }
}

if ($_REQUEST['action'] == 'clone') {
    $form = new formulaire($db);
    $form->fetch($id);
    $res = $form->cloneForm();
    if ($res > 0) {
        header("Location: formBuilder.php?id=" . $res);
    } else {
        header("Location: formBuilder.php?id=" . $id);
    }
}

if ($_REQUEST['action'] == 'addForm') {
    $form = new formulaire($db);
    $form->description = $_REQUEST['description'];
    $form->label = $_REQUEST['label'];
    $newId = $form->add();
    if ($newId > 0) {
        header('Location: formBuilder.php?id=' . $newId);
    } else {
        if ($newId == -1)
            $msg = "Erreur SQL : " . $form->error;
        else if ($newId == -2) {
            $msg = "Erreur: " . $form->error;
            $id = -2;
        }
        else
            $msg = "Erreur ind&eacute;finie : " . $form->error;
        $forceCreate = 'Create';
    }
}

if ($_REQUEST['action'] == "Create" || $forceCreate) {
    $js .= <<<EOF
    <script>
    jQuery(document).ready(function(){
        jQuery('#createForm').validate({
            messages: {
                description: {
                    required:" <br/> Ce champs est requis"
                },
                label: {
                    required:" <br/> Ce champs est requis"
                }
            }
        });
    });
    </script>
EOF;



    llxHeader($js, "Nouveau formulaire");
    print "<div class='titre'>Nouveau formulaire</div><br/>";
    if ($msg) {
        print "<div class='error ui-state-error'>" . $msg . "</div>";
    }
    print "<br/>";
    //label description
    print "<form id='createForm' action='formBuilder.php?action=addForm' method=POST>";
    print "<table width=400 cellpadding=15>";
    print "<tr><th class='ui-widget-header ui-state-default'>Nom";
    if ($id == -2) {
        print "    <td class='ui-widget-content'><input class='required error' name='label' id='label' value='" . $_REQUEST['label'] . "'>";
    } else {
        print "    <td class='ui-widget-content'><input class='required' name='label' id='label' value='" . $_REQUEST['label'] . "'>";
    }
    print "<tr><th class='ui-widget-header ui-state-default'>Description";
    print "    <td class='ui-widget-content'><textarea class='required' name='description' id='description'>" . $_REQUEST['description'] . "</textarea>";
    print "<tr><th class='ui-widget-header' colspan=2><button class='butAction'>Ajouter</button>";
    print "</table>";
    print "</form>";
    llxFooter();
    exit;
} else if (!$id > 0) {
    header('Location: listForm.php');
}

$js .= <<< EOF
    <style>
        .ui-icon-trash, #preview { cursor: pointer; }
        .ui-icon-carat-2-n-s { cursor: move; }
        #sortable1 li { font-size: 80%; }
        #sortable1, #sortable2 { list-style-type: none; margin: 0; padding: 0; float: left;  }
        #sortable2 li {  margin: 0; padding:0; width: 950px; }
        #sortable2 li.ui-state-error { height: 75px; border-style: dashed;background-repeat: repeat-x; background-image: url("../Synopsis_Common/css/flick/images/ui-bg_inset-soft_95_fef1ec_1x100.png")}
    </style>
EOF;

$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.scrollfollow.js'></script>";
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Process/formBuilder.js'></script>";
$js .= "<script type='text/javascript' src='" . DOL_URL_ROOT . "/Synopsis_Common/jquery/jquery.easing.js'></script>";
$jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
$js .= ' <script src="' . $jspath . '/jquery.jeditable.js" type="text/javascript"></script>';
$js .= <<<EOF
        <style>
        #descForm, #nomForm { cursor: pointer; }
        #descForm button, #nomForm button{
            -moz-border-radius: 8px 8px 8px 8px;
            background-color: #0073EA;
EOF;
$js .= ' background-image: url("' . $conf->global->DOL_URL_ROOT . '/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_100_f6f6f6_1x100.png");';
$js .= <<<EOF
            background-repeat: repeat-x;
            border: 1px solid #0073EA;
            color: #fff;
            cursor: pointer;
            display: inline-block;
            font-family: 'Lucida Grande';
            font-size: 11px;
            font-style: normal;
            font-variant: normal;
            font-weight: bold;
            margin: 2px;
            min-width: 95px;
            padding: 5px 10px;
            text-align: center;
            text-transform: none;
        }

        #descForm button:hover, #nomForm button:hover{
            -moz-border-radius: 8px 8px 8px 8px;
            background-color: #F6F6F6;
EOF;
$js .= ' background-image: url("' . $conf->global->DOL_URL_ROOT . '/Synopsis_Common/css/flick/images/ui-bg_highlight-soft_25_0073ea_1x100.png");';
$js .= <<<EOF
            background-repeat: repeat-x;
            border: 1px solid #DDDDDD;
            color: #0073EA;
            cursor: pointer;
            display: inline-block;
            font-family: 'Lucida Grande';
            font-size: 11px;
            font-style: normal;
            font-variant: normal;
            font-weight: bold;
            margin: 2px;
        }
        </style>
            <script>
            
EOF;
$js .= " var formId = " . ($_REQUEST['id'] > 0 ? $_REQUEST['id'] : "false") . ";";
$js .= "var formId = " . $id . ";";
$js .= <<< EOF

            </script>
EOF;

llxHeader($js, "Constructeur de formulaire");
$form = new formulaire($db);
$form->fetch($id);

print '<div class="ficheForm">';
print <<<EOF
  <table width=1000 cellpadding=8>
EOF;
print "<tr><td colspan=2 id='restrictDraggable'>";
print "<form id='createForm' action='formBuilder.php?action=modForm' method=POST>";
print "<table width=100% cellpadding=15>";
print "<tr><th width=220 class='ui-widget-header ui-state-default'>Nom";
print "    <td class='ui-widget-content'><table cellpadding=10><tr><td>" . img_edit() . "<td><div id='nomForm'>" . $form->label . "</div></table>";
print "<tr><th class='ui-widget-header ui-state-default'>Description";
print "    <td class='ui-widget-content'><table cellpadding=10><tr><td>" . img_edit() . "<td><div id='descForm'>" . $form->description . "</div></table>";
print "<tr><th class='ui-widget-header ui-state-default'>Statut";
print "    <td class='ui-widget-content'><a href='formBuilder.php?action=toggleActive&id=" . $form->id . "'><table cellpadding=10><tr><td>" . img_edit() . "</td><td><div>" . $form->getLibStatut(5) . "</div></td></tr></table></a>";
print "</table>";
print "</form>";

print <<<EOF
    <tr>
        <td width=250 rowspan=1 valign=top style='min-width: 250px;max-width: 250px;' id='scrollparent' ><div style='position:relative;' id='testDraggable'>
            <table width=100% cellpadding=0>
                <tr>
                    <td width=100% rowspan=1>
                        <div class='ui-widget-header ui-state-hover ui-corner-top' style='padding: 10px 10px;'><span>Type &agrave; ajouter</span></div>
                <tr>
                    <td width=100%>
                        <div class='drag' style='width:99%; border: 1px Solid;' class='ui-widget-content'>
EOF;
print '  <ul id="sortable1" class="connectedSortable" style="padding-left: 0px; width:250px;">';
$requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_type ORDER BY label";
$sql = $db->query($requete);
$arrCodeToLabel = array();
$arrCodeTohasDescription = array();
$arrCodeTohasDflt = array();
$arrCodeTohasTitle = array();
$arrCodeTohasSource = array();
while ($res = $db->fetch_object($sql)) {
    $arrCodeToLabel[utf8_encode($res->code)] = utf8_encode($res->label);
    print "<li class='ui-state-default' style='width:234px; padding-top: 2px; padding-bottom: 2px; padding-left: 15px'><table width=100% class='" . $res->code . "'><tr><td>" . $res->label . "</td></tr></table></li>";
    $arrCodeTohasDescription[utf8_encode($res->code)] = utf8_encode($res->hasDescription);
    $arrCodeTohasDflt[utf8_encode($res->code)] = utf8_encode($res->hasDflt);
    $arrCodeTohasTitle[utf8_encode($res->code)] = utf8_encode($res->hasTitle);
    $arrCodeTohasSource[utf8_encode($res->code)] = utf8_encode($res->hasSource);
}
print <<<EOF
             </ul>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td width=100% rowspan=1>
                        <div class='ui-widget-header ui-state-hover ui-corner-bottom' style='padding: 5px 10px;'></div>
                    </td>
                </tr>
            </table></div>
        <td width=75% rowspan=1 valign=top style='min-width: 950px;'>
        <div id="idMsg"></div>
        <script>
EOF;
print 'var arrCodeToLabel=' . json_encode($arrCodeToLabel) . ';';
print 'var arrCodeTohasDescription=' . json_encode($arrCodeTohasDescription) . ';';
print 'var arrCodeTohasDflt=' . json_encode($arrCodeTohasDflt) . ';';
print 'var arrCodeTohasTitle=' . json_encode($arrCodeTohasTitle) . ';';
print 'var arrCodeTohasSource=' . json_encode($arrCodeTohasSource) . ';';
?>
</script>
<table width=952 cellpadding=15>
    <thead>
        <tr>
            <td width=100% colspan=6 style='padding:0; border-bottom: 1px Solid;'>
                <div class='ui-widget-header ui-state-hover ui-corner-top' style='padding: 10px 10px;'><span>Formulaire</span><span title='Aper&ccedil;u'><span id='preview' style="float:right" class='ui-icon ui-icon-extlink'></span></span></div>
        <tr><th width=75 class='ui-widget-header ui-state-default'>Type
            <th width=100 class='ui-widget-header ui-state-default'>Titre
            <th width=225 class='ui-widget-header ui-state-default'>Description
            <th width=150 class='ui-widget-header ui-state-default'>Valeur par d&eacute;faut
            <th width=215 class='ui-widget-header ui-state-default'>Source
            <th width=50 class='ui-widget-header ui-state-default'>&nbsp;
    </thead>
</table>

<ul id="sortable2" class="connectedSortable" style='min-height: 100px; min-width:100%; padding-bottom:5px;'>
<?php
$form = new Formulaire($db);
$form->fetch($id);
$i = 0;
foreach ($form->lignes as $key => $val) {
    $i++;
    $select = getSelect(($val->src->type . $val->src->uniqElem->id . "x" != "x" ? $val->src->type . $val->src->uniqElem->id : false), $val->id, $val->type->Source);
    $select2 = getSelect(($val->dflt . "x" != "x" ? $val->dflt : false), '' . $val->id . '-var', $val->type->Source, true);

    print "<li id='sortable_" . $val->id . "' class='ui-state-highlight'>";
    print '  <form onsubmit="return(false);">';
    print '    <table class="' . $val->type->code . '" cellpadding=15 width=952>';
    print '      <tr>';
    print '        <td width=75 align=center class="' . $val->type->code . '"><input type="hidden" name="type-' . $val->id . '" value="' . $val->type->code . '">' . $val->type->label . '</td>';
    if ($val->type->hasTitle == 1)
        print '        <td width=100 align=center><textarea name="titre-' . $val->id . '" style="width:75%; height: 2em;">' . $val->label . '</textarea></td>';
    else
        print '        <td width=100 align=center><input type="hidden" name="titre-' . $val->id . '" style="width:75%" value="' . $val->label . '"></td>';
    if ($val->type->hasDescription == 1)
        print '        <td width=225 align=center><textarea name="descr-' . $val->id . '" style="width:75%">' . $val->description . '</textarea></td>';
    else
        print '        <td width=225 align=center><input type="hidden" name="descr-' . $val->id . '" style="width:75%" value="' . $val->description . '"></td>';

    if ($val->type->hasDflt == 1) {
        print '        <td width=150 align=center>';
        print '<table><tr><td>';
        if (preg_match('/^[GLOBVAR][0-9]*$/', $val->dflt))
            print '             <input name="dflt-' . $val->id . '" style="width:75%" value="' . $val->dflt . '">';
        else
            print '             <input name="dflt-' . $val->id . '" style="width:75%" value="">';
        print '       <tr><td>ou';
        print '       <tr><td>';
        print $select2;
        print '</table>';
        print '         </td>';
    } else {
        print '        <td width=150 align=center><input type="hidden" name="dflt-' . $val->id . '" style="width:75%" value="' . $val->dflt . '"></td>';
    }
    if ($val->type->hasSource > 0)
        print '        <td width=215 align=center>' . $select . '</td>';
    else
        print '        <td width=215 align=center></td>';
    print '        <td align=center width=50><table><tr><td style="padding: 0"><span class="ui-icon ui-icon-gear"></span><td style="padding: 0"><span class="ui-icon ui-icon-carat-2-n-s"></span><td style="padding: 0"><span class="ui-icon ui-icon-trash"></span></table>';
    print '    </table>';
    print '  </form>';
    print "</li>";
}
?>
</ul>
<button id='savButton' class='butAction'>Sauvegarder</button>
<button id='cloneButton' class='butAction'>Cloner</button>
<button id='supprButton' class='butActionDelete'>Supprimer</button>
</table>
</div>
<div id='paramsDialog' style='display:none;'>
    <div id='formParamsDiv'>
    </div>
</div>
<div style='display:none;'>
    <div id="srcSelClone">
    <?php
    $select = getSelect(false, false, 2, false);

    print $select;
    ?>

    </div>
    <div id="srcSelDfltClone">
<?php
$select = getSelect(false, false, 2, true);

print $select;
?>

    </div>
</div>
</html>
        <?php

        function getSelect($src_refid = false, $name = false, $source = 2, $forDfltVal = false) {
            global $db;
            $select = "";
            if ($name && $forDfltVal)
                $select = '<select class="noSelDeco" name="dflt-' . $name . '">';
            else if ($name)
                $select = '<select class="noSelDeco" name="src-' . $name . '">';
            else
                $select = '<select class="noSelDeco">';
            $select.= "<OPTION value=''>Selectionner-></OPTION>";
            if (!$forDfltVal) {
                $select.= "<OPTGROUP label='Requete'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete ORDER BY label";
                $sql = $db->query($requete);
                while ($res = $db->fetch_object($sql)) {
                    if ($src_refid == 'r' . $res->id)
                        $select.= "<OPTION SELECTED value='r-" . $res->id . "'>" . $res->label . "</OPTION>";
                    else
                        $select.= "<OPTION value='r-" . $res->id . "'>" . $res->label . "</OPTION>";
                }
                $select.= "</OPTGROUP>";
            }

            $select.= "<OPTGROUP label='Variable'>";
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_global ORDER BY label";
            $sql = $db->query($requete);
            while ($res = $db->fetch_object($sql)) {
                if ($src_refid == 'g' . $res->id || ($forDfltVal && $src_refid == '[GLOBVAR]' . $res->id))
                    $select.= "<OPTION SELECTED value='g-" . $res->id . "'>" . $res->label . "</OPTION>";
                else
                    $select.= "<OPTION value='g-" . $res->id . "'>" . $res->label . "</OPTION>";
            }
            $select.= "</OPTGROUP>";

            if (!$forDfltVal) {
                $select.= "<OPTGROUP label='Liste'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_list ORDER BY label";
                $sql = $db->query($requete);
                while ($res = $db->fetch_object($sql)) {
                    if ($src_refid == 'l' . $res->id)
                        $select.= "<OPTION SELECTED value='l-" . $res->id . "'>" . $res->label . "</OPTION>";
                    else
                        $select.= "<OPTION value='l-" . $res->id . "'>" . $res->label . "</OPTION>";
                }
                $select.= "</OPTGROUP>";
                $select.= "<OPTGROUP label='Fonction'>";
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_fct ORDER BY label";
                $sql = $db->query($requete);
                while ($res = $db->fetch_object($sql)) {
                    if ($src_refid == 'f' . $res->id)
                        $select.= "<OPTION SELECTED value='f-" . $res->id . "'>" . $res->label . "</OPTION>";
                    else
                        $select.= "<OPTION value='f-" . $res->id . "'>" . $res->label . "</OPTION>";
                }
                $select.= "</OPTGROUP>";
            }
            $select.='</SELECT>';
            return($select);
        }

        llxFooter('$Date: 2007/05/28 11:51:00 $ - $Revision: 1.6 $');
        ?>