// Traitements Ajax:

var object_labels = {};

function reloadObjectList(list_id, callback) {
    var $list = $('#' + list_id);

    if (!$list.length) {
//        console.error('Erreur technique: identifiant de la liste invalide (' + list_id + '). Echec du rechargement de la liste');
        return;
    }

    $list.find('.headerTools').find('.loadingIcon').css('opacity', 1);

    var $resultContainer = $('#' + list_id + '_result');
    var object_name = $list.data('object_name');
    var id_parent_object = parseInt($list.find('#' + object_name + '_id_parent').val());

    if ((typeof (id_parent_object) === 'undefined') || !id_parent_object) {
        id_parent_object = 0;
    }

    // Données de base:
    var data = {
        'list_name': $list.data('name'),
        'list_id': list_id,
        'module': $list.data('module'),
        'object_name': object_name,
        'id_parent': id_parent_object
    };

    // Champs de recherche:
    var $row = $('#' + list_id + '_searchRow');

    if ($row.length) {
        data['search_fields'] = {};
        $row.find('.searchInputContainer').each(function () {
            var search_type = $(this).data('search_type');
            var field_name = $(this).data('field_name');
            if (field_name) {
                var field = field_name.replace(/^search_(.+)$/, '$1');
                switch (search_type) {
                    case 'time_range':
                    case 'date_range':
                    case 'datetime_range':
                        var $from = $(this).find('[name=' + field_name + '_from]');
                        var $to = $(this).find('[name=' + field_name + '_to]');
                        data['search_fields'][field] = {};
                        if ($from.length) {
                            data['search_fields'][field]['from'] = $from.val();
                        }
                        if ($to.length) {
                            data['search_fields'][field]['to'] = $to.val();
                        }
                        break;

                    default:
                        var $input = $(this).find('[name=' + field_name + ']');
                        if ($input.length) {
                            data['search_fields'][field] = $input.val();
                        }
                }
            }
        });
    }

    // Lignes modifiées:
    var $rows = $list.find('tbody.listRows').find('tr.modified');
    if ($rows.length) {
        data['new_values'] = {};
        $rows.each(function () {
            var id_object = $(this).data('id_object');
            $(this).find('.inputContainer').each(function () {
                var field_name = $(this).data('field_name');
                if (field_name) {
                    var $input = $(this).find('[name="' + field_name + '"]');
                    if ($input.length) {
                        if ($input.hasClass('modified')) {
                            if (typeof (data['new_values'][id_object]) === 'undefined') {
                                data['new_values'][id_object] = {};
                            }
                            data['new_values'][id_object][field_name] = $input.val();
                        }
                    }
                }
            });
        });
    }

    // Options de trie et de pagination:
    var sort_col = $list.find('input[name=param_sort_field]').val();
    var sort_way = $list.find('input[name=param_sort_way]').val();
    var sort_option = $list.find('input[name=param_sort_option]').val();
    var n = $list.find('input[name=param_n]').val();
    var p = $list.find('input[name=param_p]').val();
    var joins = $list.find('input[name=param_joins]').val();

    if (sort_col) {
        data['param_sort_field'] = sort_col;
    }
    if (sort_way) {
        data['param_sort_way'] = sort_way;
    }
    if (sort_option) {
        data['param_sort_option'] = sort_option;
    }
    if (n) {
        data['param_n'] = n;
    }
    if (p) {
        data['param_p'] = p;
    }
    if (joins) {
        data['param_joins'] = joins;
    }

    // Filtres: 
    if ($list.find('input[name=param_list_filters]').length) {
        data['param_list_filters'] = $list.find('input[name=param_list_filters]').val();
    }
    if ($list.find('input[name=param_associations_filters]').length) {
        data['param_associations_filters'] = $list.find('input[name=param_associations_filters]').val();
    }

    // Envoi requête:
    var error_msg = 'Une erreur est sruvenue. La liste des ';
    if (typeof (object_labels[object_name].name_plur) !== 'undefined') {
        error_msg += object_labels[object_name].name_plur;
    } else {
        error_msg += 'objets "' + object_name + '"';
    }
    error_msg += ' n\'a pas pu être rechargée';

    BimpAjax('loadObjectList', data, null, {
        $list: $list,
        $resultContainer: $resultContainer,
        display_success: false,
        error_msg: error_msg,
        success: function (result, bimpAjax) {
            $list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            if (result.rows_html) {
                bimpAjax.$list.find('tbody.listRows').html(result.rows_html);
                if (result.pagination_html) {
                    bimpAjax.$list.find('.listPagination').each(function () {
                        $(this).data('event_init', 0);
                        $(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
                    });
                    setPaginationEvents(bimpAjax.$list);
                } else {
                    bimpAjax.$list.find('.listPagination').each(function () {
                        $(this).html('').parent('td').parent('tr.paginationContainer').hide();
                    });
                }

                onListRefeshed(bimpAjax.$list);

                if (typeof (callback) === 'function') {
                    callback(true);
                }
            } else {
                if (typeof (callback) === 'function') {
                    callback(false);
                }
            }
        },
        error: function () {
            $list.find('.headerTools').find('.loadingIcon').css('opacity', 0);
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

function loadModalList(module, object_name, list_name, id_parent, $button, title) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    if (typeof (title) === 'undefined' || !title) {
        title = '<i class="fa fa-bars iconLeft"></i>Liste des ';
        if (typeof (object_labels[object_name].name_plur) !== 'undefined') {
            title += object_labels[object_name].name_plur;
        } else {
            title += 'Objet "' + object_name + '"';
        }
    }

    $modal.find('.modal-title').html(title);
    $modal.find('.loading-text').text('Chargement');
    $modal.find('.content-loading').show();
    $modal.modal('show');

    var isCancelled = false;

    $modal.on('hide.bs.modal', function (e) {
        $modal.find('.extra_button').remove();
        $modal.find('.content-loading').hide();
        isCancelled = true;
        $button.removeClass('disabled');
    });

    if (typeof (id_parent) === 'undefined') {
        id_parent = 0;
    }
    var data = {
        'module': module,
        'object_name': object_name,
        'list_name': list_name,
        'id_parent': id_parent
    };

    BimpAjax('loadObjectListFullPanel', data, null, {
        $modal: $modal,
        $resultContainer: $resultContainer,
        display_success: false,
        success: function (result, bimpAjax) {
            bimpAjax.$modal.find('.content-loading').hide();
            if (!isCancelled) {
                if (typeof (result.html) !== 'undefined') {
                    bimpAjax.$resultContainer.html(result.html).slideDown(250);
                    var $new_list = bimpAjax.$resultContainer.find('#' + result.list_id);
                    if ($new_list.length) {
                        onListLoaded($new_list);
                    }
                }
                bimpAjax.$modal.modal('handleUpdate');
            }
        },
        error: function (result, bimpAjax) {
            bimpAjax.$modal.find('.content-loading').hide();
            bimpAjax.$modal.modal('handleUpdate');
        }
    });
}

function loadModalFormFromList(list_id, form_name, $button, id_object, id_parent, title) {
    var $list = $('#' + list_id);
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    var data = {
        'module': $list.data('module'),
        'object_name': $list.data('object_name'),
        'form_name': form_name,
        'id_object': id_object,
        'id_parent': id_parent
    };

    var $values_input = $list.find('input[name=' + form_name + '_add_form_values]');
    if ($values_input.length) {
        data['param_values'] = $values_input.val();
    }

    var $asso_filters_input = $list.find('input[name=param_associations_filters]');
    if ($asso_filters_input.length) {
        data['param_associations_params'] = $asso_filters_input.val();
    }

    loadModalForm($button, data, title);
}

function updateObjectFromRow(list_id, id_object, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var object_name = $list.data('object_name');

//    var $resultContainer = $('#' + list_id + '_result');
    var $row = $list.find('tbody.listRows').find('#' + object_name + '_row_' + id_object);

    if (!$row.length) {
        bimp_msg('Erreur technique: liste non trouvée', 'danger');
        return;
    }

    $button.addClass('disabled');

    var data = {
        'list_name': $list.data('name'),
        'module': $list.data('module'),
        'object_name': object_name,
        'id_object': id_object
    };

    $row.removeClass('modified');
    $row.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        if (field_name) {
            var val = $(this).find('[name=' + field_name + ']').val();
            if (typeof (val) !== 'undefined') {
                data[field_name] = val;
            }
        }
    });

    BimpAjax('saveObject', data, null, {
        success: function (result) {
            $button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        },
        error: function (result) {
            $button.removeClass('disabled');
        }
    });
}

function saveAllRowsModifications(list_id, $button) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    $list.find('tbody.listRows').children('tr.modified').each(function () {
        var id_object = $(this).data('id_object');
        var $button = $(this).find('.updateButton');
        updateObjectFromRow(list_id, id_object, $button);
    });
}

function addObjectFromList(list_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var $result = $('#' + list_id + '_result');
    var $row = $('#' + list_id + '_addObjectRow');

    if (!$row.length) {
        if ($result.length) {
            bimp_msg('Aucun formulaire trouvé', 'danger');
        }
        return;
    }

    $button.addClass('disabled');

    var data = {
        'list_name': $list.data('name'),
        'module': $list.data('module'),
        'object_name': $list.data('object_name'),
        'id_object': 0
    };

    $row.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        var $input = $(this).find('[name=' + field_name + ']');
        if ($input.length) {
            data[field_name] = $input.val();
        }
    });

    BimpAjax('saveObject', data, null, {
        success: function (result) {
            resetListAddObjectRow(list_id);
            $button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        },
        error: function (result) {
            $button.removeClass('disabled');
        }
    });
}

function deleteObjects(list_id, objects_list, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (!objects_list.length) {
        return;
    }

    var $list = $('#' + list_id);
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var object_name = $list.data('object_name');

    var msg = 'Voulez-vous vraiment supprimer les ';

    if (typeof (object_labels[object_name]) !== 'undefined') {
        msg += object_labels[object_name]['name_plur'];
        if (object_labels[object_name]['is_female']) {
            msg += ' sélectionnées?';
        } else {
            msg += ' sélectionnés?';
        }
    } else {
        msg += 'objets sélectionnés?';
    }

    if (confirm(msg)) {
        var $resultContainer = $('#' + list_id + '_result');
        $button.addClass('disabled');
        var data = {
            'module': $list.data('module'),
            'object_name': object_name,
            'objects': objects_list
        };

        BimpAjax('deleteObjects', data, null, {
            success: function (result) {
                $button.removeClass('disabled');
                for (var i in result.objects_list) {
                    $('body').trigger($.Event('objectDelete', {
                        module: result.module,
                        object_name: result.object_name,
                        id_object: result.objects_list[i]
                    }));
                }
            },
            error: function (result) {
                $button.removeClass('disabled');
            }
        });
    }
}

function deleteSelectedObjects(list_id, $button) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var $resultContainer = $('#' + list_id + '_result');
    var $selected = $list.find('tbody').find('input.item_check:checked');
    var object_name = $list.data('object_name');

    if (!$selected.length) {
        var msg = '';
        if (object_labels[object_name]['is_female']) {
            msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
        } else {
            msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
        }
        bimp_msg(msg, 'danger');
    } else {
        var objects_list = [];
        $selected.each(function () {
            objects_list.push(parseInt($(this).data('id_object')));
        });
        deleteObjects(list_id, objects_list, $button, $resultContainer);
    }
}

function saveObjectPosition(list_id, id_object, position) {
    var $list = $('#' + list_id);
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var data = {
        'list_id': list_id,
        'module': $list.data('module'),
        'object_name': $list.data('object_name'),
        'id_object': id_object,
        'position': position
    };

    BimpAjax('saveObjectPosition', data, null, {
        success: function (result) {
            var $list = $('#' + result.list_id);
            sortListByPosition($list.attr('id'));
        },
        error: function (result) {
            var $list = $('#' + result.list_id);
            sortListByPosition($list.attr('id'));
        }
    });
}

function toggleSelectedItemsAssociation(list_id, operation, association, id_associate) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    var $resultContainer = $('#' + list_id + '_result');
    var $selected = $list.find('tbody').find('input.item_check:checked');
    var module = $list.data('module');
    var object_name = $list.data('object_name');

    if (!$selected.length) {
        var msg = '';
        if (object_labels[object]['is_female']) {
            msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
        } else {
            msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
        }
        bimp_msg(msg, 'danger');
    } else {
        var associations = [];

        $selected.each(function () {
            associations.push({
                module: module,
                object_name: object_name,
                association: association,
                id_associate: id_associate,
                id_object: parseInt($(this).data('id_object'))
            });
        });

        saveAssociations(operation, associations, $resultContainer);
    }
}

// Actions:

function cancelObjectRowModifications(list_id, id_object, $button) {
    var $list = $('#' + list_id);
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }
    var object_name = $list.data('object_name');

    var $row = $list.find('tbody.listRows').find('tr#' + object_name + '_row_' + id_object);
    if (!$row.length) {
        bimp_msg('Erreur technique: ligne correspondante non trouvée', 'danger');
        return;
    }

    $row.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        var $input = $(this).find('[name="' + field_name + '"]');
        var initial_value = $(this).data('initial_value');
        if ($input.length) {
            $input.val(initial_value).change();
        }
        $input.removeClass('modified');
    });

    $row.removeClass('modified');
    $row.find('.cancelModificationsButton').hide();
    $row.find('.updateButton').hide();

    checkRowsModifications($list);
}

function cancelAllRowsModifications(list_id, $button) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    $list.find('tbody.listRows').children('tr.modified').each(function () {
        var id_object = $(this).data('id_object');
        $button = $(this).find('.cancelModificationsButton');
        cancelObjectRowModifications(list_id, id_object, $button);
    });
}

function checkRowModifications($row) {
    if ($row.length) {
        var modified = false;
        $row.find('.inputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                var $input = $(this).find('[name="' + field_name + '"]');
                var initial_value = $(this).data('initial_value');
                if ($input.length) {
                    if (initial_value != $input.val()) {
                        $input.addClass('modified');
                    } else {
                        $input.removeClass('modified');
                    }
                }
                if ($input.length) {
                    if ($input.hasClass('modified')) {
                        modified = true;
                    }
                }
            }
        });
        if (modified) {
            $row.addClass('modified');
            $row.find('.cancelModificationsButton').removeClass('hidden').show();
            $row.find('.updateButton').removeClass('hidden').show();
        } else {
            $row.removeClass('modified');
            $row.find('.cancelModificationsButton').hide();
            $row.find('.updateButton').hide();
        }
        var $list = findParentByClass($row, 'object_list_table');
        if ($list.length) {
            checkRowsModifications($list);
        }
    }
}

function checkRowsModifications($list) {
    if ($list.length) {
        var hasModifications = false;
        $list.find('tbody.listRows').children('tr').each(function () {
            if ($(this).hasClass('modified')) {
                hasModifications = true;
            }
        });
        var $container = $('#' + $list.attr('id') + '_container');
        if (hasModifications) {
            $container.find('.modifiedRowsActions').show();
        } else {
            $container.find('.modifiedRowsActions').hide();
        }
    }
}

function toggleCheckAll(list_id, $input) {
    var $inputs = $('#' + list_id).find('tbody').find('input.item_check');
    if ($input.prop('checked')) {
        $inputs.each(function () {
            $(this).prop('checked', true).change();
        });
    } else {
        $inputs.each(function () {
            $(this).removeAttr('checked').prop('checked', false).change();
        });
    }
}

function sortList(list_id, col_name) {
    var $row = $('#' + list_id).find('.headerRow');
    if ($row.length) {
        var $span = $row.find('#' + col_name + '_sortTitle');
        if ($span.length) {
            var $list = $('#' + list_id);
            var prev_sort_field = $list.find('input[name=param_sort_field]').val();
            var prev_sort_way = $list.find('input[name=param_sort_way]').val();
            var prev_sort_option = $list.find('input[name=param_sort_option]').val();
            $list.find('input[name=param_sort_field]').val($span.parent('th').data('field_name'));
            if ($span.hasClass('sorted-asc')) {
                $list.find('input[name=param_sort_way]').val('desc');
            } else {
                $list.find('input[name=param_sort_way]').val('asc');
            }
            var sort_option = $span.data('sort_option');
            if (!sort_option) {
                sort_option = '';
            }
            $list.find('input[name=param_sort_option]').val(sort_option);
            reloadObjectList(list_id, function (success) {
                if (success) {
                    $row.find('.sortTitle').each(function () {
                        if ($(this).parent('th').data('col_name') !== col_name) {
                            $(this).removeClass('active').removeClass('sorted-desc').addClass('sorted-asc');
                        }
                    });
                    $span.addClass('active');
                    if ($span.hasClass('sorted-desc')) {
                        $span.removeClass('sorted-desc').addClass('sorted-asc');
                    } else {
                        $span.removeClass('sorted-asc').addClass('sorted-desc');
                    }
                } else {
                    $list.find('input[name=param_sort_field]').val(prev_sort_field);
                    $list.find('input[name=param_sort_way]').val(prev_sort_way);
                    $list.find('input[name=param_sort_option]').val(prev_sort_option);
                }
            });
        }
    }
}

function sortListByPosition(list_id, first_page) {
    if (typeof (first_page) === 'undefined') {
        first_page = false;
    }
    var $list = $('#' + list_id);

    if ($list.length) {
        $list.find('input[name=param_sort_field]').val('position');
        $list.find('input[name=param_sort_way]').val('asc');
        $list.find('input[name=param_sort_option]').val('');
        if (!first_page) {
            var $pagination = $('#' + list_id + '_pagination');
            if ($pagination.length) {
                var p = parseInt($pagination.find('.pageBtn.active').data('p'));
                if (p) {
                    loadPage($list, p);
                    return;
                }
            }
        }
        reloadObjectList($list.attr('id'));
    }
}

function loadPage($list, page) {
    if (!$list.length) {
        bimp_msg('Erreur technique: identifiant de la liste invalide', 'danger');
        return;
    }

    $list.find('input[name=param_p]').val(page);

    reloadObjectList($list.attr('id'));
}

function deactivateSorting($list) {
    var $row = $list.find('thead').find('tr.headerRow');
    $row.find('.sortTitle').addClass('deactivated').removeClass('active');
}

function activateSorting($list) {
    var $row = $list.find('thead').find('tr.headerRow');
    $row.find('.sortTitle').removeClass('deactivated');
}

// Gestion des inputs:

function resetListAddObjectRow(list_id) {
    var $row = $('#' + list_id + '_addObjectRow');
    if ($row.length) {
        var $containers = $row.find('.inputContainer');
        $containers.each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                var $input = $(this).find('[name=' + field_name + ']');
                if ($input.length) {
                    var defval = $(this).data('default_value');
                    if (defval !== 'undefined') {
                        $input.val(defval);
                    } else {
                        if ($input.hasClass('switch')) {
                            $(this).val(0);
                        } else {
                            $(this).val('');
                        }
                    }
                }
            }

        });
        $row.find('.ui-autocomplete-input').val('');
        $row.find('.search_list_input').val('');
        $row.find('.select2-chosen').text('');
        $row.find('.bs_datetimepicker').each(function () {
            $(this).data('DateTimePicker').clear();
            $(this).parent().find('.datepicker_value').val('');
        });
        $row.find('.ui-autocomplete-input').val('');
    }
}

function resetListSearchInputs(list_id) {
    var $row = $('#' + list_id + '_searchRow');
    if ($row.length) {
        $row.find('.searchInputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                $(this).find('[name=' + field_name + ']').val('');
            }
        });
        $row.find('.ui-autocomplete-input').val('');
        $row.find('.search_list_input').val('');
        $row.find('.select2-chosen').text('');
        $row.find('.bs_datetimepicker').each(function () {
            $(this).data('DateTimePicker').clear();
            $(this).parent().find('.datepicker_value').val('');
        });
        $row.find('.search_input_selected_label').html('').hide();
    }
    reloadObjectList(list_id);
}

// Gestion des événements:

function onListLoaded($list) {
    if (!$list.length) {
        return;
    }

//    bimp_msg($list.attr('id'));

    if (!parseInt($list.data('loaded_event_processed'))) {
        $list.data('loaded_event_processed', 1);

        $list.find('input[name="param_n"]').change(function () {
            reloadObjectList($list.attr('id'));
        });

        var $tools = $list.find('.headerTools');
        if ($tools.length) {
            $tools.find('.openSearchRowButton').click(function () {
                var $searchRow = $list.find('.listSearchRow');
                if ($searchRow.length) {
                    if ($(this).hasClass('action-open')) {
                        $searchRow.stop().fadeIn(150);
                        $(this).removeClass('action-open').addClass('action-close');
                    } else {
                        $searchRow.stop().fadeOut(150);
                        $(this).removeClass('action-close').addClass('action-open');
                    }
                }
            });
            $tools.find('.openAddObjectRowButton').click(function () {
                var $addRow = $list.find('.addObjectRow');
                if ($addRow.length) {
                    if ($(this).hasClass('action-open')) {
                        $addRow.stop().fadeIn(150);
                        $(this).removeClass('action-open').addClass('action-close');
                    } else {
                        $addRow.stop().fadeOut(150);
                        $(this).removeClass('action-close').addClass('action-open');
                    }
                }
            });
            $tools.find('.activatePositionsButton').click(function () {
                var $handles = $list.find('.positionHandle');
                if ($handles.length) {
                    if ($(this).hasClass('action-open')) {
                        $handles.show();
                        $(this).removeClass('action-open').addClass('action-close');
                        deactivateSorting($list);
                        $list.find('input[name=param_p]').val('1');
                        sortListByPosition($list.attr('id'), true);
                    } else {
                        $handles.hide();
                        $(this).removeClass('action-close').addClass('action-open');
                        activateSorting($list);
                    }
                }
            });
            $tools.find('input[name="select_n"]').change(function () {
                var n = parseInt($(this).val());
                $list.find('input[name="param_n"]').val(n).change();
            });

            $tools.find('.refreshListButton').click(function () {
                reloadObjectList($list.attr('id'));
            });

            $list.find('input[name="param_n"]').change(function () {
                var val = parseInt($(this).val());
                var select_val = parseInt($tools.find('input[name="select_n"]').val());
                if (val !== select_val) {
                    $tools.find('input[name="select_n"]').val(val).change();
                }
            });
        }

        $list.find('tr.listSearchRow').each(function () {
            $(this).find('.searchInputContainer').each(function () {
                var field_name = $(this).data('field_name');
                if (field_name) {
                    $(this).find('[name=' + field_name + ']').val('');
                }
            });
            $(this).find('.ui-autocomplete-input').val('');
            setSearchInputsEvents($list);
        });

//        $list.find('tbody').find('a').each(function () {
//            $(this).attr('target', '_blank');
//        });

        $list.find('tbody.listRows').children('tr.objectListItemRow').each(function () {
            checkRowModifications($(this));
        });

        setCommonEvents($('#' + $list.attr('id') + '_container'));
        setInputsEvents($list);
        setListEditInputsEvents($list);
        setPositionsHandlesEvents($list);
        setPaginationEvents($list);

        if (!$list.data('object_change_event_init')) {
            var module = $list.data('module');
            var object_name = $list.data('object_name');

            var objects = $list.data('objects_change_reload');
            if (objects) {
                objects = objects.split(',');
            } else {
                objects = [];
            }

            if (!$('body').data($list.attr('id') + '_object_events_init')) {
                $('body').on('objectChange', function (e) {
//                    bimp_msg($list.attr('id') + ' => ' + e.module + ', ' + module + ', ' + e.object_name + ', ' + object_name);
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectList($list.attr('id'));
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectList($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').on('objectDelete', function (e) {
                    if ((e.module === module) && (e.object_name === object_name)) {
                        reloadObjectList($list.attr('id'));
                    } else if (objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectList($list.attr('id'));
                            }
                        }
                    }
                });
                $('body').data($list.attr('id') + '_object_events_init', 1);
            }

            $list.data('object_change_event_init', 1);
        }
    }
}

function onListRefeshed($list) {
//    $list.find('tbody').find('a').each(function () {
//        $(this).attr('target', '_blank');
//    });

    var $tbody = $list.find('tbody.listRows');

    $tbody.find('a').each(function () {
//        $(this).attr('target', '_blank');
        var link_title = $(this).attr('title');
        if (link_title) {
            $(this).removeAttr('title');
            $(this).popover({
                trigger: 'hover',
                content: link_title,
                placement: 'bottom',
                html: true
            });
        }
    });

    $list.find('tbody.listRows').children('tr.objectListItemRow').each(function () {
        checkRowModifications($(this));
    });

    setCommonEvents($tbody);
    setInputsEvents($tbody);
    setListEditInputsEvents($list);
    setPositionsHandlesEvents($list);

    $list.trigger('listRefresh');
}

function setSearchInputsEvents($list) {
    if ($list.length) {
        $list.find('.searchInputContainer').each(function () {
            if (!parseInt($(this).data('event_init'))) {
                $(this).data('event_init', 1);
                var field_name = $(this).data('field_name');
                var search_type = $(this).data('search_type');
                if (field_name) {
                    switch (search_type) {
                        case 'value_part':
                            var $input = $(this).find('[name=' + field_name + ']');
                            if ($input.length) {
                                var search_on_key_up = $(this).data('search_on_key_up');
                                if (typeof (search_on_key_up) === 'undefined') {
                                    search_on_key_up = 0;
                                }
                                if (parseInt(search_on_key_up)) {
//                                    var min_chars = $(this).data('min_chars');
//                                    if (typeof (min_chars) === 'undefined') {
//                                        min_chars = 0;
//                                    }
                                    $input.keyup(function () {
//                                        var val = '' + $input.val();

//                                        if (val.length >= min_chars) {
                                        reloadObjectList($list.attr('id'));
//                                        }
                                    });
                                } else {
                                    $input.change(function () {
                                        reloadObjectList($list.attr('id'));
                                    });
                                }
                            }
                            break;

                        case 'time_range':
                        case 'date_range':
                        case 'datetime_range':
                            setDateRangeEvents($(this), field_name);
                            var $from = $(this).find('[name=' + field_name + '_from]');
                            var $to = $(this).find('[name=' + field_name + '_to]');
                            $from.add($to).change(function () {
                                reloadObjectList($list.attr('id'));
                            });
                            break;

                        case 'field_value':
                        default:
                            var $input = $(this).find('[name=' + field_name + ']');
                            if ($input.length) {
                                $input.change(function () {
                                    reloadObjectList($list.attr('id'));
                                });
                            }
                            break;
                    }
                }
            }
        });
    }
}

function setListEditInputsEvents($list) {
    var $rows = $list.find('tbody.listRows').find('.objectListItemRow');
    if ($rows.length) {
        $rows.each(function () {
            var $row = $(this);
            $row.find('.item_check').change(function () {
                if ($(this).prop('checked')) {
                    $row.addClass('selected');
                } else {
                    $row.removeClass('selected');
                }
            });

            $(this).find('.inputContainer').each(function () {
                var field_name = $(this).data('field_name');
                if (field_name) {
                    var $input = $(this).find('[name="' + field_name + '"]');
                    if ($input.length) {
                        if (!$input.data('list_row_change_event_init')) {
                            $input.change(function () {
                                checkRowModifications($row);
                            });
                            $input.keyup(function () {
                                $(this).change();
                            });
                            $input.data('list_row_change_event_init', 1);
                        }
                    }
                }
            });
        });
    }
}

function setPaginationEvents($list) {
    if (!$list.length) {
        return;
    }

    var $container = $('#' + $list.attr('id') + '_container');
    if (!$container.length) {
        return;
    }
    $container.find('div.listPagination').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).data('event_init', 1);
        }
        var p = $(this).find('.pageBtn.active').data('p');
        if (p) {
            p = parseInt(p);
        } else {
            p = 1;
        }
        var $prev = $((this)).find('.prevButton');
        if ($prev.length) {
            if (!$prev.hasClass('disabled')) {
                $prev.click(function () {
                    if (p <= 1) {
                        return;
                    }
                    loadPage($list, p - 1);
                });
            }
        }
        var $next = $((this)).find('.nextButton');
        if ($next.length) {
            if (!$next.hasClass('disabled')) {
                $next.click(function () {
                    loadPage($list, p + 1);
                });
            }
        }
        $(this).find('.pageBtn').each(function () {
            if (!$(this).hasClass('active')) {
                $(this).click(function () {
                    loadPage($list, parseInt($(this).data('p')));
                });
            }
        });
    });
}

function setPositionsHandlesEvents($list) {
    var $tbody = $list.find('tbody.listRows');
    var $handles = $tbody.find('td.positionHandle');
    if ($list.find('.headerTools').find('.activatePositionsButton').hasClass('action-close')) {
        $handles.show();
    }
    var $rows = $handles.parent('tr');
    var first_position = 1;
    if ($handles.length) {
        $tbody.sortable({
            appendTo: $tbody,
            axis: 'y',
            cursor: 'move',
            handle: 'td.positionHandle',
            items: $rows,
            opacity: 0.75,
            start: function (e, ui) {
                var $list = ui.item.findParentByClass('object_list_table');
                first_position = parseInt($list.find('tbody.listRows').find('tr.objectListItemRow').first().data('position'));
            },
            update: function (e, ui) {
                var id_object = parseInt(ui.item.data('id_object'));
                var $list = ui.item.findParentByClass('object_list_table');
                var position = 0;
                if (id_object && $list.length) {
                    var current_position = first_position;
                    var check = true;
                    $list.find('tbody.listRows').find('tr.objectListItemRow').each(function () {
                        if (check) {
                            if (parseInt($(this).data('id_object')) === id_object) {
                                position = current_position;
                            } else {
                                current_position++;
                            }
                        }
                    });
                    if (position) {
                        saveObjectPosition($list.attr('id'), id_object, position);
                        return;
                    }
                }
            }
        });
    }
}

$(document).ready(function () {
    $('.object_list_table').each(function () {
        onListLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_list_table').each(function () {
                onListLoaded($(this));
            });
        }
    });
});