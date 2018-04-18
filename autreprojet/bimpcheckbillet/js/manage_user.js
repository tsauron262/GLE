var events;

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
            setMessage('alertSubmit', 'Erreur serveur 3544.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.events.length !== 0) {
                events = out.events;
                displayCheckboxes(events);
                getUser();
            } else {
                setMessage('alertSubmit', 'Erreur serveur 3546.', 'error');
            }
        }
    });
}

function getUser() {

    $.ajax({
        url: '../interface.php',
        type: 'POST',
        data: {
            action: 'get_users'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6475.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.users.length !== 0) {
                for (var id in out.users) {
                    user = out.users[id];
                    $('select[name=user]').append(
                            '<option value=' + user.id + '>' + user.last_name + ' ' + user.first_name + '</option>');
                }
                initEvents(out.users);
                $(".chosen-select").chosen({no_results_text: 'Pas de résultat'});
            } else {
                setMessage('alertSubmit', 'Erreur serveur 6441.', 'error');
            }
        }
    });
}

function changeEventAdmin(new_status, id_event, id_user) {

    $.ajax({
        url: '../interface.php',
        type: 'POST',
        data: {
            new_status: new_status,
            id_event: id_event,
            id_user: id_user,
            action: 'change_event_admin'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6415.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                $('input[type=checkbox][value=' + id_event + ']+label').addClass('pulse');
                setTimeout(function () {
                    $('input[type=checkbox][value=' + id_event + ']+label').removeClass('pulse');
                }, 1000);
            } else {
                setMessage('alertSubmit', 'Erreur serveur 6741.', 'error');
            }
        }
    });
}

function changeLoginAndPassWord(id_user, login, pass_word) {
    $.ajax({
        url: '../interface.php',
        type: 'POST',
        data: {
            id_user: id_user,
            login: login,
            pass_word: pass_word,
            action: 'change_login_and_pass_word'
        },
        error: function () {
            setMessage('alertSubmit', 'Erreur serveur 6865.', 'error');
        },
        success: function (rowOut) {
            var out = JSON.parse(rowOut);
            if (out.errors.length !== 0) {
                printErrors(out.errors, 'alertSubmit');
            } else if (out.code_return > 0) {
                setMessage('alertSubmit', 'Login et mot de passe changé.', 'msg');
            } else {
                setMessage('alertSubmit', 'Erreur serveur 6811.', 'error');
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


function initEvents(users) {
    $('select[name=user]').change(function () {
        var id_user = $('select[name=user] > option:selected').val();
        var user = users[id_user];
        $('input.form-control').prop('disabled', (!id_user > 0));
        $('input[type=checkbox]').prop('checked', false);

        if (id_user > 0) {
            // Identification
            $('input[name=login]').val(user.login);
            $('input[name=pass_word]').val(user.pass_word);

            // Event
            if (user.status === '1') {
                $('input[type=checkbox]').prop('disabled', false);
                $('input[type=checkbox]').each(function () {
                    if (user.id_events.includes($(this).val()))
                        $(this).prop('checked', true);
                });
            } else {
                $('input[type=checkbox]').prop('disabled', true);
                $('input[type=checkbox]').prop('checked', true);
            }
        }
    });

    $('input[type=checkbox]').change(function () {
        var new_status = $(this).prop('checked');
        var id_event = $(this).val();
        var id_user = $('select[name=user] > option:selected').val();
        changeEventAdmin(new_status, id_event, id_user);
    });

    $('button[name=modify]').click(function () {
        var id_user = $('select[name=user] > option:selected').val();
        if (id_user > 0) {
            var login = $('input[name=login]').val();
            var pass_word = $('input[name=pass_word]').val();
            changeLoginAndPassWord(id_user, login, pass_word);
        } else {
            setMessage('alertSubmit', 'Sélectionnez un utilisateur avant de modifier ses login et mot de passe.', 'error');
        }
    });
}

function displayCheckboxes(events) {
    var html = '';
    events.forEach(function (event) {
        html += '<input type="checkbox" id=' + event.id + ' value=' + event.id + ' disabled>';
        html += '<label for=' + event.id + '>' + event.label + '</label>';
    });
    html += '';
    $('#container_event').append(html);
}