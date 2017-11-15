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
			url: DOL_URL_ROOT + "/bimpproductbrowser/browse.php?id=1",
			data: {ids : checkedValues, action: 'filldb'},
			cache: false,
			success: function(result)
			{
				showalert("<strong>Succès !</strong> Restriction ajoutée ", 'alert-success');
			},	
			error: function() {
				showalert("<strong>Erreur !</strong> Une erreur inconnu est survenue, merci de le signaler à l\'adresse suivante : r.PELEGRIN@bimp.fr", 'alert-error');
			}
		})
	return false;
	});
});

function showalert(message, alerttype)
{
	var time;
	((alerttype == 'alert-success' ) ? time = 3000 : time = 3600000);
	$('#placeforalert').hide().fadeIn(500).append('<div id="alertdiv" class="alert ' +  alerttype + '"><a class="close" data-dismiss="alert">×</a><span>' + message + '</span></div>')
	setTimeout(function()
	{
		$("#alertdiv").remove();
	}, time);
}