<?php

require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/chronoDetailList.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
llxHeader($js, $titre);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>http://stackoverflow.com/q/8422878/315935</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.1/css/ui.jqgrid.css" />
    <link rel="stylesheet" type="text/css" href="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.1/plugin/ui.multiselect.css" />
    <style type="text/css">
        html, body { font-size: 75%; }
    </style>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.17/jquery-ui.min.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.1/plugin/ui.multiselect.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.1/js/i18n/grid.locale-en.js"></script>
    <script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/jquery.jqGrid-4.3.1/js/jquery.jqGrid.src.js"></script>
    <!--<script type="text/javascript" src="http://www.ok-soft-gmbh.com/jqGrid/json2.js"></script>-->

    <script type="text/javascript">
    //<![CDATA[
        /*global $ */
        /*jslint devel: true, browser: true, plusplus: true */
        $.jgrid.formatter.integer.thousandsSeparator = ',';
        $.jgrid.formatter.number.thousandsSeparator = ',';
        $.jgrid.formatter.currency.thousandsSeparator = ',';
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
                myColumnStateName = 'ColumnChooserAndLocalStorage1.colState',
                saveColumnState = function () {
                    var colModel = this.jqGrid('getGridParam', 'colModel'), i, l = colModel.length, colItem, cmName,
                        postData = this.jqGrid('getGridParam', 'postData'),
                        columnsState = {
                            search: this.jqGrid('getGridParam', 'search'),
                            page: this.jqGrid('getGridParam', 'page'),
                            rowNum: this.jqGrid('getGridParam', 'rowNum'),
                            sortname: this.jqGrid('getGridParam', 'sortname'),
                            sortorder: this.jqGrid('getGridParam', 'sortorder'),
                            permutation: this.jqGrid('getGridParam', 'remapColumns'),
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
                firstLoad = true;

            myColumnsState = restoreColumnState(cm);
            isColState = typeof (myColumnsState) !== 'undefined' && myColumnsState !== null;

            $grid.jqGrid({
                datatype: 'local',
                data: myData,
                colNames: [/*'Inv No',*/'Date', 'Client', 'Amount', 'Tax', 'Total', 'Closed', 'Shipped via', 'Notes'],
                colModel: cm,
                rowNum: isColState ? myColumnsState.rowNum : 10,
                rowList: [5, 10, 20],
                pager: '#pager',
                gridview: true,
                page: isColState ? myColumnsState.page : 1,
                search: isColState ? myColumnsState.search : false,
                postData: isColState ? { filters: myColumnsState.filters } : {},
                sortname: isColState ? myColumnsState.sortname : 'invdate',
                sortorder: isColState ? myColumnsState.sortorder : 'desc',
                rownumbers: true,
                ignoreCase: true,
                //shrinkToFit: false,
                //viewrecords: true,
                caption: 'The usage of localStorage to save jqGrid preferences',
                height: 'auto',
                loadComplete: function () {
                    var $this = $(this);
                    if (firstLoad) {
                        firstLoad = false;
                        if (isColState && myColumnsState.permutation.length > 0) {
                            $this.jqGrid("remapColumns", myColumnsState.permutation, true);
                        }
                        if (typeof (this.ftoolbar) !== "boolean" || !this.ftoolbar) {
                            // create toolbar if needed
                            $this.jqGrid('filterToolbar',
                                {stringResult: true, searchOnEnter: true, defaultSearch: myDefaultSearch});
                        }
                    }
                    refreshSerchingToolbar($this, myDefaultSearch);
                    saveColumnState.call($this);
                },
                resizeStop: function () {
                    saveColumnState.call($grid);
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
                                saveColumnState.call(this);
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