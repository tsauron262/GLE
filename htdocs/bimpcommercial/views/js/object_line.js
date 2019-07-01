var bimpcomm_url = dol_url_root + '/bimpcommercial/index.php';

// Traitements ajax: 

function reloadObjectLineFormMargins($form) {
    if ($.isOk($form)) {
        var data = {};
        var check = false;

        if ($form.hasClass('BS_SavPropalLine_form') ||
                $form.hasClass('Bimp_PropalLine_form')) {
            check = true;
            data.module = $form.find('[name="module"]').val();
            data.object_name = $form.find('[name="object_name"]').val();
            data.id_object_line = parseInt($form.find('[name="id_object"]').val());
            data.id_parent = parseInt($form.find('[name="id_obj"]').val());

            var $input = $form.find('[name="pu_ht"]');
            if ($input.length) {
                data.line_pu_ht = parseFloat($input.val());
            }

            $input = $form.find('[name="tva_tx"]');
            if ($input.length) {
                data.line_tva_tx = parseFloat($input.val());
            }

            $input = $form.find('[name="qty"]');
            if ($input.length) {
                data.line_qty = parseFloat($input.val());
            }

            $input = $form.find('[name="pa_ht"]');
            if ($input.length) {
                data.line_pa_ht = parseFloat($input.val());
            } else {
                $input = $form.find('[name="id_fourn_price"]');
                if ($input.length) {
                    data.line_id_fourn_price = parseInt($input.val());
                }
            }

            $input = $form.find('[name="remisable"]');
            if ($input.length) {
                data.line_remisable = parseInt($input.val());
            } else {
                data.line_remisable = 1;
            }

            if (data.line_remisable) {
                $input = $form.find('[name="remise"]');
                if ($input.length) {
                    data.line_remise = parseFloat($input.val());
                } else {
                    data.line_remises = [];

                    var $container = $form.find('#remises_subObjectsContainer');
                    if ($container.length) {
                        $container.find('.subObjectForm').each(function () {
                            var $remiseForm = $(this);
                            var idx = $remiseForm.data('idx');
                            if (idx !== 'sub_object_idx') {
                                var remise = {
                                    id: parseInt($remiseForm.find('[name="remises_' + idx + '_id_object"]').val()),
                                    type: parseInt($remiseForm.find('[name="remises_' + idx + '_type"]').val()),
                                    percent: parseFloat($remiseForm.find('[name="remises_' + idx + '_percent"]').val()),
                                    montant: parseFloat($remiseForm.find('[name="remises_' + idx + '_montant"]').val()),
                                    per_unit: parseInt($remiseForm.find('[name="remises_' + idx + '_per_unit"]').val()),
                                    remise_ht: parseInt($remiseForm.find('[name="remises_' + idx + '_remise_ht"]').val()),
                                };
                                data.line_remises.push(remise);
                            }
                        });
                    }
                }
            }
        } else if ($form.hasClass('ObjectLineRemise_form')) {
            check = true;

            data.id_object_line = parseInt($form.find('[name="id_object_line"]').val());
            data.object_type = $form.find('[name="object_type"]').val();
            data.line_remises = [];
            var remise = {};

            var $input = $form.find('[name="id_object"]');
            if ($input.length) {
                remise.id = parseInt($input.val());
            }

            var $input = $form.find('[name="type"]');
            if ($input.length) {
                remise.type = parseInt($input.val());
            }

            var $input = $form.find('[name="percent"]');
            if ($input.length) {
                remise.percent = parseFloat($input.val());
            }

            var $input = $form.find('[name="montant"]');
            if ($input.length) {
                remise.montant = parseFloat($input.val());
            }

            var $input = $form.find('[name="per_unit"]');
            if ($input.length) {
                remise.per_unit = parseInt($input.val());
            }

            var $input = $form.find('[name="remise_ht"]');
            if ($input.length) {
                remise.remise_ht = parseInt($input.val());
            }

            data.line_remises.push(remise);
        }

        if (check) {
            var $resultContainer = $form.find('.formMarginsContainer').parent();
            if ($resultContainer.length) {
                BimpAjax('loadFormMargins', data, null, {
                    url: bimpcomm_url,
                    display_success: false,
                    display_processing: false,
                    success: function (result, bimpAjax) {
                        if (result.html) {
                            $resultContainer.html(result.html).show();
                        } else {
                            $resultContainer.html('<p class="alert alert-danger">Une erreur est survenue</p>');
                        }
                    }
                });
            }
        }
    }
}

function quickAddObjectLine($button) {
    var $container = $button.findParentByClass('objectLineQuickAddForm');

    if (!$.isOk($container)) {
        bimp_msg('Une erreur est survenue. Opération abandonnée', 'danger', null, true);
        return;
    }
    
    var data = {};
    $container.find('.inputContainer').each(function () {
        var field = $(this).data('field_name').replace(/^quick_add_(.*)$/, '$1');
        data[field] = getInputValue($(this));
    });
    
    data.module = $container.data('module');
    data.object_name = $container.data('object_name');
    data.id_obj = $container.data('id_obj');

    BimpAjax('saveObject', data, $container.find('.quickAddForm_ajax_result'), {
        $button: $button,
        $container: $container,
        display_success_in_popup_only: true,
        display_warnings_in_popup_only: true,
        display_processing: true,
        processing_padding: 10,
        success: function (result, bimpAjax) {
            bimpAjax.$container.find('.inputContainer').each(function () {
                resetInputValue($(this));
            });
            $('body').trigger($.Event('objectChange', {
                module: result.module,
                object_name: result.object_name,
                id_object: result.id_object
            }));
        }
    });
}

// Events: 

function onObjectLineFormLoaded($form) {
    if ($.isOk($form)) {
        if ($form.find('.margins_table_inputContainer').length &&
                !parseInt($form.data('form_margins_events_init'))) {
            $form.find('[name="pu_ht"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });

            $form.find('[name="tva_tx"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });

            $form.find('[name="qty"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });

            $form.find('[name="id_fourn_price"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });

            $form.find('[name="remisable"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });

            var id_form = $form.data('identifier');

            if (!parseInt($('body').data(id_form + '_form_margins_events_init'))) {
                $('body').data(id_form + '_form_margins_events_init', 1);
                $('body').on('inputReloaded', function (e) {
                    if (e.$form.data('identifier') === id_form) {
                        if (e.input_name === 'pu_ht' ||
                                e.input_name === 'tva_tx' ||
                                e.input_name === 'qty' ||
                                e.input_name === 'id_fourn_price' ||
                                e.input_name === 'remisable') {
                            e.$input.change(function () {
                                reloadObjectLineFormMargins(e.$form);
                            });
                        }
                    }
                });

                $('body').on('subObjectFormAdded', function (e) {
                    if (e.id_form === id_form &&
                            e.$subForm.data('object_name') === 'ObjectLineRemise') {
                        var $input = e.$subForm.find('[name="remises_' + e.idx + '_type"]');
                        if ($input.length) {
                            $input.change(function () {
                                reloadObjectLineFormMargins($('#' + e.id_form));
                            });
                        }

                        var $input = e.$subForm.find('[name="remises_' + e.idx + '_percent"]');
                        if ($input.length) {
                            $input.change(function () {
                                reloadObjectLineFormMargins($('#' + e.id_form));
                            });
                        }

                        var $input = e.$subForm.find('[name="remises_' + e.idx + '_montant"]');
                        if ($input.length) {
                            $input.change(function () {
                                reloadObjectLineFormMargins($('#' + e.id_form));
                            });
                        }

                        var $input = e.$subForm.find('[name="remises_' + e.idx + '_per_unit"]');
                        if ($input.length) {
                            $input.change(function () {
                                reloadObjectLineFormMargins($('#' + e.id_form));
                            });
                        }

                        var $input = e.$subForm.find('[name="remises_' + e.idx + '_remise_ht"]');
                        if ($input.length) {
                            $input.change(function () {
                                reloadObjectLineFormMargins($('#' + e.id_form));
                            });
                        }
                        reloadObjectLineFormMargins($('#' + e.id_form));
                    }
                });

                $('body').on('subObjectFormRemoved', function (e) {
                    if (e.id_form === id_form &&
                            e.object_name === 'ObjectLineRemise') {
                        reloadObjectLineFormMargins($('#' + e.id_form));
                    }
                });
            }

            $form.data('form_margins_events_init', 1);
        }
    }
}

function onObjectLineRemiseFormLoaded($form) {
    if ($.isOk($form)) {
        if ($form.find('.margins_table_inputContainer').length &&
                !parseInt($form.data('form_margins_events_init'))) {
            $form.find('[name="type"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });
            $form.find('[name="percent"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });
            $form.find('[name="montant"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });
            $form.find('[name="per_unit"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });
            $form.find('[name="remise_ht"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });
            $form.find('[name="remisable"]').change(function () {
                reloadObjectLineFormMargins($(this).findParentByClass('object_form'));
            });


            $form.data('form_margins_events_init', 1);
        }
    }
}

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('BS_SavPropalLine_form') ||
                    e.$form.hasClass('Bimp_PropalLine_form')) {
                onObjectLineFormLoaded(e.$form);
            } else if (e.$form.hasClass('ObjectLineRemise_form')) {
                onObjectLineRemiseFormLoaded(e.$form);
            }
        }
    });
});