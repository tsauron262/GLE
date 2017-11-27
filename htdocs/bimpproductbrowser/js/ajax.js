/* global DOL_URL_ROOT */
var objs = [];
var cnt = 0;
var cntRestr = [];
var catArr = [];

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

$(document).ready(function () {

    $('<div id="navContainer" class="customBody"></div>').appendTo('.fiche');
    $('<div class="underbanner clearboth"><div>').appendTo('.fiche');
    $('<div id="mainContainer" class="customBody"></div>').appendTo('.fiche');

    deleteAllCateg();
    addRestr(0);
    addDivs();
}
);
$(document).ready(function () {
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
            addDivs();
        }
    });
});

function deleteFrom(id_div, name) {
    
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
    catArr.length=id_div;
    deleteCateg(catOut);
    addDivs();
}

function addDivs() {
    if (cnt >= objs.length) {
        $('<div  class="customDiv divClikable"><strong><br>Merci</strong><br><br> Cliquez ici pour revenir<br>à la fiche du produit<a class="fillTheDiv" href=""></a></div>').
                attr("id", 'divEnd').
                appendTo('#mainContainer');
    } else {
        $('<div  class="customDiv divClikable navDiv">' + objs[cnt].label + '</div>').
                attr("id", cnt).
                attr("name", objs[cnt].idParent).
                appendTo('#navContainer');
        $('<div  class="customDiv fixDiv">' + objs[cnt].label + '</div><br>').
                attr("id", objs[cnt].id).
                appendTo('#mainContainer');
        for (var i = 0; i < objs[cnt].tabIdChild.length; i++) {
            $('<div  class="customDiv divClikable">' + objs[cnt].tabNameChild[i] + '<a class="fillTheDiv" href=""></a></div>').
                    attr("id", objs[cnt].tabIdChild[i]).
                    appendTo('#mainContainer');
        }
        ++cnt;
    }
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