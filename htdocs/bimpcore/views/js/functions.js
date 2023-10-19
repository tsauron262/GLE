// Notifications:
var bimp_msg_enable = true;
var ctrl_down = false;
var shift_down = false;
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

function openAllFoldable($container) {
    $container.find('.foldable_container.closed').each(function () {
        $(this).removeClass('closed').addClass('open');
        $(this).children('.foldable_content').show();
    });
}

function closeAllFoldable($container) {
    $container.find('.foldable_container.open').each(function () {
        $(this).removeClass('open').addClass('closed');
        $(this).children('.foldable_content').hide();
    });
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
    // Select Color: 
    $container.find('select').each(function () {
        if (!parseInt($(this).data('color_event_init'))) {
            checkSelectColor($(this));
            $(this).change(function () {
                checkSelectColor($(this));
            });
            $(this).data('color_event_init', 1);
        }
    });
    // Nav tabs
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
    // Hide on click out: 
    $container.find('.hideOnClickOut').each(function () {
        if (!parseInt($(this).data('hide_on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();

                $(this).find('ul.dropdown-menu').hide();
            });

            // Patch:  
//            $(this).find('.dropdown-toggle').each(function () {
//                if (!parseInt($(this).data('dropdown_btns_events_init'))) {
//                    var $menu = $(this).parent().children('ul.dropdown-menu').show();
//                    if ($.isOk($menu)) {
//                        $menu.hide();
//                        $(this).click(function (e) {
//                            if ($menu.css('display') === 'none') {
//                                $menu.show();
//                            } else {
//                                $menu.hide();
//                            }
//                            e.stopPropagation();
//                        });
//                    }
//                    $(this).data('dropdown_btn_event_init', 1);
//                }
//
//            });
            $(this).data('hide_on_click_event_init', 1);
        }
    });
    // Product stock button: 
    $container.find('.displayProductStocksBtn').each(function () {
        if (!parseInt($(this).data('on_click_event_init'))) {
            $(this).click(function (e) {
                e.stopPropagation();
                displayProductStocks($(this), $(this).data('id_product'), $(this).data('id_entrepot'));
            });

            $(this).data('on_click_event_init', 1);
        }
    });
    // Tab link: 
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
    // Dol tooltip: 
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
    // Card popover icon: 
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
    // Multiple values: 
    checkMultipleValues();
    // Copy Icon: 
    $container.find('.copyTextIcon').each(function () {
//        $(this).click(function (e) {
//            e.preventDefault();
//            e.stopPropagation();
//            var text = $(this).data('text');
//
//            if (text) {
//                navigator.permissions.query({name: "clipboard-write"}).then((result) => {
//                    if (result.state === "granted" || result.state === "prompt") {
//                        bimp_msg('ici');
//                        if (typeof (window.navigator.clipboard) !== 'undefined' && typeof (window.navigator.clipboard.writeText) === 'function') {
//                            window.navigator.clipboard.writeText(text).then(
//                                    () => {
//                                bimp_msg('Copié', 'success', null, true);
//                            },
//                                    () => {
//                                bimp_msg('Echec copie: ' + text, 'danger', null, true);
//                            }
//                            );
//                        }
//                        return;
//                    } else {
//                        bimp_msg('KO');
//                    }
//                });
//
//                bimp_msg('Copie impossible : ' + text, 'danger', null, false);
//
//            } else {
//                bimp_msg('Aucun texte à copier', 'danger', null, true);
//            }
//        });
    });
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
//        $popup.addClass('hideOnClickOut'); // Fait bugguer les dropdowns inclus dans le popup
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

function setInputCursorPos($input, pos) {
    if ($.isOk($input)) {
        var tagName = $input.tagName();
        if (tagName === 'input' || tagName === 'textarea') {
            var elem = $input.get(0);

            if (elem.setSelectionRange) {
                elem.setSelectionRange(pos, pos);
            } else if (elem.createTextRange) {
                var range = elem.createTextRange();
                range.collapse(true);
                range.moveEnd('character', pos);
                range.moveStart('character', pos);
                range.select();
            }
        }
    }
}

function setCKEditorCursorPos(instance_name, pos) {
    if (typeof (CKEDITOR.instances[instance_name]) !== 'undefined') {
        var editor = CKEDITOR.instances[instance_name];
        editor.focus();

        var selection = editor.getSelection();
        var range = selection.getRanges()[0];
        var pCon = range.startContainer.getAscendant({p: 2}, true); //getAscendant('p',true);
        var newRange = new CKEDITOR.dom.range(range.document);
        newRange.moveToPosition(pCon, pos);
        newRange.select();
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
            var protocol = window.location.protocol;
            if (protocol != '')
                server = protocol + '//' + server;
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

function selectElementText($element) {
    if ($.isOk($element)) {
        var element = $element.get(0);

        if (element) {
            var text = document.getElementById(element);
            if (document.body.createTextRange) { // Internet Explorer
                var range = document.body.createTextRange();
                range.moveToElementText(text);
                range.select();
            } else if (window.getSelection) { // Mozilla, Chrome, Opera
                var selection = window.getSelection();
                var range = document.createRange();
                range.selectNodeContents(text);
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                bimp_msg('Sélection du text non disponible sur votre navigateur', 'danger', null, true);
            }
        }
    }
}

function bimp_htmlentities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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

/*
 * simpleUpload.js v.1.1
 *
 * Copyright 2018, Michael Brook, All rights reserved.
 * http://simpleupload.michaelcbrook.com/
 *
 * simpleUpload.js is an extremely simple yet powerful jQuery file upload plugin.
 * It is free to use under the MIT License (http://opensource.org/licenses/MIT)
 *
 * https://github.com/michaelcbrook/simpleUpload.js
 * @michaelcbrook
 */
function simpleUpload(e, l, n) {
    var t = !1, a = null, o = 0, r = 0, i = [], s = [], p = "auto", u = null, f = null, d = "file", c = {}, m = {}, h = function (e) {}, v = function (e) {}, y = function (e) {}, U = function (e) {}, g = function (e) {}, b = function () {}, w = function (e) {}, x = function () {}, j = function (e, l) {}, k = [], E = [], S = {files: k}, z = 0, F = null, T = function (e, l) {
        M(e, l), 0 == --z && D(), simpleUpload.activeUploads--, simpleUpload.uploadNext()
    }, C = function (e) {
        return h.call(S, e)
    }, I = function (e, l) {
        return!(R(e) > 0) && (!1 === v.call(k[e], l) ? (O(e, 4), !1) : !(R(e) > 0) && void O(e, 1))
    }, L = function (e, l) {
        1 == R(e) && y.call(k[e], l)
    }, q = function (e, l) {
        1 == R(e) && (O(e, 2), U.call(k[e], l), T(e, "success"))
    }, W = function (e, l) {
        1 == R(e) && (O(e, 3), g.call(k[e], l), T(e, "error"))
    }, _ = function (e) {
        b.call(k[e]), T(e, "cancel")
    }, M = function (e, l) {
        w.call(k[e], l)
    }, D = function () {
        x.call(S), null != F && F.remove()
    }, N = function (e, l, n) {
        j.call(k[e], l, n)
    };
    function A(n) {
        if (1 == R(n)) {
            if (null != a) {
                if (null == a[n] || null == a[n])
                    return void W(n, {name: "InternalError", message: "There was an error uploading the file"});
                if (window.FormData) {
                    var t = $.ajaxSettings.xhr();
                    if (t.upload) {
                        var o = a[n], r = new FormData;
                        !function e(l, n, t) {
                            null != t && "" !== t || (t = null);
                            for (var a in n)
                                void 0 === n[a] || null === n[a] ? l.append(null == t ? a + "" : t + "[" + a + "]", "") : "object" == typeof n[a] ? e(l, n[a], null == t ? a + "" : t + "[" + a + "]") : "boolean" == typeof n[a] ? l.append(null == t ? a + "" : t + "[" + a + "]", n[a] ? "true" : "false") : "number" == typeof n[a] ? l.append(null == t ? a + "" : t + "[" + a + "]", n[a] + "") : "string" == typeof n[a] && l.append(null == t ? a + "" : t + "[" + a + "]", n[a])
                        }(r, c), r.append(d, o);
                        var i = {url: e, data: r, type: "post", cache: !1, xhrFields: m, beforeSend: function (e, l) {
                                N(n, e, l), E[n].xhr = e
                            }, xhr: function () {
                                return t.upload.addEventListener("progress", function (e) {
                                    e.lengthComputable && L(n, e.loaded / e.total * 100)
                                }, !1), t
                            }, error: function (e) {
                                E[n].xhr = null, W(n, {name: "RequestError", message: "Upload failed", xhr: e})
                            }, success: function (e) {
                                E[n].xhr = null, L(n, 100), q(n, e)
                            }, contentType: !1, processData: !1};
                        return"auto" != p && (i.dataType = p), void $.ajax(i)
                    }
                }
            }
            "object" == typeof l && null !== l ? function (l) {
                if (0 == l) {
                    var n = simpleUpload.queueIframe({origin: function (e) {
                            var l = document.createElement("a");
                            l.href = e;
                            var n = l.host, t = l.protocol;
                            "" == n && (n = window.location.host);
                            "" != t && ":" != t || (t = window.location.protocol);
                            return t.replace(/\:$/, "") + "://" + n
                        }(e), expect: p, complete: function (e) {
                            1 == R(l) && (E[l].iframe = null, simpleUpload.dequeueIframe(n), L(l, 100), q(l, e))
                        }, error: function (e) {
                            1 == R(l) && (E[l].iframe = null, simpleUpload.dequeueIframe(n), W(l, {name: "RequestError", message: e}))
                        }});
                    E[l].iframe = n;
                    var t = function e(l, n) {
                        null != n && "" !== n || (n = null);
                        var t = "";
                        for (var a in l)
                            void 0 === l[a] || null === l[a] ? t += $("<div>").append($('<input type="hidden">').attr("name", null == n ? a + "" : n + "[" + a + "]").val("")).html() : "object" == typeof l[a] ? t += e(l[a], null == n ? a + "" : n + "[" + a + "]") : "boolean" == typeof l[a] ? t += $("<div>").append($('<input type="hidden">').attr("name", null == n ? a + "" : n + "[" + a + "]").val(l[a] ? "true" : "false")).html() : "number" == typeof l[a] ? t += $("<div>").append($('<input type="hidden">').attr("name", null == n ? a + "" : n + "[" + a + "]").val(l[a] + "")).html() : "string" == typeof l[a] && (t += $("<div>").append($('<input type="hidden">').attr("name", null == n ? a + "" : n + "[" + a + "]").val(l[a])).html());
                        return t
                    }(c);
                    F.attr("action", e + (-1 == e.lastIndexOf("?") ? "?" : "&") + "_iframeUpload=" + n + "&_=" + (new Date).getTime()).attr("target", "simpleUpload_iframe_" + n).prepend(t).submit()
                } else
                    W(l, {name: "UnsupportedError", message: "Multiple file uploads not supported"})
            }(n) : W(n, {name: "UnsupportedError", message: "Your browser does not support this upload method"})
        }
    }
    function R(e) {
        return E[e].state
    }
    function O(e, l) {
        var n = "";
        if (0 == l)
            n = "init";
        else if (1 == l)
            n = "uploading";
        else if (2 == l)
            n = "success";
        else if (3 == l)
            n = "error";
        else {
            if (4 != l)
                return!1;
            n = "cancel"
        }
        E[e].state = l, k[e].upload.state = n
    }
    function B(e) {
        var l = e.lastIndexOf(".");
        return-1 != l ? e.substr(l + 1) : ""
    }
    function J(e) {
        return!isNaN(e) && parseInt(e) + "" == e
    }
    !function () {
        if ("object" == typeof n && null !== n) {
            if ("boolean" == typeof n.forceIframe && (t = n.forceIframe), "function" == typeof n.init && (h = n.init), "function" == typeof n.start && (v = n.start), "function" == typeof n.progress && (y = n.progress), "function" == typeof n.success && (U = n.success), "function" == typeof n.error && (g = n.error), "function" == typeof n.cancel && (b = n.cancel), "function" == typeof n.complete && (w = n.complete), "function" == typeof n.finish && (x = n.finish), "function" == typeof n.beforeSend && (j = n.beforeSend), "string" == typeof n.hashWorker && "" != n.hashWorker && (u = n.hashWorker), "function" == typeof n.hashComplete && (f = n.hashComplete), "object" == typeof n.data && null !== n.data)
                for (var e in n.data)
                    c[e] = n.data[e];
            if ("number" == typeof n.limit && J(n.limit) && n.limit > 0 && (o = n.limit), "number" == typeof n.maxFileSize && J(n.maxFileSize) && n.maxFileSize > 0 && (r = n.maxFileSize), "object" == typeof n.allowedExts && null !== n.allowedExts)
                for (var e in n.allowedExts)
                    i.push(n.allowedExts[e]);
            if ("object" == typeof n.allowedTypes && null !== n.allowedTypes)
                for (var e in n.allowedTypes)
                    s.push(n.allowedTypes[e]);
            if ("string" == typeof n.expect && "" != n.expect) {
                var S = n.expect.toLowerCase(), T = ["auto", "json", "xml", "html", "script", "text"];
                for (var e in T)
                    if (T[e] == S) {
                        p = S;
                        break
                    }
            }
            if ("object" == typeof n.xhrFields && null !== n.xhrFields)
                for (var e in n.xhrFields)
                    m[e] = n.xhrFields[e]
        }
        if ("object" == typeof l && null !== l && l instanceof jQuery) {
            if (!(l.length > 0))
                return!1;
            l = l.get(0)
        }
        if (!t && window.File && window.FileReader && window.FileList && window.Blob && ("object" == typeof n && null !== n && "object" == typeof n.files && null !== n.files ? a = n.files : "object" == typeof l && null !== l && "object" == typeof l.files && null !== l.files && (a = l.files)), ("object" != typeof l || null === l) && null == a)
            return!1;
        "object" == typeof n && null !== n && "string" == typeof n.name && "" != n.name ? d = n.name.replace(/\[\s*\]/g, "[0]") : "object" == typeof l && null !== l && "string" == typeof l.name && "" != l.name && (d = l.name.replace(/\[\s*\]/g, "[0]"));
        var M = 0;
        if (null != a ? a.length > 0 && (M = a.length > 1 && window.FormData && $.ajaxSettings.xhr().upload ? o > 0 && a.length > o ? o : a.length : 1) : "" != l.value && (M = 1), M > 0) {
            if ("object" == typeof l && null !== l) {
                var N = $(l);
                F = $("<form>").hide().attr("enctype", "multipart/form-data").attr("method", "post").appendTo("body"), N.after(N.clone(!0).val("")).removeAttr("onchange").off().removeAttr("id").attr("name", d).appendTo(F)
            }
            for (var Q = 0; Q < M; Q++)
                !function (e) {
                    E[e] = {state: 0, hashWorker: null, xhr: null, iframe: null}, k[e] = {upload: {index: e, state: "init", file: null != a ? a[e] : {name: l.value.split(/(\\|\/)/g).pop()}, cancel: function () {
                                if (0 == R(e))
                                    O(e, 4);
                                else {
                                    if (1 != R(e))
                                        return!1;
                                    O(e, 4), null != E[e].hashWorker && (E[e].hashWorker.terminate(), E[e].hashWorker = null), null != E[e].xhr && (E[e].xhr.abort(), E[e].xhr = null), null != E[e].iframe && ($("iframe[name=simpleUpload_iframe_" + E[e].iframe + "]").attr("src", "javascript:false;"), simpleUpload.dequeueIframe(E[e].iframe), E[e].iframe = null), _(e)
                                }
                                return!0
                            }}}
                }(Q);
            var H = C(M);
            if (!1 !== H) {
                var X = M;
                if ("number" == typeof H && J(H) && H >= 0 && H < M)
                    for (var Y = X = H; Y < M; Y++)
                        O(Y, 4);
                for (var G = [], K = 0; K < X; K++)
                    !1 !== I(K, k[K].upload.file) && (G[G.length] = K);
                G.length > 0 ? (z = G.length, simpleUpload.queueUpload(G, function (e) {
                    !function (e) {
                        if (1 == R(e)) {
                            var n = null;
                            if (null != a) {
                                if (null == a[e] || null == a[e])
                                    return void W(e, {name: "InternalError", message: "There was an error uploading the file"});
                                n = a[e]
                            } else if ("" == l.value)
                                return void W(e, {name: "InternalError", message: "There was an error uploading the file"});
                            i.length > 0 && !function (e, n) {
                                if (null != n && null != n) {
                                    var t = n.name;
                                    if (null != t && null != t && "" != t) {
                                        var a = B(t).toLowerCase();
                                        if ("" != a) {
                                            var o = !1;
                                            for (var r in e)
                                                if (e[r].toLowerCase() == a) {
                                                    o = !0;
                                                    break
                                                }
                                            return!!o
                                        }
                                        return!1
                                    }
                                }
                                if ("object" != typeof l || null === l)
                                    return!0;
                                var i = l.value;
                                if ("" != i) {
                                    var a = B(i).toLowerCase();
                                    if ("" != a) {
                                        var o = !1;
                                        for (var r in e)
                                            if (e[r].toLowerCase() == a) {
                                                o = !0;
                                                break
                                            }
                                        if (o)
                                            return!0
                                    }
                                }
                                return!1
                            }(i, n) ? W(e, {name: "InvalidFileExtensionError", message: "That file format is not allowed"}) : s.length > 0 && !function (e, l) {
                                if (null != l && null != l) {
                                    var n = l.type;
                                    if (null != n && null != n && "" != n) {
                                        n = n.toLowerCase();
                                        var t = !1;
                                        for (var a in e)
                                            if (e[a].toLowerCase() == n) {
                                                t = !0;
                                                break
                                            }
                                        return!!t
                                    }
                                }
                                return!0
                            }(s, n) ? W(e, {name: "InvalidFileTypeError", message: "That file format is not allowed"}) : r > 0 && !function (e, l) {
                                if (null != l && null != l) {
                                    var n = l.size;
                                    if (null != n && null != n && "" != n && J(n))
                                        return n <= e
                                }
                                return!0
                            }(r, n) ? W(e, {name: "MaxFileSizeError", message: "That file is too big"}) : null != u && null != f ? function (e) {
                                if (null != a && null != a[e] && null != a[e] && window.Worker) {
                                    var l = a[e];
                                    if (null != l.size && null != l.size && "" != l.size && J(l.size) && (l.slice || l.webkitSlice || l.mozSlice))
                                        try {
                                            var n, t, o, r, i, s, p = new Worker(u);
                                            return p.addEventListener("error", function (l) {
                                                p.terminate(), E[e].hashWorker = null, A(e)
                                            }, !1), p.addEventListener("message", function (l) {
                                                if (l.data.result) {
                                                    var n = l.data.result;
                                                    p.terminate(), E[e].hashWorker = null, function (e, l) {
                                                        if (1 == R(e)) {
                                                            var n = !1;
                                                            f.call(k[e], l, {success: function (l) {
                                                                    return 1 == R(e) && !n && (n = !0, L(e, 100), q(e, l), !0)
                                                                }, proceed: function () {
                                                                    return 1 == R(e) && !n && (n = !0, A(e), !0)
                                                                }, error: function (l) {
                                                                    return 1 == R(e) && !n && (n = !0, W(e, {name: "HashError", message: l}), !0)
                                                                }})
                                                        }
                                                    }(e, n)
                                                }
                                            }, !1), s = function (e) {
                                                p.postMessage({message: e.target.result, block: t})
                                            }, i = function (e) {
                                                t.end !== l.size && (t.start += n, t.end += n, t.end > l.size && (t.end = l.size), (o = new FileReader).onload = s, l.slice ? r = l.slice(t.start, t.end) : l.webkitSlice ? r = l.webkitSlice(t.start, t.end) : l.mozSlice && (r = l.mozSlice(t.start, t.end)), o.readAsArrayBuffer(r))
                                            }, n = 1048576, (t = {file_size: l.size, start: 0}).end = n > l.size ? l.size : n, p.addEventListener("message", i, !1), (o = new FileReader).onload = s, l.slice ? r = l.slice(t.start, t.end) : l.webkitSlice ? r = l.webkitSlice(t.start, t.end) : l.mozSlice && (r = l.mozSlice(t.start, t.end)), o.readAsArrayBuffer(r), void(E[e].hashWorker = p)
                                        } catch (e) {
                                        }
                                }
                                A(e)
                            }(e) : A(e)
                        }
                    }(e)
                }), simpleUpload.uploadNext()) : D()
            } else {
                for (var Y in k)
                    O(Y, 4);
                D()
            }
        }
    }()
}
simpleUpload.maxUploads = 10, simpleUpload.activeUploads = 0, simpleUpload.uploads = [], simpleUpload.iframes = {}, simpleUpload.iframeCount = 0, simpleUpload.queueUpload = function (e, l) {
    simpleUpload.uploads[simpleUpload.uploads.length] = {uploads: e, callback: l}
}, simpleUpload.uploadNext = function () {
    if (simpleUpload.uploads.length > 0 && simpleUpload.activeUploads < simpleUpload.maxUploads) {
        var e = simpleUpload.uploads[0], l = e.callback, n = e.uploads.splice(0, 1)[0];
        0 == e.uploads.length && simpleUpload.uploads.splice(0, 1), simpleUpload.activeUploads++, l(n), simpleUpload.uploadNext()
    }
}, simpleUpload.queueIframe = function (e) {
    for (var l = 0; 0 == l || l in simpleUpload.iframes; )
        l = Math.floor(999999999 * Math.random() + 1);
    return simpleUpload.iframes[l] = e, simpleUpload.iframeCount++, $("body").append('<iframe name="simpleUpload_iframe_' + l + '" style="display: none;"></iframe>'), l
}, simpleUpload.dequeueIframe = function (e) {
    e in simpleUpload.iframes && ($("iframe[name=simpleUpload_iframe_" + e + "]").remove(), delete simpleUpload.iframes[e], simpleUpload.iframeCount--)
}, simpleUpload.convertDataType = function (e, l, n) {
    var t = "auto";
    if ("auto" == e) {
        if ("string" == typeof l && "" != l) {
            var a = l.toLowerCase(), o = ["json", "xml", "html", "script", "text"];
            for (var r in o)
                if (o[r] == a) {
                    t = a;
                    break
                }
        }
    } else
        t = e;
    if ("auto" == t)
        return void 0 === n ? "" : "object" == typeof n ? n : String(n);
    if ("json" == t) {
        if (null == n)
            return null;
        if ("object" == typeof n)
            return n;
        if ("string" == typeof n)
            try {
                return $.parseJSON(n)
            } catch (e) {
                return!1
            }
        return!1
    }
    if ("xml" == t) {
        if (null == n)
            return null;
        if ("string" == typeof n)
            try {
                return $.parseXML(n)
            } catch (e) {
                return!1
            }
        return!1
    }
    if ("script" == t) {
        if (void 0 === n)
            return"";
        if ("string" == typeof n)
            try {
                return $.globalEval(n), n
            } catch (e) {
                return!1
            }
        return!1
    }
    return void 0 === n ? "" : String(n)
}, simpleUpload.iframeCallback = function (e) {
    if ("object" == typeof e && null !== e) {
        var l = e.id;
        if (l in simpleUpload.iframes) {
            var n = simpleUpload.convertDataType(simpleUpload.iframes[l].expect, e.type, e.data);
            !1 !== n ? simpleUpload.iframes[l].complete(n) : simpleUpload.iframes[l].error("Upload failed")
        }
    }
}, simpleUpload.postMessageCallback = function (e) {
    try {
        var l = e[e.message ? "message" : "data"];
        if ("string" == typeof l && "" != l && "object" == typeof (l = $.parseJSON(l)) && null !== l && "string" == typeof l.namespace && "simpleUpload" == l.namespace) {
            var n = l.id;
            if (n in simpleUpload.iframes && e.origin === simpleUpload.iframes[n].origin) {
                var t = simpleUpload.convertDataType(simpleUpload.iframes[n].expect, l.type, l.data);
                !1 !== t ? simpleUpload.iframes[n].complete(t) : simpleUpload.iframes[n].error("Upload failed")
            }
        }
    } catch (e) {
    }
}, window.addEventListener ? window.addEventListener("message", simpleUpload.postMessageCallback, !1) : window.attachEvent("onmessage", simpleUpload.postMessageCallback), function (e) {
    "function" == typeof define && define.amd ? define(["jquery"], e) : "object" == typeof exports ? module.exports = e(require("jquery")) : e(jQuery)
}(function (e) {
    e.fn.simpleUpload = function (l, n) {
        return 0 == e(this).length && "object" == typeof n && null !== n && "object" == typeof n.files && null !== n.files ? (new simpleUpload(l, null, n), this) : this.each(function () {
            new simpleUpload(l, this, n)
        })
    }, e.fn.simpleUpload.maxSimultaneousUploads = function (e) {
        return void 0 === e ? simpleUpload.maxUploads : "number" == typeof e && e > 0 ? (simpleUpload.maxUploads = e, this) : void 0
    }
});

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
        } else if (e.key === 'Shift') {
            shift_down = true;
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
        } else if (e.key === 'Shift') {
            shift_down = false;
        }
    });
});
