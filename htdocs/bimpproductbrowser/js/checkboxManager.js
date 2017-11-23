/* global DOL_URL_ROOT */

$(document).ready(function()
	{
		var cb;
	$("#submitTree").click(function()
		{
		var allInputs = document.getElementsByTagName("input");
		var checkboxs = [];
		for (var i = 0, max = allInputs.length; i < max; i++)
		{
    		if (allInputs[i].type === 'checkbox') 
    			checkboxs.push({
    				id: allInputs[i].id,
    				val: allInputs[i].checked
    			});
		}
		var urlRequest = DOL_URL_ROOT+"/bimpproductbrowser/addLink.php";
		$.ajax(
		{
			type: "POST",
			url: urlRequest,
			data: {
                            id_oject : $('#id_oject').val(),
                            checked : checkboxs,
                            action: 'filldb'
                        },
			cache: false,
			success: function(objOut)
			{
                            obj = JSON.parse(objOut);
                            (obj.insertion > 1) ? multipleInsertion = "s" : multipleInsertion = '';
                            (obj.deletion > 1) ? multipleDeletion = "s" : multipleDeletion = '';
                            if (obj.insertion !== 0 && obj.deletion !== 0) {
                                showalert("<strong>Succès !</strong> Vous avez inséré " + obj.insertion 
                                        + " restriction"  + multipleInsertion 
                                        + " et supprimé " + obj.deletion 
                                        + " restriction" + multipleDeletion + ".<br>Raffraichissez la page pour observer le changement.", 'no_error');
                            } else if (obj.insertion !== 0) {
                                showalert("<strong>Succès !</strong> Vous avez inséré " + obj.insertion 
                                        + " restriction"  + multipleInsertion + ".<br>Raffraichissez la page pour observer le changement.", 'no_error');
                            } else if (obj.deletion !== 0) {
                                showalert("<strong>Succès !</strong> Vous avez supprimé " + obj.deletion 
                                        + " restriction"  + multipleDeletion + ".<br>Raffraichissez la page pour observer le changement.", 'no_error');
                            } else {
                                showalert("<strong>Attention !</strong> Aucune modification n'a été prise en compte, vérifiez de bien avoir coché et/ou décoché au moins une case", 'error');
                            }
			},	
			error: function() {
				showalert("<strong>Erreur !</strong> URL inconnu :"+urlRequest, 'error');
			}
		});
	return false;
	});
});

function showalert(message, alerttype)
{
	var time;
	var backgroundColor;
	if (alerttype === 'no_error' )
	{
		time = 30000;
		backgroundColor='#c4ff7a ';
	} else
	{
		time = 30000;
		backgroundColor='#ff887a ';
	}
	$('#placeforalert').hide().fadeIn(500).append('<div id="alertdiv" style="background-color: ' + backgroundColor + '">' + message + '</span></div>');
	setTimeout(function()
	{
		$("#alertdiv").remove();
	}, time);
}