

/**
 * Ajax call
 */

function getEvents() {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_events_user'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3764.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                out.events.forEach(function (event) {
                    displayEvent(event);
                });
                if (id_event_session > 0)
                    $('div#event' + id_event_session + ' > h4 > a').click();
            } else if (out.events.length === 0) {
                setMessage('alertSubmit', "Aucun évènement n'a été créer", 'warn');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3767.', 'error');
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

/**
 * Function
 */


function displayEvent(event) {
    var status;
    if (event.status === '1')
        status = 'Brouillon';
    if (event.status === '2')
        status = 'Validé';
    if (event.status === '3')
        status = 'Terminé';
    else
        status = 'Non définit';
    var html = '<div class="panel panel-default">';
    html += '<div class="panel-heading" role="tab" id="event' + event.id + '">';
    html += '<h4 class="panel-title">';
    html += '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse' + event.id + '" aria-expanded="false" aria-controls="collapse' + event.id + '" style="display: inline ; padding:0px">';
    html += '<button class="btn btn-primary" onclick="window.location=\'../view/modify_event.php?id_event=' + event.id + '\'">Voir</button> ';
    html += event.label + ' (' + status + ')</a></h4></div>';
    html += '<div id="collapse' + event.id + '" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="event' + event.id + '">';
    if (event.tariffs.length !== 0 && Array.isArray(event.tariffs)) {
        html += '<div class="panel-body">';
        html += '<table class="table">';
        html += '<th>Libellé</th>';
        html += '<th>Prix</th>';
        html += '<th>Lien</th>';
        event.tariffs.forEach(function (tariff) {
            html += '<tr>';
            html += '<td>' + tariff.label + '</td>';
            html += '<td>' + tariff.price + ' €' + '</td>';
            html += '<td><button class="btn btn-primary" onclick="window.location=\'../view/modify_tariff.php?id_tariff=' + tariff.id + '\'">Voir</button></td>';
            html += '</tr>';
        });
        html += '</table></div>';
    } else {
        html += '<div class="alert alert-info" role="alert">';
        html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        html += '<span aria-hidden="true">&times;</span>';
        html += '</button>';
        html += '<strong>Information : </strong>Aucun tariff n\'a été créer pour cet évènement.'
        html += '</div>';
        html += '</div>';
    }

    html += '</div>';
    $('#container_event').append(html);
}
