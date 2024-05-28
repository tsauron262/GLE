var sav_form_submit_locked = false;

function SavPublicForm() {
    var ptr = this;
    this.$form = null;
    this.inputs = [
        'id_client',
        'client_email',
        'client_type',
        'client_nom_societe',
        'client_siret',
        'client_id_contact',
        'client_civility',
        'client_firstname',
        'client_lastname',
        'client_address',
        'client_zip',
        'client_town',
        'client_pays',
        'client_phone_mobile',
        'client_phone_perso',
        'client_phone_pro',
        'client_pref_contact',
        'eq_type',
        'eq_serial',
        'eq_symptomes',
        'eq_etat',
        'eq_system',
        'sav_centre',
        'sav_day',
        'reservation_id'
    ];

    // Evénements: 

    this.setEmailFormEvents = function () {
        var $form = $('#new_sav_form');
        if ($form.length && !parseInt($form.data('sav_form_events_init'))) {
            ptr.$form = $form;

            var $input = $form.find('[name="client_email"]');
            if ($input.length) {
                $input.blur(function () {
                    ptr.emailSubmit($(this));
                });
            }

            $form.data('email_form_events_init', 1);
        }
    };

    this.setCustomerInfosFormEvents = function () {
        var $form = $('#customer_infos');

        if ($form.length) {
            if (!parseInt($form.data('customer_infos_form_events_init'))) {
                var $input = $form.find('select[name="client_type"]');

                if ($input.length) {
                    $input.change(function () {
                        var val = parseInt($(this).val());

                        if (val === 0 || val === 8) {
                            $form.find('input[name="client_nom_societe"]').parent().stop().slideUp(250);
                            $form.find('input[name="client_siret"]').parent().stop().slideUp(250);
                        } else {
                            $form.find('input[name="client_nom_societe"]').parent().stop().slideDown(250);
                            $form.find('input[name="client_siret"]').parent().stop().slideDown(250);
                        }
                    }).change();
                }

                $input = $form.find('select[name="client_id_contact"]');

                if ($input.length) {
                    $input.change(function () {
                        ptr.loadClientContactInfos(parseInt($(this).val()));

                        if (!parseInt($(this).val())) {
                            $('#customer_infos').find('.editContactNotif').stop().slideUp(250);
                        } else {
                            $('#customer_infos').find('.editContactNotif').stop().slideDown(250);
                        }
                    });
                }

                $form.data('custom_form_events_init', 1);
            }
        }
    };

    this.setEquipmentsFormEvents = function () {
        var $form = $('#equipment_infos');

        if ($form.length) {
            if (!parseInt($form.data('equipment_infos_form_events_init'))) {
                var $input = $form.find('select[name="eq_type"]');

                if ($input.length) {
                    $input.change(function () {
                        ptr.fetchAvailableSlots();
                    }).change();
                }

                $form.data('equipment_infos_form_events_init', 1);
            }
        }
    };

    this.setRdvFormEvents = function () {
        var $form = $('#rdv_infos');

        if ($form.length) {
            if (!parseInt($form.data('rdv_infos_form_events_init'))) {
                var $input = $form.find('[name="sav_centre"]');

                if ($input.length) {
                    $input.change(function () {
                        ptr.fetchAvailableSlots();
                    }).change();
                }

                $form.data('rdv_infos_form_events_init', 1);
            }
        }
    };

    this.setRdvSlotEvents = function () {
        var $form = $('#sav_slot');

        if ($form.length) {
            if (!parseInt($form.data('sav_slot_form_events_init'))) {
                var $input = $form.find('select[name="sav_day"]');

                if ($input.length) {
                    $input.change(function () {
                        var day = $(this).val();
                        var $container = $('#sav_slots_container');

                        if ($container.length) {
                            $container.find('.select2').css('width', '100%');
                            $container.find('.sav_slot_container').stop().hide();

                            if (day) {
                                $container.css('opacity', 1);
                                $container.find('select[name="sav_slot_' + day + '"]').parent().slideDown(250);
                            } else {
                                $container.css('opacity', 0);
                            }
                        }
                        ptr.checkCanSubmit();
                    }).change();
                }

                $form.find('select.slot_select').each(function () {
                    $(this).change(function () {
                        ptr.checkCanSubmit();
                    });
                });

                $form.data('sav_slot_form_events_init', 1);
            }
        }
    };

    // Requêtes ajax: 

    this.emailSubmit = function ($input) {
        if (typeof ($input) === 'undefined') {
            $input = $('#new_sav_form').find('[name="client_email"]');
        }

        if ($.isOk($input)) {
            var val = $input.val();
            if (val) {
                var $cust_infos = $('#customer_infos');
                var cur_email = '';
                if ($cust_infos.length) {
                    cur_email = $cust_infos.data('client_email');
                }

                var code_centre = '';
                var $code_centre = $('#client_email').find('[name="code_centre"]');
                if ($code_centre.length) {
                    code_centre = $code_centre.val();
                }

                if (!cur_email || cur_email !== val) {
                    BimpAjax('loadPublicSavForm', {
                        'client_email': val,
                        'code_centre': code_centre
                    }, $('#client_email_ajax_result'), {
                        'display_success': false,
                        'display_processing': true,
                        'processing_msg': '',
                        'processing_padding': 20,
                        'display_warnings_in_popup_only': false,
                        'append_html': true,
                        success: function (result, bimpAjax) {
                            if (result.html) {
                                $('#new_sav_form').find('.emailFormSubmit').slideUp(250);
                            }
                        }
                    });
                }
            } else {
                $('#client_email_ajax_result').slideDown(250);
            }
        }
    };

    this.loadClientContactInfos = function (id_contact) {
        var $form = $('#customer_infos');
        if ($form.length) {
            if (id_contact) {
                $form.find('[name="client_firstname"]').addClass('disabled');
                $form.find('[name="client_lastname"]').addClass('disabled');
                $form.find('[name="client_address"]').addClass('disabled');
                $form.find('[name="client_zip"]').addClass('disabled');
                $form.find('[name="client_town"]').addClass('disabled');
                $form.find('[name="client_phone_mobile"]').addClass('disabled');
                $form.find('[name="client_phone_perso"]').addClass('disabled');
                $form.find('[name="client_phone_pro"]').addClass('disabled');

                BimpAjax('loadClientContactInfo', {
                    'id_contact': id_contact
                }, null, {
                    display_success: false,
                    display_errors: false,
                    display_warnings: false,
                    success: function (result, bimpAjax) {
                        var $form = $('#customer_infos');

                        if ($form.length) {
                            $form.find('select[name="client_civility"]').val(result.civility).change();
                            $form.find('input[name="client_firstname"]').removeClass('disabled').val(result.firstname);
                            $form.find('input[name="client_lastname"]').removeClass('disabled').val(result.lastname);
                            $form.find('textarea[name="client_address"]').removeClass('disabled').val(result.address);
                            $form.find('input[name="client_zip"]').removeClass('disabled').val(result.zip);
                            $form.find('input[name="client_town"]').removeClass('disabled').val(result.town);
                            $form.find('input[name="client_pays"]').val(result.fk_pays).change();
                            $form.find('input[name="client_phone_mobile"]').removeClass('disabled').val(result.tel_mobile);
                            $form.find('input[name="client_phone_perso"]').removeClass('disabled').val(result.tel_perso);
                            $form.find('input[name="client_phone_pro"]').removeClass('disabled').val(result.tel_pro);
                        }
                    },
                    error: function (result, bimpAjax) {
                        var $form = $('#customer_infos');

                        if ($form.length) {
                            $form.find('[name="client_firstname"]').removeClass('disabled');
                            $form.find('[name="client_lastname"]').removeClass('disabled');
                            $form.find('[name="client_address"]').removeClass('disabled');
                            $form.find('[name="client_zip"]').removeClass('disabled');
                            $form.find('[name="client_town"]').removeClass('disabled');
                            $form.find('[name="client_phone_mobile"]').removeClass('disabled');
                            $form.find('[name="client_phone_perso"]').removeClass('disabled');
                            $form.find('[name="client_phone_pro"]').removeClass('disabled');
                        }
                    }
                });
            } else {
                $form.find('select[name="client_civility"]').val('').change();
                $form.find('input[name="client_firstname"]').val('');
                $form.find('input[name="client_lastname"]').val('');
                $form.find('textarea[name="client_address"]').val('');
                $form.find('input[name="client_zip"]').val('');
                $form.find('input[name="client_town"]').val('');
                $form.find('input[name="client_pays"]').val(1).change();
                $form.find('input[name="client_phone_mobile"]').val('');
                $form.find('input[name="client_phone_perso"]').val('');
                $form.find('input[name="client_phone_pro"]').val('');
            }
        }
    };

    this.fetchAvailableSlots = function () {
        if (ptr.$form && ptr.$form.length) {
            var $code_prod = ptr.$form.find('select[name="eq_type"]');
            var $centre = ptr.$form.find('[name="sav_centre"]');

            if ($code_prod.length && $centre.length) {
                var code_prod = $code_prod.val();
                var centre = $centre.val();

                if (code_prod && centre) {
                    $('#rdv_form_ajax_result').stop().slideUp(250, function () {
                        $(this).html('');
                    });

                    BimpAjax('publicSavFormFetchAvailableSlots', {
                        'code_product': code_prod,
                        'code_centre': centre
                    }, $('#rdv_form_ajax_result'), {
                        'display_success': false,
                        'display_processing': false,
                        'display_warnings_in_popup_only': false,
                        'append_html': true
                    });
                    return;
                }
            }
        }
    };

    // Traitements: 

    this.checkCanSubmit = function () {
        var $btn = $('#savFormSubmit');

        if ($btn.length) {
            $btn.addClass('disabled');

            if ($.isOk(ptr.$form)) {
                var $input = ptr.$form.find('[name="reservation_id"]');

                if ($input.length) {
                    if ($input.val()) {
                        $btn.removeClass('disabled');
                        return true;
                    }
                }
            }

            // Check du slot: 
            var $form = $('#sav_slot');
            if ($form.length) {
                var $day = $form.find('[name="sav_day"]');

                if ($day.length) {
                    var day = $day.val();

                    if (day) {
                        var $slot = $form.find('[name="sav_slot_' + day + '"]');

                        if ($slot.length) {
                            var slot = $slot.val();

                            if (slot) {
                                $btn.removeClass('disabled');
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    };

    this.checkForm = function () {
        var errors = [];
        var check = true;
        var $form = ptr.$form;

        if ($form.length) {
            // Checks des inputs required: 
            for (var i in ptr.inputs) {
                var $input = $form.find('[name="' + ptr.inputs[i] + '"]');

                if ($input.length) {
                    if ($input.hasClass('required')) {
                        if ($input.val() === '') {
                            if ($input.tagName() === 'select') {
                                $input.parent().children('span.select2').addClass('value_missing');
                            } else {
                                $input.addClass('value_missing');
                            }
                            check = false;
                        } else {
                            if ($input.tagName() === 'select') {
                                $input.parent().children('span.select2').removeClass('value_missing');
                            } else {
                                $input.removeClass('value_missing');
                            }
                        }
                    }
                }
            }

            // Check nom société: 
            if ($form.find('[name="client_type"]').length) {
                var fk_typent = parseInt($form.find('[name="client_type"]').val());

                if (fk_typent !== 0 && fk_typent !== 8) {
                    var $nomSoc = $form.find('[name="client_nom_societe"]');

                    if ($nomSoc.length) {
                        if ($nomSoc.val() === '') {
                            $nomSoc.addClass('value_missing');
                            check = false;
                        } else {
                            $nomSoc.removeClass('value_missing');
                        }
                    }

                    var $siret = $form.find('[name="client_siret"]');

                    if ($siret.length) {
                        if ($siret.val() === '') {
                            $siret.addClass('value_missing');
                            check = false;
                        } else {
                            $siret.removeClass('value_missing');
                        }
                    }
                }
            }

            if (!check) {
                errors.push('Certains champ obligatoires ne sont pas renseignés');
            }

            // Check tel:    
            var $telMobile = $form.find('[name="client_phone_mobile"]');
            var $telPerso = $form.find('[name="client_phone_perso"]');
            var $telPro = $form.find('[name="client_phone_pro"]');
            var $telOk = $form.find('[name="phone_ok"]');

            var telMobile = '';
            var telPerso = '';
            var telPro = '';
            var telOk = '';

            if ($telMobile.length) {
                telMobile = $telMobile.val();
            }

            if ($telPerso.length) {
                telPerso = $telPerso.val();
            }

            if ($telPro.length) {
                telPro = $telPro.val();
            }

            if ($telOk.length) {
                telOk = $telOk.val();
            }

            if (!telMobile && !telPerso && !telPro && !telOk) {
                check = false;
                errors.push('Veuillez saisir au moins un numéro de téléphone');
            }
        } else {
            check = false;
        }

        if (errors.length) {
            var msg = '';

            for (var e in errors) {
                msg += errors[e] + '<br/>';
            }

            bimp_msg(msg, 'danger', $('#sav_form_submit_result'));
        } else {
            $('#sav_form_submit_result').stop().slideUp(250, function () {
                $(this).html('');
            });
        }

        return check;
    };

    this.submit = function ($button, force_validate, force_validate_reason) {
        if (sav_form_submit_locked) {
            return;
        }

        if ($button.hasClass('disabled')) {
            return;
        }

        sav_form_submit_locked = true;

        $button.addClass('disabled');

        if (typeof (force_validate) === 'undefined') {
            force_validate = 0;
        }

        if (typeof (force_validate_reason) === 'undefined') {
            force_validate = '';
        }

        $('#sav_form_submit_result').stop().html('').hide();

        if (!force_validate) {
            if (!ptr.checkCanSubmit()) {
                $button.removeClass('disabled');
                sav_form_submit_locked = false;
                return;
            }
        }

        if (!ptr.checkForm()) {
            $button.removeClass('disabled');
            sav_form_submit_locked = false;
            return;
        }

        var data = {
            'force_validate': force_validate,
            'force_validate_reason': force_validate_reason
        };

        var $form = ptr.$form;

        if ($form.length) {
            for (var i in ptr.inputs) {
                var val = '';
                var $input = $form.find('[name="' + ptr.inputs[i] + '"]');

                if ($input.length) {
                    val = $input.val();
                }

                data[ptr.inputs[i]] = val;
            }

            if (typeof (data['reservation_id']) !== 'undefined' && data['reservation_id']) {
                data['sav_slot'] = $form.find('[name="sav_slot"]').val();
            } else {
                var slot = '';
                if (data['sav_day']) {
                    var $input = $form.find('[name="sav_slot_' + data['sav_day'] + '"]');
                    if ($input.length) {
                        slot = $input.val();
                    }
                }
                data['sav_slot'] = slot;
            }

            $('#SlotNotAvailableNotif').stop().slideUp(250);
            $('#reservationErrorNotif').stop().slideUp(250);
            if (!force_validate && !parseInt($('#noReservationSubmit').data('never_hidden'))) {
                $('#noReservationSubmit').stop().slideUp(250);
            }

            $('#debug').html('').hide();

            BimpAjax('savFormSubmit', data, $('#sav_form_submit_result'), {
                $btn: $button,
                display_success: false,
                display_processing: true,
                processing_padding: 20,
                append_html: true,
                success: function (result, bimpAjax) {
                    if (result.debug) {
                        $('#debug').html(result.debug).stop().slideDown(250);
                    }

                    if (result.slot_not_available) {
                        $('#SlotNotAvailableNotif').stop().slideDown(250);
                        $('#noReservationSubmit').find('span.btn').attr('onclick', 'SavPublicForm.submit($(this), 1, \'' + result.force_validate_reason + '\')');
                        $('#noReservationSubmit').stop().slideDown(250);
                        ptr.fetchAvailableSlots();
                        bimpAjax.$btn.removeClass('disabled');
                        sav_form_submit_locked = false;
                    } else if (result.force_validate) {
                        $('#reservationErrorNotif').stop().slideDown(250);
                        $('#noReservationSubmit').find('span.btn').attr('onclick', 'SavPublicForm.submit($(this), 1, \'' + result.force_validate_reason + '\')');
                        $('#noReservationSubmit').stop().slideDown(250);
                        bimpAjax.$btn.removeClass('disabled');
                        sav_form_submit_locked = false;
                    } else if (result.success_html) {
                        $('#new_sav_form').stop().fadeOut(250, function () {
                            $('#new_sav_form').html(result.success_html).fadeIn(250);
                        });
                    }
                },
                error: function (result, bimpAjax) {
                    bimpAjax.$btn.removeClass('disabled');
                    sav_form_submit_locked = false;
                }
            });
        } else {
            $button.removeClass('disabled');
            sav_form_submit_locked = false;
        }
    };

    this.cancelRDV = function ($btn, id_sav, ref_sav, reservation_id) {
        BimpAjax('cancelSav', {
            id_sav: id_sav,
            ref_sav: ref_sav,
            reservation_id: reservation_id
        }, $('#cancelSavAjaxResult'), {
            $button: $btn,
            display_success: false,
            display_processing: true,
            processing_padding: 20,
            success: function (result, bimpAjax) {
                if (result.success_html) {
                    $('#cancel_confirm').fadeOut(250, function () {
                        $(this).html(result.success_html).fadeIn(250);
                    });
                }
            }
        });
    };
}

var SavPublicForm = new SavPublicForm();

$(document).ready(function () {
    SavPublicForm.setEmailFormEvents();
});