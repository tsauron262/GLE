

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
                initEvents(events);
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
            action: 'get_stats'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3547.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.event.length !== undefined) {
                alert('retour');
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
}
);
function initEvents(events) {
    $('select[name=event]').change(function () {
        var id_event = $('select[name=event] > option:selected').val();
        if (id_event > 0)
            getStats(id_event);
    });
}