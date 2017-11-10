var inputsEvents = [];

function addInputEvent(form_id, input_name, event, callback) {
    inputsEvents.push({
        form_id: form_id,
        input_name: input_name,
        event: event,
        callback: callback
    });
}

// Enregistrements ajax des objets: 

function saveObjectFromForm(form_id, $button, success_callback) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $result = $('#' + form_id + '_result');
    var $form = $('#' + form_id);

    if (!$form.length) {
        var msg = 'Erreur technique: formulaire non trouvé';
        bimp_display_msg(msg, $result, 'danger');
        return;
    }

    var object_name = $form.data('object_name');
    var data = $form.serialize();

    bimp_json_ajax('saveObject', data, $result, function (result) {
        var $lists = $('.' + object_name + '_list');
        if ($lists.length) {
            $lists.each(function () {
                reloadObjectList($(this).attr('id'));
            });
        }
        var $views = $('.' + object_name + '_view');
        if ($views.length) {
            $views.each(function () {
                reloadObjectView($(this).attr('id'));
            });
        }
        $button.removeClass('disabled');

        if ((typeof (result.object_view_url) !== 'undefined') && result.object_view_url) {
            var $link = $button.parent().find('.objectViewLink');
            if ($link.length) {
                $link.removeClass('hidden').attr('href', result.object_view_url);
            }
        }

        if (typeof (success_callback) !== 'undefined') {
            if (typeof (success_callback) === 'function') {
                success_callback();
            }
        }
    }, function () {
        $button.removeClass('disabled');
    });
}

function saveObjectField(module, object_name, id_object, field, value, $resultContainer, successCallBack) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        value: value
    };

    bimp_json_ajax('saveObjectField', data, $resultContainer, function (result) {
        var $lists = $('.' + object_name + '_list');
        if ($lists.length) {
            $lists.each(function () {
                reloadObjectList($(this).attr('id'));
            });
        }
        var $views = $('.' + object_name + '_view');
        if ($views.length) {
            $views.each(function () {
                reloadObjectView($(this).attr('id'));
            });
        }
        if (typeof (successCallBack) !== 'undefined') {
            successCallBack(result);
        }
    });
}

function saveObjectAssociations(id_object, object_name, association, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $resultContainer = $('#' + object_name + '_' + association + '_associatonsAjaxResult');

    var list = [];

    $('#' + object_name + '_' + association + '_associations_list').find('input[type=checkbox]').each(function () {
        if ($(this).prop('checked')) {
            list.push(parseInt($(this).val()));
        }
    });

    var data = {
        'id_object': id_object,
        'object_name': object_name,
        'association': association,
        'list': list
    };

    bimp_json_ajax('saveObjectAssociations', data, $resultContainer, function () {
        $button.removeClass('disabled');
    }, function () {
        $button.removeClass('disabled');
    });
}

function deleteObject($button, module, object_name, id_object, $resultContainer, successCallBack) {
    if ($button.hasClass('disabled')) {
        return;
    }
    var msg = 'Voulez-vous vraiment supprimer ';

    if (typeof (object_labels[object_name]) !== 'undefined') {
        msg += object_labels[object_name]['the'] + ' n°' + id_object;
    } else {
        msg += 'cet objet?';
    }

    if (confirm(msg)) {
        $button.addClass('disabled');
        var data = {
            'module': module,
            'object_name': object_name,
            'objects': [id_object]
        };

        bimp_json_ajax('deleteObjects', data, $resultContainer, function (result) {
            $button.removeClass('disabled');
            var $lists = $('.' + object_name + '_list');
            if ($lists.length) {
                $lists.each(function () {
                    reloadObjectList($(this).attr('id'));
                });
            }
            var $views = $('.' + object_name + '_view');
            if ($views.length) {
                var html = '<div class="alert alert-danger" style="margin: 15px">';
                if (typeof (object_labels[object_name]) !== 'undefined') {
                    html += object_labels[object_name]['name'] + ' n°' + id_object + ' supprimé';
                    if (object_labels[object_name]['is_female']) {
                        html += 'e';
                    }
                } else {
                    html += 'objet supprimé';
                }
                html += '</div>';
                $views.each(function () {
                    $(this).html(html);
                });
            }
            if (typeof(successCallBack) === 'function') {
                successCallBack(result);
            }
        }, function (result) {
            $button.removeClass('disabled');
        });
    }
}

function loadModalForm(modal_id, $button, module_name, object_name, form_name, id_object, id_parent) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    var $modal = $('#' + modal_id);
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    var title = '';

    if (id_object) {
        title = '<i class="fa fa-edit iconLeft"></i>Edition ';
        if (typeof (object_labels[object_name].of_the) !== 'undefined') {
            title += object_labels[object_name].of_the;
        } else {
            title += 'l\'objet "' + object_name + '"';
        }
        title += ' n°' + id_object;
    } else {
        title = '<i class="fa fa-plus-circle iconLeft"></i>Ajout ';
        if (typeof (object_labels[object_name].of_a) !== 'undefined') {
            title += object_labels[object_name].of_a;
        } else {
            title += 'd\'un objet "' + object_name + '"';
        }
    }

    $modal.find('.modal-title').html(title);
    $modal.find('.loading-text').text('Chargement du formulaire');
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
        'module_name': module_name,
        'object_name': object_name,
        'form_name': form_name,
        'id_object': id_object,
        'id_parent': id_parent,
        'full_panel': 0
    };

    bimp_json_ajax('loadObjectForm', data, null, function (result) {
        $modal.find('.content-loading').hide();
        if (!isCancelled) {
            if (!bimp_display_result_errors(result, $resultContainer)) {
                if (typeof (result.html) !== 'undefined') {
                    $resultContainer.html(result.html).slideDown(250);

                    var button_html = '<button type="button" class="extra_button btn btn-primary"';
                    button_html += ' onclick="saveObjectFromForm(\'' + result.form_id + '\', $(this));">';
                    button_html += '<i class="fa fa-save iconLeft"></i>Enregistrer</button>';
                    $modal.find('.modal-footer').append(button_html);

                    button_html = '<a class="hidden objectViewLink extra_button btn btn-primary" href="">';
                    button_html += '<i class="fa fa-file-o iconLeft"></i>Afficher</a>';
                    $modal.find('.modal-footer').append(button_html);

                    var $form = $resultContainer.find('.objectForm');
                    if ($form.length) {
                        $form.each(function () {
                            onFormLoaded($form);
                        });
                    }
                }
            }
            $modal.modal('handleUpdate');
        }

    }, function (result) {
        $modal.find('.content-loading').hide();
        if (!bimp_display_result_errors(result, $resultContainer)) {
            bimp_display_msg('Echec du chargement du formulaire', $resultContainer, 'danger');
        }
        $modal.modal('handleUpdate');
    });
}

// Gestion des formulaires objets: 

function reloadObjectInput(form_id, input_name, fields) {
    var $form = $('#' + form_id);
    if (!$form.length) {
        return;
    }

    var object_module = $form.data('object_module');
    var object_name = $form.data('object_name');

    var $container = $form.find('#' + form_id + '_' + input_name);

    if (!$container.length) {
        return;
    }

    var $input = $container.find('[name=' + input_name + ']');

    var data = {
        object_module: object_module,
        object_name: object_name,
        field_name: input_name,
        fields: fields
    };

    bimp_json_ajax('loadObjectInput', data, $container, function (result) {
        if (typeof (result.html) !== 'undefined') {
            $container.html(result.html).slideDown(250);
            if ($input.length) {
                setInputEvents($form, $input);
            }
        }
    }, 'Echec du chargement du champ');
}

function searchObjectList($input) {
    var data = {
        'table': $input.data('table'),
        'fields_search': $input.data('fields_search'),
        'field_return_value': $input.data('field_return_value'),
        'field_return_label': $input.data('field_return_label'),
        'join': $input.data('join'),
        'join_on': $input.data('join_on'),
        'join_return_label': $input.data('join_return_label'),
        'value': $input.val()
    };

    var $container = $input.parent('div');
    var $spinner = $container.find('.loading');
    var $result = $container.find('.search_input_results');

    $spinner.addClass('active');
    $result.html('').hide();

    bimp_json_ajax('searchObjectlist', data, null, function (result) {
        $result.html('').hide();
        $spinner.removeClass('active');
        if (typeof (result.list) !== 'undefined') {
            if (result.list.length) {
                for (var i in result.list) {
                    var html = '<button type="button" class="btn btn-light-default"';
                    html += ' data-value="' + result.list[i].value + '">';
                    html += result.list[i].label;
                    if (result.list[i].join_label) {
                        html += ' (' + result.list[i].join_label + ')';
                    }
                    html += '</button>';
                    $result.append(html);
                }
                var field_name = $container.data('field_name');
                var $field_input = $container.find('[name=' + field_name + ']');
                $result.find('button').click(function () {
                    $field_input.val($(this).data('value')).change();
                    $result.html('').hide();
                    $input.val($(this).text());
                });
                $result.show();
                $result.off('mouseleave');
                $result.mouseenter(function () {
                    $(this).off('mouseenter');
                    $(this).mouseleave(function () {
                        $(this).slideUp(250);
                    });
                });
            }
        }
    }, function () {
        $result.html('').hide();
        $spinner.removeClass('active');
    });
}

function getFieldValue($form, field_name) {
    if (!$form.length) {
        return '';
    }

    var $inputContainer = $form.find('#' + $form.attr('id') + '_' + field_name);
    var $input = $inputContainer.find('[name=' + field_name + ']');
    if ($input.length) {
        return $input.val();
    }
    return '';
}

// Gestion de l'affichage conditionnel des champs: 

function toggleInputDisplay($container, $input) {
    var input_val = $input.val();
    var show_values = $container.data('show_values');
    var hide_values = $container.data('hide_values');
    var show = false;
    if (typeof (show_values) !== 'undefined') {
        show_values = show_values.split(',');
        for (i in show_values) {
            if (input_val == show_values[i]) {
                show = true;
                break;
            }
        }
    } else if (typeof (hide_values) !== 'undefined') {
        show = true;
        hide_values = hide_values.split(',');
        for (i in hide_values) {
            if (input_val == hide_values[i]) {
                show = false;
                break;
            }
        }
    }

    if (show) {
        $container.stop().slideDown(250);
    } else {
        $container.stop().slideUp(250);
    }
}

function resetInputDisplay($form) {
    $form.find('.display_if').each(function () {
        var $display_if = $(this);
        var input_name = $display_if.data('input_name');
        if (input_name) {
            var $input = $form.find('[name=' + input_name + ']');
            if ($input.length) {
                if ($input.attr('type') === 'radio') {
                    $input = $form.find('input[name=' + input_name + ']:checked');
                }
                toggleInputDisplay($display_if, $input);
            }
        }
    });
}

// Gestion des évennements: 

function onFormLoaded($form) {
    setFormEvents($form);
    setCommonEvents($form);
}

function setFormEvents($form) {
    if (!$form.length) {
        return;
    }

    // Gestion automatique des affichages d'options supplémentaires lorsque certains input prennent certaines valeurs.
    // placer les champs optionnels dans un container avec class="display_if". 
    // Et Attributs: 
    // data-input_name=nom_input_a_checker (string)
    // data-show_values=valeur(s)_pour_afficher_container (string ou objet)
    // data-hide_values=valeur(s)_pour_masquer_container (string ou objet)

    $form.find('.display_if').each(function () {
        var $container = $(this);
        var input_name = $container.data('input_name');
        if (input_name) {
            var $input = $form.find('[name=' + input_name + ']');
            if ($input.length) {
                $input.change(function () {
                    toggleInputDisplay($container, $(this));
                });
            }
        }
    });
    resetInputDisplay($form);
    setInputsEvents($form);

    var form_id = $form.attr('id');
    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id) {
            var $input = $('#' + inputsEvents[i].input_name);
            if ($input.length) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
            }
        }
    }
}

function setInputsEvents($container) {
    $container.find('.switch').each(function () {
        setSwitchInputEvents($(this));
    });
    $container.find('.searchListOptions').each(function () {
        setSearchListOptionsEvents($(this));
    });
}

function setInputEvents($form, $input) {
    if (!$input.length) {
        return;
    }

    if ($input.hasClass('switch')) {
        setSwitchInputEvents($input);
    }

    var input_name = $input.attr('name');
    $form.find('.display_if').each(function () {
        var $container = $(this);
        if ($(this).data('input_name') === input_name) {
            $input.change(function () {
                toggleInputDisplay($container, $(this));
            });
        }
    });

    var form_id = $form.attr('id');
    for (var i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id) {
            if (inputsEvents[i].input_name === input_name) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
            }
        }
    }

    resetInputDisplay($form);
}

function setSwitchInputEvents($input) {
    if (parseInt($input.val()) === 1) {
        $input.css({
            'color': '#3b6ea0',
            'border-bottom-color': '#3b6ea0'
        });
    } else {
        $input.css({
            'color': '#B4B4B4',
            'border-bottom-color': '#B4B4B4'
        });
    }
    $input.change(function () {
        if (parseInt($(this).val()) === 1) {
            $(this).css({
                'color': '#3b6ea0',
                'border-bottom-color': '#3b6ea0'
            });
        } else {
            $(this).css({
                'color': '#B4B4B4',
                'border-bottom-color': '#B4B4B4'
            });
        }
    });
}

function setDateRangeEvents($container, input_name) {
    var $from = $container.find('[name=' + input_name + '_from_picker]');
    var $to = $container.find('[name=' + input_name + '_to_picker]');

    $from.datetimepicker({
        useCurrent: false //Important! See issue #1075
    });
    $from.on("dp.change", function (e) {
        if (e.date) {
            $to.data("DateTimePicker").minDate(e.date);
        }
    });
    $to.on("dp.change", function (e) {
        if (e.date) {
            $from.data("DateTimePicker").maxDate(e.date);
        }
    });
}

function setSearchListOptionsEvents($container) {
    var $parent = $container.parent();
    var $switch = $container.find('.switchInputContainer');
    if ($switch.length) {
        var input_name = $switch.data('input_name');
        if (input_name) {
            $switch.find('[name=' + input_name + ']').change(function () {
                var option = $(this).val();
                var fields_search = $container.find('#searchList_' + option + '_fields_search').val();
                var help = $container.find('#searchList_' + option + '_help').val();
                if (typeof (help) === 'undefined') {
                    help = '';
                }
                var join = $container.find('#searchList_' + option + '_join').val();
                if (typeof (join) === 'undefined') {
                    join = '';
                }
                var join_on = $container.find('#searchList_' + option + '_join_on').val();
                if (typeof (join_on) === 'undefined') {
                    join_on = '';
                }
                var join_return_label = $container.find('#searchList_' + option + '_join_return_label').val();
                if (typeof (join_return_label) === 'undefined') {
                    join_return_label = '';
                }

                var $input = $parent.find('.search_list_input');
                $input.val('');
                $input.data('fields_search', fields_search);
                $input.data('join', join);
                $input.data('join_on', join_on);
                $input.data('join_return_label', join_return_label);
                if (help) {
                    if (!$parent.find('.help').length) {
                        $parent.append('<p class="help">' + help + '</p>');
                    } else {
                        $parent.find('.help').text(help);
                    }
                } else {
                    $parent.find('.help').remove();
                }
            }).change();
        }
    }
}

$(document).ready(function () {
    $('.objectForm').each(function () {
        onFormLoaded($(this));
    });
    $.datepicker.setDefaults($.datepicker.regional[ "fr" ]);
});