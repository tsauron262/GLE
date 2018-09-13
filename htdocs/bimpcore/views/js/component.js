function BimpComponent($component) {
    var ptr = this;

    if (typeof ($component) !== 'undefined') {
        this.setComponent($component);
    } else {
        this.reset();
    }

    this.reset = function () {
        ptr.$component = null;
        ptr.$container = null;
        ptr.$result = null;

        ptr.data = {
            identifier: '',
            type: '',
            name: '',
            module: '',
            object: '',
            id_object: ''
        };
    };

    this.setComponent = function ($component) {
        ptr.reset();
        if ($component.length) {
            ptr.$component = $component;
            ptr.data.identifier = $component.data('identifier');
            ptr.data.type = $component.data('type');
            ptr.data.name = $component.data('name');
            ptr.data.module = $component.data('module');
            ptr.data.object = $component.data('object');
            ptr.data.id_object = $component.data('id_object');

            ptr.$container = $('#' + ptr.data.identifier + '_container');
            ptr.$result = $('#' + ptr.data.identifier + '_result');
        }
    };

    this.setIdentifier = function (identifier) {
        ptr.setComponent($('#' + identifier));
    };

    this.onLoad = function () {
        if (!ptr.$component.length) {
            return;
        }

        if (parseInt(ptr.$component.data('onload_init'))) {
            return;
        }

        setCommonEvents(ptr.$container);
        setInputsEvents(ptr.$container);

        switch (ptr.data.type) {
            case 'list_table':
                ptr.$component.find('#' + ptr.data.identifier + '_n').change(function () {
                    reloadObjectList(ptr.data.identifier);
                });

                var $tools = ptr.$component.find('.headerTools');
                if ($tools.length) {
                    $tools.find('.openSearchRowButton').click(function () {
                        var $searchRow = ptr.$component.find('.listSearchRow');
                        if ($searchRow.length) {
                            if ($(this).hasClass('action-open')) {
                                $searchRow.stop().fadeIn(150);
                                $(this).removeClass('action-open').addClass('action-close');
                            } else {
                                $searchRow.stop().fadeOut(150);
                                $(this).removeClass('action-close').addClass('action-open');
                            }
                        }
                    });
                    $tools.find('.openAddObjectRowButton').click(function () {
                        var $addRow = ptr.$component.find('.addObjectRow');
                        if ($addRow.length) {
                            if ($(this).hasClass('action-open')) {
                                $addRow.stop().fadeIn(150);
                                $(this).removeClass('action-open').addClass('action-close');
                            } else {
                                $addRow.stop().fadeOut(150);
                                $(this).removeClass('action-close').addClass('action-open');
                            }
                        }
                    });
                    $tools.find('.activatePositionsButton').click(function () {
                        var $handles = ptr.$component.find('.positionHandle');
                        if ($handles.length) {
                            if ($(this).hasClass('action-open')) {
                                $handles.show();
                                $(this).removeClass('action-open').addClass('action-close');
                                deactivateSorting(ptr.$component);
                                ptr.$component.find('input[name=p]').val('1');
                                sortListByPosition(ptr.$component.attr('id'), true);
                            } else {
                                $handles.hide();
                                $(this).removeClass('action-close').addClass('action-open');
                                activateSorting(ptr.$component);
                            }
                        }
                    });
                }

                ptr.$component.find('tr.listSearchRow').each(function () {
                    $(this).find('.searchInputContainer').each(function () {
                        var field_name = $(this).data('field_name');
                        if (field_name) {
                            $(this).find('[name=' + field_name + ']').val('');
                        }
                    });
                    $(this).find('.ui-autocomplete-input').val('');
                    setSearchInputsEvents(ptr.$component);
                });
                ptr.$component.find('tbody').find('a').each(function () {
                    $(this).attr('target', '_blank');
                });
                ptr.$component.find('tbody.listRows').children('tr.objectListItemRow').each(function () {
                    checkRowModifications($(this));
                });

                setListEditInputsEvents(ptr.$component);
                setPositionsHandlesEvents(ptr.$component);
                setPaginationEvents(ptr.$component);
                break;

            case 'form':
                break;

            case 'view':
                break;

            case 'list_views:':
                break;
        }

        if (!ptr.$component.data('object_change_event_init')) {
            var objects = ptr.$component.data('objects_change_reload');
            if (objects) {
                objects = objects.split(',');
            }

            if (!$('body').data(ptr.data.identifier + '_object_events_init')) {
                $('body').on('objectChange', function (e) {
                    if ((e.module === ptr.data.module) && (e.object_name === ptr.data.object) &&
                            ((!ptr.id_object && !e.id_object) ||
                                    (ptr.data.id_object && e.id_object && ptr.data.id_object === e.id_object))) {
                        ptr.refresh();
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                ptr.refresh();
                            }
                        }
                    }
                });
                $('body').on('objectDelete', function (e) {
                    if ((e.module === ptr.data.module) && (e.object_name === ptr.data.object) &&
                            ((!ptr.id_object && !e.id_object) ||
                                    (ptr.data.id_object && e.id_object && ptr.data.id_object === e.id_object))) {
                        ptr.refresh();
                    } else if (objects && objects.length) {
                        for (var i in objects) {
                            if (e.object_name === objects[i]) {
                                ptr.refresh();
                            }
                        }
                    }
                });
                $('body').data(ptr.data.identifier + '_object_events_init', 1);
            }

            ptr.$component.data('object_change_event_init', 1);
        }

        ptr.$component.data('onload_init', 1);
    };

    this.onRefresh = function () {
        if (!ptr.$component.length) {
            return;
        }
    };
}

function loadModalComponent() {
    
}