/**
 * Globals variable
 */

/* global DOL_URL_ROOT */

var cnt_product;
var inventory_id;
var entrepot_id;
var is_responsible;

/**
 * Ajax call
 */

function getAllProducts() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            id_entrepot: entrepot_id,
            inventory_id: inventory_id,
            action: 'getAllProducts'
        },
        error: function () {
            setMessage('alertPlaceHolder', 'Erreur serveur 1826.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertPlaceHolder');
            } else if (is_responsible) {
                for (var id in out.products) {
                    var prod = out.products[id];
                    addLineProduct(id, prod.ref, prod.label, prod.qty, prod.qtyScanned);
                }
                for (var id in out.equipments) {
                    var eq = out.equipments[id];
                    addLineEquipment(id, eq.id_product, eq.ref, eq.serial, eq.label, eq.scanned);
                }
            }
        }
    });
}
// entry = ref, barcode or serial
function addProductInInventory(entry) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            ref: entry,
            inventory_id: inventory_id,
            action: 'addLine'
        },
        error: function () {
            setMessage('alertPlaceHolder', 'Erreur serveur 8581.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertPlaceHolder');
            } else if (out.new_id_line > 0) {
                setMessage('alertPlaceHolder', entry + " enregistré", 'error');
            }
            if (is_responsible) {
                if (out.equipment_id > 0) {
                    setScanned(out.equipment_id);
                    if (out.entrepot_name !== null)
                        setMessage('alertPlaceHolder', "Cet équipment devrait-être dans l'entrepôt de " + out.entrepot_name, 'error');
                } else if (out.product_id > 0)
                    incrementQty(out.product_id);
            }
        }
    });
}


function closeInventory() {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            inventory_id: inventory_id,
            action: 'closeInventory'
        },
        error: function () {
            setMessage('alertPlaceHolder', 'Erreur serveur 8811.', 'error');
        },
        success: function (rowOut) {
            console.log("success");
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertPlaceHolder');
            } /*else if (out.new_id_line > 0) {
                setMessage('alertPlaceHolder', entry + " enregistré", 'error');
            }*/
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {
    if ($('#productTable').length !== 0)
        is_responsible = true;
    else
        is_responsible = false;

    cnt_product = 0;
    inventory_id = getUrlParameter('id');
    entrepot_id = getUrlParameter('entrepot_id');
    getAllProducts();
    initIE('input[name=refScan]', 'addProductInInventory');

    if (is_responsible) {
        $('#closeInventory').click(function () {
            closeInventory();
        });
    }

});



/**
 * Functions
 */

/* Add a line in the table of equipments */
function addLineEquipment(equipment_id, product_id, ref, serial, label, scanned) {

    cnt_product++;

    if (scanned === undefined)
        var line = '<tr id=e' + equipment_id + '>';
    else
        var line = '<tr id=e' + equipment_id + ' style="background: #c6ffc6">';
    line += '<td name="cnt">' + cnt_product + '</td>';    // cnt ligne
    line += '<td>' + product_id + '</td>';    // id
    line += '<td>' + ref + '</td>';    // refUrl
    line += '<td>' + serial + '</td>';    // num série
    line += '<td>' + label + '</td>';    // label
    line += '<td></td>';   // Quantité Totale
    line += '<td></td>';   // Quantité Manquante
    line += '<td></td>';   // Quantité Indiqué
    $(line).appendTo('#productTable tbody');
}

/* Add a line in the table of product */
function addLineProduct(product_id, ref, label, qty_totale, qty_scanned) {

    var init_qty_scanned = qty_scanned;
    cnt_product++;
    qty_totale = parseInt(qty_totale);
    qty_scanned = parseInt(qty_scanned);

    var qty_missing = qty_totale - qty_scanned;
    if (init_qty_scanned === undefined) {
        qty_scanned = 0;
        qty_missing = qty_totale;
        var line = '<tr id=p' + product_id + '>';
    } else {
        var color;
        if (qty_missing === 0)
            color = 'c6ffc6';
        else if (qty_missing < 0)
            color = 'ff4d4d';
        else if (qty_missing > 0)
            color = 'ffd699';
        else
            color = 'cc3300';
        var line = '<tr id=p' + product_id + ' style="background: #' + color + '">';
    }

    line += '<td name="cnt">' + cnt_product + '</td>';    // cnt ligne
    line += '<td>' + product_id + '</td>';
    line += '<td>' + ref + '</td>';
    line += '<td></td>';
    line += '<td>' + label + '</td>';
    line += '<td name="qtyTotale">' + qty_totale + '</td>';
    line += '<td name="qtyMissing">' + qty_missing + '</td>';
    line += '<td name="qtyGiven">' + qty_scanned + '</td>';
    $(line).appendTo('#productTable tbody');
}

function setScanned(equipment_id) {
    $('#productTable > tbody > tr#e' + equipment_id).css('background', '#c6ffc6');
}

function incrementQty(product_id) {
    var newMissingQty = parseInt($('#productTable > tbody > tr#p' + product_id + ' > td[name=qtyMissing]').text()) - 1;
    var newGivenQty = parseInt($('#productTable > tbody > tr#p' + product_id + ' > td[name=qtyGiven]').text()) + 1;
    $('#productTable > tbody > tr#p' + product_id + ' > td[name=qtyMissing]').text(newMissingQty);
    $('#productTable > tbody > tr#p' + product_id + ' > td[name=qtyGiven]').text(newGivenQty);

    var color;
    if (newMissingQty === 0)
        color = 'c6ffc6';
    else if (newMissingQty < 0)
        color = 'ff4d4d';
    else if (newMissingQty > 0)
        color = 'ffd699';
    else
        color = 'cc3300';

    $('#productTable > tbody > tr#p' + product_id).css('background', '#' + color);
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
