/*
 * Global variables
 */

var obj = [];   // existing categories before the execution


/*
 *  AJAX requests
 */

/* Get all datas about categories of the product from the database */
function getOldWay() {
    id_prod = getUrlParameter('id');

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpproductbrowser/nextCategory.php",
        data: {
            id_prod: id_prod,
            action: 'getOldWay'
        },
        async: false,
        error: function () {
            alert("Error");
        },
        success: function (objOut) {
            obj = JSON.parse(objOut);
        }
    });
}

/* Remove some in the table category_product 
 * catOut is an Array 
 */
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

/* Add the category in the table category_product */
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
        }
    });
}


/*
 *  When the document is loaded
 */

$(document).ready(function () {
    initAll();

    $('body').on('viewRefresh', function(e){
        for(var i=0 ; i < e.$view[0].attributes.length ; i++) {
            if(e.$view[0].attributes[i].nodeValue === 'product_categorize')
                initAll();
        }
    });
});


/*
 * All other function
 */

function initAll(){
    initNav();
    reloadAllAndPrint();
    $(document).on("click", ".divClikable", function () {
        if ($(this).attr('id') === 'divEnd') {
            location.href = DOL_URL_ROOT + '/bimpcore/index.php?fc=product&id=' + getUrlParameter('id');
        } else if ($(this).attr('id') === 'divToBrowse') {
            location.href = DOL_URL_ROOT + '/bimpproductbrowser/browse.php?id=' + objInit.ROOT_CATEGORY;
        } else if ($(this).hasClass('navDiv')) {    // If we delete some categories
            deleteCategAfter($(this).attr('id'));
            reloadAllAndPrint();
        } else {                                    // If we add one category
            addCatInProd($(this).attr('id'));
            reloadAllAndPrint();
        }
    });
}

/* Create all container for adding div in it */
function initNav() {
    /* Annexes categories bar */
    $('<div></div>')
            .attr('id', 'otherContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');

    /* Nav bar */
    $('<div></div>')
            .attr('id', 'categorization')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');

    /* Main container */
    $('<div></div>')
            .attr('id', 'choice')
            .attr('class', 'customBody')
            .appendTo('.fiche');
}

/* Supress category and all after it */
function deleteCategAfter(id) {
    var categToremove = [];
    categToremove.push($("#categorization").find('#' + id).attr('idFille'));
    $("#categorization").find('#' + id).nextAll().each(function (elt) {
        categToremove.push($(this).attr('idFille'));
    });
    deleteCateg(categToremove);
}

/* Retrieve data and redisplay the content of the page */
function reloadAllAndPrint() {
    getOldWay();
    resetAllDivs();
}

/* Delete old divs and add new divs */
function resetAllDivs() {
    $("#annex").empty();
    $("#categorization").empty();
    $("#choice").empty();

    if (obj.ROOT_CATEGORY === null || obj.ROOT_CATEGORY === undefined) {
        addErrorDivs();
        return;
    }

    addWays();
    addAllNav();
    addDivChoice();
}

/* Add a message in a div if the BIMP_ROOT_CATEGORY isn't */
function addErrorDivs() {
    $('<div></div><br>')
            .attr('class', 'customDiv fixDiv errorDiv')
            .text("Erreur : La catégorie racine n'est pas définie, veuillez conctactez l'administrateur pour qu'il en désigne une.")
            .appendTo('#choice');
}

function addWays() {
    $('<p></p><br>')
            .attr('style', 'margin-bottom:-15px ; margin-top: -5px')
            .text('Catégories hors module ')
            .appendTo('#annex');
    var id;
    for (id in obj.waysAnnexesCategories) {
        addOneWay(obj.waysAnnexesCategories[id]);
    }
}

/* Add a single annexe category */
function addOneWay(way) {
    $('<li></li>')
            .attr('class', "noborderoncategories customLi")
            .attr('style', 'margin-right:5px ; background-color:#aaa')
            .html(way)
            .appendTo('#annex');
    $('<img>')
            .attr('src', DOL_URL_ROOT + '/theme/eldy/img/object_category.png')
            .attr('class', "inline-block valigntextbottom")
}

/* Add div in the "navigation bar" */
function addAllNav() {
    for (var id in obj.catOk) {
        $('<div>' + obj.catOk[id].nomMere + ' :<br>' + obj.catOk[id].nomFille + '</div>')
                .attr('id', id)
                .attr('idFille', obj.catOk[id].idFille)
                .attr('class', 'customDiv divClikable navDiv')
                .appendTo('#categorization');
    }
}

/* Add div to set a value to a category */
function addDivChoice() {
    if (obj.restrictionNonSatisfaite === null && obj.catOk === null) {  // The BIMP_ROOT_CATEGORY isn't set
        $('<div>Aucune catégorie faisant partie de ce module n\'a été définie.<br> Cliquez ici pour en ajouter une.<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divToBrowse')
                .appendTo('#choice');
    } else if (obj.catAChoisir === undefined) {     // The product is categorized
        $('<div><strong style="font-size: 32px;"><br>Merci</strong><br><br> Cliquez ici pour revenir<br>à la fiche du produit<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divEnd')
                .appendTo('#choice');
    } else {        // The product isn't fully categorized
        $('<div>' + obj.catAChoisir['labelMere'] + '</div>')
                .attr('id', obj.catAChoisir['idMere'])
                .attr('class', 'customDiv divClikable navDiv')
                .appendTo('#categorization');
        $('<div></div><br>')
                .attr('class', 'customDiv fixDiv')
                .text(obj.catAChoisir['labelMere'])
                .appendTo('#choice');

        var catAChoisir = sortcatAChoisir(obj.catAChoisir);
        var cat;
        catAChoisir.forEach(function (cat) {
            $('<div>' + cat.nom + '<a class="fillTheDiv" href=""></a></div>')
                    .attr("id", cat.id)
                    .attr('class', 'customDiv divClikable')
                    .appendTo('#choice');
        });
    }
}

function sortcatAChoisir() {

    var catAChoisir = [];
    var cat;

    for (var id in obj.catAChoisir) {
        if (obj.catAChoisir[id].nom != undefined) {
            cat = {
                nom: obj.catAChoisir[id].nom,
                id: id
            };
            catAChoisir.push(cat);
        }
    }

    catAChoisir.sort(function (a, b) {
        return a.nom.localeCompare(b.nom);
    });

    return catAChoisir;
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
}
;