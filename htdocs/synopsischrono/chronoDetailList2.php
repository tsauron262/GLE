<?php

class htmlOld {

    public function listjqGrid_subGrid($arr) {
        $this->arrLigne = array();
        $this->listjqGrid_base($arr);
        $js = join(',' . "\n\t", $this->arrLigne);
        return $js;
    }

    public function listjqGrid($arr, $id = 'grid', $pager = true, $display = true, $pagerArr = array(view => false, add => false, edit => false, search => true, position => "left")) {
        $this->arrLigne = array();
        $js = 'jQuery(document).ready(function(){';
        $js .= '
            
var grid = jQuery("#' . $id . '");
    initDateSearch = function (elem) {
                    setTimeout(function () {
                        $(elem).datepicker({
                            dateFormat: \'dd-M-yy\',
                            autoSize: true,
                            //showOn: \'button\', // it dosn\'t work in searching dialog
                            changeYear: true,
                            changeMonth: true,
                            showButtonPanel: true,
                            showWeek: true,
                            onSelect: function () {
                                if (this.id.substr(0, 3) === "gs_") {
                                    setTimeout(function () {
                                        grid[0].triggerToolbar();
                                    }, 50);
                                } else {
                                    // to refresh the filter
                                    $(this).trigger(\'change\');
                                }
                            }
                        });
                    }, 100);
                },
                numberSearchOptions = [\'eq\', \'ne\', \'lt\', \'le\', \'gt\', \'ge\', \'nu\', \'nn\', \'in\', \'ni\'],
                numberTemplate = {formatter: \'number\', align: \'right\', sorttype: \'number\',
                    searchoptions: { sopt: numberSearchOptions }},
                myDefaultSearch = \'cn\',
                getColumnIndex = function (grid, columnIndex) {
                    var cm = grid.jqGrid(\'getGridParam\', \'colModel\'), i, l = cm.length;
                    for (i = 0; i < l; i++) {
                        if ((cm[i].index || cm[i].name) === columnIndex) {
                            return i; // return the colModel index
                        }
                    }
                    return -1;
                },
                refreshSerchingToolbar = function (grid, myDefaultSearch) {
                    var postData = grid.jqGrid(\'getGridParam\', \'postData\'), filters, i, l,
                        rules, rule, iCol, cm = grid.jqGrid(\'getGridParam\', \'colModel\'),
                        cmi, control, tagName;

                    for (i = 0, l = cm.length; i < l; i++) {
                        control = $("#gs_" + $.jgrid.jqID(cm[i].name));
                        if (control.length > 0) {
                            tagName = control[0].tagName.toUpperCase();
                            if (tagName === "SELECT") { // && cmi.stype === "select"
                                control.find("option[value=\'\']")
                                    .attr(\'selected\', \'selected\');
                            } else if (tagName === "INPUT") {
                                control.val(\'\');
                            }
                        }
                    }

                    if (typeof (postData.filters) === "string" &&
                            typeof (grid[0].ftoolbar) === "boolean" && grid[0].ftoolbar) {

                        filters = $.parseJSON(postData.filters);
                        if (filters && filters.groupOp === "AND" && typeof (filters.groups) === "undefined") {
                            // only in case of advance searching without grouping we import filters in the
                            // searching toolbar
                            rules = filters.rules;
                            for (i = 0, l = rules.length; i < l; i++) {
                                rule = rules[i];
                                iCol = getColumnIndex(grid, rule.field);
                                if (iCol >= 0) {
                                    cmi = cm[iCol];
                                    control = $("#gs_" + $.jgrid.jqID(cmi.name));
                                    if (control.length > 0 &&
                                            (((typeof (cmi.searchoptions) === "undefined" ||
                                            typeof (cmi.searchoptions.sopt) === "undefined")
                                            && rule.op === myDefaultSearch) ||
                                              (typeof (cmi.searchoptions) === "object" &&
                                                  $.isArray(cmi.searchoptions.sopt) &&
                                                  cmi.searchoptions.sopt.length > 0 &&
                                                  cmi.searchoptions.sopt[0] === rule.op))) {
                                        tagName = control[0].tagName.toUpperCase();
                                        if (tagName === "SELECT") { // && cmi.stype === "select"
                                            control.find("option[value=\'" + $.jgrid.jqID(rule.data) + "\']")
                                                .attr(\'selected\', \'selected\');
                                        } else if (tagName === "INPUT") {
                                            control.val(rule.data);
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
                cm = [
                    //{name: \'id\', index: \'id\', width: 70, align: \'center\', sorttype: \'int\', formatter: \'int\'},
                    {name: \'invdate\', index: \'invdate\', width: 75, align: \'center\', sorttype: \'date\',
                        formatter: \'date\', formatoptions: {newformat: \'d-M-Y\'}, datefmt: \'d-M-Y\',
                        searchoptions: {
                            sopt: [\'eq\', \'ne\'],
                            dataInit: initDateSearch
                        }},
                    {name: \'name\', index: \'name\', width: 65},
                    {name: \'amount\', index: \'amount\', width: 75, template: numberTemplate},
                    {name: \'tax\', index: \'tax\', width: 52, template: numberTemplate},
                    {name: \'total\', index: \'total\', width: 60, search: false, template: numberTemplate},
                    {name: \'closed\', index: \'closed\', width: 67, align: \'center\', formatter: \'checkbox\',
                        edittype: \'checkbox\', editoptions: {value: \'Yes:No\', defaultValue: \'Yes\'},
                        stype: \'select\', searchoptions: { sopt: [\'eq\', \'ne\'], value: \':Any;true:Yes;false:No\' }},
                    {name: \'ship_via\', index: \'ship_via\', width: 95, align: \'center\', formatter: \'select\',
                        edittype: \'select\', editoptions: {value: \'FE:FedEx;TN:TNT;IN:Intim\', defaultValue: \'Intime\'},
                        stype: \'select\', searchoptions: { sopt: [\'eq\', \'ne\'], value: \':Any;FE:FedEx;TN:TNT;IN:Intim\'}},
                    {name: \'note\', index: \'note\', width: 60, sortable: false}
                ],
                saveObjectInLocalStorage = function (storageItemName, object) {
                    if (typeof window.localStorage !== \'undefined\') {
                        window.localStorage.setItem(storageItemName, JSON.stringify(object));
                    }
                },
                removeObjectFromLocalStorage = function (storageItemName) {
                    if (typeof window.localStorage !== \'undefined\') {
                        window.localStorage.removeItem(storageItemName);
                    }
                },
                getObjectFromLocalStorage = function (storageItemName) {
                    if (typeof window.localStorage !== \'undefined\') {
                        return JSON.parse(window.localStorage.getItem(storageItemName));
                    }
                },
                myColumnStateName = \'ColumnChooserAndLocalStorage2single.colState\',
                idsOfSelectedRows = [],
                saveColumnState = function (perm) {
                    var colModel = this.jqGrid(\'getGridParam\', \'colModel\'), i, l = colModel.length, colItem, cmName,
                        postData = this.jqGrid(\'getGridParam\', \'postData\'),
                        columnsState = {
                            search: this.jqGrid(\'getGridParam\', \'search\'),
                            page: this.jqGrid(\'getGridParam\', \'page\'),
                            sortname: this.jqGrid(\'getGridParam\', \'sortname\'),
                            sortorder: this.jqGrid(\'getGridParam\', \'sortorder\'),
                            permutation: perm,
                            selectedRows: idsOfSelectedRows,
                            colStates: {}
                        },
                        colStates = columnsState.colStates;

                    if (typeof (postData.filters) !== \'undefined\') {
                        columnsState.filters = postData.filters;
                    }

                    for (i = 0; i < l; i++) {
                        colItem = colModel[i];
                        cmName = colItem.name;
                        if (cmName !== \'rn\' && cmName !== \'cb\' && cmName !== \'subgrid\') {
                            colStates[cmName] = {
                                width: colItem.width,
                                hidden: colItem.hidden
                            };
                        }
                    }
                    saveObjectInLocalStorage(myColumnStateName, columnsState);
                },
                myColumnsState,
                isColState,
                restoreColumnState = function (colModel) {
                    var colItem, i, l = colModel.length, colStates, cmName,
                        columnsState = getObjectFromLocalStorage(myColumnStateName);

                    if (columnsState) {
                        colStates = columnsState.colStates;
                        for (i = 0; i < l; i++) {
                            colItem = colModel[i];
                            cmName = colItem.name;
                            if (cmName !== \'rn\' && cmName !== \'cb\' && cmName !== \'subgrid\') {
                                colModel[i] = $.extend(true, {}, colModel[i], colStates[cmName]);
                            }
                        }
                    }
                    return columnsState;
                },
                updateIdsOfSelectedRows = function (id, isSelected) {
                    var index = $.inArray(id, idsOfSelectedRows);
                    if (!isSelected && index >= 0) {
                        idsOfSelectedRows.splice(index, 1); // remove id from the list
                    } else if (index < 0) {
                        idsOfSelectedRows.push(id);
                    }
                },
                firstLoad = true;
alert("ici");
            myColumnsState = restoreColumnState(cm);
            isColState = typeof (myColumnsState) !== \'undefined\' && myColumnsState !== null;
            idsOfSelectedRows = isColState && typeof (myColumnsState.selectedRows) !== "undefined" ? myColumnsState.selectedRows : [];

            grid.jqGrid({';

        $this->listjqGrid_base($arr);
        if ($pager) {
            $this->arrLigne[] = " pager:jQuery('#" . $id . "Pager')";
        }

        $js .= join(',' . "\n\t", $this->arrLigne);

        $js .= ',
               rownumbers: true,
                ignoreCase: true,
                //multiselect: true,
                //shrinkToFit: false,
                //viewrecords: true,
                caption: \'The usage of localStorage to save jqGrid preferences\',
                height: \'auto\',
                onSelectRow: function (id, isSelected) {
                    updateIdsOfSelectedRows(id, isSelected);
                    saveColumnState.call(grid, grid[0].p.remapColumns);
                },
                onSelectAll: function (aRowids, isSelected) {
                    var i, count, id;
                    for (i = 0, count = aRowids.length; i < count; i++) {
                        id = aRowids[i];
                        updateIdsOfSelectedRows(id, isSelected);
                    }
                    saveColumnState.call(grid, grid[0].p.remapColumns);
                },
                loadComplete: function () {
                    var $this = $(this), i, count;

                    if (firstLoad) {
                        firstLoad = false;
                        if (isColState) {
                            $this.jqGrid("remapColumns", myColumnsState.permutation, true);
                        }
                        if (typeof (this.ftoolbar) !== "boolean" || !this.ftoolbar) {
                            // create toolbar if needed
                            $this.jqGrid(\'filterToolbar\',
                                {stringResult: true, searchOnEnter: true, defaultSearch: myDefaultSearch});
                        }
                    }
                    refreshSerchingToolbar($this, myDefaultSearch);
                    for (i = 0, count = idsOfSelectedRows.length; i < count; i++) {
                        $this.jqGrid(\'setSelection\', idsOfSelectedRows[i], false);
                    }
                    saveColumnState.call($this, this.p.remapColumns);
                },
                resizeStop: function () {
                    saveColumnState.call(grid, grid[0].p.remapColumns);
                }
            })
';
        if ($pager) {
            $js .= '.navGrid("#' . $id . 'Pager",';
            $js .= '       { ';
            $js .= '         view:' . ($pagerArr['view'] ? 'true' : 'false') . ',';
            $js .= '         add:' . ($pagerArr['add'] ? 'true' : 'false') . ',';
            $js .= '         del:' . ($pagerArr['del'] ? 'true' : 'false') . ',';
            $js .= '         edit:' . ($pagerArr['edit'] ? 'true' : 'false') . ',';
            $js .= '         search:' . ($pagerArr['search'] ? 'true' : 'false') . ',';
            $js .= '         position:"' . ($pagerArr['position'] . "x" != "x" ? $pagerArr['position'] : 'left') . '"';
            $js .= '       });'
                    . ''
          . "jQuery('#" . $id . "').jqGrid('navButtonAdd', '#" . $id . "Pager', {
                caption: '',
                buttonicon: \"ui-icon-calculator\",
                title: \"Choose columns\",
                onClickButton: function () {
                    $(this).jqGrid('columnChooser', {
                        done: function (perm) {
                            if (perm) {
                                this.jqGrid(\"remapColumns\", perm, true);
                                saveColumnState.call(this, perm);
                            }
                        }
                    });
                }
            });";
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
    
    $width = 1300;


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

        $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE inDetList = 1 AND model_refid = " . $id;
        $sql = $db->query($requete);
        $colModelArr = array();
        $colModelArr[0] = array('name' => "id", "index" => "id", "width" => 0, 'hidden' => true, "search" => false, "align" => "left", "key" => true, "hidedlg" => true);
        $colModelArr[1] = array('name' => "Réf", "index" => "ref", "width" => 150, "align" => "left");
        $colModelArr[2] = array('name' => "hasRev", "index" => "hasRev", 'hidden' => true, "search" => false, "hidedlg" => true);
        $i = 3;
        if ($chronoRef->hasDescription) {
            $colModelArr[$i] = array('name' => $chronoRef->nomDescription, "index" => "description", "width" => 130, "align" => "left", "search" => true, "sortable"=>false);
            $i++;
        }
        while ($res = $db->fetch_object($sql)) {
            $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur WHERE id = " . $res->type_valeur;
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
            if($res1->nom == "Liste"){
//                print_r($res1);
                $sql2 = $db->query("SELECT label as val, valeur as id FROM ".MAIN_DB_PREFIX."Synopsis_Process_form_list_members WHERE list_refid = ".$res->type_subvaleur);
                while($result= $db->fetch_object($sql2))
                        $tabVal[] = "".$result->val . ":".$result->val."";
//                die(implode("','", $tabVal));
                $colModelArr[$i]['searchoptions'] = '{ value: ": ;'.implode(";", $tabVal).'" }';
                $colModelArr[$i]['stype'] = 'select';
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
            url => DOL_URL_ROOT . "/synopsischrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . "&withRev=1&chrono_refid='+row_id+'",
            datatype => "json",
            height => "100%",
            rowNum => 20,
            width => ($width-40),
            sortname => 'id',
            sortorder => "desc",
            colModel => $colModelArr
        );
        $subGrid = $htmlOld->listjqGrid_subGrid($arr2);

        $arr = array(
            url => DOL_URL_ROOT . "/synopsischrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . $optionSearch,
            caption => '<span style="padding:4px; font-size: 16px; ">Chrono ' . addslashes($chronoRef->titre) . "</span>",
            sortname => 'chrono_id',
            sortorder => "desc",
            datatype => 'json',
            rowNum => 25,
            rowList => "[25,50,100]",
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
            width => $width,
            height => 575,
            colModel => $colModelArr,
            subGrid => true,
            subGridUrl => DOL_URL_ROOT . "synopsischrono/ajax/listChronoDetail_json.php?userId=" . $user->id . "&id=" . $id . "&withRev=1",
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


    $requete = "SELECT DISTINCT fk_statut FROM " . MAIN_DB_PREFIX . "synopsischrono ORDER BY fk_statut ASC";
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
        $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . DOL_URL_ROOT . '/includes/jquery/plugins/multiselect/css/ui.multiselect.css" />';
        $js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>';
        $js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>';
        $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/multiselect/js/ui.multiselect.js" type="text/javascript"></script>';
         $js .= ' <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/json2.js"></script>';
    }

    return $js;
}
?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>http://stackoverflow.com/q/8545953/315935</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/redmond/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.0/css/ui.jqgrid.css" />
    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.0/plugin/ui.multiselect.css" />
    <style type="text/css">
        html, body { font-size: 75%; }
    </style>
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.0/plugin/ui.multiselect.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.0/js/i18n/grid.locale-en.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.0/js/jquery.jqGrid.src-multiselect.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/json2.js"></script>

    <script type="text/javascript">
    //<![CDATA[
        /*global $ */
        /*jslint devel: true, browser: true, plusplus: true */
        $.jgrid.formatter.integer.thousandsSeparator = ',';
        $.jgrid.formatter.number.thousandsSeparator = ',';
        $.jgrid.formatter.currency.thousandsSeparator = ',';
        var statutRess = '';
        $(document).ready(function () {
            'use strict';
            var myData = [
                    {id: "1",  invdate: "2007-10-01", name: "test",   note: "note",   amount: "200.00", tax: "10.00", closed: true,  ship_via: "TN", total: "210.00"},
                    {id: "2",  invdate: "2007-10-02", name: "test2",  note: "note2",  amount: "300.00", tax: "20.00", closed: false, ship_via: "FE", total: "320.00"},
                    {id: "3",  invdate: "2011-07-30", name: "test3",  note: "note3",  amount: "400.00", tax: "30.00", closed: true,  ship_via: "FE", total: "430.00"},
                    {id: "4",  invdate: "2007-10-04", name: "test4",  note: "note4",  amount: "200.00", tax: "10.00", closed: true,  ship_via: "TN", total: "210.00"},
                    {id: "5",  invdate: "2007-10-31", name: "test5",  note: "note5",  amount: "300.00", tax: "20.00", closed: false, ship_via: "FE", total: "320.00"},
                    {id: "6",  invdate: "2007-09-06", name: "test6",  note: "note6",  amount: "400.00", tax: "30.00", closed: false, ship_via: "FE", total: "430.00"},
                    {id: "7",  invdate: "2011-07-30", name: "test7",  note: "note7",  amount: "200.00", tax: "10.00", closed: true,  ship_via: "TN", total: "210.00"},
                    {id: "8",  invdate: "2007-10-03", name: "test8",  note: "note8",  amount: "300.00", tax: "20.00", closed: true,  ship_via: "FE", total: "320.00"},
                    {id: "9",  invdate: "2007-09-01", name: "test9",  note: "note9",  amount: "400.00", tax: "30.00", closed: false, ship_via: "TN", total: "430.00"},
                    {id: "10", invdate: "2007-09-08", name: "test10", note: "note10", amount: "500.00", tax: "30.00", closed: true,  ship_via: "TN", total: "530.00"},
                    {id: "11", invdate: "2007-09-08", name: "test11", note: "note11", amount: "500.00", tax: "30.00", closed: false, ship_via: "FE", total: "530.00"},
                    {id: "12", invdate: "2007-09-10", name: "test12", note: "note12", amount: "500.00", tax: "30.00", closed: false, ship_via: "FE", total: "530.00"}
                ],
                $grid = $("#list"),
                initDateSearch = function (elem) {
                    setTimeout(function () {
                        $(elem).datepicker({
                            dateFormat: 'dd-M-yy',
                            autoSize: true,
                            //showOn: 'button', // it dosn't work in searching dialog
                            changeYear: true,
                            changeMonth: true,
                            showButtonPanel: true,
                            showWeek: true,
                            onSelect: function () {
                                if (this.id.substr(0, 3) === "gs_") {
                                    setTimeout(function () {
                                        $grid[0].triggerToolbar();
                                    }, 50);
                                } else {
                                    // to refresh the filter
                                    $(this).trigger('change');
                                }
                            }
                        });
                    }, 100);
                },
                numberSearchOptions = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'nu', 'nn', 'in', 'ni'],
                numberTemplate = {formatter: 'number', align: 'right', sorttype: 'number',
                    searchoptions: { sopt: numberSearchOptions }},
                myDefaultSearch = 'cn',
                getColumnIndex = function (grid, columnIndex) {
                    var cm = grid.jqGrid('getGridParam', 'colModel'), i, l = cm.length;
                    for (i = 0; i < l; i++) {
                        if ((cm[i].index || cm[i].name) === columnIndex) {
                            return i; // return the colModel index
                        }
                    }
                    return -1;
                },
                refreshSerchingToolbar = function ($grid, myDefaultSearch) {
                    var postData = $grid.jqGrid('getGridParam', 'postData'), filters, i, l,
                        rules, rule, iCol, cm = $grid.jqGrid('getGridParam', 'colModel'),
                        cmi, control, tagName;

                    for (i = 0, l = cm.length; i < l; i++) {
                        control = $("#gs_" + $.jgrid.jqID(cm[i].name));
                        if (control.length > 0) {
                            tagName = control[0].tagName.toUpperCase();
                            if (tagName === "SELECT") { // && cmi.stype === "select"
                                control.find("option[value='']")
                                    .attr('selected', 'selected');
                            } else if (tagName === "INPUT") {
                                control.val('');
                            }
                        }
                    }

                    if (typeof (postData.filters) === "string" &&
                            typeof ($grid[0].ftoolbar) === "boolean" && $grid[0].ftoolbar) {

                        filters = $.parseJSON(postData.filters);
                        if (filters && filters.groupOp === "AND" && typeof (filters.groups) === "undefined") {
                            // only in case of advance searching without grouping we import filters in the
                            // searching toolbar
                            rules = filters.rules;
                            for (i = 0, l = rules.length; i < l; i++) {
                                rule = rules[i];
                                iCol = getColumnIndex($grid, rule.field);
                                if (iCol >= 0) {
                                    cmi = cm[iCol];
                                    control = $("#gs_" + $.jgrid.jqID(cmi.name));
                                    if (control.length > 0 &&
                                            (((typeof (cmi.searchoptions) === "undefined" ||
                                            typeof (cmi.searchoptions.sopt) === "undefined")
                                            && rule.op === myDefaultSearch) ||
                                              (typeof (cmi.searchoptions) === "object" &&
                                                  $.isArray(cmi.searchoptions.sopt) &&
                                                  cmi.searchoptions.sopt.length > 0 &&
                                                  cmi.searchoptions.sopt[0] === rule.op))) {
                                        tagName = control[0].tagName.toUpperCase();
                                        if (tagName === "SELECT") { // && cmi.stype === "select"
                                            control.find("option[value='" + $.jgrid.jqID(rule.data) + "']")
                                                .attr('selected', 'selected');
                                        } else if (tagName === "INPUT") {
                                            control.val(rule.data);
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
                cm = [
                    //{name: 'id', index: 'id', width: 70, align: 'center', sorttype: 'int', formatter: 'int'},
                    {name: 'invdate', index: 'invdate', width: 75, align: 'center', sorttype: 'date',
                        formatter: 'date', formatoptions: {newformat: 'd-M-Y'}, datefmt: 'd-M-Y',
                        searchoptions: {
                            sopt: ['eq', 'ne'],
                            dataInit: initDateSearch
                        }},
                    {name: 'name', index: 'name', width: 65},
                    {name: 'amount', index: 'amount', width: 75, template: numberTemplate},
                    {name: 'tax', index: 'tax', width: 52, template: numberTemplate},
                    {name: 'total', index: 'total', width: 60, search: false, template: numberTemplate},
                    {name: 'closed', index: 'closed', width: 67, align: 'center', formatter: 'checkbox',
                        edittype: 'checkbox', editoptions: {value: 'Yes:No', defaultValue: 'Yes'},
                        stype: 'select', searchoptions: { sopt: ['eq', 'ne'], value: ':Any;true:Yes;false:No' }},
                    {name: 'ship_via', index: 'ship_via', width: 95, align: 'center', formatter: 'select',
                        edittype: 'select', editoptions: {value: 'FE:FedEx;TN:TNT;IN:Intim', defaultValue: 'Intime'},
                        stype: 'select', searchoptions: { sopt: ['eq', 'ne'], value: ':Any;FE:FedEx;TN:TNT;IN:Intim'}},
                    {name: 'note', index: 'note', width: 60, sortable: false}
                ],
                saveObjectInLocalStorage = function (storageItemName, object) {
                    if (typeof window.localStorage !== 'undefined') {
                        window.localStorage.setItem(storageItemName, JSON.stringify(object));
                    }
                },
                removeObjectFromLocalStorage = function (storageItemName) {
                    if (typeof window.localStorage !== 'undefined') {
                        window.localStorage.removeItem(storageItemName);
                    }
                },
                getObjectFromLocalStorage = function (storageItemName) {
                    if (typeof window.localStorage !== 'undefined') {
                        return JSON.parse(window.localStorage.getItem(storageItemName));
                    }
                },
                myColumnStateName = 'ColumnChooserAndLocalStorage2single.colState',
                idsOfSelectedRows = [],
                saveColumnState = function (perm) {
                    var colModel = this.jqGrid('getGridParam', 'colModel'), i, l = colModel.length, colItem, cmName,
                        postData = this.jqGrid('getGridParam', 'postData'),
                        columnsState = {
                            search: this.jqGrid('getGridParam', 'search'),
                            page: this.jqGrid('getGridParam', 'page'),
                            sortname: this.jqGrid('getGridParam', 'sortname'),
                            sortorder: this.jqGrid('getGridParam', 'sortorder'),
                            permutation: perm,
                            selectedRows: idsOfSelectedRows,
                            colStates: {}
                        },
                        colStates = columnsState.colStates;

                    if (typeof (postData.filters) !== 'undefined') {
                        columnsState.filters = postData.filters;
                    }

                    for (i = 0; i < l; i++) {
                        colItem = colModel[i];
                        cmName = colItem.name;
                        if (cmName !== 'rn' && cmName !== 'cb' && cmName !== 'subgrid') {
                            colStates[cmName] = {
                                width: colItem.width,
                                hidden: colItem.hidden
                            };
                        }
                    }
                    saveObjectInLocalStorage(myColumnStateName, columnsState);
                },
                myColumnsState,
                isColState,
                restoreColumnState = function (colModel) {
                    var colItem, i, l = colModel.length, colStates, cmName,
                        columnsState = getObjectFromLocalStorage(myColumnStateName);

                    if (columnsState) {
                        colStates = columnsState.colStates;
                        for (i = 0; i < l; i++) {
                            colItem = colModel[i];
                            cmName = colItem.name;
                            if (cmName !== 'rn' && cmName !== 'cb' && cmName !== 'subgrid') {
                                colModel[i] = $.extend(true, {}, colModel[i], colStates[cmName]);
                            }
                        }
                    }
                    return columnsState;
                },
                updateIdsOfSelectedRows = function (id, isSelected) {
                    var index = $.inArray(id, idsOfSelectedRows);
                    if (!isSelected && index >= 0) {
                        idsOfSelectedRows.splice(index, 1); // remove id from the list
                    } else if (index < 0) {
                        idsOfSelectedRows.push(id);
                    }
                },
                firstLoad = true;

            myColumnsState = restoreColumnState(cm);
            isColState = typeof (myColumnsState) !== 'undefined' && myColumnsState !== null;
            idsOfSelectedRows = isColState && typeof (myColumnsState.selectedRows) !== "undefined" ? myColumnsState.selectedRows : [];

            $grid.jqGrid({url:'/gle_dev/synopsischrono/ajax/listChronoDetail_json.php?userId=1&id=100&_search2=true&fk_projet=2',
	caption:'<span style="padding:4px; font-size: 16px; ">Chrono Appel</span>',
	sortname:'chrono_id',
	sortorder:'desc',
	datatype:'json',
	rowNum:25,
	rowList:[25,50,100],
	beforeRequest:function(){
                jQuery('#gridChronoDet100').find('.ui-jqgrid-titlebar').addClass('ui-state-default');
            },
	gridComplete:function(){
                jQuery('.butAction').mouseover(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover'); });
                jQuery('.butAction').mouseout(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default'); });
                jQuery('.butAction-rev').mouseover(function(){ jQuery(this).removeClass('ui-state-hover'); jQuery(this).addClass('ui-state-default'); jQuery(this).find('.ui-icon').css('background-image','url("/images/ui-icons_0073ea_256x240.png")'); });
                jQuery('.butAction-rev').mouseout(function(){ jQuery(this).removeClass('ui-state-default'); jQuery(this).addClass('ui-state-hover'); jQuery(this).find('.ui-icon').css('background-image','url("/images/ui-icons_ffffff_256x240.png")'); });
                jQuery('.jqgrow').each(function(){
                    var hasRev = jQuery(this).find('.hasRev').text();
                    if(hasRev != 1)
                    {
                        jQuery(this).find('.ui-sgcollapsed .ui-icon').parent().remove();
                        jQuery(this).find('.ui-sgcollapsed').removeClass('ui-sgcollapsed').removeClass('sgcollapsed');
                    }
                });
            },
	mtype:'POST',
	viewrecords:true,
	width:1300,
	height:575,
	colModel:[{name:'id',
					index:'id',
					width:0,
					hidden:true,
					search:false,
					align:'left',
					key:true,
					hidedlg:true},
			{name:'Réf',
					index:'ref',
					width:150,
					align:'left'},
			{name:'hasRev',
					index:'hasRev',
					hidden:true,
					search:false,
					hidedlg:true},
			{name:'Objet',
					index:'description',
					width:130,
					align:'left',
					search:true,
					sortable:false},
			{name:'Note',
					index:'Note',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]},
					formoptions:{ elmprefix:"*  " }},
			{name:'Date___Heure',
					index:'Date___Heure',
					width:130,
					hidden:false,
					search:true,
					align:'center',
					key:false,
					hidedlg:false,
					sorttype:'date',
					formatter:'date',
					formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
					editable:false,
					searchoptions:{
                                                    dataInit:function(el){
                                                        jQuery(el).datepicker({
                                                            showTime: true,
                                                        });
                                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                    },
                                                    sopt:["eq","ne","le","lt","ge","gt"],
                                                }},
			{name:'Contrat',
					index:'Contrat',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]},
					formoptions:{ elmprefix:"*  " }},
			{name:'Produit_Client',
					index:'Produit_Client',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]},
					formoptions:{ elmprefix:"*  " }},
			{name:'Etat',
					index:'Etat',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{ value: ": ;En cours:En cours;Trensférer:Trensférer;Demande Inter:Demande Inter;Résolu:Résolu" },
					formoptions:{ elmprefix:"*  " },
					stype:'select'},
			{name:'Tech',
					index:'Tech',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]},
					formoptions:{ elmprefix:"*  " }},
			{name:'hh',
					index:'hh',
					width:130,
					hidden:false,
					search:true,
					align:'center',
					key:false,
					hidedlg:false,
					sorttype:'date',
					formatter:'date',
					formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
					editable:false,
					searchoptions:{
                                                    dataInit:function(el){
                                                        jQuery(el).datepicker({
                                                            showTime: true,
                                                        });
                                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                    },
                                                    sopt:["eq","ne","le","lt","ge","gt"],
                                                }},
			{name:'dfdsf',
					index:'dfdsf',
					width:130,
					hidden:false,
					search:true,
					align:'center',
					key:false,
					hidedlg:false,
					sorttype:'date',
					formatter:'date',
					formatoptions:{srcformat:"Y-m-d H:i",newformat:"d/m/Y H:i"},
					editable:false,
					searchoptions:{
                                                    dataInit:function(el){
                                                        jQuery(el).datepicker({
                                                            showTime: true,
                                                        });
                                                        jQuery("#ui-datepicker-div").addClass("promoteZ");
                                                    },
                                                    sopt:["eq","ne","le","lt","ge","gt"],
                                                }},
			{name:'Tech2',
					index:'Tech2',
					width:130,
					hidden:false,
					search:true,
					align:'left',
					key:false,
					hidedlg:false,
					editable:false,
					searchoptions:{sopt:["eq","ne","nc","cn","bw","ew","nb","ne"]},
					formoptions:{ elmprefix:"*  " }},
			{name:'Société',
					index:'soc',
					width:130,
					align:'left',
					search:true,
					sortable:false},
			{name:'Propal',
					index:'propal',
					width:100,
					align:'left',
					search:true,
					sortable:false},
			{name:'Projet',
					index:'fkprojet',
					width:130,
					align:'left',
					search:true,
					sortable:false},
			{name:'Statut',
					index:'fk_statut',
					width:80,
					align:'right',
					stype:'select',
					searchoptionspp:{sopt:['eq','ne']},
					searchoptions:{value: statutRess},
					formoptions:{ elmprefix:"*  " }},
			{name:'NbDoc',
					index:'nb_doc',
					width:60,
					align:'right',
					search:false,
					sortable:false}],
	colNames:['id',			
'Réf',			
'hasRev',			
'Objet',			
'Note',			
'Date___Heure',			
'Contrat',			
'Produit_Client',			
'Etat',			
'Tech',			
'hh',			
'dfdsf',			
'Tech2',			
'Société',			
'Propal',			
'Projet',			
'Statut',			
'NbDoc'],
                onSelectRow: function (id, isSelected) {
                    updateIdsOfSelectedRows(id, isSelected);
                    saveColumnState.call($grid, $grid[0].p.remapColumns);
                },
                onSelectAll: function (aRowids, isSelected) {
                    var i, count, id;
                    for (i = 0, count = aRowids.length; i < count; i++) {
                        id = aRowids[i];
                        updateIdsOfSelectedRows(id, isSelected);
                    }
                    saveColumnState.call($grid, $grid[0].p.remapColumns);
                },
                loadComplete: function () {
                    var $this = $(this), i, count;

                    if (firstLoad) {
                        firstLoad = false;
                        if (isColState) {
                            $this.jqGrid("remapColumns", myColumnsState.permutation, true);
                        }
                        if (typeof (this.ftoolbar) !== "boolean" || !this.ftoolbar) {
                            // create toolbar if needed
                            $this.jqGrid('filterToolbar',
                                {stringResult: true, searchOnEnter: true, defaultSearch: myDefaultSearch});
                        }
                    }
                    refreshSerchingToolbar($this, myDefaultSearch);
                    for (i = 0, count = idsOfSelectedRows.length; i < count; i++) {
                        $this.jqGrid('setSelection', idsOfSelectedRows[i], false);
                    }
                    saveColumnState.call($this, this.p.remapColumns);
                },
                resizeStop: function () {
                    saveColumnState.call($grid, $grid[0].p.remapColumns);
                }
            });
            $.extend($.jgrid.search, {
                multipleSearch: true,
                multipleGroup: true,
                recreateFilter: true,
                closeOnEscape: true,
                closeAfterSearch: true,
                overlay: 0
            });
            $grid.jqGrid('navGrid', '#pager', {edit: false, add: false, del: false});
            $grid.jqGrid('navButtonAdd', '#pager', {
                caption: "",
                buttonicon: "ui-icon-calculator",
                title: "choose columns",
                onClickButton: function () {
                    $(this).jqGrid('columnChooser', {
                        done: function (perm) {
                            if (perm) {
                                this.jqGrid("remapColumns", perm, true);
                                saveColumnState.call(this, perm);
                            }
                        }
                    });
                }
            });
            $grid.jqGrid('navButtonAdd', '#pager', {
                caption: "",
                buttonicon: "ui-icon-closethick",
                title: "clear saved grid's settings",
                onClickButton: function () {
                    removeObjectFromLocalStorage(myColumnStateName);
                    window.location.reload();
                }
            });
        });
    //]]>
    </script>
</head>
<body>
    <table id="list"><tr><td/></tr></table>
    <div id="pager"></div>
</body>
</html>
<?php die; ?>