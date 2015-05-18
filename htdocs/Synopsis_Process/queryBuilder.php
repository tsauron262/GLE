<?php

/*
 */
/**
 *
 * Name : queryBuilder.php
 * GLE-1.2
 */


$req1 = $_REQUEST['requete'];
$req2 = $_REQUEST['requeteValue'];
$_POST['requete'] = '';
$_POST['requeteValue'] = '';

require_once('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/Synopsis_Process/class/process.class.php');

if (!$user->rights->process->configurer) {
    accessforbidden();
}

$forceUpdate = false;
if ($_REQUEST['action'] == 'update') {
    $requeteObj = new requete($db);
    $requeteObj->id = $_REQUEST['id'];
    $requeteObj->label = $_REQUEST['label'];
    $requeteObj->OptGroup = $_REQUEST['OptGroup'];
    $requeteObj->OptGroupLabel = $_REQUEST['OptGroupLabel'];
    $requeteObj->description = $_REQUEST['description'];
    $requeteObj->requete = $req1;
    $requeteObj->indexField = $_REQUEST['index'];
    $requeteObj->showFields = $_REQUEST['affiche'];
    $requeteObj->limit = $_REQUEST['limite'];
    $requeteObj->params = $_REQUEST['params'];
    $requeteObj->requeteValue = $req2;
    $requeteObj->tableName = $_REQUEST['tableName'];
    foreach ($_REQUEST as $key => $val) {
        if (preg_match('/^postTrait-([\w]*)$/', $key, $arr)) {
            $tmpArr[$arr[1]] = $val;
        }
    }
    $requeteObj->postTraitement = serialize($tmpArr);
    $res = $requeteObj->update();
    if ($res > 0) {
        header("Location: queryBuilder.php?id=" . $requeteObj->id);
        exit();
    } else {
        if ($res == -1) {
            $msg = "Erreur SQL : " . $requeteObj->error;
            $forceUpdate = 'Update';
        } else if ($res == -2) {
            $msg = "Erreur: " . $requeteObj->error;
            $id = -2;
            $forceUpdate = 'Update';
        }
    }
}
if ($_REQUEST['action'] == 'add') {
    $requeteObj = new requete($db);
    $requeteObj->requete = $req1;
    $requeteObj->description = $_REQUEST['description'];
    $requeteObj->label = $_REQUEST['label'];
    $res = $requeteObj->add();
    if ($res > 0) {
        header("Location: queryBuilder.php?id=" . $res);
        exit();
    } else {
        if ($res == -1)
            $msg = "Erreur SQL : " . $requeteObj->error;
        else if ($res == -2) {
            $msg = "Erreur: " . $requeteObj->error;
            $id = -2;
        }
        else
            $msg = "Erreur ind&eacute;finie : " . $requeteObj->error;
        $forceCreate = 'Create';
    }
}
if ($_REQUEST['action'] == 'del') {
    $requeteObj = new requete($db);
    $requeteObj->id = $_REQUEST['id'];
    $res = $requeteObj->del();
    if ($res) {
        header("Location: listQuery.php");
        exit();
    } else {
        $msg = "Erreur dans la supression";
    }
}

$js = <<<EOF
        <script>
            jQuery(document).ready(function(){
                jQuery('#createForm').validate();
                jQuery('#modForm').validate();
            });
        </script>
        <style>
            #modForm input, #createForm input { width:85%; text-align:center; }
            #modForm textarea, #createForm textarea  { width:95%; }
        </style>
EOF;

$js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"/>';


if ($_REQUEST['action'] == "Create" || $forceCreate) {
    llxHeader($js, "Nouvelle requête");

    print "<div class='titre'>Nouvelle requ&ecirc;te</div><br/>";
    if ($msg) {
        print "<div class='error ui-state-error'>" . $msg . "</div>";
    }
    print "<br/>";
    print "<form id='createForm' action='queryBuilder.php?action=add' method='POST'>";
    print "<table cellpadding=15 width=100%>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Label";
    if ($id == -2) {
        print "      <td align=center class='ui-widget-content'><input name='label' value='" . $_REQUEST['label'] . "' class='required error'>";
    } else {
        print "      <td align=center class='ui-widget-content'><input name='label' value='" . $_REQUEST['label'] . "' class='required'>";
    }
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Description";
    print "      <td align=center class='ui-widget-content'><textarea name='description' class='required'>" . $_REQUEST['description'] . "</textarea>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Requ&ecirc;te";
    print "      <td align=center class='ui-widget-content'><textarea name='requete' class='required'>" . $req1 . "</textarea>";
    print '  <tr>';
    print "      <th class='ui-widget-header' colspan=2><button class='butAction'>Ajouter</button>";
    print "</table></form>";
} else if ($_REQUEST['id'] > 0 && ($_REQUEST['action'] == 'mod' || $forceUpdate)) {
    $requeteObj = new requete($db);
    $requeteObj->fetch($_REQUEST['id']);
    llxHeader($js, "Modification requête");
    print "<div class='titre'>Modification requ&ecirc;te</div><br/>";
    if ($msg) {
        print "<div class='error ui-state-error'>" . $msg . "</div><br/>";
    }

    print "<form id='modForm' action='queryBuilder.php?action=update&id=" . $requeteObj->id . "' method='POST'><table cellpadding=15 width=100%>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default' width=150>Label";
    print "      <td align=left class='ui-widget-content' width=350><input type='text' name='label' value='" . $requeteObj->label . "'>";

    $requete = vsprintf($requeteObj->requete, unserialize($requeteObj->params));
    $requete .= " LIMIT 100 ";

    print "      <th class='ui-widget-header ui-state-default' width=150>Test";
    print "      <td width=350 align=left class='ui-widget-content'>" . $requete;


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Description";
    print "      <td align=left class='ui-widget-content'><textarea name='description'>" . $requeteObj->description . "</textarea>";

//Tester la requete

    $sql = $db->query($requete);
    print "      <th rowspan=11 class='ui-widget-header ui-state-default'>R&eacute;sultat";
    $th = array();
    if ($sql) {
        print "      <td rowspan=11 align=center class='ui-widget-content'>";
        if ($db->num_rows($sql) > 0) {
            $iter = 0;
            print "<table width=80%>";
            $th = array();
            $td = array();
            while ($res = $db->fetch_array($sql, MYSQL_ASSOC)) {
                foreach ($res as $key => $val) {
                    if (!is_int($key)) {
                        $th[$key] = $key;
                        $td[$key][] = "<td class='ui-widget-content'>" . $val;
                    }
                }
            }
            print "<tr><th class='ui-widget-header ui-state-focus'>";
            print join("<th class='ui-widget-header ui-state-focus'>", $th);
            print "<tr><td class='ui-widget-content'>";
            $newArray = array();
            foreach ($td as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    $newArray[$key1][$key] = $val1;
                }
            }
            foreach ($newArray as $key => $val) {
                print "<tr>";
                foreach ($val as $key1 => $val1) {
                    print $val1;
                }
            }
            print "</table>";
        } else {
            print "Vide";
        }
    } else {
        print "      <td rowspan=11 align=center class='ui-widget-content'>Erreur :" . $db->error . " " . $db->last_query . " " . $db->last_queryerror;
    }

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Requ&ecirc;te";
    print "      <td align=left class='ui-widget-content'><textarea name='requete'>" . $requeteObj->requete . "</textarea>";
    print '  <tr>';

    print "      <th class='ui-widget-header ui-state-default'>Valeur";
    print "      <td class='ui-widget-content'><select name='index'>";

    foreach ($th as $key => $val) {
        if ($val == $requeteObj->indexField) {
            print "<option SELECTED value='" . $val . "'>" . $val . "</option>";
        } else {
            print "<option value='" . $val . "'>" . $val . "</option>";
        }
    }
    print "</select>";

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Affichage";
    print "      <td class='ui-widget-content'><select class='noSelDeco' name='affiche[]' multiple=true size=6>";
    $arr = unserialize($requeteObj->showFields);
    foreach ($th as $key => $val) {
        if (in_array($val, $arr)) {
            print "<option SELECTED value='" . $val . "'>" . $val . "</option>";
        } else {
            print "<option value='" . $val . "'>" . $val . "</option>";
        }
    }
    print "</select>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>limite";
    print "      <td class='ui-widget-content'><input name='limite' value='" . $requeteObj->limit . "'>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Param&egrave;tres <br/><em>Liste &agrave; virgule</em>";
    print "      <td class='ui-widget-content'><input name='params' value='" . join(',', unserialize($requeteObj->params)) . "'>";
//parametres
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Opt Groupe</em>";
    print "      <td class='ui-widget-content'><select name='OptGroup'>";
    print "<option value=''></option>";
    $arr = $requeteObj->OptGroup;
    foreach ($th as $key => $val) {
        if ($val == $arr) {
            print "<option SELECTED value='" . $val . "'>" . $val . "</option>";
        } else {
            print "<option value='" . $val . "'>" . $val . "</option>";
        }
    }
    print "</select>";


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Opt Groupe Label</em>";
    print "      <td class='ui-widget-content'><select name='OptGroupLabel'>";
    print "<option value=''></option>";
    $arr = $requeteObj->OptGroupLabel;
    foreach ($th as $key => $val) {
        if ($val == $arr) {
            print "<option SELECTED value='" . $val . "'>" . $val . "</option>";
        } else {
            print "<option value='" . $val . "'>" . $val . "</option>";
        }
    }
    print "</select>";

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Post - Traitement";
    print "      <td class='ui-widget-content'>";
    print "<table width=100%>";
    $arr = unserialize($requeteObj->postTraitement);
    $tabPostTrait = unserialize($requeteObj->showFields);
    if (is_array($tabPostTrait))
        foreach ($tabPostTrait as $key => $val) {
            print "  <tr><td width=30% class='ui-widget-content'>" . $val;
            print "      <td class='ui-widget-content' align=center><input name='postTrait-" . $val . "' value='" . ($arr[$val] . "x" != "x" ? $arr[$val] : "") . "' type='text'>";
        }
    print "</table>";

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Requete des valeurs<br/><small><em>variable: [[indexField]] => indexField = valeur</em></small>";
    print "      <td class='ui-widget-content'>";
    print "      <textarea name='requeteValue'>";
    print $requeteObj->requeteValue;
    print "</textarea>";

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Table de l'index pour la requete de valeur";
    print "      <td class='ui-widget-content'>";
    print "      <input name='tableName' value='" . $requeteObj->tableName . "'>";


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default' colspan=4><button class='butAction'>Modifier</button>";
    print "                                                              <button onClick='location.href=\"queryBuilder.php?id=" . $requeteObj->id . "\"; return(false);' class='butAction'>Annuler</button>";

    print "</table></form>";
} else if ($_REQUEST['id'] > 0) {
    $requeteObj = new requete($db);
    $requeteObj->fetch($_REQUEST['id']);

    $js .= '<script>';
    $js .= 'var requeteId = ' . $_REQUEST['id'] . ' ;';
    $js .= <<< EOF
       jQuery(document).ready(function(){
            jQuery('#delDialog').dialog({
                buttons:{
                    "OK": function(){
                        location.href="queryBuilder.php?id="+requeteId+"&action=del"
                    },
                    "Annuler": function(){
                        jQuery('#delDialog').dialog('close');
                    },
                },
                autoOpen: false,
                width: 520,
                minWidth: 520,
                modal: true,
                title: "Supprimer une requ&ecirc;te",
            });
       });
       function delDialog(){
            jQuery('#delDialog').dialog('open');
       }
       </script>

EOF;

    llxHeader($js, "Visualisation requête");
    if ($msg) {
        print "<div class='error ui-state-error'>" . $msg . "</div><br/>";
    }

    print "<div class='titre'>Visualisation requ&ecirc;te</div><br/>";
    print " <table cellpadding=15 width=100%>";
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default' width=150>Label";
    print "      <td align=left class='ui-widget-content'>" . $requeteObj->getNomUrl(1);

    $requete = vsprintf($requeteObj->requete, unserialize($requeteObj->params));
    $requete .= " LIMIT 100 ";
    eval("\$requete = \"$requete\";");

    print "      <th class='ui-widget-header ui-state-default' width=150>Test";
    print "      <td align=left class='ui-widget-content'>" . $requete;


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Description";
    print "      <td align=left class='ui-widget-content'>" . $requeteObj->description;

//Tester la requete

    $sql = $db->query($requete);
    print "      <th rowspan=9 class='ui-widget-header ui-state-default'>Resultat";
    $th = array();
    if ($sql) {
        print "      <td rowspan=9 align=center class='ui-widget-content'>";
        if ($db->num_rows($sql) > 0) {
            $iter = 0;
            print "<table width=80%>";
            $th = array();
            $td = array();
            while ($res = $db->fetch_array($sql, MYSQL_ASSOC)) {
                foreach ($res as $key => $val) {
                    if (!is_int($key)) {
                        $th[$key] = $key;
                        $td[$key][] = "<td class='ui-widget-content'>" . $val;
                    }
                }
            }
            print "<tr><th class='ui-widget-header ui-state-focus'>";
            print join("<th class='ui-widget-header ui-state-focus'>", $th);
            print "<tr><td class='ui-widget-content'>";
            $newArray = array();
            foreach ($td as $key => $val) {
                foreach ($val as $key1 => $val1) {
                    $newArray[$key1][$key] = $val1;
                }
            }
            foreach ($newArray as $key => $val) {
                print "<tr>";
                foreach ($val as $key1 => $val1) {
                    print $val1;
                }
            }
            print "</table>";
        } else {
            print "Vide";
        }
    } else {
        $error = $db->lasterrno . " " . $db->lastqueryerror . " " . $db->lasterror . " " . $db->error;

        print "      <td rowspan=9 align=center class='ui-widget-content ui-state-error error'>Erreur :" . $error;
    }


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Requ&ecirc;te";
    print "      <td align=left class='ui-widget-content'>" . $requeteObj->requete;
    print '  <tr>';

    print "      <th class='ui-widget-header ui-state-default'>Valeur";
    print "      <td class='ui-widget-content'>" . $requeteObj->indexField;

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Affichage";
    print "      <td class='ui-widget-content'>";
    $arr = unserialize($requeteObj->showFields);
    if (count($arr) > 0) {
        print "<table>";
        foreach ($arr as $key => $val) {
            print "<tr><td>" . $val;
        }
        print "</table>";
    }
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>limite";
    print "      <td class='ui-widget-content'>" . $requeteObj->limit;
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Param&egrave;tres";
    print "      <td class='ui-widget-content'>";
    $arr = unserialize($requeteObj->params);
    if (count($arr) > 0) {
        print "<table>";
        if (is_array($arr))
            foreach ($arr as $key => $val) {
                print "<tr><td>" . $val;
            }
        print "</table>";
    }
    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Opt Groupe";
    print "      <td class='ui-widget-content'>";
    print $requeteObj->OptGroup;

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Opt Groupe Label";
    print "      <td class='ui-widget-content'>";
    print $requeteObj->OptGroupLabel;

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Post - Traitement";
    print "      <td class='ui-widget-content'>";
    print "<table width=100%>";
    $arr = unserialize($requeteObj->postTraitement);
    $tabPostTrait = unserialize($requeteObj->showFields);
    if (is_array($tabPostTrait))
        foreach ($tabPostTrait as $key => $val) {
            print "  <tr><td width=30% class='ui-widget-content'>" . $val;
            print "      <td class='ui-widget-content' align=center>" . ($arr[$val] . "x" != "x" ? $arr[$val] : "-");
        }
    print "</table>";

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Requete des valeurs";
    print "      <td class='ui-widget-content'>";
    print $requeteObj->requeteValue;

    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default'>Table de l'index pour la requete de valeur";
    print "      <td class='ui-widget-content'>";
    print $requeteObj->tableName;


    print '  <tr>';
    print "      <th class='ui-widget-header ui-state-default' colspan=4><button onClick='location.href=\"queryBuilder.php?id=" . $requeteObj->id . "&action=mod\"' class='butAction'>Modifier</button><button onClick='delDialog();' class='butActionDelete'>Supprimer</button>";

    print "</table>";
    print "<div id='delDialog'>&Ecirc;tes vous sur de vouloir effacer cette requ&ecirc;te ?</div>";
} else if (!$_REQUEST['id'] > 0) {
    header("Location: listQuery.php");
}


llxFooter('$Date: 2008/02/25 20:03:27 $ - $Revision: 1.20 $');
?>