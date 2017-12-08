// Traitements Ajax

function reloadObjectView(view_id) {
    var $view = $('#' + view_id);

    if (!$view.length) {
        return;
    }

    if ($view.hasClass('no_reload')) {
        return;
    }

    var data = {
        'module_name': $view.data('module_name'),
        'object_name': $view.data('object_name'),
        'view_name': $view.data('view_name'),
        'id_object': $view.data('id_object'),
        'content_only': 1
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
    loadModalForm($button, $view.data('module_name'), $view.data('object_name'), form_name, $view.data('id_object'));
}

function loadModalView(view_id, view_name, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $view = $('#' + view_id);
    if (!$view.length) {
        return;
    }

    var id_object = $view.data('id_object');
    var object_name = $view.data('object_name');

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
        'object_module': $view.data('module'),
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'content_only': 1
    };

    bimp_json_ajax('loadObjectView', data, null, function (result) {
        $modal.find('.content-loading').hide();
        if (!isCancelled) {
            if (!bimp_display_result_errors(result, $resultContainer)) {
                if (typeof (result.html) !== 'undefined') {
                    $resultContainer.html(result.html).slideDown(250);
                    var $new_view = $resultContainer.find('.objectView');
                    if ($new_view.length) {
                        $new_view.each(function () {
                            onViewLoaded($new_view);
                        });
                    }
                }
            }
            $modal.modal('handleUpdate');
        }

    }, function (result) {
        $modal.find('.content-loading').hide();
        if (!bimp_display_result_errors(result, $resultContainer)) {
            bimp_display_msg('Echec du chargement du contenu', $resultContainer, 'danger');
        }
        $modal.modal('handleUpdate');
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

function saveObjectfromFieldsTable(table_id, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $table = $('#' + table_id);
    if ($table.length) {
        var $view = $table.parent();
        while (1) {
            if ($view.hasClass('objectView')) {
                break;
            }
            if (!$view.length) {
                break;
            }
            $view = $view.parent();
        }

        if ($view.length) {
            var $resultContainer = $table.find('.ajaxResultsContainer');

            $resultContainer.parent('td').parent('tr').show();
            var data = {
                'object_module': $view.data('module_name'),
                'object_name': $view.data('object_name'),
                'id_object': $view.data('id_object')
            };

            $table.find('.inputContainer').each(function () {
                var field = $(this).data('field_name');
                var $input = $(this).find('[name="' + field + '"]');
                if ($input.length) {
                    data[field] = $input.val();
                }
            });
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

    bimp_json_ajax('loadObjectView', data, $container, function (result) {
        if (typeof (result.html) !== 'undefined') {
            if (result.html) {
                $container.html(result.html).show();
                onViewLoaded($container.find('#' + result.view_id));
            }
        }
    }, null, false);
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

// Gestion des événements

function onViewLoaded($view) {
    if (!$view.length) {
        return;
    }

    if (!parseInt($view.data('loaded_event_processed'))) {
        $view.data('loaded_event_processed', 1);
        $view.find('.modal').modal();

        setCommonEvents($view);

        $view.find('.objectViewtable').each(function () {
            setInputsEvents($(this));
        });

        $view.find('.objectList').each(function () {
            onListLoaded($(this));
        });

        $view.find('.objectForm').each(function () {
            onFormLoaded($(this));
        });

        $view.find('.objectView').each(function () {
            onViewLoaded($(this));
        });

        $view.find('.objectViewslist').each(function () {
            onViewsListLoaded($(this));
        });

        if (!$view.data('object_change_event_init')) {
            $('body').on('objectChange', function (e) {
                if ((e.module === $view.data('module_name')) && (e.object_name === $view.data('object_name'))
                        && parseInt(e.id_object) === parseInt($view.data('id_object'))) {
                    reloadObjectView($view.attr('id'));
                }
            });
        }

        $('body').trigger($.Event('viewLoaded', {
            $view: $view
        }));
    }
}

function onViewRefreshed($view) {
    $view.find('.objectViewtable').each(function () {
        setInputsEvents($(this));
    });

    $view.find('.objectList').each(function () {
        onListLoaded($(this));
    });

    $view.find('.objectForm').each(function () {
        onFormLoaded($(this));
    });

    $view.find('.objectView').each(function () {
        onViewLoaded($(this));
    });

    $view.find('.objectViewslist').each(function () {
        onViewsListLoaded($(this));
    });

    setCommonEvents($view);
    $('body').trigger($.Event('viewRefresh', {
        $view: $view
    }));
}

$(document).ready(function () {
    $('.objectView').each(function () {
        onViewLoaded($(this));
    });

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.objectView').each(function () {
                onViewLoaded($(this));
            });
        }
    });
});