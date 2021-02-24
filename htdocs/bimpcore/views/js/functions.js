// Notifications:
var bimp_msg_enable = true;
var ctrl_down = false;
var text_input_focused = false;
var bimp_decode_textarea = null;

var notifications_remove_delay = 3000;

// Debug modale:

var BimpDebugModal = false;

function bimp_msg(msg, className, $container, auto_hide) {
    if (!bimp_msg_enable) {
        return;
    }

    if (typeof (className) === 'undefined') {
        className = 'info';
    }

    if (typeof (auto_hide) === 'undefined') {
        auto_hide = false;
    }

    var html = '<div class="bimp_msg alert alert-' + className + '">';
    html += '<span class="removeBimpMsgButton" onclick="removeNotification($(this))"></span>';
    html += msg;
    html += '</div>';

    if ($.isOk($container)) {
        $container.append(html).stop().slideDown(250, function () {
            $(this).css('height', 'auto');
        });
    } else {
        $container = $('#page_notifications');

        if (!$container.length) {
            return;
        }

        $container.append(html).show().css('height', 'auto');

        var $div = $container.find('div.bimp_msg:last-child');

        $div.css('margin-left', '370px').animate({
            'margin-left': 0
        }, {
            'duration': 250,
            complete: function () {
                if ($div.hasClass('alert-success') || auto_hide) {
                    setTimeout(function () {
                        if (!$div.data('hold')) {
                            $div.fadeOut(500, function () {
                                if (!$(this).data('hold')) {
                                    $div.remove();
                                    checkNotifications();
                                } else {
                                    $div.css('opactity', 1);
                                }
                            });
                        }
                    }, notifications_remove_delay);
                }
            }
        });
    }
}

function bimp_display_element_popover($element, content, side) {
    if ($element.length) {
        if (typeof (side) === 'undefined') {
            side = 'right';
        }

        if (typeof ($element.data('bs.popover')) !== 'undefined') {
            $element.data('bs.popover').options.content = content;
            $element.data('bs.popover').options.side = side;
            $element.popover('show');
        } else {
            $element.popover({
                html: true,
                content: content,
                placement: side,
                trigger: 'manual',
                container: 'body'
            });
            $element.popover('show');
            $element.addClass('bs-popover');
        }
    }
}

function bimp_destroy_element_popover($element) {
    if (typeof ($element.data('bs.popover')) !== 'undefined') {
        $element.popover('destroy');
        $element.removeClass('bs-popover');
    }
}

function bimp_notify(content) {
    var html = '';

    html += '<div class="notification_content">';
    html += content;
    html += '</div>';

    var $modal = $('#notifications_modal');

    if (!$modal.length) {
        insertNotificationsModal();
        $modal = $('#notifications_modal');
    }

    $modal.find('.modal-body').append(content);
    $modal.modal('show');
}

function bimp_notify_error(content) {
    var html = '<div class="danger" style="text-align: center; font-size: 18px; margin: 30px 0">';
    html += '<i class="fas fa5-exclamation-triangle iconLeft"></i>Une erreur inattendue est survenue';
    html += '</div>';
    html += '<h3>Informations reçues: </h3>';
    html += '<div>';
    html += content;
    html += '</div>';
    bimp_notify(html);
}

// Notifications:

function setNotificationsEvents() {
    var $notifications = $('#page_notifications');

    $notifications.mouseover(function () {
        $notifications.find('div.bimp_msg.alert-success').each(function () {
            $(this).data('hold', 1).css('opacity', 1);
        });
    }).mouseleave(function () {
        $notifications.find('div.bimp_msg.alert-success').each(function () {
            $(this).data('hold', 0);
            var $div = $(this);
            setTimeout(function () {
                if (!$div.data('hold')) {
                    $div.fadeOut(500, function () {
                        if (!$(this).data('hold')) {
                            $div.remove();
                            checkNotifications();
                        }
                    });
                }
            }, notifications_remove_delay);
        });
    });
}

function removeNotification($button) {
    $button.findParentByClass('bimp_msg').fadeOut(250, function () {
        $(this).remove();
        checkNotifications();
    });
}

function removeAllNotifications() {
    $('#page_notifications').find('div.bimp_msg').each(function () {
        $(this).fadeOut(250, function () {
            $(this).remove();
            checkNotifications();
        });
    });
}

function checkNotifications() {
    var $container = $('#page_notifications');

    if (!$container.find('div.bimp_msg').length) {
        $container.hide();
    }
}

// function Notifications modale:

function insertNotificationsModal() {
    if ($('#notifications_modal').length) {
        return;
    }
    var html = '';

    html = '<div class="modal ajax-modal fade" tabindex="-1" role="dialog" id="notifications_modal">';
    html += '<div class="modal-dialog modal-md" role="document">';
    html += '<div class="modal-content">';

    html += '<div class="modal-header">';
    html += '<h4 class="modal-titles_container"><i class="fas fa5-comment iconLeft"></i>Message important</h4>';
    html += '<button type="button" class="close" onclick="closeNotificationsModal(true);" aria-label="Close">';
    html += '<span aria-hidden="true">&times;</span>';
    html += '</button>';
    html += '</div>';

    html += '<div class="modal-body">';
    html += '</div>';

    html += '<div class="modal-footer">';
    html += '<button type="button" class="btn btn-secondary" onclick="closeNotificationsModal(true);">';
    html += '<i class="fa fa-times iconLeft"></i>Fermer</button>';
    html += '</div>';

    html += '</div>';
    html += '</div>';
    html += '</div>';

    $('body').append(html);
}

function closeNotificationsModal(clear_content) {
    if (typeof (clear_content) === 'undefined') {
        clear_content = false;
    }

    var $modal = $('#notifications_modal');

    if ($modal.length) {
        $modal.modal('hide');
        if (clear_content) {
            $modal.find('.modal-body').html('');
        }
    }
}

// Modales:

function loadModalIFrame($button, url, title) {
    bimpModal.loadIframe($button, url, title);
}

function loadImageModal($button, src, title) {
    bimpModal.loadImage($button, src, title);
}

// Popovers: 

function hidePopovers($container) {
    $container.find('.bs-popover').each(function () {
        $(this).popover('hide');
    });
}

function resetPopovers($container) {
    $container.find('.bs-popover').each(function () {
        var options = $(this).data('bs.popover').options;
        $(this).popover('destroy');
        $(this).popover(options);
    });
}

// Navtabs: 

function loadNavtabContent($link) {
    var ajax_loaded = parseInt($link.data('ajax_loaded'));
    if (isNaN(ajax_loaded) || !ajax_loaded) {
        var ajax_callback = $link.data('ajax_callback');
        if (typeof (ajax_callback) === 'string') {
            $link.data('ajax_loaded', 1);
            eval(bimp_htmlDecode(ajax_callback));
        } else {
            // Todo: callback générique dans le cas des nav_tabd définies dans le yml du controller. 
        }
    }
}

// Actions: 

function toggleFoldableSection($caption) {
    var $section = $caption.parent('.foldable_section');
    if (!$section.length) {
        return;
    }
    var $content = $section.children('.foldable_section_content');
    if (!$content.length) {
        return;
    }

    if ($section.hasClass('open')) {
        $content.stop().slideUp(250, function () {
            $section.removeClass('open').addClass('closed');
            $(this).removeAttr('style');
        });
    } else {
        $content.stop().slideDown(250, function () {
            $section.removeClass('closed').addClass('open');
            $(this).removeAttr('style');
        });
    }
}

function toggleElementDisplay($element, $button) {
    if (!$.isOk($element)) {
        return;
    }

    if ($element.css('display') === 'none') {
        $element.stop().slideDown(250);
        if ($.isOk($button)) {
            if ($button.hasClass('open-close')) {
                $button.removeClass('action-open').addClass('action-close');
            }
        }
    } else {
        $element.stop().slideUp(250);
        if ($.isOk($button)) {
            if ($button.hasClass('open-close')) {
                $button.removeClass('action-close').addClass('action-open');
            }
        }
    }
}

function displayObjectLinkCardPopover($button) {
    if (!$.isOk($button)) {
        return;
    }

    var $parent = $button.findParentByClass('objectLink');

    if (!$.isOk($parent)) {
        return;
    }

    var $elem = $parent.children('.card-popover');

    if ($elem.length) {
        var content = $elem.data('content');

        if (content) {
            $button.popover('destroy');
            $button.popover({
                container: 'body',
                animation: false,
                placement: 'bottom',
                html: true,
                trigger: 'manual',
                content: content
            });
            $button.popover('show');
            $button.addClass('destroyPopoverOnClickOut');

            var id = $button.attr('aria-describedby');
            $('#' + id).click(function (e) {
                e.stopPropagation();
            });
        }
    }

    $(this).data('bs_popover_click_event_init', 1);
}

// Evenements: 

function setCommonEvents($container) {
    //Foldable custom: 
    $container.find('.foldable_container').each(function () {
        if (!parseInt($(this).data('foldable_container_event_init'))) {
            var $caption = $(this).children('.foldable_caption');

            $caption.click(function () {
                var $foldableContainer = $(this).findParentByClass('foldable_container');
                if ($.isOk($foldableContainer)) {
                    if ($foldableContainer.hasClass('open')) {
                        $foldableContainer.removeClass('open').addClass('closed');
                        $foldableContainer.children('.foldable_content').slideUp(250);
                        var onclose = $foldableContainer.attr('onclose');
                        if (typeof (onclose) === 'string' && onclose) {
                            eval(onclose.replace('$(this)', '$foldableContainer'));
                        }
                    } else {
                        $foldableContainer.removeClass('closed').addClass('open');
                        $foldableContainer.children('.foldable_content').slideDown(250);
                        var onopen = $foldableContainer.attr('onopen');
                        if (typeof (onopen) === 'string' && onopen) {
                            eval(onopen.replace('$(this)', '$foldableContainer'));
                        }
                    }
                }
            });
            $(this).data('foldable_container_event_init', 1);
        }
    });
    // foldable sections: 
    $container.find('.foldable_section_caption').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            $(this).click(function () {
                toggleFoldableSection($(this));
            });
            $(this).data('foldable_event_init', 1);
        }
    });
    // foldable panels:
    $container.find('.panel.foldable').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            var $panel = $(this);
            $panel.children('.panel-heading').click(function () {
                if ($panel.hasClass('open')) {
                    $panel.children('.panel-body, .panel-footer').slideUp(250, function () {
                        $panel.removeClass('open').addClass('closed');
                    });
                } else {
                    $panel.children('.panel-body, .panel-footer').slideDown(250, function () {
                        $panel.removeClass('closed').addClass('open');
                    });
                }
            });
            $panel.children('.panel-heading').find('.headerBtn').click(function (e) {
                e.stopPropagation();
            });
            $panel.children('.panel-heading').find('.panel_header_icon').click(function (e) {
                e.stopPropagation();
            });
            $(this).data('foldable_event_init', 1);
        }
    });
    // foldable view tables:
    $container.find('.objectViewtable.foldable').each(function () {
        var $table = $(this);
        $table.children('thead').children('tr:first-child').each(function () {
            if (!parseInt($(this).data('foldable_event_init'))) {
                $(this).click(function () {
                    if ($table.hasClass('open')) {
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeOut(250, function () {
                            $table.removeClass('open').addClass('closed');
                        });
                    } else {
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeIn(250, function () {
                            $table.removeClass('closed').addClass('open');
                        });
                    }
                });
                $(this).data('foldable_event_init', 1);
            }
        });
    });
    // foldable array contents: 
    $container.find('.array_content_container.foldable').each(function () {
        if (!parseInt($(this).data('foldable_array_content_container_init'))) {
            $(this).children('.array_content_caption').children('span.title').click(function () {
                var $foldableContainer = $(this).findParentByClass('array_content_container');
                if ($.isOk($foldableContainer)) {
                    if ($foldableContainer.hasClass('open')) {
                        $foldableContainer.children('.array_content').slideUp(250, function () {
                            $foldableContainer.removeClass('open').addClass('closed');
                        });
                    } else {
                        $foldableContainer.children('.array_content').slideDown(250, function () {
                            $foldableContainer.removeClass('closed').addClass('open');
                        });
                    }
                }
            });

            $(this).children('.folding_buttons').children('span.open_all').click(function () {
                var $foldableContainer = $(this).findParentByClass('array_content_container');
                if ($.isOk($foldableContainer)) {
                    $foldableContainer.find('.array_content').each(function () {
                        $(this).slideDown(250, function () {
                            $(this).findParentByClass('array_content_container').removeClass('closed').addClass('open');
                        });
                    });
                }
            });

            $(this).children('.folding_buttons').children('span.close_all').click(function () {
                var $foldableContainer = $(this).findParentByClass('array_content_container');
                if ($.isOk($foldableContainer)) {
                    $foldableContainer.find('.array_content').each(function () {
                        $(this).slideUp(250, function () {
                            $(this).findParentByClass('array_content_container').removeClass('open').addClass('closed');
                        });
                    });
                }
            });

            $(this).data('foldable_array_content_container_init', 1);
        }
    });

    // Open-Close button: 
    $container.find('.openCloseButton').each(function () {
        if (!parseInt($(this).data('open_close_events_init'))) {
            $(this).data('open_close_events_init', 1);

            $(this).click(function (e) {
                e.stopPropagation();

                var parent_level = $(this).data('parent_level');
                if (typeof (parent_level) !== 'undefined') {
                    parent_level = parseInt(parent_level);
                } else {
                    parent_level = 1;
                }

                if (isNaN(parent_level) || parent_level < 1) {
                    parent_level = 1;
                }

                var i = 0;
                var $parent = $(this);
                while (i < parent_level) {
                    $parent = $parent.parent();

                    if (!$.isOk($parent)) {
                        break;
                    }

                    i++;
                }

                if ($.isOk($parent)) {
                    var extra_class = $(this).data('content_extra_class');

                    if (typeof (extra_class) === 'undefined') {
                        extra_class = '';
                    }

                    var sel = '.openCloseContent';

                    if (extra_class) {
                        sel += '.' + extra_class;
                    }
                    var $content = $parent.children(sel);

                    if ($.isOk($content)) {
                        if ($(this).hasClass('open-content')) {
                            $content.stop().slideDown(250);
                            $(this).removeClass('open-content').addClass('close-content');
                        } else {
                            $content.stop().slideUp(250);
                            $(this).removeClass('close-content').addClass('open-content');
                        }
                    }
                }
            });
        }
    });

    // Popup
    $container.find('.displayPopupButton').each(function () {
        if (!parseInt($(this).data('popup_btn_event_init'))) {
            setDisplayPopupButtonEvents($(this));
            $(this).data('popup_btn_event_init', 1);
        }
    });
    // bootstrap popover:
    $container.find('.bs-popover').each(function () {
        if (!parseInt($(this).data('bs_popover_event_init'))) {
            $(this).popover();
            $(this).click(function () {
                $(this).popover('hide');
            });
            $(this).data('bs_popover_event_init', 1);
        }
    });
    // Auto-expand: 
    if (!parseInt($container.data('auto_expand_event_init'))) {
        $container.on('input.auto_expand', 'textarea.auto_expand', function () {
            checkInputAutoExpand(this);
        });
        $container.data('auto_expand_event_init', 1);
    }
    $container.find('.auto_expand').each(function () {
        var minRows = parseInt($(this).data('min_rows')), rows;
        if (!minRows) {
            minRows = 3;
        }
        this.baseScrollHeight = minRows * 16;
        this.rows = minRows;
        if (this.scrollHeight) {
            rows = Math.floor((this.scrollHeight - this.baseScrollHeight) / 16);
            this.rows = rows + minRows;
        }
    });
    $container.find('select').each(function () {
        if (!parseInt($(this).data('color_event_init'))) {
            checkSelectColor($(this));
            $(this).change(function () {
                checkSelectColor($(this));
            });
            $(this).data('color_event_init', 1);
        }
    });
    $container.find('.nav-tabs').each(function () {
        if (!parseInt($(this).data('nav_tabs_event_init'))) {
            $(this).find('li > a[data-toggle="tab"]').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            $(this).find('li > a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var $link = $(e.target);
                var $tabContent = $($(e.target).attr('href'));

                var ajax = $link.data('ajax');
                if (!isNaN(ajax) && ajax) {
                    loadNavtabContent($link);
                }

                if ($.isOk($tabContent)) {
                    $tabContent.find('.object_list_table').each(function () {
                        checkListWidth($(this));
                    });
                }
            });

            var $activeTab = $(this).find('li.active > a[data-toggle="tab"]');

            if ($.isOk($activeTab)) {
                $activeTab.each(function () {
                    var ajax = $(this).data('ajax');
                    if (!isNaN(ajax) && ajax) {
                        loadNavtabContent($(this));
                    }
                });
            }

            $(this).data('nav_tabs_event_init', 1);
        }
    });
    $container.find('.hideOnClickOut').each(function () {
        if (!parseInt($(this).data('hide_on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();

                $(this).find('ul.dropdown-menu').hide();
            });

            // Patch:  
            $(this).find('.dropdown-toggle').each(function () {
                if (!parseInt($(this).data('dropdown_btns_events_init'))) {
                    var $menu = $(this).parent().children('ul.dropdown-menu').show();
                    if ($.isOk($menu)) {
                        $menu.hide();
                        $(this).click(function (e) {
                            if ($menu.css('display') === 'none') {
                                $menu.show();
                            } else {
                                $menu.hide();
                            }
                            e.stopPropagation();
                        });
                    }
                    $(this).data('dropdown_btn_event_init', 1);
                }

            });
            $(this).data('hide_on_click_event_init', 1);
        }
    });
    $container.find('.displayProductStocksBtn').each(function () {
        if (!parseInt($(this).data('on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();
                displayProductStocks($(this), $(this).data('id_product'), $(this).data('id_entrepot'));
            });

            $(this).data('on_click_event_init', 1);
        }
    });
    $container.find('a[data-toggle="tab"]').each(function () {
        if (!parseInt($(this).data('toggle_tab_event_init'))) {
            $(this).on('shown.bs.tab', function (e) {
                var target = '' + e.target;
                var tab_id = target.replace(/^.*#(.*)$/, '$1');

                var $li = $('a[href="#' + tab_id + '"]').parent('li');
                if ($li.length && $li.parent('ul').data('navtabs_id') === 'maintabs') {
                    var prev = '' + e.relatedTarget;
                    prev = prev.replace(/^.*#(.*)$/, '$1');
                    var wndScrollTop = $(window).scrollTop();
                    var $prevLi = $('a[href="#' + prev + '"]').parent('li');
                    $prevLi.data('scrollTop', parseInt(wndScrollTop));

//                    if (wndScrollTop > $li.position().top) {
//                        $(window).scrollTop($li.position().top);
//                    }
                    var scrollTop = parseInt($li.data('scrollTop'));
//                    bimp_msg(scrollTop);
                    if (isNaN(scrollTop)) {
                        if (wndScrollTop > $li.position().top) {
                            scrollTop = $li.position().top;
                        } else {
                            scrollTop = wndScrollTop;
                        }
                        $li.data('scrollTop', scrollTop);
                    }

//                    if (object_header_scroll_trigger && scrollTop < object_header_scroll_trigger) {
//                        $(window).scrollTop(object_header_scroll_trigger + 1);
//                    } else {
//                        $(window).scrollTop(scrollTop);
//                    }
                }

                var $content = $('#' + tab_id);
                if ($content.length) {
                    setCommonEvents($content);
                }
            });
            $(this).data('toggle_tab_event_init', 1);
        }
    });

    $container.find('.classfortooltip').each(function () {
        $(this).removeClass('classfortooltip').addClass('bs-popover');
        $(this).popover({
            container: 'body',
            placement: 'bottom',
            html: true,
            trigger: 'hover',
            content: $(this).data('title')
        });
    });

    $container.find('.cardPopoverIcon').each(function () {
        if (!parseInt($(this).data('bs_popover_click_event_init'))) {
            $(this).click(function (e) {
                displayObjectLinkCardPopover($(this));
                e.stopPropagation();
            });
            $(this).data('bs_popover_click_event_init', 1);
        }
    });

    // Bimp List Table: 
    $container.find('table.bimp_list_table').each(function () {
        BimpListTable.setEvents($(this));
    });

    checkMultipleValues();
}

function setDisplayPopupButtonEvents($button) {
    if (!$button.length) {
        return;
    }

    if (parseInt($button.data('event_init'))) {
        return;
    }

    var $popup = $button.parent().find('#' + $button.data('popup_id'));
    if ($popup.length) {
        $popup.addClass('hideOnClickOut');
        $button.add($popup).mouseover(function () {
            $popup.show();
        }).mouseout(function () {
            if (!$popup.hasClass('locked')) {
                $popup.hide();
            }
        });

        $popup.mouseenter(function () {
            $popup.removeClass('locked');
        });

        $button.click(function (e) {
            $popup.addClass('locked');
            $popup.show();
            e.stopPropagation();
        });
    }
    $button.data('event_init', 1);
}

function onWindowScroll() {
    $('.object_page_header').each(function () {
        if (!$.isOk($(this).findParentByClass('modal'))) {
            setObjectHeaderPosition($(this));
        }
    });
}

// Rendus HTML 

function renderLoading(msg, id_container) {
    var html = '<div class="content-loading"';
    if (id_container) {
        html += ' id="' + id_container + '">';
    }
    html += '>';
    html += '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
    if (msg) {
        html += '<p class="loading-text">' + msg + '</p>';
    }
    html += '</div>';
    return html;
}

// Inputs

function checkInputAutoExpand(input) {
    var minRows = $(input).data('min_rows'), rows;
    if (!minRows) {
        minRows = 3;
    }
    input.rows = minRows;
    rows = Math.floor((input.scrollHeight - input.baseScrollHeight) / 16);
    input.rows = rows + minRows;
}

function selectSwitchOption($button) {
    if ($button.hasClass('selected')) {
        return;
    }

    var val = $button.data('value');
    var $container = $button.parent('div').parent('.switchInputContainer');
    $container.find('input').val(val).change();
    $container.find('.switchOption').each(function () {
        $(this).removeClass('selected');
    });
    $button.addClass('selected');
}

function updateTimerInput($input, input_name) {
    var $container = $input.parent('div.timer_input');
    var days = $container.find('[name=' + input_name + '_days]').val();
    if (!/^[0-9]+$/.test(days)) {
        days = 0;
        $container.find('[name=' + input_name + '_days]').val(days);
    }
    days = parseInt(days);
    var hours = parseInt($container.find('[name=' + input_name + '_hours]').val());
    var minutes = parseInt($container.find('[name=' + input_name + '_minutes]').val());
    var secondes = parseInt($container.find('[name=' + input_name + '_secondes]').val());
    var total_secs = secondes + (minutes * 60) + (hours * 3600) + (days * 86400);
    $container.find('input[name=' + input_name + ']').val(total_secs).change();
}

function checkSelectColor($select) {
    var color = $select.find('option:selected').data('color');
    if (color) {
        $select.css({'color': '#' + color, 'font-weight': 'bold', 'border-bottom-color': '#' + color});
    } else {
        $select.css({'color': '#3C3C3C', 'font-weight': 'normal', 'border-bottom-color': 'rgba(0, 0, 0, 0.2)'});
    }
}

function inputQtyUp($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        var val = $input.val();
        if (val === '') {
            val = 0;
        }
        var step = 1;
        var decimals = parseInt($input.data('decimals'));
        if (decimals > 0) {
            val = Math.round10(parseFloat(val), -decimals);
            step = parseFloat($input.data('step'));
        } else {
            val = parseInt(val);
            step = parseInt($input.data('step'));
        }
        if (isNaN(step) || typeof (step) === 'undefined') {
            step = 1;
        }
        if (typeof (val) === 'number') {
            val += step;
            if (decimals > 0) {
                val = Math.round10(val, -decimals);
            }
            $input.val(val);
            checkInputQty($qtyInputContainer);
            $input.change();
        }
    }
}

function inputQtyDown($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        var val = $input.val();
        if (val === '') {
            val = 0;
        }
        var step = 1;
        var decimals = parseInt($input.data('decimals'));

        if (decimals > 0) {
            val = Math.round10(parseFloat(val), -decimals);
            step = parseFloat($input.data('step'));
        } else {
            val = parseInt(val);
            step = parseInt($input.data('step'));
        }
        if (isNaN(step) || typeof (step) === 'undefined') {
            step = 1;
        }
        if (typeof (val) === 'number') {
            val -= step;
            if (decimals > 0) {
                val = Math.round10(val, -decimals);
            }
            $input.val(val);
            checkInputQty($qtyInputContainer);
            $input.change();
        }
    }
}

function inputQtyMax($qtyInputcontainer) {

}

function inputQtyMin($qtyInputcontainer) {

}

function checkInputQty($qtyInputContainer) {
    var $input = $qtyInputContainer.find('input.qtyInput');
    if ($input.length) {
        checkTextualInput($input);
    }
}

function checkAll($container, filter, max_elements) {
    if (typeof (filter) === 'undefined') {
        filter = '';
    }

    if (typeof (max_elements) === 'undefined') {
        max_elements = 0;
    }

    if ($.isOk($container)) {
        var nDone = 0;
        $container.find('input[type="checkbox"]' + filter).each(function () {
            if (max_elements && nDone >= max_elements) {
                return;
            }
            $(this).prop('checked', true).change();
            nDone++;
        });
    }
}

function uncheckAll($container, filter) {
    if (typeof (filter) === 'undefined') {
        filter = '';
    }
    if ($.isOk($container)) {
        $container.find('input[type="checkbox"]' + filter).each(function () {
            $(this).prop('checked', false).change();
        });
    }
}

// Components: 

function getComponentParams($component) {
    var params = {};

    if ($.isOk($component)) {
        var $container = $component.children('.object_component_params');
        if ($.isOk($container)) {
            $container.find('input.object_component_param').each(function () {
                params[$(this).attr('name')] = $(this).val();
            });
        }
    }

    return params;
}

// Affichages: 

function displayMoneyValue(value, $container, classCss, currency) {
    var negatif = false;
    if (!$container.length) {
        return;
    }

    if (typeof (currency) === 'undefined') {
        currency = '&euro;';
    }
    if (typeof (classCss) === 'undefined') {
        classCss = '';
    }

    value = parseFloat(value);
    if (value < 0) {
        negatif = true;
        value = -value;
    }

    if (value === null || isNaN(value)) {
        value = '0,00';
    } else {
        value = Math.round10(value, -2);
        value = '' + value;
        if (!/^[0-9]+\.[0-9]+/.test(value)) {
            value += ',0';
        }
        if (!/^[0-9]+\.[0-9]{2}$/.test(value)) {
            value += '0';
        }
    }

    value = value.replace(/^([0-9]+)\.?([0-9]?)([0-9]?)$/, '$1,$2$3');
    value = lisibilite_nombre(value);
    if (negatif)
        value = "-" + value;
    $container.html('<span class="' + classCss + '">' + value + ' ' + currency + '</span>');
}

function lisibilite_nombre(nbr) {
    var nombre = '' + nbr;
    var retour = '';
    var count = 0;
    for (var i = nombre.length - 1; i >= 0; i--)
    {
        if (count != 0 && count % 3 == 0)
            retour = nombre[i] + ' ' + retour;
        else
            retour = nombre[i] + retour;
        count++;
    }
    return retour.replace(" ,", ",");
}

// Math:

(function () {
    function decimalAdjust(type, value, exp) {
        if (typeof exp === 'undefined' || +exp === 0) {
            return Math[type](value);
        }
        value = +value;
        exp = +exp;
        if (value === null || isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
            return NaN;
        }
        if (value < 0) {
            return -decimalAdjust(type, -value, exp);
        }
        value = value.toString().split('e');
        value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
    }

    if (!Math.round10) {
        Math.round10 = function (value, exp) {
            return decimalAdjust('round', value, exp);
        };
    }
    if (!Math.floor10) {
        Math.floor10 = function (value, exp) {
            return decimalAdjust('floor', value, exp);
        };
    }
    if (!Math.ceil10) {
        Math.ceil10 = function (value, exp) {
            return decimalAdjust('ceil', value, exp);
        };
    }
})();

function getRandomInt(max) {
    return Math.floor(Math.random() * Math.floor(max));
}

// Divers:

function getUrlParam(param) {
    var search = window.location.search.replace('?', '');
    var args = search.split('&');
    var value = '';
    for (i in args) {
        var regex = new RegExp('^' + param + '=(.*)$');
        if (regex.test(args[i])) {
            value = args[i].replace(regex, '$1');
        }
    }
    return value;
}

function getUrlParams(param) {
    var search = window.location.search.replace('?', '');
    var args = search.split('&');
    var params = {};
    for (i in args) {
        var regex = new RegExp('^(.*)=(.*)$');
        if (regex.test(args[i])) {
            var name = args[i].replace(regex, '$1');
            var value = args[i].replace(regex, '$2');
            if (name && value) {
                params[name] = value;
            }
        }
    }
    return params;
}

function bimp_reloadPage() {
    // Recharge la page en cours en tenant compte des onglets actifs: 

    var url = window.location.pathname + '?';

    var navtabs = {};

    var $navtabs = $('body').find('.nav-tabs');
    if ($navtabs.length) {
        $navtabs.each(function () {
            var name = 'navtab-' + $(this).data('navtabs_id');
            var active = '';
            $(this).find('li').each(function () {
                if (!active) {
                    if ($(this).hasClass('active')) {
                        active = $(this).data('navtab_id');
                    }
                }
            });
            if (active) {
                navtabs[name] = active;
            }
        });
    }

    var tab = '';
    if (window.location.hash) {
        tab = window.location.hash.replace('#', '');
    }

    var params = getUrlParams();

    var first = true;
    var hasParams = false;
    for (var param_name in params) {
        if (param_name === 'tab') {
            if (!tab) {
                tab = params[param_name];
            }
        } else if (!/^navtab\-?.*$/.test(param_name)) {
            if (!first) {
                url += '&';
            } else {
                first = false;
            }
            url += param_name + '=' + params[param_name];
            hasParams = true;
        }
    }

    first = true;
    for (var tabname in navtabs) {
        if (!first) {
            url += '&';
        } else {
            if (hasParams) {
                url += '&';
            }
            first = false;
        }
        url += tabname + '=' + navtabs[tabname];
    }

    if (tab) {
        if (hasParams) {
            url += '&';
        }
        url += 'tab=' + tab;
    }

    window.location = url;
}

function bimp_htmlDecode(html) {
    if (!bimp_decode_textarea) {
        bimp_decode_textarea = document.createElement('textarea');
    }

    bimp_decode_textarea.innerHTML = html;
    return bimp_decode_textarea.value;
}

function bimp_copyTabsUrl($button, url, server) {
    if (typeof (url) !== 'string' || !url) {
        url = window.location;
    }

    url = url.replace(/#.*$/, '');

    var url_base = url.replace(/^([^\?]+)(\?.*)?$/, '$1');
    var query = url.replace(/^([^\?]+)\??(.*)?$/, '$2');
    var args = [];

    if (query) {
        args = query.split('&');
    }

    var params = [];

    if (args.length) {
        for (var i in args) {
            if (!/^navtab(\-.*)?=.*$/.test(args[i])) {
                params.push(args[i]);
            }
        }
    }

//    var $tabs = $('.bimp_controller_content').find('.tabs');
//    if ($tabs.length) {
//        if ($tabs.length > 1) {
//            bimp_msg('Erreur: il y a plusieurs barres d\'onglets de premier niveau', 'danger', null, true);
//            return;
//        }
//
//        var $a = $tabs.find('.tabactive');
//        if ($a.length) {
//            if ($a.length > 1) {
//                bimp_msg('Erreur: il y a plusieurs onglets de premier niveau actifs', 'danger', null, true);
//                return;
//            }
//
//            var id = $a.attr('id');
//            if (id) {
//                params.push('tab=' + id);
//            }
//        }
//    }

    var $container = false;

    if ($.isOk($button)) {
        var $modal = $button.findParentByClass('modal_content');
        if ($.isOk($modal)) {
            $container = $modal;
        }
    }

    if (!$container) {
        $container = $('.bimp_controller_content');

        if (!$container.length) {
            $container = $('body');
        }
    }

    var $navtabs = $container.find('ul.nav-tabs');

    if ($navtabs.length) {
        $navtabs.each(function () {
            var $parent_navtab = $(this).findParentByClass('tab-pane');

            if (!$.isOk($parent_navtab) || $parent_navtab.hasClass('active')) {
                var navtabs_id = $(this).data('navtabs_id');
                var $navtab = $(this).find('li.active');

                if ($navtab.length) {
                    var navtab_id = $navtab.data('navtab_id');

                    if (navtab_id) {
                        var param = 'navtab';
                        if (navtabs_id) {
                            param += '-' + navtabs_id;
                        }
                        param += '=' + navtab_id;
                        params.push(param);
                    }
                }
            }
        });
    }

    url = '';

    if (typeof (server) === 'string') {
        var regex = new RegExp('^' + server + '(.*)$', 'i');
        if (!regex.test(url_base)) {
            url += server;
        }
    }

    if (!/^\/.*$/.test(url_base)) {
        url += '/';
    }

    url += url_base;
    if (params.length) {
        url += '?';

        var fl = true;
        for (var j in params) {
            if (!fl) {
                url += '&';
            } else {
                fl = false;
            }
            url += params[j];
        }
    }

    if (typeof (navigator.clipboard) !== 'undefined') {
        if (typeof (navigator.clipboard.writeText) === 'function') {
            navigator.clipboard.writeText(url);
            bimp_msg('Lien copié', 'success', null, true);
            return;
        }
    }

    bimp_msg('Lien: </br></br>' + url);
}

// Ajouts jQuery:

$.fn.tagName = function () {
    if (this.length) {
        var elem = this.get(0);
        if (elem && elem.tagName) {
            return elem.tagName.toLowerCase();
        }
    }
    return '';
};

$.fn.findParentByClass = function (className) {
    if (!this.length) {
        return this;
    }

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.hasClass(className)) {
            return $parent;
        }
        $parent = $parent.parent();
    }
    return $();
};

$.fn.findParentByTag = function (tag) {
    if (!this.length) {
        return this;
    }

    tag = tag.toLowerCase();

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.tagName() === tag) {
            return $parent;
        }
        $parent = $parent.parent();
    }

    return $();
};

$.isOk = function (object) {
    if (object === null) {
        return false;
    }

    if (typeof (object) !== 'object') {
        return false;
    }

    if (typeof (object.length) === 'undefined') {
        return false;
    }

    if (!object.length) {
        return false;
    }

    return true;
};

function findParentByClass($element, className) {
    return $element.findParentByClass(className);
}

function findParentByTag($element, tag) {
    return $element.findParentByTag(tag);
}

$(document).ready(function () {
    $('body').click(function (e) {
        $(this).find('.hideOnClickOut').removeClass('locked').hide();
        $(this).find('.destroyPopoverOnClickOut').popover('destroy');
        $(this).find('.bs-popover').popover('hide');
        $(this).find('.popover.fade').remove();
    });

    // Notifications: 
    var html = '<div id="page_notifications">';
    html += '<div id="notifications_tools">';
    html += '<span class="btn btn-danger removeAllBimpMsgButton" onclick="removeAllNotifications()"><i class="far fa5-times-circle iconLeft"></i>Masquer toutes les notifications</span>';
    html += '</div>';
    html += '</div>';

    $('body').append(html);
    setNotificationsEvents();

    // Notifications importantes (modale): 
    insertNotificationsModal();

    // Debug Modale: 
    var $debugModal = $('#debug_modal');
    if ($debugModal.length) {
        BimpDebugModal = new BimpModal($debugModal, 'BimpDebugModal', 'openDebugModalBtn', {
            'content_removable': false,
            'max_contents': 'none'
        });

        var $debugContent = $('#bimp_page_debug_content');
        if ($debugContent.length) {
            BimpDebugModal.newContent('Debug chargement page', $debugContent.html(), false, '', null, 'large', false);
            $debugContent.remove();
        }
    }

    // Evénements communs: 
    $('.object_header').each(function () {
        setCommonEvents($(this));
    });

    setCommonEvents($('body'));
    setInputsEvents($('body'));

    $(window).scroll(function () {
        onWindowScroll();
    });

    $('body').on('contentLoaded', function (e) {
        if (e.$container.length) {
            setCommonEvents(e.$container);
        }
    });

    $('body').keydown(function (e) {
        if (e.key === 'Alt') {
            ctrl_down = true;
            $(this).find('.object_page_header').each(function () {
                setObjectHeaderPosition($(this));
            });
        } else if (ctrl_down && !text_input_focused) {
            if (e.key === 'ArrowRight') {
                navTabNext('maintabs');
            } else if (e.key === 'ArrowLeft') {
                navTabPrev('maintabs');
            } else if (e.key === 'ArrowUp') {
                $(window).scrollTop(0);
            } else if (e.key === 'ArrowDown') {
                var maxScroll = $('body').height() - $(window).height();
                $(window).scrollTop(maxScroll);
            }
        }
    });
    $('body').keyup(function (e) {
        if (e.key === 'Alt') {
            ctrl_down = false;
            $(this).find('.object_page_header').each(function () {
                setObjectHeaderPosition($(this));
            });
        }
    });
});
