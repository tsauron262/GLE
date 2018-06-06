var id_order = 0;
var id_prods = new Array();
var products = new Array();

$(document).ready(function () {

    $('div[name=tariff]').each(function () {
        id_prods.push($(this).attr('id_product'));
        products.push({id: $(this).attr('id_product'), qty: $(this).attr('qty')});
    });
    id_order = getUrlParameter('id_order');

    if (id_prods[0] == -1) {
        $('section#content').append('<div class="alert alert-danger"><strong style="font-size: 16px; text-aligne: center">' +
                '<img src="img/admin/error2.png" style="width: 16px; height: 16px; margin-bottom: 4px"> ' +
                'Les tickets ne seront disponibles qu\'une fois que le paiement sera effectué.</strong>' +
                '</div>');
    } else if (id_prods[0] == -2) {
        alert('Tentative de piratage');
    }
});

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};
