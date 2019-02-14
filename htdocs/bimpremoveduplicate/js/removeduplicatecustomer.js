/**
 * Ajax functions
 */

/**
 * 
 * @param {type} message message to display
 * @param {type} code 'mesgs' by default, 'warnings', 'errors'
 * @return {undefined}
 */
function displayMessage(message, code = 'mesgs') {
    if (code != 'mesgs' && code != 'warnings' && code != 'errors')
        alert("Le type de l'erreur est inconnu, la voici : " + code);
    if (message == '')
        alert("Message d'erreur vide");

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            message: message,
            code: code,
            action: 'set_message'
        },
        error: function () {
            console.log("Erreur PHP 1861");
        },
        success: function () {
            location.reload();
        }
    });
}


function getAllFactures(limit, details) {
    
    if (limit > 100) {
        alert("La requête est trop longue (100 doublons ou plus) pour être exécuter");
        return -1;
    }
    
    if (limit > 30) {
        if (!confirm("Vous êtes sur le point d'exécuter une longue requête (plus de 30 doublons), continuer ?"))
            return -2;
    }

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            limit: limit,
            details: details,
            action: 'get_all_duplicate'
        },
        beforeSend: function () {
            $('i#spinner').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
        },
        error: function () {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            alert("Erreur PHP 2654");
        },
        success: function (out) {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');

            var array_of_duplicate = JSON.parse(out);
            for (var i in array_of_duplicate) {
                var duplicates = array_of_duplicate[i];
                duplicates.forEach(function (useless, index) {
                    var duplicate = duplicates[index];
                    var last = Object.keys(duplicate).length - 1;
                    for (var counter in duplicate) {
                        var instance = duplicate[counter];
                        var key_checkbox = index;
                        if (counter == last)
                            add_line(instance, key_checkbox, 2, true);
                        else
                            add_line(instance, key_checkbox, 2, false);

                    }
                });
            }
            // Add button and his event
            $('div#id-right').append('<br/><input type="submit" class="butAction" id="remove_duplicates" value="Supprimer les doublons">');
            iniEventAfterDisplayDuplicate();
        }
    });
}

function deleteCustomers(ids_to_delete) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            action: 'delete_customers',
            ids_to_delete: ids_to_delete
        },
        beforeSend: function () {
            $('i#spinner').css('display', 'block');
            $('input#display_duplicates').css('display', 'none');
        },
        error: function () {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');
            alert("Erreur PHP 8647");
        },
        success: function (rowOut) {
            $('i#spinner').css('display', 'none');
            $('input#display_duplicates').css('display', 'block');

            var out = JSON.parse(rowOut);
            displayOutputDelete(out.nb_delete);
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {
    initEvents();

    var limit = parseInt(getUrlParameter('limit'));
    console.log(limit);
    if (limit > 0)
        getAllFactures(limit, 'true');
});

/**
 * Functions
 */
function initEvents() {
    $('input#display_duplicates').click(function () {
        var limit = parseInt($('input#limit').val());
        if (limit > 0)
            window.location.replace('removeduplicatecustomer.php?limit=' + limit);
        else
            displayMessage("Le nombre de doublon doit être supérieur à zéro.", 'errors');
    });
}

function iniEventAfterDisplayDuplicate() {
    $('input#remove_duplicates').click(function () {
        var ids_to_delete = [];
        $('input[type=checkbox][cb_delete=true]:checked').each(function () {
            ids_to_delete.push(parseInt($(this).val()));
        });
        deleteCustomers(ids_to_delete);
    });

    // Search if every checkbox are checked to prevent deleting a customer
    $('input[cb_delete=true]').change(function () {
        if ($('input[cb_delete=true][name=' + $(this).attr('name') + ']:not(:checked)').length == 0)
            alert("Attention vous avez selectionné toutes les instances de ce client,\n\
vous risquez de le supprimer totalement.");
    });
}

/**
 * 
 * @param {type} c customer
 * @param {type} key_checkbox key used as name in checkbox
 * @param {type} is_last used to split group with black tr
 * @return {undefined}
 */
function add_line(c, key_checkbox, key_radio, is_last) {

    if (is_last)
        var html = '<tr id="' + c.rowid + '" class="last">';
    else
        var html = '<tr id="' + c.rowid + '">';

    html += '<td><input r_keep="true" type="radio" name="' + key_radio + '" value=' + c.rowid + ' checked></td>'; // TODO remove "checked"
    html += '<td><input cb_delete="true" type="checkbox" name="' + key_checkbox + '" value=' + c.rowid + ' checked></td>'; // TODO remove "checked"
    html += '<td>' + c.nom + '</td>';
    html += '<td>' + c.email + '</td>';
    html += '<td>' + c.address + '</td>';
    html += '<td>' + c.zip + '</td>';
//    html += '<td>' + ((c.statut == '1') ? 'Oui' : 'Non') + '</td>';
    html += '<td>' + c.town + '</td>';
    html += '<td>' + c.phone + '</td>';
    html += '<td>' + c.link + '</td>';
    html += '<td name="errors"></td>';

    $('table#customer').append(html);
}

function displayOutputDelete(tab) {

    // Errors
    for (var i in tab[-1]) {
        var out = tab[-1][i];
        var all_errors = '';
        out.errors.forEach(function (message, index) {
            all_errors += index + ':' + message;
        });
        all_errors += (out.error != null) ? out.error : '';
        $('table#customer > tbody > tr#' + out.id).css('background-color', 'red');
        $('table#customer > tbody > tr#' + out.id + ' > td[name=errors]').text(all_errors);
    }

    // Nothing done
    for (var i in tab[0]) {
        var out = tab[0][i];
        var all_errors = '';
        out.errors.forEach(function (message, index) {
            all_errors += index + ':' + message;
        });
        all_errors += (out.error != null) ? out.error : '';
        $('table#customer > tbody > tr#' + out.id).css('background-color', 'yellow');
        $('table#customer > tbody > tr#' + out.id + ' > td[name=errors]').text(all_errors);
    }

    // Success
    for (var i in tab[1]) {
        var out = tab[1][i];
        $('table#customer > tbody > tr#' + out.id).css('background-color', 'green');
        $('table#customer > tbody > tr#' + out.id + ' > td[name=errors]').text("OK");
    }
}

/**
 * Functions Annexes
 */

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
