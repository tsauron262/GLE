// Traitements Ajax

function reloadObjectView(view_id) {
    var $view = $('#' + view_id);

    if (!$view.length) {
        return;
    }

    if ($view.hasClass('no_reload')) {
        return;
    }

    var new_values = {};

    $view.find('.object_fields_table').each(function () {
        $(this).find('.objectFieldsTable').children('tbody').children('tr').each(function () {
            if ($(this).hasClass('modified')) {
                var $inputContainer = $(this).find('.inputContainer');
                if ($inputContainer.length && !$inputContainer.hasClass('no-modified')) {
                    var field_name = $inputContainer.data('field_name');
                    if (field_name) {
                        var $input = $inputContainer.find('[name="' + field_name + '"]');
                        if ($input.length) {
                            new_values[field_name] = $input.val();
                        }
                    }
                }
            }
        });
    });

    var data = {
        'module': $view.data('module'),
        'object_name': $view.data('object_name'),
        'view_name': $view.data('name'),
        'id_object': $view.data('id_object'),
        'content_only': 1,
        'new_values': new_values
    };

    BimpAjax('loadObjectView', data, null, {
        $view: $view,
        display_success: false,
        success: function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined') {
                if (result.html) {
                    bimpAjax.$view.find('.object_view_content').stop().fadeOut(250, function () {
                        $(this).html(result.html);
                        $(this).fadeIn(250, function () {
                            onViewRefreshed(bimpAjax.$view);
                        });
                    });
                }
            }
            if (typeof (result.header_html) !== 'undefined') {
                if (result.header_html) {
                    var $header = $('#' + bimpAjax.$view.data('object_name') + '_' + bimpAjax.$view.data('id_object') + '_header');
                    if ($header.length) {
                        $header.html(result.header_html);
                    }
                }
            }
        }
    });
}

function loadModalFormFromView(view_id, form_name, $button, title) {
    var $view = $('#' + view_id);
    if (!$view.length) {
        return;
    }

    var data = {
        'module': $view.data('module'),
        'object_name': $view.data('object_name'),
        'id_object': $view.data('id_object'),
        'form_name': form_name
    };

    loadModalForm($button, data, title);
}

function loadModalView(module, object_name, id_object, view_name, $button, title) {
    if (typeof (title) === 'undefined' || !title) {
        title = '<i class="far fa5-file iconLeft"></i>';
        if (object_labels[object_name] && typeof (object_labels[object_name].name) !== 'undefined') {
            title += object_labels[object_name].name;
        } else {
            title += 'Objet "' + object_name + '"';
        }
        if (id_object) {
            title += ' n°' + id_object;
        }
    }

    var data = {
        'module': module,
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'panel': 0,
    };

    bimpModal.loadAjaxContent($button, 'loadObjectView', data, title, null, function (result, bimpAjax) {
        var $new_view = bimpAjax.$resultContainer.find('#' + result.view_id);
        if ($new_view.length) {
            $new_view.data('modal_idx', bimpAjax.$resultContainer.data('idx'));
            bimpModal.removeComponentContent($new_view.attr('id'));
            onViewLoaded($new_view);
        }
    }, {}, 'large');
}

function deleteObjectFromView(view_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $view = $('#' + view_id);

    if (!$view.length) {
        return;
    }

    $button.addClass('disabled');
}

function saveObjectFromViewModalForm(view_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var $view = $('#' + view_id);

    if (!$view.length) {
        return;
    }

    var $modal = $('#' + view_id + '_modal');
    var $formContainer = $modal.find('.formContainer');
    if ($formContainer.length) {
        var $resultContainer = $formContainer.find('.ajaxResultContainer');
        var $form = $formContainer.find('.objectForm');
        if ($form.length) {
            var data = $form.serialize();
        }

        BimpAjax('saveObject', data, $resultContainer, {
            $button: $button,
            $view: $view,
            success: function (result, bimpAjax) {
                $('body').trigger($.Event('objectChange', {
                    module: bimpAjax.$view.data('module'),
                    object_name: bimpAjax.$view.data('object_name'),
                    id_object: bimpAjax.$view.data('id_object')
                }));
            }
        });
    }
}

function saveObjectfromFieldsTable(fields_table_id, $button) {
    var $container = $button.findParentByClass('object_fields_table_container');

    var $fieldsTable = $container.find('#' + fields_table_id);
    if (!$fieldsTable.length) {
        $fieldsTable = $('#' + fields_table_id);
    }
    if (!$fieldsTable.length) {
        bimp_msg('Erreur: liste des champs non trouvée', 'danger');
        return;
    }

    var $resultContainer = $fieldsTable.find('#' + fields_table_id + '_result');

    var data = getInputsValues($fieldsTable);

    console.table(data);

    data['module'] = $fieldsTable.data('module');
    data['object_name'] = $fieldsTable.data('object_name');
    data['id_object'] = $fieldsTable.data('id_object');

    BimpAjax('saveObject', data, $resultContainer, {
        $button: $button,
        success: function (result) {
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        }
    });
}

function displayObjectView($container, module_name, object_name, view_name, id_object, panel_type) {
    if (!$container.length) {
        return;
    }

    if (typeof (panel_type) === 'undefined') {
        panel_type = 'secondary';
    }

    var data = {
        'module': module_name,
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'content_only': 0,
        'panel_type': panel_type
    };

    BimpAjax('loadObjectView', data, $container, {
        append_html: true,
        display_processing: true,
        success: function (result) {
            if (typeof (result.html) !== 'undefined') {
                if (result.html) {
                    var $viewContainer = $('#' + result.view_id + '_container');
                    if ($viewContainer.length) {
                        var button = '<button class="btn btn-default" type="button" onclick="';
                        button += 'closeObjectView(\'' + $container.attr('id') + '\')';
                        button += '"><i class="fa fa-times iconLeft"></i>Fermer</button>';
                        $viewContainer.find('.panel-footer .panelFooterButtons').append(button);
                    }
                    onViewLoaded($('#' + result.view_id));
                }
            }
        }
    });
}

function closeObjectView(container_id) {
    var $container = $('#' + container_id);
    if ($container.length) {
        $container.stop().slideUp(250, function () {
            $container.html('');
        });
    }
}

function loadModalObjectPage($button, url, title) {
    bimpModal.loadIframe($button, url, title);
}

// Actions 
function checkFieldsTableModifications($fieldsTable) {
    if (!$fieldsTable.length) {
        return;
    }

    var hasModifications = false;
    $fieldsTable.find('.inputContainer').each(function () {
        if (!$(this).hasClass('no-modified')) {
            var field_name = $(this).data('field_name');
            if (field_name) {
                var $input = $(this).find('[name="' + field_name + '"]');
                if ($input.length) {
                    var $row = $(this).findParentByTag('tr');
                    var initial_value = $(this).data('initial_value');

                    if (typeof (initial_value) === 'string' && initial_value) {
                        initial_value = bimp_htmlDecode(initial_value);
                    }

                    if ($input.val() != initial_value) {
                        $row.addClass('modified');
                        hasModifications = true;
                    } else {
                        $row.removeClass('modified');
                    }
                }
            }
        }
    });

    var $container = $fieldsTable.findParentByClass('object_fields_table_container');

    if ($container.length) {
        var $footer = $container.find('.fieldsTableFooter');
        if ($footer.length) {
            if (hasModifications) {
                $footer.find('.saveButton').show();
                $footer.find('.cancelmodificationsButton').show();
            } else {
                $footer.find('.saveButton').hide();
                $footer.find('.cancelmodificationsButton').hide();
            }
        }
    }
}

function cancelFieldsTableModifications(fields_table_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $fieldsTable = $('#' + fields_table_id);
    if (!$fieldsTable.length) {
        bimp_msg('Erreur: liste des champs non trouvée', 'danger');
        return;
    }

    $fieldsTable.find('.inputContainer').each(function () {
        var field_name = $(this).data('field_name');
        if (field_name) {
            var $input = $(this).find('[name="' + field_name + '"]');


            var initial_value = $(this).data('initial_value');

            if (typeof (initial_value) === 'string' && initial_value) {
                initial_value = bimp_htmlDecode(initial_value);
            }

            if ($input.length) {
                if ($input.val() != initial_value) {
                    if ($input.hasClass('datepicker_value')) {
                        if (!initial_value) {
                            $input.val('').change();
                            $input.parent().find('input.bs_datetimepicker').val('');
                        } else {
                            $input.parent().find('input.bs_datetimepicker').data('DateTimePicker').date(moment(initial_value));
                        }
                    } else {
                        $input.val(initial_value).change();
                    }
                }
            }
        }
    });

    $button.removeClass('disabled');
}

// Gestion des événements

function onViewLoaded($view) {
    if (!$view.length) {
        return;
    }

    if (!parseInt($view.data('loaded_event_processed'))) {
        $view.data('loaded_event_processed', 1);
        $view.find('.modal').modal();

        setCommonEvents($('#' + $view.attr('id') + '_container'));
        setInputsEvents($view);

        $view.find('.object_fields_table').each(function () {
            setFieldsTableEvents($(this));
        });

        $view.find('.object_list_table').each(function () {
            onListLoaded($(this));
        });

        $view.find('.object_form').each(function () {
            onFormLoaded($(this));
        });

        $view.find('.object_view').each(function () {
            onViewLoaded($(this));
        });

        $view.find('.object_list_views').each(function () {
            onViewsListLoaded($(this));
        });

        $view.find('.object_list_custom').each(function () {
            onListCustomLoaded($(this));
        });

        if (!$view.data('object_change_event_init')) {
            var objects = $view.data('objects_change_reload');
            if (objects) {
                objects = objects.split(',');
            }

            if (!$('body').data($view.attr('id') + '_object_events_init')) {
                $('body').on('objectChange', function (e) {
                    if ((e.module === $view.data('module')) && (e.object_name === $view.data('object_name'))
                            && parseInt(e.id_object) === parseInt($view.data('id_object'))) {
                        reloadObjectView($view.attr('id'));
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectView($view.attr('id'));
                            }
                        }
                    }
                });

                if (objects && objects.length) {
                    $('body').on('objectDelete', function (e) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                reloadObjectView($view.attr('id'));
                            }
                        }
                    });
                }
                $('body').data($view.attr('id') + '_object_events_init', 1);
            }

            $view.data('object_change_event_init', 1);
        }

        $('body').trigger($.Event('viewLoaded', {
            $view: $view
        }));
    }
}

function onViewRefreshed($view) {
    $view.find('.object_fields_table').each(function () {
        setInputsEvents($(this));
        setFieldsTableEvents($(this));
    });

    $view.find('.object_list_table').each(function () {
        onListLoaded($(this));
    });

    $view.find('.obejct_form').each(function () {
        onFormLoaded($(this));
    });

    $view.find('.object_view').each(function () {
        onViewLoaded($(this));
    });

    $view.find('.object_list_views').each(function () {
        onViewsListLoaded($(this));
    });

    setCommonEvents($view);
    setInputsEvents($view);
    $('body').trigger($.Event('viewRefresh', {
        $view: $view
    }));
}

function setFieldsTableEvents($fieldsTable) {
    if (!parseInt($fieldsTable.data('events_init'))) {
        $fieldsTable.find('.inputContainer').each(function () {
            var field_name = $(this).data('field_name');
            if (field_name) {
                var $input = $(this).find('[name="' + field_name + '"]');
                if ($input.length) {
                    $input.on('change', function () {
                        checkFieldsTableModifications($fieldsTable);
                    });
                    $input.keyup(function () {
                        $input.change();
                    });
                }
            }
        });
        checkFieldsTableModifications($fieldsTable);
        $fieldsTable.data('events_init', 1);
    }
}

$(document).ready(function () {
    $('body').on('bimp_ready', function (e) {
        $('.object_view').each(function () {
            onViewLoaded($(this));
        });
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_view').each(function () {
                onViewLoaded($(this));
            });
        }
    });
});
