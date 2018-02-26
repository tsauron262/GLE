/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var cntProduct = 0;


/**
 * Ajax call
 */

function getAllProducts() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            id_entrepot: getUrlParameter('id_entrepot'),
            action: 'getAllProducts'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 8546.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertEnregistrer');
            } else {
                out.products.forEach(function (prod) {
                    addLineProduct(prod.id, prod.ref, prod.label, prod.qty);
                });
                out.equipments.forEach(function (eq) {
                    addLineEquipment(eq.id, eq.id_product, eq.ref, eq.serial, eq.label);
                });
            }
        }
    });
}


/**
 * Ready
 */

$(document).ready(function () {
    getAllProducts();
});



/**
 * Functions
 */

/* Add a line in the table of equipments */
function addLineEquipment(equipment_id, product_id, ref, serial, label) {

    cntProduct++;

    var line = '<tr id="' + equipment_id + '">';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + product_id + '</td>';    // id
    line += '<td>' + ref + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td></td>';   // Quantité Totale
    line += '<td></td>';   // Quantité Manquante
    line += '<td></td>';   // Quantité Indiqué
    line += '<td></td>';   // Modifier
    $(line).appendTo('#productTable tbody');
}

/* Add a line in the table of product */
function addLineProduct(product_id, ref, label, qty_totale) {

    cntProduct++;
//    qtyTotale = parseInt(qtyTotale);
//    qtyGiven = parseInt(qtyGiven);

//    var qtyMissing = qtyTotale - qtyGiven;
    var line = '<tr id=' + product_id + '>';
    line += '<td name="cnt">' + cntProduct + '</td>';    // cnt ligne
    line += '<td>' + product_id + '</td>';
    line += '<td>' + ref + '</td>';
    line += '<td></td>';
    line += '<td>' + label + '</td>';
    line += '<td name="qtyTotale"    >' + qty_totale + '</td>';
    line += '<td name="qtyMissing"    ></td>';
    line += '<td name="qtyGiven"></td>';
    $(line).appendTo('#productTable tbody');
}


/**
 * 
 * @param String idElement id of the element to append the message in
 * @param String message the message you want to displ  ay
 * @param String type 'mesgs' => normal message (green) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var backgroundColor;
    (type === 'mesgs') ? backgroundColor = '#25891c ' : backgroundColor = '#ff887a ';
    if (type === "error")
        document.querySelector("#bipError").play();

    $('#' + idElement).append('<div id="alertdiv" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px; color:black">' + message + '</div>');
    setTimeout(function () {
        $("#alertdiv").fadeOut(1000);
        setTimeout(function () {
            $("#alertdiv").remove();
        }, 1000);
    }, 10000);
}

/**
 * Print and array of string in
 * @param {Array} errors
 * @param {String} idAlertPlaceHolder the id of the element where you want to
 *  set the message in.
 * @dependent setMessage()
 */
function printErrors(errors, idAlertPlaceHolder) {
    for (var i = 0; i < errors.length && i < 100; i++) {
        setMessage(idAlertPlaceHolder, errors[i], 'error');
    }
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
