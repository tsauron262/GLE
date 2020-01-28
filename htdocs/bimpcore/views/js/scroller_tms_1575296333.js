function BimpScroller() {
    var scroller = this;
    this.curWndScrollTop = 0;
    this.$container = $(window);
    this.$element = $('body');

    this.params = {
        loop: {
            max: 250,
            delay: 40
        },
        accel: {
            multiplicator: 2
        },
        decel: {
            divisor: 2,
            distanceToEnd: 250
        }
    };

    this.curScroll = {
        isScrolling: false,
        nextDistance: 1,
        lastCallTop: 0,
        curEndValue: 0
    };

    this.reset = function () {
        this.curScroll.isScrolling = false;
        this.curScroll.nextDistance = 1;
        this.curScroll.lastCallTop = 0;
        this.curScroll.curEndValue = 0;
    };

    this.newScrollToAnchor = function (anchorId, offsetTop, $container, $element) {
        this.reset();
        this.setElements($container, $element);

        if (typeof (offsetTop) === 'undefined')
            offsetTop = 0;
        if (anchorId) {
            if (anchorId === 'top') {
                this.curScroll.curEndValue = 0;
            } else {
                var $anchor = $('#' + anchorId);
                if (!$anchor.length) {
                    this.reset();
                    return;
                }
                this.curScroll.curEndValue = $anchor.offset().top - offsetTop;
            }
        }
        this.curScroll.isScrolling = true;
        this.scroll();
    };

    this.newScrollValue = function (value, $container, $element) {
        this.reset();
        this.setElements($container, $element);

        this.curScroll.curEndValue = value;
        this.curScroll.isScrolling = true;
        this.scroll();
    };

    this.newScrollToBottom = function ($container, $element) {
        this.reset();
        this.setElements($container, $element);

        var maxScroll = this.$element.height() - this.$container.height();
        this.curScroll.curEndValue = maxScroll;
        this.curScroll.isScrolling = true;
        this.scroll();
    };

    this.scroll = function () {
        var start = this.$container.scrollTop();
        var end = 0;
        var maxScroll = this.$element.height() - this.$container.height();
        var newScroll = 0;

        if (!this.curScroll.isScrolling) {
            this.reset();
            return;
        }

        end = this.curScroll.curEndValue;

        if (end < 0)
            end = 0;
        if (end > maxScroll)
            end = maxScroll;

        this.curScroll.curEndValue = end;

        var diff = end - start;

        if (!diff) {
            newScroll = end;
        } else if (diff > 0) {
            if (diff <= this.params.decel.distanceToEnd) {
                this.curScroll.nextDistance = diff / this.params.decel.divisor;
                if (this.curScroll.nextDistance < 1)
                    this.curScroll.nextDistance = 1;
            } else {
                this.curScroll.nextDistance *= this.params.accel.multiplicator;
                if (this.curScroll.nextDistance > this.params.loop.max)
                    this.curScroll.nextDistance = this.params.loop.max;
            }
            newScroll = start + this.curScroll.nextDistance;
            if (newScroll > end)
                newScroll = end;
        } else {
            diff *= -1;
            if (diff <= this.params.decel.distanceToEnd) {
                this.curScroll.nextDistance = diff / this.params.decel.divisor;
                if (this.curScroll.nextDistance < 1)
                    this.curScroll.nextDistance = 1;
            } else {
                this.curScroll.nextDistance *= this.params.accel.multiplicator;
                if (this.curScroll.nextDistance > this.params.loop.max)
                    this.curScroll.nextDistance = this.params.loop.max;
            }
            newScroll = start - this.curScroll.nextDistance;
            if (newScroll < end)
                newScroll = end;
        }

        this.curWndScrollTop = Math.floor(newScroll);
        this.$container.scrollTop(this.curWndScrollTop);

        if (newScroll !== end)
            setTimeout(function () {
                scroller.scroll();
            }, this.params.loop.delay);
        else {
            this.reset();
        }
    };

    this.checkForUserScrollStop = function (wndScrollTop) {
        // Arrête le scrolling si le user intervient
        if (!this.curScroll.isScrolling)
            return;

        if (wndScrollTop < scroller.curWndScrollTop) {
            if (this.curScroll.curEndValue > scroller.curWndScrollTop) {
                this.curScroll.curEndValue = wndScrollTop;
                this.reset();
            }
        } else if (wndScrollTop > scroller.curWndScrollTop) {
            if (this.curScroll.curEndValue < scroller.curWndScrollTop) {
                this.curScroll.curEndValue = wndScrollTop;
                this.reset();
            }
        }
    };

    this.setElements = function ($container, $element) {
        if (!$.isOk($container)) {
            $container = $(window);
        }

        if (!$.isOk($element)) {
            $element = $('body');
        }

        this.$container = $container;
        this.$element = $element;

        this.setContainerScrollEvents($container);
    };

    this.setContainerScrollEvents = function ($container) {
        if (!parseInt($container.data('bimp_scroller_events_init'))) {
            $container.scroll(function () {
                scroller.checkForUserScrollStop($container.scrollTop());
            });

            $container.data('bimp_scroller_events_init', 1);
        }
    };
}

var bimpScroller = new BimpScroller();