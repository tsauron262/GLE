<?php

function getValueForm($chrid, $keyid, $socid, $withEntete = true) {
    require_once(DOL_DOCUMENT_ROOT."/synopsischrono/class/chrono.class.php");
    global $db, $user;
    $requete = "SELECT k.nom,
                           k.id,
                           "/*v.`value`,*/."
                           t.nom as typeNom,
                           t.hasSubValeur,
                           t.subValeur_table,
                           t.subValeur_idx,
                           t.subValeur_text,
                           t.htmlTag,
                           t.htmlEndTag,
                           t.endNeeded,
                           t.cssClass,
                           t.cssScript,
                           t.jsCode,
                           t.valueIsChecked,
                           t.valueIsSelected,
                           t.valueInTag,
                           t.valueInValueField,
                           t.sourceIsOption,
                           k.type_subvaleur,
                           k.extraCss,
                           t.phpClass
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur AS t,
                           " . MAIN_DB_PREFIX . "synopsischrono_key AS k
                "/*      LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_value AS v ON v.key_id = k.id AND v.chrono_refid = " . $chrid . "*/."
                     WHERE t.id = k.type_valeur
                       AND k.id = " . $keyid;
//    print $requete;
    $sql = $db->query($requete);
    
    $chrono = new Chrono($db);
    $chrono->fetch($chrid);
    $chrono->getValuesPlus();
    $res = $chrono->valuesPlus[$keyid];
    
//    while ($res = $db->fetch_object($sql)) {
        getValueForm3($res, $chrid, $keyid, $socid, $withEntete);
//    }
}

function getValueForm2($res, $chr, $withEntete = true) {
    $socid = $chr->socid;
    $chrid = $chr->id;
    $keyid = $res->id;
    getValueForm3($res, $chrid, $keyid, $socid, $withEntete);
}

function getValueForm3($res, $chrid, $keyid, $socid, $withEntete = true) {
    global $db, $user;
    $res->value = stripslashes($res->value);
    if ($withEntete) {
        print '<tr><th class="ui-state-default ui-widget-header" nowrap class="ui-state-default">' . $res->nom;
        print '    <td  class="ui-widget-content" colspan="3">';
    }
    if ($res->phpClass == "lien")
        print '<input type="hidden" class="chrid-keyid" value="' . $chrid . "-" . $keyid . '"/>';
    if ($res->hasSubValeur == 1) {
        if ($res->sourceIsOption) {
            $tag = preg_replace('/>$/', "", $res->htmlTag);
            $html = "";
            $html .= $tag;
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
            $tmp = $res->phpClass;
            $obj = new $tmp($db);
            $obj->socid = $socid;
            $obj->cssClassM = $res->extraCss;
            $obj->idChrono = $chrid;
            $obj->fetch($res->type_subvaleur);
            $obj->getValues();
            echo $obj->formHtml;
            if (isset($obj->tabVal[0])) {
                $res->value = $obj->tabVal[0];
                $res->valueIsSelected = true;
            }
            if (($res->value == 0 || $res->value == '') && stripos($obj->cssClassM, "myUser") !== false)
                $res->value = $user->id;
            $extra_extraClass = "";
            if ($obj->OptGroup . "x" != "x") {
                $extra_extraClass = " double noSelDeco ";
                print <<<EOF
                      <script>
jQuery(document).ready(function(){
EOF;
                print "jQuery('#Chrono" . $res->id . "').jDoubleSelect({\n";
                print <<<EOF
        text:'',
        finish: function(){
EOF;
                print " jQuery('#Chrono" . $res->id . "_jDS').each(function(){
                            var select = $(this);
//                            $(select).combobox({
//                                selected: function(event, ui) {
//                                    select.find('option[value=\"'+$(this).val()+'\"]').attr('selected', 'selected');
//                                    select.change();
//                                }
//                            });
      });
        },
        el1_change: function(){";
                print " /*jQuery('#Chrono" . $res->id . "_jDS_2').selectmenu({\n";
                print <<<EOF
                style:'dropdown',
                maxHeight: 300
            });*/
        },
EOF;
                print "el2_dest: jQuery('#destChrono" . $res->id . "'),\n";
                print <<<EOF
    });
});

                      </script>
EOF;
            }
            if ($res->extraCss . $res->cssClass . $extra_extraClass . "x" != "x") {
                $html .= " class='" . $res->cssClass . " " . $res->extraCss . " " . $extra_extraClass . "' ";
            }
            if ($res->valueInValueField) {
                $html .= " value='" . $res->value . "' ";
            }
            if ($res->valueIsChecked) {
                $html .= ($res->value == 1 ? " CHECKED " : "");
            }
            $html .= " name='Chrono" . $res->id . "' ";
            $html .= " id='Chrono" . $res->id . "' ";
            $html.=">";
            if ($res->valueInTag) {
                $html .= $res->value;
            }
            $remOpt = false;

            $html .= "<option value=''>S&eacute;lectionner</option>";
            if ($obj->OptGroup . "x" != "x") {
                $html = "<table><tr><td width=50%>" . $html;
                $html .= "<OPTGROUP label=' '>";
                $html .= "<OPTION value='0'></OPTION>";
                foreach ($obj->valuesGroupArr as $key => $val) {
                    $val['label'] = str_replace(" ", "_", $val['label']);
                    $val['label'] = str_replace("'", "_", $val['label']);
                    $val['label'] = str_replace("/", "_", $val['label']);
                    $html .= "<OPTGROUP label='" . $val['label'] . "'>";
                    $html .= "<OPTION value='0'></OPTION>";
                    foreach ($val['data'] as $key1 => $val1) {
                        $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key1 ? "SELECTED" : "") . " value='" . $key1 . "'>" . $val1 . "</OPTION>";
                    }
                    $html .= "</OPTGROUP>";
                }
                $html .= "<td><div id='destChrono" . $res->id . "'></div>";
                $html .= "</table>";
                echo ajax_combobox("Chrono" . $res->id . "_jDS", "", 0);
            } else {
                foreach ($obj->valuesArr as $key => $val) {
                    $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key ? "SELECTED" : "") . " value='" . $key . "'>" . $val . "</OPTION>";
                }
                echo ajax_combobox("Chrono" . $res->id . "", "", 0);
            }
            if ($res->endNeeded == 1)
                $html .= $res->htmlEndTag;
            print $html;
        } else {
            //Beta
            if ($res->phpClass == 'fct')
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
            $tmp = $res->phpClass;
            $obj = new $tmp($db);
            $obj->socid = $socid;
            $obj->cssClassM = $res->extraCss;
            $obj->idChrono = $chrid;
            $obj->fetch($res->type_subvaleur);
            echo $obj->call_function_chronoModule($keyid, $chrid);
        }
    } else {
        //Construct Form
        $tag = preg_replace('/>$/', "", $res->htmlTag);
        $html = $suffixe = "";
        
        
        if ($res->valueIsChecked){
            $html .= "<input type='hidden' name='Chrono" . $res->id . "' value='forChecked'/>";
            $suffixe = "_check";
        }
        
        $html .= $tag;

//        if ($res->cssClass == 'datetimepicker') {
//            if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/', $res->value, $arr)) {
////                        $res->value = $arr[3] . '-' . $arr[2] . '-' . $arr[1] . " " . $arr[4] . ":" . $arr[5];
//            }
//        }

        if ($res->cssClass == 'datetimepicker') {
            $res->value = convertirDate($res->value, false, false, "/");
        }

        if ($res->extraCss . $res->cssClass . "x" != "x") {
            $html .= " class='" . $res->cssClass . " " . $res->extraCss . "' ";
        }
        if ($res->valueInValueField) {
            $html .= " value='" . $res->value . "' ";
        }
        if ($res->valueIsChecked) {
            $html .= ($res->value == 1 ? " CHECKED " : "");
        }
        $html .= " name='Chrono" . $res->id .$suffixe. "' ";
        $html .= " id='Chrono" . $res->id . "' ";
        $html.=">";
        if ($res->valueInTag) {
            $html .= $res->value;
        }
        if ($res->endNeeded == 1)
            $html .= $res->htmlEndTag;
        print $html;
    }
    print '</td>';
//    }
}

////function getValueForm($chrid, $keyid, $socid, $withEntete = true) {
//    global $db, $user;
//    $requete = "SELECT k.nom,
//                           k.id,
//                           v.`value`,
//                           t.nom as typeNom,
//                           t.hasSubValeur,
//                           t.subValeur_table,
//                           t.subValeur_idx,
//                           t.subValeur_text,
//                           t.htmlTag,
//                           t.htmlEndTag,
//                           t.endNeeded,
//                           t.cssClass,
//                           t.cssScript,
//                           t.jsCode,
//                           t.valueIsChecked,
//                           t.valueIsSelected,
//                           t.valueInTag,
//                           t.valueInValueField,
//                           t.sourceIsOption,
//                           k.type_subvaleur,
//                           k.extraCss,
//                           t.phpClass
//                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur AS t,
//                           " . MAIN_DB_PREFIX . "synopsischrono_key AS k
//                      LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_value AS v ON v.key_id = k.id AND v.chrono_refid = " . $chrid . "
//                     WHERE t.id = k.type_valeur
//                       AND k.id = " . $keyid;
////    print $requete;
//    $sql = $db->query($requete);
//    while ($res = $db->fetch_object($sql)) {
//            $res->value = stripslashes($res->value);
//        if ($withEntete) {
//            print '<tr><th class="ui-state-default ui-widget-header" nowrap class="ui-state-default">' . $res->nom;
//            print '    <td  class="ui-widget-content" colspan="3">';
//        }
//        if($res->phpClass == "lien")
//            print '<input type="hidden" class="chrid-keyid" value="'.$chrid."-".$keyid.'"/>';
//        if ($res->hasSubValeur == 1) {
//            if ($res->sourceIsOption) {
//                $tag = preg_replace('/>$/', "", $res->htmlTag);
//                $html = "";
//                $html .= $tag;
//                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
//                $tmp = $res->phpClass;
//                $obj = new $tmp($db);
//                $obj->socid = $socid;
//                $obj->cssClassM = $res->extraCss;
//                $obj->idChrono = $chrid;
//                $obj->fetch($res->type_subvaleur);
//                $obj->getValues();
//                echo $obj->formHtml;
//                if (isset($obj->tabVal[0])) {
//                    $res->value = $obj->tabVal[0];
//                    $res->valueIsSelected = true;
//                }
//                if ($res->value == 0 && stripos($obj->cssClassM, "myUser") !== false)
//                    $res->value = $user->id;
//                $extra_extraClass = "";
//                if ($obj->OptGroup . "x" != "x") {
//                    $extra_extraClass = " double noSelDeco ";
//                    print <<<EOF
//                      <script>
//jQuery(document).ready(function(){
//EOF;
//                    print "jQuery('#Chrono" . $res->id . "').jDoubleSelect({\n";
//                    print <<<EOF
//        text:'',
//        finish: function(){
//EOF;
//                    print " jQuery('#Chrono" . $res->id . "_jDS').each(function(){
//                            var select = $(this);
////                            $(select).combobox({
////                                selected: function(event, ui) {
////                                    select.find('option[value=\"'+$(this).val()+'\"]').attr('selected', 'selected');
////                                    select.change();
////                                }
////                            });
//      });
//        },
//        el1_change: function(){";
//                    print " /*jQuery('#Chrono" . $res->id . "_jDS_2').selectmenu({\n";
//                    print <<<EOF
//                style:'dropdown',
//                maxHeight: 300
//            });*/
//        },
//EOF;
//                    print "el2_dest: jQuery('#destChrono" . $res->id . "'),\n";
//                    print <<<EOF
//    });
//});
//
//                      </script>
//EOF;
//                }
//                if ($res->extraCss . $res->cssClass . $extra_extraClass . "x" != "x") {
//                    $html .= " class='" . $res->cssClass . " " . $res->extraCss . " " . $extra_extraClass . "' ";
//                }
//                if ($res->valueInValueField) {
//                    $html .= " value='" . $res->value . "' ";
//                }
//                if ($res->valueIsChecked) {
//                    $html .= ($res->value == 1 ? " CHECKED " : "");
//                }
//                $html .= " name='Chrono" . $res->id . "' ";
//                $html .= " id='Chrono" . $res->id . "' ";
//                $html.=">";
//                if ($res->valueInTag) {
//                    $html .= $res->value;
//                }
//                $remOpt = false;
//
//                $html .= "<option value=''>S&eacute;lectionner</option>";
//                if ($obj->OptGroup . "x" != "x") {
//                    $html = "<table><tr><td width=50%>" . $html;
//                    $html .= "<OPTGROUP label=' '>";
//                    $html .= "<OPTION value='0'></OPTION>";
//                    foreach ($obj->valuesGroupArr as $key => $val) {
//                        $val['label'] = str_replace(" ", "_", $val['label']);
//                        $val['label'] = str_replace("'", "_", $val['label']);
//                        $val['label'] = str_replace("/", "_", $val['label']);
//                        $html .= "<OPTGROUP label='" . $val['label'] . "'>";
//                        $html .= "<OPTION value='0'></OPTION>";
//                        foreach ($val['data'] as $key1 => $val1) {
//                            $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key1 ? "SELECTED" : "") . " value='" . $key1 . "'>" . $val1 . "</OPTION>";
//                        }
//                        $html .= "</OPTGROUP>";
//                    }
//                    $html .= "<td><div id='destChrono" . $res->id . "'></div>";
//                    $html .= "</table>";
//                    echo ajax_combobox("Chrono" . $res->id . "_jDS", "", 3);
//                } else {
//                    foreach ($obj->valuesArr as $key => $val) {
//                        $html .= "<OPTION " . ($res->valueIsSelected && $res->value == $key ? "SELECTED" : "") . " value='" . $key . "'>" . $val . "</OPTION>";
//                    }
//                    echo ajax_combobox("Chrono" . $res->id . "", "", 3);
//                }
//                if ($res->endNeeded == 1)
//                    $html .= $res->htmlEndTag;
//                print $html;
//            } else {
//                //Beta
//                if ($res->phpClass == 'fct')
//                    require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
//                $tmp = $res->phpClass;
//                $obj = new $tmp($db);
//                $obj->socid = $socid;
//                $obj->cssClassM = $res->extraCss;
//                $obj->idChrono = $chrid;
//                $obj->fetch($res->type_subvaleur);
//                echo $obj->call_function_chronoModule($keyid, $chrid);
//            }
//        } else {
//            //Construct Form
//            $tag = preg_replace('/>$/', "", $res->htmlTag);
//            $html = "";
//            $html .= $tag;
//
//            if ($res->cssClass == 'datetimepicker') {
//                if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/', $res->value, $arr)) {
////                        $res->value = $arr[3] . '-' . $arr[2] . '-' . $arr[1] . " " . $arr[4] . ":" . $arr[5];
//                }
//            }
//
//            if ($res->extraCss . $res->cssClass . "x" != "x") {
//                $html .= " class='" . $res->cssClass . " " . $res->extraCss . "' ";
//            }
//            if ($res->valueInValueField) {
//                $html .= " value='" . $res->value . "' ";
//            }
//            if ($res->valueIsChecked) {
//                $html .= ($res->value == 1 ? " CHECKED " : "");
//            }
//            $html .= " name='Chrono" . $res->id . "' ";
//            $html .= " id='Chrono" . $res->id . "' ";
//            $html.=">";
//            if ($res->valueInTag) {
//                $html .= $res->value;
//            }
//            if ($res->endNeeded == 1)
//                $html .= $res->htmlEndTag;
//            print $html;
//        }
//        print '</td>';
//    }
//}
