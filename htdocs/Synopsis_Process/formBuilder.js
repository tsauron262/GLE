jQuery(document).ready(function()
    {
        delButtonInit();
        paramsButtonInit();
        srcChangeInit();
        jQuery('#paramsDialog').dialog({
            buttons:{
                "OK": function(){
                    var data = jQuery('#formParamsDiv form').serialize();
                    jQuery.ajax({
                        url:'ajax/formBuilder-xml_response.php',
                        type:"POST",
                        datatype:"xml",
                        data:"id="+formElementId+"&action=saveParamsData&"+data,
                        cache: false,
                        success: function(msg){
                            jQuery('#paramsDialog').dialog('close');
                        }
                    })
                //location.href="formBuilder.php?id="+formId+"&action=setParams";
                },
                "Annuler": function(){
                    jQuery('#paramsDialog').dialog('close');
                }
            },
            open: function(){
                var data = "id="+formElementId+"&type="+formElementType;
                jQuery('#formParamsDiv').replaceWith('<div id="formParamsDiv"></div>');
                jQuery.ajax({
                    url:"ajax/listParams-xml_response.php",
                    data:data,
                    datatype:"xml",
                    type: 'POST',
                    cache: false,
                    success:function(msg){
                        var longHtml = "";
                        var longHtml2 = "";
                        var longHtml3 = "";
                        var longHtml4 = "";
                        var longHtmlRights = "";
                        jQuery(msg).find('params').each(function(){
                            var name = jQuery(this).find('name').text();
                            var id = jQuery(this).find('id').text();
                            var valeur = jQuery(this).find('valeur').text();
                            longHtml += "<tr><th class='ui-state-default ui-widget-header'>"+name+"</th><td class='ui-widget-content'><input type='text' name='p||"+id+"' value='"+valeur+"' ></td>";
                        });
                        jQuery(msg).find('style').each(function(){
                            var name = jQuery(this).find('name').text();
                            var id = jQuery(this).find('id').text();
                            var valeur = jQuery(this).find('valeur').text();
                            longHtml2 += "<tr><th class='ui-state-default ui-widget-header'>"+name+"</th><td class='ui-widget-content'><input type='text' name='s||"+id+"' value='"+valeur+"' ></td>";
                        });
                        var valeur="";
                        jQuery(msg).find('class').each(function(){
                            valeur = jQuery(this).find('valeur').text();
                        });
                        longHtml3 += "<tr><th class='ui-state-default ui-widget-header'>Class CSS</th><td class='ui-widget-content'><input type='text' name='c||class' value='"+valeur+"' ></td>";

                        var valeurRights="";
                        jQuery(msg).find('rights').each(function(){
                            valeurRights = jQuery(this).find('valeur').text();
                        });
                        longHtmlRights += "<table width=100% cellpadding=10><tr><th class='ui-state-default ui-widget-header'>Droits</th><td class='ui-widget-content' colspan=2><input type='text' name='r||rights' value='"+valeurRights+"' ></td></table>";

                        var fctName = jQuery(msg).find('fctParams').attr('fct_name');
                        var fct_id = jQuery(msg).find('fctParams').attr('fct_id');
                        var hasFct = false;
                        jQuery(msg).find('fctParams').each(function(){
                            var name = jQuery(this).find('label').text();
                            var id = jQuery(this).find('id').text();
                            var valeur = jQuery(this).find('valeur').text();
                            if (name +"x" != "x") hasFct=true;
                            longHtml4 += "<tr><th class='ui-state-default ui-widget-header'>"+name+"</th><td class='ui-widget-content'><input type='text' name='f||"+id+";;"+fct_id+"' value=\""+valeur+"\" ></td>";
                        });


                        jQuery('#formParamsDiv').replaceWith('<div id="formParamsDiv"><form><table cellpadding=10><tr><td valign=top><table cellpadding=10><tr><th class="ui-widget-header ui-state-default" colspan=2>Propri&eacute;t&eacute;s'+longHtml+'</table><td valign=top><table cellpadding=10><tr><th class="ui-widget-header ui-state-default" colspan=2> Style'+longHtml2+'</table><td valign=top><table cellpadding=10><tr><th class="ui-widget-header ui-state-default" colspan=2>CSS'+longHtml3+'</table>'+(hasFct?'<tr><td valign=top><table cellpadding=10><tr><th class="ui-widget-header ui-state-default" colspan=2>Fonction '+fctName+" "+longHtml4+'</table>':"")+'<tr><td colspan=3>'+longHtmlRights+'</table></form></div>');
                    }
                });
            },
            autoOpen: false,
            width: 720,
            minWidth: 720,
            modal: true,
            title: "Param&egrave;tres"
        });
        jQuery('#savButton').click(function(){
            saveDatas();
        //            console.log(data);
        });
        jQuery('#cloneButton').click(function(){
            location.href='formBuilder.php?action=clone&id='+formId;
        });
        jQuery('#supprButton').click(function(){
            jQuery.ajax({
                url:'ajax/formBuilder-xml_response.php',
                type:"POST",
                datatype:"xml",
                data:"id="+formId+"&action=supprData",
                cache: false,
                success: function(msg)
                {
                    if(jQuery(msg).find('OK') && jQuery(msg).find('OK').length > 0)
                    {
                        location.href='listForm.php';
                    } else {
                        alert('Suppression impossible');
                    }
                }
            })
        });
        jQuery('#sortable1 li').draggable({
            connectToSortable: ".connectedSortable",
            "grid":[ 20,20 ],
            "delay":500,
            revert: true,
            "distance":30,
            "cursor":"move",
            "top":"-5px",
            "left":"-5px",
            "helper":"clone",
            "appendTo":"body",
            start: function(e,ui){
                var type = ui.helper.find('table:first').attr('class');
                if (type=='Item1'){
                    ui.helper.find('table:first tr:first').prepend('<td>Item1</td>');
                } else {
                    ui.helper.find('table:first tr:first').prepend('<td>Item2</td>');
                }
            }
        });
        jQuery('#sortable2').sortable({
            "grid":[ 20,20 ],
            "delay":500,
            "distance":30,
            "cursor":"move",
            placeholder: 'ui-state-error',
            "top":"-5px",
            "appendTo":"#ficheForm",
            "left":"-5px",
            stop: function(){
                saveDatas();
            },
            receive: function(event, ui,t)
            {
                var cnt = (jQuery('#sortable2').find('li.ui-state-highlight').length>0?jQuery('#sortable2').find('li.ui-state-highlight').length:0);
                var nextId = "n"+parseInt(cnt)+1;
                jQuery('#sortable2').css("height","auto");
                jQuery('#sortable2').find('.ui-draggable').each(function()
                {
                    var type = jQuery(this).find('table:first').attr('class');
                    jQuery(this).attr('id',"sortable_"+nextId);
                    jQuery(this).removeClass('ui-draggable');
                    jQuery(this).removeClass('ui-state-default');
                    jQuery(this).addClass('ui-state-highlight');
                    jQuery(this).find('table:first').wrap('<form onSubmit="return false;"></form>');
                    jQuery(this).find('table:first').attr('width','952');
                    jQuery(this).find('table:first').attr('cellpadding','15');
                    jQuery(this).attr('style','');
                    var srcSel = jQuery('#srcSelClone').clone(1);
                    srcSel.find('select').attr('name','src-'+nextId);
                    srcSel.find('select').removeClass('noSelDeco');

                    var srcSelDflt = jQuery('#srcSelDfltClone').clone(1);
                    srcSelDflt.find('select').attr('name','dflt-'+nextId+"-var");
                    srcSelDflt.find('select').removeClass('noSelDeco');

                    var hasDesc = (arrCodeTohasDescription[type]==1?arrCodeTohasDescription[type]:false);
                    var hasDflt = (arrCodeTohasDflt[type]==1?arrCodeTohasDflt[type]:false);
                    var hasTitle = (arrCodeTohasTitle[type]==1?arrCodeTohasTitle[type]:false);
                    var hasSrc = (arrCodeTohasSource[type]>0?arrCodeTohasSource[type]:false);
                    if(hasSrc!=1 && hasSrc != 2) {
                        srcSel=jQuery('<div><input type="hidden" name="src-'+nextId+'"></div>');
                    }
                    jQuery(this).find('table:first td:first')
                    .replaceWith('<td width=75 align=center class="'+type+'"><input type="hidden" name="type-'+nextId+'" value="'+type+'">'+arrCodeToLabel[type]+'</td>\
                                         <td width=100 align=center><input name="titre-'+nextId+'" style="width:75%;'+(hasTitle==1?'':'display:none;')+'"></td>\
                                         <td width=225 align=center><textarea name="descr-'+nextId+'" style="width:75%;'+(hasDesc==1?'':'display:none;')+'"></textarea></td>\
                                         <td width=150 align=center><table><tr><td><input name="dflt-'+nextId+'" style="width:75%;'+(hasDflt==1?'':'display:none;')+'"><tr><td>ou<tr><td>'+srcSelDflt.html()+'</table></td>\
                                         <td width=215 align=center>'+srcSel.html()+'</td>\
                                         <td align=center width=50><table><tr><td style="padding: 0"><span class="ui-icon ui-icon-gear"></span><td style="padding: 0"><span class="ui-icon ui-icon-carat-2-n-s"></span><td style="padding: 0"><span class="ui-icon ui-icon-trash"></span></table>');
                    jQuery('#sortable2').css("min-height",parseInt(jQuery('#sortable2').height())+75);
//                    jQuery('#sortable2 select').selectmenu({
//                        style: 'dropdown', 
//                        maxHeight: 300, 
//                        menuWidth: 165
//                    });
                //                    saveDatas();
                });
                delButtonInit();
                paramsButtonInit();
                srcChangeInit();
                jQuery('#sortable2').sortable('refresh');

            }
        })/*.disableSelection()*/;
//        jQuery('#sortable2 select').selectmenu({
//            style: 'dropdown', 
//            maxHeight: 300, 
//            menuWidth: 165
//        });


        jQuery('#preview').mouseover(function(){
            jQuery(this).parent().addClass('ui-state-error').css("border","0");
        });
        jQuery('#preview').mouseout(function(){
            jQuery(this).parent().removeClass('ui-state-error').css("border","0");
        });
        jQuery('#preview').click(function(){
            saveDatas();
            window.open('formPreview.php?id='+formId);
        });
        jQuery('#testDraggable').scrollFollow({
            delay: 100,
            offText: 'Off Text',
            onText: 'On Text',
            container:"scrollparent",
            offset:0,
            easing:"easeOutBack",
            speed:500
        });

    });
    
    
    
    function saveDatas()
    {
        var data = "";
        jQuery('#sortable2').find('li form').each(function(){
            data+= "&"+jQuery(this).serialize();
        });
        data += "&"+jQuery('#sortable2').sortable("serialize");
        jQuery.ajax({
            url:'ajax/formBuilder-xml_response.php',
            type:"POST",
            datatype:"xml",
            data:"id="+formId+"&action=saveData&"+data,
            cache: false,
            success: function(msg)
            {
                jQuery(msg).find('majId').each(function(){
                    var oldId = jQuery(this).find('old').text();
                    var newId = jQuery(this).find('new').text();
                    jQuery('#sortable_'+oldId).attr('id','sortable_'+newId);
                    jQuery('input[name=type-'+oldId+']').attr('name','type-'+newId);
                    jQuery('input[name=titre-'+oldId+']').attr('name','titre-'+newId);
                    jQuery('textarea[name=descr-'+oldId+']').attr('name','descr-'+newId);
                    jQuery('input[name=dflt-'+oldId+']').attr('name','dflt-'+newId);
                    jQuery('select[name=dflt-'+oldId+'-var]').attr('name','dflt-'+newId+'-var');
                    jQuery('select[name=src-'+oldId+']').attr('name','src-'+newId);
                });
                jQuery("#idMsg").append('<div id="modSav" style="position: fixed; top: 20%; left: 20%; padding:5px 10px; border-style: double; border-width:4px;" class="ui-state-error ui-widget-header ui-corner-all"><table><tr><td><span class="ui-icon ui-icon-alert"></span><td>Modification sauvegard&eacute;e</table></div>');
                setTimeout(function()
                {
                    jQuery("#idMsg  #modSav").fadeOut(
                        "slow",
                        function ()
                        {
                            jQuery("#idMsg #modSav").remove();
                         }
                    );
                 }, 3500);

            }
        });

    }
    function delButtonInit(){
        jQuery('.ui-icon-trash').each(function(){
            jQuery(this).mouseover(function(){
                jQuery(this).parent().addClass('ui-state-default ui-corner-all').css("border","0");
            });
            jQuery(this).mouseout(function(){
                jQuery(this).parent().removeClass('ui-state-default ui-corner-all').css("border","0");
            });
            jQuery(this).unbind('click');
            jQuery(this).click(function(){
                jQuery(this).parents("li").remove();
                jQuery('#sortable2').css("min-height",parseInt(jQuery('#sortable2').height())-75);
                saveDatas();

            });
        });
    }
    var formElementId;
    var formElementType;
    function paramsButtonInit(){
        jQuery('.ui-icon-gear').each(function(){
            jQuery(this).mouseover(function(){
                jQuery(this).parent().addClass('ui-state-default ui-corner-all').css("border","0");
            });
            jQuery(this).mouseout(function(){
                jQuery(this).parent().removeClass('ui-state-default ui-corner-all').css("border","0");
            });
            jQuery(this).unbind('click');
            jQuery(this).click(function(){
                formElementId = jQuery(this).parents("li").attr('id');
                formElementType = jQuery(this).parents("li").find('td:first').attr('class');
                jQuery('#paramsDialog').dialog('open');
            });
        });
    }
    function srcChangeInit(){
        jQuery('select.noSelDeco').each(function(){
            jQuery(this).change(function(){
                //Save
                saveDatas();
            })
        });
    }
    
    
    
    
    
    
    
    
    
    jQuery(document).ready(function(){
                if (formId>0){
                 jQuery('#nomForm').editable('ajax/nomForm-xml_response.php', {
                     type      : 'text',
                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     onblur : 'cancel',
                     width: '150px',
//                     height:"18em",
                     submitdata : {id: formId},
                     data : function(value, settings) {
                          var retval = value; //Global var
                          return retval;
                     }
                 });
                 jQuery('#descForm').editable('ajax/descForm-xml_response.php', {
                     type      : 'textarea',
                     cancel    : 'Annuler',
                     submit    : 'OK',
                     indicator : '<img src="img/ajax-loader.gif">',
                     tooltip   : 'Editer',
                     placeholder : 'Cliquer pour &eacute;diter',
                     onblur : 'cancel',
                     width: '350px',
                     height:"8em",
                     submitdata : {id: formId},
                     data : function(value, settings) {
                          var retval = value; //Global var
                              retval = value.replace(/<br>/g,"");
                          return retval;
                     }
                 });

                }

            });