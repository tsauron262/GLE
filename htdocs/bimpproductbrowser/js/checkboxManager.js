$(document).ready(function()
	{
		var cb;
	$("#submitTree").click(function()
		{
		// var checkedValues = $('input:checkbox:checked').map(function()
		// 	{
		// 		return this.id;
		// 	}).get();
		// var uncheckedValues = $('input:checkbox:not(:checked)').map(function()
		// 	{
		// 		return this.id;
		// 	}).get();
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
		console.log(checkboxs);
		var urlRequest = DOL_URL_ROOT + "/bimpproductbrowser/addLink.php";//?id="+findGetParameter('id');
		$.ajax(
		{
			type: "POST",
			url: urlRequest,
			data: {checked : checkboxs, action: 'filldb'},
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

function findGetParameter(parameterName)
{
    var result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          tmp = item.split("=");
          if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}