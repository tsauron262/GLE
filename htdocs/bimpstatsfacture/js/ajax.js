/* global DOL_URL_ROOT */

/**
 * Global
 */

var groupes;
var taxesOrNot;

/**
 * Ajax functions
 */

function getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, format) {

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
            etats: etats,
            format: format,
            action: 'getFactures'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {
            groupes = JSON.parse(objOut);
            $('#forArray').empty();
            if (taxes === 'ttc')
                taxesOrNot = 'Total TTC';
            else
                taxesOrNot = 'Total HT';
            displayArray(taxesOrNot);
            $('#go').show();
            $('#waiting').removeClass('loading');
        }
    });
}

$(document).ready(function () {

    var date = new Date(), y = date.getFullYear(), m = date.getMonth();
    var firstDay = new Date(Date.UTC(y, m, 20, 3, 0, 0));
    var lastDay = new Date(Date.UTC(y, m + 1, 0, 3, 0, 0));
//    var firstDay = new Date(Date.UTC(y, m - 1, 1, 3, 0, 0));
//    var lastDay = new Date(Date.UTC(y, m, 0, 3, 0, 0));   // good

    $('#dateStart').datepicker();
    $('#dateStart').datepicker('setDate', firstDay);
    $('#dateEnd').datepicker();
    $('#dateEnd').datepicker('setDate', lastDay);

    $('.select2').select2();

    initSelectMultiple();
    initButtonFillAndEmpty();

    $('#go').on('click', function () {
        $(this).hide();
        valider();
    });
});

function valider() {
    dateStart = getDate('dateStart');
    dateEnd = getDate('dateEnd');
    if (dateEnd < dateStart) {
        alert('La date de début doit être antérieur à la date de fin.');
    } else {
        $('#waiting').addClass('loading');
        var types = $('#type').val();
        var centres = $('#centre').val();
        var statut = $("input[type='radio'][name='statutPayment']:checked").val();
        var taxes = $("input[type='radio'][name='priceTaxes']:checked").val();
        var format = $("input[type='radio'][name='formatOutput']:checked").val();
        var etats = [];
        var sortBy = [];
        $("input[type='checkbox'][name='sortBy']:checked").each(function () {
            sortBy.push($(this).val());
        });
        $("input[type='checkbox'][name='etat']:checked").each(function () {
            etats.push($(this).val());
        });
        getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, format);
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
    var prevFactureId;
    ligne = 0;  // dev TODO remove
    for (var key in groupes) {
        initTable(taxesOrNot, groupes[key], key);
        for (var i = 0; i < groupes[key].factures.length; i++) {
            fillTable(groupes[key].factures[i], key, prevFactureId);
            prevFactureId = groupes[key].factures[i].fac_id;
            ligne++;
        }
        addTotaux(groupes[key], key);
    }
//    console.log("Nombre de ligne total = " + ligne);
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

    var arrayOfField = ['Societe', 'Facture', taxesOrNot, 'Total marge', 'Statut', 'Paiement', 'Payé TTC', 'Centre', 'Type', 'Equipement', 'Numéro de série', 'Type de garantie', 'id SAV'];

    arrayOfField.forEach(function (field) {
        $('<th></th>').text(field).appendTo('#thead' + key);
    });
}

function fillTable(facture, key, prevFactureId) {

    if (prevFactureId === facture.fac_id)
        arrayOfValue = ['- - -', '- - -', '- - -', '- - -', '- - -', facture.paiurl, facture.paipaye_ttc, facture.centre, facture.type];
    else
        arrayOfValue = [facture.nom_societe, facture.nom_facture, facture.factotal, facture.marge, facture.facstatut, facture.ref_paiement, facture.paipaye_ttc, facture.centre, facture.type, facture.equip_ref, facture.numero_serie, facture.type_garantie, facture.sav_id];

    $('<tr></tr>')
            .attr('id', 'tr' + facture.fac_id + facture.pai_id)
            .appendTo('#table' + key);
    arrayOfValue.forEach(function (elt) {
        $('<td></td>')
                .html(elt)
                .appendTo('#tr' + facture.fac_id + facture.pai_id);
    });
}

function addTotaux(groupe, key) {
    arrayOfValue = ['', '<strong>Nb facture : ' + groupe.nb_facture + '</strong>', '<strong>' + groupe.total_total + '</strong>', '<strong>' + groupe.total_total_marge + '</strong>', '', '', '<strong>' + groupe.total_payer + '</strong>', '', '', '', '', '', ''];

    $('<tr></tr>')
            .attr('id', 'tr' + key + 'end')
            .appendTo('#table' + key);
    arrayOfValue.forEach(function (elt) {
        $('<td></td>')
                .html(elt)
                .appendTo('#tr' + key + 'end');
    });
}