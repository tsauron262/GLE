/* global DOL_URL_ROOT */

var idEntrepotTable;

function getInventoriesForEntrepot() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            idEntrepot: idEntrepotTable,
            action: 'getInventoriesForEntrepot'
        },
        error: function () {
            setMessage('alertEnregistrer', 'Erreur serveur 5326.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertPlaceholder');
            } else {
                out.inventories.forEach(function (inventory) {
                    addLineInventory(inventory.id, inventory.url_user, inventory.statut, inventory.date_ouverture, inventory.date_fermeture, inventory.prod_scanned);
                });
            }
        }
    });
}

function createInventory(idEntrepotcreate) {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
            idEntrepotCreate: idEntrepotcreate,
            action: 'createInventory'
        },
        error: function () {
            setMessage('alertCreate', 'Erreur serveur 2926.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertCreate');
            } else if (out.id_inserted) {
                $('#alertCreate').append('<input type="button" class="butAction" value="Voir nouvel inventaire" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewInventory.php?id=' + out.id_inserted + '&entrepot_id=' + $('#entrepotCreate').val() + '\'" style="margin-top: 5px">');
            } else {
                setMessage('alertCreate', "Pas de création, pas d'erreur côté serveur.", 'error');
            }
        }
    });
}


function addLineInventory(id, responsable, statut, date_ouverture, date_fermeture, nb_prod) {
    if (statut === '0') {
        statut = 'Brouillon';
    } else if (statut === '1') {
        statut = 'En cours';
    } else if (statut === '2') {
        statut = 'Fermé'
    }

    var line = '<tr>';
    line += '<td>' + id + '</td>';   // id
    line += '<td>' + responsable + '</td>';   // responsible
    line += '<td>' + statut + '</td>';   // statut
    line += '<td>' + date_ouverture + '</td>';   // date_open
    line += '<td>' + date_fermeture + '</td>';   // date_close
    line += '<td>' + nb_prod + '</td>';   // nb_prod
    line += '<td><input type="button" class="butAction" value="Voir" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewInventory.php?id=' + id + '&entrepot_id=' + idEntrepotTable + '\'"></td>';   // link
    line += '</tr>';

    $(line).appendTo('#allInventories tbody');
}


$(document).ready(function () {
    
    initEvents();

    $('#entrepotTable').select2({placeholder: 'Rechercher ...'});
    $('#entrepotTable  option:selected').prop('selected', true);
    $('#entrepotTable').trigger('change');

    $('#entrepotCreate').select2({placeholder: 'Rechercher ...'});
    $('#entrepotCreate  option:selected').prop('selected', true);

});




function initEvents() {

    $('#entrepotTable').on('change', function () {
        if (idEntrepotTable === undefined) {
            $('#allTheFiche').css('visibility', 'visible');
            $('#allTheFiche').addClass('fade-in');
        } else {
            $('table#allInventories > tbody > tr').remove();
        }
        idEntrepotTable = $(this).val();
        getInventoriesForEntrepot();
    });


    $('#createInventory').on('click', function () {
        if (confirm('Êtes-vous sûr de vouloir créer un nouveau inventaire ?')) {
            createInventory($('#entrepotCreate').val());
        }
    });
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