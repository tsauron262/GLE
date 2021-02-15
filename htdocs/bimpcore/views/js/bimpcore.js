// Mettre ici les fonctions spécifiques aux objets bimpcore 

// Clients: 

function onSocieteFormLoaded($form) {
    var $input = $form.find('[name="siret"]');
    if ($input.length) {
        if (!parseInt($input.data('siret_input_change_event_init'))) {
            $input.change(function () {
                var val = $(this).val();
                if (val) {
                    onSocieteSiretOrSirenChange($(this), 'siret', val);
                }
            });
            $input.data('siret_input_change_event_init', 1);
        }
    }

    $input = $form.find('[name="siren"]');
    if ($input.length) {
        if (!parseInt($input.data('siren_input_change_event_init'))) {
            $input.change(function () {
                var val = $(this).val();
                if (val) {
                    onSocieteSiretOrSirenChange($(this), 'siren', val);
                }
            });
            $input.data('siren_input_change_event_init', 1);
        }
    }
}

function onSocieteSiretOrSirenChange($input, field, value) {
    if (typeof (value) === 'undefined') {
        if (!$.isOk($input)) {
            bimp_msg('Champ non trouvé', 'danger');
            return;
        }

        value = $input.val();
    }

//    var $form = $input.findParentByClass('object_form');
    var $form = $input.findParentByClass('Bimp_Client_form');

    if (!$.isOk($form)) {
        $form = $input.findParentByClass('Bimp_Fournisseur_form');
    }

    if (!$.isOk($form)) {
        $form = $input.findParentByClass('Bimp_Societe_form');
    }

    var id_object = parseInt($form.data('id_object'));

    $form.find('[name="siret"]').addClass('disabled');
    $form.find('[name="siren"]').addClass('disabled');

    if (!id_object) {
        $form.find('[name="nom"]').addClass('disabled');
        $form.find('[name="address"]').addClass('disabled');
        $form.find('[name="zip"]').addClass('disabled');
        $form.find('[name="town"]').addClass('disabled');
        $form.find('[name="phone"]').addClass('disabled');
        $form.find('[name="tva_intra"]').addClass('disabled');
//        $form.find('[name="outstanding_limit"]').addClass('disabled');
        $form.find('[name="capital"]').addClass('disabled');
        $form.find('[name="ape"]').addClass('disabled');
        $form.find('[name="notecreditsafe"]').addClass('disabled');
    }

    if ($.isOk($form)) {
        BimpAjax('checkSocieteSiren', {
            module: $form.data('module'),
            object_name: $form.data('object_name'),
            id_object: $form.data('id_object'),
            field: field,
            value: value
        }, $form.find('.ajaxResultContainer'), {
            url: dol_url_root + '/bimpcore/index.php?fc=societe',
            $form: $form,
            id_object: id_object,
            display_success: false,
            display_errors_in_popup_only: true,
            display_warnings_in_popup_only: true,
            success: function (result, bimpAjax) {
                $form.find('[name="siret"]').removeClass('disabled');
                $form.find('[name="siren"]').removeClass('disabled');
                $form.find('[name="nom"]').removeClass('disabled');
                $form.find('[name="address"]').removeClass('disabled');
                $form.find('[name="zip"]').removeClass('disabled');
                $form.find('[name="town"]').removeClass('disabled');
                $form.find('[name="phone"]').removeClass('disabled');
                $form.find('[name="tva_intra"]').removeClass('disabled');
//                $form.find('[name="outstanding_limit"]').removeClass('disabled');
                $form.find('[name="capital"]').removeClass('disabled');
                $form.find('[name="ape"]').removeClass('disabled');
                $form.find('[name="notecreditsafe"]').removeClass('disabled');

                if (typeof (result.data.siret) === 'string' && result.data.siret) {
                    $form.find('[name="siret"]').val(result.data.siret);
                }

                if (typeof (result.data.siren) === 'string' && result.data.siren) {
                    $form.find('[name="siren"]').val(result.data.siren);
                }

                $form.find('[name="siren_ok"]').val(result.siren_ok);

                if (!id_object) {
                    if (typeof (result.data.siret) === 'string' && result.data.siret) {
                        $form.find('[name="siret"]').val(result.data.siret);
                    }

                    if (typeof (result.data.nom) === 'string' && result.data.nom) {
                        $form.find('[name="nom"]').val(result.data.nom);
                    }

                    if (typeof (result.data.address) === 'string' && result.data.address) {
                        $form.find('[name="address"]').val(result.data.address);
                    }

                    if (typeof (result.data.zip) === 'string' && result.data.zip) {
                        $form.find('[name="zip"]').val(result.data.zip);
                    }

                    if (typeof (result.data.town) === 'string' && result.data.town) {
                        $form.find('[name="town"]').val(result.data.town);
                    }

                    if (typeof (result.data.phone) === 'string' && result.data.phone) {
                        $form.find('[name="phone"]').val(result.data.phone);
                    }

                    if (typeof (result.data.tva_intra) === 'string' && result.data.tva_intra) {
                        $form.find('[name="tva_intra"]').val(result.data.tva_intra);
                    }

//                    if (typeof (result.data.outstanding_limit) === 'string' && result.data.outstanding_limit) {
//                        $form.find('[name="outstanding_limit"]').val(result.data.outstanding_limit);
//                    }

                    if (typeof (result.data.capital) === 'string' && result.data.capital) {
                        $form.find('[name="capital"]').val(result.data.capital);
                    }

                    if (typeof (result.data.ape) === 'string' && result.data.ape) {
                        $form.find('[name="ape"]').val(result.data.ape);
                    }

                    if (typeof (result.data.notecreditsafe) === 'string' && result.data.notecreditsafe) {
                        $form.find('[name="notecreditsafe"]').val(result.data.notecreditsafe);
                    }
                }
            },
            error: function (result, bimpAjax) {
                $form.find('[name="siret"]').removeClass('disabled');
                $form.find('[name="siren"]').removeClass('disabled');
                $form.find('[name="nom"]').removeClass('disabled');
                $form.find('[name="address"]').removeClass('disabled');
                $form.find('[name="zip"]').removeClass('disabled');
                $form.find('[name="town"]').removeClass('disabled');
                $form.find('[name="phone"]').removeClass('disabled');
                $form.find('[name="tva_intra"]').removeClass('disabled');
//                $form.find('[name="outstanding_limit"]').removeClass('disabled');
                $form.find('[name="capital"]').removeClass('disabled');
                $form.find('[name="ape"]').removeClass('disabled');
                $form.find('[name="notecreditsafe"]').removeClass('disabled');
            }
        });
    }
}

function checkSocieteTva(tva, title) {
    newpopup(dol_url_root + '/societe/checkvat/checkVatPopup.php?vatNumber=' + tva, title, 500, 285);
}

function onClientAddFreeRelanceFormSubmit($form, extra_data) {
    extra_data['factures'] = [];

    if ($.isOk($form)) {
        var $table = $form.find('table.bimp_factures_list');

        if ($.isOk($table)) {
            var rows = BimpListTable.getSelectedRows($table);

            for (i in rows) {
                var id_facture = parseInt(rows[i].data('id_facture'));

                if (id_facture && !isNaN(id_facture)) {
                    var activate_relances = 0;
                    var $input = rows[i].find('input[name="fac_' + id_facture + '_activate_relances"]');

                    if ($input.length) {
                        activate_relances = parseInt($input.val());
                    }

                    extra_data['factures'].push({
                        'id': id_facture,
                        'activate_relances': activate_relances
                    });
                }
            }
        }
    }

    if (!extra_data['factures'].length) {
        bimp_msg('Aucune facture sélectionnée', 'warning', null, true);
        return null;
    }

    return extra_data;
}

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if ($.isOk(e.$form)) {
            if (e.$form.hasClass('Bimp_Client_form') || e.$form.hasClass('Bimp_Fournisseur_form') || e.$form.hasClass('Bimp_Societe_form')) {
                onSocieteFormLoaded(e.$form);
            }
        }
    });
});
