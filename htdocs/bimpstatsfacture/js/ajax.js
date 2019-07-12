/* global DOL_URL_ROOT */

/**
 * Global
 */

var groupes;
var taxesOrNot;
var format;
var is_customer;
var id_place;
var name_place;

/**
 * Ajax functions
 */

function getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, type, format, nomFichier, typePlace) {

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
            type: type,
            format: format,
            nomFichier: nomFichier,
            is_customer: is_customer,
            typePlace: typePlace,
            action: 'getFactures'
        },
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {

            groupes = JSON.parse(objOut);
            $('#forArray').empty();
            $('#sommaire').empty();
            $('#linkCsv').empty();

            if (format === 'c') {
                $(groupes.urlCsv).appendTo('#linkCsv');
                delete(groupes.urlCsv);
            }
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
    var firstDay = new Date(Date.UTC(y, m - 1, 1, 3, 0, 0));
    var lastDay = new Date(Date.UTC(y, m, 0, 3, 0, 0));

    $('#dateStart').datepicker();
    $('#dateStart').datepicker('setDate', firstDay);
    $('#dateEnd').datepicker();
    $('#dateEnd').datepicker('setDate', lastDay);

    $('.select2').select2();
    id_place = 'entrepot';
    name_place = 'Entrepôt';
    $('input#place_entrepot').prop('checked', 'true');

    var object = getUrlParameter('object');
    if (object === 'facture_fournisseur')
        is_customer = false;
    else
        is_customer = true;


    initSelectMultiple();
    initButtonFillAndEmpty();
    initButtonFormat();
    initButtonPlace();
    $('#go').on('click', function () {
        valider();
    });
});

function valider() {
    var dateStart = getDate('dateStart');
    var dateEnd = getDate('dateEnd');
    if (dateEnd < dateStart) {
        alert('La date de début doit être antérieur à la date de fin.');
    } else if ($("input[type='radio'][name='formatOutput']:checked").val() === 'c' && $('#nomFichier').val() === '') {
        alert('Veuillez entrer un nom de fichier de sortie valide.');
    } else {
        $('#go').hide();
        $('#waiting').addClass('loading');
        var types = $('#type').val();
        var centres = $('#' + id_place).val();
        var statut = $("input[type='radio'][name='statutPayment']:checked").val();
        var taxes = $("input[type='radio'][name='priceTaxes']:checked").val();
        var format = $("input[type='radio'][name='formatOutput']:checked").val();
        var nomFichier = $('#nomFichier').val();
        var typePlace = $("input[type='radio'][name='place']:checked").val();
        var etats = [];
        var type = [];
        var sortBy = [];
        $("input[type='checkbox'][name='sortBy']:checked").each(function () {
            sortBy.push($(this).val());
        });
        $("input[type='checkbox'][name='etat']:checked").each(function () {
            etats.push($(this).val());
        });
        $("input[type='checkbox'][name='type']:checked").each(function () {
            type.push($(this).val());
        });
        getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, type, format, nomFichier, typePlace);
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
    $('#' + id_place).change(function () {
        if ($('#' + id_place + ' option:not(:selected)').length === 0) {
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

    $('#selectAllEntrepots').click(function () {
        $('#entrepot').find('option').prop('selected', true);
        $('#entrepot').trigger('change');
    });
    $('#deselectAllEntrepots').click(function () {
        $('#entrepot').find('option').prop('selected', false);
        $('#entrepot').trigger('change');
    });
}

function initButtonFormat() {
    $("input[type='radio'][name='formatOutput']").on('click', function () {
        if ($(this).val() === 'c') {
            $('#divFichier').show();
            $('#nomFichier').val('nom_du_fichier');
            $('#nomFichier').select();
        } else {
            $('#divFichier').hide();
        }
    });
}

function initButtonPlace() {
    $("input[type='radio'][name='place']").on('click', function () {
        if ($(this).val() === 'c') { // centre
            id_place = 'centre';
            name_place = 'Centre';
            $('#tr_centre').css('display', '');
            $('#tr_entrepot').css('display', 'none');
            $('label[for=sortByCentre]').text(name_place);
        } else { // entrepot
            id_place = 'entrepot';
            name_place = 'Entrepôt';
            $('#tr_centre').css('display', 'none');
            $('#tr_entrepot').css('display', '');
            $('label[for=sortByCentre]').text(name_place);
        }
    });
}

/* Get the date in the datepicker as TMS */
function getDate(id) {
    var date = $('#' + id).datepicker('getDate');
    return $.datepicker.formatDate('@', date) / 1000;
}

function displayArray(taxesOrNot) {
    var prevFactureId;
    for (var key in groupes) {
//        if (key === 'urlCsv') 
//            next;
        initTable(taxesOrNot, groupes[key], key);
        sortie = "";
        for (var i = 0; i < groupes[key].factures.length; i++) {
            sortie += fillTable(groupes[key].factures[i], prevFactureId);
            prevFactureId = groupes[key].factures[i].fac_id;
        }
        $(sortie).appendTo('#table' + key);

        addTotaux(groupes[key], key);
    }
}

function initTable(taxesOrNot, facture, key) {
    $('<h2></h2>')
            .attr('id', 'title' + key)
            .text(facture.title)
            .appendTo('#forArray');

    $('<a href="#title' + key + '">' + facture.title + '</a><br/>').appendTo('#sommaire');

    $('<table></table>')
            .attr('id', 'table' + key)
            .appendTo('#forArray');

    $('<thead></thead>')
            .attr('id', 'thead' + key)
            .appendTo('#table' + key);

    if (is_customer)
        var arrayOfField = ['Societe', 'Facture', 'Date', 'Propal ' + taxesOrNot, 'Facture ' + taxesOrNot, 'Total marge', 'Statut', 'Paiement', 'Payé TTC', name_place, 'Type', 'Equipement', 'Type de garantie', 'Numéro de série', 'SAV', 'Num GSX'];
    else
        var arrayOfField = ['Fournisseur', 'Facture', 'Facture ' + taxesOrNot, 'Statut', 'Paiement', 'Payé TTC', name_place];

    arrayOfField.forEach(function (field) {
        $('<th></th>').text(field).appendTo('#thead' + key);
    });
}

function fillTable(facture, prevFactureId) {
    var centre = facture.centre_url ? facture.centre_url : (facture.centre ? facture.centre : '');
    if (is_customer) {
        if (prevFactureId === facture.fac_id)
            arrayOfValue = ['- - -', '- - -', '- - -', '- - -', '- - -', '- - -', '- - -', facture.ref_paiement, facture.paipaye_ttc, centre, facture.type, '- - -', '- - -', '- - -', '- - -', '- - -'];
        else
            arrayOfValue = [facture.nom_societe, facture.nom_facture, facture.fact_date, facture.prop_total, facture.factotal, facture.marge, facture.facstatut, facture.ref_paiement, facture.paipaye_ttc, centre, facture.type, facture.equip_ref, facture.type_garantie, facture.numero_serie, facture.sav_ref, facture.ggsx];
    } else {
        if (prevFactureId === facture.fac_id)
            arrayOfValue = ['- - -', '- - -', '- - -', '- - -', facture.ref_paiement, facture.paipaye_ttc, centre];
        else
            arrayOfValue = [facture.nom_societe, facture.nom_facture, facture.factotal, facture.facstatut, facture.ref_paiement, facture.paipaye_ttc, centre];
    }

    sortie = "";
    arrayOfValue.forEach(function (elt) {
        sortie += '<td>' + elt + '</td>';
    });

    return '<tr id="tr' + facture.fac_id + facture.pai_id + '">' + sortie + '</tr>';
}

function addTotaux(groupe, key) {
    if (is_customer)
        arrayOfValue = ['', '<strong>Nb facture : ' + groupe.nb_facture + '</strong>', '', '<strong>' + groupe.total_total_prop + '</strong>', '<strong>' + groupe.total_total + '</strong>', '<strong>' + groupe.total_total_marge + '</strong>', '', '', '<strong>' + groupe.total_payer + '</strong>', '', '', '', '', '', '', ''];
    else
        arrayOfValue = ['', '<strong>Nb facture : ' + groupe.nb_facture + '</strong>', '<strong>' + groupe.total_total + '</strong>', '', '', '<strong>' + groupe.total_payer + '</strong>', ''];

    $('<tr></tr>')
            .attr('id', 'tr' + key + 'end')
            .appendTo('#table' + key);
    arrayOfValue.forEach(function (elt) {
        $('<td></td>')
                .html(elt)
                .appendTo('#tr' + key + 'end');
    });
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