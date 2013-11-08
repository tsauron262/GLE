<?php

class htmlOld {

    public function listjqGrid_subGrid($arr) {
        $this->arrLigne = array();
        $this->listjqGrid_base($arr);
        $js = join(',' . "\n\t", $this->arrLigne);
        return $js;
    }

    public function listjqGrid($arr, $id = 'grid', $pager = true, $display = true, $pagerArr = array(view => false, add => false, edit => false, search => false, position => "left")) {
        $this->arrLigne = array();
        $js = 'jQuery(document).ready(function(){';
        $js .= 'var grid = jQuery("#' . $id . '").jqGrid({';

        $this->listjqGrid_base($arr);
        if ($pager) {
            $this->arrLigne[] = " pager:jQuery('#" . $id . "Pager')";
        }

        $js .= join(',' . "\n\t", $this->arrLigne);

        $js .= '})';
        if ($pager) {
            $js .= '.navGrid("#' . $id . 'Pager",';
            $js .= '       { ';
            $js .= '         view:' . ($pagerArr['view'] ? 'true' : 'false') . ',';
            $js .= '         add:' . ($pagerArr['add'] ? 'true' : 'false') . ',';
            $js .= '         del:' . ($pagerArr['del'] ? 'true' : 'false') . ',';
            $js .= '         edit:' . ($pagerArr['edit'] ? 'true' : 'false') . ',';
            $js .= '         search:' . ($pagerArr['search'] ? 'true' : 'false') . ',';
            $js .= '         position:"' . ($pagerArr['position'] . "x" != "x" ? $pagerArr['position'] : 'left') . '"';
            $js .= '       });';
        } else {
            $js .= ';';
        }

        $js .= '});';
        if ($display) {
            print $js;
        }
        return($js);
    }

    private function listjqGrid_colNames($arrCol) {
        $tmp = array();
        foreach ($arrCol as $key => $val) {
            if ($val['name'] . "x" != "x") {
                $titre = addslashes($val['name']);
                $tmp[] = "'" . $titre . "'";
            }
        }
        $this->arrLigne[] = "colNames:[" . join(',' . "\t\t\t\n", $tmp) . "]";
    }

    private function listjqGrid_colModel($arrCol) {
        $tmp = array();
        foreach ($arrCol as $key => $val) {
            $tmp1 = array();
            foreach ($val as $key1 => $val1) {
                if (is_bool($val1) && $val1)
                    $tmp1[] = "" . $key1 . ":true";
                else if (is_bool($val1) && !$val1)
                    $tmp1[] = "" . $key1 . ":false";
                else if (is_numeric($val1))
                    $tmp1[] = "" . $key1 . ":" . $val1;
                else if (preg_match('/^{/', $val1))
                    $tmp1[] = "" . $key1 . ":" . $val1;
                else
                    $tmp1[] = "" . $key1 . ":'" . $val1 . "'";
            }
            $tmp[] = "{" . join(',' . "\n\t\t\t\t\t", $tmp1) . "}";
        }
        $this->arrLigne[] = "colModel:[" . join(',' . "\n\t\t\t", $tmp) . "]";
    }

    private function listjqGrid_base($arr) {
        foreach ($arr as $key => $val) {
            if ($key == 'colModel') {
                $this->listjqGrid_colModel($val);
                $this->listjqGrid_colNames($val);
            } else {
                if (is_bool($val) && $val)
                    $this->arrLigne[] = "" . $key . ":true";
                else if (is_bool($val) && !$val)
                    $this->arrLigne[] = "" . $key . ":false";
                else if (is_numeric($val))
                    $this->arrLigne[] = "" . $key . ":" . $val;
                else if (preg_match('/^{/', $val))
                    $this->arrLigne[] = "" . $key . ":" . $val;
                else if (preg_match('/^\[/', $val))
                    $this->arrLigne[] = "" . $key . ":" . $val;
                else if (preg_match('/^function/', $val))
                    $this->arrLigne[] = "" . $key . ":" . $val;
                else
                    $this->arrLigne[] = "" . $key . ":'" . $val . "'";
            }
        }
    }

}

function tabChronoDetail($id, $nomDiv, $optionSearch = "") {
    global $db, $user;


    $htmlOld = new htmlOld();
//$html = new form($db);

    $js = "<script>";
    $js .= <<<EOF
   jQuery(document).ready(function(){
        jQuery('#typeChrono').change(function(){
            if (jQuery(this).find(':selected').val() > 0)
            {
                location.href='listDetail.php?id='+jQuery(this).find(':selected').val();
            } else {
                location.href='listDetail.php';
            }
        });
   });

EOF;

    if ($id > 0) {
        $chronoRef = new ChronoRef($db);
        $chronoRef->fetch($id);
        $js .= "var chronoTypeId = " . $id . ";";
        $js .= "var userId = " . $user->id . ";";

        $colModel = array();

        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key WHERE inDetList = 1 AND model_refid = " . $id;
        $sql = $db->query($requete);
        $colModelArr = array();
        $colModelArr[0] = array('name' => "id", "index" => "id", "width" => 0, 'hidden' => true, "search" => false, "align" => "left", "key" => true, "hidedlg" => true);
        $colModelArr[1] = array('name' => "ref", "index" => "ref", "width" => 80, "align" => "left");
        $colModelArr[2] = array('name' => "hasRev", "index" => "hasRev", 'hidden' => true, "search" => false, "hidedlg" => true);
        $i = 3;
        while ($res = $db->fetch_object($sql)) {
            $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur WHERE id = " . $res->type_valeur;
            $sql1 = $db->query($requete1);
            $res1 = $db->fetch_object($sql1);
            $align = "left";
            $colModelArr[$i] = array(
//            "name" => str_replace("'", "`", $res->nom),
                "name" => sanitize_string($res->nom),
                "index" => sanitize_string($res->nom),
                "width" => 130,
                'hidden' => false,
                "search" => true,
                "align" => $align,
                "key" => false,
                "hidedlg" => false
            );
//var_dump($sql);
            if ($res1->cssClass == "datepicker") {
                $colModelArr[$i]['sorttype'] = "date";
                $colModelArr[$i]['formatter'] = "date";
                $colModelArr[$i]['formatoptions'] = '{srcformat:"Y-m-d",newformat:"d/m/Y"}';
                $colModelArr[$i]['align'] = 'center';
                $colModelArr[$i]['editable'] = false;
                $colModelArr[$i]['searchoptions'] = '{    dataInit:function(el){  jQuery(el).datepicker({ showTime: false, }); jQuery("#ui-datepicker-div").addClass("promoteZ"); jQuery(el).datepicker("change", {dateFormat: "dd/mm/yyyy", firstDay:1 }).attr("readonly","readonly");}, sopt:["eq","ne","le","lt","ge","gt"], }';
            } else if ($res1->cssClass == "datetimepicker") {
                $colModelArr[$i]['sorttype'] = "date";
                $colModelArr[$i]['formatter'] = "date";
                $colModelArr[$i]['formatoptions'] = '{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"}';
                $colModelArr[$i]['editable'] = false;
                $colModelArr[$i]['align'] = 'center';
                $colModelArr[$i]['searchoptions'] = '{
                                                    dataInit:function(el){
                                                        jQuery(el).datepicker({
                                                            showTime: true,
                                                        });
                                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                    },
                                                    sopt:["eq","ne","le","lt","ge","gt"],
                                                }';
            } else {
                $colModelArr[$i]['editable'] = false;
                $colModelArr[$i]['searchoptions'] = '{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]}';
                $colModelArr[$i]['formoptions'] = '{ elmprefix:"*  " }';
            }

            $i++;
        }
        if ($chronoRef->hasSociete) {
            $i++;
            $colModelArr[$i] = array('name' => "Société", "index" => "soc", "width" => 130, "align" => "left", "search" => true, "sortable"=>false);
        }
        if ($chronoRef->hasPropal){
            $i++;
            $colModelArr[$i] = array('name' => "Propal", "index" => "propal", "width" => 100, "align" => "left", "search" => true, "sortable"=>false);
        }
        if ($chronoRef->hasProjet){
            $i++;
            $colModelArr[$i] = array('name' => "Projet", "index" => "fkprojet", "width" => 130, "align" => "left", "search" => true, "sortable"=>false);
        }
        if ($chronoRef->hasStatut){
            $i++;
        $colModelArr[$i] = array('name' => "Statut", "index" => "fk_statut", "width" => 80, "align" => "right", "stype" => 'select', 'searchoptionspp' => "{sopt:['eq','ne']}", 'searchoptions' => "{value: statutRess}", 'formoptions' => '{ elmprefix:"*  " }');
        }
        if ($chronoRef->hasFile){
            $i++;
        $colModelArr[$i] = array('name' => "NbDoc", "index" => "nb_doc", "width" => 60, "align" => "right", "search" => false, "sortable"=>false);
        }


        $arr2 = array(
            url => DOL_URL_ROOT . "/Synopsis_Chrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . "&withRev=1&chrono_refid='+row_id+'",
            datatype => "json",
            height => "100%",
            rowNum => 20,
            width => 1087,
            sortname => 'id',
            sortorder => "desc",
            colModel => $colModelArr
        );
        $subGrid = $htmlOld->listjqGrid_subGrid($arr2);

        $arr = array(
            url => DOL_URL_ROOT . "/Synopsis_Chrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . $optionSearch,
            caption => '<span style="padding:4px; font-size: 16px; ">Chrono ' . addslashes($chronoRef->titre) . "</span>",
            sortname => 'chrono_id',
            sortorder => "desc",
            datatype => 'json',
            rowNum => 30,
            rowList => "[30,50,100]",
            beforeRequest => "function(){
                jQuery('#" . $nomDiv . "').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            }",
            gridComplete => "function(){
                jQuery('.butAction').mouseover(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover'); });
                jQuery('.butAction').mouseout(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default'); });
                jQuery('.butAction-rev').mouseover(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default'); jQuery(this).find('.ui-icon').css('background-image','url(\"" . $css . "/images/ui-icons_0073ea_256x240.png\")'); });
                jQuery('.butAction-rev').mouseout(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover'); jQuery(this).find('.ui-icon').css('background-image','url(\"" . $css . "/images/ui-icons_ffffff_256x240.png\")'); });
                jQuery('.jqgrow').each(function(){
                    var hasRev = jQuery(this).find('.hasRev').text();
                    if(hasRev != 1)
                    {
                        jQuery(this).find('.ui-sgcollapsed .ui-icon').parent().remove();
                        jQuery(this).find('.ui-sgcollapsed').removeClass('ui-sgcollapsed').removeClass('sgcollapsed');
                    }
                });
            }",
            mtype => "POST",
            viewrecords => true,
            width => "1300",
            height => 470,
            colModel => $colModelArr,
            subGrid => true,
            subGridUrl => DOL_URL_ROOT . "Synopsis_Chrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . "&withRev=1",
            subGridRowExpanded => 'function(subgrid_id, row_id) {
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                  jQuery("#"+subgrid_id).html("<table id="+subgrid_table_id+" class=\'scroll\'></table>");
                  jQuery("#"+subgrid_table_id).jqGrid({
            ' . $subGrid . ' });
            }',
        );


        $js .= $htmlOld->listjqGrid($arr, $nomDiv, true, false, array(view => false, add => false, edit => false, search => false, position => "left"));

        $js .= "  setTimeout(function(){   jQuery('#" . $nomDiv . "').filterToolbar('');},500);";
    }


    $requete = "SELECT DISTINCT fk_statut FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono ORDER BY fk_statut ASC";
    $sql = $db->query($requete);
    $js .= 'var statutRess = "';
    $js .= "-1:" . preg_replace("/'/", "\\'", "Sélection ->") . ";";

    while ($res = $db->fetch_object($sql)) {
        $fakeChrono = new Chrono($db);
        $fakeChrono->statut = $res->fk_statut;

        $js .= $res->fk_statut . ":" . html_entity_decode(str_replace("&eacute;", "e", $fakeChrono->getLibStatut(0))) . ";";
    }

    $js = preg_replace('/;$/', '', $js);
    $js .= '";';

    $js .= "</script>";


    if ($id > 0) {
        $jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery";
        $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />';
        $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />';
        $js .= ' <script src="' . $jspath . '/jqGrid-3.5/src/i18n/grid.locale-fr.js" type="text/javascript"></script>';
        $js .= ' <script src="' . $jspath . '/jqGrid-3.5/jquery.jqGrid.min.js" type="text/javascript"></script>';
    }

    return $js;
}

?>
