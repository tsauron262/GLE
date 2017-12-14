// Traitements Ajax:

var object_labels = {};

function reloadObjectList(list_id, callback) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var $resultContainer = $('#' + list_id + '_result');
    var object_name = $list.data('object_name');
    var id_parent_object = parseInt($list.find('#' + object_name + '_id_parent').val());

    if ((typeof (id_parent_object) === 'undefined') || !id_parent_object) {
        id_parent_object = 0;
    }

    var data = {
        'list_name': $list.data('list_name'),
        'module_name': $list.data('module_name'),
        'object_name': object_name,
        'id_parent': id_parent_object
    };

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

    var sort_col = $list.find('input[name=sort_col]').val();
    var sort_way = $list.find('input[name=sort_way]').val();
    var sort_option = $list.find('input[name=sort_option]').val();
    var n = $list.find('input[name=n]').val();
    var p = $list.find('input[name=p]').val();

    if (sort_col) {
        data['sort_col'] = sort_col;
    }
    if (sort_way) {
        data['sort_way'] = sort_way;
    }
    if (sort_option) {
        data['sort_option'] = sort_option;
    }
    if (n) {
        data['n'] = n;
    }
    if (p) {
        data['p'] = p;
    }

    if ($list.find('input[name=associations_filters]')) {
        data['associations_filters'] = $list.find('input[name=associations_filters]').val();
    }

    bimp_json_ajax('loadObjectList', data, 0, function (result) {
        if (result.rows_html) {
            $list.find('tbody.listRows').html(result.rows_html);
            var $container = $('#' + $list.attr('id') + '_container');
            if ($container.length) {
                if (result.pagination_html) {
                    $container.find('.listPagination').each(function () {
                        $(this).data('event_init', 0);
                        $(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
                    });
                    setPaginationEvents($list);
                } else {
                    $container.find('.listPagination').each(function () {
                        $(this).html('').parent('td').parent('tr.paginationContainer').hide();
                    });
                }
            }

            onListRefeshed($list);

            if (typeof (callback) === 'function') {
                callback(true);
            }
        } else {
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    }, function (result) {
        if (!bimp_display_result_errors(result, $resultContainer)) {
            bimp_display_msg('La liste des ' + object_labels[object_name].name_plur + ' n\'a pas pu être rechargée', $resultContainer, 'danger');
            if (typeof (callback) === 'function') {
                callback(false);
            }
        }
    });
}

function loadModalFormFromList(list_id, form_name, $button, id_object, id_parent) {
    var $list = $('#' + list_id);
    if (!$list.length) {
        return;
    }

    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    var data = {
        'module_name': $list.data('module_name'),
        'object_name': $list.data('object_name'),
        'form_name': form_name,
        'id_object': id_object,
        'id_parent': id_parent
    };

    var $values_input = $list.find('input[name=' + form_name + '_add_form_values]');
    if ($values_input.length) {
        data.values = $values_input.val();
    }

    var $asso_filters_input = $list.find('input[name=associations_filters]');
    if ($asso_filters_input.length) {
        data['associations_params'] = $asso_filters_input.val();
    }

    loadModalForm($button, data);
}

function updateObjectFromRow(list_id, id_object, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var object_name = $list.data('object_name');

    var $resultContainer = $('#' + list_id + '_result');
    var $row = $list.find('tbody').find('#' + object_name + '_row_' + id_object);

    if (!$row.length) {
        bimp_display_msg('Erreur technique: liste non trouvée', $resultContainer, 'danger');
        return;
    }

    $button.addClass('disabled');

    var data = {
        'list_name': $list.data('list_name'),
        'module_name': $list.data('module_name'),
        'object_name': object_name,
        'id_object': id_object
    };

    $row.find('.editInputContainer').each(function () {
        var field_name = $(this).data('field_name');
        if (field_name) {
            var val = $(this).find('[name=' + field_name + ']').val();
            if (typeof (val) !== 'undefined') {
                data[field_name] = val;
            }
        }
    });

    bimp_json_ajax('saveObject', data, $resultContainer, function (result) {
        $button.removeClass('disabled');
        $('body').trigger($.Event('objectChange', {
            module: $list.data('module_name'),
            object_name: object_name,
            id_object: id_object
        }));
    }, function (result) {
        $button.removeClass('disabled');
    });
}

function addObjectFromList(list_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var $result = $('#' + list_id + '_result');
    var $row = $('#' + list_id + '_addObjectRow');

    if (!$row.length) {
        if ($result.length) {
            bimp_display_msg('Aucun formulaire trouvé', $result, 'danger');
        }
        return;
    }

    $button.addClass('disabled');

    var data = {
        'list_name': $list.data('list_name'),
        'module_name': $list.data('module_name'),
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

    bimp_json_ajax('saveObject', data, $result, function (result) {
        if (!result.errors.length) {
            resetListAddObjectRow(list_id);
            $button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: $list.data('module_name'),
                object_name: $list.data('object_name'),
                id_object: result.id_object
            }));
        }
    }, function (result) {
        $button.removeClass('disabled');
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
            'object_name': object_name,
            'objects': objects_list
        };

        bimp_json_ajax('deleteObjects', data, $resultContainer, function (result) {
            $button.removeClass('disabled');
            for (var i in objects_list) {
                $('body').trigger($.Event('objectDelete', {
                    module: $list.data('module_name'),
                    object_name: $list.data('object_name'),
                    id_object: objects_list[i]
                }));
            }
        }, function (result) {
            $button.removeClass('disabled');
        });
    }
}

function deleteSelectedObjects(list_id, $button) {
    var $list = $('#' + list_id);

    if (!$list.length) {
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
        bimp_display_msg(msg, $resultContainer, 'danger');
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
        return;
    }

    var data = {
        'module_name': $list.data('module_name'),
        'object_name': $list.data('object_name'),
        'id_object': id_object,
        'position': position
    };

    bimp_json_ajax('saveObjectPosition', data, null, function () {
        sortListByPosition($list.attr('id'));
    }, function () {
        sortListByPosition($list.attr('id'));
    });
}

function toggleSelectedItemsAssociation(list_id, operation, association, id_associate) {
    var $list = $('#' + list_id);

    if (!$list.length) {
        return;
    }

    var $resultContainer = $('#' + list_id + '_result');
    var $selected = $list.find('tbody').find('input.item_check:checked');
    var module = $list.data('module_name');
    var object_name = $list.data('object_name');

    if (!$selected.length) {
        var msg = '';
        if (object_labels[object_name]['is_female']) {
            msg = 'Aucune ' + object_labels[object_name]['name'] + ' sélectionnée';
        } else {
            msg = 'Aucun ' + object_labels[object_name]['name'] + ' sélectionné';
        }
        bimp_display_msg(msg, $resultContainer, 'danger');
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

function toggleCheckAll(list_id, $input) {
    var $inputs = $('#' + list_id).find('tbody').find('input.item_check');
    if ($input.prop('checked')) {
        $inputs.each(function () {
            $(this).attr('checked', 1);
        });
    } else {
        $inputs.each(function () {
            $(this).removeAttr('checked').removeProp('checked');
            var html = $(this).parent('td').html();
            $(this).parent('td').html(html);
        });
    }
}

function sortList(list_id, col_name) {
    var $row = $('#' + list_id).find('.headerRow');
    if ($row.length) {
        var $span = $row.find('#' + col_name + '_sortTitle');
        if ($span.length) {
            var $list = $('#' + list_id);
            var prev_sort_col = $list.find('input[name=sort_col]').val();
            var prev_sort_way = $list.find('input[name=sort_way]').val();
            var prev_sort_option = $list.find('input[name=sort_option]').val();
            $list.find('input[name=sort_col]').val($span.parent('th').data('col_name'));
            if ($span.hasClass('sorted-asc')) {
                $list.find('input[name=sort_way]').val('desc');
            } else {
                $list.find('input[name=sort_way]').val('asc');
            }
            var sort_option = $span.data('sort_option');
            if (!sort_option) {
                sort_option = '';
            }
            $list.find('input[name=sort_option]').val(sort_option);
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
                    $list.find('input[name=sort_col]').val(prev_sort_col);
                    $list.find('input[name=sort_way]').val(prev_sort_way);
                    $list.find('input[name=sort_option]').val(prev_sort_option);
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
        $list.find('input[name=sort_col]').val('position');
        $list.find('input[name=sort_way]').val('asc');
        $list.find('input[name=sort_option]').val('');
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
        return;
    }

    $list.find('input[name=p]').val(page);
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
    }
    reloadObjectList(list_id);
}

// Gestion des événements:

function onListLoaded($list) {
    if (!$list.length) {
        return;
    }

    if (!parseInt($list.data('loaded_event_processed'))) {
        $list.data('loaded_event_processed', 1);

        $list.find('#' + $list.attr('id') + '_n').change(function () {
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
                        $list.find('input[name=p]').val('1');
                        sortListByPosition($list.attr('id'), true);
                    } else {
                        $handles.hide();
                        $(this).removeClass('action-close').addClass('action-open');
                        activateSorting($list);
                    }
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

        $list.find('tbody').find('a').each(function () {
            $(this).attr('target', '_blank');
        });

        setCommonEvents($('#' + $list.attr('id') + '_container'));
        setInputsEvents($list);
        setPositionsHandlesEvents($list);
        setPaginationEvents($list);

        if (!$list.data('object_change_event_init')) {
            var module = $list.data('module_name');
            var object_name = $list.data('object_name');

            var objects = $list.data('objects_change_reload');
            if (objects) {
                objects = objects.split(',');
            }

            $('body').on('objectChange', function (e) {
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

            $list.data('object_change_event_init', 1);
        }
    }
}

function onListRefeshed($list) {
    $list.find('tbody').find('a').each(function () {
        $(this).attr('target', '_blank');
    });

    var $tbody = $list.find('tbody.listRows');
    $list.find('input[name=p]').val('');

    $tbody.find('a').each(function () {
        $(this).attr('target', '_blank');
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

    setCommonEvents($tbody);
    setInputsEvents($tbody);
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
                                    var min_chars = $(this).data('min_chars');
                                    if (typeof (min_chars) === 'undefined') {
                                        min_chars = 1;
                                    }
                                    $input.keyup(function () {
                                        var val = '' + $input.val();

                                        if (val.length >= min_chars) {
                                            reloadObjectList($list.attr('id'));
                                        }
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
    var $tbody = $list.find('tbody');
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
                var $list = ui.item.parent('tbody').parent('table').parent('.objectList');
                first_position = parseInt($list.find('tbody').find('tr.objectListItemRow').first().data('position'));
            },
            update: function (e, ui) {
                var id_object = parseInt(ui.item.data('id_object'));
                var $list = ui.item.parent('tbody').parent('table').parent('.objectList');
                var position = 0;
                if (id_object && $list.length) {
                    var current_position = first_position;
                    var check = true;
                    $list.find('tbody').find('tr.objectListItemRow').each(function () {
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
    $('.objectList').each(function () {
        onListLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.objectList').each(function () {
                onListLoaded($(this));
            });
        }
    });
});