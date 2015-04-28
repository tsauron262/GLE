var gridimgpath="images/";
$(document).ready(function(){

//jqGrids
    $("#gridList").jqGrid({
        datatype: "json",
        url: "ajax/listCamp_json.php",
        colNames:['id', 'D&eacute;signation','Date de d&eacute;but', 'Date de fin', 'Statut','Tiers'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                    {name:'nom',index:'nom', width:90},
                    {name:'datedeb',index:'datedeb', width:90, datefmt: "dd/mm/yyyy",sorttype: "date"},
                    {name:'datefin',index:'datefin', width:100, datefmt: "dd/mm/yyyy",sorttype: "date"},
                    {name:'Statut',index:'statut', width:80, align:"right",stype:'select',editoptions:{value:"0:Selection;1:Brouillon;2:Valider;3:Cloturer"}},
                    {name:'Tiers',index:'Tiers', width:80, align:"right"},
                  ],
        rowNum:10,
        rowList:[10,20,30],
        imgpath: gridimgpath,
        pager: jQuery('#gridListPager'),
        sortname: 'id',
        mtype: "POST",
        gridview : false,
        viewrecords: false,
        sortorder: "desc",
        width: 850,
        //multiselect: true,
        caption: "Liste des campagnes"
    });
    jQuery('#lui_gridList').remove();
    campagneID = $("#campId").val();
    var mygrid = $("#gridListSocL").jqGrid({
        datatype: "json",
        url: "ajax/listSoc_json.php?action=listed&campagneId="+campagneID,
        colNames:['id','Nom', 'fk_effectif', 'Effectif','departmentStr','D&eacute;partement','fk_secteur','Secteur d\'activit&eacute;'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                    {name:'nom',index:'nom', width:90},
                    {name:'fk_effectif',index:'fk_effectif', hidden:true,width:90,hidedlg:true,search:false},
                    {
                        name: 'effectifStr',
                        index: 'fk_effectif',
                        width: 100,
                        stype: 'select',
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        editoptions: {
                            value: EditOpt["effectif"],
                        }
                    },
                    {name:'fk_departement',index:'fk_departement', width:80, hidden:true,align:"right",hidedlg:true,search:false },
                    {
                        name: 'departmentStr',
                        index: 'fk_departement',
                        width: 80,
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        stype: 'select',
                        align: "right",
                        editoptions: {
                            value: EditOpt["departements"]
                        }
                    },
                    {name:'fk_secteur',index:'fk_secteur', width:80, hidden:true,align:"right",hidedlg:true,search:false},
                    {
                        name: 'fk_secteurStr',
                        index: 'fk_secteur',
                        width: 80,
                        searchoptions:{sopt:['eq','ne']},
                        align: "right",
                        stype: 'select',
                        editoptions: {
                            value: EditOpt["sectAct"],
                        }
                    },
                  ],
        rowNum:15,
        rowList:[15,30,45],
        imgpath: gridimgpath,
        pager: jQuery('#gridListSocLPager'),
        sortname: 'nom',
        mtype: "POST",
        gridview : true,
        //rownumbers: true,
        viewrecords: true,
        height: 250,
        width: 641,
        sortorder: "asc",
        multiselect: true,
        caption: "Soci&eacute;t&eacute;s non s&eacute;lectionn&eacute;es",
    }).navGrid('#gridListSocLPager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    }).navButtonAdd("#gridListSocLPager",
                    {caption:"",
                     title:"Afficher la barre de recherche",
                     buttonicon :'ui-icon-pin-s',
                     onClickButton:function(){ mygrid[0].toggleToolbar()}
    }).navButtonAdd("#gridListSocLPager",
                    {gridList:"Clear",
                     title:"Annuler les filtres",
                     buttonicon :'ui-icon-refresh',
                     caption: "",
                     onClickButton:function(){ mygrid[0].clearToolbar() }
    });
    mygrid.filterToolbar();

var mygrid2 = $("#gridListSocNL").jqGrid({
        datatype: "json",
        url: "ajax/listSoc_json.php?action=unlisted&campagneId="+campagneID,
        colNames:['id','Nom', 'fk_effectif', 'Effectif','departmentStr','D&eacute;partement','fk_secteur','Secteur d\'activit&eacute;'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                    {name:'nom',index:'nom', width:90},
                    {name:'fk_effectif',index:'fk_effectif', hidden:true,width:90,hidedlg:true,search:false},
                    {
                        name: 'effectifStr',
                        index: 'fk_effectif',
                        width: 100,
                        stype: 'select',
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        editoptions: {
                            value: EditOpt["effectif"],
                        }
                    },
                    {name:'fk_departement',index:'fk_departement', width:80, hidden:true,align:"right",hidedlg:true,search:false },
                    {
                        name: 'departmentStr',
                        index: 'fk_departement',
                        width: 80,
                        stype: 'select',
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        align: "right",
                        editoptions: {
                            value: EditOpt["departements"]
                        }
                    },
                    {name:'fk_secteur',index:'fk_secteur', width:80, hidden:true,align:"right",hidedlg:true,search:false},
                    {
                        name: 'fk_secteurStr',
                        index: 'fk_secteur',
                        width: 80,
                        align: "right",
                        searchoptions:{sopt:['eq','ne']},
                        stype: 'select',
                        editoptions: {
                            value: EditOpt["sectAct"],
                        }
                    },
                  ],
        rowNum:15,
        rowList:[15,30,45],
        imgpath: gridimgpath,
        pager: jQuery('#gridListSocNLPager'),
        sortname: 'nom',
        mtype: "POST",
        gridview : true,
        //rownumbers: true,
        viewrecords: true,
        height: 250,
        width: 641,
        sortorder: "asc",
        multiselect: true,
        caption: "Soci&eacute;t&eacute;s s&eacute;lectionn&eacute;es"
    }).navGrid('#gridListSocNLPager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    }).navButtonAdd("#gridListSocNLPager",
                    {caption:"",
                     title:"Afficher la barre de recherche",
                     buttonicon :'ui-icon-pin-s',
                     onClickButton:function(){ mygrid2[0].toggleToolbar()}
    }).navButtonAdd("#gridListSocNLPager",
                    {gridList:"Clear",
                    caption:"",
                     title:"Annuler les filtres",
                     buttonicon :'ui-icon-refresh',
                     onClickButton:function(){ mygrid2[0].clearToolbar() }
    });
    mygrid2.filterToolbar();

var mygrid1 = $("#gridListSocNLRo").jqGrid({
        datatype: "json",
        url: "ajax/listSoc_json.php?action=unlisted&campagneId="+campagneID,
        colNames:['id','Nom', 'fk_effectif', 'Effectif','fk_departement','D&eacute;partement','fk_secteur','Secteur d\'activit&eacute;'],
        colModel:[  {name:'id',index:'id', width:55, hidden:true,key:true,hidedlg:true,search:false},
                    {name:'nom',index:'nom', width:90, searchoptions:{sopt:['eq','ne','bw','bn','in','ni','ew','en','cn','nc']}},
                    {name:'fk_effectif',index:'fk_effectif', hidden:true,width:90,hidedlg:true,search:false},
                    {
                        name: 'effectifStr',
                        index: 'fk_effectif',
                        width: 100,
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        stype: 'select',
                        editoptions: {
                            value: EditOpt["effectif"],
                        }
                    },
                    {name:'fk_departement',index:'fk_departement', width:80, hidden:true,align:"right",hidedlg:true,search:false },
                    {
                        name: 'departmentStr',
                        index: 'fk_departement',
                        width: 80,
                        stype: 'select',
                        align: "right",
                        searchoptions:{sopt:['eq','ne','gt','ge','lt','le']},
                        editoptions: {
                            value: EditOpt["departements"]
                        }
                    },
                    {name:'fk_secteur',index:'fk_secteur', width:80, hidden:true,align:"right",hidedlg:true,search:false},
                    {
                        name: 'fk_secteurStr',
                        index: 'fk_secteur',
                        width: 80,
                        align: "right",
                        stype: 'select',
                        searchoptions:{sopt:['eq','ne']},
                        editoptions: {
                            value: EditOpt["sectAct"],
                        }
                    },
                  ],
        rowNum:15,
        rowList:[15,30,45],
        imgpath: gridimgpath,
        pager: jQuery('#gridListSocNLRoPager'),
        sortname: 'nom',
        mtype: "POST",
        gridview : true,
        //rownumbers: true,
        viewrecords: true,
        height: 250,
        width: 1200,
        sortorder: "asc",
        caption: "Soci&eacute;t&eacute;s s&eacute;lectionn&eacute;es"
    }).navGrid('#gridListSocNLRoPager',
               { add:false,
                 del:false,
                 edit:false,
                 position:"left"
    }).navButtonAdd("#gridListSocNLRoPager",
                    {caption:"Cherche",
                     title:"Affiche la barre de recherche",
                     buttonicon :'ui-icon-pin-s',
                     onClickButton:function(){ mygrid1[0].toggleToolbar()}
    }).navButtonAdd("#gridListSocNLRoPager",
                    {gridList:"Clear",
                    caption:"Annuler les filtres",
                     title:"Annuler les filtres",
                     buttonicon :'ui-icon-refresh',
                     onClickButton:function(){ mygrid1[0].clearToolbar() }
    });
    mygrid1.filterToolbar();

    $('#sel').click(function(){
        //call ajax, select listed
        //get listed
        var s;
        s = jQuery("#gridListSocL").getGridParam('selarrrow');
        if (s + "x" != "x") {

            $.ajax({
                type: "POST",
                url: "ajax/listSoc_json.php?action=add&listed=unlisted&campagneId=" + campagneID, //
                data: "socidStr=" + s,
                success: function(msg){
                    //reload both grid
                    $("#gridListSocNL").trigger("reloadGrid");
                    $("#gridListSocL").trigger("reloadGrid");
                }
            });
        }

    });
    $('#desel').click(function(){
        //call ajax, deselect listed
        //get listed
        var s;
        s = jQuery("#gridListSocNL").getGridParam('selarrrow');
        if (s+"x" != "x")
        {
            $.ajax({
                type: "POST",
                url: "ajax/listSoc_json.php?action=add&listed=listed&campagneId="+campagneID, //
                data: "socidStr="+s,
                success: function(msg){
                    //reload both grid
                    $("#gridListSocNL").trigger("reloadGrid");
                    $("#gridListSocL").trigger("reloadGrid");
                }
            });
        }
    });

/// Click details

//    $("#m1").click( function() {
//        var s;
//        s = jQuery("#gridList").getGridParam('selarrrow');
//        alert(s);
//    });
//        $("#m1s").click( function() {
//        $("#gridList").setSelection("13");
//    });

    $("#editCampBut").click(function(){
        $('#editCampDialog').dialog('open');
    });
    $('#editCampDialog').dialog({ autoOpen: false ,
                                       hide: 'explode',
                                       modal: false,
                                       position: "center",
                                       minWidth: 400,
                                       width: 400,
                                       show: 'bounce',
                                       modal: true,
                                       title: 'Edition campagne',
                                       buttons: {
                                            "Cancel" : function() {
                                                $('#editCampDialog').dialog('close');
                                              },
                                            "Ok": function() {
                                                                //post data
                                                                var nom = $('#ModCampnom').val();
                                                                var resp = $('#Responsable :selected').val();
                                                                var comm = "";
                                                                var dateDeb = $('#modCampdateDebut').val();
                                                                var dateFin = $('#modCampdateFin').val();
                                                                var note = $.fck.content('fckGLE', '');
                                                                var iter =0;
                                                                $('#comm :selected').each(function(){
                                                                    comm += "&comm"+iter+"="+$(this).val();
                                                                    iter++;
                                                                });

                                                                var post = '';
                                                                    post += "&nom="+nom;
                                                                    post += "&resp="+resp;
                                                                    post += comm;
                                                                    post += "&dateDeb="+dateDeb;
                                                                    post += "&dateFin="+dateFin;
                                                                    post += "&note="+note;



                                                                    post += "&userid="+userId;
                                                                    post += "&action=update";
                                                                    post += "&campId="+campId;
//                                                                if ($("#formDialog").validate({
//                                                                    rules: {
//
//
//                                                                        },
//                                                                        messages: {
//
//                                                                        }
//
//                                                                }).form())
//                                                                {

                                                                    $.ajax({
                                                                        async: false,
                                                                        url: "ajax/updateCamp-xmlresponse.php",
                                                                        type: "POST",
                                                                        data: post,
                                                                        success: function(msg){
                                                                            if ($(msg).find('OK').text()=="OK")
                                                                            {
                                                                                location.reload();
                                                                            }
                                                                        }
                                                                    });
//                                                                }
                                                            }
                                                },
                                       open: function (e,u){
                                            $('#fckGLE').fck({ path: "../..//includes/fckeditor/",height:100
                                            //$.scrollTo(0,0);
                                       });
                                    }

        }); //dialog
        $('#modCampdateFin').datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });
        $('#modCampdateDebut').datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'cal.png',
                    buttonImageOnly: true,
                    showTime: true,
                    duration: '',
                    constrainInput: false,
            });

}); //end document.ready