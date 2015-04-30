var g;
var obj1;
var mod="";
var remLeft;

jQuery(document).ready(function()
{
    g = new JSGantt.GanttChart('g',document.getElementById('GanttChartDIV'), 'day');

    g.setShowRes(1); // Show/Hide Responsible (0/1)
    g.setShowDur(1); // Show/Hide Duration (0/1)
    g.setShowComp(1); // Show/Hide % Complete(0/1)
    g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
    g.setShowStartDate(1); // Show/Hide Start Date(0/1)
    g.setShowEndDate(1); // Show/Hide End Date(0/1)
    g.setDateInputFormat('yyyy-mm-dd');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
    g.setDateDisplayFormat('dd/mm/yyyy'); // Set format to display dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
    g.setFormatArr( "day","week","month"); // Set format options (up to 4 : "minute","hour","day","week","month","quarter")

    JSGantt.parseXML("project-xmlresponse.php?id="+project_id,g);


    g.Draw();
    g.DrawDependencies();
    resetDialog();
    jQuery.validator.addMethod(
	"FRDate",
	function(value, element) {
	    // put your own logic here, this is just a (crappy) example
	    return value.match(/^\d\d?\/\d\d?\/\d\d\d\d\W?\d?\d?\:?\d?\d?$/);
	},
	"La date doit �tre au format dd/mm/yyyy hh:mm"
	);

    jQuery.validator.addMethod(
	"speselect",
	function(value, element) {
	    // put your own logic here, this is just a (crappy) example
	    var ret = false;
	    if (value > -2 ) {
		ret = true;
	    }
	    return ret;
	},
	"Merci de s&eacute;lectionner un &eacute;l&eacute;ment dans la liste"
	);


    jQuery("#deldyalog").dialog({
	autoOpen: false,
	modal: true ,
	show: 'slide',
	title: 'Modification d\'une t&acirc;che',
	width: 740,
	position: "center",
	bgiframe: true,
	buttons: {
	    "Ok": function()

	    {
		if (currentModTask > 0)
		{
		    resetDialog();
		    //Call jq modal
		    jQuery.ajax({
			async: false,
			type: "post",
			url: "project-xmlresponse.php",
			data: "taskId="+currentModTask+"&action=delete",
			success: function(msg){
			    //reload
			    jQuery("#deldyalog").dialog('close');
			    jQuery("#GanttChartDIV").replaceWith('<div style="position:relative" class="gantt" id="GanttChartDIV"></div>');
			    g = new JSGantt.GanttChart('g',document.getElementById('GanttChartDIV'), 'day');

			    g.setShowRes(1); // Show/Hide Responsible (0/1)
			    g.setShowDur(1); // Show/Hide Duration (0/1)
			    g.setShowComp(1); // Show/Hide % Complete(0/1)
			    g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
			    g.setShowStartDate(1); // Show/Hide Start Date(0/1)
			    g.setShowEndDate(1); // Show/Hide End Date(0/1)
			    g.setDateInputFormat('yyyy-mm-dd');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			    g.setDateDisplayFormat('dd/mm/yyyy'); // Set format to display dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			    g.setFormatArr("hour", "day","week","month"); // Set format options (up to 4 : "minute","hour","day","week","month","quarter")

			    JSGantt.parseXML("project-xmlresponse.php?id="+project_id,g);
			    g.Draw();
			    g.DrawDependencies();
			    redrawResizeDrag();
			}
		    });
		} else {
		    alert ('On ne peut effacer un projet dans cette interface');
		}

	    },
	    "Annuler": function(){
		jQuery("#deldyalog").dialog('close');
	    }
	} // Close button
    });


jQuery("#dialog").dialog({
    autoOpen: false,
    modal: true ,
    show: 'slide',
    title: 'Modification d\'une t&acirc;che',
    width: 830,
    position: "center",
    bgiframe: true,
    open: function(event, ui) {
	resetDialog();
	//Call jq modal
	colorInit="";
	if (!trancheHoraireSav[currentModTask])
	{
	    trancheHoraireSav[currentModTask]=new Array();
	}
	jQuery.ajax({
	    async: false,
	    type: "post",
	    url: "project-xmlresponse.php",
	    data: "taskId="+currentModTask+"&action=descTask",
	    success: function(msg){
                msg = jQuery(msg).find('task:first');
		jQuery("#Modname").val(jQuery(msg).find('pName').text());
		jQuery("#Moddatedeb").val(jQuery(msg).find('pStart').text());
		jQuery("#Moddatefin").val(jQuery(msg).find('pEnd').text());
		var parentId = jQuery(msg).find('pParent').text();
		jQuery("#Modparent").find('option').each(function(){
		    if (parentId == jQuery(this).val())
		    {
			jQuery(this).attr('selected',true);
			return(true);
		    }
		});
		var ModtypeId = jQuery(msg).find('pType').text();
		jQuery("#Modtype").find('option').each(function(){
		    if (ModtypeId == jQuery(this).val())
		    {
			jQuery(this).attr('selected',true);
			return(true);
		    }
		});
		jQuery("#Modcomplet").val(jQuery(msg).find('pComp').text());
		jQuery("#Modgroup").val(jQuery(msg).find('pGroup').text());
		jQuery("#Moddepend").val(jQuery(msg).find('depend').text());
		jQuery("#ModshortDesc").val(jQuery(msg).find('caption').text());
		jQuery("#ModColor").val(jQuery(msg).find('pColor').text());
		jQuery("#ModUrl").val(jQuery(msg).find('pLink').text());
		jQuery("#pMile").val(jQuery(msg).find('pMile').text());
		jQuery("#pOpen").val(jQuery(msg).find('pOpen').text());
		colorInit = jQuery(msg).find('pColor').text();
		//    remove table content
		jQuery('#DepresultMod').find('tr').each(function(){
		    jQuery(this).remove();
		});


		jQuery(msg).find('Depends').find('depend').each(function(){//TODO
		    var pDependId = jQuery(this).find("pDependId").text();
		    var pDependName = jQuery(this).find("pDependName").text();
		    var pDependPerc = jQuery(this).find("pDependPercent").text();
		    //add content
		    if (pDependName +"x" != "x")
		    {

			// remove from list
			jQuery('#Moddepend option').each(function(){
			    if (jQuery(this).val() == pDependId)
			    {
				jQuery(this).remove();
			    }
			});
			if (jQuery('#Moddepend option').length < 2)
			{
			    jQuery('#Moddepend').attr('disabled',true);
			} else {
			    jQuery('#Moddepend').attr('disabled',false);
			}
			var longHtml = "<tr class='ui-widget-content '>" +
			"           <td align='center' class='ui-widget-content'>"+pDependId+"</td> \
                                                                        <td align='center' class='ui-widget-content'>"+pDependName+"</td> \
                                                                        <td align='center' class='ui-widget-content'>"+pDependPerc+"</td> \
                                                                        <td align='center' class='ui-widget-content'><span class='delFromDepTableMod'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                                                    </tr>";
			jQuery('#DepresultMod').append(jQuery(longHtml));
			//init Del
			jQuery('.delFromDepTable'+mod).click(function(){
			    var id = jQuery(this).parent().parent().find('td:nth-child(1)').text();
			    var name = jQuery(this).parent().parent().find('td:nth-child(2)').text();
			    var accomp = jQuery(this).parent().parent().find('td:nth-child(3)').text();

			    jQuery('#'+mod+'depend').append(jQuery('<option value="'+id+'">'+name+'</option>'));
			    jQuery('#'+mod+'depend').attr('disabled',false);
			    jQuery(this).parent().parent().remove();

			});
		    }
		});

		//ressource
		//reinit List
		jQuery('#resultMod').find('tr').each(function(){
		    jQuery(this).remove();
		});
		//admin
		jQuery(msg).find('admin').find('user').each(function(){
		    var id = jQuery(this).find('userid').text();
		    var name = jQuery(this).find('username').text();
		    var occup = jQuery(this).find('percent').text();
		    var type = jQuery(this).find('type').text();

		    var longHtml = "<tr class='ui-widget-content tooltipTranche'>" +
		    "           <td align='center' class='ui-widget-content'>"+type+"</td> \
                                                                <td align='center' class='ui-widget-content'>"+name+"</td> \
                                                                <td align='center' class='ui-widget-content'>"+occup+"</td> \
                                                                <td align='center' class='ui-widget-content'>admin</td> \
                                                                <td align='center' class='ui-widget-content'><span id='Iduser"+id+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                                            </tr>";
		    mod='Mod';
		    currentUser = id;
		    jQuery('#result'+mod).append(jQuery(longHtml));

		    //Remove from list
		    jQuery('#SelUser'+mod).find('option').each(function(){
			if (jQuery(this).val() == currentUser)
			{
			    jQuery(this).remove();
			}
		    });
		    if (jQuery('#SelUser'+mod).find('option').length == 1)
		    {
			currentUser = 0;
			jQuery('#SelUser'+mod).attr('disabled',true);
			jQuery("#AddToTable"+mod).css('display',"none");
		    } else {
			jQuery('#SelUser'+mod).attr('disabled',false);
			jQuery("#AddToTable"+mod).css('display',"block");
			jQuery('#select'+currentUser).attr('disabled',false);
			jQuery('.sliderOccupDial').slider( 'enable' );
			currentUser = 0;
		    }

		});


		jQuery(msg).find('acto').find('user').each(function(){
		    var id = jQuery(this).find('userid').text();
		    var name = jQuery(this).find('username').text();
		    var occup = jQuery(this).find('percent').text();
		    var type = jQuery(this).find('type').text();

		    var longHtml = "<tr class='ui-widget-content tooltipTranche'>" +
		    "           <td align='center' class='ui-widget-content'>"+type+"</td> \
                                                                    <td align='center' class='ui-widget-content'>"+name+"</td> \
                                                                    <td align='center' class='ui-widget-content'>"+occup+"</td> \
                                                                    <td align='center' class='ui-widget-content'>actor</td> \
                                                                    <td align='center' class='ui-widget-content'><span id='Iduser"+id+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                                                </tr>";
		    mod='Mod';
		    currentUser = id;
		    jQuery('#result'+mod).append(jQuery(longHtml));

		    //Remove from list
		    jQuery('#SelUser'+mod).find('option').each(function(){
			if (jQuery(this).val() == currentUser)
			{
			    jQuery(this).remove();
			}
		    });
		    if (jQuery('#SelUser'+mod).find('option').length == 1)
		    {
			currentUser = 0;
			jQuery('#SelUser'+mod).attr('disabled',true);
			jQuery("#AddToTable"+mod).css('display',"none");
		    } else {
			jQuery('#SelUser'+mod).attr('disabled',false);
			jQuery("#AddToTable"+mod).css('display',"block");
			jQuery('#select'+currentUser).attr('disabled',false);
			jQuery('.sliderOccupDial').slider( 'enable' );
			currentUser = 0;
		    }
		});

		jQuery(msg).find('read').find('user').each(function(){
		    var id = jQuery(this).find('userid').text();
		    var name = jQuery(this).find('username').text();
		    var occup = jQuery(this).find('percent').text();
		    var type = jQuery(this).find('type').text();

		    var longHtml = "<tr class='ui-widget-content tooltipTranche'>" +
		    "           <td align='center' class='ui-widget-content'>"+type+"</td> \
                                                                    <td align='center' class='ui-widget-content'>"+name+"</td> \
                                                                    <td align='center' class='ui-widget-content'>"+occup+"</td> \
                                                                    <td align='center' class='ui-widget-content'>read</td> \
                                                                    <td align='center' class='ui-widget-content'><span id='Iduser"+id+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                                                </tr>";
		    mod='Mod';
		    currentUser = id;
		    jQuery('#result'+mod).append(jQuery(longHtml));

		    //Remove from list
		    jQuery('#SelUser'+mod).find('option').each(function(){
			if (jQuery(this).val() == currentUser)
			{
			    jQuery(this).remove();
			}
		    });
		    if (jQuery('#SelUser'+mod).find('option').length == 1)
		    {
			currentUser = 0;
			jQuery('#SelUser'+mod).attr('disabled',true);
			jQuery("#AddToTable"+mod).css('display',"none");
		    } else {
			jQuery('#SelUser'+mod).attr('disabled',false);
			jQuery("#AddToTable"+mod).css('display',"block");
			jQuery('#select'+currentUser).attr('disabled',false);
			jQuery('.sliderOccupDial').slider( 'enable' );
			currentUser = 0;
		    }
		});

		jQuery(".delFromTable").click(function(){
		    delFromTable(this,currentUser);
		}); //close delete
		//Tranche Horaire
		jQuery(msg).find('trancheHoraires').find('trancheHoraire').each(function(){
		    var idTranche = jQuery(this).find('idTranche').text();
		    var idUser = jQuery(this).find('idUser').text();
		    var jour = jQuery(this).find('day').text();
		    var type = jQuery(this).find('type').text();
		    var qte = jQuery(this).find('qte').text();
		    if (!trancheHoraireSav[currentModTask])
		    {
			trancheHoraireSav[currentModTask] = new Array();
		    }
		    if (!trancheHoraireSav[currentModTask][type])
		    {
			trancheHoraireSav[currentModTask][type]=new Array();
		    }
		    if (!trancheHoraireSav[currentModTask][type][idUser])
		    {
			trancheHoraireSav[currentModTask][type][idUser]=new Array();
		    }
		    if (!trancheHoraireSav[currentModTask][type][idUser][jour])
		    {
			trancheHoraireSav[currentModTask][type][idUser][jour]=new Array();
		    }
		    trancheHoraireSav[currentModTask][type][idUser][jour][idTranche]=qte;
		});
	    }//fin de success
	});//fin de ajax
	jQuery.farbtastic('#colorpicker1',function callback(color) 
	{  
	    $('#color1').val(color) ;
	    $('#color1').css('background-color',color) ;
	}).setColor('#'+colorInit);



	init2ndPanel("Mod");
	jQuery(".tooltipTranche").tooltip({
	    track: true,
	    delay: 100,
	    showURL: false,
	    showBody: " - ",
	    opacity: 0.95,
	    extraClass: "promoteZZ",
	    fade: 250,
	    bodyHandler: function() {
		//get Id
		//
		var obj = this;
		return(initToolTipUser(obj));
	    }
	});
	currentGrp = 0;
	currentUser = 0;
    },
    buttons: {
	"Ok": function()

	{
	    mod="Mod";
	    var action = "Mod";
	    //send datas to project-xmlresponse.php?id=
	    var postStr = "";
	    postStr += "&datedeb="+jQuery("#Moddatedeb").val();
	    postStr += "&datefin="+jQuery("#Moddatefin").val();
	    postStr += "&parent="+jQuery("#Modparent").val();
	    postStr += "&type="+jQuery("#Modtype").val();
	    postStr += "&progress="+jQuery("#Modcomplet").val();
	    postStr += "&url="+jQuery("#ModUrl").val();
	    postStr += "&description="+jQuery("#ModDesc").val();
	    postStr += "&shortDescription="+jQuery("#ModshortDesc").val();
	    postStr += "&name="+jQuery("#Modname").val();
	    //var color = jQuery("#color").css('background-color');
	    //color = rgbToHex(color);
	    var color = jQuery("#color1").val() ;
	    if(color.indexOf("#", 0) >= 0)
		color = color.substr(1, 6) ;
	    postStr += "&color="+color;
	    postStr += "&userid="+user_id;



	    var Modressource = new Array();
	    var RemId=new Array();
	    RemId["Group"]=new Array();
	    RemId["User"]=new Array();
	    jQuery('#resultMod').find('tr').each(function(){
		var type = jQuery(this).find('td:nth-child(1)').text();
		var id = jQuery(this).find('td:nth-child(5)').find('span').attr('id').replace(/[a-zA-Z]*/,"");
		var percent = jQuery(this).find('td:nth-child(3)').text();
		var role = jQuery(this).find('td:nth-child(4)').text();
		RemId[type]=id;
		Modressource.push(type+":"+id+":"+percent+":"+role);
	    });
	    var ModressourceStr = Modressource.join(',');

	    //Horaire spe
	    var trancheHorairePost=new Array();
	    //user
	    for (var iiiii in trancheHoraireSav[currentModTask])
	    {
		var type = iiiii;
		for (var ii in trancheHoraireSav[currentModTask][type])
		{
		    var userId = ii;
		    for (var iii in trancheHoraireSav[currentModTask][type][ii])
		    {
			var jour = iii;
			for (var iiii in trancheHoraireSav[currentModTask][type][ii][iii])
			{
			    var trancheId = iiii;
			    if (trancheId > 0 && trancheId < 999)
			    {
				var qte = trancheHoraireSav[currentModTask][type][ii][iii][iiii];
				var Str = type+':'+userId+":"+jour+":"+trancheId+":"+qte;
				trancheHorairePost.push(Str);
			    }
			}
		    }
		}
	    }
	    var tmpTrancheStr = trancheHorairePost.join(',');
	    postStr += '&TrancheHoraire='+tmpTrancheStr;

	    var Moddepend = "";
	    var ModdependArr = new Array();
	    //tout ceux de la table
	    jQuery('#DepresultMod').find('tr').each(function(){
		var id = jQuery(this).find('td:nth-child(1)').text();
		var percent = jQuery(this).find('td:nth-child(3)').text();
		ModdependArr.push(id+":"+percent);
	    });

	    postStr += "&ressource="+ModressourceStr;
	    postStr += "&depend="+Moddepend;

	    if (jQuery("#ModForm").validate({
		rules: {
		    Moddatedeb: {
			FRDate: true,
			required: true
		    },
		    Moddatefin: {
			FRDate: true,
			required: true
		    },
		    Modparent: {
			required: true,
			speselect: true
		    },
		    Modname: {
			required: true,
			minlength: 2
		    }
		},
		messages: {
		    Moddatedeb: {
			FRDate: "<br>Le format de la date est inconnu",
			required: "<br>Champ requis"
		    },
		    Moddatefin: {
			FRDate: "<br>Le format de la date est inconnu",
			required: "<br>Champ requis"
		    },
		    Modname: {
			required: "<br>Champ requis",
			minlength: "<br>Le nom doit faire au moins 2 caract&egrave;res"
		    },
		    Modparent: {
			required: "<br>Champ requis",
			speselect: "Merci de s&eacute;l&eacute;tionner un parent"
		    }
		}
	    }).form()) {
		jQuery.ajax({
		    async: true,
		    type: "post",
		    url: "project-xmlresponse.php",
		    data: "id="+project_id+"&action=update&taskId=" + currentModTask + postStr,
		    success: function(msg){
			jQuery("#GanttChartDIV").replaceWith('<div style="position:relative" class="gantt" id="GanttChartDIV"></div>');
			g = new JSGantt.GanttChart('g',document.getElementById('GanttChartDIV'), 'day');

			g.setShowRes(1); // Show/Hide Responsible (0/1)
			g.setShowDur(1); // Show/Hide Duration (0/1)
			g.setShowComp(1); // Show/Hide % Complete(0/1)
			g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
			g.setShowStartDate(1); // Show/Hide Start Date(0/1)
			g.setShowEndDate(1); // Show/Hide End Date(0/1)
			g.setDateInputFormat('yyyy-mm-dd');  // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			g.setDateDisplayFormat('dd/mm/yyyy'); // Set format to display dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			g.setFormatArr("hour", "day","week","month"); // Set format options (up to 4 : "minute","hour","day","week","month","quarter")

			JSGantt.parseXML("project-xmlresponse.php?id="+project_id,g);
			g.Draw();
			g.DrawDependencies();
			redrawResizeDrag();
			jQuery("#dialog").dialog('close');
		    }
		}); //close ajax
	    } // close validator
	}
    } // Close button
});
//End dialog
jQuery("#ajouterPanel").dialog({
    autoOpen: false,
    modal: true ,
    show: 'slide',
    title: 'Ajouter une t&acirc;che',
    width: 830,
    bgiframe: true,
    open: function(){
	currentGrp = 0;
	currentUser = 0;
	init2ndPanel("add");
	currentModTask=-1;
    },
    position: "center",
    buttons: {
	Ok: function(){
	    currentModTask=-1;
	    if (!trancheHoraireSav[currentModTask])
	    {
		trancheHoraireSav[currentModTask]=new Array();
	    }
	    var name=jQuery("#addname").val();
	    var datedeb=jQuery("#adddatedeb").val();
	    var datefin=jQuery("#adddatefin").val();
	    var color = jQuery("#color").val();
	    if(color.indexOf("#", 0) >= 0)
		color = color.substr(1, 6) ;
	    //color = rgbToHex(color);
	    var complet = jQuery("#addcomplet").val();
	    if ("x"+complet == "x")
	    {
		complet=0;
	    }
	    var desc = jQuery("#addDesc").val();
	    var shortDesc = jQuery("#addshortDesc").val();
	    var url = jQuery("#addUrl").val();

	    var parent = jQuery("#addparent").val();
	    var type = jQuery("#addtype").val();

	    var Modressource = new Array();
	    jQuery('#resultadd').find('tr').each(function(){
		var type = jQuery(this).find('td:nth-child(1)').text();
		var id = jQuery(this).find('td:nth-child(5)').find('span').attr('id').replace(/[a-zA-Z]*/,"");
		var percent = jQuery(this).find('td:nth-child(3)').text();
		var role = jQuery(this).find('td:nth-child(4)').text();
		Modressource.push(type+":"+id+":"+percent+":"+role);
	    });
	    var ModressourceStr = Modressource.join(',');

	    var Moddepend = "";
	    var ModdependArr = new Array();
	    //tout ceux de la table
	    jQuery('#Depresultadd').find('tr').each(function(){
		var id = jQuery(this).find('td:nth-child(1)').text();
		var percent = jQuery(this).find('td:nth-child(3)').text();
		ModdependArr.push(id+":"+percent);
	    });
	    Moddepend = ModdependArr.join(',');

	    var postStr = "action=insert&id="+project_id;
	    postStr += "&name="+name;
	    postStr += "&datedeb="+datedeb;
	    postStr += "&datefin="+datefin;
	    postStr += "&color="+color;
	    postStr += "&userid="+user_id;
	    postStr += "&complet="+complet;
	    postStr += "&desc="+desc;
	    postStr += "&shortDesc="+shortDesc;
	    postStr += "&url="+url;
	    postStr += "&parent="+parent;
	    postStr += "&type="+type;
	    postStr += "&ressource="+ModressourceStr;
	    postStr += "&depend="+Moddepend;


	    //Horaire spe
	    var trancheHorairePost=new Array();
	    //user
	    for (var iiiii in trancheHoraireSav[currentModTask])
	    {
		var type = iiiii;
		for (var ii in trancheHoraireSav[currentModTask][type])
		{
		    var userId = ii;
		    for (var iii in trancheHoraireSav[currentModTask][type][ii])
		    {
			var jour = iii;
			for (var iiii in trancheHoraireSav[currentModTask][type][ii][iii])
			{
			    var trancheId = iiii;
			    if (trancheId > 0 && trancheId < 999)
			    {
				var qte = trancheHoraireSav[currentModTask][type][ii][iii][iiii];
				var Str = type+':'+userId+":"+jour+":"+trancheId+":"+qte;
				trancheHorairePost.push(Str);
			    }
			}
		    }
		}
	    }
	    var tmpTrancheStr = trancheHorairePost.join(',');
	    postStr += '&TrancheHoraire='+tmpTrancheStr;

	    if (jQuery("#addForm").validate({
		rules: {
		    adddatedeb: {
			FRDate: true,
			required: true
		    },
		    adddatefin: {
			FRDate: true,
			required: true
		    },
		    addparent: {
			required: true,
			speselect: true
		    },
		    addname: {
			required: true,
			minlength: 2
		    }
		},
		messages: {
		    adddatedeb: {
			FRDate: "Le format de la date est inconnu",
			required: "<br>Champ requis"
		    },
		    adddatefin: {
			FRDate: "<br>Le format de la date est inconnu",
			required: "<br>Champ requis"
		    },
		    addname: {
			required: "<br>Champ requis",
			minlength: "<br>Le nom doit faire au moins 2 caract&egrave;res"
		    },
		    addparent: {
			required: "<br>Champ requis",
			speselect: "Merci de s&eacute;lectionner un parent"
		    }
		}
	    }).form()) {

		jQuery.ajax({
		    type: "POST",
		    url: "project-xmlresponse.php",
		    data: postStr,
		    success: function(msg){
			jQuery("#GanttChartDIV").replaceWith('<div style="position:relative" class="gantt" id="GanttChartDIV"></div>');
			g = new JSGantt.GanttChart('g', document.getElementById('GanttChartDIV'), 'day');

			g.setShowRes(1); // Show/Hide Responsible (0/1)
			g.setShowDur(1); // Show/Hide Duration (0/1)
			g.setShowComp(1); // Show/Hide % Complete(0/1)
			g.setCaptionType('Resource'); // Set to Show Caption (None,Caption,Resource,Duration,Complete)
			g.setShowStartDate(1); // Show/Hide Start Date(0/1)
			g.setShowEndDate(1); // Show/Hide End Date(0/1)
			g.setDateInputFormat('yyyy-mm-dd'); // Set format of input dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			g.setDateDisplayFormat('dd/mm/yyyy'); // Set format to display dates ('mm/dd/yyyy', 'dd/mm/yyyy', 'yyyy-mm-dd')
			g.setFormatArr("hour", "day", "week", "month"); // Set format options (up to 4 : "minute","hour","day","week","month","quarter")
			JSGantt.parseXML("project-xmlresponse.php?id=" + project_id, g);
			g.Draw();
			redrawResizeDrag();
			g.DrawDependencies();
			jQuery("#ajouterPanel").dialog('close');
		    }
		});
	    }
	}
    }

});   // close dialog

//jQuery('#colorpicker').farbtastic('#color') ;


//DatePicker

jQuery.datepicker.setDefaults(jQuery.extend({
    showMonthAfterYear: false
}, jQuery.datepicker.regional['fr']));
var arr = new Array("adddatedeb", "adddatefin", "Moddatedeb", "Moddatefin");
for (i in arr)
{
    jQuery("#"+arr[i]).datetimepicker({
	dateFormat: 'dd/mm/yy',
	changeMonth: true,
	changeYear: true,
	showButtonPanel: true,
	buttonImage: 'cal.png',
	buttonImageOnly: true,
	showTime: true,
	duration: '',
	constrainInput: false
    });

}
jQuery("#ui-datepicker-div").addClass("promoteZ");
jQuery("#ui-timepicker-div").addClass("promoteZ");

//Find Date Picker

//Buttons

jQuery("#ajouter").click(function(){
    mod="add";
    resetDialog();
    jQuery("#ajouterPanel").dialog('open');
});
//End button
//dialog accordion

jQuery("#accordionadd").tabs({
    cache: true,
    fx: {
	opacity: 'toggle'
    }, 
    spinner:"Chargement ..."
});
jQuery("#accordionMod").tabs({
    cache: true,
    fx: {
	opacity: 'toggle'
    }, 
    spinner:"Chargement ..."
});
redrawResizeDrag();

}); //end document.ready



// Resize BG
function redrawResizeDrag()
{
    //cnt num task
    jQuery('.rowTask').each(function(){
	//get Id
	var id = jQuery(this).attr('id');
	if (id.match(new RegExp("^child_","g")))
	{
	    tid = id.replace(new RegExp("^child_","g"),"");
	    var height = jQuery("#child_"+tid).css('height');
	    //                    height = parseInt(height) - 1 ;
	    //                console.log(height);
	    //                    height = height+"px";
	    //                  console.log(height);
	    jQuery("#childrow_"+tid).css('height',height);
	    jQuery("#childgrid_"+tid).css('height',height);
	    //                jQuery("#childrow_"+tid).css('minHeight',height);
	    //                jQuery("#childrow_"+tid).css('maxHeight',height);
	    jQuery("#childrow_"+tid).css('line-height',height);

	    jQuery('#bardiv_'+tid).css('top',parseInt(height)/2-5); //txt size = 14px;

	}
    });
    g.DrawDependencies();

    //End context menu
    jQuery(".rowTask").contextMenu({
	menu: 'myMenu'
    },
    function(action, el, pos) {
	var taskid = jQuery(el).attr('id').replace(/^child_/g,"");
	if (action == 'edit')
	{
	    editTask(el, taskid ,"");
	} else if (action == 'delete')
{
	    delTask(taskid);
	} else if (action == 'DI')
{
	    location.href=DOL_URL_ROOT+"/synopsisdemandeinterv/card.php?action=create&leftmenu=ficheinter"
	} else if (action == 'FI')
{
	    location.href=DOL_URL_ROOT+"/fichinter/card.php?action=create&leftmenu=ficheinter"
	}

    });
    //Column sizing

    jQuery("#leftTable").columnSizing({
	viewResize : true,
	viewGhost : false,
	selectCells : "tr:eq(1)>*",
	tableWidthFixed: false,
	speed: false,
	cookies: false
    })
    .end();
// End Column sizing


} //End redraw resize bg
var currentModTask = "-1";
function editTask(obj,taskId,rowType)
{
    currentModTask=taskId;
    jQuery("#dialog").dialog('open');
}

function delTask(taskId)
{
    currentModTask=taskId;
    //addDialog
    jQuery("#deldyalog").dialog('open');
}

function resetDialog()
{
    jQuery("#Modname").val("");
    jQuery("#Moddatedeb").val("");
    jQuery("#Moddatefin").val("");
    jQuery("#Modparent").val("");
    jQuery("#Modtype").val("");
    jQuery("#Modgroup").val("");
    jQuery("#Moddepend").val("");
    jQuery("#ModshortDesc").val("");
    jQuery("#ModColor").val("");
    jQuery("#ModUrl").val("");
    jQuery("#pMile").val("");
    jQuery("#pOpen").val("");
    jQuery('#Moddepend').val("");
    jQuery('#ModressourceAdmin').val("");
    jQuery('#ModressourceActo').val("");
    jQuery('#ModressourceRead').val("");

    //reset ep and RH options list
    jQuery('#SelUserMod').find('option').each(function(){
	jQuery(this).remove();
    });
    jQuery('#SelUserMod').append(jQuery(optUsrStr));
}
function rgbToHex(rgb){
    var parts = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)jQuery/);
    if (parts)
	delete (parts[0]);
    else {
	parts=new Array();
	parts[1] = 0;
	parts[2] = 0;
	parts[3] = 0;
    }
    for (var i = 1; i <= 3; ++i) {
	parts[i] = parseInt(parts[i]).toString(16);
	if (parts[i].length == 1) parts[i] = '0' + parts[i];
    }
    var hexString = parts.join('');
    return(hexString);
}

var currentUser = 0;
var valueArr = new Array();
var currentType = "User";
var trancheHoraireSav = new Array();
var mod="";


function init2ndPanel(pMod)
{
    mod = pMod;
    jQuery('#SelUser'+mod).attr('disabled',false);
    jQuery('#SelUser'+mod).change(function(){
	var value = jQuery('#SelUser'+mod).find(':selected').val();
	if (value > 0)
	{
	    currentUser=value;
	    ChangeIt(false);
	} else {
	    currentUser = 0;
	    jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
	    jQuery('#AddToTable'+mod).css('display','none');

	}
    });

    jQuery('#SelUserBut'+mod).click(function(){
	var value = jQuery('#SelUser'+mod).find(':selected').val();
	if (value > 0)
	{
	    currentUser=value;
	    ChangeIt(false);
	} else {
	    currentUser = 0;
	    jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
	    jQuery('#AddToTable'+mod).css('display','none');

	}
    });
    jQuery('#'+mod+'depend').change(function(){
	//draw a dependancy form
	var longHtml = "<div class='dependChange'><table width=100%><tbody>"
	+ "<tr><td colspan=3 align=right><span id='addDep' class='addDep'><img src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif' width=16 height=16></span></tr>"
	+ "<tr><td>Pourcentage d'accomplissement</td>" +
	"<td><span class='sliderDependSpan'>100</span>%<input type='hidden' class='sliderDepend"+mod+"Input' value=100></input></td>" +
	"<td width=300><div class='sliderDepend'></div></td></tr>"
	+ "</tbody></table></div>";
	jQuery('.dependChange').replaceWith(longHtml);
	jQuery('.sliderDepend').slider({
	    animate: true,
	    max: 100,
	    step: 10,
	    range: 'min',
	    value:100,
	    change:function(ev,ui){
		var value = ui.value;
		jQuery('.sliderDepend'+mod+'Input').val(ui.value);
		jQuery('.sliderDependSpan').text(ui.value);
	    }
	});
	jQuery('.addDep').click(function(){
	    var id=jQuery('#'+mod+'depend').find(":selected").val();
	    if (id>0)
	    {

		var name=jQuery('#'+mod+'depend').find(":selected").text();
		var accomp = jQuery('.sliderDepend'+mod+'Input').val();
		var longHtml = "<tr class='ui-widget-content'>" +
		"           <td align='center' class='ui-widget-content'>"+id+"</td> \
                                        <td align='center' class='ui-widget-content'>"+name+"</td> \
                                        <td align='center' class='ui-widget-content'>"+accomp+"</td> \
                                        <td align='center' class='ui-widget-content'><span id='Iduser"+currentUser+"' class='delFromDepTable"+mod+"'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                    </tr>";

		jQuery('#Depresult'+mod).append(jQuery(longHtml));
		//remove task from list
		jQuery('#'+mod+'depend option').each(function(){
		    if( id == jQuery(this).val()){
			jQuery(this).remove();
		    }
		});
		//delete
		if (jQuery('#'+mod+'depend option').length == 1)
		{
		    jQuery('#'+mod+'depend').attr('disabled',true);
		} else {
		    jQuery('#'+mod+'depend').attr('disabled',false);
		}
		jQuery('.delFromDepTable'+mod).click(function(){
		    var id = jQuery(this).parent().parent().find('td:nth-child(1)').text();
		    var name = jQuery(this).parent().parent().find('td:nth-child(2)').text();
		    var accomp = jQuery(this).parent().parent().find('td:nth-child(3)').text();

		    jQuery('#'+mod+'depend').append(jQuery('<option value="'+id+'">'+name+'</option>'));
		    jQuery('#'+mod+'depend').attr('disabled',false);
		    jQuery(this).parent().parent().remove();

		});
	    }

	});
    });
    jQuery('#'+mod+'depend').attr('disabled',false);


    jQuery("#AddToTable"+mod).click(function(){
	saveTrancheHoraire();
	if (currentType == "User")
	{
	    if (currentUser > 0)
	    {
		getFormValue(currentUser);

		if ('x'+valueArr['userName'] != "x" && valueArr['occupation'] +"x" != "x")
		{

		    var longHtml = "<tr class='ui-widget-content tooltipTranche'>" +
		    "           <td align='center' class='ui-widget-content'>User</td> \
                                            <td align='center' class='ui-widget-content'>"+valueArr['userName']+"</td> \
                                            <td align='center' class='ui-widget-content'>"+valueArr['occupation']+"</td> \
                                            <td align='center' class='ui-widget-content'>"+valueArr['role']+"</td> \
                                            <td align='center' class='ui-widget-content'><span id='Iduser"+currentUser+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                        </tr>";
		    jQuery('#result'+mod).append(jQuery(longHtml));

		    //Remove from list
		    jQuery('#SelUser'+mod).find('option').each(function(){
			if (jQuery(this).val() == currentUser)
			{
			    jQuery(this).remove();
			}
		    });
		    if (jQuery('#SelUser'+mod).find('option').length == 1)
		    {
			currentUser = 0;
			jQuery('#SelUser'+mod).attr('disabled',true);
			jQuery("#AddToTable"+mod).css('display',"none");
			ChangeIt(true);
		    } else {
			jQuery('#SelUser'+mod).attr('disabled',false);
			jQuery("#AddToTable"+mod).css('display',"block");
			jQuery('#select'+currentUser).attr('disabled',false);
			jQuery('.sliderOccupDial').slider( 'enable' );
			currentUser = 0;
			ChangeIt(true);
		    }

		    jQuery(".delFromTable").click(function(){
			delFromTable(this,nextVal);
		    }); //close delete

		    jQuery(".tooltipTranche").tooltip({
			track: true,
			delay: 100,
			showURL: false,
			showBody: " - ",
			opacity: 0.95,
			extraClass: "promoteZZ",
			fade: 250,
			bodyHandler: function() {
			    //get Id
			    //
			    var obj = this;
			    return(initToolTipUser(obj));
			}
		    });

		} //close if value ok
	    } else { // close currentUser > 0
		jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
		jQuery('#AddToTable'+mod).css('display','none');

	    }
	} else if (currentType == "Group")
{
	    getFormValueGrp(currentGrp);

	    if ('x'+valueArr['grpName'] != "x" && valueArr['occupation'] +"x" != "x")
	    {
		var longHtml = "<tr class='ui-widget-content'>" +
		"            <td align='center' class='ui-widget-content'>Group</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['grpName']+"</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['occupation']+"</td> \
                                        <td align='center' class='ui-widget-content'>"+valueArr['role']+"</td> \
                                        <td align='center' class='ui-widget-content'><span id='IdGrp"+currentGrp+"' class='delFromTable'><img height=16 width=16 src='"+DOL_URL_ROOT+"/theme/common/treemenu/plus.gif'></span></td> \
                                        </tr>";
		jQuery('#result'+mod).append(jQuery(longHtml));

		//Remove from list
		var nextVal = 0;
		var i=0;
		jQuery('#tree'+mod).find('span').each(function(){
		    if (jQuery(this).attr('id') == currentGrp)
		    {
			jQuery(this).parent().css('cursor','no-drop');
			jQuery(this).parent().addClass('notSelectable');
			jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
			jQuery('#AddToTable'+mod).css('display','none');
		    }

		});

		jQuery(".delFromTable").click(function(){
		    delFromTable(this);
		});
	    }
	}
    });
    jQuery('#SubAccordion'+mod).accordion({
	animated: 'slide',
	active: 1,
	autoHeight: false,
	change:function(ev,ui){
	    if (currentType == "User")
	    {
		currentType ="Group";
		jQuery("#AddToTable"+mod).css('display',"block");
	    } else {
		currentType = "User";
		if(jQuery('#SelUser'+mod).find('option').length > 1)
		{
		    currentUser = 0;
		    jQuery('#SelUser'+mod).attr('disabled',false);
		    jQuery("#AddToTable"+mod).css('display',"block");
		} else {
		    currentUser = 0;
		    jQuery('#SelUser'+mod).attr('disabled',true);
		    jQuery("#AddToTable"+mod).css('display',"none");
		}
	    }
	    jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
	    jQuery('#AddToTable'+mod).css('display','none');

	}
    });

    jQuery(".treeview").treeview({
	animated: "slow"

    });
    jQuery(".treeview").filter(":has(>ul):not(:has(>a))").find(">span").click().add( jQuery("a", jQuery(".treeview")) ).hoverClass();
    jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
    jQuery('#AddToTable'+mod).css('display','none');

}
var currentGrp = "";

function updateTrancheHoraire()
{
    if (currentType == "User" && trancheHoraireSav)
    {
	if(trancheHoraireSav[currentModTask][currentType] && currentUser && trancheHoraireSav[currentModTask][currentType][currentUser] )
	{
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][1])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentUser][1][i] > 0)
		{
		    jQuery("#subFragment-1").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][1][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][6])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentUser][6][i] > 0)
		{
		    jQuery("#subFragment-2").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][6][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][7])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentUser][7][i] > 0)
		{
		    jQuery("#subFragment-3").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][7][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentUser][8])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentUser][8][i] > 0)
		{
		    jQuery("#subFragment-4").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentUser][8][i]);
		}
	    }
	}
    }else if (trancheHoraireSav){
	if(trancheHoraireSav[currentModTask][currentType] && currentGrp && trancheHoraireSav[currentModTask][currentType][currentGrp] )
	{
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][1])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentGrp][1][i] > 0)
		{
		    jQuery("#subFragment-1").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][1][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][6])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentGrp][6][i] > 0)
		{
		    jQuery("#subFragment-2").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][6][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][7])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentGrp][7][i] > 0)
		{
		    jQuery("#subFragment-3").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][7][i]);
		}
	    }
	    for (var i in trancheHoraireSav[currentModTask][currentType][currentGrp][8])
	    {
		if (trancheHoraireSav[currentModTask][currentType][currentGrp][8][i] > 0)
		{
		    jQuery("#subFragment-4").find('tbody').find('tr').find('#tr'+i).find('input').val(trancheHoraireSav[currentModTask][currentType][currentGrp][8][i]);
		}
	    }
	}

    }
}

function saveTrancheHoraire()
{
    if (currentType == "User")
    {
	if (!trancheHoraireSav[currentModTask])
	{
	    trancheHoraireSav[currentModTask] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType])
	{
	    trancheHoraireSav[currentModTask][currentType] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentUser])
	{
	    trancheHoraireSav[currentModTask][currentType][currentUser] = new Array();
	}
	//semaine
	if (!trancheHoraireSav[currentModTask][currentType][currentUser][1])
	{
	    trancheHoraireSav[currentModTask][currentType][currentUser][1] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentUser][6])
	{
	    trancheHoraireSav[currentModTask][currentType][currentUser][6] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentUser][7])
	{
	    trancheHoraireSav[currentModTask][currentType][currentUser][7] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentUser][8])
	{
	    trancheHoraireSav[currentModTask][currentType][currentUser][8] = new Array();
	}
	jQuery("#subFragment-1").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][1][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][1][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
	jQuery("#subFragment-2").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][6][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][6][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
	jQuery("#subFragment-3").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][7][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][7][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
	jQuery("#subFragment-4").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][8][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentUser][8][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});

    } else {
	if (!trancheHoraireSav[currentModTask])
	{
	    trancheHoraireSav[currentModTask] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType])
	{
	    trancheHoraireSav[currentModTask][currentType] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentGrp])
	{
	    trancheHoraireSav[currentModTask][currentType][currentGrp] = new Array();
	}
	//semaine
	if (!trancheHoraireSav[currentModTask][currentType][currentGrp][1])
	{
	    trancheHoraireSav[currentModTask][currentType][currentGrp][1] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentGrp][6])
	{
	    trancheHoraireSav[currentModTask][currentType][currentGrp][6] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentGrp][7])
	{
	    trancheHoraireSav[currentModTask][currentType][currentGrp][7] = new Array();
	}
	if (!trancheHoraireSav[currentModTask][currentType][currentGrp][8])
	{
	    trancheHoraireSav[currentModTask][currentType][currentGrp][8] = new Array();
	}

	jQuery("#subFragment-1").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    //alert(trancheHoraireSav[currentModTask][currentType][currentGrp][1][id]);
	    }
	});
	jQuery("#subFragment-2").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][6][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][6][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
	jQuery("#subFragment-3").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][7][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][7][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
	jQuery("#subFragment-4").find('tbody').find('tr').each(function(){
	    if (jQuery(this).find('td:nth-child(2)').text() == "-"){
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][8][id]=0;
	    } else {
		var id = "";
		jQuery(this).find('td:nth-child(2)').each(function(){
		    id = jQuery(this).attr('id').replace(/[a-zA-Z]*/,"");
		});
		trancheHoraireSav[currentModTask][currentType][currentGrp][8][id]= (jQuery(this).find('td:nth-child(2)').find('input').val()+"x"=="x"?0:jQuery(this).find('td:nth-child(2)').find('input').val()) ;
	    }
	});
    }
}

function delFromTable(obj,nextVal)
{
    var id = jQuery(obj).attr('id').replace(/[a-zA-Z]*/,'');
    var type = jQuery(obj).parent().parent().find("td:nth-child(1)").text();
    var userName = jQuery(obj).parent().parent().find("td:nth-child(2)").text();
    var occupation = jQuery(obj).parent().parent().find("td:nth-child(3)").text();
    var role = jQuery(obj).parent().parent().find("td:nth-child(4)").text();

    if (type =='Group')
    {
	valueArr['occupation']=occupation;
	valueArr['role']=role;
	valueArr['grpName']=userName;
	currentGrp=id;
	//Add in treeview
	jQuery("#tree"+mod).find('#'+currentGrp).parent().removeClass('notSelectable');
	jQuery("#tree"+mod).find('#'+currentGrp).parent().css('cursor','pointer');

	//Rem from table
	jQuery(obj).parent().parent().remove();
	setFormValueGrp(currentGrp);
	ChangeIt(true);
    } else if (type == 'User')
{
	valueArr['occupation']=occupation;
	valueArr['role']=role;
	valueArr['userName']=userName;
	currentUser=id;
	//Add in seletUser
	jQuery('#SelUser'+mod).append('<option SELECTED value="'+id+'">'+userName+'</option>');
	if (jQuery('#SelUser'+mod).find('option').length == 1)
	{
	    currentUser = 0;
	    jQuery('#SelUser'+mod).attr('disabled',true);
	    jQuery("#AddToTable"+mod).css('display',"none");
	} else {
	    jQuery('#SelUser'+mod).attr('disabled',false);
	    jQuery('#select'+currentUser).attr('disabled',false);
	    jQuery('.sliderOccupDial').slider( 'enable' );
	    currentUser = nextVal;
	}
	//Rem from table
	jQuery(obj).parent().parent().remove();
	setFormValue(currentUser);
	ChangeIt(true);
    }
}
function secToTime(pSec)
{
    var sec = parseInt(pSec);
    var hour = Math.floor((sec / 3600));
    var min = Math.abs(((hour * 3600) - sec) / 60);
    var minStr = new String(min);
    var hourStr = new String(hour);
    if (minStr.length == 1) {
	minStr = "0"+minStr;
    }
    if (hourStr.length == 1) {
	hourStr = "0"+hourStr;
    }
    var ret = hourStr + ":"+minStr;
    return ret;
}

function SelectGrp(grpId)
{
    currentGrp = grpId;
    if (jQuery("#tree"+mod).find('li').find('#'+grpId).parent().hasClass('notSelectable'))
    {
	jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
	jQuery('#AddToTable'+mod).css('display','none');

    } else {
	//Get the Form
	var tableauSemaine = "<table><thead><tr><th class='ui-state-default ui-th-column'>Tranche horaire</th><th class='ui-state-default ui-th-column' style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column'>Facteur</th></tr></thead><tbody>";
	var iter = 0;
	var count = 0;
	for (var i in trancheHoraire[1])
	{
	    count++;
	}
	var iter1=1;
	if (count == 0)
	{
	    tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
	} else {

	    for( var i in trancheHoraire[1])
	    {
		var debut = trancheHoraire[1][i]['debut'];
		var fin = trancheHoraire[1][i]['fin'];
		var facteur = trancheHoraire[1][i]['facteur'];
		if (iter == 0 && debut > 0)
		{
		    tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		    iter ++;
		}
		tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
		if (count == iter1 && fin < (23*3600 + 59 * 60))
		{
		    tableauSemaine += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		}
		iter1++;
	    }
	}
	tableauSemaine += "</tbody></table>";

	var tableauSamedi = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
	iter = 0;
	count = 0;
	for (var i in trancheHoraire[6])
	{
	    count++;
	}
	iter1=1;
	if (count == 0)
	{
	    tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
	} else {

	    for( var i in trancheHoraire[6])
	    {
		var debut = trancheHoraire[6][i]['debut'];
		var fin = trancheHoraire[6][i]['fin'];
		var facteur = trancheHoraire[6][i]['facteur'];
		if (iter == 0 && debut > 0)
		{
		    tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		    iter ++;
		}
		tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
		if (count == iter1 && fin < (23*3600 + 59 * 60))
		{
		    tableauSamedi += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		}
		iter1++;
	    }
	}
	tableauSamedi += "</tbody></table>";

	var tableauDimanche = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
	iter = 0;
	count = 0;
	for (var i in trancheHoraire[7])
	{
	    count++;
	}
	iter1=1;
	if (count == 0)
	{
	    tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
	} else {

	    for( var i in trancheHoraire[7])
	    {
		var debut = trancheHoraire[7][i]['debut'];
		var fin = trancheHoraire[7][i]['fin'];
		var facteur = trancheHoraire[7][i]['facteur'];
		if (iter == 0 && debut > 0)
		{
		    tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		    iter ++;
		}
		tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
		if (count == iter1 && fin < (23*3600 + 59 * 60))
		{
		    tableauDimanche += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		}
		iter1++;
	    }
	}
	tableauDimanche += "</tbody></table>";

	var tableauFerie = "<table><thead><tr><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
	iter = 0;
	count = 0;
	for (var i in trancheHoraire[8])
	{
	    count++;
	}
	iter1=1;
	if (count == 0)
	{
	    tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
	} else {

	    for( var i in trancheHoraire[8])
	    {
		var debut = trancheHoraire[8][i]['debut'];
		var fin = trancheHoraire[8][i]['fin'];
		var facteur = trancheHoraire[8][i]['facteur'];
		if (iter == 0 && debut > 0)
		{
		    tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		    iter ++;
		}
		tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
		if (count == iter1 && fin < (23*3600 + 59 * 60))
		{
		    tableauFerie += "<tr><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td >"+facteurDefault+"</td></tr>";
		}
		iter1++;
	    }
	}
	tableauFerie += "</tbody></table>";


	longHtml = "<div id='div"+currentGrp+"'>"
	+ "    <table  class='horaire' width='100%' style='margin-left: 10px; border-collapse: collapse;'>"
	+ "        <tr>"
	+ "            <td colspan=3>"
	+ "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id='divTitle'>group</div> "
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr>"
	+ "            <td colspan=3>&nbsp;"
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr style='margin: 10px; font-size: 125%;'>"
	+ "            <td>Occupation</td>"
	+ "            <td width=20>"
	+ "                 <span id='span"+currentGrp+"'>100</span>% "
	+ "            </td>"
	+ "            <td>"
	+ "                    <input type='hidden' value='100' id='input"+currentGrp+"'>"
	+ "                <div class='sliderOccupDial' id='slide"+currentGrp+"'></div>"
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr style='margin: 10px; font-size: 125%;'>"
	+ "            <td>R&ocirc;le</td>"
	+ "            <td colspan=2 align=right>"
	+ "                <SELECT style='width: 304px;' id='select"+currentGrp+"' size=1>"
	+ "                    <OPTION value='actor'>Acteur</OPTION>"
	+ "                    <OPTION value='admin'>Administrateur</OPTION>"
	+ "                    <OPTION value='read'>Reviewer</OPTION>"
	+ "                </SELECT>"
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr>"
	+ "            <td colspan=3>&nbsp;"
	+ "            </td>"
	+ "        </tr>"
	//horaire
	+ "        <tr>"
	+ "            <td colspan=3>"
	+ "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='font-width: 120%; width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id=''>Tranche horaire</div> "
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr>"
	+ "            <td colspan=3>&nbsp;"
	+ "            </td>"
	+ "        </tr>"
	+ "        <tr>"
	+ "            <td colspan=3>"
	+ "                 <div id='TRTabs' style='width: 100%;'>"
	+ "                     <ul>"
	+ "                         <li><a href='#subFragment-1'><span>Semaine</span></a></li>"
	+ "                         <li><a href='#subFragment-2'><span>Samedi</span></a></li>"
	+ "                         <li><a href='#subFragment-3'><span>Dimanche</span></a></li>"
	+ "                         <li><a href='#subFragment-4'><span>F&eacute;ri&eacute;</span></a></li>"
	+ "                     </ul>"
	+ "                     <div id='subFragment-1'>"
	//par tranche horaire
	+ "                         <p>"+tableauSemaine+"</p>"
	+ "                     </div>"
	+ "                     <div id='subFragment-2'>"
	+ "                         <p>"+tableauSamedi+"</p>"
	+ "                     </div>"
	+ "                     <div id='subFragment-3'>"
	+ "                         <p>"+tableauDimanche+"</p>"
	+ "                     </div>"
	+ "                     <div id='subFragment-4'>"
	+ "                         <p>"+tableauFerie+"</p>"
	+ "                     </div>"
	+ "                 </div> "
	+ "            </td>"
	+ "        </tr>"
	+ "    </table>"
	+ "</div>";
	jQueryform = jQuery(longHtml);

	//draw form
	jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'>"+jQueryform.html()+"</div>");
	jQuery('#AddToTable'+mod).css('display','block');
	jQuery('#TRTabs').tabs({
	    spinner: "Chargement",
	    cache: true, 
	    fx: {
		opacity: 'toggle'
	    }
	});
	//redraw Slider
	jQuery('.sliderOccupDial').slider({
	    animate: true,
	    max: 100,
	    step: 5,
	    range: 'min',
	    value:100,
	    change:function(ev,ui){
		var value = ui.value;
		jQuery('#input'+currentGrp).val(ui.value);
		jQuery('#span'+currentGrp).text(ui.value);
	    }
	});

	//fill the form
	var occupation = 0;
	var role = "";
	if (currentGrp != 0)
	{
	    setFormValueGrp(currentGrp);
	    updateTrancheHoraire();
	}
    }
}

function ChangeIt(disabled)
{

    var tableauSemaine = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
    var iter = 0;
    var count = 0;
    for (var i in trancheHoraire[1])
    {
	count++;
    }
    var iter1=1;
    if (count == 0)
    {
	tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td id='tr0' class='ui-widget-content'>-</td></tr>";
    } else {
	for( var i in trancheHoraire[1])
	{
	    var debut = trancheHoraire[1][i]['debut'];
	    var fin = trancheHoraire[1][i]['fin'];
	    var facteur = trancheHoraire[1][i]['facteur'];
	    if (iter == 0 && debut > 0)
	    {
		tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		iter ++;
	    }
	    tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
	    if (count == iter1 && fin < (23*3600 + 59 * 60))
	    {
		tableauSemaine += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
	    }
	    iter1++;
	}
    }
    tableauSemaine += "</tbody></table>";

    var tableauSamedi = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
    iter = 0;
    count = 0;
    for (var i in trancheHoraire[6])
    {
	count++;
    }
    iter1=1;

    if (count == 0)
    {
	tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
    } else {

	for( var i in trancheHoraire[6])
	{
	    var debut = trancheHoraire[6][i]['debut'];
	    var fin = trancheHoraire[6][i]['fin'];
	    var facteur = trancheHoraire[6][i]['facteur'];
	    if (iter == 0 && debut > 0)
	    {
		tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		iter ++;
	    }
	    tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
	    if (count == iter1 && fin < (23*3600 + 59 * 60))
	    {
		tableauSamedi += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
	    }
	    iter1++;
	}
    }
    tableauSamedi += "</tbody></table>";

    var tableauDimanche = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
    iter = 0;
    count = 0;
    for (var i in trancheHoraire[7])
    {
	count++;
    }
    iter1=1;
    if (count == 0)
    {
	tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
    } else {
	for( var i in trancheHoraire[7])
	{
	    var debut = trancheHoraire[7][i]['debut'];
	    var fin = trancheHoraire[7][i]['fin'];
	    var facteur = trancheHoraire[7][i]['facteur'];
	    if (iter == 0 && debut > 0)
	    {
		tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'> - </td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		iter ++;
	    }
	    tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
	    if (count == iter1 && fin < (23*3600 + 59 * 60))
	    {
		tableauDimanche += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'> - </td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
	    }
	    iter1++;
	}
    }
    tableauDimanche += "</tbody></table>";

    var tableauFerie = "<table><thead><tr class='ui-widget-content'><th class='ui-state-default ui-th-column' >Tranche horaire</th><th class='ui-state-default ui-th-column'  style='min-width:162px'>Nb heure</th><th class='ui-state-default ui-th-column' >Facteur</th></tr></thead><tbody>";
    iter = 0;
    count = 0;
    for (var i in trancheHoraire[8])
    {
	count++;
    }
    iter1=1;
    if (count == 0)
    {
	tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr0'>-</td></tr>";
    } else {

	for( var i in trancheHoraire[8])
	{
	    var debut = trancheHoraire[8][i]['debut'];
	    var fin = trancheHoraire[8][i]['fin'];
	    var facteur = trancheHoraire[8][i]['facteur'];
	    if (iter == 0 && debut > 0)
	    {
		tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(0)+" &agrave; "+secToTime(debut)+" </td><td class='ui-widget-content' id='tr0'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
		iter ++;
	    }
	    tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+" </td><td class='ui-widget-content' id='tr"+i+"'><input></input></td><td class='ui-widget-content'>"+facteur+"</td></tr>";
	    if (count == iter1 && fin < (23*3600 + 59 * 60))
	    {
		tableauFerie += "<tr class='ui-widget-content'><td class='ui-widget-content'>De "+secToTime(fin)+" &agrave; 23:59 </td><td class='ui-widget-content' id='tr999'>-</td><td class='ui-widget-content'>"+facteurDefault+"</td></tr>";
	    }
	    iter1++;
	}
    }
    tableauFerie += "</tbody></table>";

    //Get the Form
    longHtml = "<div id='div"+currentUser+"'>"
    + "    <table class='horaire' width='350' style='margin-left: 10px; border-collapse: collapse;'>"
    + "        <tr>"
    + "            <td colspan=3>"
    + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id='divTitle'></div> "
    + "            </td>"
    + "        </tr>"
    + "        <tr>"
    + "            <td colspan=3>&nbsp;"
    + "            </td>"
    + "        </tr>"

    + "        <tr  style='margin: 10px; font-size: 125%;'>"
    + "            <td>Occupation</td>"
    + "            <td width=20>"
    + "                 <span id='span"+currentUser+"'>100</span>% "
    + "            </td>"
    + "            <td>"
    + "                    <input type='hidden' value='100' id='input"+currentUser+"'>"
    + "                <div class='sliderOccupDial' id='slide"+currentUser+"'></div>"
    + "            </td>"
    + "        </tr>"
    + "        <tr style='margin: 10px; font-size: 125%;'>"
    + "            <td>R&ocirc;le</td>"
    + "            <td colspan=2 align=right>"
    + "                <SELECT  style='width: 304px;' id='select"+currentUser+"' size=1>"
    + "                    <OPTION value='actor'>Acteur</OPTION>"
    + "                    <OPTION value='admin'>Administrateur</OPTION>"
    + "                    <OPTION value='read'>Reviewer</OPTION>"
    + "                </SELECT>"
    + "            </td>"
    + "        </tr>"
    //horaire
    + "        <tr>"
    + "            <td colspan=3>&nbsp;"
    + "            </td>"
    + "        </tr>"
    + "        <tr>"
    + "            <td colspan=3>"
    + "                 <div class='ui-widget-header ui-widget  ui-helper-reset ui-state-default ui-corner-all ' style='width: 100%; margin: 0; margin-right: 0; padding-left: 10px;padding-top: 10px;padding-bottom: 10px;' id=''>Tranche horaire</div> "
    + "            </td>"
    + "        </tr>"
    + "        <tr>"
    + "            <td colspan=3>&nbsp;"
    + "            </td>"
    + "        </tr>"

    + "        <tr>"
    + "            <td colspan=3>"
    + "                 <div id='TRTabs'  style='width: 100%;'>"
    + "                     <ul>"
    + "                         <li><a href='#subFragment-1'><span>Semaine</span></a></li>"
    + "                         <li><a href='#subFragment-2'><span>Samedi</span></a></li>"
    + "                         <li><a href='#subFragment-3'><span>Dimanche</span></a></li>"
    + "                         <li><a href='#subFragment-4'><span>F&eacute;ri&eacute;</span></a></li>"
    + "                     </ul>"
    + "                     <div id='subFragment-1'>"
    //par tranche horaire
    + "                         <p>"+tableauSemaine+"</p>"
    + "                     </div>"
    + "                     <div id='subFragment-2'>"
    + "                         <p>"+tableauSamedi+"</p>"
    + "                     </div>"
    + "                     <div id='subFragment-3'>"
    + "                         <p>"+tableauDimanche+"</p>"
    + "                     </div>"
    + "                     <div id='subFragment-4'>"
    + "                         <p>"+tableauFerie+"</p>"
    + "                     </div>"
    + "                 </div> "
    + "            </td>"
    + "        </tr>"

    + "    </table>"
    + "</div>";
    jQueryform = jQuery(longHtml);
    if (disabled)
    {
	jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'></div>");
	jQuery('#AddToTable'+mod).css('display','none');
    } else {
	//draw form
	jQuery('#toChange'+mod).replaceWith("<div id='toChange"+mod+"'>"+jQueryform.html()+"</div>");
	jQuery('#AddToTable'+mod).css('display','block');


	//redraw Slider
	jQuery('.sliderOccupDial').slider({
	    animate: true,
	    max: 100,
	    step: 5,
	    range: 'min',
	    value:100,
	    change:function(ev,ui){
		var value = ui.value;
		jQuery('#input'+currentUser).val(ui.value);
		jQuery('#span'+currentUser).text(ui.value);
	    }
	});
	jQuery('#TRTabs').tabs({
	    spinner: "Chargement",
	    cache: true, 
	    fx: {
		opacity: 'toggle'
	    }
	});

	//fill the form
	var occupation = 0;
	var role = "";
	if (currentUser != 0)
	{
	    setFormValue(currentUser);
	    updateTrancheHoraire();
	}
    }
}
function setFormValue(userId,disabled)
{
    occupation = (valueArr['occupation'] > 0?valueArr['occupation']:100);
    role = valueArr['role'];
    jQuery('#select'+userId).find('option').each(function(){
	if (jQuery(this).val() == role)
	{
	    jQuery(this).attr("selected","true");
	}
    });
    jQuery('.sliderOccupDial').slider('value',occupation);
    jQuery('#input'+userId).val(occupation);
    jQuery('#span'+userId).text(occupation);

    var userName = jQuery("#SelUser"+mod).find('option:selected').text();
    jQuery('#divTitle').text(userName);
    //jQuery('#divTitle').animate('hightlight');
    var options = {};
    jQuery("#divTitle").effect("highlight",options,500);
}

function setFormValueGrp(grpId,disabled)
{
    occupation = (valueArr['occupation'] > 0?valueArr['occupation']:100);
    role = valueArr['role'];

    jQuery('.sliderOccupDial').slider('value',occupation);
    jQuery('#input'+grpId).val(occupation);
    jQuery('#span'+grpId).text(occupation);

    var grpName = "";
    jQuery("#tree"+mod).find('span').each(function(){
	if (jQuery(this).attr('id')==grpId)
	    grpName = jQuery(this).text();
    });
    jQuery('#divTitle').text(grpName);
    //jQuery('#divTitle').animate('hightlight');
    var options = {};
    jQuery("#divTitle").effect("highlight",options,500);
}


function getFormValue(userId)
{
    //Save old value
    valueArr=new Array();
    var getOccup = jQuery('#input'+userId).val();
    if (! getOccup>0) {
	getOccup = 0;
    }
    valueArr['occupation']=getOccup;
    valueArr['role']=jQuery('#select'+userId).find(':selected').val();
    valueArr['userName']=jQuery('#SelUser'+mod).find(':selected').text();
}
function getFormValueGrp(grpId)
{
    //Save old value
    valueArr=new Array();
    var getOccup = jQuery('#input'+grpId).val();
    if (! getOccup>0) {
	getOccup = 0;
    }
    valueArr['occupation']=getOccup;
    valueArr['role']=jQuery('#select'+grpId).find(':selected').val();
    valueArr['grpName']=jQuery('#divTitle').text();
}
function initToolTipUser(obj)
{
    var jQueryobj = jQuery(obj);
    var id = jQueryobj.find('span').attr('id').replace(/[a-zA-Z]*/,'');
    // Type
    var type = jQueryobj.find('td:nth-child(1)').text();
    var longHtml = "<div style='background-color:#FFFFFF;'><table><thead><tr><th class='ui-state-default ui-th-column' colspan=3>Semaine</th></thead><tbody></tr>";
    var ArrStr = new Array();
    ArrStr[1]="Semaine";
    ArrStr[6]="Samedi";
    ArrStr[7]="Dimanche";
    ArrStr[8]="F&eacute;ri&eacute;";

    var ArrJour = new Array();
    ArrJour[0]=1;
    ArrJour[1]=6;
    ArrJour[2]=7;
    ArrJour[3]=8;


    var ArrType = new Array();
    ArrType[0]='User';
    ArrType[1]='Group';
    //        if (type=='User')
    for (var h in ArrType)
    {
	var type = ArrType[h];
	for (var j in ArrJour)
	{
	    for(var iii in trancheHoraireSav[currentModTask][type][id])
	    {
		if (iii == ArrJour[j]) // semaine
		{
		    for (var iiii in trancheHoraireSav[currentModTask][type][id][iii]) // Chaque tranche
		    {
			var tranche = iiii;
			if (tranche > 0 && tranche < 999)
			{
			    var qte = trancheHoraireSav[currentModTask][type][id][iii][iiii];
			    var debut = trancheHoraire[iii][tranche]["debut"];
			    var fin = trancheHoraire[iii][tranche]["fin"];
			    var facteur = trancheHoraire[iii][tranche]["facteur"];
			    longHtml+= "<tr><td class='ui-widget-content'>De "+secToTime(debut)+" &agrave; "+secToTime(fin)+"</td><td  class='ui-widget-content'>"+qte+"</td><td class='ui-widget-content'>"+facteur+"%</td></tr>";
			}
		    }
		}
	    }
	    if (ArrJour[j] != 1 )
	    {
		longHtml += "</tbody><thead><tr><th class='ui-state-default ui-th-column' colspan=3>"+ArrStr[ArrJour[j]]+"</th></thead><tbody></tr>";
	    }
	    if (ArrJour[j]==8){
		longHtml+= "</tbody></table></div>";
	    }

	}
	return jQuery(longHtml);
    }
}

