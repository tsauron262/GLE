/* global DOL_URL_ROOT */

$(document).ready(function ()
{
    var cb;
    $("#submitTree").click(function ()
    {
        var inputChecked = $("input:checkbox:checked").length;
        if (inputChecked > 1) {
            showalert("<strong>Attention !</strong> Sélectionnez une seule catégorie. Aucune modification n'a été prise en compte.", 'error');
        } else if (inputChecked == 0) {
            showalert("<strong>Attention !</strong> Sélectionnez au moins une catégorie. Aucune modification n'a été prise en compte.", 'error');
        } else {
            
        }
//        $.ajax(
//                {
//                    type: "POST",
//                    url: urlRequest,
//                    data: {
//                        id_oject: $('#id_oject').val(),
//                        checked: checkboxs,
//                        action: 'filldb'
//                    },
//                    cache: false,
//                    success: function (objOut)
//                    {
    });
});

function showalert(message, alerttype)
{
     $('#placeforalert').hide().fadeIn(1000).append('<div id="alertdiv" style="background-color: #ff887a ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function ()
    {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function ()
        {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}