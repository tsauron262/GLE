
/**
 * Globals
 */
/* global DOL_URL_ROOT */

var id_warehouse;

/**
 * Ajax call
 */
function retrieveSentLines() {
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpequipment/manageequipment/interface.php",
        data: {
        }
    });
}





$(document).ready(function () {

    initEvents();
});

function initEvents() {

}
