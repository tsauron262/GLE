function loadObjectForm(object_name, $container, id_object, id_parent) {
    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }
    if (typeof (id_parent) === 'undefined') {
        id_parent = 0;
    }

    bimp_display_msg('Chargement du formulaire en cours', $container, 'info');

    var data = {
        'object_name': object_name,
        'id_object': id_object,
        'id_parent': id_parent
    };

    $('#' + object_name + '_closeFormButton').hide();
    $('#' + object_name + '_openFormButton').hide();

    bimp_json_ajax('loadObjectForm', data, $container, function (result) {
        if (typeof (result.html) !== 'undefined') {
            $container.html(result.html).slideDown(250);
            $('#' + object_name + '_closeFormButton').show();
        }
    }, function (result) {
        bimp_display_msg('Echec du chargement du formulaire', $container, 'danger');
        $('#' + object_name + '_closeFormButton').show();
    });
}

function openObjectForm(object_name, id_parent, id_object) {
    if (typeof (id_parent) === 'undefined') {
        id_parent = 0;
    }
    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }
    var $container = $('#' + object_name + '_formContainer');
    if ($container.length) {
        var $list = $('#' + object_name + '_listContainer');
        if ($list.length) {
            $list.hide();
        }
        $('#' + object_name + '_openFormButton').hide();
        $('#' + object_name + '_closeFormButton').hide();
        loadObjectForm(object_name, $container, id_object, id_parent);
    }
}

function closeObjectForm(object_name) {
    if (typeof (id_parent) === 'undefined') {
        id_parent = 0;
    }
    var $container = $('#' + object_name + '_formContainer');
    if ($container.length) {
        $container.stop().slideUp(250, function () {
            $(this).html('');
            var $list = $('#' + object_name + '_listContainer');
            if ($list.length) {
                $list.stop().slideDown(250);
            }
        });
    }
    $('#' + object_name + '_closeFormButton').hide();
    $('#' + object_name + '_openFormButton').show();
}

function saveObjectFromForm(object_name) {
    var $result = $('#' + object_name + '_formResultContainer');
    var $form = $('#' + object_name + '_form');

    if (!$form.length) {
        var msg = 'Enregistrement de l\'objet "' + object_name + '" impossible (Formulaire non trouvé)';
        bimp_display_msg(msg, $result, 'danger');
        return;
    }

    var data = $form.serialize();
    bimp_json_ajax('saveObject', data, $result, function () {
        if ($('#' + object_name + '_listContainer').length) {
            closeObjectForm(object_name);
            reloadObjectsList(object_name);
        }
    });
}

function reloadObjectsList(object_name) {
    var $resultContainer = $('#' + object_name + '_listResultContainer');
    var id_parent_object = parseInt($('#' + object_name + '_id_parent').val());

    if (!id_parent_object) {
        var msg = 'Impossible de recharger la liste';
        if (typeof (object_labels.name_plur) !== 'undefined') {
            msg += ' des ' + object_labels.name_plur;
        }
        msg += '. ID de l\'objet parent absent';
        bimp_display_msg(msg, $resultContainer, 'danger');
        return;
    }

    var data = {
        'object_name': object_name,
        'id_parent': id_parent_object
    };

    bimp_json_ajax('loadObjectList', data, 0, function (result) {
        if (result.html) {
            var $inputsRow = $('#' + object_name + '_listInputsRow');
            var inputsRowHtml = '';
            if ($inputsRow.length) {
                resetListRowInputs(object_name);
                inputsRowHtml = '<tr class="inputsRow" id="' + object_name + '_listInputsRow">';
                inputsRowHtml += $inputsRow.html();
                inputsRowHtml += '</tr>';
            }
            $('#' + object_name + '_list_table').find('tbody').html(result.html + inputsRowHtml);
        }
    }, function (result) {
        if (!bimp_display_result_errors(result, $resultContainer)) {
            bimp_display_msg('La liste des ' + object_labels.name_plur + ' n\'a pas pu être rechargée', $resultContainer, 'danger');
        }
    });
}

function toggleCheckAll(object_name, $input) {
    var $inputs = $('#' + object_name + '_list_table').find('tbody').find('input.item_check');
    if ($input.prop('checked')) {
        $inputs.each(function () {
            $(this).prop('checked', 'checked');
        });
    } else {
        $inputs.each(function () {
            $(this).removeProp('checked');
        });
    }
}

function addObjectFromListInputsRow(object_name, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var $result = $('#' + object_name + '_listResultContainer');
    var $row = $('#' + object_name + '_listInputsRow');
    if (!$row.length) {
        if ($result.length) {
            bimp_display_msg('Aucun formulaire trouvé', $result, 'danger');
        }
        return;
    }

    $button.addClass('disabled');

    var $inputs = $row.find('.objectListRowInput');

    var data = {'object_name': object_name};
    $inputs.each(function () {
        data[$(this).attr('name')] = $(this).val();
    });

    bimp_json_ajax('saveObject', data, $result, function (result) {
        if (!result.errors.length) {
            resetListRowInputs(object_name);
            $button.removeClass('disabled');
            reloadObjectsList(object_name);
        }
    }, function (result) {
        $button.removeClass('disabled');
    });
}

function resetListRowInputs(object_name) {
    var $row = $('#' + object_name + '_listInputsRow');
    if ($row.length) {
        var $inputs = $row.find('.objectListRowInput');
        $inputs.each(function () {
            var defval = $(this).data('default_value');
            if (defval !== 'undefined') {
                $(this).val(defval);
            } else {
                if ($(this).hasClass('switch')) {
                    $(this).val(0);
                } else {
                    $(this).val('');
                }
            }
        });
    }
}

function updateObjectFromRow(object_name, id_object, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $resultContainer = $('#' + object_name + '_listResultContainer');
    var $row = $('#' + object_name + '_list_table').find('tbody').find('#' + object_name + '_row_' + id_object);

    if (!$row.length) {
        bimp_display_msg('Erreur technique, liste non trouvée', $resultContainer, 'danger');
        return;
    }

    $button.addClass('disabled');

    var data = {
        'object_name': object_name,
        'id_object': id_object
    };

    var $inputs = $row.find('.objecRowEditInput');
    $inputs.each(function () {
        data[$(this).attr('name')] = $(this).val();
    });

    bimp_json_ajax('saveObject', data, $resultContainer, function (result) {
        $button.removeClass('disabled');
        reloadObjectsList(object_name);
    }, function (result) {
        $button.removeClass('disabled');
    });
}

function deleteObjects(object_name, objects_list, $button, $resultContainer) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (!objects_list.length) {
        return;
    }

    var msg = 'Voulez-vous vraiment supprimer les ';

    if (typeof (object_labels) !== 'undefined') {
        msg += object_labels['name_plur'];
        if (object_labels['isFemale']) {
            msg += ' sélectionnées?';
        } else {
            msg += ' sélectionnés?';
        }
    } else {
        msg += 'objets sélectionnés?';
    }

    if (confirm(msg)) {
        $button.addClass('disabled');
        var data = {
            'object_name': object_name,
            'objects': objects_list
        }

        bimp_json_ajax('deleteObjects', data, $resultContainer, function (result) {
            $button.removeClass('disabled');
            reloadObjectsList(object_name);
        }, function (result) {
            $button.removeClass('disabled');
        });
    }
}

function deleteSelectedObjects(object_name, $button) {
    var $resultContainer = $('#' + object_name + '_listResultContainer');
    var $table = $('#' + object_name + '_list_table');
    var $selected = $table.find('tbody').find('input.item_check:checked');
    if (!$selected.length) {
        var msg = '';
        if (object_labels['isFemale']) {
            msg = 'Aucune ' + object_labels['name'] + ' sélectionnée';
        } else {
            msg = 'Aucun ' + object_labels['name'] + ' sélectionné';
        }
        bimp_display_msg(msg, $resultContainer, 'danger');
    } else {
        var objects_list = [];
        $selected.each(function () {
            objects_list.push(parseInt($(this).data('id_object')));
        });
        deleteObjects(object_name, objects_list, $button, $resultContainer);
    }
}