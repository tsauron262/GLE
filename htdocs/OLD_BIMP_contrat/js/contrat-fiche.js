
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

        //Ga

        jQuery('.deleteGA').click(function(){
            var id = jQuery(this).attr('id');
        });

        jQuery("#GAaddLine").click(function(){
            var href="ajax/contrat_fiche_ajax.php";

            var fkprod = jQuery("input[name=p_idprod]").val();
            if (fkprod > 0)
            {
                var data = "&fk_product="+fkprod;
                    data += "&qty="+jQuery('#qty').val();
                   jQuery.ajax({
                      url: "ajax/contrat_fiche_ajax.php",
                      datatype: "xml",
                      url: href,
                      data: "id="+idContratCurrent+"&action=addligneGA"+data,
                      success: function(msg){
                        jQuery('#prodTable').append(jQuery(msg).find('OKtext').text());

                        jQuery('.plus').mouseover(function(){
                            jQuery(this).addClass('ui-state-default ui-widget-content');
                            jQuery(this).removeClass('ui-widget-header');
                        });
                        jQuery('.plus').mouseout(function(){
                            jQuery(this).removeClass('ui-state-default ui-widget-content');
                            jQuery(this).addClass('ui-widget-header');
                        });
                        jQuery('.moins').unbind('click');
                        jQuery('.plus').unbind('click');
                        jQuery('.moins').click(function(){
                            var id = jQuery(this).attr('id');
                            var href="ajax/contrat_fiche_ajax.php";
                            var data = "&lineId="+id;
                            var self = jQuery(this);
                            jQuery.ajax({
                                url: href,
                                data: "id="+idContratCurrent+"&action=subQty"+data,
                                datatype: 'xml',
                                success: function(msg)
                                {
                                    if (jQuery(msg).find('OK').text()=='OK')
                                    {
                                        var val = jQuery(msg).find('OKtext').text();
                                        self.parent().parent().find('span').text(val);
                                    }
                                }
                            });
                            //send - to ajax
                            //get back the new qty
                        });
                        jQuery('.plus').click(function(){
                            var id = jQuery(this).attr('id');
                            var href="ajax/contrat_fiche_ajax.php";
                            var data = "&lineId="+id;
                            var self = jQuery(this);
                            jQuery.ajax({
                                url: href,
                                data: "id="+idContratCurrent+"&action=addQty"+data,
                                datatype: 'xml',
                                success: function(msg)
                                {
                                    if (jQuery(msg).find('OK').text()=='OK')
                                    {
                                        var val = jQuery(msg).find('OKtext').text();
                                        self.parent().parent().find('span').text(val);
                                    }
                                }
                            });
                            //send - to ajax
                            //get back the new qty
                        });

                        jQuery('.moins').mouseover(function(){
                            jQuery(this).addClass('ui-state-default ui-widget-content');
                            jQuery(this).removeClass('ui-widget-header');
                        });
                        jQuery('.moins').mouseout(function(){
                            jQuery(this).removeClass('ui-state-default ui-widget-content');
                            jQuery(this).addClass('ui-widget-header');
                        });

                        jQuery('.deleteGA').click(function(){
                            var id = jQuery(this).attr('id');
                            var href="ajax/contrat_fiche_ajax.php";
                            var data = "&lineId="+id;
                            var self = jQuery(this);
                            jQuery.ajax({
                                url: href,
                                data: "id="+idContratCurrent+"&action=delGAline"+data,
                                datatype: 'xml',
                                success: function(msg)
                                {
                                    if (jQuery(msg).find('OK').text()=='OK')
                                    {
                                        self.parent().parent().remove();
                                    }
                                }
                            });
                        });

                    }

                   });
                } else {
                    $("body").append('<div id="dialogTmp">le produit ne peut &ecirc;tre vide</div>');
                    $("#dialogTmp").dialog({
                            title:"Erreur!",
                            modal: true,
                            buttons:{
                                Fermer: function(){
                                    $('#dialogTmp').remove();
                                }
                            }
                        });
                }
            });
        //end GA

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


        jQuery('.plus').mouseover(function(){
            jQuery(this).addClass('ui-state-default ui-widget-content');
            jQuery(this).removeClass('ui-widget-header');
        });
        jQuery('.plus').mouseout(function(){
            jQuery(this).removeClass('ui-state-default ui-widget-content');
            jQuery(this).addClass('ui-widget-header');
        });
        jQuery('.moins').click(function(){
            var id = jQuery(this).attr('id');
            var href="ajax/contrat_fiche_ajax.php";
            var data = "&lineId="+id;
            var self = jQuery(this);
            jQuery.ajax({
                url: href,
                data: "id="+idContratCurrent+"&action=subQty"+data,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        var val = jQuery(msg).find('OKtext').text();
                        self.parent().parent().find('span').text(val);
                    }
                }
            });
            //send - to ajax
            //get back the new qty
        });
        jQuery('.plus').click(function(){
            var id = jQuery(this).attr('id');
            var href="ajax/contrat_fiche_ajax.php";
            var data = "&lineId="+id;
            var self = jQuery(this);
            jQuery.ajax({
                url: href,
                data: "id="+idContratCurrent+"&action=addQty"+data,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        var val = jQuery(msg).find('OKtext').text();
                        self.parent().parent().find('span').text(val);
                    }
                }
            });
            //send - to ajax
            //get back the new qty
        });

        jQuery('.moins').mouseover(function(){
            jQuery(this).addClass('ui-state-default ui-widget-content');
            jQuery(this).removeClass('ui-widget-header');
        });
        jQuery('.moins').mouseout(function(){
            jQuery(this).removeClass('ui-state-default ui-widget-content');
            jQuery(this).addClass('ui-widget-header');
        });

        jQuery('.deleteGA').click(function(){
            var id = jQuery(this).attr('id');
            var href="ajax/contrat_fiche_ajax.php";
            var data = "&lineId="+id;
            var self = jQuery(this);
            jQuery.ajax({
                url: href,
                data: "id="+idContratCurrent+"&action=delGAline"+data,
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        self.parent().parent().remove();
                    }
                }
            });
        });

        jQuery('#renewContrat').click(function(){
            var href="ajax/contrat_fiche_ajax.php";
            jQuery.ajax({
                url: href,
                data: "id="+idContratCurrent+"&action=renew",
                datatype: 'xml',
                success: function(msg)
                {
                    if (jQuery(msg).find('OK').text()=='OK')
                    {
                        //TODO va vers le nouveau contrat
                    }
                }
                });
        });
        jQuery('#clotureGA').click(function(){
            var href="ajax/contrat_fiche_ajax.php";
            //TODO Open dialog => 4 cas possible

        });



        jQuery('.ui-button').mouseover(function(){
            jQuery(this).removeClass('ui-state-default');
            jQuery(this).addClass('ui-state-hover');
        });
        jQuery('.ui-button').mouseout(function(){
            jQuery(this).removeClass('ui-state-hover');
            jQuery(this).addClass('ui-state-default');
        });


        jQuery('#configContrat').click(function(){
            jQuery('#configDialog').dialog('open');
        });

            jQuery('#configDialog').find('#delLigneFinContrat').click(function(){
                if ($(this).parent().parent().parent().find('tr').length >1)
                {
                    $(this).parent().parent().remove();
                }

            })
            jQuery('#configDialog').find('#addLigneFinContrat').click(function(){
                var date = new Date();
                var iter = date.getTime();
                html = '<tr>\
                            <td class="ui-state-default ui-widget-header"><input class="required"  id="design-'+iter+'" name="design-'+iter+'" ></td>\
                            <td align="center" style="width: 100px;" class="ui-widget-content">\
                                <input  id="total-'+iter+'" class="required currency" name="total-'+iter+'" value="0" style="text-align: center; width: 100px;">\
                            </td>\
                            <td align="center" class="ui-widget-content" style="width: 100px;">\
                                <input  id="taux-'+iter+'" class="required percentdecimal" name="taux-'+iter+'"  value="0" style="text-align: center; width: 100px;">\
                            </td>\
                            <td align="center" class="ui-widget-content" style="width: 100px;">\
                                <input  id="marge-'+iter+'Configurer" class="required percentdecimal" name="marge-'+iter+'"  value="0" style="text-align: center; width: 100px;"></td>\
                            <td align="center"><span id="delLigneFinContrat" class="ui-icon ui-icon-trash"></span></td></tr>';


                $(this).parent().parent().parent().parent().append($(html));
                jQuery('#configDialog').find('#delLigneFinContrat').click(function(){
                    if ($(this).parent().parent().parent().find('tr').length >1)
                    {
                        $(this).parent().parent().remove();
                    }

                });

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
                        var href = "ajax/contrat_fiche_ajax.php";
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
            title: "Ajouter une ligne",
            width: 935,
            autoOpen: false,
            buttons: {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
                "Ajouter": function() {
                    if (jQuery('#addForm').validate({
                         rules: {
                            addPrice: {
                                required: function(e){
                                    var fk_prod = jQuery('#ajdynfieldp_idprod_add').find(":selected").val();
                                    if (fk_prod > 0)
                                    {
                                        return(false);
                                    }  else {
                                        return(true);
                                    }
                                },
                            },
                            addDesc: {
                                required: function(e){
                                    var fk_prod = jQuery('#ajdynfieldp_idprod_add').find(":selected").val();
                                    if (fk_prod > 0)
                                    {
                                        return(false);
                                    }  else {
                                        return(true);
                                    }
                                }
                            },
                        },
                        messages: {
                            addPrice: {
                                required: "<br/><span style=' font-size: 9px;'> Merci de saisir un prix</span>",
                            },
                            addDesc: {
                                required: "<br/><span style=' font-size: 9px;'> Merci de saisir une description</span>",
                            },
                        }

                    }).form()){
                        var data = "";
                        var fk_prod = jQuery('#ajdynfieldp_idprod_add').find(":selected").val();
                        if (fk_prod>0)
                        {
                            data += "&p_idprod="+fk_prod;
                        }


                        data += "&desc="+jQuery('#addDesc').val();
                        data += "&pu="+jQuery('#addPrice').val();
                        data += "&tva_tx="+jQuery('#addDialog #addLinetva_tx :selected').val();
                        data += "&pqty="+jQuery('#addQty').val();
                        data += "&premise="+jQuery('#addRemise').val();
                        data += "&dateDeb="+jQuery("#dateDebadd").val();
                        data += "&dateFin="+jQuery("#dateFinadd").val();
                        if (jQuery('#addFinancementadd').attr('checked') == true)
                        {
                            data += "&financement=1";
                            data += "&financDuree="+jQuery('#nbPeriodeadd').val();
                            data += "&financTxAchat="+jQuery('#addTauxAchat').val();
                            data += "&financTx="+jQuery('#addTauxVente').val();
                            data += "&typePeriod="+jQuery('#typePeriodadd :selected').val();
                        } else {
                            data += "&financement=0";
                        }

                        var href="ajax/contrat_fiche_ajax.php";

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
        var href="ajax/contrat_fiche_ajax.php";
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
        var href="ajax/contrat_fiche_ajax.php";
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
        var href="ajax/contrat_fiche_ajax.php";
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
        var href="ajax/contrat_fiche_ajax.php";
        jQuery('#modDialog').dialog('option',
           'buttons',
           {
                "Annuler": function() { jQuery(this).dialog("close"); } ,
                "Modifier": function() {
                    var data = "";

                    var fk_prod = jQuery('#ajdynfieldp_idprod_mod').find(":selected").val();
                    if (fk_prod>0)
                    {
                        data += "&p_idprod="+fk_prod;
                    }
                    data += "&Desc="+jQuery('#modDesc').val();
                    data += "&Price="+jQuery('#modPrice').val();
                    data += "&Linetva_tx="+jQuery('#modDialog #modLinetva_tx :selected').val();
                    data += "&Qty="+jQuery('#modQty').val();
                    data += "&Remise="+jQuery('#modRemise').val();
                    data += "&dateDeb="+jQuery("#dateDebmod").val();
                    data += "&dateFin="+jQuery("#dateFinmod").val();
                    if (jQuery('#addFinancementmod').attr('checked') == true)
                    {
                        data += "&financement=1";
                        data += "&nbPeriode="+jQuery('#nbPeriodemod').val();
                        data += "&TauxAchat="+jQuery('#modTauxAchat').val();
                        data += "&TauxVente="+jQuery('#modTauxVente').val();
                        data += "&typePeriod="+jQuery('#typePeriodmod :selected').val();
                    } else {
                        data += "&financement=0";
                    }
                    jQuery.ajax({
                        url: href,
                        data: "id="+idContrat+"&action=updateline&lineid="+idLigne+data,
                        datatype: 'xml',
                        success: function(msg)
                        {
                            if (jQuery(msg).find('OK').text()=='OK')
                            {
                                var parentO = jQuery(obj).parent().parent().parent().parent().parent();
                                //Recreate the line with new datas
                                var ret = "<div>"+jQuery(msg).find('OKtext').text()+"</div>";
                                var tmp = jQuery(ret).html();
                                parentO.replaceWith(tmp);

                                jQuery('#modDialog').dialog("close");
                                updateStatutPanel(idContrat);
                            } else {
                                alert ('Il y a eu une erreur !!');
                                console.log(jQuery(msg).find('KO').text());
                            }
                        }
                });
            },
        });
        jQuery('#modDialog').bind('dialogopen', function(e,u){
                //get data from ajax
                jQuery.ajax({
                    url: 'ajax/contrat_fiche_ajax.php',
                    datatype: 'xml',
                    data: 'action=getLineDet&idContrat='+idContrat+'&idLigneContrat='+idLigne+'&userId='+userId,
                    success: function(msg){
                        jQuery('#modPrice').val(jQuery(msg).find('price_ht').text());
                        jQuery('#modQty').val(jQuery(msg).find('qty').text());
                        jQuery('#modTVA').val(jQuery(msg).find('tva_tx').text());
                        jQuery('#modDesc').val(jQuery(msg).find('description').text());

                        jQuery('#modQty').val(jQuery(msg).find('qty').text());
//                            if (fk_product > 0) name = jQuery(msg).find('libelleProduit').text() + jQuery(msg).find('description').text();
                        jQuery('#modRemise').val(jQuery(msg).find('remise_percent').text());

//Cas du fk_produit
                        if (jQuery(msg).find('fk_product').text() + "x" != "x")
                        {
                            jQuery('#ajdynfieldp_idprod_mod').html(jQuery(msg).find('libelleProduit').text()+"<select style='display:none' name='p_idprod' id='p_idprod'><option value='"+jQuery(msg).find('fk_product').text()+"'>"+jQuery(msg).find('fk_product').text()+"</option></select>" );
                        }

                        //setDate
                        if (jQuery(msg).find('date_ouverture').text()+"x" != 'x')
                        {
                            jQuery('#dateDebMod').val(jQuery(msg).find('date_ouverture').text());
                        } else if (jQuery(msg).find('date_ouverture_prevue').text()+"x" != 'x')
                        {
                            jQuery('#dateDebmod').val(jQuery(msg).find('date_ouverture_prevue').text());
                        }
                        if (jQuery(msg).find('date_cloture').text()+"x" != 'x')
                        {
                            jQuery('#dateFinmod').val(jQuery(msg).find('date_cloture').text());
                        } else if (jQuery(msg).find('date_fin_validite').text()+"x" != 'x')
                        {
                            jQuery('#dateFinmod').val(jQuery(msg).find('date_fin_validite').text());
                        }

                        if (jQuery(msg).find('financement_id').text() > 0)
                        {
                            jQuery('#addFinancementadd').attr('checked',true);
                            jQuery("#financementLignemod").css('display','block');
                            jQuery("#nbPeriodemod").val(jQuery(msg).find("duree").text());
                            jQuery("#modTauxAchat").val(jQuery(msg).find("tauxachat").text());
                            jQuery("#modTauxVente").val(jQuery(msg).find("taux").text());

                            jQuery('#typePeriodmod').each(function() {
                                //console.log(jQuery(msg).find('financement_period_id').text());
                                if (jQuery(this).attr('id') == jQuery(msg).find('financement_period_id').text())
                                {
                                    jQuery(this).attr('selected',true);
                                } else {
                                    jQuery(this).attr('selected',false);
                                }
                            });
                        } else {
                            jQuery('#addFinancementmod').attr('checked',false);
                            jQuery("#financementLignemod").css('display','none');
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
//                    console.log(jQuery(this).attr('id') + " " + i);
            }
        });
        jQuery.ajax({
            url: "ajax/contrat_fiche_ajax.php",
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
        var href="ajax/contrat_fiche_ajax.php";
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
        var href="ajax/contrat_fiche_ajax.php";
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

