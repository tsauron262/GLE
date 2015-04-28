
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

        jQuery('#addFinancementadd').click(function(){
            if (jQuery(this).attr('checked')==true)
            {
                jQuery("#financementLigneadd").css('display','block');
            } else {
                jQuery("#financementLigneadd").css('display','none');
            }
        });
        jQuery('#addFinancementmod').click(function(){
            if (jQuery(this).attr('checked')==true)
            {
                jQuery("#financementLignemod").css('display','block');
            } else {
                jQuery("#financementLignemod").css('display','none');
            }
        });

        jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);


          jQuery("#dateDebadd").datepicker({dateFormat: 'dd/mm/yy',
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
            });

        jQuery('#delDialog').dialog({
            modal: true,
            autoOpen: false,
            title: 'Confirmation',
        });

//        jQuery('#renewContrat').click(function(){
//            var href="ajax/contratTkt_fiche_ajax.php";
//            jQuery.ajax({
//                url: href,
//                data: "id="+idContratCurrent+"&action=renew",
//                datatype: 'xml',
//                success: function(msg)
//                {
//                    if (jQuery(msg).find('OK').text()=='OK')
//                    {
//                        //TODO va vers le nouveau contrat
//                    }
//                }
//                });
//        });

        jQuery('.butAction').mouseover(function(){
            jQuery(this).removeClass('ui-state-default');
            jQuery(this).addClass('ui-state-hover');
        });
        jQuery('.butAction').mouseout(function(){
            jQuery(this).removeClass('ui-state-hover');
            jQuery(this).addClass('ui-state-default');
        });


        jQuery('#configContrat').click(function(){
            jQuery('#configDialog').dialog('open');
        });

        jQuery('#configDialog').dialog({
            modal: true,
            title: "Configurer le contrat",
            width: 935,
            autoOpen: false,
            buttons: {
                "Configurer": function(){
                    if (jQuery('#formConfig').validate().form()) {
                        var data = jQuery('#formConfig').serialize();
                        var href = DOL_URL_ROOT+"Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
                        jQuery.ajax({
                            url: href,
                            data: "id=" + idContratCurrent + "&action=configure&" + data,
                            datatype: 'xml',
                            success: function(msg){
                                //3 error mgt
                                if (jQuery(msg).find('OK').text() == 'OK') {
                                    location.reload();
                                }
                                else {
                                    alert('il y a eu une erreur');
                                }
                            }
                        });
                    }
                },
                "Annuler": function(){
                    jQuery(this).dialog("close");
                },
            }
        });


        jQuery('#addDialog').dialog({
            modal: true,
            title: "Ajouter des tickets",
            width: 635,
            autoOpen: false,
            buttons: {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
                "Ajouter": function() {
                    if (jQuery('#addForm').validate({
                         rules: {
                            addQty: {
                                required: function(e){
                                    return(false);
                                },
                            },
                        },
                        messages: {
                            addQty: {
                                required: "<br/><span style=' font-size: 9px;'> Merci de saisir une quantit&eacute;</span>",
                            },
                        }

                    }).form()){
                        var data = "";
                        var fk_prod = jQuery('#addfk_prod').find(":selected").val();
                        if (fk_prod>0)
                        {
                            data += "&p_idprod="+fk_prod;
                        }


                        data += "&pqty="+jQuery('#addQty').val();
                        data += "&pu_ht="+jQuery('#addpu_ht').val();
                        data += "&dateDeb="+jQuery("#dateDebadd").val();
                        data += "&addDur="+jQuery("#addDur").val();
                        data += "&Linetva_tx="+jQuery('#addLinetva_tx :selected').val();

                        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";

                        jQuery.ajax({
                            url: href,
                            data: "id="+idContratCurrent+"&action=addligne"+data,
                            datatype: 'xml',
                            success: function(msg)
                            {
                                if (jQuery(msg).find('OK').text()=='OK')
                                {
                                    //Recreate the line with new datas
                                    var ret = jQuery(msg).find('OKtext').text();
                                        ret = jQuery(ret).html();
                                    jQuery("#sortable").append(ret);
                                    jQuery('#sortable').sortable({
                                         items: 'li:not(li.titre)',
                                        distance: 15,
                                        delay: 200,
                                        placeholder: 'ui-placeHolder',
                                        axis: 'y',
                                        containment: 'body',
                                        opacity: 0.8,
                                        start: function(e,u)
                                        {
                                            //get element height
                                            var h = u.helper.css('height');
                                            jQuery('.ui-placeHolder').css('height',h);
                                        },
                                        stop: function(event, ui) { reorderLine();},
                                    });
                                    jQuery('#sortable').disableSelection();
                                    jQuery('#addDialog').dialog("close");
                                    location.href="fiche.php?id="+idContratCurrent;
                                } else {
                                    alert ('Il y a eu une erreur !!');
                                    console.log(jQuery(msg).find('KO').text());
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
        jQuery('#modLine').click(function(){
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
            title: "Cl&ocirc:turer une ligne",
            width: 935,
            autoOpen: false
        })
        jQuery('#dateDebEff').datepicker({dateFormat: 'dd/mm/yy',
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
            });

    });
    function activateLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery('#activateDialog').dialog('option', 'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
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
                },
           }
        );
        jQuery('#activateDialog').dialog('open');

    }

    function unactivateLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery('#unactivateDialog').dialog('option', 'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
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
                },
           }
        );
        jQuery('#unactivateDialog').dialog('open');

    }

    function closeLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery('#closeLineDialog').dialog('option', 'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
                "Cloturer": function() {
                    jQuery.ajax({
                        url: href,
                        data: "id="+idContrat+"&action=closeLine&lineid="+idLigne,
                        datatype: 'xml',
                        success: function(msg)
                        {
                            if (jQuery(msg).find('OK').text()=='OK')
                            {
                                var parentO = jQuery(obj).parent().parent().parent().parent().parent();
                                //Recreate the line with new datas
                                var ret = jQuery(msg).find('OKtext').text();
                                parentO.replaceWith(jQuery(ret).html());
                                jQuery('#closeLineDialog').dialog("close");
                                updateStatutPanel(idContrat);
                            } else {
                                alert ('Il y a eu une erreur !!');
                                console.log(jQuery(msg).find('KO').text());
                            }
                        }
                    });
                },
           }
        );
        jQuery('#closeLineDialog').dialog('open');

    }


    function editLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery('#modDialog').dialog('option',
           'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
                "Modifier": function() {
                    if (jQuery('#modForm').validate({
                         rules: {
                            modQty: {
                                required: function(e){
                                    //console.log(e);
                                    return(false);
                                },
                            },
                        },
                        messages: {
                            modQty: {
                                required: "<br/><span style=' font-size: 9px;'> Merci de saisir une quantit&eacute;</span>",
                            },
                        }

                    }).form()){
                        var data = "";
                        var fk_prod = jQuery('#modfk_prod').find(":selected").val();
                        if (fk_prod>0)
                        {
                            data += "&p_idprod="+fk_prod;
                        }


                        data += "&pqty="+jQuery('#modQty').val();
                        data += "&pu_ht="+jQuery('#modpu_ht').val();
                        data += "&dateDeb="+jQuery("#dateDebmod").val();
                        data += "&modDur="+jQuery("#modDur").val();
                        data += "&Linetva_tx="+jQuery('#modLinetva_tx :selected').val();
                        data += "&lineId="+idLigne;
                        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";

                        jQuery.ajax({
                            url: href,
                            data: "id="+idContratCurrent+"&action=modligne"+data,
                            datatype: 'xml',
                            success: function(msg)
                            {
                                if (jQuery(msg).find('OK').text()=='OK')
                                {
                                    //Recreate the line with new datas
                                    var ret = jQuery(msg).find('OKtext').text();
                                        ret = jQuery(ret).html();
                                    jQuery("#sortable").append(ret);
                                    jQuery('#sortable').sortable({
                                         items: 'li:not(li.titre)',
                                        distance: 15,
                                        delay: 200,
                                        placeholder: 'ui-placeHolder',
                                        axis: 'y',
                                        containment: 'body',
                                        opacity: 0.8,
                                        start: function(e,u)
                                        {
                                            //get element height
                                            var h = u.helper.css('height');
                                            jQuery('.ui-placeHolder').css('height',h);
                                        },
                                        stop: function(event, ui) { reorderLine();},
                                    });
                                    jQuery('#sortable').disableSelection();
                                    jQuery('#modDialog').dialog("close");
                                    location.href="fiche.php?id="+idContratCurrent;
                                } else {
                                    alert ('Il y a eu une erreur !!');
                                    console.log(jQuery(msg).find('KO').text());
                                }
                            }
                        });
                    } else {
                        return(false)
                    }
            },
        });
        jQuery('#modDialog').bind('dialogopen', function(e,u){
                //get data from ajax
                jQuery.ajax({
                    url: DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php",
                    datatype: 'xml',
                    data: 'action=getLineDet&idContrat='+idContrat+'&idLigneContrat='+idLigne+'&userId='+userId,
                    success: function(msg){
                        var qteTkt = jQuery(msg).find('qtyTkt').text();
                        var pu_ht = jQuery(msg).find('price_ht').text();
                        var qty = jQuery(msg).find('qty').text();
                        var tva_tx = jQuery(msg).find('tva_tx').text();
                        var fk_product = jQuery(msg).find('fk_product').text();
                        var libelle = jQuery(msg).find('libelleProduit').text();
                        var durValid = jQuery(msg).find('durValid').text();
                        //setDate
                        var date_ouverture=jQuery(msg).find('date_ouverture').text();
                        var date_ouverture_prevue=jQuery(msg).find('date_ouverture_prevue').text();
                        var date_cloture = jQuery(msg).find('date_cloture').text();
                        var date_fin_validite = jQuery(msg).find('date_fin_validite').text();
                        var fk_product = jQuery(msg).find('fk_product').text();

                        if (fk_product >0 ){
                            jQuery('#modQtyTxt').css('display','inline');
                            jQuery('#modQtyTxt').find('#txtQty').text(qteTkt);
                            jQuery('#modQty').val(qty);
                        } else {
                            jQuery('#modQtyTxt').css('display','nonoe');
                            jQuery('#modQty').val(qty);
                    }
                        jQuery('#modpu_ht').val(pu_ht);
                        jQuery('#modLinetva_tx').val(tva_tx);
                        jQuery('#modDur').val(durValid);

                        //Cas du fk_produit
                        if (fk_product+"x" != "x")
                        {
                            var i = 0;
                            var remi = 0;
                            jQuery('#modfk_prod').find('option').each(function(){
                                if (jQuery(this).val() == fk_product)
                                {
                                    remi = i;
                                }
                                i++;
                            });
                            jQuery('#modfk_prod').selectmenu('value',remi);
                        } else {
                            jQuery('#modfk_prod').selectmenu('value',0);
                            jQuery('#modQtyTxt').css('display','none');

                        }
                        //Cas de la tva
                        if (tva_tx + "x" == "x")
                        {
                            var i =0;
                            var remi=0;
                            jQuery('#modLinetva_tx').find('option').each(function(){
                                if (tva_tx == jQuery(this).val())
                                {
                                    remi=i;
                                }
                                i++;

                            });
                            jQuery('#modLinetva_tx').selectmenu('value',remi);
                        } else {
                            jQuery('#modLinetva_tx').selectmenu('value',2);
                        }

                        if (date_ouverture+"x" != 'x')
                        {
                            jQuery('#dateDebmod').val(date_ouverture);
                        } else if (date_ouverture_prevue+"x" != 'x')
                        {
                            jQuery('#dateDebmod').val(date_ouverture_prevue);
                        }

                    },
                });
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
            }
        });
        jQuery.ajax({
            url: DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php",
            data: "action=sortLine&data="+data.toString(),
            datatype: "xml",
            success: function(msg){
                if (jQuery(msg).find('OK').text()=='OK')
                {
                    //
                } else {
                    if (jQuery(msg).find('KO'))
                    console.log('Error')
                }
            }
        });

    }

    function deleteLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery('#delDialog').dialog('option',
           'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
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
            },
        });
        jQuery('#delDialog-content').replaceWith('<div id="delDialog-content" style="padding: 10px;"><span style="float: left; " class="ui-icon ui-icon-alert"></span><span style="margin-top: 10px; margin-left: 10px;">&Ecirc;tes vous sur de vouloir supprimer cette ligne&nbsp;?</span></div>');
        jQuery('#delDialog').dialog('open');
    }

    function updateStatutPanel(idContrat)
    {
        var href=DOL_URL_ROOT+"/Babel_GMAO/ajax/contratTkt_fiche_ajax.php";
        jQuery.ajax({
            url: href,
            data: "id="+idContrat+"&action=getStatut",
            datatype: 'xml',
            success: function(msg)
            {
                var panel = $(msg).find('srvPanel').text();
                jQuery('#statutPanel').fadeOut('fast',function(){ jQuery('#statutPanel').html(jQuery(panel).html()).fadeIn('fast')  });
            }
        })
    }

