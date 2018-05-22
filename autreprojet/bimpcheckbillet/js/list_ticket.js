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
                        $('select[name=event]').trigger('change');
                    }
                }
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3716.', 'error');
            }
        }
    });
}

function getTicketList(id_event) {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            id_event: id_event,
            action: 'get_ticket_list'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3547.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.tariffs !== undefined) {
                displayTable(out.tariffs);
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
    getEvents();
});

function initEvents() {
    $('select[name=event]').change(function () {
        var id_event = $('select[name=event] > option:selected').val();
        if (id_event > 0)
            getTicketList(id_event);
        else
            $('div#displayTable').empty();
    });
}


function displayTable(tariffs) {
    $('div#displayTable').empty();
    var html = '';
    tariffs.forEach(function (tariff) {
        html += '<h5><strong>' + tariff.label + '</strong></h5>';
        html += '<table>';
        html += addHeader(tariff);
        html += addTickets(tariff);
        html += '</table><br/><br/>';
    });
    $('div#displayTable').append(html);
}

function addHeader(tariff) {
    var html = '<tr>';
    html += '<th>Nom</th>';
    html += '<th>Prénom</th>';
    html += '<th>Prix</th>';

    if (tariff.type_extra_1 !== 0 && tariff.name_extra_1 !== null)
        html += '<th>' + tariff.name_extra_1 + '</th>';
    if (tariff.type_extra_2 !== 0 && tariff.name_extra_2 !== null)
        html += '<th>' + tariff.name_extra_2 + '</th>';
    if (tariff.type_extra_3 !== 0 && tariff.name_extra_3 !== null)
        html += '<th>' + tariff.name_extra_3 + '</th>';
    if (tariff.type_extra_4 !== 0 && tariff.name_extra_4 !== null)
        html += '<th>' + tariff.name_extra_4 + '</th>';
    if (tariff.type_extra_5 !== 0 && tariff.name_extra_5 !== null)
        html += '<th>' + tariff.name_extra_5 + '</th>';
    if (tariff.type_extra_6 !== 0 && tariff.name_extra_6 !== null)
        html += '<th>' + tariff.name_extra_6 + '</th>';
    html += '<th>Code barre</th>';
    html += '</tr>';
    return html;
}

function addTickets(tariff) {
    var html = '';
    var tickets = tariff.tickets;

    if (tickets === undefined)
        return html;

    tickets.forEach(function (ticket) {
        html += '<tr>';
        html += '<td>' + ((ticket.last_name !== null) ? ticket.last_name : '') + '</td>';
        html += '<td>' + ((ticket.first_name !== null) ? ticket.first_name : '') + '</td>';
        html += '<td>' + ((ticket.price !== null) ? (ticket.price + ' €') : '') + '</td>';
        if (tariff.type_extra_1 !== 0 && tariff.name_extra_1 !== null)
            html += '<td>' + ((ticket.extra_1 !== null) ? ticket.extra_1 : '') + '</td>';
        if (tariff.type_extra_2 !== 0 && tariff.name_extra_2 !== null)
            html += '<td>' + ((ticket.extra_2 !== null) ? ticket.extra_2 : '') + '</td>';
        if (tariff.type_extra_3 !== 0 && tariff.name_extra_3 !== null)
            html += '<td>' + ((ticket.extra_3 !== null) ? ticket.extra_3 : '') + '</td>';
        if (tariff.type_extra_4 !== 0 && tariff.name_extra_4 !== null)
            html += '<td>' + ((ticket.extra_4 !== null) ? ticket.extra_4 : '') + '</td>';
        if (tariff.type_extra_5 !== 0 && tariff.name_extra_5 !== null)
            html += '<td>' + ((ticket.extra_5 !== null) ? ticket.extra_5 : '') + '</td>';
        if (tariff.type_extra_6 !== 0 && tariff.name_extra_6 !== null)
            html += '<td>' + ((ticket.extra_6 !== null) ? ticket.extra_6 : '') + '</td>';
        html += '<td>' + ticket.barcode + '</td>';
        html += '</tr>';
    });
    return html;
}
