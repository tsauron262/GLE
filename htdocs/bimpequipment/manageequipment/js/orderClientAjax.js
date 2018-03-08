
/**
 * Globals
 */
/* global DOL_URL_ROOT */
var cnt_product = 0;
var green = '#ADFF92';
var yellow = '#FFFF96';
var red = '#FF8484';

/**
 * Ajax call
 */
function retrieveOrderClient() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_order: getUrlParameter('id'),
            ref_order: getUrlParameter('ref'),
            action: 'retrieveOrderClient'
        },
        error: function () {
            setMessage('alert_ordered', 'Erreur interne 6896.', 'error');
        },
        success: function (rowOut) {
            try {
                var out = JSON.parse(rowOut);
                if (out.errors.length !== 0) {
                    printErrors(out.errors, 'alert_ordered');
                } else if (out.order) {
                    out.order['lines'].forEach(function (prod) {
                        addLineProduct(prod);
                    });
                } else {
                    setMessage('alert_ordered', 'Erreur interne 5343.', 'error');
                }
            } catch (e) {
                setMessage('alert_ordered', 'Erreur interne 3783.' + e, 'error');
            }
        }
    });
}



$(document).ready(function () {

    retrieveOrderClient();
    initEvents();
    initIE('input[name=scan_ordered]', 'validateProductOrdered', 'input#qty_ordered');
    initIE('input[name=scan_sent]', 'validateProductSent', 'input#qty_sent');
    initIE('input[name=scan_received]', 'validateProductReceived', 'input#qty_received');
});

function initEvents() {

}

function validateProductOrdered() {
    
}

function validateProductSent() {
    
}

function validateProductReceived() {
    
}

function addLineProduct(prod) {

    cnt_product++;

    var id_tr = "p" + parseInt(prod.fk_product);
    var line = '<tr id=' + id_tr + ' is_equipment=false>';//barcode="' + prod.barcode + '" ref="' + prod.ref + '" qty_received_befor=' + prod.quantity_received + '>';
    line += '<td>' + cnt_product + '</td>';
    line += '<td>' + prod.ref_url + '</td>'; // refUrl
    line += '<td></td>'; // num série
    line += '<td>' + prod.label + '</td>'; // label
    line += '<td name="sent_qty" style="text-align: right">' + prod.qty_total + '</td>';
    line += '<td style="padding: 0px; text-align: center"><img name="arrow_fill_qty" class="clickable" src="css/next.png"></img></td>';
    line += '<td style="padding: 0px"><input name="received_qty"  type="number" class="custInput" style="width: 60px" min=0 value=0> + ' + prod.qty_total + '</td>';
    $(line).appendTo('#table_ordered tbody');
//    initSetFullQty(id_tr);
//
//    if (prod.quantity_received > 0)
//        setColors(id_tr, parseInt(prod.quantity_received), parseInt(prod.quantity_sent));
}

//function setColors(id_tr, quantity_received, quantity_sent) {
//    var color;
//    if (quantity_received === quantity_sent)
//        color = green;
//    else if (quantity_received < quantity_sent)
//        color = yellow;
//    else if (quantity_received > quantity_sent)
//        color = red;
//    $('#product_table tr#' + id_tr).css('background', color);
//}

function addLineEquipment(equip) {

    cnt_product++;
    var id_tr = "e" + parseInt(equip.fk_equipment);
    var line = '<tr id=' + id_tr + ' is_equipment="true" barcode="' +
            equip.barcode + '" ref="' + equip.ref + '" serial="' + equip.serial + '" qty_received_befor=' + equip.quantity_received + '>';
    line += '<td>' + cnt_product + '</td>';
    line += '<td>' + equip.refurl + '</td>'; // refUrl
    line += '<td>' + equip.serial + '</td>'; // num série
    line += '<td>' + equip.label + '</td>'; // label
    line += '<td></td>';
    line += '<td></td>';
    line += '<td></td>';
    $(line).appendTo('#product_table tbody');

    if (equip.quantity_received === equip.quantity_sent)
        $('#product_table tr#' + id_tr).css('background', green);
}



/**
 * @param {HTML element} tr
 */
function setScannedProduct(tr, qty_to_add) {
    if (qty_to_add < 0)
        return;

    var qty_to_add = parseInt(qty_to_add);
    var init_qty = parseInt(tr.find('input[name=received_qty]').val());
    var new_qty = init_qty + qty_to_add;
    tr.find('input[name=received_qty]').val(new_qty);
    tr.attr('scanned_this_session', true);
    var total_received = new_qty + parseInt(tr.attr('qty_received_befor'));
    setColors(tr.attr('id'), total_received, (parseInt(tr.find('td[name=sent_qty]').text())));
}


/**
 * @param {HTML element} tr
 */
function setScannedEquipment(tr) {
    tr.attr('scanned_this_session', true);
    tr.css('background', green);
}


function getId(input) {
    return input.match(/[0-9]+/)[0];
}




/*
 * Annexes functions
 */

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