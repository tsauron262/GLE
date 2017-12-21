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

function newContrat(contrat, dateDeb) {
    socid = getUrlParameter('socid');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpcontratauto/interface.php",
        data: {
            socid: socid,
            newContrat: contrat,
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
 * Functions when a an event is triggered
 */

/* Get the new height of the clicked contrat */
function getNewContratHeight(accDiv) {
    if (accDiv.height() === HEIGHT_DIV_CONTRAT) {
        var nbOfLine = parseInt(accDiv.attr('nbChild'));
        return HEIGHT_DIV_CONTRAT * (nbOfLine + 2);
    }
    return HEIGHT_DIV_CONTRAT;
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
    var contrat = [];
    var i = 0;
    $('#invisibleDiv').find('.isSelected').each(function () {
        var field = {};
        field.name = $(this).parent().attr('name');
        field.value = $(this).text();
        contrat[i] = field;
        i++;
    });
    
    var date = getDate();
    date/=1000;
    console.log(date);
    if (date !== -1) {
        newContrat(contrat, date);
//        location.reload();
    } else {
        $('#datepicker').css('border', '2px solid red');
        $('#errorDate').empty();
        $('#errorDate').append('Veuillez saisir une date');
    }
}

function getDate() {
    var date = $("#datepicker").datepicker('getDate');
    if ($('#datepicker').val() == '') {
        return -1;
    }
    return $.datepicker.formatDate('@', date);
}


/**
 * Other functions
 */

/* display table of contrat */
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

/* Add contratcs and services in the array */
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
                    '&nbsp;'.repeat(5) + 'Total facturé: ' + 'A faire' + ' €' +
                    '&nbsp;'.repeat(5) + 'Total payé: ' + 'A faire' + ' €' +
                    '&nbsp;'.repeat(5) + 'Total restant: ' + 'A faire' + ' €' +
                    '&nbsp;'.repeat(5) + 'Prix total: ' + contrat.prixTotalContrat + ' €' +
                    '</div>');

    initTable(contratId);

    for (var ind in contrat.services) {
        printServiceDetails(contratId, contrat.services[ind], ind);
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