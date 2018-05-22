
/**
 * Globals
 */
/* global DOL_URL_ROOT */

var id_warehouse;

var green = '#ADFF92';
var yellow = '#FFFF96';
var red = '#FF8484';
var white = '#FFFFFF';

/**
 * Ajax call
 */
function retrieveSentLines() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_transfer: getUrlParameter('id'),
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
                    if (parseInt(prod.fk_equipment) === 0) { // product
                        addLineProduct(prod);
                    } else { // equipment
                        addLineEquipment(prod);
                    }
                });
                $('#table_canceled > tbody > tr').css('background', red);
            }
        }
    });
}

function receiveTransfert(products, equipments) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_transfer: getUrlParameter('id'),
            products: products,
            equipments: equipments,
            action: 'receiveTransfer'
        },
        error: function () {
            setMessage('alertTop', 'Erreur interne 1514.', 'error');
        },
        success: function (rowOut) {
            try {
                var out = JSON.parse(rowOut);
                if (out.errors.length !== 0) {
                    printErrors(out.errors, 'alertTop');
                } else if (out.nb_update) {
                    $('#table_pending  > tbody').empty();
                    $('#table_received > tbody').empty();
                    $('#table_canceled > tbody').empty();
                    retrieveSentLines();
                    setMessage('alertTop', 'Produits enregistré', 'mesgs');
                    alert('Enregistrement réalisé');
                    if (out.is_now_closed === true) {
                        alert('Tous les produits ont été reçus. Le transfert a été fermé automatiquement.');
                        location.reload();
                    }
                } else {
                    setMessage('alertTop', 'Erreur interne 6490.', 'error');
                }
            } catch (e) {
                setMessage('alertTop', 'Erreur interne 6489.', 'error');
            }
        }
    });
}

function closeTransfer() {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_transfer: getUrlParameter('id'),
            action: 'closeTransfer'
        },
        error: function () {
            setMessage('alertTop', 'Erreur interne 3854.', 'error');
        },
        success: function (rowOut) {
            try {
                var out = JSON.parse(rowOut);
                if (out.errors.length !== 0) {
                    printErrors(out.errors, 'alertTop');
                } else if (out.status_changed) {
                    alert('Transfert fermé');
                    location.reload();
                }
            } catch (e) {
                setMessage('alertTop', 'Erreur interne 7961.', 'error');
            }
        }
    });
}

$(document).ready(function () {

    retrieveSentLines();
    initEvents();
    initIE('input[name=refScan]', 'validateProduct', 'input#qty');
});

function initEvents() {
    $('#register').click(function () {
        var equipments = [];
        var products = [];
        var no_new_scan = true;
        $('#table_pending tr[scanned_this_session]').each(function () {
            no_new_scan = false;
            var tr = $(this);
            var previous_qty = parseInt(tr.attr('qty_received_befor'));
            var added_qty = parseInt(tr.find('input[name=received_qty]').val());
            if (tr.attr('is_equipment') === 'true') {
                equipments.push({
                    id_reservation: tr.attr('id_reservation'),
                    fk_product: tr.attr('fk_product'),
                    fk_equipment: getId(tr.attr('id'))
                });
            } else {
                products.push({
                    id_reservation: tr.attr('id_reservation'),
                    fk_product: tr.attr('fk_product'),
                    previous_qty: previous_qty,
                    added_qty: added_qty
                });
            }
        });

        if (no_new_scan) {
            setMessage('alertTop', 'Veuillez scanner des produits avant d\'enregistrer la réception du transfert.', 'error');
            return;
        }
        receiveTransfert(products, equipments);
    });


    $('#closeTransfer').click(function () {
        if (confirm("Vous êtes sur le point de fermer le transfert. \
Cette action implique de marquer comme abandonné tous les produits\
 qui n'ont pas étés reçu. Continuer ?"))
            closeTransfer();
    });
}

function addLineProduct(prod) {

    var table_suffix;
    if (parseInt(prod.status) === 201)
        table_suffix = 'pending';
    else if (parseInt(prod.status) === 301)
        table_suffix = 'received';
    else if (parseInt(prod.status) === 303)
        table_suffix = 'canceled';

    var id_tr = 'p' + parseInt(prod.fk_product) + 'r' + prod.id_reservation;
    var line = '<tr id=' + id_tr + ' fk_product=' + prod.fk_product + ' is_equipment=false barcode="' + prod.barcode + '" id_reservation=' + prod.id_reservation +
            ' ref="' + prod.ref + '" qty_received_befor=' + prod.quantity_received + ' table_suffix="' + table_suffix + '">';
    line += '<td>' + prod.date_from + '</td>';
    line += '<td>' + prod.refurl + '</td>'; // refUrl
    line += '<td></td>'; // num série
    line += '<td>' + prod.label + '</td>'; // label
    line += '<td></td>'; // price
    line += '<td name="sent_qty" style="text-align: right">' + prod.quantity_sent + '</td>';
    if (parseInt(prod.status) === 201) {
        line += '<td style="padding: 0px; text-align: center"><img name="arrow_fill_qty" class="clickable" src="css/next.png"></img></td>';
        line += '<td style="padding: 0px"><input name="received_qty"  type="number" class="custInput" style="width: 60px" min=0 max=' + parseInt(prod.quantity_sent) + ' value=0></td>';
    } else {
        line += '<td></td>';
        line += '<td>' + prod.quantity_received + '</td>';
    }
    $(line).appendTo('#table_' + table_suffix + ' tbody');

    if (parseInt(prod.status) === 201) {
        initSetFullQty(id_tr, table_suffix);
        initControlMax(id_tr, table_suffix);
        initColorEvent(id_tr, table_suffix);
    }

    if (parseInt(prod.quantity_received) > 0)
        setColors(id_tr, table_suffix, parseInt(prod.quantity_received), parseInt(prod.quantity_sent));
}

function initControlMax(id_tr, table_suffix) {
    $('#table_' + table_suffix + ' tr#' + id_tr + ' input[name=received_qty]').bind('keyup mouseup', function () {
        if(parseInt($(this).val()) > parseInt($(this).attr('max'))) {
            $(this).val(parseInt($(this).attr('max')));
        }
    });
}

function initColorEvent(id_tr, table_suffix) {
    $('#table_' + table_suffix + ' tr#' + id_tr + ' input[name=received_qty]').bind('keyup mouseup', function () {
        var quantity_received = parseInt($(this).val()) + parseInt($('tr#' + id_tr).attr('qty_received_befor'));
        var quantity_sent = parseInt($('tr#' + id_tr + ' td[name=sent_qty]').text());
        setColors(id_tr, table_suffix, quantity_received, quantity_sent);
    });
}

function setColors(id_tr, table_suffix, quantity_received, quantity_sent) {
    var color;
    if (quantity_received === 0)
        color = white;
    else if (quantity_received === quantity_sent)
        color = green;
    else if (quantity_received < quantity_sent)
        color = yellow;
    else if (quantity_received > quantity_sent)
        color = red;
    $('#table_' + table_suffix + ' tr#' + id_tr + " td").attr('style', 'background-color:'+ color + "!important");
}

function addLineEquipment(equip) {

    var table_suffix;
    if (parseInt(equip.status) === 201)
        table_suffix = 'pending';
    else if (parseInt(equip.status) === 301)
        table_suffix = 'received';
    else if (parseInt(equip.status) === 303)
        table_suffix = 'canceled';

    var id_tr = "e" + parseInt(equip.fk_equipment);
    var line = '<tr id=' + id_tr + ' fk_product=' + equip.fk_product + ' is_equipment="true" fk_product=' + equip.fk_product + '  id_reservation=' + equip.id_reservation + ' barcode="' +
            equip.barcode + '" ref="' + equip.ref + '" serial="' + equip.serial + '" qty_received_befor=' + equip.quantity_received + ' table_suffix="' + table_suffix + '">';
    line += '<td>' + equip.date_from + '</td>';
    line += '<td>' + equip.refurl + '</td>'; // refUrl
    line += '<td>' + equip.serial + '</td>'; // num série
    line += '<td>' + equip.label + '</td>'; // label
    line += '<td>' + equip.price + ' €</td>'; // price
    line += '<td></td>';
    line += '<td></td>';
    line += '<td></td>';
    $(line).appendTo('#table_' + table_suffix + ' tbody');

    if (parseInt(equip.quantity_received) === 1)
        $('#table_' + table_suffix + ' tr#' + id_tr + ' td').attr('style', 'background-color:green!important');
}


function initSetFullQty(id_tr, table_suffix) {
    $('#table_' + table_suffix + ' tr#' + id_tr + ' > td > img[name=arrow_fill_qty]').click(function () {
        var sent_qty = parseInt($('#table_' + table_suffix + ' tr#' + id_tr + ' > td[name=sent_qty]').text());
        var qty_received_befor = parseInt($('#table_' + table_suffix + ' tr#' + id_tr).attr('qty_received_befor'));
        var new_qty = sent_qty - qty_received_befor;
        if (new_qty < 0)
            new_qty = 0;
        $('#table_' + table_suffix + ' tr#' + id_tr + ' > td > input[name=received_qty]').val(new_qty);
        $('#table_' + table_suffix + ' tr#' + id_tr).attr('scanned_this_session', true);
        setColors(id_tr, table_suffix, new_qty, sent_qty);
    });
}


/**
 * Triggered by the ref input
 * @param {String} ref can also be a barcode or a serial number
 */
function validateProduct(ref) {
    //console.log('déclenché ' + ref);
    var ok =false;
    $('#table_pending tr ').each(function () {
        var tr = $(this);
        if (tr.attr('is_equipment') === 'true') { // Equipment
            if (ref === tr.attr('barcode')) {
                setMessage('alertTop', 'Merci de renseigner le numéro de série plutôt que le code barre.', 'error');
                return false;
            } else if (ref === tr.attr('ref')) {
                setMessage('alertTop', 'Merci de renseigner le numéro de série plutôt que la référence.', 'error');
                return false;
            } else if (ref === tr.attr('serial')) {
                setScannedEquipment(tr);
                ok = true;
            }
        } else if (tr.attr('is_equipment') === 'false') { // Product
            if (ref === tr.attr('barcode') || ref === tr.attr('ref')) {
                setScannedProduct(tr, $('input#qty').val());
                ok = true;
            }
        }
    });
    if(!ok){
        setMessage('alertTop', 'Aucune correpondance trouvée.', 'error');
        return false;
    }
}

function getNewStatus() {
    var new_status = 'total';
    var new_qty;
    var previous_qty;
    var added_qty;
    var sent_qty;
    $('#table_pending > tbody > tr').each(function () {
        var tr = $(this);
        if (tr.attr('is_equipment') === 'true') {
            if (tr.attr('qty_received_befor') !== '1' && tr.attr('scanned_this_session') !== 'true') {
                new_status = 'partial';
                return;
            }
        } else {
            previous_qty = parseInt(tr.attr('qty_received_befor'));
            added_qty = parseInt(tr.find('input[name=received_qty]').val());
            new_qty = previous_qty + added_qty;
            sent_qty = parseInt(tr.find('td[name=sent_qty]').text());
            if (new_qty < sent_qty) {
                new_status = 'partial';
                return;
            }
        }

    });
    return new_status;
}

function setScannedProduct(tr, qty_to_add) {
    if (qty_to_add < 0)
        return;

    var qty_to_add = parseInt(qty_to_add);
    var init_qty = parseInt(tr.find('input[name=received_qty]').val());
    var new_qty = init_qty + qty_to_add;
    if (qty_to_add > parseInt(tr.find('input[name=received_qty]').attr('max'))) {
        new_qty = parseInt(tr.find('input[name=received_qty]').attr('max'));
    }
    tr.find('input[name=received_qty]').val(new_qty);
    tr.attr('scanned_this_session', true);
    var total_received = new_qty + parseInt(tr.attr('qty_received_befor'));
    setColors(tr.attr('id'), 'pending', total_received, (parseInt(tr.find('td[name=sent_qty]').text())));
}

/**
 * @param {HTML element} tr
 */
function setScannedEquipment(tr) {
    tr.attr('scanned_this_session', true);
    tr.find('td').attr('style', 'background-color:green!important');
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


function getId(input) {
    return input.match(/[0-9]+/)[0];
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