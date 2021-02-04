function loadUserConfigsModalList($button, id_user, config_object_name, filters, title, success_callback) {
    if (typeof (title) !== 'string' || !title) {
        title = 'Gestion des configurations';
    }
    bimpModal.loadAjaxContent($button, 'loadUserConfigsList', {
        id_user: id_user,
        config_object_name: config_object_name,
        config_filters: filters
    }, title, 'Chargement', function (result, bimpAjax) {
        var $new_list = bimpAjax.$resultContainer.find('#' + result.list_id);
        if ($new_list.length) {
            $new_list.data('modal_idx', bimpAjax.$resultContainer.data('idx'));
            bimpModal.removeComponentContent($new_list.attr('id'));
            onListLoaded($new_list);
        }

        if (typeof (success_callback) === 'function') {
            success_callback(result, bimpAjax);
        }
    }, {}, 'large');
}

function loadBCUserConfigsModalList($button, id_user, identifier, config_object_name, title, success_callback) {
    var $comp = $('#' + identifier);

    if (!$comp.length) {
        bimp_msg('Une erreur est survenue. Chargement de la liste des configurations impossible');
        console.error('loadBCUserConfigsModalList: composant non trouvé pour l\'identifiant "' + identifier + '"');
        return;
    }

    var filters = {
        obj_module: $comp.data('module'),
        obj_name: $comp.data('object_name'),
        component_name: $comp.data('name')
    };

    loadUserConfigsModalList($button, id_user, config_object_name, filters, title, success_callback);
}

// ListTableConfig: 

function ListTableConfig() {
    var config = this;

    // Chargements ajax: 

    this.loadFieldOptions = function ($resultContainer, module, object_name, list_name, field_name, field_prefixe) {
        loadObjectCustomContent(null, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig'
        }, 'renderFieldConfigOptions', {
            module: module,
            object_name: object_name,
            list_name: list_name,
            field_name: field_name,
            field_prefixe: field_prefixe
        }, function (result, bimpAjax) {
            var $colsConfigContainer = bimpAjax.$resultContainer.findParentByClass('list_add_col_container');
            if ($.isOk($colsConfigContainer)) {
                $colsConfigContainer.find('.list_add_col_submit_container').stop().slideDown(250);
                config.setColOptionsFormEvents($colsConfigContainer);
            }
        });
    };

    this.loadLinkedObjectOptions = function ($resultContainer, module, object_name, linked_object, object_label, fields_prefixe) {
        loadObjectCustomContent(null, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig'
        }, 'renderLinkedObjectOptions', {
            module: module,
            object_name: object_name,
            linked_object: linked_object,
            object_label: object_label,
            fields_prefixe: fields_prefixe
        }, function (result, bimpAjax) {
            config.setColsConfigEvents(bimpAjax.$resultContainer);
        });
    };

    this.loadChildrenOptions = function ($resultContainer, module, object_name, children) {

    };

    this.loadDisplayOptions = function ($resultContainer, module, object_name, field_name, display_name) {
        loadObjectCustomContent(null, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig'
        }, 'renderFieldDisplayOptions', {
            module: module,
            object_name: object_name,
            field_name: field_name,
            display_name: display_name
        }, function (result, bimpAjax) {
//        var $colsConfigContainer = bimpAjax.$resultContainer.findParentByClass('list_add_col_container');
//        if ($.isOk($colsConfigContainer)) {
//            $colsConfigContainer.find('.list_add_col_submit_container').stop().slideDown(250);
//            config.setColOptionsFormEvents($colsConfigContainer);
//        }
        });
    };

    this.loadDefaultCols = function ($button) {
    };

    // Traitements:

    this.resetColsConfigForm = function ($container) {
        if ($.isOk($container)) {
            var $options = $container.find('.col_type_item_options');

            if ($options.length) {
                $options.slideUp(250, function () {
                    $(this).html('');

                    $container.find('.objectColTypeItemsSelectContainer').each(function () {
                        $(this).find('select').val('').change();
                    });
                });
            }
        }
    };

    this.checkColsListItems = function ($container) {
        if ($.isOk($container)) {
            var $colsList = $container.find('.inputMultipleValues');

            if ($.isOk($colsList)) {
                var $rows = $colsList.find('tbody.multipleValuesList').children('tr.itemRow');

                var $no_cols = $container.find('.no_cols');
                if ($.isOk($no_cols)) {
                    if ($rows.length) {
                        $no_cols.stop().hide();
                    } else {
                        $no_cols.stop().slideDown(250);
                    }
                }

                if ($rows.length) {
                    $colsList.show();
                } else {
                    $colsList.hide();
                }

                var $removeAllButton = $container.find('.removeAllColsButton');
                if ($.isOk($removeAllButton)) {
                    if ($rows.length > 1) {
                        $removeAllButton.show();
                    } else {
                        $removeAllButton.hide();
                    }
                }
            }
        }
    };

    this.getColOptionsFormData = function ($form) {
        var data = {};

        if ($.isOk($form)) {
            $form.find('.colOptionInput').each(function () {
                var input_name = $(this).data('input_name');
                if (input_name) {
                    var $input = $(this).find('[name="' + input_name + '"]');
                    if ($input.length) {
                        data[input_name] = $input.val();
                    }
                }
            });

            data['display_options'] = {};

            $form.find('.fieldDisplayOptionInput').each(function () {
                var input_name = $(this).data('input_name');
                if (input_name) {
                    var $input = $(this).find('[name="' + input_name + '"]');
                    if ($input.length) {
                        data['display_options'][input_name] = $input.val();
                    }
                }
            });
        }

        return data;
    };

    // Actions Ajax: 

    this.addFieldCol = function ($button, col_name) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        var $configContainer = $button.findParentByClass('list_cols_config_container');

        if (!$.isOk($configContainer)) {
            bimp_msg('Une erreur est survenue. Ajout de la colonne impossible', 'danger', null, true);
            console.error('ListTableConfig::addFieldCol(): $container absent');
            return;
        }

        var $form = $button.findParentByClass('list_col_options_form').children('form');

        if (!$.isOk($form)) {
            bimp_msg('Une erreur est survenue. Ajout de la colonne impossible', 'danger', null, true);
            console.error('ListTableConfig::addFieldCol(): $form absent');
            return;
        }

        var id_config = parseInt($configContainer.data('id_config'));
        var data = {
            col_name: col_name,
            options: config.getColOptionsFormData($form)
        };

        setObjectAction($button, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig',
            id_object: id_config
        }, 'addCol', data, null, null, function (result) {
            if (typeof (result.col_html) !== 'undefined' && result.col_html) {
                var $cols_list = $configContainer.find('.list_cols_list_container');
                if ($.isOk($cols_list)) {
                    $cols_list.find('tbody.multipleValuesList').append(result.col_html);
                    config.checkColsListItems($cols_list);
                    config.setColsListItemsEvents($cols_list);
                }

                $('body').trigger($.Event('listTableColsChange', {
                    id_config: id_config
                }));
            }

            config.resetColsConfigForm($configContainer);
        });
    };

    this.saveColOptions = function ($button, id_config, col_name) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        var $form = $button.findParentByClass('list_col_options_form').children('form');

        if (!$.isOk($form)) {
            bimp_msg('Une erreur est survenue. Mise à jour des options de la colonne impossible', 'danger', null, true);
            console.error('ListTableConfig::saveColOptions(): $form absent');
            return;
        }

        var data = {
            col_name: col_name,
            options: config.getColOptionsFormData($form)
        };

        setObjectAction($button, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig',
            id_object: id_config
        }, 'saveColOptions', data, null, null, function (result) {
            $('body').trigger($.Event('listTableColsChange', {
                id_config: id_config
            }));
        });
    };

    this.saveColsPositions = function ($tbody) {
        if ($.isOk($tbody)) {
            var $configContainer = $tbody.findParentByClass('list_cols_config_container');

            if (!$.isOk($configContainer)) {
                bimp_msg('Une erreur est survenue. Mise à jour de l\'ordre des colonnes impossible', 'danger', null, true);
                console.error('ListTableConfig::saveColsPositions(): $container absent');
                return;
            }

            var id_config = parseInt($configContainer.data('id_config'));

            var data = {
                'cols': []
            };

            $tbody.children('tr.listCols_itemRow').each(function () {
                var col_name = $(this).data('col_name');
                if (col_name) {
                    data.cols.push(col_name);
                }
            });

            setObjectAction($(), {
                module: 'bimpuserconfig',
                object_name: 'ListTableConfig',
                id_object: id_config
            }, 'saveColsPositions', data, null, null, function (result) {
                $('body').trigger($.Event('listTableColsChange', {
                    id_config: id_config
                }));
            });
        }
    };

    this.removeCol = function ($button, col_name) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        var $configContainer = $button.findParentByClass('list_cols_config_container');

        if (!$.isOk($configContainer)) {
            bimp_msg('Une erreur est survenue. Retrait de la colonne impossible', 'danger', null, true);
            console.error('ListTableConfig::removeCol(): $container absent');
            return;
        }

        var id_config = parseInt($configContainer.data('id_config'));
        var data = {
            col_name: col_name
        };

        setObjectAction($button, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig',
            id_object: id_config
        }, 'removeCol', data, null, null, function (result) {
            var $tr = $button.findParentByClass('itemRow');
            if ($.isOk($tr)) {
                var $parent = $tr.findParentByClass('list_cols_list_container');
                $tr.remove();
                config.checkColsListItems($parent);

                $('body').trigger($.Event('listTableColsChange', {
                    id_config: id_config
                }));
            }
        });
    };

    this.removeAllCols = function ($button) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        if (!confirm('Veuillez confirmer le retrait de toutes les colonnes')) {
            return;
        }

        var $configContainer = $button.findParentByClass('list_cols_config_container');

        if (!$.isOk($configContainer)) {
            bimp_msg('Une erreur est survenue. Retrait de la colonne impossible', 'danger', null, true);
            console.error('ListTableConfig::removeCol(): $container absent');
            return;
        }

        var id_config = parseInt($configContainer.data('id_config'));

        setObjectAction($button, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig',
            id_object: id_config
        }, 'removeAllCols', {}, null, null, function (result) {
            var $rows = $configContainer.find('.inputMultipleValues').find('tbody.multipleValuesList').children('tr.itemRow');

            if ($rows.length) {
                $rows.remove();
            }

            config.checkColsListItems($configContainer);
            $('body').trigger($.Event('listTableColsChange', {
                id_config: id_config
            }));
        });
    };

    this.useDefaultCols = function ($button) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        if (!confirm('Voulez-vous remplacer les colonnes actuelles par les colonnes par défaut de cette liste?')) {
            return;
        }

        var $configContainer = $button.findParentByClass('list_cols_config_container');

        if (!$.isOk($configContainer)) {
            bimp_msg('Une erreur est survenue. Chargement des colonnes par défaut impossible', 'danger', null, true);
            console.error('ListTableConfig::useDefaultCols(): $container absent');
            return;
        }

        var id_config = parseInt($configContainer.data('id_config'));

        setObjectAction($button, {
            module: 'bimpuserconfig',
            object_name: 'ListTableConfig',
            id_object: id_config
        }, 'useDefaultCols', {}, null, null, function (result) {
            if (typeof (result.cols_html) !== 'undefined') {
                var $listContainer = $configContainer.find('.list_cols_list_container');

                if ($.isOk($listContainer)) {
                    var $tbody = $listContainer.find('tbody.multipleValuesList');
                    if ($.isOk($tbody)) {
                        $tbody.html(result.cols_html);
                        config.checkColsListItems($listContainer);
                        config.setColsListItemsEvents($listContainer);
                    }
                }

                $('body').trigger($.Event('listTableColsChange', {
                    id_config: id_config
                }));
            }
        });
    };

    // Evénements: 

    this.setColsConfigEvents = function ($container) {
        if ($.isOk($container)) {
            $container.find('.objectListColsTypesSelect_container').each(function () {
                var list_name = '';
                var $colsConfigContainer = $(this).findParentByClass('list_cols_config_container');
                if ($.isOk($colsConfigContainer)) {
                    list_name = $colsConfigContainer.data('list_name');
                }
                var fields_prefixe = $(this).data('fields_prefixe');
                if (typeof (fields_prefixe) === 'undefined') {
                    fields_prefixe = '';
                }

                if (!parseInt($(this).data('list_cols_config_events_init'))) {
                    $(this).data('list_cols_config_events_init', 1);

                    var module = $(this).data('module');
                    var object_name = $(this).data('object_name');

                    // Sélection du type de colonne
                    $(this).find('select.col_type_select').each(function () {
                        $(this).change(function () {
                            if ($.isOk($colsConfigContainer)) {
                                $colsConfigContainer.find('.list_add_col_submit_container').stop().slideUp(250);
                            }

                            var $parent = $(this).findParentByClass('objectListColsTypesSelect_content');
                            if ($.isOk($parent)) {
                                var col_type = $(this).val();
                                $parent.children('.objectColTypeItemsSelectContainer').each(function () {
                                    if ($(this).data('col_type') === col_type) {
                                        $(this).stop().slideDown(250);
                                    } else {
                                        $(this).stop().slideUp(250);
                                    }
                                    $(this).children('select').val('').change();
                                    $(this).children('.col_type_item_options').html('').hide();
                                });
                            }
                        });
                    });

                    $(this).find('.objectColTypeItemsSelectContainer').each(function () {
                        var col_type = $(this).data('col_type');
                        var $select = $(this).children('select');
                        var $itemOptions = $(this).children('.col_type_item_options');

                        $select.change(function () {
                            if ($.isOk($colsConfigContainer)) {
                                $colsConfigContainer.find('.list_add_col_submit_container').stop().slideUp(250);
                            }

                            var item = $(this).val();
                            if (item) {
                                switch (col_type) {
                                    case 'fields':
                                        // Sélection d'un champ objet:
                                        config.loadFieldOptions($itemOptions, module, object_name, list_name, item, fields_prefixe);
                                        break;

                                        // Sélection d'un objet lié; 
                                    case 'linked_objects':
                                        var object_label = $(this).find('option[value="' + item + '"]').text();
                                        config.loadLinkedObjectOptions($itemOptions, module, object_name, item, object_label, fields_prefixe);
                                        break;

                                        // Sélection d'une sous-liste d'enfants: 
                                    case 'children':
                                        config.loadChildrenOptions($itemOptions, module, object_name, item);
                                        break;
                                }
                            } else {
                                // Pour annuler un éventuel chargement ajax en cours: 
                                var ajax_refresh_idx = parseInt($itemOptions.data('ajax_refresh_idx'));
                                if (typeof (ajax_refresh_idx) !== 'undefined') {
                                    ajax_refresh_idx++;
                                    $itemOptions.data('ajax_refresh_idx', ajax_refresh_idx);
                                }
                                $itemOptions.html('').hide();
                            }
                        });
                    });
                }
            });
        }
    };

    this.setColOptionsFormEvents = function ($container) {
        if (!parseInt($container.data('col_options_event_init'))) {
            var $select = $container.find('select[name="display_name"]');

            if ($.isOk($select) && !parseInt($select.data('col_options_event_init'))) {
                $select.data('col_options_event_init', 1);

                $select.change(function () {
                    var display_name = $(this).val();

                    if (display_name) {
                        var $form = $(this).findParentByClass('list_col_options_form');

                        if ($.isOk($form)) {
                            var module = $form.data('module');
                            var object_name = $form.data('object_name');
                            var field_name = $form.data('field_name');

                            var $resultContainer = $form.find('.display_type_options');

                            if ($.isOk($resultContainer)) {
                                config.loadDisplayOptions($resultContainer, module, object_name, field_name, display_name);
                            }
                        }
                    }
                });
            }
        }
    };

    this.setColsListItemsEvents = function ($container) {
        if ($.isOk($container)) {
            if (!$container.hasClass('list_cols_list_container')) {
                $container = $container.find('.list_cols_list_container');

                if (!$.isOk($container)) {
                    return;
                }
            }
        }

        var $tbody = $container.children('.inputMultipleValues').children('.cols_list').children('tbody.multipleValuesList');

        if ($tbody.length) {
            var $rows = $tbody.children('tr.itemRow');

            if ($rows.length) {
                if ($tbody.hasClass('ui-sortable')) {
                    $tbody.sortable('destroy');
                }

                var $handles = $tbody.find('td.positionHandle');

                if ($handles.length) {
                    $tbody.sortable({
                        appendTo: $tbody,
                        axis: 'y',
                        cursor: 'move',
                        handle: 'td.positionHandle',
                        items: $rows,
                        opacity: 1,
                        start: function (e, ui) {
                        },
                        update: function (e, ui) {
                            config.saveColsPositions($tbody);
                        }
                    });
                }

                $rows.each(function () {
                    var $tr = $(this);

                    if (!parseInt($tr.data('cols_list_item_events_init'))) {
                        $tr.data('cols_list_item_events_init', 1);

                        setCommonEvents($tr);
                        setInputsEvents($tr);
                        config.setColOptionsFormEvents($tr);
                    }
                });
            }
        }
    };
}

var ListTableConfig = new ListTableConfig();

// FiltersConfig: 

function FiltersConfig() {
    var config = this;

    // Chargement ajax: 

    this.loadFilterOptions = function ($resultContainer, module, object_name, filter_name, filter_prefixe) {
        loadObjectCustomContent(null, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'FiltersConfig'
        }, 'renderFilterOptions', {
            module: module,
            object_name: object_name,
            filter_name: filter_name,
            filter_prefixe: filter_prefixe
        }, function (result, bimpAjax) {
            var $filtersConfigContainer = bimpAjax.$resultContainer.findParentByClass('add_filter_container');
            if ($.isOk($filtersConfigContainer)) {
                $filtersConfigContainer.find('.add_filter_submit_container').stop().slideDown(250);
                config.setFilterOptionsFormEvents($filtersConfigContainer);
            }
        });
    };

    this.loadLinkedObjectOptions = function ($resultContainer, module, object_name, linked_object, object_label, filters_prefixe) {
        loadObjectCustomContent(null, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'FiltersConfig'
        }, 'renderLinkedObjectOptions', {
            module: module,
            object_name: object_name,
            linked_object: linked_object,
            object_label: object_label,
            filters_prefixe: filters_prefixe
        }, function (result, bimpAjax) {
            config.setFiltersConfigEvents(bimpAjax.$resultContainer);
        });
    };

    // Traitements: 

    this.resetAddFilterForm = function ($container) {
        if ($.isOk($container)) {
            var $options = $container.find('.filter_item_options');

            if ($options.length) {
                $options.slideUp(250, function () {
                    $(this).html('');

                    $container.find('.objectFilterItemsSelectContainer').each(function () {
                        $(this).find('select').val('').change();
                    });
                });
            }
        }
    };

    this.getFilterOptionsFormValues = function ($container) {
        var data = {
            label: '',
            open: 0
        };

        var $input = $container.find('input[name="new_filter_label"]');
        if ($input.length) {
            data.label = $input.val();
        }

        $input = $container.find('input[name="new_filter_open"]');
        if ($input.length) {
            data.open = parseInt($input.val());
            if (isNaN(data.open)) {
                data.open = 0;
            }
        }

        return data;
    };

    this.addFilter = function ($button, filter_name) {
        var $container = $button.findParentByClass('filter_options_form');

        if (!$.isOk($container)) {
            bimp_msg('Une erreur est survenue. Ajout du filtre impossible', 'danger', null, true);
            console.error('FiltersConfig::addFilter(): $container absent');
            return;
        }

        var $filtersConfigContainer = $container.findParentByClass('filters_config_container');

        if (!$.isOk($filtersConfigContainer)) {
            bimp_msg('Une erreur est survenue. Ajout du filtre impossible', 'danger', null, true);
            console.error('FiltersConfig::addFilter(): $filtersConfigContainer absent');
            return;
        }

        var $filtersList = $filtersConfigContainer.find('.filters_list_container');
        if (!$.isOk($filtersList)) {
            bimp_msg('Une erreur est survenue. Ajout du filtre impossible', 'danger', null, true);
            console.error('FiltersConfig::addFilter(): $filtersList absent');
            return;
        }

        var check = true;
        $filtersList.find('.itemRow').each(function () {
            if (check) {
                var item_filter_name = $(this).data('filter_name');
                if (item_filter_name && item_filter_name === filter_name) {
                    bimp_msg('Ce filtre a déjà été ajouté', 'danger', null, true);
                    check = false;
                }
            }
        });

        if (!check) {
            config.resetAddFilterForm($filtersConfigContainer);
            return;
        }

        var data = config.getFilterOptionsFormValues($container);
        var module = $filtersConfigContainer.data('module');
        var object_name = $filtersConfigContainer.data('object_name');

        loadObjectCustomContent(null, null, {
            module: 'bimpuserconfig',
            object_name: 'FiltersConfig'
        }, 'renderFilterItem', {
            object: {
                module: module,
                object_name: object_name
            },
            filter_name: filter_name,
            label: data.label,
            open: data.open
        }, function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                if ($.isOk($filtersList)) {
                    $filtersList.find('tbody.multipleValuesList').append(result.html);
                    config.checkFiltersListItems($filtersList);
                    config.setFiltersListItemsEvents($filtersList);
                    config.resetAddFilterForm($filtersConfigContainer);
                }
            }
        });
    };

    this.removeFilter = function ($button) {
        if ($.isOk($button)) {
            var $container = $button.findParentByClass('filters_list_container')
            $button.findParentByClass('itemRow').remove();
            config.checkFiltersListItems($container);
        }
    };

    this.removeAllFilters = function ($button) {
        var $listContainer = $button.findParentByClass('filters_list_container');

        if ($.isOk($listContainer)) {
            $listContainer.find('tr.filters_itemRow').remove();
            config.checkFiltersListItems($listContainer);
        }
    };

    this.checkFiltersListItems = function ($container) {
        if ($.isOk($container)) {
            var $filtersList = $container.find('.inputMultipleValues');

            if ($.isOk($filtersList)) {
                var $rows = $filtersList.find('tbody.multipleValuesList').children('tr.itemRow');

                var $no_cols = $container.find('.no_cols');
                if ($.isOk($no_cols)) {
                    if ($rows.length) {
                        $no_cols.stop().hide();
                    } else {
                        $no_cols.stop().slideDown(250);
                    }
                }

                if ($rows.length) {
                    $filtersList.show();
                } else {
                    $filtersList.hide();
                }

                var $removeAllButton = $container.find('.removeAllFiltersButton');
                if ($.isOk($removeAllButton)) {
                    if ($rows.length > 1) {
                        $removeAllButton.show();
                    } else {
                        $removeAllButton.hide();
                    }
                }
            }
        }
    };

    this.userDefaultFilters = function ($button) {
        if (!$.isOk($button) || $button.hasClass('disabled')) {
            return;
        }

        if (!confirm('Voulez-vous remplacer les filtres actuels par les filtres par défaut pour cette liste?')) {
            return;
        }

        var $configContainer = $button.findParentByClass('filters_config_container');

        if (!$.isOk($configContainer)) {
            bimp_msg('Une erreur est survenue. Chargement des filtres par défaut impossible', 'danger', null, true);
            console.error('FiltersConfig::userDefaultFilters(): $container absent');
            return;
        }

        var $resultContainer = $configContainer.find('table.filters_list').children('tbody.multipleValuesList');

        if (!$.isOk($resultContainer)) {
            bimp_msg('Une erreur est survenue. Chargement des filtres par défaut impossible', 'danger', null, true);
            console.error('FiltersConfig::userDefaultFilters(): $resultContainer absent');
            return;
        }

        var module = $configContainer.data('module');
        var object_name = $configContainer.data('object_name');

        loadObjectCustomContent($button, $resultContainer, {
            module: 'bimpuserconfig',
            object_name: 'FiltersConfig'
        }, 'renderDefaultFiltersItems', {
            module: module,
            object_name: object_name
        }, function (result, bimpAjax) {
            if (typeof (result.html) !== 'undefined' && result.html) {
                if ($.isOk(bimpAjax.$resultContainer)) {
                    var $filtersList = bimpAjax.$resultContainer.findParentByClass('filters_list_container');
                    if ($.isOk($filtersList)) {
                        config.checkFiltersListItems($filtersList);
                        config.setFiltersListItemsEvents($filtersList);
                        config.resetAddFilterForm($configContainer);
                    }
                }
            }
        });
    };

    // Evénements: 

    this.onFormLoaded = function ($form) {
        var $container = $form.find('.filters_config_container');

        if ($container.length) {
            config.setFiltersConfigEvents($container.find('.add_filter_container'));
            
            var $filtersList = $container.find('.filters_list_container');
            config.setFiltersListItemsEvents($filtersList);
            config.checkFiltersListItems($filtersList);
        }
    };

    this.setFiltersConfigEvents = function ($container) {
        if ($.isOk($container)) {
            $container.find('.objectFiltersSelect_container').each(function () {
                var $filtersConfigContainer = $(this).findParentByClass('filters_config_container');

                var fields_prefixe = $(this).data('fields_prefixe');
                if (typeof (fields_prefixe) === 'undefined') {
                    fields_prefixe = '';
                }

                if (!parseInt($(this).data('filters_config_events_init'))) {
                    $(this).data('filters_config_events_init', 1);

                    var module = $(this).data('module');
                    var object_name = $(this).data('object_name');

                    // Sélection du type: 
                    $(this).find('select.filter_type_select').each(function () {
                        $(this).change(function () {
                            if ($.isOk($filtersConfigContainer)) {
                                $filtersConfigContainer.find('.add_filter_submit_container').stop().slideUp(250);
                            }

                            var $parent = $(this).findParentByClass('objectFiltersTypeSelect_content');
                            if ($.isOk($parent)) {
                                var type = $(this).val();
                                $parent.children('.objectFilterItemsSelectContainer').each(function () {
                                    if ($(this).data('type') === type) {
                                        $(this).stop().slideDown(250);
                                    } else {
                                        $(this).stop().slideUp(250);
                                    }
                                    $(this).children('select').val('').change();
                                    $(this).children('.filter_item_options').html('').hide();
                                });
                            }
                        });
                    });

                    $(this).find('.objectFilterItemsSelectContainer').each(function () {
                        var type = $(this).data('type');
                        var $select = $(this).children('select');
                        var $itemOptions = $(this).children('.filter_item_options');

                        $select.change(function () {
                            if ($.isOk($filtersConfigContainer)) {
                                $filtersConfigContainer.find('.add_filter_submit_container').stop().slideUp(250);
                            }

                            var item = $(this).val();
                            if (item) {
                                switch (type) {
                                    case 'fields':
                                        // Sélection d'un champ objet:
                                        config.loadFilterOptions($itemOptions, module, object_name, item, fields_prefixe);
                                        break;

                                        // Sélection d'un objet lié; 
                                    case 'linked_objects':
                                        var object_label = $(this).find('option[value="' + item + '"]').text();
                                        config.loadLinkedObjectOptions($itemOptions, module, object_name, item, object_label, fields_prefixe);
                                        break;
                                }
                            } else {
                                // Pour annuler un éventuel chargement ajax en cours: 
                                var ajax_refresh_idx = parseInt($itemOptions.data('ajax_refresh_idx'));
                                if (typeof (ajax_refresh_idx) !== 'undefined') {
                                    ajax_refresh_idx++;
                                    $itemOptions.data('ajax_refresh_idx', ajax_refresh_idx);
                                }
                                $itemOptions.html('').hide();
                            }
                        });
                    });
                }
            });
        }
    };

    this.setFilterOptionsFormEvents = function ($container) {

    };

    this.setFiltersListItemsEvents = function ($container) {
        if ($.isOk($container)) {
            if (!$container.hasClass('filters_list_container')) {
                $container = $container.find('.filters_list_container');

                if (!$.isOk($container)) {
                    console.error('FiltersConfig::setFiltersListItemsEvents() : $container absent');
                    return;
                }
            }
        }

        var $tbody = $container.children('.inputMultipleValues').children('.filters_list').children('tbody.multipleValuesList');

        if ($tbody.length) {
            var $rows = $tbody.children('tr.itemRow');

            if ($rows.length) {
                if ($tbody.hasClass('ui-sortable')) {
                    $tbody.sortable('destroy');
                }

                var $handles = $tbody.find('td.positionHandle');

                if ($handles.length) {
                    bimp_msg('ici');
                    $tbody.sortable({
                        appendTo: $tbody,
                        axis: 'y',
                        cursor: 'move',
                        handle: 'td.positionHandle',
                        items: $rows,
                        opacity: 1,
                        start: function (e, ui) {
                        }
                    });
                }

                $rows.each(function () {
                    var $tr = $(this);

                    if (!parseInt($tr.data('filters_list_item_events_init'))) {
                        $tr.data('filters_list_item_events_init', 1);

                        setCommonEvents($tr);
                        setInputsEvents($tr);
                        config.setFilterOptionsFormEvents($tr);
                    }
                });
            }
        }
    };
}

var FiltersConfig = new FiltersConfig();

$(document).ready(function () {
    $('body').on('formLoaded', function (e) {
        if (e.$form.hasClass('FiltersConfig_form')) {
            FiltersConfig.onFormLoaded(e.$form);
        }
    });
});