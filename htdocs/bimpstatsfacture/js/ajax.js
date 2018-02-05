/* global DOL_URL_ROOT */

/**
 * Global
 */

var groupes;
var taxesOrNot;
var format;

/**
 * Ajax functions
 */

function getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, format, nomFichier) {

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
            nomFichier: nomFichier,
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
    initButtonFormat();
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
        var centres = $('#centre').val();
        var statut = $("input[type='radio'][name='statutPayment']:checked").val();
        var taxes = $("input[type='radio'][name='priceTaxes']:checked").val();
        var format = $("input[type='radio'][name='formatOutput']:checked").val();
        var nomFichier = $('#nomFichier').val();
        var etats = [];
        var sortBy = [];
        $("input[type='checkbox'][name='sortBy']:checked").each(function () {
            sortBy.push($(this).val());
        });
        $("input[type='checkbox'][name='etat']:checked").each(function () {
            etats.push($(this).val());
        });
        getAllFactures(dateStart, dateEnd, types, centres, statut, sortBy, taxes, etats, format, nomFichier);
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

    var arrayOfField = ['Societe', 'Facture', taxesOrNot, 'Total marge', 'Statut', 'Paiement', 'Payé TTC', 'Centre', 'Type', 'Equipement', 'Type de garantie', 'Numéro de série', 'SAV'];

    arrayOfField.forEach(function (field) {
        $('<th></th>').text(field).appendTo('#thead' + key);
    });
}

function fillTable(facture, prevFactureId) {

    if (prevFactureId === facture.fac_id)
        arrayOfValue = ['- - -', '- - -', '- - -', '- - -', '- - -', facture.ref_paiement, facture.paipaye_ttc, facture.centre, facture.type, '- - -', '- - -', '- - -', '- - -'];
    else
        arrayOfValue = [facture.nom_societe, facture.nom_facture, facture.factotal, facture.marge, facture.facstatut, facture.ref_paiement, facture.paipaye_ttc, facture.centre, facture.type, facture.equip_ref, facture.type_garantie, facture.numero_serie, facture.sav_ref];
       
    sortie = "";
    arrayOfValue.forEach(function (elt) {
        sortie += '<td>'+ elt +'</td>';
    });
    
     return '<tr id="tr'+ facture.fac_id + facture.pai_id+'">'+sortie+'</tr>';
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