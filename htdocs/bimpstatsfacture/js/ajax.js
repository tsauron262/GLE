/**
 * Global
 */

var factures;
var taxesOrNot;
var sortType = false;
var sortCenter = false;

/**
 * Ajax functions
 */

function getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpstatsfacture/interface.php",
        data: {
            dateStart: dateStart,
            dateEnd: dateEnd,
            types: types,
            centres: centres,
            statut: statut,
            sortBy: sortBy,
            taxes: taxes,
            action: 'getFactures'
        },
        async: false,
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {
            factures = JSON.parse(objOut);
        }
    });
}

$(document).ready(function () {

    var date = new Date(), y = date.getFullYear(), m = date.getMonth();
    var firstDay = new Date(Date.UTC(y, m - 1, 1, 3, 0, 0));
    var lastDay = new Date(Date.UTC(y, m + 2, 0, 3, 0, 0));
//    var lastDay = new Date(Date.UTC(y, m, 0, 3, 0, 0));   // TODO remettre celui-ci

    $('#dateStart').datepicker();
    $('#dateStart').datepicker('setDate', firstDay);
    $('#dateEnd').datepicker();
    $('#dateEnd').datepicker('setDate', lastDay);

    $('.select2').select2();

    initSelectMultiple();
    initButtonFillAndEmpty();

    $('#go').on('click', function () {
        valider();
    });
});

function valider() {
    dateStart = getDate('dateStart');
    dateEnd = getDate('dateEnd');
    if (dateEnd < dateStart) {
        alert('La date de début doit être antérieur à la date de fin.');
    } else {
        var types = $('#type').val();
        var centres = $('#centre').val();
        var statut = $("input[type='radio'][name='statutPayment']:checked").val();
        var taxes = $("input[type='radio'][name='priceTaxes']:checked").val();
        var sortBy = [];
        $("input[type='checkbox'][name='sortBy']:checked").each(function () {
            sortBy.push($(this).val());
            if ($(this).val() === 'c')
                sortCenter = true;
            else
                sortCenter = false;
            if ($(this).val() === 't')
                sortType = true;
            else
                sortType = false;
        });
        getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes);
        $('#forArray').empty();
        if (taxes === 'ttc')
            taxesOrNot = 'Total TTC';
        else
            taxesOrNot = 'Total HT';
        displayArray(taxesOrNot);
    }
}

function initSelectMultiple() {
    $('#type').change(function () {
        if ($('#type option:not(:selected)').length === 0) {
            if ($('#allType').prop('checked') === false) {
                $('#allType').prop('checked', true);
            }
        }
    });
    $('#centre').change(function () {
        if ($('#centre option:not(:selected)').length === 0) {
            if ($('#allCentre').prop('checked') === false) {
                $('#allCentre').prop('checked', true);
            }
        }
    });
}


function initButtonFillAndEmpty() {
    $('#selectAllTypes').click(function () {
        $('#type').find('option').prop('selected', true);
        $('#type').trigger('change');
    });
    $('#deselectAllTypes').click(function () {
        $('#type').find('option').prop('selected', false);
        $('#type').trigger('change');
    });

    $('#selectAllCentres').click(function () {
        $('#centre').find('option').prop('selected', true);
        $('#centre').trigger('change');
    });
    $('#deselectAllCentres').click(function () {
        $('#centre').find('option').prop('selected', false);
        $('#centre').trigger('change');
    });
}

/* Get the date in the datepicker as TMS */
function getDate(id) {
    var date = $('#' + id).datepicker('getDate');
    return $.datepicker.formatDate('@', date) / 1000;
}

function displayArray(taxesOrNot) {
    var prevFacture;
    for (var key in factures) {
        initTable(taxesOrNot, factures[key], key);
        for (var i = 0; i < factures[key].factures.length; i++) {
            fillTable(factures[key].factures[i], key, prevFacture);
            prevFacture = factures[key].factures[i].fac_id;
        }
    }
}

function initTable(taxesOrNot, facture, key) {
    $('<h2></h2>')
            .text(facture.title)
            .appendTo('#forArray');

    $('<table></table>')
            .attr('id', 'table' + key)
            .appendTo('#forArray');

    $('<thead></thead>')
            .attr('id', 'thead' + key)
            .appendTo('#table' + key);

    var arrayOfField = ['Societe', 'Facture', taxesOrNot, 'Total marge', 'Statut', 'Paiement', 'Payé TTC'];

    arrayOfField.forEach(function (field) {
        $('<th></th>').text(field).appendTo('#thead' + key);
    });
}

function fillTable(facture, key, prevFacture) {

    if (prevFacture === facture.fac_id)
        arrayOfValue = ['- - -', '- - -', '- - -', '- - -', '- - -', facture.paiurl, facture.paipaye_ttc];
    else
        arrayOfValue = [facture.socurl, facture.facurl, facture.factotal, facture.marge, facture.facstatut, facture.paiurl, facture.paipaye_ttc];

    $('<tr></tr>')
            .attr('id', 'tr' + facture.fac_id)
            .appendTo('#table' + key);
    arrayOfValue.forEach(function (elt) {
        $('<td></td>')
                .html(elt)
                .appendTo('#tr' + facture.fac_id);
    });
}


































function sortFactures() {
    if (sortType && sortCenter) {
        factures.sort(function (a, b) {
            if (a.type < b.type)
                return -1;
            if (a.type > b.type)
                return 1;

            if (a.centre < b.centre)
                return -1;
            if (a.centre > b.centre)
                return 1;

            return 0;
        });
    } else if (sortType) {
        factures.sort(function (a, b) {
            if (a.type < b.type)
                return -1;
            if (a.type > b.type)
                return 1;

            return 0;
        });
    } else {
        factures.sort(function (a, b) {
            if (a.centre < b.centre)
                return -1;
            if (a.centre > b.centre)
                return 1;

            return 0;
        });
    }
}



function objToArray() {
    var tmp = [];
    for (var key in factures) {
        tmp.push(factures[key]);
    }
    factures = tmp;
}