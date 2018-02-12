/**
 * Globals variable
 */

/* global DOL_URL_ROOT */


/**
 * Ajax call
 */

/**
 * Insert modifications in database
 * 
 * @param {Object} products
 * @returns {undefined}
 */
function modifyOrder(products) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            action: 'modifyOrder',
            entrepotId: $('#entrepot').val(),
            products: products,
            orderId: getUrlParameter('id')
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (out) {
            setMessage('alertEnregistrer', products.length + ' Groupes de produits on été rajouté avec succès.', 'mesgs');
        }
    });
}



/**
 * Ready
 */

$(document).ready(function () {
    $('#entrepot').select2();

    initEvents();

});

/**
 * Functions
 */

function initEvents() {
    $('.modify').click(modifyQuantity);

    $('input[name=checkAll]').change(function () {
        var isChecked = $(this).prop('checked');
        $('table#productTable [name=stocker]').prop('checked', isChecked);
    });

    $('#enregistrer').click(function () {
        $('p[name=confTransfert]').text('Etes-vous sur de vouloir mettre en stock ces produits ?');
        $('div [name=confirmEnregistrer]').show();
    });

    $('input#okEnregistrer').click(function () {
        saveProducts();
        $('div [name=confirmEnregistrer]').hide();
    });

    $('input#noEnregistrer').click(function () {
        $('div [name=confirmEnregistrer]').hide();
    });
}

function modifyQuantity() {
    var idLine = $(this).parent().parent().attr('id');
    var selectoTr = 'table#productTable tr#' + idLine;
    var newQty = parseInt($(selectoTr + ' td input[name=modify]').val());
    $(selectoTr + ' td[name=qty]').text(newQty);

}

function saveProducts() {
    var products = [];

    $('table#productTable tr').each(function () {
        if ($(this).find('td input[name=stocker]').prop('checked')) { // is the kine checked ?
            var newProd = {
                id_prod: parseInt($(this).find('td[name=productId]').text()),
                qty: parseInt($(this).find('td[name=qty]').text())
            };
            products.push(newProd);
        }
    });
    console.log(products);
    modifyOrder(products);
}



/**
 * 
 * @param {String} idElement id of the element to append the message in
 * @param {String} message the message you want to displ  ay
 * @param {String} type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px;">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}

/* Get the parameter sParam */
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