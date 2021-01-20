'use strict';
!function(global, factory) {
  if ("object" == typeof exports && "undefined" != typeof module) {
    module.exports = factory();
  } else {
    if ("function" == typeof define && define.amd) {
      define(factory);
    } else {
      global.SignaturePad = factory();
    }
  }
}(this, function() {
  function Point(x, y, time) {
    this.x = x;
    this.y = y;
    this.time = time || (new Date).getTime();
  }
  function Bezier(start, control1, control2, endPoint) {
    this.startPoint = start;
    this.control1 = control1;
    this.control2 = control2;
    this.endPoint = endPoint;
  }
  function throttle(fn, wait, options) {
    var c;
    var n;
    var tmp;
    var timeoutId = null;
    var concurency = 0;
    if (!options) {
      options = {};
    }
    var later = function() {
      concurency = false === options.leading ? 0 : Date.now();
      timeoutId = null;
      tmp = fn.apply(c, n);
      if (!timeoutId) {
        c = n = null;
      }
    };
    return function() {
      var connectNumber = Date.now();
      if (!(concurency || false !== options.leading)) {
        concurency = connectNumber;
      }
      var remaining = wait - (connectNumber - concurency);
      return c = this, n = arguments, remaining <= 0 || remaining > wait ? (timeoutId && (clearTimeout(timeoutId), timeoutId = null), concurency = connectNumber, tmp = fn.apply(c, n), timeoutId || (c = n = null)) : timeoutId || false === options.trailing || (timeoutId = setTimeout(later, remaining)), tmp;
    };
  }
  function SignaturePad(canvas, options) {
    var self = this;
    var opts = options || {};
    this.velocityFilterWeight = opts.velocityFilterWeight || .7;
    this.minWidth = opts.minWidth || .5;
    this.maxWidth = opts.maxWidth || 2.5;
    this.throttle = "throttle" in opts ? opts.throttle : 16;
    this.minDistance = "minDistance" in opts ? opts.minDistance : 5;
    if (this.throttle) {
      this._strokeMoveUpdate = throttle(SignaturePad.prototype._strokeUpdate, this.throttle);
    } else {
      this._strokeMoveUpdate = SignaturePad.prototype._strokeUpdate;
    }
    this.dotSize = opts.dotSize || function() {
      return (this.minWidth + this.maxWidth) / 2;
    };
    this.penColor = opts.penColor || "black";
    this.backgroundColor = opts.backgroundColor || "rgba(0,0,0,0)";
    this.onBegin = opts.onBegin;
    this.onEnd = opts.onEnd;
    this._canvas = canvas;
    this._ctx = canvas.getContext("2d");
    this.clear();
    this._handleMouseDown = function(event) {
      if (1 === event.which) {
        self._mouseButtonDown = true;
        self._strokeBegin(event);
      }
    };
    this._handleMouseMove = function(event) {
      if (self._mouseButtonDown) {
        self._strokeMoveUpdate(event);
      }
    };
    this._handleMouseUp = function(event) {
      if (1 === event.which && self._mouseButtonDown) {
        self._mouseButtonDown = false;
        self._strokeEnd(event);
      }
    };
    this._handleTouchStart = function(event) {
      if (1 === event.targetTouches.length) {
        var touch = event.changedTouches[0];
        self._strokeBegin(touch);
      }
    };
    this._handleTouchMove = function(event) {
      event.preventDefault();
      var touch = event.targetTouches[0];
      self._strokeMoveUpdate(touch);
    };
    this._handleTouchEnd = function(event) {
      if (event.target === self._canvas) {
        event.preventDefault();
        self._strokeEnd(event);
      }
    };
    this.on();
  }
  return Point.prototype.velocityFrom = function(start) {
    return this.time !== start.time ? this.distanceTo(start) / (this.time - start.time) : 1;
  }, Point.prototype.distanceTo = function(v) {
    return Math.sqrt(Math.pow(this.x - v.x, 2) + Math.pow(this.y - v.y, 2));
  }, Point.prototype.equals = function(other) {
    return this.x === other.x && this.y === other.y && this.time === other.time;
  }, Bezier.prototype.length = function() {
    var llength = 0;
    var x = void 0;
    var y = void 0;
    var currentTime = 0;
    for (; currentTime <= 10; currentTime = currentTime + 1) {
      var t = currentTime / 10;
      var cx = this._point(t, this.startPoint.x, this.control1.x, this.control2.x, this.endPoint.x);
      var cy = this._point(t, this.startPoint.y, this.control1.y, this.control2.y, this.endPoint.y);
      if (currentTime > 0) {
        var dx = cx - x;
        var dy = cy - y;
        llength = llength + Math.sqrt(dx * dx + dy * dy);
      }
      x = cx;
      y = cy;
    }
    return llength;
  }, Bezier.prototype._point = function(t, start, c1, c2, end) {
    return start * (1 - t) * (1 - t) * (1 - t) + 3 * c1 * (1 - t) * (1 - t) * t + 3 * c2 * (1 - t) * t * t + end * t * t * t;
  }, SignaturePad.prototype.clear = function() {
    var ctx = this._ctx;
    var canvas = this._canvas;
    ctx.fillStyle = this.backgroundColor;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    this._data = [];
    this._reset();
    this._isEmpty = true;
  }, SignaturePad.prototype.fromDataURL = function(dataUrl) {
    var game = this;
    var options = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : {};
    var img = new Image;
    var ratio = options.ratio || window.devicePixelRatio || 1;
    var draw_width = options.width || this._canvas.width / ratio;
    var draw_height = options.height || this._canvas.height / ratio;
    this._reset();
    img.src = dataUrl;
    img.onload = function() {
      game._ctx.drawImage(img, 0, 0, draw_width, draw_height);
    };
    this._isEmpty = false;
  }, SignaturePad.prototype.toDataURL = function(type) {
    var _canvas;
    switch(type) {
      case "image/svg+xml":
        return this._toSVG();
      default:
        var length = arguments.length;
        var args = Array(length > 1 ? length - 1 : 0);
        var i = 1;
        for (; i < length; i++) {
          args[i - 1] = arguments[i];
        }
        return (_canvas = this._canvas).toDataURL.apply(_canvas, [type].concat(args));
    }
  }, SignaturePad.prototype.on = function() {
    this._handleMouseEvents();
    this._handleTouchEvents();
  }, SignaturePad.prototype.off = function() {
    this._canvas.removeEventListener("mousedown", this._handleMouseDown);
    this._canvas.removeEventListener("mousemove", this._handleMouseMove);
    document.removeEventListener("mouseup", this._handleMouseUp);
    this._canvas.removeEventListener("touchstart", this._handleTouchStart);
    this._canvas.removeEventListener("touchmove", this._handleTouchMove);
    this._canvas.removeEventListener("touchend", this._handleTouchEnd);
  }, SignaturePad.prototype.isEmpty = function() {
    return this._isEmpty;
  }, SignaturePad.prototype._strokeBegin = function(event) {
    this._data.push([]);
    this._reset();
    this._strokeUpdate(event);
    if ("function" == typeof this.onBegin) {
      this.onBegin(event);
    }
  }, SignaturePad.prototype._strokeUpdate = function(event) {
    var x = event.clientX;
    var y = event.clientY;
    var point = this._createPoint(x, y);
    var first = this._data[this._data.length - 1];
    var node = first && first[first.length - 1];
    var parentNode = node && point.distanceTo(node) < this.minDistance;
    if (!node || !parentNode) {
      var _addPoint = this._addPoint(point);
      var curve = _addPoint.curve;
      var widths = _addPoint.widths;
      if (curve && widths) {
        this._drawCurve(curve, widths.start, widths.end);
      }
      this._data[this._data.length - 1].push({
        x : point.x,
        y : point.y,
        time : point.time,
        color : this.penColor
      });
    }
  }, SignaturePad.prototype._strokeEnd = function(event) {
    var canDrawCurve = this.points.length > 2;
    var point = this.points[0];
    if (!canDrawCurve && point && this._drawDot(point), point) {
      var result = this._data[this._data.length - 1];
      var existsGlobal = result[result.length - 1];
      if (!point.equals(existsGlobal)) {
        result.push({
          x : point.x,
          y : point.y,
          time : point.time,
          color : this.penColor
        });
      }
    }
    if ("function" == typeof this.onEnd) {
      this.onEnd(event);
    }
  }, SignaturePad.prototype._handleMouseEvents = function() {
    this._mouseButtonDown = false;
    this._canvas.addEventListener("mousedown", this._handleMouseDown);
    this._canvas.addEventListener("mousemove", this._handleMouseMove);
    document.addEventListener("mouseup", this._handleMouseUp);
  }, SignaturePad.prototype._handleTouchEvents = function() {
    this._canvas.style.msTouchAction = "none";
    this._canvas.style.touchAction = "none";
    this._canvas.addEventListener("touchstart", this._handleTouchStart);
    this._canvas.addEventListener("touchmove", this._handleTouchMove);
    this._canvas.addEventListener("touchend", this._handleTouchEnd);
  }, SignaturePad.prototype._reset = function() {
    this.points = [];
    this._lastVelocity = 0;
    this._lastWidth = (this.minWidth + this.maxWidth) / 2;
    this._ctx.fillStyle = this.penColor;
  }, SignaturePad.prototype._createPoint = function(x, y, time) {
    var canvasBound = this._canvas.getBoundingClientRect();
    return new Point(x - canvasBound.left, y - canvasBound.top, time || (new Date).getTime());
  }, SignaturePad.prototype._addPoint = function(point) {
    var points = this.points;
    var tmp = void 0;
    if (points.push(point), points.length > 2) {
      if (3 === points.length) {
        points.unshift(points[0]);
      }
      tmp = this._calculateCurveControlPoints(points[0], points[1], points[2]);
      var c2 = tmp.c2;
      tmp = this._calculateCurveControlPoints(points[1], points[2], points[3]);
      var c3 = tmp.c1;
      var curve = new Bezier(points[1], c2, c3, points[2]);
      var widths = this._calculateCurveWidths(curve);
      return points.shift(), {
        curve : curve,
        widths : widths
      };
    }
    return {};
  }, SignaturePad.prototype._calculateCurveControlPoints = function(s1, s2, s3) {
    var lightI = s1.x - s2.x;
    var lightJ = s1.y - s2.y;
    var v_next_x = s2.x - s3.x;
    var v_next_y = s2.y - s3.y;
    var p = {
      x : (s1.x + s2.x) / 2,
      y : (s1.y + s2.y) / 2
    };
    var c = {
      x : (s2.x + s3.x) / 2,
      y : (s2.y + s3.y) / 2
    };
    var respondents = Math.sqrt(lightI * lightI + lightJ * lightJ);
    var emptyStroke = Math.sqrt(v_next_x * v_next_x + v_next_y * v_next_y);
    var r = p.x - c.x;
    var h = p.y - c.y;
    var factor = emptyStroke / (respondents + emptyStroke);
    var il1 = {
      x : c.x + r * factor,
      y : c.y + h * factor
    };
    var x = s2.x - il1.x;
    var offset = s2.y - il1.y;
    return {
      c1 : new Point(p.x + x, p.y + offset),
      c2 : new Point(c.x + x, c.y + offset)
    };
  }, SignaturePad.prototype._calculateCurveWidths = function(curve) {
    var startPoint = curve.startPoint;
    var endPoint = curve.endPoint;
    var widths = {
      start : null,
      end : null
    };
    var velocity = this.velocityFilterWeight * endPoint.velocityFrom(startPoint) + (1 - this.velocityFilterWeight) * this._lastVelocity;
    var newWidth = this._strokeWidth(velocity);
    return widths.start = this._lastWidth, widths.end = newWidth, this._lastVelocity = velocity, this._lastWidth = newWidth, widths;
  }, SignaturePad.prototype._strokeWidth = function(velocity) {
    return Math.max(this.maxWidth / (velocity + 1), this.minWidth);
  }, SignaturePad.prototype._drawPoint = function(x, y, size) {
    var ctx = this._ctx;
    ctx.moveTo(x, y);
    ctx.arc(x, y, size, 0, 2 * Math.PI, false);
    this._isEmpty = false;
  }, SignaturePad.prototype._drawCurve = function(curve, startWidth, endWidth) {
    var ctx = this._ctx;
    var widthDelta = endWidth - startWidth;
    var gradLength = Math.floor(curve.length());
    ctx.beginPath();
    var magnitude = 0;
    for (; magnitude < gradLength; magnitude = magnitude + 1) {
      var t = magnitude / gradLength;
      var tt = t * t;
      var ttt = tt * t;
      var u = 1 - t;
      var uu = u * u;
      var uuu = uu * u;
      var x = uuu * curve.startPoint.x;
      x = x + 3 * uu * t * curve.control1.x;
      x = x + 3 * u * tt * curve.control2.x;
      x = x + ttt * curve.endPoint.x;
      var y = uuu * curve.startPoint.y;
      y = y + 3 * uu * t * curve.control1.y;
      y = y + 3 * u * tt * curve.control2.y;
      y = y + ttt * curve.endPoint.y;
      var width = startWidth + ttt * widthDelta;
      this._drawPoint(x, y, width);
    }
    ctx.closePath();
    ctx.fill();
  }, SignaturePad.prototype._drawDot = function(point) {
    var ctx = this._ctx;
    var dotSize = "function" == typeof this.dotSize ? this.dotSize() : this.dotSize;
    ctx.beginPath();
    this._drawPoint(point.x, point.y, dotSize);
    ctx.closePath();
    ctx.fill();
  }, SignaturePad.prototype._fromData = function(pointGroups, drawCurve, drawDot) {
    var i = 0;
    for (; i < pointGroups.length; i = i + 1) {
      var eq = pointGroups[i];
      if (eq.length > 1) {
        var i = 0;
        for (; i < eq.length; i = i + 1) {
          var e = eq[i];
          var point = new Point(e.x, e.y, e.time);
          var colorString = e.color;
          if (0 === i) {
            this.penColor = colorString;
            this._reset();
            this._addPoint(point);
          } else {
            if (i !== eq.length - 1) {
              var _addPoint = this._addPoint(point);
              var curve = _addPoint.curve;
              var widths = _addPoint.widths;
              if (curve && widths) {
                drawCurve(curve, widths, colorString);
              }
            }
          }
        }
      } else {
        this._reset();
        drawDot(eq[0]);
      }
    }
  }, SignaturePad.prototype._toSVG = function() {
    var o = this;
    var pointGroups = this._data;
    var canvas = this._canvas;
    var d = Math.max(window.devicePixelRatio || 1, 1);
    var vy = canvas.width / d;
    var along1 = canvas.height / d;
    var node = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    node.setAttributeNS(null, "width", canvas.width);
    node.setAttributeNS(null, "height", canvas.height);
    this._fromData(pointGroups, function(curve, persistedSelection, i) {
      var path = document.createElement("path");
      if (!(isNaN(curve.control1.x) || isNaN(curve.control1.y) || isNaN(curve.control2.x) || isNaN(curve.control2.y))) {
        var TEXT_SIZE_CONTAINER_ID = "M " + curve.startPoint.x.toFixed(3) + "," + curve.startPoint.y.toFixed(3) + " C " + curve.control1.x.toFixed(3) + "," + curve.control1.y.toFixed(3) + " " + curve.control2.x.toFixed(3) + "," + curve.control2.y.toFixed(3) + " " + curve.endPoint.x.toFixed(3) + "," + curve.endPoint.y.toFixed(3);
        path.setAttribute("d", TEXT_SIZE_CONTAINER_ID);
        path.setAttribute("stroke-width", (2.25 * persistedSelection.end).toFixed(3));
        path.setAttribute("stroke", i);
        path.setAttribute("fill", "none");
        path.setAttribute("stroke-linecap", "round");
        node.appendChild(path);
      }
    }, function(attrs) {
      var element = document.createElement("circle");
      var TEXT_SIZE_CONTAINER_ID = "function" == typeof o.dotSize ? o.dotSize() : o.dotSize;
      element.setAttribute("r", TEXT_SIZE_CONTAINER_ID);
      element.setAttribute("cx", attrs.x);
      element.setAttribute("cy", attrs.y);
      element.setAttribute("fill", attrs.color);
      node.appendChild(element);
    });
    var map = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 ' + vy + " " + along1 + '" width="' + vy + '" height="' + along1 + '">';
    var result = node.innerHTML;
    if (void 0 === result) {
      var dummy = document.createElement("dummy");
      var childNodes = node.childNodes;
      dummy.innerHTML = "";
      var i = 0;
      for (; i < childNodes.length; i = i + 1) {
        dummy.appendChild(childNodes[i].cloneNode(true));
      }
      result = dummy.innerHTML;
    }
    var paddedPartNum = map + result + "</svg>";
    return "data:image/svg+xml;base64," + btoa(paddedPartNum);
  }, SignaturePad.prototype.fromData = function(pointGroups) {
    var _this3 = this;
    this.clear();
    this._fromData(pointGroups, function(curve, widths) {
      return _this3._drawCurve(curve, widths.start, widths.end);
    }, function(rawPoint) {
      return _this3._drawDot(rawPoint);
    });
    this._data = pointGroups;
  }, SignaturePad.prototype.toData = function() {
    return this._data;
  }, SignaturePad;
});
