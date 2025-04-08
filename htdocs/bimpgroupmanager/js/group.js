/**
 * globals var
 */
/* global DOL_URL_ROOT */

var groups = {};



/**
 * Ajax functions
 */

function getOldGroup() {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpgroupmanager/interface.php",
        data: {
            action: 'getOldGroup',
			token: dol_token,
        },
        async: false,
        error: function () {
            console.log("Erreur PHP");
        },
        success: function (objOut) {
            groups = JSON.parse(objOut);
        }
    });
}

function updateGroup(groupId, newGroupId) {

    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpgroupmanager/interface.php",
        data: {
            action: 'updateGroup',
            groupId: groupId,
            newGroupId: newGroupId,
			token: dol_token,
        },
        error: function () {
            alert("Erreur serveur. Il est possible qu'un autre utilisateur " +
                    "soit en train de modifier les propriété de ce groupe.");
        }
    });
}

function setAllUsers() {
    
    $.ajax({
        type: "POST",
        url: DOL_URL_ROOT + "/bimpgroupmanager/interface.php",
        data: {
            action: 'setAllUsers',
			token: dol_token,
        },
        async: false,
        error: function () {
            console.log("Erreur PHP");
        },
        success: function () {
            alert("Les groupes de tous utilisateurs ont été mis à jour.");
        }
    });
}



/**
 * On ready
 */

$(document).ready(function () {

    var elem;
    getOldGroup();
    printGroups();
    $('.dd').nestable();

    $('#nestable-menu').on('click', function (e) {
        var target = $(e.target),
                action = target.data('action');
        if (action === 'expand-all') {
            $('.dd').nestable('expandAll');
        }
        if (action === 'collapse-all') {
            $('.dd').nestable('collapseAll');
        }
        if (action === 'set-all-users' && confirm("Vous êtes sur le points de redéfinir les groupes de tous les utilisateurs" +
                ", il s'agit d'une lourde opération. Poursuivre ?")) {
            setAllUsers();
        }

    });

    elem = null;
    $('.dd-handle').on('mousedown touchend', function () {
        elem = $(this);
    });

    $('.dd').on('change', function () {
        if (!elem)
            alert("Pas d'élem referent");
        else
            updateGroup(elem.parent().attr('data-id'), elem.parent().parent().parent().attr('data-id'));
    });
});



/**
 * Functions
 */

/* Just to vizualize data, do not use that function in production, 
 * it can be use to check if nestable is OK
 */
function dev() {

    $('<textarea id="nestable-output" style="visibility: hidden"></textarea>').appendTo('div.cf.nestable-lists');

    var updateOutput = function (e) {
        var list = e.length ? e : $(e.target),
                output = list.data('output');
        if (window.JSON) {
            output.val(window.JSON.stringify(list.nestable('serialize')));//, null, 2));
        }
    };

    $('#nestable').nestable({
        group: 1
    }).on('change', updateOutput);

    updateOutput($('#nestable').data('output', $('#nestable-output')));
}

function printGroups() {
    groups.forEach(function (grp) {
        if (grp.isRoot === true) {
            addItem(grp, '#nestable ol:first');
        } else {
            addItem(grp, 'ol#' + grp.id_parent);
        }
    });
}

/* Add a group */
function addItem(element, fullBalise) {
    if (element.childs.length !== 0) {
        $('<li class="dd-item dd3-item" data-id="' + element.id + '">' +
                '<div class="dd-handle dd3-handle"></div><div class="dd3-content">' + element.name + '</div>')
                .appendTo(fullBalise);
        addNewList(element);
    } else {
        $('<li class="dd-item dd3-item" data-id="' + element.id + '">' +
                '<div class="dd-handle dd3-handle"></div><div class="dd3-content">' + element.name + '</div>')
                .appendTo(fullBalise);
    }
}

/* If the group got children, use that function */
function addNewList(element) {
    $('<ol class="dd-list" id=' + element.id + '></ol>').appendTo('li[data-id="' + element.id + '"]')
}
