// Traitements Ajax
function reloadObjectView(view_id) {
    var $view = $('#' + view_id);

    if (!$view.length) {
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
                $view.find('#' + view_id + '_panel').find('.panel-body').stop().fadeOut(250, function () {
                    $(this).html(result.html);
                    $(this).fadeIn(250);
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
    loadModalForm(view_id + '_modal', $button, $view.data('module_name'), $view.data('object_name'), form_name, $view.data('id_object'));
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
            reloadObjectView(view_id);
        }, function () {
            $button.removeClass('disabled');
        });
    }
}

function displayObjectView($container, module_name, object_name, view_name, id_object) {
    if (!$container.length) {
        return;
    }

    var data = {
        'module_name': module_name,
        'object_name': object_name,
        'view_name': view_name,
        'id_object': id_object,
        'content_only': 0
    };

    $container.html(renderLoading('Chargement'));
    $container.find('.content-loading').show();
    $container.slideDown(250);

    bimp_json_ajax('loadObjectView', data, $container, function (result) {
        if (typeof (result.html) !== 'undefined') {
            if (result.html) {
                $container.html(result.html).show();
            }
        }
    }, null, false);
}

// Gestion des événements

function onViewLoaded($view) {
    if (!$view.length) {
        return;
    }

    $view.find('.modal').modal();

    setCommonEvents($view);
}

$(document).ready(function () {
    $('.objectView').each(function () {
        onViewLoaded($(this));
    });
});