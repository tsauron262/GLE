<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1. Created on : 29 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : riskDetail-expert.php
  * GLE-1.1
  */

  //TODO
  //prob efface => prend parent ou enfant selon les cas

$selectedTab = "";
if (isset($_REQUEST['selectedTab']))
{

    $selectedTab = intval(preg_replace('/[\W]$/',"",$_REQUEST['selectedTab']));
}

require_once("../../main.inc.php");


$csspath = DOL_URL_ROOT.'/Synopsis_Common/css/';
$jspath = DOL_URL_ROOT.'/Synopsis_Common/jquery/';
$jqueryuipath = DOL_URL_ROOT.'/Synopsis_Common/jquery/ui/';

$header = '<link rel="stylesheet" type="text/css" href="'.$csspath.'ui.all.css" />'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery-1.3.2.js"></script>'."\n";
$header .= ' <script src="'.$jqueryuipath.'/ui.selectmenu.js" type="text/javascript"></script>';
$header .= " <script > jQuery(document).ready(function(){ jQuery('select').selectmenu(); });  </script>\n";

$header .= '<link rel="stylesheet" href="'.$csspath.'flick/jquery-ui-1.7.2.custom.css" type="text/css" />'."\n";


$header .= '<link rel="stylesheet" href="'.$csspath.'jquery.treeview.css" type="text/css" />'."\n";



$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.core.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.draggable.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.droppable.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.tabs.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.dialog.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.accordion.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jqueryuipath.'ui.slider.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.validate.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.bgiframe.js"></script>'."\n";
$header .= '<script language="javascript" src="'.$jspath.'jquery.tooltip.js"></script>'."\n";

$header .= '<script language="javascript" src="'.$jspath.'jquery.treeview.js"></script>'."\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.core.js"></script>'."\n";

$header .= '<script language="javascript" src="'.$jqueryuipath.'effects.slide.js"></script>'."\n";

//TODO sortable

$project_id = $_REQUEST["project_id"];


$header .= '<script language="javascript"> var projId = '.$project_id.'</script>'."\n";


//llxHeader($header,"Projet - Cout","",1);
print $header;
?>

<style type="text/css">
    body{ min-width: 1100px; }
    #gallery {  min-height: 580px; height: 580px; width: 190px; }
    .gallery.custom-state-active { background: #eee; }
    .gallery li { border: 0;  width: 96px; padding: 0; margin: 0; padding-left: 13pt; text-align: center; padding-top: 3px;padding-bottom: 0px;  margin-top: -5px;  }
    .gallery li h5 { margin: 0 0 0.4em; cursor: move; text-transform: capitalize; -moz-border-radius-topright: 8px; -webkit-border-top-right-radius: 8px; }
    .gallery li a { float: right; }
    .gallery li a.ui-icon-zoomin { float: left; }
    .gallery li img { width: 100%; cursor: move; }
    .leftpart { top:0; left: 0; padding: 5pt; height: 100%;  width: 190px;  z-Index: 6000; background-color: #FFFFFF; }
    .trashupper { left: 280px; }

    .trash { float: left; width: 280px; min-height: 350px; background-color: #CCCCCC; background-image: none;}
    .trash h4 { line-height: 16px; margin: 0 0 0.4em; }
    .trash h5 .ui-icon-arrowrefresh-1-w { float: left; }
    .trash .ui-icon { float: left; }
    .trash div { max-width: 230px; overflow-x: auto;}
    .trash ul { margin-left: 10px;  background: transparent; }
    .trash li h5 { margin: 0 0 0.4em; cursor: move; text-transform: capitalize; -moz-border-radius-topright: 8px; -webkit-border-top-right-radius: 8px; }
    .trash .gallery h5 { display: none; }
    .trash li { border: 0;  width: 96px; padding: 0; margin: 0; padding-left: 13pt; text-align: center; padding-top: 3px;padding-bottom: 0px;  margin-top: -5px;  }

    #tree { vertical-align: top; margin-top:0; padding-top:0; max-height: 100%; overflow-y: auto;}
    .head { width:100%; }
    .ui-state-highlight {
        background: #FFFFFF ;
        background-color: #FFFFFF ;
        backgroung-image: none ;
    }
    .editInPlace{
        cursor: pointer;
    }
    .fs {
        width: 250px;
        padding: 10pt;
        margin-left: 2em;
    }
    .slide {
        height: 18px;
    }
    .promoteZBig{
        z-index: 1000000;
        position: fixed;
        background-color: #EFEFEF;
        padding-left: 10pt;
        padding-right: 10pt;
        padding-top: 2pt;
        padding-bottom: 2pt;
        -moz-border-radius: 8px;
        -webkit-border-radius: 8px;
        -moz-outline-radius: 8px;
        -webkit-outline: 0px;
        border: 1px solid #0073EA;
        -moz-outline: 3px solid #EFEFEF;
        min-width: 200px;
    }
</style>
<script type="text/javascript">
    var currentId = "";
    var coutTache=0;
    var occurence=0;
    var importance = 0;
documentReady();
    function documentReady() {

        $("#add_cat").click(function(ev)
        {
            $('#addDialog').dialog('open');
        }
        );
        $("#mod_cat").click(function(ev)
        {
            var task = $('#trash'+currentId).text().replace(/^[\s]*/,'');
            $("#modCatName").val(task);
            $('#modDialog').dialog('open');

        }
        );
        $("#del_cat").click(function(ev)
        {
            $('#delDialog').dialog('open');
        }
        );

        $('#addDialog').dialog({
            autoOpen: false,
            buttons: {
                Ok: function(){
                    post = "projId="+projId;
                    var name = $("#addCatName").val();
                    post += "&name="+name;
                    post += "&oper=add";
                    if ($("#addDialogForm").validate({
                                    rules: { addCatName: {
                                                required: true,
                                                minlength: 5,
                                            },
                                          },
                                messages: {
                                            addCatName: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                        }
                                }).form())
                                {
                                    $.ajax({
                                            async: false,
                                            url: "ajax/risk_group-ajax.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if ($(msg).find('ok').text()+"x" != "x")
                                                {
                                                    location.reload();
                                                } else {
                                                    alert ($(msg).find('ko').text());
                                                }
                                            }
                                        });
                                }            }
            },
            modal: true,
            zIndex: 1000006,
            title: 'Ajouter un groupe',
        });
        $('#modDialog').dialog({
            autoOpen: false,
            buttons: {
                Ok: function(){
                    post = "projId="+projId;
                    var name = $("#modCatName").val();
                    post += "&name="+name;
                    post += "&id="+currentId;
                    post += "&oper=mod";
                    if ($("#modDialogForm").validate({
                                    rules: { modCatName: {
                                                required: true,
                                                minlength: 5,
                                            },
                                          },
                                messages: {
                                            modCatName: {
                                                required : "<br>Champs requis",
                                                minlength : "<br>Le nom doit faire au moins 5 caract&egrave;res",
                                            },
                                        }
                                }).form())
                                {
                                    $.ajax({
                                            async: false,
                                            url: "ajax/risk_group-ajax.php",
                                            type: "POST",
                                            data: post,
                                            success: function(msg){
                                                if ($(msg).find('ok').text()+"x" != "x")
                                                {
                                                    location.reload();
                                                } else {
                                                    alert ($(msg).find('ko').text());
                                                }
                                            }
                                        });
                                }            }
            },
            modal: true,
            zIndex: 1000006,
            title: 'Modifier un groupe',
        });
        $('#delDialog').dialog({
            autoOpen: false,
            buttons: {
                Ok: function(){
                    post = "projId="+projId;
                    post += "&oper=del";
                    post += "&id="+currentId;
                    $.ajax({
                        async: false,
                        url: "ajax/risk_group-ajax.php",
                        type: "POST",
                        data: post,
                        success: function(msg){
                            if ($(msg).find('ok').text()+"x" != "x")
                            {
                                location.reload();
                            } else {
                                alert ($(msg).find('ko').text());
                            }
                        }
                    });

                },
                Cancel: function(){
                    $('#delDialog').dialog('close');
                }
            },
            modal: true,
            title: 'Effacer un groupe',
        })
        $(".accordion").accordion(
        {
            animated: 'slide',
            clearStyle: true  ,
            height: 450,
            navigation: true,
            collapsible: true,
            change: function(event,ui){
                if (ui && ui.newHeader &&  ui.newHeader.attr('id'))
                {
                    var id = ui.newHeader.attr('id').replace(/^[a-zA-Z]*/,"");
                    currentId = id; //global
                    //reset draggable
                    resetList(id);
                } else {
                    currentId = ''; //global
                }
            },
            <?php if ($selectedTab.'x' != "x") { print 'active: '.$selectedTab.' ,'; } else { print 'active: "false" ,'; } ?>
        });
        $(".ui-accordion-header a").each(function(){
            $(this).css('font-size','13px');
            $(this).css('padding','0.2em 0.2em 0.2em 2.2em');
        });
        dropInit();
        <?php if ($selectedTab.'x' != "x") { print 'alert('.$selectedTab.'); var id = $(".ui-state-active").attr("id").replace(/^[a-zA-Z]*/,"");  currentId = id; resetList(id); '; }  ?>
}//fin de document.ready

function dropInit (){
    setLoading();
    resetTooltip();

    // there's the gallery and the trash
    var $gallery = $('#gallery'), $trash = $('.trash');

<?php
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
             WHERE fk_projet = ".$project_id;
$sql1 = $db->query($requete);
if ($db->num_rows($sql1)> 0)
{
    while ($res=$db->fetch_object($sql1))
    {
        // let the trash be droppable, accepting the gallery items
        print '$("#trash'.$res->id.'").droppable({';
            print "accept: '#gallery > li, #gallery * li',";
            print "activeClass: 'ui-state-highlight',";
            print "drop: function(ev, ui) {";
            print '             deleteImage(ui.draggable,this,ev,ui); ';
            print "             resetList(currentId);";
       print " }";
       print "});";
    }
}

?>

    // let the gallery items be draggable
   $('li',$gallery).draggable({
                cancel: 'a.ui-icon',// clicking an icon won't initiate dragging
                revert: 'invalid', // when not dropped, the item will revert back to its initial position
                containment: 'document', // stick to demo-frame if present
                helper: 'clone',
                cursor: 'move',
                iframefix: true,
                zIndex: 100000,
                refreshPositions: true,
                distance: 10,
            });


    // let the trash items be draggable
   $('li',".trash").draggable({
                cancel: 'a.ui-icon',// clicking an icon won't initiate dragging
                revert: 'invalid', // when not dropped, the item will revert back to its initial position
                containment: 'document',
                helper: 'clone',
                cursor: 'move',
                iframefix: true,
                zIndex: 100010,
                refreshPositions: true,
                distance: 10,
            });

    // let the gallery be droppable as well, accepting items from the trash
    $gallery.droppable({
        accept: '.trash li',
        activeClass: 'custom-state-active',
        drop: function(ev, ui) {
            resetList(currentId);
            recycleImage(ui.draggable,ui.helper,ui);
        }
    });

    // image deletion function
    var recycle_icon = '<a href="#" title="Supprimer du groupe" class="ui-icon ui-icon-arrowrefresh-1-w">Recycle image</a>';
    function deleteImage($item,$droppableobj,$ev,$ui) {
        var $dropI= $droppableobj;
        var $list = $('ul',$dropI).length ? $('ul',$dropI) : $('<ul class="gallery ui-helper-reset"/>').appendTo($dropI);
        $item.append(recycle_icon).appendTo($list).fadeIn(function() {
            $item.animate({ width: '148px' }).find('h5').animate({ height: '18px' });
        });
        //send to ajax
        var taskId =  $($item).find('h5').attr('id').replace(/[a-zA-Z]*/,'');
        var groupId = $($droppableobj).attr('id').replace(/[a-zA-Z]*/,'');

        var post = "oper=addToGroup";
            post += "&taskId="+taskId;
            post += "&groupId="+groupId;
        $.ajax({
            async: false,
            url: "ajax/risk_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                if ($(msg).find('ok').text()+"x" != "x")
                {
                    resetCount(true);
                    //alert ($(msg).find('ok').text());
                } else {
                    //alert ($(msg).find('ko').text());
                }
            }
        });
    }

    // image recycle function
    var trash_icon = '<a href="#" title="Supprimer du groupe" class="ui-icon ui-icon-trash" style="display: none">Supprimer</a>';
    function recycleImage($item,$droppableobj,$ev) {
        var $dropI= $droppableobj;
        $item.fadeOut(function() {
            $item.find('a.ui-icon-arrowrefresh-1-w').remove();
            $item.css('width','96px').append(trash_icon).css('display','none').find('img').css('height','72px').end().appendTo($gallery).fadeIn();
            $item.animate({ width: '96px' }).find('h5').animate({ height: '18px' });
        });
        //send to ajax
        var taskId =  $dropI[0].firstChild.id.replace(/[a-zA-Z-]*/,'');
        if (taskId + "x" == "x")
        {
            taskId = $dropI[0].id.replace(/[a-zA-Z-]*/,'');
        }

        var groupId = currentId;

        var post = "oper=remFromGroup";
            post += "&taskId="+taskId;
            post += "&groupId="+groupId;

        $.ajax({
            async: false,
            url: "ajax/risk_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                if ($(msg).find('ok').text()+"x" != "x")
                {
                    //alert ($(msg).find('ok').text());
                    resetCount(true);
                } else {
                    //alert ($(msg).find('ko').text());
                }
            }
        });
    }

//    // resolve the icons behavior with event delegation
    $('ul.trash > li').click(function(ev) {
        var $item = $(this);
        var $target = $(ev.target);

        if ($target.is('a.ui-icon-trash')) {
            deleteImage($item);
        } else if ($target.is('a.ui-icon-zoomin')) {
            viewLargerImage($target);
        } else if ($target.is('a.ui-icon-arrowrefresh-1-w')) {
            recycleImage($item,$item,ev);
            resetList();

        }

        return false;
    });

    $(".slide").slider({
         animate: true ,
         max: 100,
         min: 0,
         range: 'min',
         step: 5,
         change: function(event, ui)
         {
             var value = ui.value;
             var id = event.target.id.replace(/[\W]{1}[0-9]*$/,'');
             $('#'+id+'Str'+currentId).text(value); //affiche le pourcentage
             $('#'+id+'Val'+currentId).val(value); // stock la valeur dans le dom
             var slide1Val = parseInt($("#slide1Val"+currentId).val()); //occurence
             var slide2Val = parseInt($("#slide2Val"+currentId).val()); // importance
             if (! slide1Val > 0)
             {
                slide1Val = 0;
             }
             if (! slide2Val > 0)
             {
                slide2Val = 0;
             }
             var TmpCost = coutTache;
                 TmpCost = TmpCost.replace(/\ /,'').replace(/,/,".");
             var coutRisk = parseFloat(TmpCost) * (slide1Val + slide2Val)/200;

            var desc = $("#description"+currentId).text();

             //format Price:
                 coutRisk = CurrencyFormatted(coutRisk);

             $('#totalRisk'+currentId).html(coutRisk+" &euro;");
             //stor edata ajax
             var post = "oper=setRisk";
                 post += "&occurence="+slide1Val;
                 post += "&importance="+slide2Val;
                 post += "&description="+desc;
                 post += "&groupId="+currentId;

            $.ajax({
                    async: false,
                    url: "ajax/risk_group-ajax.php",
                    type: "POST",
                    data: post,
                    success: function(msg){
                        if ($(msg).find('ok').text()+"x" != "x")
                        {
                            //location.reload();
                        } else {
                            alert ($(msg).find('ko').text());
                        }
                    }
                });
         }
    });
    $('.editInPlace').click(function(){
        editInplace(this);
    });
                unSetLoading();

}//fin de drop init

function jQouterHTML (obj) {
    return $('<div>').append( obj.eq(0).clone() ).html();
};

function resetList(pId)
{
    $.getJSON('ajax/risk_group-json.php?oper=notin&curGrp='+currentId,
    function(data,status){
        if (status == 'success')
        {
            longHtml = '<ul id="gallery" class="gallery ui-helper-reset ui-helper-clearfix" style="width: 190px; z-index: 10000">'
            if (data && data.rows)
            {
                var Arr = new Array();
                var ArrBis = new Array();
                var Arr1 = new Array();
                var Arr2 = new Array();
                for (var i in data.rows)
                {
                    var tmpHtml = '<li class="ui-widget-content ui-corner-tr "  id="taskLiId-'+data.rows[i].id +'">';
                    if (data.rows[i].groupDet.type == 3)
                    {

                        tmpHtml += '<h5 class="ui-widget-header" id="task' + data.rows[i].id + '" style="text-tranform: capitalize; white-space: nowrap; background: none; background-color: #FFFFAA;">' + data.rows[i].groupDet.name +'<div class="title" style="display:none;">'+data.rows[i].groupDet.desc+'</div><div class="titleStr" style="display:none;">'+data.rows[i].groupDet.name+'</div></h5>';
                    } else {

                        tmpHtml += '<h5 class="ui-widget-header" id="task' + data.rows[i].id + '" style="text-tranform: capitalize; white-space: nowrap; ">' + data.rows[i].groupDet.name +'<div class="title" style="display:none;">'+data.rows[i].groupDet.desc+'</div><div class="titleStr" style="display:none;">'+data.rows[i].groupDet.name+'</div></h5>';
                    }
                    tmpHtml += '</li>';
                    Arr[data.rows[i].id]=$(tmpHtml);
                    ArrBis[data.rows[i].id]=$(tmpHtml);
                    Arr1[data.rows[i].id]=data.rows[i].groupDet.fk_task_parent;
                    Arr2[data.rows[i].id]=data.rows[i].groupDet.level;
                }
                //Parent Display
                for (var i in Arr1)
                {
                    var parentId = Arr1[i];
                    if (Arr1[i]+"x" != "x" && Arr[parentId])
                    {
                        // on le place dans jQuery
                          tmp = Arr[parentId];
                          //si y a des ul dedans au bon level
                          count = tmp.find('.level'+Arr2[i]).length;
                          if (count > 0)
                          {
                              // si oui
                              Arr[parentId].find('.level'+Arr2[i]).append(Arr[i]);
                              Arr[i]=false;
                          }  else {
                                // sinon
                                var ul = $("<ul class = 'level" + Arr2[i] + "' >"+jQouterHTML(Arr[i])+"</ul>");
                                Arr[parentId].append(jQouterHTML(ul));
                                Arr[i]=false;
                          }
                    } else if (Arr1[i]+"x" != "x" && Arr[parentId] == false && ArrBis[parentId])
                    {
                        var directParent = parentId;
                        //Dans ce cas c'est dans le DOM
                        for (var j in Arr)
                        {
                            if (Arr[parentId] == false)
                            {
                                while (Arr[parentId] == false)
                                {
                                    //get Parent Of the Parent
                                    parentId = Arr1[parentId];
                                }
                            }
                            if (Arr[j] != false)
                            {
                                count = 0;
                                Arr[j].each(function(){
                                    if ($(this).attr('id')=='taskLiId-'+parentId)
                                    {
                                        count = 1;
                                    }
                                });
                                var count1 = Arr[j].find('#taskLiId-'+directParent).find('.level'+Arr2[i]).length;
                                if (count > 0 && count1 >0)
                                {

                                    Arr[j].find('#taskLiId-'+directParent).find('.level'+Arr2[i]).append(jQouterHTML(ArrBis[i]));
                                    Arr[i]=0;
                                } else  if( count >0  && count1 == 0){

                                    var ul = $("<ul style='display:none;' class = 'level" + Arr2[i] + "' >"+jQouterHTML(ArrBis[i])+"</ul>");
                                    Arr[j].find('#taskLiId-'+directParent).append(jQouterHTML(ul));
                                    Arr[i]=0;
                                }
                            }
                        }
                    }
                }

                for (var i in Arr)
                {
                    if (Arr[i] != false)
                    {
                        var prot = $("<div></div>").append(Arr[i]);
                        longHtml += prot.html();
                    }

                }
                longHtml+='</ul>';
                //alert(longHtml);
                $('#gallery').replaceWith(longHtml);
                resetTooltip();
                $("#gallery").treeview( { animated: "fast",  });
                dropInit();
            }
        } else {
            alert ('Erreur de fonctionnement. Merci de contacter le support.');
        }
    }); // fin de getJSON

    //affiche les tasks dans le panier
    $.getJSON('ajax/risk_group-json.php?oper=in&curGrp='+currentId,
        function(data,status){
            if (status == 'success')
            {
                longHtml = '<ul id="trashUL'+currentId+'" class="trash ui-helper-reset ui-helper-clearfix" style="width: 190px; z-index: 10000">'
                if (data && data.rows)
                {
                    var Arr = new Array();
                    var ArrBis = new Array();
                    var Arr1 = new Array();
                    var Arr2 = new Array();

                for (var i in data.rows)
                {
                        var tmpHtml = '<li class="ui-widget-content ui-corner-tr ui-draggable" id="taskLiId-'+data.rows[i].id +'" style="min-width: 148px; display: list-item;">';
                if (data.rows[i].groupDet.type == 3)
                {
                    tmpHtml += '<h5   title="' + data.rows[i].groupDet.desc  + ' "   id="task' + data.rows[i].id + '" class="ui-widget-header" style="white-space: nowrap; text-transform: capitalize; background:none; background-color: #FFFFAA">'+ data.rows[i].groupDet.name +'<div class="title" style="display:none;">'+data.rows[i].groupDet.desc+'</div><div class="titleStr" style="display:none;">'+data.rows[i].groupDet.name+'</div></h5>';
                    tmpHtml += '<a class="ui-icon ui-icon-arrowrefresh-1-w" style="float: left; margin-top: -24px; " title="Supprimer du groupe" href="#"></a></h5>';
                } else {
                    tmpHtml += '<h5   title="' + data.rows[i].groupDet.desc  + ' "   id="task' + data.rows[i].id + '" class="ui-widget-header" style="white-space: nowrap; text-transform: capitalize;">'+ data.rows[i].groupDet.name +'<div class="title" style="display:none;">'+data.rows[i].groupDet.desc+'</div><div class="titleStr" style="display:none;">'+data.rows[i].groupDet.name+'</div></h5>';
                    tmpHtml += '<a class="ui-icon ui-icon-arrowrefresh-1-w" style="float: left; margin-top: -24px; " title="Supprimer du groupe" href="#"></a></h5>';
                }
                        tmpHtml += '</li>';
                    Arr[data.rows[i].id]=$(tmpHtml);
                    ArrBis[data.rows[i].id]=$(tmpHtml);
                    Arr1[data.rows[i].id]=data.rows[i].groupDet.fk_task_parent;
                    Arr2[data.rows[i].id]=data.rows[i].groupDet.level;
                }

                //Parent Display
                for (var i in Arr1)
                {
                    var parentId = Arr1[i];
                    if (Arr1[i]+"x" != "x" && Arr[parentId])
                    {
                        // on le place dans jQuery
                          tmp = Arr[parentId];
                          //si y a des ul dedans au bon level
                          count = tmp.find('.level'+Arr2[i]).length;
                          if (count > 0)
                          {
                              // si oui
                              Arr[parentId].find('.level'+Arr2[i]).append(Arr[i]);
                              Arr[i]=false;
                          }  else {
                                // sinon
                                var ul = $("<ul class = 'level" + Arr2[i] + "' >"+jQouterHTML(Arr[i])+"</ul>");
                                Arr[parentId].append(jQouterHTML(ul));
                                Arr[i]=false;
                          }
                    } else if (Arr1[i]+"x" != "x" && Arr[parentId] == false && ArrBis[parentId])
                    {
                        var directParent = parentId;
                        //Dans ce cas c'est dans le DOM
                        for (var j in Arr)
                        {
                            if (Arr[parentId] == false)
                            {
                                while (Arr[parentId] == false)
                                {
                                    //get Parent Of the Parent
                                    parentId = Arr1[parentId];
                                }
                            }
                            if (Arr[j] != false)
                            {
                                count = 0;
                                Arr[j].each(function(){
                                    if ($(this).attr('id')=='taskLiId-'+parentId)
                                    {
                                        count = 1;
                                    }
                                });
                                var count1 = Arr[j].find('#taskLiId-'+directParent).find('.level'+Arr2[i]).length;
                                if (count > 0 && count1 >0)
                                {

                                    Arr[j].find('#taskLiId-'+directParent).find('.level'+Arr2[i]).append(jQouterHTML(ArrBis[i]));
                                    Arr[i]=0;
                                } else  if( count >0  && count1 == 0){

                                    var ul = $("<ul style='display:none;' class = 'level" + Arr2[i] + "' >"+jQouterHTML(ArrBis[i])+"</ul>");
                                    Arr[j].find('#taskLiId-'+directParent).append(jQouterHTML(ul));
                                    Arr[i]=0;
                                }
                            }
                        }
                    }
                }
                for (var i in Arr)
                {
                    if (Arr[i] != false)
                    {
                        var prot = $("<div></div>").append(Arr[i]);
                        longHtml += prot.html();
                    }

                }
                longHtml+='</ul>';
                //alert(longHtml);
                $('#trashUL'+currentId).replaceWith(longHtml);
                resetTooltip();
                $("#trash"+currentId).treeview( { animated: "fast",  });
                dropInit();
                resetCount(false,data);

            }

            } else {
                alert ('Erreur de fonctionnement. Merci de contacter le support.');
            }
        }); // fin de getJSON
}

function makeTree()
{
    //Treeview
    $("#gallery").treeview( { animated: "fast" });
    $("#tree").treeview( { animated: "fast" });


}
function resetTooltip()
{
    $('#gallery h5').each(function(){
       $(this).tooltip({
            bodyHandler: function() {
                var html = "<b>"+ $(this).find('.titleStr').text() +"</b>";
                    html += '<hr>'+$(this).find('.title').text();
                return html;
            },
            showURL: false,
            track: true,
            delay: 100,
            showBody: " - ",
            fade: 250,
            extraClass: "promoteZBig",
            left: 40,
            top: -30,
        });
    });



    $('.trash h5').tooltip({
        bodyHandler: function() {
            var title = "";
            var html = "<b>"+ $(this).find('.titleStr').text()+"</b>";
                html += '<hr>'+$(this).find('.title').text();
            return html;
        },
        showURL: false,
        track: true,
        delay: 100,
        showBody: " - ",
        fade: 250,
        extraClass: "promoteZBig",
        left: 40,
        top: -30,
    });

}

function resetCount(getIt,pData)
{
    if (getIt)
    {
        $.getJSON('ajax/risk_group-json.php?oper=in&curGrp='+currentId,
        function(data,status){
            if (status == 'success')
            {
                if (data)
                {
                        occurence = data.data.occurence;
                        importance = data.data.importance;
                    var total = data.data.total;
                    var totalRisque = data.data.totalRisque;
                    var description = data.data.description;
                        description = description.replace(/^[\s]*/,"").replace(/[\s]*$/,"");
                    if (description + "x" == "x") description = "Cliquer pour m'&eacute;diter";
                    if (total + "x" == "x"){ total = 0 ;}
                    coutTache=total;
                    $('#trash'+currentId).parent().find('span').each(function(){
                        if ($(this).attr('id')=='totalGroup'+currentId)
                        {
                            $(this).html(total+" &euro;");
                        }
                    });
                    if (totalRisque + "x" == "x"){ totalRisque = 0 ;}
                    $('#trash'+currentId).parent().find('span').each(function(){
                        if ($(this).attr('id')=='totalRisk'+currentId)
                        {
                            $(this).html(totalRisque+" &euro;");
                        }
                    });
                    //set slider Value:
                    $('#slide1-'+currentId).slider('option', 'value', occurence);
                    $('#slide2-'+currentId).slider('option', 'value', importance);
                    $('#slide1Str'+currentId).text(occurence); //affiche le pourcentage
                    $('#slide2Str'+currentId).text(importance); //affiche le pourcentage
                    $('#slide1Val'+currentId).val(occurence); // stock la valeur dans le dom
                    $('#slide2Val'+currentId).val(importance); // stock la valeur dans le dom

                    $('#description'+currentId).html(description);
                }
                dropInit();
            } else {
                alert ('Erreur de fonctionnement. Merci de contacter le support.');
            }
        }); // fin de getJSON
    } else {
        if (pData)
        {
            var data = pData;
                occurence = data.data.occurence;
                importance = data.data.importance;
            var total = data.data.total;
            var totalRisque = data.data.totalRisque;
            var description = data.data.description;
                description = description.replace(/^[\s]*/,"").replace(/[\s]*$/,"");
            if (description + "x" == "x") description = "Cliquer pour m'&eacute;diter";
            if (total + "x" == "x"){ total = 0 ;}
            coutTache=total;
            $('#trash'+currentId).parent().find('span').each(function(){
                if ($(this).attr('id')=='totalGroup'+currentId)
                {
                    $(this).html(total+" &euro;");
                }
            });
            if (totalRisque + "x" == "x"){ totalRisque = 0 ;}
            $('#trash'+currentId).parent().find('span').each(function(){
                if ($(this).attr('id')=='totalRisk'+currentId)
                {
                    $(this).html(totalRisque+" &euro;");
                }
            });
            //set slider Value:
            $('#slide1-'+currentId).slider('option', 'value', occurence);
            $('#slide2-'+currentId).slider('option', 'value', importance);
            $('#slide1Str'+currentId).text(occurence); //affiche le pourcentage
            $('#slide2Str'+currentId).text(importance); //affiche le pourcentage
            $('#slide1Val'+currentId).val(occurence); // stock la valeur dans le dom
            $('#slide2Val'+currentId).val(importance); // stock la valeur dans le dom
            $('#description'+currentId).html(description);

        }
    }

}
function setLoading()
{
    $("#loading").css('display',"block");
}
function unSetLoading()
{
    $("#loading").css('display',"none");
}

function CurrencyFormatted(amount)
{
    var delimiter = "&nbsp;"; // replace comma if desired
    var i = parseInt(amount);
    if(isNaN(i)) { return ''; }
    var minus = '';

    var j = parseFloat(amount) - i;
    var mm = new String(j*100);
    mmm=mm.replace(/,/,".");

    mmm = ","+mmm;
    if (mmm.length == 2)
    {
        mmm += "0";
    } else if (mmm.length == 1){
        mmm += "00";
    }



    if(i < 0) { minus = '-'; }

    i = Math.abs(i);
    var n = new String(i);
    var a = [];
    while(n.length > 3)
    {
        var nn = n.substr(n.length-3);
        a.unshift(nn);
        n = n.substr(0,n.length-3);
    }
    if(n.length > 0) { a.unshift(n); }
    n = a.join(delimiter);
    amount = minus + n;
    return amount + mmm;

}

var eipText="";
var eipObj;
function editInplace(obj)
{
    eipObj=$("#description"+currentId).clone(true);
    $(obj).replaceWith('<textarea  onBlur="editInPlaceRet(this)" style="min-height:50px;width:250px;">'+eipText+'</textarea>');
}
function editInPlaceRet(obj)
{
    eipText = $(obj).val();
    eipObj.text(eipText);
    $('#descStr').val(eipText);
    $(obj).replaceWith(eipObj);

     //stor edata ajax
     var post = "oper=setRisk";
         post += "&occurence="+occurence;
         post += "&importance="+importance;
         post += "&description="+eipText;
         post += "&groupId="+currentId;

    $.ajax({
            async: false,
            url: "ajax/risk_group-ajax.php",
            type: "POST",
            data: post,
            success: function(msg){
                if ($(msg).find('ok').text()+"x" != "x")
                {
                    //location.reload();
                } else {
                    alert ($(msg).find('ko').text());
                }
            }
        });
}

//var coutTache = ".$coutTache.";
        </script>
<table width=100%>
<tr><td width=205 style='vertical-align: top;'>

        <div class="leftpart ui-widget ui-helper-clearfix" >
            <ul id="gallery" class="gallery ui-helper-reset ui-helper-clearfix" style="width: 150px; z-index: 10000">

<?php
$requete = "SELECT ".MAIN_DB_PREFIX."projet_task.label,
                   ".MAIN_DB_PREFIX."projet_task.rowid
              FROM ".MAIN_DB_PREFIX."projet_task
         LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_risk ON ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_task = ".MAIN_DB_PREFIX."projet_task.rowid
             WHERE ".MAIN_DB_PREFIX."Synopsis_projet_risk.fk_risk_group IS NULL
               AND ".MAIN_DB_PREFIX."projet_task.fk_projet = ".$project_id;
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql))
{
    print '<li class="ui-widget-content ui-corner-tr"  id="task'.$res->rowid.'" >';
    print '<h5 class="ui-widget-header" style="text-transform: capitalize;">'.$res->title.'</h5>';
    print '<a href="#" title="Supprimer du groupe" class="ui-icon ui-icon-trash" style="display:none;">Delete image</a>';
    print '</li>';
}
?>
            </ul>
        </div><!-- End leftpart -->
</td><td width=100%>

<?php


print '  <div class="treeheader ui-widget ui-corner-top ui-widget-header trashupper"  style="padding-bottom: 4px; margin-left: 20px; padding-top: 4px; margin-top: 2px; width:902px;" >&nbsp;&nbsp;&nbsp;Cat&eacute;gories</div>';
print '<div style="overflow:auto;   border: 0px; margin-left: 20px; width:903px;" class=" ui-widget ui-widget-content ">';
print '<div id="tree" class="ui-widget ui-widget-content " style=" border-bottom: 0; font-size: 0.8em; ">';

print '  <ul class="treeview ui-widget"  style="overflow-x: auto; min-height: 528px; padding-bottom: 18px; margin-bottom:0; border-bottom: 0px;">';
?>


<?php
$requete = "SELECT *
              FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
             WHERE fk_projet = ".$project_id;
$sql1 = $db->query($requete);
if ($db->num_rows($sql1)> 0)
{
    print "<div class='accordion' style='width: 80%;' >";
    while ($res=$db->fetch_object($sql1))
    {
        print '<div id="grpID'.$res->id.'"><a href="#" class="accordion-header ui-widget ui-widget-header"  >';
        print '<span  id="grpSID'.$res->id.'"  class="head accordion-header" style="text-transform: capitalize;" >' . html_entity_decode($res->group_name) . '</span>';
        print '</a></div>';
        print '<div style="">'; //ui-state-highlight
            print '<div id="trash'.$res->id.'" class="trash ui-corner-top" style="">';
            print '    <h4 class="ui-widget-header ui-corner-top ui-widget-header" style="padding: 5pt;"><span style="height: 16px;" class="ui-icon ui-icon-transferthick-e-w"></span><span style="margin-left: 10pt; text-transform: capitalize">' . html_entity_decode($res->group_name). '</span></h4>';
            print "<ul id='trashUL".$res->id."'></ul>";
            print '</div>';
            print "<div style='float:left; clear: right;'><fieldset class='fs'><legend>Total des co&ucirc;ts</legend><table width=100%><tr><td align=right><span id='totalGroup".$res->id."'>0 &euro;</span></td></tr></table></fieldset></div>";

            print "<div style='float:left; clear: right;'><fieldset class='fs'><legend>Description</legend><table width=100%><tr><td align=right><span id='description".$res->id."' class='editInPlace'>Cliquer pour &eacute;diter</span></td></tr></table></fieldset></div>";
            print "<div style='float:left; clear: right;'>";
                print "<fieldset class='fs'><legend>Risque</legend>";
                print "<table width=100%>";
                print "<tr><td >Occurence</td>";
                print "  <td width='20px'><span id='slide1Str".$res->id."'>0</span>%</td><td width=280><input type='hidden' name='value1' id='slide1Val".$res->id."' value='0'><div id='slide1-".$res->id."' class='slide'></div></td></tr>";
                print "<tr><td>Importance</td>";
                print "  <td width='20px'><span id='slide2Str".$res->id."'>0</span>%</td><td width=280><input type='hidden' name='value2' id='slide2Val".$res->id."' value='0'><div id='slide2-".$res->id."' class='slide'></div></td></tr>";
                print "</table></fieldset>";
            print "</div>";

            print "<div style='float:left; clear: right;'><fieldset class='fs'><legend>Co&ucirc;t risque</legend><table width=100%><tr><td align=right><span id='totalRisk".$res->id."'>0 &euro;</span></td></tr></table></fieldset></div>";
        print '</div>';
    }
    print '</div>';

} else {
    print "Pas de groupe de risque d&eacute;fini pour ce projet."; //TODO
}
print '</div>';
print '<input type="hidden" id="descStr" value="">';
print '<div  class="scroll  ui-widget ui-widget-content ui-state-default ui-jqgrid-pager ui-corner-bottom" style="text-align: center; margin: 0;  width: 901px; height: 24px;">';
print '<div  class="ui-pager-control" role="group" style="font-size: 1.1em; border: 1px none,font-weight: bold; outline: none ;">';

print '<table><tbody><tr>'."\n";
print '<td id="add_cat" class="ui-pg-button ui-corner-all" title="Ajouter une cat&eacute;gorie">'."\n";
print '<div class="ui-pg-div">'."\n";
print '<span class="ui-icon ui-icon-plus"></span>'."\n";
print '</div>'."\n";
print '</td>'."\n";
print '<td id="mod_cat" class="ui-pg-button  ui-corner-all" title="Modifier une cat&eacute;gorie">'."\n";
print '<div class="ui-pg-div" style="font-size: 1.1em;font-weight: bold; outline: none ;">'."\n";
print '<span class="ui-icon ui-icon-pencil"></span>'."\n";
print '</div>'."\n";
print '</td>'."\n";
print '<td id="del_cat" class="ui-pg-button ui-corner-all" title="Effacer une cat&eacute;gorie">'."\n";
print '<div class="ui-pg-div" style=" font-size: 1.1em;font-weight: bold; outline: none ">'."\n";
print '<span class="ui-icon ui-icon-trash"></span>'."\n";
print '</div>'."\n";
print '</td>'."\n";
print '</tr></tbody></table>'."\n";

print '</div>';

print '</div>';

?>
        </div><!-- End trash upper -->
<!-- dialog -->
</tr></table>
<div id='addDialog'>
    <form id='addDialogForm'>
        <table>
            <tr>
                <td> Nom du groupe </td>
                <td> <input name='addCatName' id='addCatName'> </td>
            </tr>
        </table>
    </form>
</div>

<div id='modDialog'>
<form id='modDialogForm'>
        <table>
            <tr>
                <td> Nom du groupe </td>
                <td> <input name='modCatName' id='modCatName'> </td>
            </tr>
        </table>
</form>
</div>

<div id='delDialog'>
<form id='delDialogForm'>
Et&ecirc;s vous s&ucirc;r?
</form>
</div>
<div class='' id='loading' style='display:none; background-color: #FFFFFF; border:1px Solid #0073EA; outline:3px Solid #EFEFEF; -moz-border-radius: 8px; -webkit-border-radius: 8px; -moz-outline-radius: 8px; -webkit-outline: 0px;   float: left; width: 15%; position: fixed; top:30%; left: 30%; padding: 10pt; ' ><img align=absmiddle src="../theme/auguria/img/ajax-loader.gif"/><span class="ui-state-error-text ui-priority-primary" style='padding-left: 10px;'>Chargement ...</span></div>

<div id="debug"></div>

<?php

?>