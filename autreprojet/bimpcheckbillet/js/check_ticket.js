var sound = 1;

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
                    no_results_text: 'Pas de résultat'
                });
                $('#barcode').on('keyup', function (e) {
                    if (e.keyCode === 13) {
                        var barcode = $('#barcode').val();
                        checkTicket(barcode, $('select[name=event] > option:selected').val());
                        $('#barcode').val("");
                    }
                });
                $('select[name=event]').change(function () {
                    $('input#cntEntry').val(0);
                    changeEventSession($('select[name=event] > option:selected').val());
                });
                if (id_event_session > 0) {
                    if (!$('select[name=event] > option[value=' + id_event_session + ']').prop('disabled')) {
                        $('select[name=event] > option[value=' + id_event_session + ']').prop('selected', true);
                        $(".chosen-select").trigger("chosen:updated");
                    }
                }
            } else if (out.events.length === 0){
                alert("Aucun évènement n'a été créée, vous allez être redirigé vers la page de création des évènements.");
                window.location.replace('../view/create_event.php');
            } else {
                setMessage('alertSubmit', "Erreur 7349.", 'error');
            }
        }
    });
}

function checkTicket(barcode, id_event) {
                        if (barcode.indexOf("?") > 0) {
                            var url = new URL(barcode);
                            barcode = url.searchParams.get('num');
                        }
                        
    $('div#alertSubmit').empty();

    if (barcode === '') {
        setMessage('alertSubmit', 'Code barre vide.', 'error');
        return;
    }
    if (id_event === '') {
        setMessage('alertSubmit', 'Sélectionnez un évènement avant de vérifier des billets.', 'error');
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
            $('#alertSubmit').empty();
            var out = JSON.parse(json);
            if (out.errors.length !== 0) {
                if (sound === 1)
                    document.querySelector("#errorSound").play();
                displayErrors(out.errors, barcode);
            } else if (out.errors === undefined) {
                setMessage('alertSubmit', 'Erreur serveur 1495.', 'error');
            } else {
                if (sound === 1)
                    document.querySelector("#beepSound").play();
                $('input#cntEntry').val(parseInt($('input#cntEntry').val()) + 1);
                displayNoErrors(barcode);
            }
        }
    });
}


$(document).ready(function () {
    getEvents();
    $('button#showHistory').click(function () {
        toggleHistory();
    });
    $('label[name=sound]').click(function () {
        var input = $(this).find('input');
        sound = parseInt(input.val());
        $(this).removeClass('focus');
    });
});

function toggleHistory() {
    if ($('div#history').attr('toggled') === 'false') {
        $('div#history').attr('toggled', 'true');
        $('div#history').css('height', '');
    } else {
        $('div#history').attr('toggled', 'false');
        $('div#history').css('height', '100px');
    }
}

function displayErrors(errors, barcode) {
    $('#imgEr').css('display', 'inline');
    $('#imgOk').css('display', 'none');
    $('fieldset').addClass('back_red_gradient');
    setTimeout(function () {
        $('fieldset').addClass('back_white_gradient');
        $('fieldset').removeClass('back_red_gradient');
    }, 800);
    $('fieldset').removeClass('back_white_gradient');
    addHistoryError(errors, barcode);
}

function displayNoErrors(barcode) {
    $('#imgEr').css('display', 'none');
    $('#imgOk').css('display', 'inline');
    $('fieldset').addClass('back_green_gradient');
    setTimeout(function () {
        $('fieldset').addClass('back_white_gradient');
        $('fieldset').removeClass('back_green_gradient');
    }, 800);
    $('fieldset').removeClass('back_white_gradient');
    addHistorychecked(barcode);
}

function addHistoryError(errors, barcode) {
    var html = '<img src="../img/error.png" style="height: 16px; width: 16px;"/> ';
    html += barcode + ' : <br/>';
    errors.forEach(function (error) {
        html += '<text style="margin-left: 40px; margin-top: 0px;">- ';
        html += error;
        html += '</text><br/>';
    });
    $('div#history').prepend(html);
}

function addHistorychecked(barcode) {
    var html = '<img src="../img/checked.png" style="height: 16px; width: 16px;"/> ';
    html += barcode + '<br/>';
    $('div#history').prepend(html);
}