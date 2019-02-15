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
                        var key_group = index;
                        if (counter == last)
                            add_line(instance, key_group, true);
                        else
                            add_line(instance, key_group, false);

                    }
                });
            }
            // Add button and his event
            $('div#id-right').append('<br/><input type="submit" class="butAction" id="merge_duplicates" value="Fusionner les doublons">');
            iniEventAfterDisplayDuplicate();
        }
    });
}

function mergeDuplicates(src_to_dest) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpremoveduplicate/interface.php",
        data: {
            action: 'merge_duplicates',
            src_to_dest: src_to_dest
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
            displayOutputMerge(out);
        }
    });
}

/**
 * Ready
 */

$(document).ready(function () {
    initEvents();

    var limit = parseInt(getUrlParameter('limit'));
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
    // Merge duplicate
    $('input#merge_duplicates').click(function () {
        var radio_checked = {};
        $('input[type="radio"][r_keep="true"]:checked').each(function () {
            var group_name = $(this).attr('name');
            var radio_value = $(this).val();
            radio_checked[group_name] = radio_value;
        });

        var src_to_dest = {};
        $('table#customer > tbody > tr[key_group]').each(function () {
            var key_group = $(this).attr('key_group');
            var src = parseInt($(this).find('input[type="checkbox"][cb_merge="true"]:checked').val());
            if (src > 0) {
                var dest = radio_checked[key_group];
                src_to_dest[src] = dest;
            }
        });
        mergeDuplicates(src_to_dest);
    });


    // Prevent merge it self, part 1
    $('input[r_keep=true]').change(function () {
        if ($(this).prop('checked'))
            $(this).parent().parent().find('input[type="checkbox"][cb_merge="true"]').prop('checked', false);
    });
    // Prevent merge it self, part 2
    $('input[cb_merge=true]').change(function () {
        if ($(this).prop('checked')) {
            if ($(this).parent().parent().find('input[type="radio"][r_keep="true"]').prop('checked')) {
                alert("Action impossible, il est impossible de fusionner un tier avec lui-même.");
                $(this).prop('checked', false);
            }
        }
    });
}

/**
 * 
 * @param {type} c customer
 * @param {type} key_checkbox key used as name in checkbox
 * @param {type} is_last used to split group with black tr
 * @return {undefined}
 */
function add_line(c, key_group, is_last) {

    if (is_last)
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + ' class="last">';
    else
        var html = '<tr id="' + c.rowid + '" key_group=' + key_group + '>';

    html += '<td>' + c.rowid + '</td>';
    html += '<td><input r_keep="true" type="radio" name="' + key_group + '" value=' + c.rowid + ' checked></td>'; // TODO remove "checked"
    html += '<td><input cb_merge="true" type="checkbox" name="' + key_group + '" value=' + c.rowid + '></td>'; // TODO remove "checked"
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

function displayOutputMerge(tab) {

    var success = tab.success;
    var errors = tab.errors;

    // Success
    for (var i in success) {
        var id_success = i;
        var id_merge = success[i];
        // Add color
        $('table#customer > tbody > tr#' + id_success).css('background-color', 'rgba(0, 153, 0, 0.5)');
        // Remove input
        $('table#customer > tbody > tr#' + id_success).find('input').remove();
        $('table#customer > tbody > tr#' + id_success + ' > td[name=errors]').html('Fusionné avec <a style="color: blue;">' + id_merge+"</a>");

        $('table#customer > tbody > tr#' + id_success + ' > td[name=errors]').hover(function () {
            $(this).css('cursor', 'pointer');
            $('table#customer > tbody > tr#' + id_merge).css('background-color', 'rgb(255, 255, 153)');
        }, function () {
            $('table#customer > tbody > tr#' + id_merge).css('background-color', '');
        });
    }

    // Errors
    for (var i in errors) {
        var id_error = i;
        var error = errors[id_error];
        // Add color
        $('table#customer > tbody > tr#' + id_error).css('background-color', 'rgba(255, 0, 0, 0.5)');
        // Remove input
        $('table#customer > tbody > tr#' + id_error + ' > td[name=errors]').text(error);
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
