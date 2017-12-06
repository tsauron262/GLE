/* global DOL_URL_ROOT */
var objs = [];  /*
 [
 obj.idParent
 obj.label
 obj.tabIdChild = []
 obj.tabNameChild = []
 ]
 */

var cnt = 0;
var cntRestr = [];
var catArr = [];

var objInit;   // existing categories before the execution

/*
 *  AJAX requests
 */

function getAllCateg() {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            action: 'getAllCategories'
        },
        async: false,
        error: function () {
            alert("Error");
        },
        success: function (objOut) {
            objInit = JSON.parse(objOut);
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


/*
 *  When the document is loaded
 */

$(document).ready(function () {
    /* Nav bar */
    $('<div></div>')
            .attr('id', 'otherContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');

    /* Nav bar */
    $('<div></div>')
            .attr('id', 'navContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');

    /* Main container */
    $('<div></div>')
            .attr('id', 'mainContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');

    retrieveCateg();
    $(document).on("click", ".divClikable", function () {
        if ($(this).attr('id') === 'divEnd') {
            location.href = DOL_URL_ROOT + '/product/card.php?id=' + getUrlParameter('id');
        } else if ($(this).attr('id') === 'divToBrowse') {
            location.href = DOL_URL_ROOT + '/bimpproductbrowser/browse.php?id=' + objInit.ROOT_CATEGORY;
        } else if ($(this).hasClass('navDiv')) {
            deleteFrom($(this).attr('id'), $(this).attr('name'));
        } else {
            catArr.push($(this).attr('id'));
            addCatInProd($(this).attr('id'));
            deleteAllDivs();
            changeNavDiv($(this).text());
            addDivs();
        }
    });
});

/*
 *  Annexe functions
 */

function retrieveCateg() {
    getAllCateg();
    if (objInit.ROOT_CATEGORY === null || objInit.ROOT_CATEGORY === undefined) {
        addErrorDivs();
        return;
    }
    cntRestr = objInit.tabRestrCounter;
    cnt = objInit.cnt;
    catArr = objInit.catArr;
    k = 0;
    for (i = 0; i < objInit.tabRestr.length; i++) {
        objs.push(objInit.tabRestr[i]);
        if (objInit.tabRestr[i].selectedLabel) {
            addNavDivs(objInit.tabRestr[i], k);
            k++;
        }
    }
    addWays();
    addDivs();
}

function addWays() {
    $('<p></p><br>')
            .attr('style', 'margin-bottom:-15px ; margin-top: -5px')
            .text('Catégories hors module ')
            .appendTo('#otherContainer');
    for (i = 0; i < objInit.ways.length; i++) {
//        if (objInit.color[i] === undefined) {
        objInit.color[i] = 'aaa';
//        }
        $('<li></li>')
                .attr('class', "noborderoncategories customLi")
                .attr('style', 'margin-right:5px ; background-color:#' + objInit.color[i])
                .attr('id', 'idOther' + i)
                .html(objInit.ways[i])
                .appendTo('#otherContainer');
        $('<img>')
                .attr('src', DOL_URL_ROOT + '/theme/eldy/img/object_category.png')
                .attr('class', "inline-block valigntextbottom")
                .prependTo('#idOther' + i);
    }
}

function deleteFrom(id_div) {

    var objToKeep = 0;
    for (i = cnt; i >= id_div; i--)
        $("#navContainer").children("div").eq(i).remove();
    for (i = 0; i <= id_div; i++) {
        objToKeep += cntRestr[i];
    }
    cntRestr.length = parseInt(id_div)+1;
    catOut = catArr.slice(id_div);
    if (objs.length >= objToKeep) {
        objs.length = objToKeep;
    }
    cnt = id_div;
    deleteAllDivs();
    catArr.length = id_div;
    deleteCateg(catOut);
    addDivs();
}


function addDivs() {
    if (objs.length === 0) {
        $('<div>Aucune catégorie faisant partie de ce module n\'a été définie.<br> Cliquez ici pour en créer une.<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divToBrowse')
                .appendTo('#mainContainer');
    } else if (cnt >= objs.length) {
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

function addErrorDivs() {
    $('<div></div><br>')
            .attr('class', 'customDiv fixDiv errorDiv')
            .text("Erreur : La catégorie racine n'est pas définie, veuillez conctactez l'administrateur pour qu'il en désigne une.")
            .appendTo('#mainContainer');
}

function addNavDivs(restr, k) {
    $('<div>' + restr.label + ':<br>' + restr.selectedLabel + '</div>')
            .attr('id', k)
            .attr('class', 'customDiv divClikable navDiv')
            .appendTo('#navContainer');
}

function changeNavDiv(text) {
    $("#navContainer").find('#' + (cnt - 1).toString()).append(':<br>' + text);
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