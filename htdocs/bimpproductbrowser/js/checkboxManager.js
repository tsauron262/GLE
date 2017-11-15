$(document).ready(function()
	{
	$("#submitTree").click(function()
		{
		var checkedValues = $('input:checkbox:checked').map(function()
			{
				return this.id;
			}).get();
		$.ajax(
		{
			type: "POST",
			url: DOL_URL_ROOT + "/bimpproductbrowser/browse.php?id="+findGetParameter('id'),		// TODO id à changer
			data: {ids : checkedValues, action: 'filldb'},
			cache: false,
			success: function(result)
			{
				showalert("<strong>Succès !</strong> Restriction ajoutée ", 'no_error');
			},	
			error: function() {
				showalert("<strong>Erreur !</strong> Une erreur inconnu est survenue, merci de le signaler à l\'adresse suivante : r.PELEGRIN@bimp.fr", 'error');
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