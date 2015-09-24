<?php

    /*
     ** GLE by Synopsis et DRSI
     *
     * Author: Tommy SAURON <tommy@drsi.fr>
     * Licence : Artistic Licence v2.0
     *
     * Version 1.1
     * Created on : 10 aout 09
     *
     * Infos on http://www.finapro.fr
     *
     */
    /**
     *
     * Name : index.php
     * GLE-1.1
     */
    //liste les ressources
    //TODO editoptions => check mod select + return add => should close and relaod

    require_once('pre.inc.php') ;
    //require_once(DOL_DOCUMENT_ROOT."/core/lib/ressource.lib.php");


    $langs->load( "companies" ) ;
    $langs->load( "commercial" ) ;
    $langs->load( "bills" ) ;
    $langs->load('synopsisGene@synopsistools') ;

// Security check
//    $socid = isset( $_GET[ "socid" ] ) ? $_GET[ "socid" ] : '' ;
//    if ( $user->societe_id )
//	$socid = $user->societe_id ;
//    $result = restrictedArea( $user, 'societe', $socid ) ;
// Initialisation de l'objet Societe

    if ( !($user->rights->SynopsisRessources->SynopsisRessources->Utilisateur || $user->rights->SynopsisRessources->SynopsisRessources->Admin || $user->rights->SynopsisRessources->SynopsisRessources->Resa) )
    {
	accessforbidden() ;
    }
    $soc = new Societe( $db ) ;
    $soc->fetch( $socid ) ;



    $jspath = DOL_URL_ROOT . "/Synopsis_Common/jquery" ;
    $jqueryuipath = DOL_URL_ROOT . "/Synopsis_Common/jquery/ui" ;
    $css = DOL_URL_ROOT . "/Synopsis_Common/css" ;
    $imgPath = DOL_URL_ROOT . "/Synopsis_Common/images" ;

    $js = '<link rel="stylesheet" type="text/css" media="screen" href="' . $css . '/jquery-ui.css" />' ;
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $css . '/flick/jquery-ui-1.7.2.custom.css" />' ;

    $js .= '      <link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/Synopsis_Common/css/jquery.treeview.css" />' ;


    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/ui.jqgrid.css" />' ;
    $js .= '<link rel="stylesheet" type="text/css" media="screen" href="' . $jspath . '/jqGrid-3.5/css/jquery.searchFilter.css" />' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-latest.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery-ui-latest.custom.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/includes/jquery/plugins/tiptip/jquery.tipTip.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.validate.min.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . $jspath . '/ajaxfileupload.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/i18n/grid.locale-fr.js" type="text/javascript"></script>' ;
    $js .= ' <script src="' . $jspath . '/jqGrid-4.5/js/jquery.jqGrid.js" type="text/javascript"></script>' ;



    $js .= "<style type='text/css'>body { position: static; }</style>" ;
    $js .= "<style type='text/css'>.ui-progressbar{ height: 13px; background-color: #ffffff; margin: 0px; float: left; width: 100%;}</style>" ;
    $js .= "<style type='text/css'>.ui-progressbar-value{ border:1px solid #000000;float: left; width: 100%; }
button {
    background:#FDFDFD none repeat scroll 0 0;
    border:1px solid #ACBCBB;
    margin:0;
    padding:0;
}
                                SELECT { width: 150px; }
                                INPUT.hasDatepicker { width: 150px;}
                                .ui-jqgrid-titlebar-close { display: none;}
                                .ui-pg-selbox { width: auto; }</style>" ;
    $js .= "<style type='text/css'>.promoteZ , .ui-datepicker{ z-index: 2006; /* Dialog z-index is 1006*/}</style>" ;
    $js .= ' <script src="' . $jqueryuipath . '/ui.datepicker.js" type="text/javascript"></script>' ;
//$js .= ' <script src="'.$jqueryuipath.'/ui.timepicker.js" type="text/javascript"></script>';

    $js .= ' <script src="' . $jspath . '/jqGrid-3.5/plugins/jquery.contextmenu.js" type="text/javascript"></script>' ;
    $js .= '      <script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.bgiframe.js" ></script>' ;
//$js .= '      <script type="text/javascript" src="'.DOL_URL_ROOT.'/Synopsis_Common/jquery/jquery.dimensions.js" ></script>';
    $js .= '      <script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.tooltip.js" ></script>' ;
    $js .= '      <script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.treeview.js" ></script>' ;
    $js .= '      <script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.cookie.js" ></script>' ;
    $js .= '      <script type="text/javascript" src="' . DOL_URL_ROOT . '/Synopsis_Common/jquery/jquery.filestyle.js" ></script>' ;

    $requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."user
             WHERE statut = 1
          ORDER BY firstname, lastname" ;
    $sql = $db->query( $requete ) ;
    $jsEditopts[ "userincomp" ] = "" ;
    $jsEditopts[ "userincomp" ].= "-1:" . preg_replace( "/'/", "\\'", utf8_decode( utf8_encode( html_entity_decode( "Sélection ->" ) ) ) ) . ";" ;
    while ( $res = $db->fetch_object( $sql ) )
    {
	$jsEditopts[ "userincomp" ].= $res->rowid . ":" . preg_replace( "/'/", "\\'", utf8_decode( utf8_encode( $res->firstname . " " . $res->name ) ) ) . ";" ;
    }

    $requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources
             WHERE isGroup = 1
               AND level=0" ;
    $sql = $db->query( $requete ) ;
    $jsEditopts[ "catRess" ] = "" ;
    $jsEditopts[ "catRess" ].= "-1:" . preg_replace( "/'/", "\\'", utf8_decode( utf8_encode( html_entity_decode( "Sélection ->" ) ) ) ) . ";" ;
    while ( $res = $db->fetch_object( $sql ) )
    {
	$jsEditopts[ "catRess" ].= $res->id . ":" . "|" . str_repeat( "-", $res->level ) . "->" . preg_replace( "/'/", "\\'", utf8_decode( utf8_encode( $res->nom ) ) ) . ";" ;
	$jsEditopts[ "catRess" ].= getRessourceSelectList( $res->id, $db ) ;
    }

    $jsEditopts[ "userincomp" ] = preg_replace( '/;$/', '', $jsEditopts[ "userincomp" ] ) ;
    $jsEditopts[ "catRess" ] = preg_replace( '/;$/', '', $jsEditopts[ "catRess" ] ) ;
    $jqgridJs = "<script type='text/javascript'>" ;
    $jqgridJs .= 'var gridimgpath="' . $imgPath . '/images/";' ;
    $jqgridJs .= 'var userId="' . $user->id . '";' ;
    $jqgridJs .= 'var socId="' . $soc->id . '"; ' ;
    $jqgridJs .= 'var campId="' . $campagneId . '"; ' ;
    $jqgridJs .= 'var DOL_URL_ROOT="' . DOL_URL_ROOT . '"; ' ;
    $jqgridJs .= "var userincomp='" . $jsEditopts[ "userincomp" ] . "'\n" ;
    $jqgridJs .= "var catRess='" . $jsEditopts[ "catRess" ] . "'\n" ;
    $rightJS = ($user->rights->SynopsisRessources->SynopsisRessources->Admin ? $rightJS = "true" : $rightJS = "false") ;


    $jqgridJs .= "var rightModif=" . $rightJS . ";" ;

    $jqgridJs .= "var eventsMenu = {\n" ;
    $jqgridJs .= "            bindings: {\n" ;
    $jqgridJs .= "                'editer': function(t) {\n" ;
    $jqgridJs .= "                    var gr = t.id;\n" ;
    $jqgridJs .= "                    if( gr != null ) jQuery('#gridListRessources').editGridRow(gr,{" ;
    $jqgridJs.= '                  reloadAfterSubmit:true,' . "\n" ;
    $jqgridJs.= '                  bottominfo:"Les champs marqu&eacute;s d\'une &eacute;toile sont requis",' . "\n" ;
    $jqgridJs.= '                  afterShowForm:function(a,b) {
$("#categorie").val(remParent);
},' . "\n" ;
    $jqgridJs.= '                  afterSubmit: function (a,b)' . "\n" ;
    $jqgridJs.= '                  {' . "\n" ;
    $jqgridJs.= '                      var id = $(a.responseXML).find("ok").text();' . "\n" ;
    $jqgridJs.= '                      var ret= new Array(); ret = [true,""];' . "\n" ;
    $jqgridJs.= '                      if ($("#photo").val()+"x" != "x"){' . "\n" ;
    $jqgridJs.= '                      $.ajaxFileUpload' . "\n" ;
    $jqgridJs.= '                      ({' . "\n" ;
    $jqgridJs.= '                          url:"ajax/upload_img.php?ressource_id="+id,' . "\n" ;
    $jqgridJs.= '                          secureuri:false,' . "\n" ;
    $jqgridJs.= '                          fileElementId:"photo",' . "\n" ;
    $jqgridJs.= '                          dataType: "xml",' . "\n" ;
    $jqgridJs.= '                          async: false,' . "\n" ;
    $jqgridJs.= '                          success: function (data, status)' . "\n" ;
    $jqgridJs.= '                          {' . "\n" ;
    $jqgridJs.= '                              ret =  [true,""];' . "\n" ;
    $jqgridJs.= '                              return [ret]' . "\n" ;
    $jqgridJs.= '                          },' . "\n" ;
    $jqgridJs.= '                              error: function (data, status, e)' . "\n" ;
    $jqgridJs.= '                              {' . "\n" ;
    $jqgridJs.= '                                  ret = [false,"Erreur Ajax2"];' . "\n" ;
    $jqgridJs.= '                                  return ret;' . "\n" ;
    $jqgridJs.= '                              }' . "\n" ;
    $jqgridJs.= '                      });' . "\n" ;
    $jqgridJs.= '                  }' . "\n" ;
    $jqgridJs.= '                  return (ret);' . "\n" ;
    $jqgridJs.= '                  },' . "\n" ;
    $jqgridJs .= " });\n" ;
    $jqgridJs .= "                    else alert('Merci de Sélectionner une ressource');\n" ;

    $jqgridJs .= "                },\n" ;
    $jqgridJs .= "                'supprimer': function(t) {\n" ;
    $jqgridJs .= "                    var gr = t.id;\n" ;
    $jqgridJs .= "                    if( gr != null ) jQuery('#gridListRessources').delGridRow(gr,{reloadAfterSubmit:true});\n" ;
    $jqgridJs .= "                    else alert('Merci de Sélectionner une ressource');\n" ;
    $jqgridJs .= "                },\n" ;
    $jqgridJs .= "                'reserver': function(t) {\n" ;
    $jqgridJs .= "                    var gr = t.id;\n" ;
    $jqgridJs .= "                    if( gr != null ) location.href='resa.php?ressource_id='+gr;\n" ;
    $jqgridJs .= "                    else alert('Merci de Sélectionner une ressource');\n" ;
    $jqgridJs .= "                }\n" ;
    $jqgridJs .= "            }\n" ;
    $jqgridJs .= "        };\n" ;

    function getRessourceSelectList( $id, $db )
    {
	$requete = "SELECT *
                  FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources
                 WHERE fk_parent_ressource =" . $id . "
                   AND isGroup=1" ;
	$sql = $db->query( $requete ) ;
	$tmp = "" ;
	while ( $res = $db->fetch_object( $sql ) )
	{
	    $tmp .= $res->id . ":" . "|" . str_repeat( "--", $res->level ) . "-> " . preg_replace( "/'/", "\\'", utf8_decode( utf8_encode( $res->nom ) ) ) . ";" ;
	    $tmp.= getRessourceSelectList( $res->id, $db ) ;
	}
	return($tmp) ;
    }
    $result = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_resatype");
    $arrayTypeResa = array();
    while ($res = $db->fetch_object($result))
        $arrayTypeResa[$res->id] = $res->id.":".$res->name;
    
    

    $jqgridJs .= <<<EOF
var grid;
var remParent = 0;
var remCatName = "Ressources";
var submitPhotoID=""; //case insert
function showRessource(pParentId)
{

    var get = "&parent="+pParentId;
    if (pParentId == -1)
    {
        get = "";
    }
    remParent=pParentId;
    //Get cat Name
    try{
        remCatName = $("#catRes"+pParentId).text();
    } catch(e){
        alert (e);
    }

    jQuery("#gridListRessources").setGridParam({url:"ajax/ressource_json.php?userId="+userId+get}).trigger("reloadGrid")

}
function sleepJs(millis) {
    var notifier = new EventNotifier();
    setTimeout(notifier, millis);
    //notifier.wait->();
}

$(document).ready(function(){
    $('#dialogResa').dialog({
        hide: 'explode',
        autoOpen: false,
        modal: true,
        position: "center",
        minWidth: 400,
        width: 400,
        show: 'bounce',
        title: 'Nouvelle r&eacute;servation',
        buttons: {
            "Cancel" : function(){
                $('#dialogResa').dialog('close');
            },
            "Ok": function() {

            }
        }
    });

    $('#dialogNewCat').dialog({
        hide: 'explode',
        autoOpen: false,
        modal: true,
        position: "center",
        minWidth: 400,
        width: 400,
        show: 'bounce',
        title: 'Nouvelle cat&eacute;gorie',
        buttons: {
            "Cancel" : function(){
                $('#dialogNewCat').dialog('close');
            },
            "Ok": function() {
                //post data
                var addparentID = $('#addparentID').val();
                var addnomCat = $('#addnomCat').val();

                var post = '';
                    post += "&parentID="+addparentID;
                    post += "&nomCat="+addnomCat;
                    post += "&action=add";
                if ($("#formDialogAdd").validate({
                                    rules: {
                                            addparentID: {
                                                required: true,
                                            },
                                            addnomCat : {
                                                required: true,
                                                minlength: 5
                                            },
                                        },
                                        messages: {
                                            addparentID: {
                                                required : "<br>Champs requis",
                                            },
                                            addnomCat: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                        }
                                }).form()) {
                                    $.ajax({
                                            async: false,
                                            url: "ajax/cat_xmlresponse.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if ($(msg).find('OK').text()+"x" != "x")
                                                {
                                                    location.reload();
                                                } else {
                                                    alert ($(msg).find('KO').text());
                                                }
                                            }
                                        });
                                    }
                        },

                },
       open: function (e,u){
            $('#addparentID').val(remParent);
       }
    });
    $("#add_cat").mouseover(function(){
        $(this).addClass('ui-state-hover');
    });
    $("#add_cat").mouseout(function(){
        $(this).removeClass('ui-state-hover');
    });

    $('#add_cat').click(function(){
        $('#dialogNewCat').dialog('open');
    });

    $('#dialogModCat').dialog({
        hide: 'explode',
        autoOpen: false,
        modal: true,
        position: "center",
        minWidth: 400,
        width: 400,
        show: 'bounce',
        title: 'Modifier une cat&eacute;gorie ',
        buttons: {
            "Cancel" : function(){
                $('#dialogModCat').dialog('close');
            },
            "Ok": function() {
                //post data
                var modparentID = $('#modparentID').val();
                var modnomCat = $('#modnomCat').val();

                var post = '';
                    post += "&parentID="+modparentID;
                    post += "&nomCat="+modnomCat;
                    post += "&id="+remParent;
                    post += "&action=mod";

                if ($("#formDialogMod").validate({
                    rules: {
                            modparentID: {
                                required: true,
                            },
                            modnomCat : {
                                required: true,
                                minlength: 5

                            },
                        },
                        messages: {
                            modparentID: {
                                required : "<br>Champs requis",
                            },
                            modnomCat: {
                                required : "<br>Champs requis",
                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                            },
                        }
                    }).form()) {
                        $.ajax({
                                async: false,
                                url: "ajax/cat_xmlresponse.php",
                                type: "POST",
                                data: post,
                                success: function(msg){
                                    //reload
                                    if ($(msg).find('OK').text()+"x" != "x")
                                    {
                                        location.reload();
                                    } else {
                                        alert ($(msg).find('KO').text());
                                    }
                                }
                            });
                        }
                    }
                },
       open: function (e,u){
//TODO init mod form
            $('#modnomCat').val(remCatName);
            var tmpId = $('#resParent'+remParent).val();
            $('#modparentID').val(tmpId); //prob, manque parent

       }
    });
    $("#mod_cat").mouseover(function(){
        $(this).addClass('ui-state-hover');
    });
    $("#mod_cat").mouseout(function(){
        $(this).removeClass('ui-state-hover');
    });

    $('#mod_cat').click(function(){
        $('#dialogModCat').dialog('open');
    });


    $("#del_cat").mouseover(function(){
        $(this).addClass('ui-state-hover');
    });
    $("#del_cat").mouseout(function(){
        $(this).removeClass('ui-state-hover');
    });
    $('#del_cat').click(function(){
        $('#dialogDelCat').dialog('open');
    });
    $('#dialogDelCat').dialog({
        hide: 'explode',
        autoOpen: false,
        modal: true,
        position: "center",
        minWidth: 400,
        width: 400,
        show: 'bounce',
        title: 'Effacer une cat&eacute;gorie ',
        buttons: {
            "Cancel" : function(){
                $('#dialogDelCat').dialog('close');
            },
            "Ok": function() {
                //post data

                var post = '';
                    post += "&id="+remParent;
                    post += "&action=del";

                $.ajax({
                        async: false,
                        url: "ajax/cat_xmlresponse.php",
                        type: "POST",
                        data: post,
                        success: function(msg){
                            //reload
                            if ($(msg).find('OK').text()+"x" != "x")
                            {
                                location.reload();
                            } else {
                                alert ($(msg).find('KO').text());
                            }

                        }
                    });
                }
            },
       open: function (e,u){
       }
    });

    $.datepicker.setDefaults($.extend({showMonthAfterYear: false,
                        dateFormat: 'dd/mm/yy',
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true,
                        buttonImage: 'cal.png',
                        buttonImageOnly: true,
                        showTime: true,
                        duration: '',
                        constrainInput: false,}, $.datepicker.regional['fr']));

    $("#ui-datepicker-div").addClass("promoteZ");
    //$("#ui-timepicker-div").addClass("promoteZ");
    $(".datePicker").datepicker();

    var get = "";
    $("#tree").treeview({
                        collapsed: false,
                        animated: "medium",
                        control:"#sidetreecontrol",
                        prerendered: true,
                        persist: "location",
    });
    jQuery.jgrid.edit.msg.minValue="Ce champs est requis";
    grid = $("#gridListRessources").jqGrid({
            datatype: "json",
            url: "ajax/ressource_json.php?userId="+userId+get,
            colNames:['id',"D&eacute;signation", "Cat&eacute;gorie",'R&eacute;f&eacute;rent','Description','Date achat','Valeur','Co&ucirc;t unitaire','Type Resa','Photo'],
            colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'nom',index:'nom', width:80, align:"center",editable:true,searchoptions:{
                            sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},editrules:{required:true},formoptions:{ elmprefix:"*  " }
                        },
                        {
                            name:'categorie',
                            index:'categorie',
                            display:"none",
                            align:"center",
                            editable:true,
                            hidden:true,
                            hidedlg:false,
                            edithidden: false,
                            search:false,
                            stype: 'select',
                            edittype: 'select',
                            editable: true,
                            searchoptions:{sopt:['eq','ne']},
                            editoptions: {
                                value: catRess,
                            },formoptions:{ elmprefix:"   " }
                        },
                        {
                            name:'fk_user_resp',
                            index:'fk_user_resp',
                            width:150,
                            align: 'center',
                            stype: 'select',
                            edittype: 'select',
                            editable: true,
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                            editoptions: {
                                value: userincomp,
                            },editrules:{required:true,minValue:0},formoptions:{ elmprefix:"*  " },
                        },
                        {
                            name:'description',
                            index:'description',
                            width:180,
                            align:"center",
                            editable:true,
                            edittype:"textarea",
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                            formoptions:{ elmprefix:"   ", width: "90%" },
                        },
                        {
                            name:'date_achat',
                            index:'date_achat',
                            width:90,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                            editable:true,formoptions:{ elmprefix:"   " },
                            searchoptions:{
                                dataInit:function(el){
                                    $(el).datepicker();
                                    $("#ui-datepicker-div").addClass("promoteZ");
                                    //$("#ui-timepicker-div").addClass("promoteZ");
                                },
                                sopt:['eq','ne',"le",'lt',"ge","gt"],
                            },
                            editoptions:{
                                dataInit:function(el){
                                    $(el).datepicker();
                                    $("#ui-datepicker-div").addClass("promoteZ");
                                    //$("#ui-timepicker-div").addClass("promoteZ");
                                }
                            },
                        },
                        {name:'valeur',index:'valeur', width:80, align:"center",editable:true,formoptions:{ elmprefix:"   " },
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                        },
                        {name:'cout',index:'cout', width:80, align:"center",editable:true,
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},formoptions:{ elmprefix:"   " },
                        },
                        {
                            name:'typeResa',
                            index:'typeResa',
                            width:80,
                            align:"center",
                            editable:true,
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]},
                            formoptions:{ elmprefix:"   " },
                            editable:true,
                            stype: 'select',
                            edittype: 'select',
                            editoptions: {
EOF;
//    die(implode(";", $arrayTypeResa));
    $jqgridJs .= "              value: '". utf8_encode(html_entity_decode(implode(";", $arrayTypeResa)))."'," ;
    $jqgridJs .= <<<EOF
                            },
                            formoptions:{ elmprefix:"   "},
                        },
                        {name:'photo',index:'photo', width:200, align:"center", edittype:'file',editable:true, search: false,sortable: false,formoptions:{ elmprefix:"   " },},
                      ],
            rowNum:10,
            rowList:[10,20,30],
            editurl: "ajax/ressource_ajax.php?usrId="+userId+get,
            imgpath: gridimgpath,
            pager: jQuery('#gridListRessourcesPager'),
            sortname: 'id',
            mtype: "POST",
            viewrecords: true,
            autowidth: true,
            height: 500,
            sortorder: "desc",
            //multiselect: true,
            caption: "<span style='padding:4px; font-size: 13px; '>Ressources</span>",
            viewsortcols: true,
            loadComplete: function(){
//                alert ("loadComplete");
                $('#gview_gridListRessources').find('.ui-jqgrid-titlebar').addClass('ui-state-default ui-widget-header');
            },
EOF;
    if ( !$user->rights->SynopsisRessources->SynopsisRessources->Admin )
    {
	$jqgridJs.= '    }).navGrid("#gridListRessourcesPager",' . "\n" ;
	$jqgridJs.= '                   { view:true, add:false,' . "\n" ;
	$jqgridJs.= '                     del:false,' . "\n" ;
	$jqgridJs.= '                     edit:false,' . "\n" ;
	$jqgridJs.= '                     search:true,' . "\n" ;
	$jqgridJs.= '                     position:"left"' . "\n" ;
	$jqgridJs.= '                     });' . "\n" ;
    }
    else
    {
	$jqgridJs.= '            afterInsertRow: function(rowid, rowdata, rowelem) {' . "\n" ;
	$jqgridJs.= '                    $("#" + rowid).contextMenu("MenuJqGrid", eventsMenu);' . "\n" ;
	$jqgridJs.= '            },' . "\n" ;
	$jqgridJs.= '        }).navGrid("#gridListRessourcesPager",' ;
	$jqgridJs.= '               { view:true, add:true,' . "\n" ;
	$jqgridJs.= '                 del:true,' . "\n" ;
	$jqgridJs.= '                 edit:true,' . "\n" ;
	$jqgridJs.= '                 search:true,' . "\n" ;
	$jqgridJs.= '                 position:"left"' . "\n" ;
	$jqgridJs.= '               },' ;
	$jqgridJs.= '               {' . "\n" ; //mod
	$jqgridJs.= '                  reloadAfterSubmit:true,' . "\n" ;
	$jqgridJs.= '                  bottominfo:"Les champs marqu&eacute;s d\'une &eacute;toile sont requis",' . "\n" ;
	$jqgridJs.= '                  afterShowForm:function(a,b) {
    $("#categorie").val(remParent);
    },' . "\n" ;
	$jqgridJs.= '                  afterSubmit: function (a,b)' . "\n" ;
	$jqgridJs.= '                  {' . "\n" ;
	$jqgridJs.= '                      var id = $(a.responseXML).find("ok").text();' . "\n" ;
	$jqgridJs.= '                      var ret=  new Array(); ret = [true,"",""];' . "\n" ;
	$jqgridJs.= '                      if ($("#photo").val()+"x" != "x"){' . "\n" ;
	$jqgridJs.= '                          $.ajaxFileUpload' . "\n" ;
	$jqgridJs.= '                          ({' . "\n" ;
	$jqgridJs.= '                              url:"ajax/upload_img.php?ressource_id="+id,' . "\n" ;
	$jqgridJs.= '                              secureuri:false,' . "\n" ;
	$jqgridJs.= '                              fileElementId:"photo",' . "\n" ;
	$jqgridJs.= '                              dataType: "xml",' . "\n" ;
	$jqgridJs.= '                              success: function (data, status)' . "\n" ;
	$jqgridJs.= '                              {' . "\n" ;
	$jqgridJs.= '                                  ret =  [true,""];' . "\n" ;
	$jqgridJs.= '                                  return ret' . "\n" ;
	$jqgridJs.= '                              },' . "\n" ;
	$jqgridJs.= '                              error: function (data, status, e)' . "\n" ;
	$jqgridJs.= '                              {' . "\n" ;
	$jqgridJs.= '                                  ret = [false,"Erreur Ajax5"];' . "\n" ;
	$jqgridJs.= '                                  return ret;' . "\n" ;
	$jqgridJs.= '                              }' . "\n" ;
	$jqgridJs.= '                          });' . "\n" ;
	$jqgridJs.= '                      }' . "\n" ;
	$jqgridJs.= '                      return (ret);' . "\n" ;
	$jqgridJs.= '                  },' . "\n" ;
	$jqgridJs.= '               },' ;
	$jqgridJs.= '               {' . "\n" ; //add
	$jqgridJs.= '                  reloadAfterSubmit:true,' . "\n" ;
	$jqgridJs.= '                  bottominfo:"Les champs marqu&eacute;s d\'une &eacute;toile sont requis",' . "\n" ;
	$jqgridJs.= '                  afterShowForm:function(a,b) {
    $("#categorie").val(remParent);
    },' . "\n" ;
	$jqgridJs.= '                  afterSubmit: function (a,b)' . "\n" ;
	$jqgridJs.= '                  {' . "\n" ;
	$jqgridJs.= '                      var id = $(a.responseXML).find("ok").text();' . "\n" ;
	$jqgridJs.= '                      var ret=  [true,""]' . "\n" ;
	$jqgridJs.= '                      if ($("#photo").val()+"x" != "x"){' . "\n" ;
	$jqgridJs.= '                      $.ajaxFileUpload' . "\n" ;
	$jqgridJs.= '                      ({' . "\n" ;
	$jqgridJs.= '                          url:"ajax/upload_img.php?ressource_id="+id,' . "\n" ;
	$jqgridJs.= '                          secureuri:false,' . "\n" ;
	$jqgridJs.= '                          fileElementId:"photo",' . "\n" ;
	$jqgridJs.= '                          dataType: "xml",' . "\n" ;
	$jqgridJs.= '                          async: false,' . "\n" ;
	$jqgridJs.= '                          success: function (data, status)' . "\n" ;
	$jqgridJs.= '                          {' . "\n" ;
	$jqgridJs.= '                              ret =  [true,""];' . "\n" ;
	$jqgridJs.= '                              if(typeof(data.error) != "undefined")' . "\n" ;
	$jqgridJs.= '                              {' . "\n" ;
	$jqgridJs.= '                                  if(data.error != "")' . "\n" ;
	$jqgridJs.= '                                  {' . "\n" ;
	$jqgridJs.= '                                      alert(data.error);' . "\n" ;
	$jqgridJs.= '                                      return [false,"error msg"];' . "\n" ;
	$jqgridJs.= '                                  } else {' . "\n" ;
	$jqgridJs.= '                                      return [true,""]' . "\n" ;
	$jqgridJs.= '                                      alert(data.msg);' . "\n" ;
	$jqgridJs.= '                                  }' . "\n" ;
	$jqgridJs.= '                              } else {' . "\n" ;
	$jqgridJs.= '                                  return [false,"Erreur indefinie"];' . "\n" ;
	$jqgridJs.= '                              }' . "\n" ;
	$jqgridJs.= '                          },' . "\n" ;
	$jqgridJs.= '                              error: function (data, status, e)' . "\n" ;
	$jqgridJs.= '                              {' . "\n" ;
	$jqgridJs.= '                                  ret = [false,"Erreur Ajax7"];' . "\n" ;
	$jqgridJs.= '                                  return [false,"Erreur Ajax8"];' . "\n" ;
	$jqgridJs.= '                              }' . "\n" ;
	$jqgridJs.= '                      });' . "\n" ;
	$jqgridJs.= '                      }' . "\n" ;
	$jqgridJs.= '                  return(ret);' . "\n" ;
	$jqgridJs.= '                  },' . "\n" ;
	$jqgridJs.= '              },' ;
	$jqgridJs.= '              {' . "\n" ; //del
	$jqgridJs.= '                  reloadAfterSubmit:true' . "\n" ;
	$jqgridJs.= '              },' ;
	$jqgridJs.= '              {' . "\n" ; //search
	$jqgridJs.= '                  closeOnEscape:true' . "\n" ;
	$jqgridJs.= '              },' . "\n" ;
	$jqgridJs.= '              {' ; //view
	$jqgridJs.= '                  closeOnEscape:true' . "\n" ;
	$jqgridJs.= '              }' . "\n" ;
	$jqgridJs.= '    );' . "\n" ;
    }//fin if droit

    $jqgridJs.= '    });' . "\n" ;

    $jqgridJs.= 'var avanc;' . "\n" ;
    $jqgridJs.= 'var date;' . "\n" ;
    $jqgridJs.= 'var note;' . "\n" ;

    $jqgridJs.= '</script>' . "\n" ;

    $js .= ' <script src="' . $jqueryuipath . '/ui.selectmenu.js" type="text/javascript"></script>' ;
    $js .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu({style: 'dropdown', maxHeight: 300 }); });  </script>\n" ;

    $js .= $jqgridJs ;
    require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Ressources/ressource.class.php') ;
    $ress = new Ressource( $db ) ;
    llxHeader( $js, $langs->trans( "Ressources" ), "Ressources", "1" ) ;

//Affiche un jqgrid de suivie


    print '<div class="tabBar">' ;

    print "<table width=100%>" ;
    print "<tr><td style='width:150px;'>" ;


    print '  <div class="treeheader ui-state-default ui-widget ui-corner-top ui-widget-header"  style="padding-bottom: 4px; padding-left: 4px; padding-top: 4px; margin-top: 2px;" >Cat&eacute;gories</div>' ;
    print '<div style="overflow:auto; height: 551px; width: 200px; border: 0px;" class=" ui-widget ui-widget-content ">' ;
    print '<div id="tree" class="ui-widget ui-widget-content ui-corner-bottom" style="height: 547px; font-size: 0.8em; ">' ;

    print '  <ul class="treeview ui-widget treeview" id="tree" style="overflow-x: auto; min-height: 518px; border-bottom: 0px;">' ;
    print '  <li nowrap="on" class="lastCollapsable"><div class="hitarea collapsable-hitarea lastCollapsable-hitarea"></div><a id="catRes-1" href="javascript:showRessource(-1)"><strong>Ressources</strong></a>' ;

    $ress->parseRecursiveCat() ;

    print "</li>" ;
    print '</ul>' ;
    print '<div  class="scroll  ui-widget ui-widget-content ui-state-default ui-jqgrid-pager ui-corner-bottom" style="text-align: center;  width: 197px; height: 24px;">' ;
    print '<div  class="ui-pager-control" role="group" style="font-size: 1.1em; border: 1px none,font-weight: bold; outline: none ;">' ;
    if ( $user->rights->SynopsisRessources->SynopsisRessources->Admin )
    {
	print '<table><tbody>' ;
	print '<td id="add_cat" class="ui-pg-button ui-corner-all" title="Ajouter une cat&eacute;gorie">' ;
	print '<div class="ui-pg-div">' ;
	print '<span class="ui-icon ui-icon-plus"/>' ;
	print '</div>' ;
	print '</td>' ;
	print '<td id="mod_cat" class="ui-pg-button  ui-corner-all" title="Modifier une cat&eacute;gorie">' ;
	print '<div class="ui-pg-div" style="font-size: 1.1em;font-weight: bold; outline: none ;">' ;
	print '<span class="ui-icon ui-icon-pencil"/>' ;
	print '</div>' ;
	print '</td>' ;
	print '<td id="del_cat" class="ui-pg-button ui-corner-all" title="Effacer une cat&eacute;gorie">' ;
	print '<div class="ui-pg-div" style=" font-size: 1.1em;font-weight: bold; outline: none ">' ;
	print '<span class="ui-icon ui-icon-trash"/>' ;
	print '</div>' ;
	print '</td>' ;
	print '</tbody></table>' ;
    }
    print '</div>' ;
    print '</div>' ;
    print '</div>' ;
    print '</div>' ;

    print "<td>" ;
    print '<table id="gridListRessources" class="scroll" cellpadding="0" cellspacing="0"></table>' ;
    print '<div id="gridListRessourcesPager" class="scroll" style="text-align:center;"></div>' ;
    print "</div>" ;

    print "</td></tr></table>" ;


    print ' <div class="contextMenu" id="MenuJqGrid">' ;

    print '        <ul>' ;
    print '            <li id="reserver">' ;
    print '                <img height=16 width=16 src="' . DOL_URL_ROOT . '/theme/auguria/img/object_calendar.png" />' ;
    print '                R&eacute;server</li>' ;
    print '            <li id="editer">' ;
    print '                <img height=16 width=16 src="' . DOL_URL_ROOT . '/theme/auguria/img/button_edit.png" />' ;
    print '                Editer</li>' ;
    print '            <li id="supprimer">' ;
    print '                <img src="' . DOL_URL_ROOT . '/theme/auguria/img/stcomm-1.png" />' ;
    print '                Supprimer</li>' ;

    print '        </ul>' ;
    print '    </div>' ;

    print '<div id="dialogNewCat" style="display: none">' ;
    print " <form name='formDialogAdd' id='formDialogAdd'>" ;
    print "     <table><tbody>" ;
    print "         <tr>" ;
    print "             <td>Parent" ;
    print "             </td>" ;
    print "             <td>" ;
    print "                 <select name='addparentID' id='addparentID' >" ;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE isGroup = 1 " ;
    $sql = $db->query( $requete ) ;
    print "<option value=''>Sélection -></option>" ;
    print "<option value='-1'>Racine</option>" ;
    if ( $sql )
    {
	while ( $res = $db->fetch_object( $sql ) )
	{
	    print "<option value='" . $res->id . "'>" . htmlentities( $res->nom ) . "</option>" ;
	}
    }
    print "                 </select>" ;
    print "             </td>" ;
    print "         </tr>" ;
    print "         <tr>" ;
    print "             <td>Nom" ;
    print "             </td>" ;
    print "             <td>" ;
    print "                 <input name='addnomCat' id='addnomCat'></input>" ;
    print "             </td>" ;
    print "         </tr>" ;
    print "     </tbody></table>" ;
    print " </form>" ;
    print '</div>' ;


    print '<div id="dialogModCat" style="display: none">' ;
    print " <form name='formDialogMod' id='formDialogMod'>" ;
    print "     <table><tbody>" ;
    print "         <tr>" ;
    print "             <td>Parent" ;
    print "             </td>" ;
    print "             <td>" ;
    print "                 <select name='modparentID' id='modparentID' >" ;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE isGroup = 1 " ;
    $sql = $db->query( $requete ) ;
    print "<option value=''>Sélection -></option>" ;
    print "<option value='-1'>Racine</option>" ;
    while ( $res = $db->fetch_object( $sql ) )
    {
	print "<option value='" . $res->id . "'>" . htmlentities( $res->nom ) . "</option>" ;
    }
    print "                 </select>" ;
    print "             </td>" ;
    print "         </tr>" ;
    print "         <tr>" ;
    print "             <td>Nom" ;
    print "             </td>" ;
    print "             <td>" ;
    print "                 <input name='modnomCat' id='modnomCat'></input>" ;
    print "             </td>" ;
    print "         </tr>" ;
    print "     </tbody></table>" ;
    print " </form>" ;
    print '</div>' ;



    print '<div id="dialogDelCat" style="display: none">' ;
    print "<p>&Ecirc;tes vous sur de vouloir effacer cette cat&eacute;gorie et tout son contenu ?</p>" ;
    print '</div>' ;


    print '<div id="dialogResa" style="display: none">' ;
    print "<table>" ;
    print "<tbody>" ;
    print "<tr><td>Projet</td><td><SELECT>" ;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_view WHERE fk_statut < 3" ;
    $sql = $db->query( $requete ) ;
    print "<option value='-1'>Selection -></option>" ;

    while ( $res = $db->fetch_object( $sql ) )
    {
	print "<option value='" . $res->id . "'>" . $res->title . "</option>" ;
    }
    print "</SELECT></td></tr>" ;
    print "<tr><td>Imputer &agrave; : </td><td><SELECT>" ;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 ORDER BY firstname, lastname" ;
    $sql = $db->query( $requete ) ;
    print "<option value='-1'>Selection -></option>" ;
    while ( $res = $db->fetch_object( $sql ) )
    {
	print "<option value='" . $res->rowid . "'>" . $res->firstname . " " . $res->name . "</option>" ;
    }
    print "</SELECT></td></tr>" ;
    print "<tr><td>Date de d&eacute;but</td><td><input class='datePicker promoteZ'></input></td></tr>" ;
    print "<tr><td>Date de fin</td><td><input class='datePicker promoteZ'></input></td></tr>" ;
    print "</tbody>" ;
    print "</table>" ;
    print '</div>' ;

llxFooter('$Date: 2008/09/10 09:46:02 $ - $Revision: 1.57.2.1 $');

    //
?>
