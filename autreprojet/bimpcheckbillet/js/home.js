

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

function initEvents() {

}

function displayEvent(event) {
    var html = '<div class="panel panel-default">';
    html += '<div class="panel-heading" role="tab" id="event' + event.id + '">';
    html += '<h4 class="panel-title">';
    html += '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse' + event.id + '" aria-expanded="true" aria-controls="collapse' + event.id + '">'
    html += event.label + '</a></h4></div>'
    html += '<div id="collapse' + event.id + '" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="event' + event.id + '">';
    if (event.tariffs.length !== 0 && Array.isArray(event.tariffs)) {
        html += '<div class="panel-body">';
        html += '<table class="table">';
        html += '<th>Libellé</th>';
        html += '<th>Prix</th>';
        event.tariffs.forEach(function (tariff) {
            html += '<tr>';
            html += '<td>' + tariff.label + '</td>';
            html += '<td>' + tariff.price + ' €' + '</td>';
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
//    console.log(html+"\n\n\n\n");
    $('#container_event').append(html);
}
