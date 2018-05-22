var google;


/**
 * Ajax call
 */

function getEvents() {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_events'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3554.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                out.events.forEach(function (event) {
                    $('select[name=event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
                });
                $(".chosen-select").chosen({no_results_text: 'Pas de résultat'});
                initEvents();
                $('select[name=event]').change(function () {
                    changeEventSession($('select[name=event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                        setTimeout(function () {
                            $('select[name=event]').trigger('change');
                        }, 500);
                    }
                }
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3716.', 'error');
            }
        }
    });
}

function getStats(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'get_stats'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3547.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.tab !== undefined) {
                displayStats(out.tab);
            } else {
                setMessage('alertSubmit', 'Erreur serveur 7255.', 'error');

            }
        }
    });
}



/**
 * Ready
 */

$(document).ready(function () {
    google.charts.load('current', {'packages': ['corechart']});
    getEvents();

});

function initEvents() {
    $('select[name=event]').change(function () {
        var id_event = $('select[name=event] > option:selected').val();
        if (id_event > 0)
            getStats(id_event);
        else
            $('#container_event').empty();
    });
}

function displayStats(tab) {
    var tariff;

    var dataForGraphTariff = getTariffArray(tab.tariffs);
    var dataForGraphTariffPrice = getTariffPriceArray(tab.tariffs, tab.tickets);
    var dataForGraphTicket = getTicketArray(tab.tickets);

    $('#container_event').empty();
    var html = '<p>';
    html += 'Label : <strong>' + tab.event.label + '</strong><br/>';
    html += 'Date de création : <strong>' + tab.event.date_creation + '</strong></br>';
    html += 'Date de début : <strong>' + tab.event.date_start + '</strong></br>';
    html += 'Date de fin : <strong>' + tab.event.date_end + '</strong></br>';
    html += 'Total vendu : <strong>' + tab.event.price_total + ' €</strong></br>';
    html += '</p>';
    html += '<label>Tarifs </label>';
    html += '<div id="char_tariff"></div>';
    html += '<div id="char_tariff_price"></div>';
    html += '<label>Tickets </label><br/>';
    html += 'Nombre de ticket vendu : <strong>' + (parseInt(dataForGraphTicket[1][1]) + parseInt(dataForGraphTicket[2][1])) + '</strong></br>';
    html += '<div id="char_ticket"></div>';
    $('#container_event').append(html);

    google.charts.setOnLoadCallback(drawChart(dataForGraphTariff, 'char_tariff', 'Répartition des tarifs dans les ventes'));
    google.charts.setOnLoadCallback(drawChart(dataForGraphTariffPrice, 'char_tariff_price', 'Part des tarifs dans les ventes totale'));
    google.charts.setOnLoadCallback(drawChart(dataForGraphTicket, 'char_ticket', 'Validation des tickets'));
}

function drawChart(data_in, id_div, title) {
    var data = google.visualization.arrayToDataTable(data_in);
    var options = {
        'title': title,
        'width': 500,
        'height': 320
    };
    var chart = new google.visualization.PieChart(document.getElementById(id_div));
    chart.draw(data, options);
}

function getTariffArray(tariffs) {
    var out = [];
    var couple;
    var tariff;
    var title;
    out.push(['Label', 'Nombre de vente']);

    for (var id in tariffs) {
        tariff = tariffs[id];
        couple = [];
        title = tariff.label + ' nombre de ticket vendu: ' + tariff.sold;
        couple.push(title);
        couple.push(tariff.sold);
        out.push(couple);
    }
    return out;
}

function getTariffPriceArray(tariffs, tickets) {
    var out = [];
    var couple;
    var ticket;
    var tariff;
    var title;

    out.push(['Label', 'Part des ventes']);

    for (var id in tickets) {
        ticket = tickets[id];
        if (tariffs[ticket.id_tariff].total === undefined)
            tariffs[ticket.id_tariff].total = 0;
        tariffs[ticket.id_tariff].total += ticket.price;

    }
    for (var id in tariffs) {
        tariff = tariffs[id];
        couple = [];
        title = tariff.label + ' total tarif: ' + tariff.total + ' €'
        couple.push(title);
        couple.push(tariff.total);
        out.push(couple);
    }
    return out;
}

function getTicketArray(tickets) {
    var out = [];
    var scanned = 0;
    var not_scanned = 0;
    out.push(['Statut', 'Nombre']);

    for (var id in tickets) {
        if (tickets[id].date_scan === null)
            not_scanned++;
        else
            scanned++;
    }
    var title = 'Validé: ' + scanned;
    out.push([title, scanned]);
    var title = 'Attendu: ' + not_scanned;
    out.push([title, not_scanned]);

    return out;
}