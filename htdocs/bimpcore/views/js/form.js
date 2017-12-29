var inputsEvents = [];

function addInputEvent(form_id, input_name, event, callback) {
    for (i in inputsEvents) {
        if (inputsEvents[i].form_id === form_id &&
                inputsEvents[i].input_name === input_name &&
                inputsEvents[i].event === event &&
                ('' + inputsEvents[i].callback) === ('' + callback)) {
            return;
        }
    }
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

    var module = $form.data('module_name');
    var object_name = $form.data('object_name');

    var data = new FormData($form.get(0));

    bimp_json_ajax('saveObject', data, $result, function (result) {
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

        $('body').trigger($.Event('objectChange', {
            module: module,
            object_name: object_name,
            id_object: result.id_object
        }));

    }, function () {
        $button.removeClass('disabled');
    }, true, {
        processData: false,
        contentType: false
    });
}

function saveObject(module, object_name, id_object, fields, $resultContainer, successCallback) {
    var data = fields;

    data['module_name'] = module;
    data['object_name'] = object_name;
    data['id_object'] = id_object;

    bimp_json_ajax('saveObject', data, $resultContainer, function (result) {
        if (typeof (successCallback) !== 'undefined') {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
        $('body').trigger($.Event('objectChange', {
            module: module,
            object_name: object_name,
            id_object: result.id_object
        }));
    });
}

function saveObjectField(module, object_name, id_object, field, value, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        value: value
    };

    bimp_json_ajax('saveObjectField', data, $resultContainer, function (result) {
        if (typeof (successCallback) === 'function') {
            successCallback(result);
        }
        $('body').trigger($.Event('objectChange', {
            module: module,
            object_name: object_name,
            id_object: id_object
        }));
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
            if (typeof (successCallBack) === 'function') {
                successCallBack(result);
            }
            $('body').trigger($.Event('objectDelete', {
                module: module,
                object_name: object_name,
                id_object: result.id_object
            }));
        }, function (result) {
            $button.removeClass('disabled');
        });
    }
}

function loadModalForm($button, data) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    var title = '';

    if (id_object) {
        title = '<i class="fa fa-edit iconLeft"></i>Edition ';
        if (typeof (object_labels[data.object_name].of_the) !== 'undefined') {
            title += object_labels[data.object_name].of_the;
        } else {
            title += 'l\'objet "' + data.object_name + '"';
        }
        title += ' n°' + id_object;
    } else {
        title = '<i class="fa fa-plus-circle iconLeft"></i>Ajout ';
        if (typeof (object_labels[data.object_name].of_a) !== 'undefined') {
            title += object_labels[data.object_name].of_a;
        } else {
            title += 'd\'un objet "' + data.object_name + '"';
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

    data.full_panel = 0;

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

function loadObjectFieldValue(module, object_name, id_object, field, $resultContainer, successCallback) {
    var data = {
        object_module: module,
        object_name: object_name,
        id_object: id_object,
        field: field
    };

    bimp_json_ajax('loadObjectFieldValue', data, $resultContainer, function (result) {
        if (typeof (successCallback) === 'function') {
            successCallback(result);
        }
    });
}

function addObjectMultipleValuesItem(module, object_name, id_object, field, item_value, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        item_value: item_value
    };

    bimp_json_ajax('addObjectMultipleValuesItem', data, $resultContainer, function (result) {
        if (typeof (successCallback) === 'function') {
            successCallback(result);
        }

        $('body').trigger($.Event('objectChange', {
            module: module,
            object_name: object_name,
            id_object: id_object
        }));
    });
}

function deleteObjectMultipleValuesItem(module, object_name, id_object, field, item_value, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        item_value: item_value
    };

    bimp_json_ajax('deleteObjectMultipleValuesItem', data, $resultContainer, function (result) {
        if (typeof (successCallback) === 'function') {
            successCallback(result);
        }

        $('body').trigger($.Event('objectChange', {
            module: module,
            object_name: object_name,
            id_object: id_object
        }));
    });
}

function saveAssociations(operation, associations, $resultContainer, successCallBack) {
    var data = {
        operation: operation,
        associations: associations
    };

    bimp_json_ajax('saveAssociations', data, $resultContainer, function (result) {
        if (typeof (successCallBack) === 'function') {
            successCallBack(result);
        }
        for (var i in result.done) {
            $('body').trigger($.Event('objectChange', {
                module: associations[result.done[i]].module,
                object_name: associations[result.done[i]].object_name,
                id_object: associations[result.done[i]].id_object
            }));
        }
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
    var id_parent = $form.data('id_parent');

    var $container = $form.find('#' + form_id + '_' + input_name);

    if (!$container.length) {
        return;
    }

    var custom = 0;
    if ($container.hasClass('customField')) {
        custom = 1;
    }

    var data = {
        object_module: object_module,
        object_name: object_name,
        id_parent: id_parent,
        field_name: input_name,
        fields: fields,
        custom_field: custom
    };

    if (custom) {
        data['content_config_path'] = $container.data('content_config_path');
    }

    bimp_json_ajax('loadObjectInput', data, $container, function (result) {
        if (typeof (result.html) !== 'undefined') {
            $container.html(result.html).slideDown(250, function () {
                var $input = $container.find('[name=' + input_name + ']');
                if ($input.length) {
                    setInputEvents($container, $input);
                }
            });
        }
    }, 'Echec du chargement du champ');
}

function searchObjectList($input) {
    var $container = $input.parent('div');
    var value = $input.val();

    if (!value) {
        $container.find('[name=' + $container.data('field_name') + ']').val('0').change();
        return;
    }

    var data = {
        'table': $input.data('table'),
        'fields_search': $input.data('fields_search'),
        'field_return_value': $input.data('field_return_value'),
        'field_return_label': $input.data('field_return_label'),
        'join': $input.data('join'),
        'join_on': $input.data('join_on'),
        'join_return_label': $input.data('join_return_label'),
        'label_syntaxe': $input.data('label_syntaxe'),
        'value': value
    };

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
                var multiple = parseInt($container.data('multiple'));
                if (multiple) {
                    field_name += '_add_value';
                }
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

function getInputsValues($container) {
    var values = {};
    $container.find('.inputContainer').each(function () {
        var field = $(this).data('field_name');
        var multiple = $(this).data('multiple');
        if (multiple) {
            var $inputs = $(this).find('[name="' + field + '[]"]');
            values[field] = [];
            $inputs.each(function () {
                if ($(this).attr('type') === 'checkbox') {
                    if ($(this).prop('checked')) {
                        values[field].push($(this).val());
                    }
                } else {
                    values[field].push($(this).val());
                }
            });
            if (!values[field].length) {
                values[field] = 0;
            }
        } else {
            var $input = $(this).find('[name="' + field + '"]');
            if ($input.length) {
                values[field] = $input.val();
            }
        }
    });
    return values;
}

function addMultipleInputCurrentValue($button, value_input_name, label_input_name, ajax_save) {
    if (typeof (ajax_save) === 'undefined') {
        ajax_save = false;
    }

    if ($button.hasClass('disabled')) {
        return;
    }

    var $container = $button.parent('div').parent('div.inputMultipleValuesContainer');
    if (!$container.length) {
        return;
    }
    var $inputContainer = $container.parent('.inputContainer');
    if (!$inputContainer.length) {
        return;
    }
    var $value_input = $inputContainer.find('[name=' + value_input_name + ']');
    var $label_input = $inputContainer.find('[name=' + label_input_name + ']');
    var value = $value_input.val();
    var label = $label_input.val();
    if (typeof (value) !== 'undefined' && value !== '') {
        if (!label) {
            label = value;
        }
        var field_name = $inputContainer.data('field_name');
        var html = '<tr>';
        html += '<td style="display: none"><input type="hidden" value="' + value + '" name="' + field_name + '[]"/></td>';
        html += '<td>' + label + '</td>';
        html += '<td><button type="button" class="btn btn-light-danger iconBtn"';
        html += ' onclick="$(this).parent(\'td\').parent(\'tr\').remove();';
        if (ajax_save) {
            html += 'var $button = $(this); deleteObjectMultipleValuesItem(\'' + $container.data('module') + '\', ';
            html += '\'' + $container.data('object_name') + '\', ';
            html += $container.data('id_object') + ', \'' + field_name + '\', \'' + value + '\', null, ';
            html += 'function(){$button.parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove();})});';
        } else {
            html += '$(this).parent(\'td\').parent(\'tr\').fadeOut(250, function() {$(this).remove()});';
        }
        html += '"><i class="fa fa-trash"></i></button></td>';
        html += '</tr>';

        $value_input.val('');
        $label_input.val('');

        if (ajax_save) {
            addObjectMultipleValuesItem($container.data('module'), $container.data('object_name'), $container.data('id_object'), field_name, value, null, function () {
                $container.find('div.inputMultipleValuesContainer').find('table').find('tbody').append(html);
            });
        } else {
            $container.find('table').find('tbody').append(html);
        }
    }
}

function checkTextualInput($input) {
    if ($input.length) {
        var data_type = $input.data('data_type');
        if (data_type) {
            var value = $input.val();
            if (value === '') {
                return true;
            }
            var msg = '';
            var initial_value = value;
            switch (data_type) {
                case 'number':
                    var min = $input.data('min');
                    var max = $input.data('max');
                    var decimals = parseInt($input.data('decimals'));
                    var unsigned = parseInt($input.data('unsigned'));

                    value = value.replace(/[^0-9\.,\-]/g, '');
                    value = value.replace(',', '.');
                    if (value === '-') {
                        if (unsigned || (parseFloat(min) >= 0)) {
                            value = '';
                            msg = 'Nombres négatifs interdits';
                        }
                        break;
                    }
                    var parsed_value = 0;
                    if (decimals > 0) {
                        var reg_str = '^(\-?[0-9]+\.[0-9]{0,' + decimals + '}).*$';
                        var reg = new RegExp(reg_str);
                        value = value.replace(reg, '$1');
                        if (/\.$/.test(value)) {
                            break;
                        }
                        parsed_value = parseFloat(value);
                        min = parseFloat(min);
                        max = parseFloat(max);

                    } else {
                        value = value.replace(/^(\-?[0-9]*)\.?.*$/, '$1');
                        parsed_value = parseInt(value);
                        initial_value = parseInt(initial_value);
                        min = parseInt(min);
                        max = parseInt(max);
                    }
                    if (parsed_value < min) {
                        value = min;
                        msg = 'Min: ' + min;
                    } else if (parsed_value > max) {
                        value = max;
                        msg = 'Max: ' + max;
                    }
                    break;

                case 'string':
                    var size = $input.data('size');
                    var forbidden_chars = $input.data('forbidden_chars');
                    var regexp = $input.data('regexp');
                    var invalide_msg = $input.data('invalid_msg');
                    var uppercase = $input.data('uppercase');
                    var lowercase = $input.data('lowercase');
                    break;
            }
            if (value !== initial_value) {
                $input.val(value).change();
            }
            if (msg) {
                displayInputMsg($input, msg, 'info');
            } else {
                $input.popover('destroy');
            }
        }
    }
}

function displayInputMsg($input, msg, className) {
    if (typeof (className) === 'undefined') {
        className = 'info';
    }
    var html = '<p class="alert alert-' + className + '">' + msg + '</p>';
    bimp_display_element_popover($input, html, 'right');
    $input.unbind('blur').blur(function () {
        $input.popover('destroy');
    });
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
    if (!$form.length) {
        return;
    }

    if (!parseInt($form.data('loaded_event_processed'))) {
        $form.data('loaded_event_processed', 1);

        setFormEvents($form);
        setCommonEvents($form);
    }
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
            var $input = $form.find('[name=' + inputsEvents[i].input_name + ']');
            if ($input.length) {
                $input.on(inputsEvents[i].event, inputsEvents[i].callback);
            }
        }
    }
}

function setInputsEvents($container) {
    $container.find('.switch').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSwitchInputEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.toggle_value').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setToggleInputEvent($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.searchListOptions').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSearchListOptionsEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('input[type="text"]').each(function () {
        if (!$(this).data('check_event_init')) {
            $(this).keyup(function () {
                checkTextualInput($(this));
            });
            $(this).data('check_event_init', 1);
        }
    });
}

function setInputEvents($form, $input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

    if ($input.hasClass('switch')) {
        setSwitchInputEvents($input);
    }

    if ($input.hasClass('.toggle_value')) {
        setToggleInputEvent($input);
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
    $input.data('event_init', 1);
}

function setSwitchInputEvents($input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

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

    $input.data('event_init', 1);
}

function setToggleInputEvent($input) {
    if (!$input.length) {
        return;
    }

    if (parseInt($input.data('event_init'))) {
        return;
    }

    var $toggle = $input.parent().find('.toggle');
    $toggle.change(function () {
        if ($(this).prop('checked')) {
            $input.val(1).change();
        } else {
            $input.val(0).change();
        }
    });

    $input.data('event_init', 1);
}

function setDateRangeEvents($container, input_name) {
    var $from = $container.find('[name=' + input_name + '_from_picker]');
    var $to = $container.find('[name=' + input_name + '_to_picker]');

    if (!$from.length || !$to.length) {
        return;
    }

    if (!parseInt($from.data('event_init'))) {
        $from.data('event_init', 1);
        $from.datetimepicker({
            useCurrent: false //Important! See issue #1075
        });
        $from.on("dp.change", function (e) {
            if (e.date) {
                $to.data("DateTimePicker").minDate(e.date);
            }
        });
    }

    if (!parseInt($to.data('event_init'))) {
        $to.data('event_init', 1);
        $to.on("dp.change", function (e) {
            if (e.date) {
                $from.data("DateTimePicker").maxDate(e.date);
            }
        });
    }
}

function setSearchListOptionsEvents($container) {
    if (!$container.length) {
        return;
    }

    if (parseInt($container.data('event_init'))) {
        return;
    }

    var $parent = $container.parent();
    var $switch = $container.find('.switchInputContainer');
    if ($switch.length) {
        var input_name = $switch.data('input_name');
        var $input = $parent.find('.search_list_input');
        var current_value = $input.val();
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

                $input.val('');
                $input.data('fields_search', fields_search);
                $input.data('join', join);
                $input.data('join_on', join_on);
                $input.data('join_return_label', join_return_label);
                if (help) {
                    if (!$parent.find('.help').length) {
                        $input.after('<p class="help">' + help + '</p>');
                    } else {
                        $parent.find('.help').text(help);
                    }
                } else {
                    $parent.find('.help').remove();
                }
            }).change();
            $input.val(current_value);
        }

        $container.data('event_init', 1);
    }
}

$(document).ready(function () {
    $('.objectForm').each(function () {
        onFormLoaded($(this));
    });

    $.datepicker.setDefaults($.datepicker.regional[ "fr" ]);

    $('body').on('controllerTabLoaded', function (e) {
        if (e.$container.length) {
            e.$container.find('.objectForm').each(function () {
                onFormLoaded($(this));
            });
        }
    });
});