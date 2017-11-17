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
    			})
		}
		var urlRequest = DOL_URL_ROOT + "/bimpproductbrowser/addLink.php";
		$.ajax(
		{
			type: "POST",
			url: urlRequest,
			data: {id_oject : $('#id_oject').val() , checked : checkboxs, action: 'filldb'},
			cache: false,
			success: function()
			{
				showalert("<strong>Succès !</strong> Formulaire soumis ", 'no_error');
			},	
			error: function() {
				showalert("<strong>Erreur !</strong> URL inconnu :"+urlRequest, 'error');
			}
		})
	return false;
	});
});

function showalert(message, alerttype)
{
	var time;
	var backgroundColor;
	if (alerttype == 'no_error' )
	{
		time = 3000;
		backgroundColor='#c4ff7a ';
	} else
	{
		time = 3600000;
		backgroundColor='#ff887a ';
	}
	$('#placeforalert').hide().fadeIn(500).append('<div id="alertdiv" style="background-color: ' + backgroundColor + '">' + message + '</span></div>')
	setTimeout(function()
	{
		$("#alertdiv").remove();
	}, time);
}