//var decoder;

function getEvents() {

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            action: 'get_events'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 3564.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                out.events.forEach(function (event) {
                    if (event.status === '1')
                        $('select[name=event]').append(
                                '<option value=' + event.id + ' disabled>' + event.label + ' (Brouillon)</option>');
                    else if (event.status === '2')
                        $('select[name=event]').append(
                                '<option value=' + event.id + '>' + event.label + '</option>');
                    else if (event.status === '3')
                        $('select[name=event]').append(
                                '<option value=' + event.id + ' disabled>' + event.label + ' (Terminé)</option>');
                });
                $(".chosen-select").chosen({
                    placeholder_text_single: 'Evènement',
                    no_results_text: 'Pas de résultat'});
                $('#barcode').on('keyup', function (e) {
                    if (e.keyCode === 13) {
                        checkTicket($('#barcode').val(), $('select[name=event] > option:selected').val());
                        $('#barcode').val("");
                    }
                });
                $('select[name=event]').change(function () {
                    changeEventSession($('select[name=event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                    }
                }
            } else {
                setMessage('alertSubmit', "Créer un évènement avant de définir un tarif.", 'error');
            }
        }
    });
}

function checkTicket(barcode, id_event) {

    if (barcode === '') {
        setMessage('alertSubmit', 'Code barre vide.', 'error');
        return;
    }
    if (id_event === '') {
        setMessage('alertSubmit', 'Sélectionnez un évènement avant de vérifier des billet.', 'error');
        return;
    }

    $.ajax({
        type: "POST",
        url: "../interface.php",
        data: {
            barcode: barcode,
            id_event: id_event,
            action: 'check_ticket'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 1895.', 'error');
        },
        success: function (json) {
            decoder.stop();
            var out = JSON.parse(json);
            if (out.errors.length !== 0) {
                $('#alertSubmit').empty();
                printErrors(out.errors, 'alertSubmit');
            } else if (out.errors === undefined) {
                setMessage('alertSubmit', 'Erreur serveur 1495.', 'error');
            } else {
                $('fieldset').addClass('back_green_gradient');
                setTimeout(function () {
                    $('fieldset').addClass('back_white_gradient');
                    setTimeout(function () {
                        $('fieldset').removeClass('back_green_gradient');
                    }, 1000);
                }, 1000);
            }
        }
    });
}


$(document).ready(function () {
    getEvents();

//    $("#result").click(function () {
//        $(this).hide();
//        decoder.play();
//    });
});
