
var id_warehouse;


$(document).ready(function () {
    id_warehouse = getUrlParameter('id_warehouse');
    $('#warehouse_select').select2({placeholder: 'Rechercher ...'});
    $('#warehouse_select option[value=' + id_warehouse + ']').prop('selected', true);
    $('#warehouse_select').trigger('change');
    initEvents();

});

function initEvents() {
    $('#warehouse_select').change(function () {
        var tab = window.location.hash.substr(1); // get tab (anchor)
        var url = window.location.href;
        if (id_warehouse > 0) {
            id_warehouse = $('#warehouse_select option:selected').val();
            var new_url = replaceUrlParam(url, 'id_warehouse', id_warehouse);
        } else {
            id_warehouse = $('#warehouse_select option:selected').val();
            var new_url = window.location.href.split("#")[0];
            new_url += '&id_warehouse=' + id_warehouse;
        }
        new_url += '#' + tab;
        window.history.pushState('Object', 'Accueil Boutique', new_url);
        window.location.reload();
    });
}



/*
 * Annexes functions
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
}


function replaceUrlParam(url, paramName, paramValue) {
    if (paramValue === null) {
        paramValue = '';
    }
    var pattern = new RegExp('\\b(' + paramName + '=).*?(&|$)');
    if (url.search(pattern) >= 0) {
        return url.replace(pattern, '$1' + paramValue + '$2');
    }
    url = url.replace(/\?$/, '');
    return url + (url.indexOf('?') > 0 ? '&' : '?') + paramName + '=' + paramValue;
}