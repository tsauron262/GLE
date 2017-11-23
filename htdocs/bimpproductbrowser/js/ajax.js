/* global DOL_URL_ROOT */

var arrIdCell = []; // the working id, each row correspond to a line
place_for_arrow = "\u00A0\u00A0\u00A0\u00A0\u00A0\u00A0\u00A0\u00A0";

$(function () {
    arrIdCell[0] = 0;
    $('<table></table>')
            .attr("id", 'invisibleTable')
            .attr("class", "arr")
            .appendTo('.fiche');
    $('<tr></tr>')
            .attr("id", 'a')
            .attr("class", "line")
            .appendTo('table.arr');
    searchCateg(0, 0, 0);
});

$(document).on('change', '.drop', function () {
    if ($(this).val() !== '0')
        searchCateg($(this).val(), $(this).attr('id'));
    else
        deleteNextDropDown($(this).attr('id'));

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
            id_drop_down += 1;
            setArrIdCell(id_drop_down);
            if (obj.tabRestr.length === 0)
                addEnd(id_drop_down);
            else {
                for (i = 0; i < obj.tabRestr.length; i++)
                    addNextDropDown(id_drop_down, i, obj);

            }

        }
    });
}

function setArrIdCell(id) {
    line = 0;
    for (i = 0; i < 10000; i += 100, line++) {
        if (i <= id && id <= i + 100)
            arrIdCell[line] = id;
    }
}

function deleteNextDropDown(id_drop_down) {
    $('#' + id_drop_down).nextAll('select').remove();

}

function addNextDropDown(id_drop_down, id_restr, obj) {
    alert(id_drop_down+"\n"+id_restr+"\n"+obj);
    $('<td></td>')
            .attr("id", 999)
            .attr("class", "cell")
            .text("WOOOOOOOOOOOOOOOOOOOOO")
            .appendTo('tr.line' + id_drop_down);



/*    $('<select></select>')
            .attr("id", id_drop_down)
            .attr("class", "drop")
            .appendTo('td#' + id_drop_down);

    $('select#' + id_drop_down)
            .append($("<option></option>")
                    .attr("disabled", "disabled")
                    .text(obj.tabRestr[id_restr].label + place_for_arrow));

    for (i = 0; i < obj.tabRestr[id_restr].tabIdChild.length; i++) {
        $(select'#' + id_drop_down)
                .append($("<option></option>")
                        .attr("value", obj.tabRestr[id_restr].tabIdChild[i])
                        .text(obj.tabRestr[id_restr].tabNameChild[i] + place_for_arrow));
    }*/
}

function addEnd(id_drop_down) {
    var select = $('<select></select>')
            .attr("id", id_drop_down)
            .attr("class", "unselectable")
            .appendTo('.fiche');

    $('#' + id_drop_down)
            .append($("<option></option>")
                    .attr("value", 0)
                    .text("FIN"));

//    var end = $('<button></button>')
//            .attr("type", "button")
//            .attr("class", "btn-primary")
//            .text("FIN")
//            .appendTo('.fiche');

}