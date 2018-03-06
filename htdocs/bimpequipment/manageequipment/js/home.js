/**
 * Globals
 */

/* global DOL_URL_ROOT */

var fk_warehouse;


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
        displayLinks($(this));
    });
}

function displayLinks(warehouseElement) {
    if (fk_warehouse === undefined) {
        $('#allTheFiche').css('visibility', 'visible');
        $('#allTheFiche').addClass('fade-in');
        fk_warehouse = warehouseElement.val();
        diplayLink();
    } else {
        fk_warehouse = warehouseElement.val();
        $('#allTheFiche').removeClass('fade-in');
        setTimeout(function () {
            $('#allTheFiche').empty();
            $('#allTheFiche').addClass('fade-in');
            diplayLink();
        }, 500);

    }
}

function diplayLink() {
//    $('#allTheFiche').append('<input type="button" class="butAction" value="Réception commande" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReceptionMain.php?entrepot_id=' + fk_warehouse + '\'"></td>');
    $('#allTheFiche').append('<input type="button" class="butAction" value="Réception transfert" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpequipment/manageequipment/viewReceptionMain.php?entrepot_id=' + fk_warehouse + '\'"></td>');
    $('#allTheFiche').append('<input type="button" class="butAction" value="Accéder bimp caisse" onclick="location.href=\'' + DOL_URL_ROOT + '/bimpcaisse/\'"></td>');
}