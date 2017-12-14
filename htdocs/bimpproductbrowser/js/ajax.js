




var obj = [];   // existing categories before the execution

/*
 *  AJAX requests
 */

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
//            alert("Error");
            console.log(error);
        },
        success: function (objOut) {
            obj = JSON.parse(objOut);
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
    });
}

function initNav() {
    /* Annexes categories bar */
    $('<div></div>')
            .attr('id', 'otherContainer')
            .attr('class', 'customBody')
            .appendTo('.fiche');
    $('<div><div>')
            .attr('class', 'underbanner clearboth')
            .appendTo('.fiche');

    /* Annexes categories implied bar */
    $('<div></div>')
            .attr('id', 'annexeNavContainer')
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
}

/*
 *  When the document is loaded
 */

$(document).ready(function () {
    initNav();
    reloadAllAndPrint();
    $(document).on("click", ".divClikable", function () {
        if ($(this).attr('id') === 'divEnd') {
            location.href = DOL_URL_ROOT + '/product/card.php?id=' + getUrlParameter('id');
        } else if ($(this).attr('id') === 'divToBrowse') {
            location.href = DOL_URL_ROOT + '/bimpproductbrowser/browse.php?id=' + objInit.ROOT_CATEGORY;
        } else if ($(this).hasClass('navDiv')) {
            deleteCategAfter($(this).attr('id'));
            reloadAllAndPrint();
        } /*else if ($(this).hasClass('divNavAnnexe')) {

        } else if ($(this).hasClass('divForAnnexe')) {
         
         }*/else {
            addCatInProd($(this).attr('id'));
            reloadAllAndPrint();
        }
    });
});

/* Supress category and all after it */
function deleteCategAfter(id) {
    var categToremove = [] ;
    categToremove.push($("#navContainer").find('#' + id).attr('idFille'));
    $("#navContainer").find('#' + id).nextAll().each(function (elt) {
        categToremove.push($(this).attr('idFille'));
    });
    deleteCateg(categToremove);
}

function reloadAllAndPrint() {
    getOldWay();
    resetAllDivs();
}

function resetAllDivs() {
    $("#otherContainer").empty();
    $("#annexeNavContainer").empty();
    $("#navContainer").empty();
    $("#mainContainer").empty();

    if (obj.ROOT_CATEGORY === null || obj.ROOT_CATEGORY === undefined) {
        addErrorDivs();
        return;
    }
    
    addWays();
    addAllNav();
    addDivChoice();
}

function addWays() {
    $('<p></p><br>')
            .attr('style', 'margin-bottom:-15px ; margin-top: -5px')
            .text('Catégories hors module ')
            .appendTo('#otherContainer');
    var id;
    for (id in obj.waysAnnexesCategories) {
        addOneWay(obj.waysAnnexesCategories[id]);
    }
}

function addOneWay(way) {
    $('<li></li>')
            .attr('class', "noborderoncategories customLi")
            .attr('style', 'margin-right:5px ; background-color:#aaa')
            .html(way)
            .appendTo('#otherContainer');
    $('<img>')
            .attr('src', DOL_URL_ROOT + '/theme/eldy/img/object_category.png')
            .attr('class', "inline-block valigntextbottom")
}

function addAllNav() {
    for (var id in obj.catOk) {
        $('<div>' + obj.catOk[id].nomMere + ' :<br>' + obj.catOk[id].nomFille + '</div>')
                .attr('id', id)
                .attr('idFille', obj.catOk[id].idFille)
                .attr('class', 'customDiv divClikable navDiv')
                .appendTo('#navContainer');
    }
}

function addDivChoice() {
    if (obj.restrictionNonSatisfaite === null && obj.catOk === null) {
        $('<div>Aucune catégorie faisant partie de ce module n\'a été définie.<br> Cliquez ici pour en ajouter une.<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divToBrowse')
                .appendTo('#mainContainer');
    } else if (obj.catAChoisir === undefined) {
        $('<div><strong><br>Merci</strong><br><br> Cliquez ici pour revenir<br>à la fiche du produit<a class="fillTheDiv" href=""></a></div>')
                .attr('class', 'customDiv divClikable')
                .attr('id', 'divEnd')
                .appendTo('#mainContainer');
    } else {
        $('<div>' + obj.catAChoisir['labelMere'] + '</div>')
                .attr('id', obj.catAChoisir['idMere'])
                .attr('class', 'customDiv divClikable navDiv')
                .appendTo('#navContainer');
        $('<div></div><br>')
                .attr('class', 'customDiv fixDiv')
                .text(obj.catAChoisir['labelMere'])
                .appendTo('#mainContainer');
        for (var id in obj.catAChoisir) {
            if (obj.catAChoisir[id].nom != undefined) {
                $('<div>' + obj.catAChoisir[id].nom + '<a class="fillTheDiv" href=""></a></div>')
                        .attr("id", id)
                        .attr('class', 'customDiv divClikable')
                        .appendTo('#mainContainer');
            }
        }
    }
}






























/*
 *  Annexe functions
 */

function addImpliedNav(cat) {
    $('<div>' + cat.label + '</div>')
            .attr('id', cat.id)
//                .attr('idDiv', i)
            .attr('class', 'customDiv divClikable divNavAnnexe')
            .attr('originalName', cat.label)
            .attr('name', cat.selectedLabel)
            .attr('value', cat.selectedLabel)
            .appendTo('#annexeNavContainer');
}

function retrieveCateg() {
    getOldWay();
    if (objInit.ROOT_CATEGORY === null || objInit.ROOT_CATEGORY === undefined) {
        addErrorDivs();
        return;
    }
    cntRestr = objInit.cntRestr;
    cnt = objInit.cnt;
    catArr = objInit.catArr;
    annexes = objInit.child;
    for (i = 0; i < objInit.catsToAdd.length; i++) {
        objs.push(objInit.catsToAdd[i]);
        if (objInit.catsToAdd[i].selectedLabel) {
            addNavDivs(objInit.catsToAdd[i], i);
        }
    }
    for (i = 0; i < objInit.child.length; i++) {
        if (objInit.child[i].selectedId != undefined) {
//            addImpliedNav(objInit.child[i].selectedId);
        }
        console.log('ok ' + objInit.child[i].label);
        addAnnexeNavDivs(objInit.child[i], i);
    }
    addWays();
    addNextDiv();
}

/* Search if there are annexes categories which aren't filled, else 
 * search if there are normals categories which aren't filled, then add divs */
function addNextDiv() {
    var id = -1;
    for (var i = 0; i < annexes.length; i++) {
        if (annexes[i].selectedId === undefined || annexes[i].selectedId === null) {
            id = annexes[i].id;
            break;
        }
    }
    if (id != -1) {
        addAnnexeDivs(id);
    } else {
        addDivs();
    }
}

function changeAnnexeDivs(idMother, label, id) {
    $("#annexeNavContainer").find('#' + idMother.toString()).append(':<br>' + label);
    $("#annexeNavContainer").find('#' + idMother.toString()).attr('name', id);
    $("#annexeNavContainer").find('#' + idMother.toString()).attr('value', label);

    var cat = $.grep(annexes, function (elt) {
        return elt.id == idMother;
    })[0];
    cat.selectedLabel = label;
    cat.selectedId = id;
}




function deleteFrom(id_div) {

    catOutWithoutAnnexe = [];
    var objToKeep = 0;
    for (i = cnt; i >= id_div; i--)
        $("#navContainer").children("div").eq(i).remove();
    for (i = 0; i <= id_div; i++) {
        objToKeep += cntRestr[i];
    }
    cntRestr.length = parseInt(id_div) + 1;
    catOut = catArr.slice(id_div);
    if (objs.length >= objToKeep) {
        objs.length = objToKeep;
    }
    cnt = id_div;
    deleteAllDivs();
    catArr.length = id_div;
    deleteCateg(catOut);
    addNextDiv();
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

function deleteAnnexe(name, id) {
    catToDelete = [];
    catToDelete.push(name);
    deleteCateg(catToDelete);
    var cat = $.grep(annexes, function (elt) {
        return elt.id == id;
    })[0];
    cat.selectedLabel = null;
    cat.selectedId = null;
}

function addAnnexeNavDivs(child, i) {
    var nameChild;
    var val;
    if (child.selectedId !== undefined && child.selectedId !== null) {
        nameChild = child.selectedId;
        val = ' :<br>' + child.selectedLabel;
    } else {
        nameChild = 'NO_CHILD_SELECTED';
        val = '';
    }

    if (child.unremovable === true)
        $('<div>' + child.label + val + '</div>')
                .attr('id', child.id)
                .attr('class', 'customDiv divClikable divNavAnnexe unremovable')
                .attr('originalName', child.label)
                .attr('name', nameChild)
                .attr('value', val.replace('<br>', ''))
                .appendTo('#annexeNavContainer');
    else
        $('<div>' + child.label + val + '</div>')
                .attr('id', child.id)
                .attr('class', 'customDiv divClikable divNavAnnexe')
                .attr('originalName', child.label)
                .attr('name', nameChild)
                .attr('value', val.replace('<br>', ''))
                .appendTo('#annexeNavContainer');
}

function addAnnexeDivs(id) {
//    $('<div>' + objs[cntA].label + '</div>')
//            .attr('id', cntA)
//            .attr('class', 'customDiv divClikable navDiv')
//            .appendTo('#navContainer');
    var cat = $.grep(annexes, function (elt) {
        return elt.id == id;
    })[0];

    $('<div></div><br>')
            .attr('id', cat.id)
            .attr('class', 'customDiv fixDiv')
            .text(cat.label)
            .appendTo('#mainContainer');
    for (var i = 0; i < cat.tabIdChild.length; i++) {
        $('<div>' + cat.tabNameChild[i] + '<a class="fillTheDiv" href=""></a></div>')
                .attr('id', cat.tabIdChild[i])
                .attr('idMother', cat.id)
                .attr('class', 'customDiv divClikable divForAnnexe')
                .appendTo('#mainContainer');
    }
//    ++cnt;
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