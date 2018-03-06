
/**
 * Globals
 */
/* global DOL_URL_ROOT */

var id_warehouse;
var cnt_product = 0;
/**
 * Ajax call
 */
function retrieveSentLines() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_transfert: getUrlParameter('id'),
            action: 'retrieveSentLines'
        },
        error: function () {
            setMessage('alertTop', 'Erreur interne 1854.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertTop');
            } else {
                out.prods.forEach(function (prod) {
                    if (prod.fk_equipment === '') { // product
                        addLineProduct(prod);
                    } else { // equipment
                        addLineEquipment(prod);
                    }
                });
            }
        }
    });
}





$(document).ready(function () {

    retrieveSentLines()
    initEvents();
    initIE('input[name=refScan]', 'validateProduct', 'input#qty');

});
function initEvents() {

}

/**
 * Triggered by the ref input
 */
function validateProduct(ref) {
    $('#productTable tr ').each(function() {
        var tr = $(this);
        if(tr.attr('is_equipment')) {
            console.log("")
        }
    });
}

function addLineProduct(prod) {

    cnt_product++;
    var id_tr = "p" + parseInt(prod.fk_product)
    var line = '<tr id=' + id_tr + ' is_equipment=false barcode="' + prod.barcode + '" + ref="' + prod.ref + '">';
    line += '<td>' + cnt_product + '</td>';
    line += '<td>' + prod.refurl + '</td>'; // refUrl
    line += '<td></td>'; // num série
    line += '<td>' + prod.label + '</td>'; // label
    line += '<td name="sent_qty" style="text-align: right">' + prod.quantity_sent + '</td>';
    line += '<td style="text-align: center"><img name="arrow_fill_qty" class="clickable" src="css/next.png"></img></td>';
    line += '<td><input name="received_qty"  type="number" class="custInput" style="width: 40px" min=0 value=' + prod.quantity_received + '></td>';
    $(line).appendTo('#productTable tbody');
    initSetFullQty(id_tr);
}


function addLineEquipment(equip) {

    cnt_product++;
    var id_tr = "e" + parseInt(equip.fk_product)
    var line = '<tr id=' + id_tr + ' is_equipment=true>';
    line += '<td>' + cnt_product + '</td>';
    line += '<td>' + equip.refurl + '</td>'; // refUrl
    line += '<td>' + equip.serial + '</td>'; // num série
    line += '<td>' + equip.label + '</td>'; // label
    line += '<td></td>';
    line += '<td></td>';
    line += '<td></td>';
    $(line).appendTo('#productTable tbody');
}


function initSetFullQty(id_tr) {
    $('#productTable tr#' + id_tr + ' > td > img[name=arrow_fill_qty]').click(function () {
        var sent_qty = parseInt($('#productTable tr#' + id_tr + ' > td[name=sent_qty]').text());
        $('#productTable tr#' + id_tr + ' > td > input[name=received_qty]').val(sent_qty);
    });
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
    if (type === "error")
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