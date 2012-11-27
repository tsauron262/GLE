var eventsMenu = {
            bindings: {
                'editer': function(t) {
                    var gr = t.id;
                    if( gr != null ) jQuery('#gridListRessources').editGridRow(gr,{reloadAfterSubmit:true});
                    else alert('Merci de S&eacute;lectionner une ressource');

                },
                'supprimer': function(t) {
                    var gr = t.id;
                    if( gr != null ) jQuery('#gridListRessources').delGridRow(gr,{reloadAfterSubmit:true});
                    else alert('Merci de S&eacute;lectionner une ressource');
                },
                'reserver': function(t) {
                    var gr = t.id;
                    if( gr != null ) location.href='resa.php?ressource_id='+gr;
                    else alert('Merci de S&eacute;lectionner une ressource');
                }
            }
        };
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
    grid = $("#gridListRessources").jqGrid({
            datatype: "json",
            url: "ajax/ressource_json.php?userId="+userId+get,
            colNames:['id',"D&eacute;signation", "Cat&eacute;gorie",'R&eacute;f&eacute;rent','Description','Date achat','Valeur','Co&ucirc;t horaire','Photo'],
            colModel:[  {name:'rowid',index:'rowid', width:0, hidden:true,key:true,hidedlg:true,search:false},
                        {name:'nom',index:'nom', width:80, align:"center",editable:true,searchoptions:{
                            sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
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
                            }
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
                            },
                        },
                        {
                            name:'description',
                            index:'description',
                            width:180,
                            align:"center",
                            editable:true,
                            edittype:"textarea",
                            searchoptions:{sopt:['eq','ne',"bw",'bn',"in","ni","ew","en",'cn',"nc"]}
                        },
                        {
                            name:'date_achat',
                            index:'date_achat',
                            width:90,
                            align:"center",
                            sorttype:"date",
                            formatter:'date',
                            formatoptions:{srcformat:"Y-m-d",newformat:"d/m/Y"},
                            editable:true,
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
                        {name:'valeur',index:'valeur', width:80, align:"center",editable:true,
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                        },
                        {name:'cout',index:'cout', width:80, align:"center",editable:true,
                                                    searchoptions:{sopt:['eq','ne',"le",'lt',"ge","gt"]},
                        },
                        {name:'photo',index:'photo', width:200, align:"center", edittype:'file',editable:true, search: false,sortable: false},
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
            //    alert ("loadComplete");
            },
            afterInsertRow: function(rowid, rowdata, rowelem) {
                    $("#" + rowid).contextMenu("MenuJqGrid", eventsMenu);
            },
        }).navGrid("#gridListRessourcesPager",               { view:true, add:true,
                 del:true,
                 edit:true,
                 search:true,
                 position:"left"
               },               {
                  reloadAfterSubmit:true,
                  bottominfo:"Les champs marqu&eacute;s d'une &eacute;toile sont requis",
                  afterSubmit: function (a,b)
                  {
                      var id = $(a.responseXML).find("ok").text();
                      var ret= false;
                      $.ajaxFileUpload
                      ({
                          url:"ajax/upload_img.php?ressource_id="+id,
                          secureuri:false,
                          fileElementId:"photo",
                          dataType: "xml",
                          async: true,
                          success: function (data, status)
                          {
                              ret =  [true,""];
                              if(typeof(data.error) != "undefined")
                              {
                                  if(data.error != "")
                                  {
                                      alert(data.error);
                                      return [false,"error msg"];
                                  } else {
                                      return [true,""]
                                      alert(data.msg);
                                  }
                              } else {
                                  return [false,"Erreur indefinie"];
                              }
                          },
                              error: function (data, status, e)
                              {
                                  ret = [false,"Erreur Ajax1"];
                                  return [false,"Erreur Ajax2"];
                              }
                      });
                  return(ret);
                  },
               },               {
                  reloadAfterSubmit:true,
                  bottominfo:"Les champs marqu&eacute;s d'une &eacute;toile sont requis",
                  afterSubmit: function (a,b)
                  {
                      var id = $(a.responseXML).find("ok").text();
                      var ret= false;
                      $.ajaxFileUpload
                      ({
                          url:"ajax/upload_img.php?ressource_id="+id,
                          secureuri:false,
                          fileElementId:"photo",
                          dataType: "xml",
                          async: false,
                          success: function (data, status)
                          {
                              ret =  [true,""];
                              if(typeof(data.error) != "undefined")
                              {
                                  if(data.error != "")
                                  {
                                      alert(data.error);
                                      return [false,"error msg"];
                                  } else {
                                      return [true,""]
                                      alert(data.msg);
                                  }
                              } else {
                                  return [false,"Erreur indefinie"];
                              }
                          },
                              error: function (data, status, e)
                              {
                                  ret = [false,"Erreur Ajax3"];
                                  return [false,"Erreur Ajax4"];
                              }
                      });
                  return(ret);
                  },
              },              {
                  reloadAfterSubmit:true
              },              {
                  closeOnEscape:true
              },
              {                  closeOnEscape:true
              }
    );
    });
