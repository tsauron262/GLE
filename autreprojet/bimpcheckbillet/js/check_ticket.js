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
                    $('select[name=event]').append(
                            '<option value=' + event.id + '>' + event.label + '</option>');
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
                printErrors(out.errors, 'alertSubmit');
            } else if (out.errors === undefined) {
                setMessage('alertSubmit', 'Erreur serveur 1495.', 'error');
            } else {
                setMessage('alertSubmit', "Ticket OK", 'msg');
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
