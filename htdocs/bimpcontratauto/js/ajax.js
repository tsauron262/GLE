/**
 * Global variables
 */
/* global DOL_URL_ROOT */

var contrats = [];

var HEIGHT_DIV_CONTRAT = 30; /* change height of classes ".accordeon" and ".accordeon.item" in css if you change that value */



/**
 * Ajax functions
 */

function getAllContrats() {
    socid = getUrlParameter('socid');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpcontratauto/interface.php",
        data: {
            socid: socid,
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

function newContrat(services, dateDeb) {
    socid = getUrlParameter('socid');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpcontratauto/interface.php",
        data: {
            socid: socid,
            services: services,
            dateDeb: dateDeb,
            action: 'newContrat'
        },
        async: false,
        error: function () {
            console.log("Erreur PHP");
        }
    });
}





/**
 * Page loaded
 */

$(document).ready(function () {
    getAllContrats();
    printContrats();
    $('#datepicker').datepicker({minDate: 0});                  // create datepicker and disallow previous dates (befor today)
    $(document).on("click", 'div.clickable.item', function () { // click on existing contrat
        var newHeight = getNewContratHeight($(this).parent());
        $(this).parent().css("height", newHeight);
    });
    $(document).on("click", 'div.divClikable', function () {   // change field of new contrat
        changeValueOfField($(this).parent(), $(this).text());
    });
    $(document).on("click", 'div.buttonCustom', function () {
        valider();
    });
});






/*
 * Functions when an event is triggered
 */

/* Get the new height of the clicked contrat */
function getNewContratHeight(accDiv) {
    if (accDiv.height() === HEIGHT_DIV_CONTRAT) {
        var nbOfLine = parseInt(accDiv.attr('nbChild'));
        return HEIGHT_DIV_CONTRAT * (nbOfLine + 2); // HEIGHT_DIV_CONTRAT * (nb services + contrat div + header of array)
    }
    return HEIGHT_DIV_CONTRAT;  // HEIGHT_DIV_CONTRAT * contrat div
}

/* When the user want to change the duration of a service */
function changeValueOfField(parent, value) {
    parent.children().each(function () {
        if ($(this).hasClass('divClikable')) {
            if ($(this).hasClass('isSelected')) {
                $(this).removeClass('isSelected');
            }
            if ($(this).text() === value) {
                $(this).addClass('isSelected');
            }
        }
    });
}

/* When the user want to create a new contrat */
function valider() {
    var services = [];
    $('#invisibleDiv').find('.isSelected').each(function () {
        services.push({
            id: $(this).parent().attr('id'),
            name: $(this).parent().attr('name'),
            value: $(this).text()
        });
    });

    var date = getDate();
    if (date !== '') {      // if date is selected
        date /= 1000;
        newContrat(services, date);
        location.reload();
    } else {             // if no date is selected
        $('#datepicker').css('border', '2px solid red');
        $('#errorDate').empty();
        $('#errorDate').append('<b>Veuillez saisir une date<b>');
    }
}

/* Get the date in the datepicker as TMS */
function getDate() {
    var date = $("#datepicker").datepicker('getDate');
    return $.datepicker.formatDate('@', date);
}


/**
 * Other functions
 */

/* display table of contrat */
function printContrats() {
    var id;
    var spaces = '&nbsp;'.repeat(5);
    for (id in contrats) {
        if (contrats[id].statut === '0') {
            addContratAndServices(contrats[id], '#containerForInactif', id, spaces);
        } else {
            addContratAndServices(contrats[id], '#containerForActif', id, spaces);
        }
    }
}

/* Add contratcs and services in the array */
function addContratAndServices(contrat, divIdToAppend, contratId, spaces) {

    $('<div></div>')
            .attr('id', contratId)
            .attr('class', 'accordeon item')
            .attr('nbChild', contrat.services.length)
            .appendTo(divIdToAppend);

    $('#' + contratId)
            .attr('id', contratId + 'item')
            .append('<div class="clickable item">' +
                    contrat.ref +
                    spaces + 'Date de début: <strong>' + contrat.dateDebutContrat + '</strong>' +
                    spaces + 'Date de fin: <strong>' + contrat.dateFinContrat + '</strong>' +
                    spaces + 'Nombre de service: <strong>' + contrat.nbService + '</strong>' +
                    spaces + 'Total facturé: <strong>' + contrat.totalFacturer + '</strong> €' +
                    spaces + 'Total payé: <strong>' + contrat.totalPayer + '</strong> €' +
                    spaces + 'Total restant: <strong>' + contrat.totalRestant + '</strong> €' +
                    spaces + 'Prix total: <strong>' + contrat.prixTotalContrat + '</strong> €' +
                    '</div>');

    if (contrat.services.length > 0) {
        initTable(contratId);

        for (var ind in contrat.services) {
            printServiceDetails(contratId, contrat.services[ind], ind);
        }
    } else {
        $('<p></p>')
                .text("Il n'y a pas encore de services associés à ce contrat")
                .css('margin', '0px 0px 20px 20px')
                .css('font-size', '14px')
                .css('font-family', 'Times New Roman')
                .appendTo('#' + contratId + 'item');
    }
}

/* Initilize the table of contrat */
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

/* Fill contrat array with its services */
function printServiceDetails(contratId, service, indService) {
    var arrayOfValue = [service.ref, service.dateDebutService, service.dateFinService, service.qty, service.prixUnitaire, service.prixTotal];

    $('<tr></tr>')
            .attr('id', contratId + 'tr' + indService)
            .appendTo('#' + contratId + 'table');

    if (service.statut === 0) {    // if the service have to be closed
        $('#' + contratId + 'tr' + indService).css("background-color", "#d34a4a");  // red
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