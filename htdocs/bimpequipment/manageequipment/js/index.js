/**
 * Globals
 */

/* global DOL_URL_ROOT */

var fk_warehouse;

function getLineTransferAndOrder() {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            fk_warehouse: fk_warehouse,
            action: 'getLineTransferAndOrder'
        },
        error: function () {
            setMessage('alertPlaceHolder', 'Erreur serveur 4354.', 'error');
        },
        success: function (rowOut) {
            try {
                var out = JSON.parse(rowOut);
                if (out.errors.length !== 0) {
                    printErrors(out.errors, 'alertPlaceHolder');
                } else if (out.transfers !== undefined) {
                    out.transfers.forEach(function (transfer) {
                        addLineTransfer(transfer);
                    });
                }
            } catch (e) {
                setMessage('alertPlaceHolder', 'Erreur serveur 4355.', 'error');
            }
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {

    $('#warehouseSelect').select2({placeholder: 'Rechercher ...'});
    $('#warehouseSelect option:selected').prop('selected', true);
    $('#warehouseSelect').trigger('change');
    initEvents();
});


function initEvents() {
    $('#warehouseSelect').change(function () {
        printTable($(this));
    });
}

function addLineTransfer(transfer) {

    var id_tr = transfer.id;
    var line = '<tr id=' + id_tr + '>';
    line += '<td>' + transfer.url_user + '</td>';
    line += '<td>' + transfer.name_status + '</td>';
    line += '<td>' + transfer.date_opening + '</td>';
    line += '<td>' + transfer.date_closing + '</td>';
    line += '<td>' + transfer.nb_product_scanned + '</td>';
    line += '<td>' + transfer.url_warehouse_source + '</td>';
    line += '<td><input type="button" class="butAction" value="Voir" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReception.php?id=' + transfer.id + '\'" style="margin-top: 5px"></td>';
    $(line).appendTo('#table_transfer tbody');
}

function printTable(warehouseElement) {
    if (fk_warehouse === undefined) {
        $('#allTheFiche').css('visibility', 'visible');
        $('#allTheFiche').addClass('fade-in');
        fk_warehouse = warehouseElement.val();
        diplayLinks();
        getLineTransferAndOrder();
    } else {
        fk_warehouse = warehouseElement.val();
        $('#allTheFiche').removeClass('fade-in');
        setTimeout(function () {
            $('#ph_links').empty();
            $('#table_transfer > tr > tbody').empty();
            $('#table_order > tr > tbody').empty();
            diplayLinks();
            getLineTransferAndOrder();
            $('#allTheFiche').addClass('fade-in');
        }, 500);
    }
}

function diplayLinks() {
//    $('#allTheFiche').append('<input type="button" class="butAction" value="Réception commande" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReceptionMain.php?entrepot_id=' + fk_warehouse + '\'"></td>');
    $('#ph_links').append('<input type="button" class="butAction" value="Réception transfert" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReceptionMain.php?entrepot_id=' + fk_warehouse + '\'"></td>');
    $('#ph_links').append('<input type="button" class="butAction" value="Créer transfert" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewTransfer.php\'"></td>');
    $('#ph_links').append('<input type="button" class="butAction" value="Accéder bimp caisse" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpcaisse/\'"></td>');
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