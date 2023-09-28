function triggerObjectChange(module, object_name, id_object) {
    if (typeof (id_object) === 'undefined') {
        id_object = 0;
    }

    $('body').trigger($.Event('objectChange', {
        module: module,
        object_name: object_name,
        id_object: id_object
    }));
}

function saveObject(module, object_name, id_object, fields, $resultContainer, successCallback, display_success) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
    };

    if (typeof (display_success) === 'undefined') {
        display_success = true;
    }

    for (var i in fields) {
        data[i] = fields[i];
    }

    BimpAjax('saveObject', data, $resultContainer, {
        display_success: display_success,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        }
    });
}

function saveObjectField(module, object_name, id_object, field, value, $resultContainer, successCallback, display_success) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field,
        value: value
    };

    if (typeof (display_success) === 'undefined') {
        display_success = true;
    }

    BimpAjax('saveObjectField', data, $resultContainer, {
        display_success: display_success,
        success: function (result) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        }
    });
}

function saveObjectAssociations(id_object, object_name, association, $button) {
    if ($button.hasClass('disabled')) {
        return;
    }

    var $resultContainer = $('#' + object_name + '_' + association + '_associatonsAjaxResult');
    if (!$resultContainer.length) {
        $resultContainer = null;
    }
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

    BimpAjax('saveObjectAssociations', data, $resultContainer, {
        $button: $button
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
        var data = {
            'module': module,
            'object_name': object_name,
            'objects': [id_object]
        };

        BimpAjax('deleteObjects', data, $resultContainer, {
            $button: $button,
            success: function (result, bimpAjax) {
                if (typeof (successCallBack) === 'function') {
                    successCallBack(result, bimpAjax);
                }
                for (var i in result.objects_list) {
                    $('body').trigger($.Event('objectChange', {
                        module: result.module,
                        object_name: result.object_name,
                        id_object: result.objects_list[i]
                    }));
                }
            }
        });
    }
}

function loadObjectFieldValue(module, object_name, id_object, field, $resultContainer, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        field: field
    };

    BimpAjax('loadObjectFieldValue', data, $resultContainer, {
        display_success: false,
        success: function (result) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}

function setObjectNewStatus($button, object_data, new_status, extra_data, $resultContainer, successCallback, confirm_msg) {
    // object_data.id_object peut être un array d'id
    if (typeof (confirm_msg) === 'string') {
        if (!confirm(confirm_msg.replace(/&quote;/g, '"'))) {
            return;
        }
    }

    if (typeof (extra_data) === 'undefined') {
        extra_data = {};
    }

    if (typeof ($resultContainer) === 'undefined') {
        $resultContainer = null;
    }

    var data = {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        new_status: new_status,
        extra_data: extra_data
    };

    BimpAjax('setObjectNewStatus', data, $resultContainer, {
        $button: $button,
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result, bimpAjax);
            }
            $('body').trigger($.Event('objectChange', {
                module: bimpAjax.module,
                object_name: bimpAjax.object_name,
                id_object: bimpAjax.id_object
            }));
        }
    });
}

function setObjectAction($button, object_data, action, extra_data, $resultContainer, successCallback, options) {
    if (typeof (extra_data) === 'undefined' || !extra_data) {
        extra_data = {};
    }

    if (typeof ($resultContainer) === 'undefined') {
        $resultContainer = null;
    }

    if (typeof (options) === 'undefined' || !options) {
        options = {};
    }

    var def_options = {
        modal_title: '', // Titre modale
        form_name: '', // Nom formulaire objet
        confirm_msg: '', // Message de confirmation
        on_form_submit: null, // callback traitement du formulaire
        no_triggers: false, // Ne pas déclencher le trigger "objectChange"
        modal_format: 'medium', // Format modal (small / medium / large) 
        modal_scroll_bottom: true, // Auto scroll vers le bas de la modal du formulaire (à la validation) 
        use_bimpdatasync: false, // Utiliser BimpDataSync
        use_report: false, // Utiliser les rapports BimpDataSync
        display_processing: true,
        processing_msg: 'Traitement en cours'
    };

    for (var i in def_options) {
        if (typeof (options[i]) === 'undefined') {
            options[i] = def_options[i];
        }
    }

    if (options.confirm_msg) {
        if (!confirm(options.confirm_msg.replace(/&quote;/g, '"'))) {
            return;
        }
    }

    if (!options.modal_title) {
        if ($.isOk($button)) {
            if ($button.hasClass('rowButton')) {
                options.modal_title = $button.data('content');
            } else {
                options.modal_title = $button.text();
            }
        } else {
            options.modal_title = 'Action "' + action + '"';
        }
    }

    if (options.form_name) {
        object_data.form_name = options.form_name;

        object_data.param_values = {
            fields: extra_data
        };

        loadModalForm($button, object_data, options.modal_title, function ($form) {
            if ($.isOk($form)) {
                var modal_idx = parseInt($form.data('modal_idx'));
                if (!modal_idx) {
                    bimp_msg('Erreur technique: index de la modale absent', 'danger', null, true);
                    return;
                }

                for (var field_name in extra_data) {
                    var $input = $form.find('[name="' + field_name + '"]');
                    if ($input.length) {
                        $input.val(extra_data[field_name]);
                    }
                }

                bimpModal.$footer.find('.save_object_button.modal_' + modal_idx).remove();
                bimpModal.$footer.find('.objectViewLink.modal_' + modal_idx).remove();
                bimpModal.addButton('Valider<i class="fa fa-arrow-circle-right iconRight"></i>', '', 'primary', 'set_action_button', modal_idx);
                bimpModal.$footer.find('.set_action_button.modal_' + modal_idx).click(function () {
                    if (validateForm($form)) {
                        $form.find('.inputContainer').each(function () {
                            field_name = $(this).data('field_name');
                            if ($(this).data('multiple')) {
                                field_name = $(this).data('values_field');
                            }
                            if (field_name) {
                                extra_data[field_name] = getInputValue($(this));
                            }
                        });
                        if (typeof (options.on_form_submit) === 'function') {
                            var returned_extra_data = options.on_form_submit($form, extra_data);

                            if (!returned_extra_data) {
                                return;
                            }

                            extra_data = returned_extra_data;
                        }

                        options.form_name = '';
                        options.confirm_msg = '';
                        options.on_form_submit = null;

                        /*gestion des upload TODO problé easychrone */
                        var nbFile = 0;
                        var nbFileOk = 0;
                        var button = $(this);
                        $form.find('input[type=file]').each(function () {
                            if ($(this).val() != '') {
                                var id = $(this).attr('id');
                                var name = $(this).attr('name');
                                nbFile++;
                                button.addClass('disabled');
                                $('#' + id).simpleUpload(DOL_URL_ROOT + "/bimpcore/ajax/upload.php?id=" + name, {
                                    start: function (file) {
                                        $form.append('<div id="progressbar_' + id + '"></div>');
                                    },
                                    progress: function (progress) {
//                                        console.log(progress);
                                        $("#progressbar_" + id).progressbar({value: progress});
                                    },
                                    success: function (data) {
                                        nbFileOk++;
                                        if (nbFileOk == nbFile) {
                                            button.removeClass('disabled');

                                            setObjectAction(button, object_data, action, extra_data, $('#' + $form.attr('id') + '_result'), function (result) {
                                                if (typeof (result.allow_reset_form) === 'undefined' || !result.allow_reset_form) {
                                                    if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
                                                        bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx')).remove();
                                                    } else {
                                                        bimpModal.removeContent(parseInt($form.data('modal_idx')));
                                                    }
                                                }
                                                if (typeof (successCallback) === 'function') {
                                                    successCallback(result);
                                                }
                                            }, options);
                                        }
                                    },
                                    error: function (error) {
                                        alert('error upload file');
                                    }

                                });
                            }
                        });
                        /*fin gestion des upload*/

                        if (nbFile == 0) { //pas d'upload
                            // setObjectAction($button, object_data, action, extra_data, $resultContainer, successCallback, options)
                            setObjectAction($(this), object_data, action, extra_data, $('#' + $form.attr('id') + '_result'), function (result) {
                                if (typeof (result.allow_reset_form) === 'undefined' || !result.allow_reset_form) {
                                    if (typeof (result.warnings) !== 'undefined' && result.warnings && result.warnings.length) {
                                        bimpModal.$footer.find('.set_action_button.modal_' + $form.data('modal_idx')).remove();
                                    } else {
                                        bimpModal.removeContent(parseInt($form.data('modal_idx')));
                                    }
                                }
                                if (typeof (successCallback) === 'function') {
                                    successCallback(result);
                                }
                            }, options);
                        }
                    }
                });
                checkFormInputsReloads($form);
            }
        }, '', options.modal_format);
    } else {
        var data = {
            module: object_data.module,
            object_name: object_data.object_name,
            id_object: object_data.id_object,
            object_action: action,
            use_report: options.use_report,
            extra_data: extra_data
        };

        if (options.use_bimpdatasync) {
            if (typeof (bds_initObjectActionProcess) !== 'function') {
                $('body').append('<script type="text/javascript" src="' + dol_url_root + '/bimpdatasync/views/js/operations.js"></script>');
            }

            if (typeof (bds_initObjectActionProcess) !== 'function') {
                bimp_msg('Erreur: fonction d\'initialisation du processus absente', 'danger');
                return;
            }

            bds_initObjectActionProcess($button, data, options.modal_title, $resultContainer);
        } else {
            BimpAjax('setObjectAction', data, $resultContainer, {
                $button: $button,
                display_success_in_popup_only: true,
                display_warnings_in_popup_only: true,
                module: object_data.module,
                object_name: object_data.object_name,
                id_object: object_data.id_object,
                display_processing: options.display_processing,
                processing_msg: options.processing_msg,
                processing_padding: 10,
                append_html: true,
                modal_scroll_bottom: options.modal_scroll_bottom,
                success: function (result, bimpAjax) {
                    if (typeof (successCallback) === 'function') {
                        successCallback(result);
                    }

                    if (!options.no_triggers) {
                        if (typeof (result.success_callback) !== 'string' ||
                                !/window\.location/.test(result.success_callback)) {
                            if (!$.isOk($resultContainer) || (typeof (result.html) === 'undefined') || !result.html) {
                                $('body').trigger($.Event('objectChange', {
                                    module: bimpAjax.module,
                                    object_name: bimpAjax.object_name,
                                    id_object: bimpAjax.id_object
                                }));
                            }
                        }
                    }
                }
            });
        }
    }
}

function displayProductStocks($button, id_product, id_entrepot) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $('.productStocksContainer').each(function () {
        $(this).html('').hide();
    });

    var $container = $button.parent().find('#product_' + id_product + '_stocks_popover_container');

    $container.show();

    BimpAjax('loadProductStocks', {
        id_product: id_product,
        id_entrepot: id_entrepot
    }, $container, {
        url: dol_url_root + '/bimpcore/index.php',
        $button: $button,
        display_processing: true,
        display_success: false,
        processing_msg: 'Chargement',
        processing_padding: 10,
        append_html: true,
        success: function (result, bimpAjax) {
            bimpAjax.$resultContainer.find('input[name="stockSearch"]').keyup(function (e) {
                var search = $(this).val();
                var regex = new RegExp(search, 'i');
                bimpAjax.$resultContainer.find('.productStockTable').children('tbody').children('tr').each(function () {
                    var label = $(this).children('td:first-child').text();
                    if (regex.test(label)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        }
    });
}

function loadObjectCard($container, module, object_name, id_object, card_name, successCallback) {
    var data = {
        module: module,
        object_name: object_name,
        id_object: id_object,
        card_name: card_name
    };

    BimpAjax('loadObjectCard', data, $container, {
        display_success: false,
        append_html: true,
        display_processing: true,
        processing_msg: '',
        processing_padding: 10,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result, bimpAjax);
            }
        }
    });
}

function loadModalObjectNotes($button, module, object_name, id_object, list_model, filter_by_user) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof (list_model) === 'undefined') {
        list_model = '';
    }

    if (typeof (filter_by_user) === 'undefined') {
        filter_by_user = 1;
    }

    bimpModal.loadAjaxContent($button, 'loadObjectNotes', {
        module: module,
        object_name: object_name,
        id_object: id_object,
        list_model: list_model,
        filter_by_user: filter_by_user
    }, "Messages", 'Chargement', function (result, bimpAjax) {
        setCommonEvents(bimpAjax.$resultContainer);
        setInputsEvents(bimpAjax.$resultContainer);
        bimpAjax.$resultContainer.find('.object_list').each(function () {
            onListLoaded($(this));
        });
    });
}

function loadObjectCustomContent($button, $resultContainer, object_data, method, method_params, success_callback) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }
    }

    if (typeof (method_params) === 'undefined') {
        method_params = {};
    }

    var display_processing = false;
    var processing_msg = '';
    var append_html = false;

    if ($.isOk($resultContainer)) {
        var display_processing = true;
        var processing_msg = 'Chargement';
        var append_html = true;
    }

    if (typeof (object_data.id_object) === 'undefined') {
        object_data.id_object = 0;
    }

    BimpAjax('loadObjectCustomContent', {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        method: method,
        params: method_params
    }, $resultContainer, {
        $button: $button,
        display_success: false,
        display_processing: display_processing,
        processing_msg: processing_msg,
        append_html: append_html,
        success_callback: success_callback,
        success: function (result, bimpAjax) {
            if (typeof (bimpAjax.success_callback) !== 'undefined') {
                bimpAjax.success_callback(result, bimpAjax);
            }
        }

    });
}

function loadModalObjectCustomContent($button, object_data, method, method_params, title, success_callback, modal_format) {
    if ($.isOk($button)) {
        if ($button.hasClass('disabled')) {
            return;
        }
        $button.hasClass('disabled');
    }

    if (typeof (method_params) === 'undefined') {
        method_params = {};
    }

    if (typeof (modal_format) === 'undefined') {
        modal_format = 'medium';
    }

    bimpModal.loadAjaxContent($button, 'loadObjectCustomContent', {
        module: object_data.module,
        object_name: object_data.object_name,
        id_object: object_data.id_object,
        method: method,
        params: method_params
    }, title, 'Chargement', success_callback, {}, modal_format);
}

function forceBimpObjectUnlock($button, object_data, $resultContainer) {
    if ($button.hasClass('disabled')) {
        return;
    }

    if (typeof ($resultContainer)) {
        $resultContainer = $button.findParentByClass('ajaxResultContainer');
    }

    BimpAjax('forceBimpObjectUnlock', object_data, $resultContainer, {
        $button: $button,
        display_processing: true,
        processing_padding: 15,
        success_msg: 'Dévérouillage effectué avec succès'
    });
}

function saveBimpcoreConf(module, name, value, $resultContainer, successCallback, display_success) {
    var data = {
        module: module,
        name: name,
        value: value
    };

    if (typeof (display_success) === 'undefined') {
        display_success = true;
    }

    BimpAjax('saveBimpcoreConf', data, $resultContainer, {
        display_success: display_success,
        success: function (result, bimpAjax) {
            if (typeof (successCallback) === 'function') {
                successCallback(result);
            }
        }
    });
}
// simpleUpload : déplacé dans functions.js