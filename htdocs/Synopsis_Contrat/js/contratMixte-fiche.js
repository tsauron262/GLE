jQuery(function() {
    jQuery.validator.addMethod(
        'FRDate',
        function(value, element) {
            return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
        },
        '<br>La date doit &ecirc;tre au format dd/mm/yyyy'
        );
    jQuery.validator.addMethod(
        'FRDateNoBR',
        function(value, element) {
            return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
        },
        '&nbsp;&nbsp;La date doit &ecirc;tre au format dd/mm/yyyy'
        );
    jQuery.validator.addMethod(
        'percentdecimal',
        function(value, element) {
            return value.match(/^\d\d*?[,.]?\d*?%?$/);
        },
        'Le taux n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'currency',
        function(value, element) {
            return value.match(/^\d*?[,.]?\d*?$/);
        },
        'La montant n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'required',
        function(value, element) {
            return value.match(/^[\w\W\d]+$/);
        },
        '<br>Ce champ est requis'
        );
    jQuery.validator.addMethod(
        'requiredNoBR',
        function(value, element) {
            return value.match(/^[\w\W\d]+$/);
        },
        '&nbsp;&nbsp;Ce champ est requis'
        );
    jQuery.validator.addMethod(
        'nombreentier',
        function(value, element) {
            return value.match(/^[0-9]+$/);
        },
        '<br>Ce champ n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'nombreentierNoRequired',
        function(value, element) {
            return value.match(/^[0-9]*$/);
        },
        '<br>Ce champ n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'nombreentierNoBR',
        function(value, element) {
            return value.match(/^[0-9]+$/);
        },
        '&nbsp;&nbsp;Ce champ n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'sup1',
        function(value, element) {
            return (value.match(/^[0-9]+$/) && value>0);
        },
        '<br>Ce champ n\'est pas au bon format'
        );
    jQuery.validator.addMethod(
        'sup1NoBR',
        function(value, element) {
            return (value.match(/^[0-9]+$/) && value>0);
        },
        '&nbsp;&nbsp;Ce champ n\'est pas au bon format'
        );


    jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);


    /* jQuery("#dateDebadd").datepicker({dateFormat: 'dd/mm/yy',
                    changeMonth: true,
                    yearRange: yearRange,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });
          jQuery("#dateFinadd").datepicker({dateFormat: 'dd/mm/yy',
                    yearRange: yearRange,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });
          jQuery("#dateDebmod").datepicker({dateFormat: 'dd/mm/yy',
                    yearRange: yearRange,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });
          jQuery("#dateFinmod").datepicker({dateFormat: 'dd/mm/yy',
                    yearRange: yearRange,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });*/

    jQuery('#delDialog').dialog({
        modal: true,
        autoOpen: false,
        title: 'Confirmation'
    });




    jQuery('.ui-button').mouseover(function(){
        jQuery(this).removeClass('ui-state-default');
        jQuery(this).addClass('ui-state-hover');
    });
    jQuery('.ui-button').mouseout(function(){
        jQuery(this).removeClass('ui-state-hover');
        jQuery(this).addClass('ui-state-default');
    });


    jQuery('#addDialog').dialog({
        modal: true,
        title: "Ajouter une ligne",
        width: 935,
        autoOpen: false,
        open: function(){
            jQuery('#adddialogTab').tabs({
                spinner: 'Chargement',
                cache: true,
                fx: {
                    height: 'toggle'
                }
            });
        },
        buttons: {
            "Annuler": function() {
                jQuery(this).dialog("close");
            } ,
            "Ajouter": function() {
                if (jQuery('#addForm').validate({
                    rules: {
                        addDur: {
                            sup1: true
                        },
                        dateDebadd: {
                            FRDate: true
                        },
                        addPrice: {
                            currency: true
                        },
                        addDesc: {
                            required: true
                        }
                    },
                    messages: {
                        addPrice: {
                            currency: "<br/><span style='font-size: 9px;'> Le prix n'est pas au bon format</span>"
                        },
                        addDesc: {
                            required: "<br/><span style='font-size: 9px;'> Ce champs est requis</span>"
                        }
                    }
                }).form()){
                    var data = "";
                    var data = jQuery('#addForm').serialize();
                    data.replace(/p_idContratprod_add=&/,"").replace(/p_idprod_add=&/,"");

                    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";

                    jQuery.ajax({
                        url: href,
                        data: "id="+idContratCurrent+"&action=addligne&"+data,
                        datatype: 'xml',
                        success: function(msg)
                        {
                            if (jQuery(msg).find('OK').text() == 'OK') {
                                //                                    console.log(msg);
                                location.href='card.php?id='+idContratCurrent;
                            } else {
                                //error
                                console.log(msg);
                            }
                        }
                    });
                } else {
                    return(false)
                }
            }
        }
    });
    jQuery('#AddLineBut').click(function(){
        jQuery('#addDialog').dialog('open');
    });

    jQuery('button#modLine').click(function(){
        jQuery('#modDialog').dialog('open');
    });
    jQuery('#modDialog').dialog({
        modal: true,
        title: "Modifier une ligne",
        width: 935,
        autoOpen: false
    });
    jQuery('#activateDialog').dialog({
        modal: true,
        title: "Activer une ligne",
        width: 935,
        autoOpen: false
    });
    jQuery('#unactivateDialog').dialog({
        modal: true,
        title: "Annuler une ligne",
        width: 935,
        autoOpen: false
    });
    jQuery('#closeLineDialog').dialog({
        modal: true,
        title: "Cl&ocirc;turer une ligne",
        width: 935,
        autoOpen: false
    })

    jQuery('#dialogRenew').dialog({
        modal: true,
        title: "Renouveller le contrat",
        width: 935,
        autoOpen: false,
        buttons: {
            "Ajouter": function(){
                if(jQuery('#renewContratForm').validate({
                    rules:{
                        renewDate: {
                            FRDate: true,
                            required: true
                        },
                        renewDurr: {
                            sup1: true,
                            required: true

                        }
                    }
                }).form()){
                    var data = jQuery('#renewContratForm').serialize();

                    jQuery.ajax({
                        url: DOL_URL_ROOT+'/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php',
                        datatype: 'xml',
                        type: 'POST',
                        cache: false,
                        data:"id="+idContratCurrent+"&action=renewDialog&"+data,
                        success:function(msg){
                            if (jQuery(msg).find('OK')) {
                                jQuery(this).dialog("close");
                                location.href = "card.php?id=" + idContratCurrent;
                            }
                        }
                    });
                };
            },
            "Annuler": function(){
                jQuery(this).dialog("close");
            },
            "Tout � oui":function(){
                jQuery('#renewContratForm').find(".chkBoxRenew").attr('checked','checked');
            },
            "Tout � non":function(){
                jQuery('#renewContratForm').find(".chkBoxRenew").attr('checked','');
            }
        }
    });

    jQuery('#confirmAvenant').dialog({
        modal: true,
        title: "Valider une avenant",
        width: 935,
        autoOpen: false,
        buttons: {
            "Ajouter": function(){
                jQuery.ajax({
                    url: DOL_URL_ROOT+'/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php',
                    datatype: 'xml',
                    type: 'POST',
                    cache: false,
                    data:"id="+idContratCurrent+"&action=validAvenant",
                    success:function(msg){
                        if(jQuery(msg).find('OK')){
                            jQuery(this).dialog("close");
                            location.href="card.php?id="+idContratCurrent;
                        }
                    }
                });
            },
            "Annuler": function(){
                jQuery(this).dialog("close");
            }
        }
    });
    /* jQuery('#dateDebEff').datepicker({dateFormat: 'dd/mm/yy',
                    yearRange: yearRange,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });
       jQuery('#dateFinEff').datepicker({dateFormat: 'dd/mm/yy',
                    yearRange: yearRange,
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    buttonImage: 'calendar.png',
                    buttonImageOnly: false,
                    showTime: false,
                    duration: '',
                    constrainInput: true,
                    gotoCurrent: true
            });*/
    jQuery('.ui-state-default').click(function(){
    
        });
    jQuery('#modDialog').bind('dialogopen', function(e,u){
        //get data from ajax
        jQuery('#moddialogTab').tabs({
            spinner: 'Chargement',
            cache: true,
            fx: {
                height: 'toggle'
            }
        });
        jQuery.ajax({
            url: DOL_URL_ROOT+'/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php',
            datatype: 'xml',
            data: 'action=getLineDet&idContrat='+g_idContrat+'&idLigneContrat='+g_idLigne+'&userId='+userId,
            success: function(msg){
                jQuery.ajax({
                    url: DOL_URL_ROOT+'/Synopsis_Contrat/ajax/getFormProdCli.php?id='+g_idLigne,
                    datatype: 'html',
                    success: function(msg){
                        jQuery('div#productCli').html(msg);
                        initFormChrono();
                    }
                });
                jQuery('#modPuHT').val(jQuery(msg).find('totalht').text());

                jQuery('#nbTicketmod').val(jQuery(msg).find('qteTkt').text());
                jQuery('#nbTicketMNTmod').val(jQuery(msg).find('qteMNT').text());
                var iter = 0;
                var remIter = 0;
                jQuery('#modtauxtva option').each(function(){
                    if (parseFloat(jQuery(this).val()) == parseFloat(jQuery(msg).find('tva_tx').text())) {
                        remIter = iter;
                        return false;
                    }
                    iter++;
                });
                //                        jQuery('#modtauxtva').selectmenu('value',remIter);
                jQuery('#modDesc').val(jQuery(msg).find('description').text());
                jQuery('#modserial').val(jQuery(msg).find('serial_number').text());
                jQuery('#DurSAVmod').val(jQuery(msg).find('durSAV').text());
                jQuery('#nbVisitemod').val(jQuery(msg).find('nbVisiteAn').text());
                jQuery('#nbVisitemodCur').val(jQuery(msg).find('nbVisiteAnCur').text());
                jQuery('#modClause').val(jQuery(msg).find('clause').text());
                jQuery('#modSLA').val(jQuery(msg).find('SLA').text());
                //setDate
                if (jQuery(msg).find('dateDeb').text()+"x" != 'x')
                {
                    jQuery('#dateDebmod').val(jQuery(msg).find('dateDeb').text());
                }
                jQuery('#modQte').val(jQuery(msg).find('qte').text());
                //panel detail et checkbox
                var Hotline = jQuery(msg).find('hotline').text();
                var reconductionAuto = jQuery(msg).find('recondAuto').text();
                var TeleMaintenance = jQuery(msg).find('telemaintenance').text();
                var TeleMaintenanceCur = jQuery(msg).find('telemaintenanceCur').text();
                var isTkt = jQuery(msg).find('isTkt').text();
                var isMnt = jQuery(msg).find('isMnt').text();
                var isSAV = jQuery(msg).find('isSAV').text();
                    jQuery('#hotlinemod').val(Hotline);
                    jQuery('#telemaintenancemod').val(TeleMaintenance);
                    jQuery('#telemaintenancemodCur').val(TeleMaintenanceCur);
//                if (Hotline == 1){
//                    jQuery('#hotlinemod').attr('checked',true);
//                } else {
//                    jQuery('#hotlinemod').attr('checked',false);
//                }
//                if (TeleMaintenance == 1){
//                    jQuery('#telemaintenancemod').attr('checked',true);
//                } else {
//                    jQuery('#telemaintenancemod').attr('checked',false);
//                }
                if (reconductionAuto == 1)
                {
                    jQuery('#modrecondAuto').attr('checked',true);
                } else {
                    jQuery('#modrecondAuto').attr('checked',false);
                }
//                jQuery('input[name=typemod]').attr('checked',false);
                jQuery('#DurValMntmod').val("");
                jQuery('#DurValTktmod').val("");

                if (isMnt == 1)
                {
                    jQuery('#MnTtypemod').attr('checked',true);
                    jQuery('#ticketmod').hide();
                    jQuery('#savgmaomod').hide();
                    modshowGMAO("MnT");
                    jQuery('#DurSAVmod').val("");
                    jQuery('#DurValMntmod').val(jQuery(msg).find('durValid').text());
                    jQuery('#nbTicketmod').val("");
                    jQuery('#qteTktPerDureemod').val(jQuery(msg).find('qteTktPerDuree').text());
                    jQuery('#qteTempsPerDureeHmod').val(jQuery(msg).find('qteTempsPerDureeH').text());
                    jQuery('#qteTempsPerDureeMmod').val(jQuery(msg).find('qteTempsPerDureeM').text());

                /*              $xml .= "<qteTktPerDuree><![CDATA[".$res->GMAO_qteTktPerDuree."]]></qteTktPerDuree>";
                $xml .= "<qteTempsPerDuree><![CDATA[".$res->GMAO_qteTempsPerDuree."]]></qteTempsPerDuree>";
                $arrDur= convDur($res->GMAO_qteTempsPerDuree);
                $xml .= "<qteTempsPerDureeH><![CDATA[".$arrDur['hours']['abs']."]]></qteTempsPerDureeH>";
                $xml .= "<qteTempsPerDureeM><![CDATA[".$arrDur['minutes']['rel']."]]></qteTempsPerDureeM>";*/



                } else if (isSAV == 1){
                    jQuery('#SaVtypemod').attr('checked',true);
                    jQuery('#ticketmod').hide();
                    jQuery('#maintenancemod').hide();
                    modshowGMAO("SaV");
                    jQuery('#nbTicketmod').val("");
                    jQuery('#nbTicketMNTmod').val("");
                    jQuery('#telemaintenancemod').attr('checked',false);
                    jQuery('#hotlinemod').attr('checked',false);
                } else if (isTkt == 1){
                    jQuery('#DurValTktmod').val(jQuery(msg).find('durValid').text());
                    jQuery('#TkTtypemod').attr('checked',true);
                    jQuery('#savgmaomod').hide();
                    jQuery('#maintenancemod').hide();
                    modshowGMAO("TkT");
                    jQuery('#DurSAVmod').val("");
                    jQuery('#telemaintenancemod').attr('checked',false);
                    jQuery('#hotlinemod').attr('checked',false);
                    jQuery('#nbTicketMNTmod').val("");
                }
                var iter=0;
                var remIter = 0;
                jQuery('#modCommande option').each(function(){
                    if (jQuery(this).val()==jQuery(msg).find('commande').text()){
                        jQuery(this).attr('selected', true);
                        remIter = iter;
                        return false;
                    }
                    iter++;
                });
                //                        jQuery('#modCommande').selectmenu('value',remIter);
                var commandeDet = jQuery(msg).find('commandeDet').text();
                if (remIter > 0){
                    var type='mod';
                    jQuery.ajax({
                        url: DOL_URL_ROOT+"/Synopsis_Contrat/ajax/listCommandeDet-xml_response.php",
                        data: "id="+jQuery(msg).find('commande').text(),
                        datatype:"xml",
                        type: "POST",
                        cache: true,
                        success: function(msg){
                            var longHtml = '<span id="'+type+'commandeDet">';
                            longHtml += "<SELECT name='"+type+"LigneCom'  name='"+type+"LigneCom'><OPTION value='-1'>S&eacute;lectionner-></OPTION>";
                            jQuery(msg).find('commandeDet').each(function(){
                                var idLigne = jQuery(this).attr('id');
                                var valLigne = jQuery(this).text();
                                if (idLigne == commandeDet)
                                {
                                    longHtml += "<OPTION SELECTED value='"+idLigne+"'>"+valLigne+"</OPTION>";
                                } else {
                                    longHtml += "<OPTION value='"+idLigne+"'>"+valLigne+"</OPTION>";
                                }
                            });
                            longHtml += "</SELECT></span>";
                            jQuery('#'+type+'commandeDet').replaceWith(longHtml);
                        //                        jQuery('#'+type+'commandeDet').find('SELECT').selectmenu({
                        //                            style: 'dropdown', 
                        //                            maxHeight: 300
                        //                        });
                        }
                    });
                } else {
                    jQuery('#'+type+'commandeDet').replaceWith('<span id="'+type+'commandeDet">&nbsp;</span>');
                }

                var fkProd = jQuery(msg).find('fk_product').text();
                
                
                
                var url = DOL_URL_ROOT + '/synopsistools/product/ajaxproducts.php';
                //                if (fkProd > 0) {
                jQuery('#modClauseProd').replaceWith('<div id="modClauseProd" class="ui-widget-content" style="padding: 5px;">'+jQuery(msg).find('productClause').text()+'</div>');
                
//                var retId = 'ajdynfieldp_idprod_mod';
//                var param = 'htmlname=p_idprod_mod&price_level=&type=0&mode=1&status=1';
//                if (fkProd + "x" != "x")
//                    param = param + '&prodId=' + fkProd;
//                $('#search_p_idprod_mod').bind('keyup',function(){
//                    param12 = param+"&keysearch="+jQuery(this).val();
//                    getResult1(url, param12, retId);
//                });
//                desc = jQuery(msg).find('product2').text();
//                jQuery('#search_p_idprod_mod').val(desc)
//                
//                getResult1(url, param+"&keysearch="+desc, retId);
                                 
                //                }
                
                
                
                var fkProd2 = jQuery(msg).find('fk_contrat_prod').text();
                //                if (fkProd2 + "x" != "x") {
                jQuery('#modClauseProdCont').replaceWith('<div id="modClauseProdCont" class="ui-widget-content" style="padding: 5px;">'+jQuery(msg).find('contratClause').text()+'</div>');

                var retId2 = 'p_idContratprod_mod';
                var param2 = 'htmlname=p_idContratprod_mod&price_level=&type=2&mode=1&status=1';
                if (fkProd2 + "x" != "x")
                    param2 = param2 + '&prodId=' + fkProd2;
                $('#search_p_idContratprod_mod').bind('keyup',function(){
                    desc = jQuery('#search_p_idContratprod_mod').val();
                    getResult1(url, param2+"&keysearch="+desc, retId2);
                });
                desc = jQuery(msg).find('product').text();
                jQuery('#search_p_idContratprod_mod').val(desc);
                getResult1(url, param2+"&keysearch="+desc, retId2);
                
                
                
                
            //                }
                    
                    
            //                    jQuery.ajax({
            //                        datatype: "html",
            //                        url: url,
            //                        data: param,
            //                        error: function(XMLHttpRequest, textStatus, errorThrown){
            //                            console.log(XMLHttpRequest);
            //                            console.log(textStatus);
            //                            console.log(errorThrown);
            //                        },
            //                        success: function(data){
            //                            var url = DOL_URL_ROOT + '/synopsistools/product/ajaxproducts.php';
            //                            var curId = 'keysearchp_idContratprod_mod';
            //                            var retId = 'ajdynfieldp_idContratprod_mod';
            //                            var tmpHtml = jQuery('<div></div>');
            //                            tmpHtml.html(data);
            //                            var tmp = tmpHtml.find('select').parent().html();
            //                            if (tmp == null) {
            //                                jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '"></div>'));
            //                            } else {
            //                                jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '">' + tmp + '</div>'));
            //                            //                                        jQuery("#" + retId).find('SELECT').selectmenu({
            //                            //                                            style: 'dropdown',
            //                            //                                            width: 458
            //                            //                                        });
            //                            }
            //                        }
            //                    });
            //                } else {
            //                    //TODO reinit form
            //                    if (jQuery(msg).find('fk_product').text() + "x" != "x") {
            //
            //                        jQuery('#modClauseProd').replaceWith('<div id="modClauseProd" class="ui-widget-content" style="padding: 5px;">'+jQuery(msg).find('productClause').text()+'</div>');
            //                        var url = DOL_URL_ROOT + '/synopsistools/product/ajaxproducts.php';
            //                        var curId = 'keysearchp_idprod_mod';
            //                        var retId = 'ajdynfieldp_idprod_mod';
            //                        var param = 'htmlname=p_idprod_mod&price_level=&type=0&mode=1&status=1&prodId=' + jQuery(msg).find('fk_product').text();
            //                        jQuery.ajax({
            //                            datatype: "html",
            //                            url: url,
            //                            data: param,
            //                            error: function(XMLHttpRequest, textStatus, errorThrown){
            //                                console.log(XMLHttpRequest);
            //                                console.log(textStatus);
            //                                console.log(errorThrown);
            //                            },
            //                            success: function(data){
            //                                var tmpHtml = jQuery('<div></div>');
            //                                tmpHtml.html(data);
            //                                var tmp = tmpHtml.find('select').parent().html();
            //                                if (tmp == null) {
            //                                    jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '"></div>'));
            //
            //                                }
            //                                else {
            //                                    jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '">' + tmp + '</div>'));
            //                                //                                            jQuery("#" + retId).find('SELECT').selectmenu({
            //                                //                                                style: 'dropdown',
            //                                //                                                width: 458
            //                                //                                            });
            //                                }
            //                            }
            //                        });
            //                    } else {
            //                    //TODO reinit form
            //                    }
            //                }
                
                
                
                
                
                
            }
        });
    });

});
function activateLine(obj,idContrat,idLigne)
{
    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery('#activateDialog').dialog('option', 'buttons',
    {
        "Annuler": function() {
            jQuery(this).dialog("close");
        } ,
        "Activer": function() {
            var dateDeb =jQuery('#dateDebEff').val();
            var dateFin =jQuery('#dateFinEff').val();
            var data = "&dateDebConf="+dateDeb;
            data += "&dateFinConf="+dateFin;
            jQuery.ajax({
                url: href,
                data: "id="+idContrat+"&action=activateLine&lineid="+idLigne+data,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        var parentO = jQuery(obj).parent().parent().parent().parent().parent();
                        //Recreate the line with new datas
                        var ret = jQuery(msg).find('OKtext').text();
                        parentO.replaceWith(jQuery(ret).html());
                        jQuery('#activateDialog').dialog("close");
                        updateStatutPanel(idContrat);
                    } else {
                        alert ('Il y a eu une erreur !!');
                        console.log(jQuery(msg).find('KO').text());
                    }
                }
            });
        }
    }
    );
    jQuery('#activateDialog').dialog('open');

}

function unactivateLine(obj,idContrat,idLigne)
{
    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery('#unactivateDialog').dialog('option', 'buttons',
    {
        "Annuler": function() {
            jQuery(this).dialog("close");
        } ,
        "Desactiver": function() {
            jQuery.ajax({
                url: href,
                data: "id="+idContrat+"&action=unactivateLine&lineid="+idLigne,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        var parentO = jQuery(obj).parent().parent().parent().parent().parent();
                        //Recreate the line with new datas
                        var ret = jQuery(msg).find('OKtext').text();
                        parentO.replaceWith(jQuery(ret).html());
                        jQuery('#unactivateDialog').dialog("close");
                        updateStatutPanel(idContrat);
                    } else {
                        alert ('Il y a eu une erreur !!');
                        console.log(jQuery(msg).find('KO').text());
                    }
                }
            });
        }
    }
    );
    jQuery('#unactivateDialog').dialog('open');

}

function closeLine(obj,idContrat,idLigne)
{
    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery('#closeLineDialog').dialog('option', 'buttons',
    {
        "Annuler": function() {
            jQuery(this).dialog("close");
        } ,
        "Cloturer": function() {
            jQuery.ajax({
                url: href,
                data: "id="+idContrat+"&action=closeLine&lineid="+idLigne,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text() == 'OK') {
                        //                                    console.log(msg);
                        location.href='card.php?id='+idContratCurrent;
                    } else {
                        //error
                        console.log(msg);
                    }
                }
            });
        }
    }
    );
    jQuery('#closeLineDialog').dialog('open');

}

var g_idContrat;
var g_idLigne;
var g_obj;

function editLine(obj,idContrat,idLigne)
{
    g_idContrat = idContrat;
    g_idLigne = idLigne;
    g_obj = obj;

    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery('#modDialog').dialog('option',
        'buttons',
        {
            "Annuler": function() {
                jQuery(this).dialog("close");
            } ,
            "Modifier": function() {
                if (jQuery('#modForm').validate({
                    rules: {
                        modDur: {
                            sup1: true
                        },
                        dateDebmod: {
                            FRDate: true
                        },
                        modPrice: {
                            currency: true
                        },
                        modDesc: {
                            required: true
                        }
                    },
                    messages: {
                        modPrice: {
                            currency: "<br/><span style='font-size: 9px;'> Le prix n'est pas au bon format</span>"
                        },
                        modDesc: {
                            required: "<br/><span style='font-size: 9px;'> Ce champs est requis</span>"
                        }
                    }
                }).form()){
                    var data = "";
                    data = jQuery('#modForm').serialize();
                    data.replace(/p_idContratprod_mod=&/,"").replace(/p_idprod_mod=&/,"");
                    data += "&lineid="+g_idLigne;

                    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";

                    jQuery.ajax({
                        url: href,
                        data: "id="+g_idContrat+"&action=modligne&"+data,
                        datatype: 'xml',
                        success: function(msg)
                        {
                            if (jQuery(msg).find('OK').text() == 'OK') {
                                //                                    console.log(msg);
                                location.href='card.php?id='+idContratCurrent;
                            } else {
                                //error
                                console.log(msg);
                            }
                        }
                    });
                } else {
                    return(false)
                }
            }
        });

    jQuery('#modDialog').dialog('open');

}
function reorderLine()
{
    var i=0;
    var data=new Array();
    jQuery('#sortable').find('li').each(function(){
        if (jQuery(this).attr('id')+"x" != "x")
        {
            data[i]=jQuery(this).attr('id');
            i++;
        //                    console.log(jQuery(this).attr('id') + " " + i);
        }
    });
    jQuery.ajax({
        url:DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php",
        data: "action=sortLine&data="+data.toString(),
        datatype: "xml",
        success: function(msg){
            if (jQuery(msg).find('OK').text()=='OK')
            {
            //
            } else {
                if (jQuery(msg).find('KO'))
                    console.log(jQuery(msg).find('KO').text());
            }
        }
    });

}

function deleteLine(obj,idContrat,idLigne)
{
    console.log(jQuery('#delDialog'));
    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery('#delDialog').dialog('option',
        'buttons',
        {
            "Annuler": function() {
                jQuery(this).dialog("close");
            } ,
            "Supprimer": function() {
                jQuery.ajax({
                    url: href,
                    data: "id="+idContrat+"&action=deleteline&lineid="+idLigne,
                    datatype: 'xml',
                    success: function(msg)
                    {
                        jQuery(obj).parent().parent().parent().parent().parent().remove();
                        jQuery('#delDialog').dialog("close");
                        updateStatutPanel(idContrat);
                    }
                });
            }
        });
    jQuery('#delDialog-content').replaceWith('<div id="delDialog-content" style="padding: 10px;"><span style="float: left; " class="ui-icon ui-icon-alert"></span><span style="margin-top: 10px; margin-left: 10px;">&Ecirc;tes vous sur de vouloir supprimer cette ligne&nbsp;?</span></div>');
    jQuery('#delDialog').dialog('open');
}

function updateStatutPanel(idContrat)
{
    var href=DOL_URL_ROOT+"/Synopsis_Contrat/ajax/contratMixte_fiche_ajax.php";
    jQuery.ajax({
        url: href,
        data: "id="+idContrat+"&action=getStatut",
        datatype: 'xml',
        success: function(msg)
        {
            var panel = jQuery(msg).find('srvPanel').text();
            jQuery('#statutPanel').fadeOut('fast',function(){
                jQuery('#statutPanel').html(jQuery(panel).html()).fadeIn('fast')
            });
        }
    })
}
function openDialogAdd(){
    jQuery('#addDialog').dialog('open');
}
function validateAvenant(){
    jQuery('#confirmAvenant').dialog('open');
}
function renewContrat(){
    jQuery('#dialogRenew').dialog('open');
}



       
function getResult1(url, param, retId){
    jQuery.ajax({
        datatype: "html",
        url: url,
        data: param,
        error: function(XMLHttpRequest, textStatus, errorThrown){
            console.log(XMLHttpRequest);
            console.log(textStatus);
            console.log(errorThrown);
        },
        success: function(data){
            var tmpHtml = jQuery('<div></div>');
            tmpHtml.html(data);
            var tmp = tmpHtml.find('select').parent().html();
            if (tmp == null) {
                jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '"></div>'));

            }
            else {
                jQuery("#" + retId).replaceWith(jQuery('<div id="' + retId + '">' + tmp + '</div>'));
            //                                                    jQuery("#" + retId).find('SELECT').selectmenu({
            //                                                        style: 'dropdown',
            //                                                        width: 458
            //                                                    });
            }
        }
    });
}
