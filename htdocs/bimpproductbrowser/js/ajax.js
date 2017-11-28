/* global DOL_URL_ROOT */
var objs = [];
var cnt = 0;
var cntRestr = [];
var catArr = [];


/*
 *  AJAX requests
 */

function deleteAllCateg() {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            action: 'delAll'
        },
        error: function () {
            alert("Error");
        }
    });
}

function deleteCateg(catOut) {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            id_cat_out: catOut,
            action: 'delSomeCateg'
        },
        error: function () {
            alert("Error");
        }
    });
}

function addRestr(id_categ) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_categ: id_categ,
            action: 'searchCategory'
        },
        async: false,
        error: function () {
            alert("Error");
        },
        success: function (objOut) {
            obj = JSON.parse(objOut);
            cntRestr[cnt] = 0;
            for (i = 0; i < obj.tabRestr.length; i++) {
                objs.push(obj.tabRestr[i]);
                cntRestr[cnt]++;
            }
        }
    });
}

function addCatInProd(id_categ) {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            id_categ: id_categ,
            action: 'addCategory'
        },
        error: function () {
            alert("Error");
        }
    });
}


/*
 *  When the document is loaded
 */

$(document).ready(function () {
    $('<div></div>')
            .attr('id', 'navContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');
    $('<div></div>')
            .attr('id', 'mainContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');

    deleteAllCateg();
    addRestr(0);
    addDivs();

    $(document).on("click", ".divClikable", function () {
        if ($(this).attr('id') === 'divEnd') {
            location.href = DOL_URL_ROOT + '/product/card.php?id=' + getUrlParameter('id');
        } else if ($(this).hasClass('navDiv')) {
            deleteFrom($(this).attr('id'), $(this).attr('name'));
        } else {
            catArr.push($(this).attr('id'));
            addCatInProd($(this).attr('id'));
            addRestr($(this).attr('id'));
            deleteAllDivs();
            changeNavDiv($(this).text());
            addDivs();
        }
    });
});


/*
 *  Annexe functions
 */

function deleteFrom(id_div) {

    var restrToKeep = 0;
    str = '';

    for (i = cnt; i >= id_div; i--)
        $("#navContainer").children("div").eq(i).remove();
    for (i = 0; i <= id_div; i++) {
        restrToKeep += cntRestr[i];
    }

    catOut = catArr.slice(id_div);
    objs.length = restrToKeep;
    cntRestr.length = restrToKeep;
    cnt = id_div;
    deleteAllDivs();
    catArr.length = id_div;
    deleteCateg(catOut);
    addDivs();
}

function addDivs() {
    if (cnt >= objs.length) {
        $('<div><strong><br>Merci</strong><br><br> Cliquez ici pour revenir<br>à la fiche du produit<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divEnd')
                .appendTo('#mainContainer');
    } else {
        $('<div>' + objs[cnt].label + '</div>')
                .attr('id', cnt)
                .attr('class', 'customDiv divClikable navDiv')
                .appendTo('#navContainer');
        $('<div></div><br>')
                .attr("id", objs[cnt].id)
                .attr('class', 'customDiv fixDiv')
                .text(objs[cnt].label)
                .appendTo('#mainContainer');
        for (var i = 0; i < objs[cnt].tabIdChild.length; i++) {
            $('<div>' + objs[cnt].tabNameChild[i] + '<a class="fillTheDiv" href=""></a></div>')
                    .attr("id", objs[cnt].tabIdChild[i])
                    .attr('class', 'customDiv divClikable')
                    .appendTo('#mainContainer');
        }
        ++cnt;
    }
}

function changeNavDiv(text) {
        $("#navContainer").find('#'+(cnt-1).toString()).append(':<br>'+text);
}


function deleteAllDivs() {
    $("#mainContainer").empty();
}

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