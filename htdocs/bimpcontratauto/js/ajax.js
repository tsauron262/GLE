/**
 * Global variables
 */
/* global DOL_URL_ROOT */

var contrats = [];

var HEIGHT_DIV_CONTRAT = 30; /* change height of .accordeon div and .accordeon.item in css if you change that value */
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
    $(document).on("click", 'div.clickable.item', function () { // click on existing contrat
        var newHeight = getNewContratHeight($(this).parent());
        $(this).parent().css("height", newHeight);
    });
    $(document).on("click", 'div.divClikable', function () {   // add/change field of new contrat
        changeValueOfField($(this).parent(), $(this).attr('val'));
    });
    $(document).on("click", 'div.buttonCustom', function () {
        var emptyField = valider();
    });
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

/*
 * Functions when a an event is triggered
 */

function getNewContratHeight(accDiv) {
    if (accDiv.height() === HEIGHT_DIV_CONTRAT) {
        var nbOfLine = parseInt(accDiv.attr('nbChild'));
        return HEIGHT_DIV_CONTRAT * (nbOfLine + 2);
    }
    return HEIGHT_DIV_CONTRAT;
}

function changeValueOfField(parent, value) {
    parent.children().each(function () {
        if ($(this).hasClass('divClikable')) {
            if ($(this).hasClass('isSelected')) {
                $(this).removeClass('isSelected');
            }
            if ($(this).attr('val') === value) {
                $(this).addClass('isSelected');
            }
        }
    });
}

function valider() {
    return '';
}


/**
 * Other functions
 */


function addContratAndServices(contrat, divIdToAppend, contratId) {

    $('<div></div>')
            .attr('id', contratId)
            .attr('class', 'accordeon item')
            .attr('nbChild', contrat.services.length)
            .appendTo(divIdToAppend);

    $('#' + contratId)
            .attr('id', contratId + 'item')
            .append('<div class="clickable item">' +
                    contrat.ref +
                    '&nbsp;'.repeat(5) + 'Date de début: ' + contrat.dateDebutContrat +
                    '&nbsp;'.repeat(5) + 'Date de fin: ' + contrat.dateFinContrat +
                    '&nbsp;'.repeat(5) + 'Nombre de service: ' + contrat.nbService +
                    '&nbsp;'.repeat(5) + 'Prix total: ' + contrat.prixTotalContrat + ' €' +
                    '</div>');

    initTable(contratId);

    for (var ind in contrat.services) {
        printServiceDetails(contratId, contrat.services[ind], ind);
    }
}

function initTable(contratId) {
    $('<table></table>')
            .attr('id', contratId + 'table')
            .attr('class', 'w3-table-all w3-hoverable')
            .appendTo('#' + contratId + 'item');

    $('<thead></thead>')
            .attr('id', contratId + 'thead')
            .appendTo('#' + contratId + 'table');

    $('<tr></tr>')
            .attr('id', contratId + 'trHead')
            .attr('class', 'w3-light-grey')
            .appendTo('#' + contratId + 'thead');

    var arrayOfField = ['Nom du service', 'Date de début', 'Date de fin', 'Durée (en mois)', 'Prix unitaire (en euros)', 'Prix total (en euros)'];

    arrayOfField.forEach(function (item) {
        $('<th></th>').text(item).appendTo('#' + contratId + 'trHead');
    });
}

function printServiceDetails(contratId, service, indService) {
    var arrayOfValue = [service.ref, service.dateDebutService, service.dateFinService, service.qty, service.prixUnitaire, service.prixTotal];

    $('<tr></tr>')
            .attr('id', contratId + 'tr' + indService)
            .appendTo('#' + contratId + 'table');

    if (service.statut === 0) {    // if the service have to be closed
        $('#' + contratId + 'tr' + indService).css("background-color", "#d34a4a");
    }

    arrayOfValue.forEach(function (item) {
        $('<td></td>')
                .text(item)
                .appendTo('#' + contratId + 'tr' + indService);
    });
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
;