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
        $form.find('[name="outstanding_limit_credit_safe"]').addClass('disabled');
        $form.find('[name="capital"]').addClass('disabled');
        $form.find('[name="ape"]').addClass('disabled');
        $form.find('[name="notecreditsafe"]').addClass('disabled');
        $form.find('[name="lettrecreditsafe"]').addClass('disabled');
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
                $form.find('[name="outstanding_limit_credit_safe"]').removeClass('disabled');
                $form.find('[name="capital"]').removeClass('disabled');
                $form.find('[name="ape"]').removeClass('disabled');
                $form.find('[name="notecreditsafe"]').removeClass('disabled');
                $form.find('[name="lettrecreditsafe"]').removeClass('disabled');

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

                    if (result.data.siret.substr(0, 1) == '1' || result.data.siret.substr(0, 1) == '2') {
                        $form.find('[name="fk_typent"]').val(5);
                        $form.find('[name="fk_typent"]').change();
                    }

                    if (typeof (result.data.nom) === 'string' && result.data.nom) {
                        $form.find('[name="nom"]').val(result.data.nom);
                    }
                    if (typeof (result.data.rcs) === 'string' && result.data.rcs) {
                        $form.find('[name="idprof4"]').val(result.data.rcs);
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

                    if (typeof (result.data.outstanding_limit) === 'string' && result.data.outstanding_limit) {
                        $form.find('[name="outstanding_limit_credit_safe"]').val(result.data.outstanding_limit);
                        $form.find('[name="outstanding_limit_credit_safe"]').parent().find('span').html(result.data.outstanding_limit + " €");
                    }

                    if (typeof (result.data.capital) === 'string' && result.data.capital) {
                        $form.find('[name="capital"]').val(result.data.capital);
                    }

                    if (typeof (result.data.ape) === 'string' && result.data.ape) {
                        $form.find('[name="ape"]').val(result.data.ape);
                    }

                    if (typeof (result.data.notecreditsafe) === 'string' && result.data.notecreditsafe) {
                        $form.find('[name="notecreditsafe"]').val(result.data.notecreditsafe);
                    }

                    if (typeof (result.data.lettrecreditsafe) === 'string' && result.data.lettrecreditsafe) {
                        $form.find('[name="lettrecreditsafe"]').val(result.data.lettrecreditsafe);
                    }

                    if (typeof (result.data.alert) === 'string' && result.data.alert) {
                        alert(result.data.alert)
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
                $form.find('[name="lettrecreditsafe"]').removeClass('disabled');
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

// UserRights: 

function BimpUserRightsTable() {
    var ptr = this;
    this.getTable = function ($element) {
        if (!$.isOk($element)) {
            return null;
        }

        var $container = $element.findParentByClass('panel-body');

        if (!$.isOk($container)) {
            $container = $element.findParentByClass('tab-content');
        }

        if ($.isOk($container)) {
            return $container.find('table.bimp_user_rights_table');
        }

        return null;
    };

    this.getRow = function ($table, id_right, id_entity) {
        if (typeof (id_entity) === 'undefined') {
            id_entity = 1;
        }

        if ($.isOk($table)) {
            return $table.find('tr.bimp_list_table_row[data-id_right=' + id_right + '][data-id_entity=' + id_entity + ']');
        }

        return null;
    };

    this.addUserRights = function ($button, id_user, id_rights, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        var $table = ptr.getTable($button);

        setObjectAction($button, {
            module: 'bimpcore',
            object_name: 'Bimp_User',
            id_object: id_user,
        }, 'addRight', {
            id_rights: id_rights,
            id_entities: id_entities
        }, null, function (result) {
            if (typeof (result.results) !== 'undefined') {
                for (var id_right in result.results) {
                    for (var id_entity in result.results[id_right]) {
                        if (parseInt(result.results[id_right][id_entity])) {
                            var $row = ptr.getRow($table, id_right, id_entity);
                            if ($.isOk($row)) {
                                $row.find('.add_right_button').hide();
                                $row.find('.remove_right_button').show();

                                var $col = $row.find('td.col_active');

                                if ($col.length) {
                                    $col.data('value', 'yes');
                                    $col.html('<span class="success"><i class="fas fa5-check iconLeft"></i>OUI</span>');
                                }

                                // On déselectionne toutes les lignes: 
                                if (id_rights.length > 1) {
                                    BimpListTable.uncheckAll($table);
                                }
                            }
                        }
                    }
                }
            }
        });
    };

    this.removeUserRights = function ($button, id_user, id_rights, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        var $table = ptr.getTable($button);

        setObjectAction($button, {
            module: 'bimpcore',
            object_name: 'Bimp_User',
            id_object: id_user
        }, 'removeRight', {
            id_rights: id_rights,
            id_entities: id_entities
        }, null, function (result) {
            if (typeof (result.results) !== 'undefined') {
                for (var id_right in result.results) {
                    for (var id_entity in result.results[id_right]) {
                        if (parseInt(result.results[id_right][id_entity]['ok'])) {
                            var $row = ptr.getRow($table, id_right, id_entity);
                            if ($.isOk($row)) {

                                $row.find('.remove_right_button').hide();
                                $row.find('.add_right_button').show();

                                var $col = $row.find('td.col_active');

                                if ($col.length) {
                                    $col.data('value', result.results[id_right][id_entity]['active']);

                                    switch (result.results[id_right][id_entity]['active']) {
                                        case 'inherit':
                                            $col.html('<span class="info"><i class="fas fa5-arrow-circle-down iconLeft"></i>Hérité</span>');
                                            break;

                                        case 'no':
                                            $col.html('<span class="danger"><i class="fas fa5-times iconLeft"></i>NON</span>');
                                            break;
                                    }
                                }

                                // On déselectionne toutes les lignes: 
                                if (id_rights.length > 1) {
                                    BimpListTable.uncheckAll($table);
                                }
                            }
                        }
                    }
                }
            }
        });

    };

    this.addSelectedRights = function ($button, id_user, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        $button.addClass('disabled');
        var $table = ptr.getTable($button);

        if ($.isOk($table)) {
            var $selected = $table.find('tbody').find('input.bimp_list_table_row_check:checked');

            if (!$selected.length) {
                bimp_msg('Aucun droit sélectionné', 'warning', null, true);
                $button.removeClass('disabled');
                return;
            }

            var id_rights = [];

            $selected.each(function () {
                var $row = $(this).findParentByClass('bimp_list_table_row');

                if ($.isOk($row)) {
                    var id_right = parseInt($row.data('id_right'));

                    if (!isNaN(id_right) && id_right) {
                        id_rights.push(id_right);
                    }
                }
            });

            $button.removeClass('disabled'); // On doit réactiver le bouton sinon la suite va planter.
            ptr.addUserRights($button, id_user, id_rights, id_entities);
        }
    };

    this.removeSelectedRights = function ($button, id_user, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        $button.addClass('disabled');
        var $table = ptr.getTable($button);

        if ($.isOk($table)) {
            var $selected = $table.find('tbody').find('input.bimp_list_table_row_check:checked');

            if (!$selected.length) {
                bimp_msg('Aucun droit sélectionné', 'warning', null, true);
                $button.removeClass('disabled');
                return;
            }

            var id_rights = [];

            $selected.each(function () {
                var $row = $(this).findParentByClass('bimp_list_table_row');

                if ($.isOk($row)) {
                    var id_right = parseInt($row.data('id_right'));

                    if (!isNaN(id_right) && id_right) {
                        id_rights.push(id_right);
                    }
                }
            });

            $button.removeClass('disabled');
            ptr.removeUserRights($button, id_user, id_rights, id_entities);
        }
    };
}

var BimpUserRightsTable = new BimpUserRightsTable();

//UserGroupRights

function BimpUserGroupRightsTable() {
    var ptr = this;
    this.getTable = function ($element) {
        if (!$.isOk($element)) {
            return null;
        }

//        return $element.findParentByClass('panel-body').find('table.bimp_user_rights_table'); // Attention à bien renommer les identifiants. 
        return $element.findParentByClass('panel-body').find('table.bimp_usergroup_rights_table');
    };

    this.getRow = function ($table, id_right) {
        if ($.isOk($table)) {
            return $table.find('tr.bimp_list_table_row[data-id_right=' + id_right + ']');
        }

        return null;
    };

    // Nommer correctemenet les fonctions. Ici, l'objet sur lequel on travail c'est UserGroup, pas User. 
//    this.addUserRights = function ($button, id_group, id_rights) {
    this.addUserGroupRights = function ($button, id_group, id_rights, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        var $table = ptr.getTable($button);

        setObjectAction($button, {
            module: 'bimpcore',
            object_name: 'Bimp_UserGroup',
            id_object: id_group
        }, 'addRight', {
            id_rights: id_rights,
            id_entities: id_entities
        }, null, function (result) {
            if (typeof (result.results) !== 'undefined') {
                for (var id_right in result.results) {
                    if (parseInt(result.results[id_right])) {
                        var $row = ptr.getRow($table, id_right);
                        if ($.isOk($row)) {
                            $row.find('.add_right_button').hide();
                            $row.find('.remove_right_button').show();

                            var $col = $row.find('td.col_active');

                            if ($col.length) {
                                $col.data('value', 'yes');
                                $col.html('<span class="success"><i class="fas fa5-check iconLeft"></i>OUI</span>');
                            }

                            // On déselectionne toutes les lignes: 
                            if (id_rights.length > 1) {
                                BimpListTable.uncheckAll($table);
                            }
                        }
                    }
                }
            }
        });
    };

    this.removeUserGroupRights = function ($button, id_group, id_rights, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        var $table = ptr.getTable($button);

        setObjectAction($button, {
            module: 'bimpcore',
            object_name: 'Bimp_UserGroup',
            id_object: id_group
        }, 'removeRight', {
            id_rights: id_rights,
            id_entities: id_entities
        }, null, function (result) {
            if (typeof (result.results) !== 'undefined') {
                for (var id_right in result.results) {
                    if (parseInt(result.results[id_right])) {
                        var $row = ptr.getRow($table, id_right);
                        if ($.isOk($row)) {
                            $row.find('.remove_right_button').hide();
                            $row.find('.add_right_button').show();

                            var $col = $row.find('td.col_active');

                            if ($col.length) {
                                $col.data('value', 'no');
                                $col.html('<span class="danger"><i class="fas fa5-times iconLeft"></i>NON</span>');
                            }

                            // On déselectionne toutes les lignes: 
                            if (id_rights.length > 1) {
                                BimpListTable.uncheckAll($table);
                            }
                        }
                    }
                }
            }
        });

    };

    this.addSelectedRights = function ($button, id_group, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        $button.addClass('disabled');
        var $table = ptr.getTable($button);

        if ($.isOk($table)) {
            var $selected = $table.find('tbody').find('input.bimp_list_table_row_check:checked');

            if (!$selected.length) {
                bimp_msg('Aucun droit sélectionné', 'warning', null, true);
                $button.removeClass('disabled');
                return;
            }

            var id_rights = [];

            $selected.each(function () {
                var $row = $(this).findParentByClass('bimp_list_table_row');

                if ($.isOk($row)) {
                    var id_right = parseInt($row.data('id_right'));

                    if (!isNaN(id_right) && id_right) {
                        id_rights.push(id_right);
                    }
                }
            });

            $button.removeClass('disabled'); // On doit réactiver le bouton sinon la suite va planter.
            ptr.addUserGroupRights($button, id_group, id_rights, id_entities);
        }
    };

    this.removeSelectedRights = function ($button, id_group, id_entities) {
        if ($button.hasClass('disabled')) {
            return;
        }

        if (typeof (id_entities) === 'undefined') {
            id_entities = [];
        }

        $button.addClass('disabled');
        var $table = ptr.getTable($button);

        if ($.isOk($table)) {
            var $selected = $table.find('tbody').find('input.bimp_list_table_row_check:checked');

            if (!$selected.length) {
                bimp_msg('Aucun droit sélectionné', 'warning', null, true);
                $button.removeClass('disabled');
                return;
            }

            var id_rights = [];

            $selected.each(function () {
                var $row = $(this).findParentByClass('bimp_list_table_row');

                if ($.isOk($row)) {
                    var id_right = parseInt($row.data('id_right'));

                    if (!isNaN(id_right) && id_right) {
                        id_rights.push(id_right);
                    }
                }
            });

            $button.removeClass('disabled');
            ptr.removeUserGroupRights($button, id_group, id_rights, id_entities);
        }
    };
}

var BimpUserGroupRightsTable = new BimpUserGroupRightsTable();

// UserGroup Users

function BimpUsergroupUsersTable() {
    var ptr = this;

    this.getTable = function ($element) {
        if (!$.isOk($element)) {
            return null;
        }

        return $element.findParentByClass('panel').find('#group_users_list');
    };

    this.removeSelectedUsers = function ($button, id_group) {
        if ($button.hasClass('disabled')) {
            return;
        }

        $button.addClass('disabled');
        var $table = ptr.getTable($button);

        if ($.isOk($table)) {
            var $selected = $table.find('tbody').find('input.bimp_list_table_row_check:checked');

            if (!$selected.length) {
                bimp_msg('Aucun utilisateur sélectionné', 'warning', null, true);
                $button.removeClass('disabled');
                return;
            }

            var ids_users = [];

            $selected.each(function () {
                var $row = $(this).findParentByClass('bimp_list_table_row');

                if ($.isOk($row)) {
                    var id_user = parseInt($row.data('id_user'));

                    if (!isNaN(id_user) && id_user) {
                        ids_users.push(id_user);
                    }
                }
            });

            if (ids_users.length) {
                if (confirm('Veuillez confirmer la suppression de ' + ids_users.length + ' utilisateur(s)')) {
                    $button.removeClass('disabled');
                    setObjectAction($button, {
                        module: 'bimpcore',
                        object_name: 'Bimp_UserGroup',
                        id_object: id_group
                    }, 'removeUsers', {
                        ids_users: ids_users
                    });
                }
            }
        }
    };
}

var BimpUsergroupUsersTable = new BimpUsergroupUsersTable();

// Divers: 

function getBadge(text, size, style) {
    return '<span class="badge badge-pill badge-' + style + '" style="size:' + size + '">' + text + '</span>';
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
