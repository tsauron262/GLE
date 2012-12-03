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
        jQuery.validator.addMethod(
            'reqProduct',
            function(value, element) {
                return (value.match(/^[0-9]+$/) && value>0);
            },"Ce champs est requis"
        );
        jQuery.validator.addMethod(
            'dateOrder',
            function(value, element) {
                var date1 = jQuery('#debut_loc').datepicker('getDate');
                var date2 = jQuery('#fin_loc').datepicker('getDate');
                return (date2 > date1);
            },"<br/>La date de fin doit &ecirc;tre posterieur &agrave; la date de d&eacute;but"
        );
        jQuery.validator.addMethod(
            'moddateOrder',
            function(value, element) {
                var date1 = jQuery('#moddebut_loc').datepicker('getDate');
                var date2 = jQuery('#modfin_loc').datepicker('getDate');
                return (date2 > date1);
            },"<br>La date de fin doit &ecirc;tre posterieur &agrave; la date de d&eacute;but"
        );


        jQuery.datepicker.setDefaults(jQuery.datepicker.regional['fr']);

        //Loc

        jQuery('.deleteLoc').click(function(){
            var id = jQuery(this).attr('id');
        });
        jQuery('#addline').validate({
            rules:{
                "debut_loc": { FRDate: true, required:true, dateOrder: true},
                "fin_loc": { FRDate: true, required:true, dateOrder: true},
                "duration_value": { required:true},
                "duration_unit": { required:true},
                "pu_ht": { required:true},
                "qte": { required:true},
            },
            messages:{
                "duration_value": { required:"<br/>Ce champs est requis"},
                "duration_unit": { required:"<br/>Ce champs est requis"},
                "pu_ht": { required:"<br/>Ce champs est requis"},
                "qte": { required:"<br/>Ce champs est requis"},

            }

        });
        jQuery("#LocaddLine").click(function(){
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";

            var fkprod = jQuery("input[name=p_idprod]").val();
            if (fkprod > 0 && jQuery('#addline').validate({
                rules:{
                    "debut_loc": { FRDate: true, required:true, dateOrder: true},
                    "fin_loc": { FRDate: true, required:true, dateOrder: true},
                    "duration_value": { required:true},
                    "duration_unit": { required:true},
                    "pu_ht": { required:true},
                    "qte": { required:true},
                },
                messages:{
                    "duration_value": { required:"<br/>Ce champs est requis"},
                    "duration_unit": { required:"<br/>Ce champs est requis"},
                    "pu_ht": { required:"<br/>Ce champs est requis"},
                    "qte": { required:"<br/>Ce champs est requis"},

                }
            }).form())
            {
                var data = "&fk_product="+fkprod;
                    data += "&qty="+jQuery('#qty').val();
                    data += "&pu_ht="+jQuery('#pu_ht').val();
                    data += "&debut_loc="+jQuery('#debut_loc').val();
                    data += "&fin_loc="+jQuery('#fin_loc').val();
                    data += "&duration_value="+jQuery('#duration_value').val();
                    data += "&duration_unit="+jQuery('#duration_unit').val();
                    data += "&serial="+jQuery('#serial').val();
                   jQuery.ajax({
                      url: DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php",
                      datatype: "xml",
                      url: href,
                      data: "id="+idContratCurrent+"&action=addligneLoc"+data,
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
                            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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
                                        self.parent().parent().find('span#qteLigne').text(val);
                                        self.parent().parent().parent().parent().parent().find('#totalLigneContrat').html(jQuery(msg).find('total_ht_ligne').text());
                                        jQuery('#totalContrat').html(jQuery(msg).find('total_ht').text());
                                        jQuery('#totalLoyer').html(jQuery(msg).find('loyer').text());
                                    }
                                }
                            });
                            //send - to ajax
                            //get back the new qty
                        });
                        jQuery('.plus').click(function(){
                            var id = jQuery(this).attr('id');
                            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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
                                        self.parent().parent().find('span#qteLigne').text(val);
                                        self.parent().parent().parent().parent().parent().find('#totalLigneContrat').html(jQuery(msg).find('total_ht_ligne').text());
                                        jQuery('#totalContrat').html(jQuery(msg).find('total_ht').text());
                                        jQuery('#totalLoyer').html(jQuery(msg).find('loyer').text());
                                    }
                                }
                            });
                        });

                        jQuery('.moins').mouseover(function(){
                            jQuery(this).addClass('ui-state-default ui-widget-content');
                            jQuery(this).removeClass('ui-widget-header');
                        });
                        jQuery('.moins').mouseout(function(){
                            jQuery(this).removeClass('ui-state-default ui-widget-content');
                            jQuery(this).addClass('ui-widget-header');
                        });

                        jQuery('.deleteLoc').click(function(){
                            var id = jQuery(this).attr('id');
                            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
                            var data = "&lineId="+id;
                            var self = jQuery(this);
                            jQuery.ajax({
                                url: href,
                                data: "id="+idContratCurrent+"&action=delLocline"+data,
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
//TODO Validation du formulaire
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
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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
                        self.parent().parent().find('span#qteLigne').text(val);
                        self.parent().parent().parent().parent().parent().find('#totalLigneContrat').html(jQuery(msg).find('total_ht_ligne').text());
                        jQuery('#totalContrat').html(jQuery(msg).find('total_ht').text());
                        jQuery('#totalLoyer').html(jQuery(msg).find('loyer').text());
                    }
                }
            });
            //send - to ajax
            //get back the new qty
        });
        jQuery('.plus').click(function(){
            var id = jQuery(this).attr('id');
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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
                        self.parent().parent().find('span#qteLigne').text(val);
                        self.parent().parent().parent().parent().parent().find('#totalLigneContrat').html(jQuery(msg).find('total_ht_ligne').text());
                        jQuery('#totalContrat').html(jQuery(msg).find('total_ht').text());
                        jQuery('#totalLoyer').html(jQuery(msg).find('loyer').text());
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

        jQuery('.deleteLoc').click(function(){
            var id = jQuery(this).attr('id');
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
            var data = "&lineId="+id;
            var self = jQuery(this);
            jQuery.ajax({
                url: href,
                data: "id="+idContratCurrent+"&action=delLocline"+data,
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
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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
        jQuery('#clotureLoc').click(function(){
            var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
            //TODO Open dialog => 4 cas possible

        });



        jQuery('.butAction').mouseover(function(){
            jQuery(this).removeClass('ui-state-default');
            jQuery(this).addClass('ui-state-hover');
        });
        jQuery('.butAction').mouseout(function(){
            jQuery(this).removeClass('ui-state-hover');
            jQuery(this).addClass('ui-state-default');
        });


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

    function deleteLine(obj,idContrat,idLigne)
    {
        var href=DOL_URL_ROOT+"/Babel_GA/ajax/contratLoc_fiche_ajax.php";
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

