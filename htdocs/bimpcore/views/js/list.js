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
                var field = field_name.replace(/^search_(.+)$/, '$1')
                if (search_type === 'date_range') {
                    var $from = $(this).find('[name=' + field_name + '_from]');
                    var $to = $(this).find('[name=' + field_name + '_to]');
                    data['search_fields'][field] = {};
                    if ($from.length) {
                        data['search_fields'][field]['from'] = $from.val();
                    }
                    if ($to.length) {
                        data['search_fields'][field]['to'] = $to.val();
                    }
                } else {
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

    bimp_json_ajax('loadObjectList', data, 0, function (result) {
        if (result.rows_html) {
            var $addRow = $('#' + list_id + '_addObjectRow');
            var addRowHtml = '';
            if ($addRow.length) {
                resetListAddObjectRow(list_id);
                addRowHtml = '<tr class="inputsRow" id="' + list_id + '_addObjectRow">';
                addRowHtml += $addRow.html();
                addRowHtml += '</tr>';
            }
            $list.find('tbody.listRows').html(result.rows_html + addRowHtml);
            $list.find('tbody.listRows').find('a').each(function () {
                $(this).attr('target', '_blank');
            });
            $list.find('input[name=p]').val('');
            var $container = $('#' + $list.attr('id') + '_container');
            if ($container.length) {
                if (result.pagination_html) {

                    $container.find('.listPagination').each(function () {
                        $(this).html(result.pagination_html).parent('td').parent('tr.paginationContainer').show();
                    });
                    setPaginationEvents($list);
                } else {
                    $container.find('.listPagination').each(function () {
                        $(this).html('').parent('td').parent('tr.paginationContainer').hide();
                    });
                }
            }
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

    loadModalForm($button, $list.data('module_name'), $list.data('object_name'), form_name, id_object, id_parent);
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
        reloadObjectList(list_id);
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

    var data = {};

    $row.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        var $input = $(this).find('[name=' + field_name + ']');
        if ($input.length) {
            data[field_name] = $input.val();
        }
    });

    bimp_json_ajax('saveObject', data, $result, function (result) {
        if (!result.errors.length) {
            resetListRowInputs(list_id);
            $button.removeClass('disabled');
            reloadObjectList(list_id);
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
            reloadObjectList(list_id);
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

function loadPage($list, page) {
    if (!$list.length) {
        return;
    }

    $list.find('input[name=p]').val(page);
    reloadObjectList($list.attr('id'));
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
    $list.find('tbody').find('a').each(function () {
        $(this).attr('target', '_blank');
    });

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

    setListEvents($list);
    setCommonEvents($list);
}

function setListEvents($list) {
    if (!$list.length) {
        return;
    }
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
    }
    $list.find('#' + $list.attr('id') + '_n').change(function () {
        reloadObjectList($list.attr('id'));
    });
    setPaginationEvents($list);
}

function setSearchInputsEvents($list) {
    if ($list.length) {
        $list.find('.searchInputContainer').each(function () {
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

                    case 'date_range':
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

$(document).ready(function () {
    $('.objectList').each(function () {
        onListLoaded($(this));
    });
});