
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
                    if (out.order['lines'] !== undefined) {
                        out.order['lines'].forEach(function (prod) {
                            addLineProduct(prod, 'ordered');
                        });
                    } else {
                        setMessage('alert_ordered', "Il n'y a pas de ligne dans cette commande.", 'warn');
                    }
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

function addLineProduct(prod, suffix) {

    cnt_product++;

    var id_tr = "p" + parseInt(prod.fk_product);
    var line = '<tr id=' + id_tr + ' is_equipment=false barcode="' + prod.barcode + '" ref="' + prod.ref + '" qty_total=' + prod.qty_total +
            ' qty_previous_session=' + prod.qty_previous_session + '>';
    line += '<td>' + cnt_product + '</td>';
    line += '<td>' + prod.ref_url + '</td>'; // refUrl
    line += '<td></td>'; // num série
    line += '<td>' + prod.label + '</td>'; // label
    line += '<td name="sent_qty" style="text-align: right">' + prod.qty_total + '</td>';
    line += '<td style="padding: 0px; text-align: center"><img name="arrow_fill_qty" class="clickable" src="css/next.png"></img></td>';
    line += '<td style="padding: 0px"><input name="received_qty"  type="number" class="custInput" style="width: 60px" min=0 value=0> + ' + prod.qty_previous_session + '</td>';
    $(line).appendTo('#table_' + suffix + ' tbody');
    initSetFullQty(id_tr, suffix);

    initColorEvent(id_tr, suffix);

    if (prod.qty_previous_session > 0)
        setColors(id_tr, suffix, parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_previous_session')), parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_total')));
}

function initSetFullQty(id_tr, suffix) {
    $('#table_' + suffix + ' tr#' + id_tr + ' > td > img[name=arrow_fill_qty]').click(function () {
        $('#table_' + suffix + ' tr#' + id_tr + ' > td > input[name=received_qty]').val(parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_total')) - parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_previous_session')));
        setColors(id_tr, suffix, parseInt($('#table_' + suffix + ' tr#' + id_tr + ' > td > input[name=received_qty]').val()) + parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_previous_session')), parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_total')));
    });
}

function initColorEvent(id_tr, suffix) {
    $('#table_' + suffix + ' tr#' + id_tr + ' input[name=received_qty]').bind('keyup mouseup', function () {
        var quantity_received = parseInt($(this).val()) + parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_previous_session'));
        var quantity_sent = parseInt($('#table_' + suffix + ' tr#' + id_tr).attr('qty_total'));
        setColors(id_tr, suffix, quantity_received, quantity_sent);
    });
}

function setColors(id_tr, suffix, quantity_received, quantity_sent) {
    var color;
    if (quantity_received === quantity_sent)
        color = green;
    else if (quantity_received < quantity_sent)
        color = yellow;
    else if (quantity_received > quantity_sent)
        color = red;
    $('#table_' + suffix + ' tr#' + id_tr).css('background', color);
}

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


function validateProduct(suffix, ref) {
    $('#table_' + suffix + ' tr ').each(function () {
        var tr = $(this);
        /* if (tr.attr('is_equipment') === 'true') { // Equipment
         if (ref === tr.attr('barcode')) {
         setMessage('alertTop', 'Merci de renseigner le numéro de série plutôt que le code barre.', 'error');
         return false;
         } else if (ref === tr.attr('ref')) {
         setMessage('alertTop', 'Merci de renseigner le numéro de série plutôt que la référence.', 'error');
         return false;
         } else if (ref === tr.attr('serial')) {
         setScannedEquipment(tr);
         }
         } else*/ if (tr.attr('is_equipment') === 'false') { // Product
            if (ref === tr.attr('barcode') || ref === tr.attr('ref')) {
                setScannedProduct(tr, suffix, $('input#qty_' + suffix).val());
            }
        }
    });
}

function validateProductOrdered(ref) {
    validateProduct('ordered', ref);
}

function validateProductSent(ref) {
    validateProduct('sent', ref);
}

function validateProductReceived(ref) {
    validateProduct('received', ref);
}


/**
 * @param {HTML element} tr
 */
function setScannedProduct(tr, suffix, qty_to_add) {
    if (qty_to_add < 0)
        return;
    
    var new_qty = parseInt(qty_to_add) + parseInt(tr.find('input[name=received_qty]').val());
    tr.find('input[name=received_qty]').val(new_qty);
    setColors(tr.attr('id'), suffix, new_qty + parseInt(tr.attr('qty_previous_session')), parseInt(tr.attr('qty_total')));
//    function setColors(id_tr, suffix, quantity_received, quantity_sent) {

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
 * @param {String} type 'msg' => normal message (green) 'warn' +> warning (yellow) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var is_error = false;
    var backgroundColor;
    if (type === 'msg')
        backgroundColor = '#25891c ';
    else if (type === 'warn')
        backgroundColor = '#FFFF96 ';
    else {
        backgroundColor = '#ff887a ';
        is_error = true;
    }

    var id_alert = 'alert' + Math.floor(Math.random() * 10000) + 1;
    $('#' + idElement).append('<div id="' + id_alert + '" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px; color:black">' + message + ' <span id="cross' + id_alert + '" style="position:relative; top:-8px; right:-5px ; cursor: pointer;">&#10005;</span></div>');
    $('#cross' + id_alert).click(function () {
        $(this).parent().fadeOut(500);
    });

    setTimeout(function () {
        $("#" + id_alert + "").fadeOut(1000);
        setTimeout(function () {
            $("#" + id_alert + "").remove();
        }, 1000);
    }, (is_error) ? 3600000 : 10000);
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