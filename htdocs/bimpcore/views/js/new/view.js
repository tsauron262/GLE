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

    bimp_json_ajax('loadObjectView', data, null, function (result) {
        if (typeof (result.html) !== 'undefined') {
            if (result.html) {
                $view.find('.object_view_content').stop().fadeOut(250, function () {
                    $(this).html(result.html);
                    $(this).fadeIn(250, function () {
                        onViewRefreshed($view);
                    });
                });
            }
        }
    });
}

function loadModalFormFromView(view_id, form_name, $button) {
    var $view = $('#' + view_id);
    if (!$view.length) {
        return;
    }

    var data = {
        'module_name': $view.data('module_name'),
        'object_name': $view.data('object_name'),
        'id_object': $view.data('id_object'),
        'form_name': form_name
    };

    loadModalForm($button, data);
}

function loadModalView(module, object_name, id_object, view_name, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    var title = '';

    if (id_object) {
        title = '<i class="fa fa-file-o iconLeft"></i>';
        if (typeof (object_labels[object_name].name) !== 'undefined') {
            title += object_labels[object_name].name;
        } else {
            title += 'Objet "' + object_name + '"';
        }
        title += ' n°' + id_object;
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

    var data = {
        'object_module': module,
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'panel': 0
    };

    BimpAjax('loadObjectView', data, null, {
        display_success: false,
        success: function (result) {
            var $modal = $('#page_modal');
            var $resultContainer = $modal.find('.modal-ajax-content');
            $modal.find('.content-loading').hide();
            if (!isCancelled) {
                if (typeof (result.html) !== 'undefined') {
                    $resultContainer.html(result.html).slideDown(250);
                    var $new_view = $resultContainer.find('#' + result.view_id);
                    if ($new_view.length) {
                        onViewLoaded($new_view);
                    }
                }
                $modal.modal('handleUpdate');
            }
        },
        error: function (result) {
            var $modal = $('#page_modal');
            var $resultContainer = $modal.find('.modal-ajax-content');
            $modal.find('.content-loading').hide();
            if (!bimp_display_result_errors(result, $resultContainer)) {
                bimp_display_msg('Echec du chargement du contenu', $resultContainer, 'danger');
            }
            $modal.modal('handleUpdate');
        }
    });
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

    $button.addClass('disabled');

    var $modal = $('#' + view_id + '_modal');
    var $formContainer = $modal.find('.formContainer');
    if ($formContainer.length) {
        var $resultContainer = $formContainer.find('.ajaxResultContainer');
        var $form = $formContainer.find('.objectForm');
        if ($form.length) {
            var data = $form.serialize();
        }
        bimp_json_ajax('saveObject', data, $resultContainer, function () {
            $button.removeClass('disabled');
            $('body').trigger($.Event('objectChange', {
                module: $view.data('module'),
                object_name: $view.data('object_name'),
                id_object: $view.data('id_object')
            }));
        }, function () {
            $button.removeClass('disabled');
        });
    }
}

function saveObjectfromFieldsTable(fields_table_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $fieldsTable = $('#' + fields_table_id);
    if (!$fieldsTable.length) {
        bimp_msg('Erreur: liste des champs non trouvée', 'danger');
        return;
    }

    var $resultContainer = $fieldsTable.find('#' + fields_table_id + '_result');

    var data = getInputsValues($fieldsTable);

    data['module'] = $fieldsTable.data('module');
    data['object_name'] = $fieldsTable.data('object_name');
    data['id_object'] = $fieldsTable.data('id_object');

    BimpAjax('saveObject', data, $resultContainer, {
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

function displayObjectView($container, module_name, object_name, view_name, id_object, panel_type) {
    if (!$container.length) {
        return;
    }

    if (typeof (panel_type) === 'undefined') {
        panel_type = 'secondary';
    }

    var data = {
        'module_name': module_name,
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'content_only': 0,
        'panel_type': panel_type
    };

    $container.html(renderLoading('Chargement'));
    $container.find('.content-loading').show();
    $container.slideDown(250);

    BimpAjax('loadObjectView', data, $container, {
        append_html: true,
        success: function (result) {
            if (typeof (result.html) !== 'undefined') {
                if (result.html) {
                    onViewLoaded($('#' + result.view_id));
                }
            }
        }
    });
}

function loadModalObjectPage($button, url, modal_id, title) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $modal = $('#' + modal_id);
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    $modal.find('.modal-title').html(title);
    $modal.modal('show');
    $modal.find('.content-loading').show().find('.loading-text').text('Chargement');

    $modal.on('hide.bs.modal', function (e) {
        $modal.find('.extra_button').remove();
        $modal.find('.content-loading').hide();
        $button.removeClass('disabled');
    });

    var html = '<div style="overflow: hidden"><iframe id="iframe" frameborder="0" src="' + url + '" width="100%" height="800px"></iframe></div>';
    $resultContainer.html(html);

    $('#iframe').on("load", function () {
            var $head = $("iframe").contents().find("head");                
            $head.append($("<link/>", {rel: "stylesheet", href: DOL_URL_ROOT + "/bimpcore/views/css/content_only.css", type: "text/css"}));
        $modal.find('.content-loading').hide();
        $resultContainer.slideDown(250);
      });
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
                    if ($input.val() != $(this).data('initial_value')) {
                        $row.addClass('modified');
                        hasModifications = true;
                    } else {
                        $row.removeClass('modified');
                    }
                }
            }
        }
    });

    var $footer = $('#' + $fieldsTable.attr('id') + '_container').find('.panel-footer');
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
            if ($input.length) {
                if ($input.val() != initial_value) {
                    $input.val(initial_value).change();
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

        $view.find('.object_fields_table').each(function () {
            setInputsEvents($(this));
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
                $input.change(function () {
                    checkFieldsTableModifications($fieldsTable);
                });
            }
            $input.keyup(function () {
                $input.change();
            });
        });
        checkFieldsTableModifications($fieldsTable);
        $fieldsTable.data('events_init', 1);
    }

}

$(document).ready(function () {
    $('.object_view').each(function () {
        onViewLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.object_view').each(function () {
                onViewLoaded($(this));
            });
        }
    });
});