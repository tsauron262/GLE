
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




        jQuery('#renewContrat').click(function(){
            var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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
        jQuery('.butAction').mouseover(function(){
            jQuery(this).removeClass('ui-state-default');
            jQuery(this).addClass('ui-state-hover');
        });
        jQuery('.butAction').mouseout(function(){
            jQuery(this).removeClass('ui-state-hover');
            jQuery(this).addClass('ui-state-default');
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
                            p_idprod_add: {
                                required: function(e)
                                {
                                    if (jQuery('SELECT#p_idprod_add').length > 0)
                                    {
                                        var fk_prod = jQuery('SELECT#p_idprod_add').find(":selected").val();
                                        if (fk_prod > 0)
                                        {
                                            return(true);
                                        }  else {
                                            return(false);
                                        }
                                    } else {
                                        return(true);
                                    }
                                },
                            },
                            addDur: {
                                sup1: true,
                                required: true,
                            },
                            dateDebadd: {
                                FRDate: true,
                            },
                            addserial: {
                                required: true,
                            },
                            addPrice: {
                                currency: true,
                                requiredNoBR: function(e){
                                    if (jQuery('#ajdynfieldpcontrat_idprod_add').find('#pcontrat_idprod_add').find(":selected").val() > 0) {
                                        jQuery(e).val(0);
                                        return true;
                                    } else {
                                        return jQuery(e).val().match(/^[\w\W\d]+$/);
                                    }
                                },
                            }
                        },
                        messages: {
                            addPrice: {
                                requiredNoBR: "<br/><span style='font-size: 9px;'> Merci de saisir un prix ou de choisir un contrat</span>",
                                currency: "<br/><span style='font-size: 9px;'> Le prix n'est pas au bon format</span>",
                            },
                            p_idprod_add: {
                                required: "<span style=' font-size: 9px;'> Merci de choisir un produit</span>",
                            },
                        }

                    }).form()){
                        var data = "";
                        var fk_prod = jQuery('#ajdynfieldp_idprod_add').find(":selected").val();
                        if (fk_prod>0)
                        {
                            data += "&p_idprod="+fk_prod;
                        }
                        data += "&serial="+jQuery('#addserial').val();
                        data += "&fk_prod_contrat="+jQuery('select#pcontrat_idprod_add').find(':selected').val();
                        data += "&valDur="+jQuery('#addDur').val();
                        data += "&desc="+jQuery('#addDesc').val();
                        data += "&pu="+jQuery('#addPrice').val();
                        data += "&tva_tx="+jQuery('#addDialog #addLinetva_tx :selected').val();
//                        data += "&pqty="+jQuery('#addQty').val();
//                        data += "&premise="+jQuery('#addRemise').val();
                        data += "&dateDeb="+jQuery("#dateDebadd").val();

                        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";

                        jQuery.ajax({
                            url: href,
                            data: "id="+idContratCurrent+"&action=addligne"+data,
                            datatype: 'xml',
                            success: function(msg)
                            {
                                if (jQuery(msg).find('OK').text()=='OK')
                                {
//                                    console.log(msg);
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

        jQuery('#modDialog').bind('dialogopen', function(e,u){
                //get data from ajax
                jQuery.ajax({
                    url: DOL_URL_ROOT+'/Babel_GMAO/ajax/contratSAV_fiche_ajax.php',
                    datatype: 'xml',
                    data: 'action=getLineDet&idContrat='+g_idContrat+'&idLigneContrat='+g_idLigne+'&userId='+userId,
                    success: function(msg){
                        jQuery('#modPrice').val(jQuery(msg).find('price_ht').text());
                        jQuery('#modQty').val(jQuery(msg).find('qty').text());
                        jQuery('#modTVA').val(jQuery(msg).find('tva_tx').text());
                        jQuery('#modDesc').val(jQuery(msg).find('description').text());
                        jQuery('#modserial').val(jQuery(msg).find('serial_number').text());
                        jQuery('#modQty').val(jQuery(msg).find('qty').text());
                        jQuery('#modDur').val(jQuery(msg).find('durValid').text());
//                            if (fk_product > 0) name = jQuery(msg).find('libelleProduit').text() + jQuery(msg).find('description').text();
                        jQuery('#modRemise').val(jQuery(msg).find('remise_percent').text());


                        //setDate
                        if (jQuery(msg).find('date_ouverture').text()+"x" != 'x')
                        {
                            jQuery('#dateDebmod').val(jQuery(msg).find('date_ouverture').text());
                        } else if (jQuery(msg).find('date_ouverture_prevue').text()+"x" != 'x')
                        {
                            jQuery('#dateDebmod').val(jQuery(msg).find('date_ouverture_prevue').text());
                        }
//Cas du fk_produit
                        if (jQuery(msg).find('fk_product').text() + "x" != "x")
                        {
                            jQuery('#ajdynfieldp_idprod_mod').html(jQuery(msg).find('libelleProduit').text()+"<select style='display:none' name='p_idprod' id='p_idprod'><option value='"+jQuery(msg).find('fk_product').text()+"'>"+jQuery(msg).find('fk_product').text()+"</option></select>" );
                        }
//Cas du fk_contrat_produit

//            $xml.='<fk_contrat_produit>'.$res2->fk_contrat_prod.'</fk_contrat_produit>';
//                $xml .= "<contrat_prod_libelleProduit>".$res1->label."</contrat_prod_libelleProduit>";
//                $xml .= "<contrat_prod_descriptionProduit>".$res1->description."</contrat_prod_descriptionProduit>";
                        if (jQuery(msg).find('fk_contrat_produit').text() + "x" != "x")
                        {
                            jQuery('#ajdynfieldpcontrat_idprod_mod').html("<div style='padding-left: 97px; margin-top: -30px;'>"+jQuery(msg).find('contrat_prod_libelleProduit').text()+"</div><select style='display:none' name='pcontrat_idprod' id='pcontrat_idprod_mod'><option value='"+jQuery(msg).find('fk_contrat_produit').text()+"'>"+jQuery(msg).find('fk_contrat_produit').text()+"</option></select>" );
                        }

                    },
                });
        });

    });
    function activateLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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
        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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
        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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

    var g_idContrat;
    var g_idLigne;
    var g_obj;

    function editLine(obj,idContrat,idLigne)
    {
        g_idContrat = idContrat;
        g_idLigne = idLigne;
        g_obj = obj;

        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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
                        data += "&serial="+jQuery('#modserial').val();
                        data += "&fk_prod_contrat="+jQuery('select#pcontrat_idprod_mod').find(':selected').val();
                        data += "&valDur="+jQuery('#modDur').val();
                        data += "&desc="+jQuery('#modDesc').val();
                        data += "&pu="+jQuery('#modPrice').val();
                        data += "&tva_tx="+jQuery('#modDialog #modLinetva_tx :selected').val();
                        data += "&dateDeb="+jQuery("#dateDebmod").val();



                    jQuery.ajax({
                        url: href,
                        data: "id="+idContrat+"&action=modligne&lineid="+idLigne+data,
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
            url:DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php",
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
        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
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
        var href=DOL_URL_ROOT+"Babel_GMAO/ajax/contratSAV_fiche_ajax.php";
        jQuery.ajax({
            url: href,
            data: "id="+idContrat+"&action=getStatut",
            datatype: 'xml',
            success: function(msg)
            {
                var panel = jQuery(msg).find('srvPanel').text();
                jQuery('#statutPanel').fadeOut('fast',function(){ jQuery('#statutPanel').html(jQuery(panel).html()).fadeIn('fast')  });
            }
        })
    }

