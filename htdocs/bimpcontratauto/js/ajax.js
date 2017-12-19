/**
 * Global variables
 */
/* global DOL_URL_ROOT */

var contrats = [];

/**
 * Ajax functions
 */

function getAllContrats() {
    id_client = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpcontratauto/interface.php",
        data: {
            id_client: id_client,
            action: 'getAllContrats'
        },
        async: false,
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {
            contrats = JSON.parse(objOut);
        }
    });
}


/**
 * Page loaded
 */

$(document).ready(function () {
    getAllContrats();
    printContrats();
//    $('#accordeon #contratsActif').css('height: 180px');
});

function printContrats() {
    var id;
    for (id in contrats) {
        if (contrats[id].statut === '0') {
            addContratAndServices(contrats[id], '#containerForInactif', id)
        } else {
            addContratAndServices(contrats[id], '#containerForActif', id)
        }
    }
}

function addContratAndServices(contrat, divIdToAppend, contratId) {

//        <div id="contratsInactif" class="item">
//        </div>

    $('<div></div>')
            .attr('id', contratId)
            .attr('class', 'accordeon item')
            .appendTo(divIdToAppend);

    $('#' + contratId)
            .attr('id', contratId+'item')
            .append('<a href="#" class="item">' + contrat.ref + '</a>');

    for (ind in contrat.services) {
        $('<p></p>')
                .attr('id', ind)
//                .attr('class', 'item')
                .text(contrat.services[ind].ref)
                .appendTo('#' + contratId+'item');
    }
}









/**
 * Other functions
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
;