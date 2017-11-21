$(function () {
    searchCateg(null, 0);
});

$(document).on('change', '.drop', function () {
    searchCateg($(this).val(), $(this).attr('id'), $(this).attr('name'));
});

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

function searchCateg(id_categ, id_drop_down) {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            id_categ: id_categ,
            action: 'searchCategory'
        },
        error: function () {
            alert("Error");
        },
        success: function ($objOut) {
            obj = JSON.parse($objOut);
            deleteNextDropDown(id_drop_down);
            addNextDropDown(++id_drop_down, obj);
        }
    });
}

function deleteNextDropDown(id_drop_down) {
    $('#' + id_drop_down).nextAll('select').remove();

}

function addNextDropDown(id_drop_down, obj) {
    var select = $('<select></select>')
            .attr("id", id_drop_down)
            .attr("class", "drop")
            .attr("name", "ok")
            .appendTo('.fiche');

    $('#' + id_drop_down)
            .append($("<option></option>")
                    .attr("value", 0)
                    .text("Choisir catégorie"));

    for (i = 0; i < obj.tabIdChild.length; i++) {
        $('#' + id_drop_down)
                .append($("<option></option>")
                        .attr("value", obj.tabIdChild[i])
                        .attr("name", i + 1)
                        .text(obj.tabNameChild[i]));

    }
}