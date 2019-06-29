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
                    return;
                } else {
                    right_caisse = out.right_caisse;
                    right_caisse_admin = out.right_caisse_admin;
                    out.transfers.forEach(function (transfer) {
                        addLineTransfer(transfer);
                    });
                    out.orders.forEach(function (order) {
                        addLineOrder(order);
                    });
                    diplayLinks(right_caisse, right_caisse_admin);
                }
            } catch (e) {
                setMessage('alertPlaceHolder', e + 'Erreur serveur 4355.', 'error');
            }
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {
    $('#warehouseSelect').select2({placeholder: 'Rechercher ...'});
    if ($('#warehouseSelect option:selected').length === 1 && $("#warehouseSelect option:selected").text() !== '') {
        printTable($('#warehouseSelect option:selected'));
    }
    $('#warehouseSelect').trigger('change');
    initEvents();
});

function initEvents() {
    $('#warehouseSelect').change(function () {
        printTable($(this));
    });
}

function addLineTransfer(transfer) {
    var line = '<tr id=' + transfer.id + '>';
    line += '<td>' + transfer.ref + '</td>';
    line += '<td>' + transfer.url_user + '</td>';
    line += '<td>' + transfer.name_status + '</td>';
    line += '<td>' + transfer.date_opening + '</td>';
    line += '<td>' + transfer.date_closing + '</td>';
    line += '<td>' + transfer.nb_product_scanned + '</td>';
    line += '<td>' + transfer.url_warehouse_source + '</td>';
    line += '<td><input type="button" class="butAction" value="Voir" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReception.php?id=' + transfer.id + '\'" style="margin-top: 5px"></td>';
    $(line).appendTo('#table_transfer tbody');
}

function addLineOrder(order) {

    var id_tr = order.id;
    var line = '<tr id=' + id_tr + '>';
    line += '<td>' + order.url_fourn + '</td>';
    line += '<td>' + order.name_status + '</td>';
    line += '<td>' + order.date_opening + '</td>';
    line += '<td>' + order.url_livraison + '</td>';
    $(line).appendTo('#table_order tbody');
}

function printTable(option_selected) {
    if (fk_warehouse === undefined) {
        $('#allTheFiche').css('visibility', 'visible');
        $('#allTheFiche').addClass('fade-in');
        fk_warehouse = option_selected.val();
        getLineTransferAndOrder();
    } else {
        fk_warehouse = option_selected.val();
        $('#allTheFiche').removeClass('fade-in');
        setTimeout(function () {
            $('#ph_links').empty();
            $('#table_transfer > tbody > tr').empty();
            $('#table_order > tbody > tr').empty();
            getLineTransferAndOrder();
            $('#allTheFiche').addClass('fade-in');
        }, 500);
    }
    url = window.location.href;
    var new_url = replaceUrlParam(url, 'boutique', fk_warehouse);
    window.history.pushState('Object', 'Accueil Boutique', new_url);
}

function diplayLinks(right_caisse, right_caisse_admin) {
//    $('#ph_links').append('<input type="button" class="butAction" value="Créer transfert" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewTransfer.php?entrepot=' + fk_warehouse + '\'">');
    $('#ph_links').append('<input type="button" class="butAction" value="Accéder equipement" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/?fc=entrepot&id=' + fk_warehouse + '\'"></td>');
    $('#ph_links').append('<input type="button" class="butAction" value="Accéder réservation" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpreservation/index.php?fc=entrepot&id=' + fk_warehouse + '\'">');
//    $('#ph_links').append('<input type="button" class="butAction" value="Accéder inventaire" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewInventoryMain.php?entrepot=' + fk_warehouse + '\'">');
    if (right_caisse === 1)
        $('#ph_links').append('<input type="button" class="butAction" value="Accéder caisse" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpcaisse/?id_entrepot=' + fk_warehouse + '\'"></td>');
    if (right_caisse_admin === 1)
        $('#ph_links').append('<input type="button" class="butAction" value="Accéder caisse admin" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpcaisse/?fc=admin&id_entrepot=' + fk_warehouse + '\'">');
    $('#ph_links').append('<input type="button" class="butAction" value="Tous les transferts" onclick="location.href=\'' + DOL_URL_ROOT + '/bimptransfer?entrepot_id=' + fk_warehouse + '\'">');
//    $('#ph_links').append('<input type="button" class="butAction" value="BL Non envoyés" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpreservation/index.php?fc=shipments&shipped=0&invoiced=0&id_entrepot=' + fk_warehouse + '\'">');
//    $('#ph_links').append('<input type="button" class="butAction" value="BL Non facturés" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpreservation/index.php?fc=shipments&shipped=1&invoiced=0&id_entrepot=' + fk_warehouse + '\'">');
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

function replaceUrlParam(url, paramName, paramValue) {
    if (paramValue === null) {
        paramValue = '';
    }
    var pattern = new RegExp('\\b(' + paramName + '=).*?(&|$)');
    if (url.search(pattern) >= 0) {
        return url.replace(pattern, '$1' + paramValue + '$2');
    }
    url = url.replace(/\?$/, '');
    return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue;
}