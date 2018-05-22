function changeEventSession(id_event) {

    if (parseInt(id_event) > 0) {
        $.ajax({
            type: "POST",
            url: "../interface.php",
            data: {
                id_event: id_event,
                action: 'change_event_session'
            },
            error: function () {
                alert('Erreur lors de la définition de l\'évènement en cours');
            },
            success: function (rowOut) {
                var out = JSON.parse(rowOut);
                if (parseInt(out.code_return) < 0)
                    alert('Erreur lors de la définition de l\'évènement en cours');
            }
        });
    }
}

/**
 * 
 * @param {String} idElement id of the element to append the message in
 * @param {String} message the message you want to displ  ay
 * @param {String} type 'msg' => normal message (green) 'warn' +> warning (yellow) else => error message (red)
 */
function setMessage(idElement, message, type) {
    var is_error = false;
    var backgroundColor;
    if (type === 'msg')
        backgroundColor = '#25891c ';
    else if (type === 'warn')
        backgroundColor = '#FFFF96 ';
    else {
        backgroundColor = '#ff887a ';
        is_error = true;
    }

    var id_alert = 'alert' + Math.floor(Math.random() * 10000) + 1;
    $('#' + idElement).append('<div id="' + id_alert + '" style="background-color: ' + backgroundColor + ' ; opacity: 0.9 ; display: inline ; float: left; margin: 5px ; border-radius: 8px; padding: 10px; color:black">' + message + ' <span id="cross' + id_alert + '" style="position:relative; top:-6px; right:-5px ; cursor: pointer;">&#10005;</span></div>');
    $('#cross' + id_alert).click(function () {
        $(this).parent().fadeOut(500);
    });

    setTimeout(function () {
        $("#" + id_alert + "").fadeOut(1000);
        setTimeout(function () {
            $("#" + id_alert + "").remove();
        }, 1000);
    }, (is_error) ? 3600000 : 10000);
}

/**
 * Print and array of string in
 * @param {Array} errors
 * @param {String} idAlertPlaceHolder the id of the element where you want to
 *  set the message in.
 * @dependent setMessage()
 */
function printErrors(errors, idAlertPlaceHolder) {
    if (Array.isArray(errors)) {
        for (var i = 0; i < errors.length && i < 100; i++) {
            setMessage(idAlertPlaceHolder, errors[i], 'error');
        }
    } else {
        setMessage(idAlertPlaceHolder, errors, 'error');
    }
}

/* Get the parameter sParam */
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};
