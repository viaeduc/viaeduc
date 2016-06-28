/*!
 * Masonry PACKAGED v3.1.2
 * Cascading grid layout library
 * http://masonry.desandro.com
 * MIT License
 * by David DeSandro
 */
(function (t) {
    "use strict";

    function e(t) {
        if (t) {
            if ("string" == typeof n[t]) return t;
            t = t.charAt(0).toUpperCase() + t.slice(1);
            for (var e, o = 0, r = i.length; r > o; o++)
                if (e = i[o] + t, "string" == typeof n[e]) return e
        }
    }
    var i = "Webkit Moz ms Ms O".split(" "),
        n = document.documentElement.style;
    "function" == typeof define && define.amd ? define(function () {
        return e
    }) : t.getStyleProperty = e
})(window),
function (t) {
    "use strict";

    function e(t) {
        var e = parseFloat(t),
            i = -1 === t.indexOf("%") && !isNaN(e);
        return i && e
    }

    function i() {
        for (var t = {
            width: 0,
            height: 0,
            innerWidth: 0,
            innerHeight: 0,
            outerWidth: 0,
            outerHeight: 0
        }, e = 0, i = s.length; i > e; e++) {
            var n = s[e];
            t[n] = 0
        }
        return t
    }

    function n(t) {
        function n(t) {
            if ("string" == typeof t && (t = document.querySelector(t)), t && "object" == typeof t && t.nodeType) {
                var n = r(t);
                if ("none" === n.display) return i();
                var h = {};
                h.width = t.offsetWidth, h.height = t.offsetHeight;
                for (var p = h.isBorderBox = !(!a || !n[a] || "border-box" !== n[a]), u = 0, f = s.length; f > u; u++) {
                    var d = s[u],
                        c = n[d],
                        l = parseFloat(c);
                    h[d] = isNaN(l) ? 0 : l
                }
                var m = h.paddingLeft + h.paddingRight,
                    y = h.paddingTop + h.paddingBottom,
                    g = h.marginLeft + h.marginRight,
                    v = h.marginTop + h.marginBottom,
                    _ = h.borderLeftWidth + h.borderRightWidth,
                    E = h.borderTopWidth + h.borderBottomWidth,
                    b = p && o,
                    L = e(n.width);
                L !== !1 && (h.width = L + (b ? 0 : m + _));
                var T = e(n.height);
                return T !== !1 && (h.height = T + (b ? 0 : y + E)), h.innerWidth = h.width - (m + _), h.innerHeight = h.height - (y + E), h.outerWidth = h.width + g, h.outerHeight = h.height + v, h
            }
        }
        var o, a = t("boxSizing");
        return function () {
            if (a) {
                var t = document.createElement("div");
                t.style.width = "200px", t.style.padding = "1px 2px 3px 4px", t.style.borderStyle = "solid", t.style.borderWidth = "1px 2px 3px 4px", t.style[a] = "border-box";
                var i = document.body || document.documentElement;
                i.appendChild(t);
                var n = r(t);
                o = 200 === e(n.width), i.removeChild(t)
            }
        }(), n
    }
    var o = document.defaultView,
        r = o && o.getComputedStyle ? function (t) {
            return o.getComputedStyle(t, null)
        } : function (t) {
            return t.currentStyle
        }, s = ["paddingLeft", "paddingRight", "paddingTop", "paddingBottom", "marginLeft", "marginRight", "marginTop", "marginBottom", "borderLeftWidth", "borderRightWidth", "borderTopWidth", "borderBottomWidth"];
    "function" == typeof define && define.amd ? define(["get-style-property/get-style-property"], n) : t.getSize = n(t.getStyleProperty)
}(window),
function (t) {
    "use strict";
    var e = document.documentElement,
        i = function () {};
    e.addEventListener ? i = function (t, e, i) {
        t.addEventListener(e, i, !1)
    } : e.attachEvent && (i = function (e, i, n) {
        e[i + n] = n.handleEvent ? function () {
            var e = t.event;
            e.target = e.target || e.srcElement, n.handleEvent.call(n, e)
        } : function () {
            var i = t.event;
            i.target = i.target || i.srcElement, n.call(e, i)
        }, e.attachEvent("on" + i, e[i + n])
    });
    var n = function () {};
    e.removeEventListener ? n = function (t, e, i) {
        t.removeEventListener(e, i, !1)
    } : e.detachEvent && (n = function (t, e, i) {
        t.detachEvent("on" + e, t[e + i]);
        try {
            delete t[e + i]
        } catch (n) {
            t[e + i] = void 0
        }
    });
    var o = {
        bind: i,
        unbind: n
    };
    "function" == typeof define && define.amd ? define(o) : t.eventie = o
}(this),
function (t) {
    "use strict";

    function e(t) {
        "function" == typeof t && (e.isReady ? t() : r.push(t))
    }

    function i(t) {
        var i = "readystatechange" === t.type && "complete" !== o.readyState;
        if (!e.isReady && !i) {
            e.isReady = !0;
            for (var n = 0, s = r.length; s > n; n++) {
                var a = r[n];
                a()
            }
        }
    }

    function n(n) {
        return n.bind(o, "DOMContentLoaded", i), n.bind(o, "readystatechange", i), n.bind(t, "load", i), e
    }
    var o = t.document,
        r = [];
    e.isReady = !1, "function" == typeof define && define.amd ? (e.isReady = "function" == typeof requirejs, define(["eventie/eventie"], n)) : t.docReady = n(t.eventie)
}(this),
function () {
    "use strict";

    function t() {}

    function e(t, e) {
        for (var i = t.length; i--;)
            if (t[i].listener === e) return i;
        return -1
    }

    function i(t) {
        return function () {
            return this[t].apply(this, arguments)
        }
    }
    var n = t.prototype;
    n.getListeners = function (t) {
        var e, i, n = this._getEvents();
        if ("object" == typeof t) {
            e = {};
            for (i in n) n.hasOwnProperty(i) && t.test(i) && (e[i] = n[i])
        } else e = n[t] || (n[t] = []);
        return e
    }, n.flattenListeners = function (t) {
        var e, i = [];
        for (e = 0; t.length > e; e += 1) i.push(t[e].listener);
        return i
    }, n.getListenersAsObject = function (t) {
        var e, i = this.getListeners(t);
        return i instanceof Array && (e = {}, e[t] = i), e || i
    }, n.addListener = function (t, i) {
        var n, o = this.getListenersAsObject(t),
            r = "object" == typeof i;
        for (n in o) o.hasOwnProperty(n) && -1 === e(o[n], i) && o[n].push(r ? i : {
            listener: i,
            once: !1
        });
        return this
    }, n.on = i("addListener"), n.addOnceListener = function (t, e) {
        return this.addListener(t, {
            listener: e,
            once: !0
        })
    }, n.once = i("addOnceListener"), n.defineEvent = function (t) {
        return this.getListeners(t), this
    }, n.defineEvents = function (t) {
        for (var e = 0; t.length > e; e += 1) this.defineEvent(t[e]);
        return this
    }, n.removeListener = function (t, i) {
        var n, o, r = this.getListenersAsObject(t);
        for (o in r) r.hasOwnProperty(o) && (n = e(r[o], i), -1 !== n && r[o].splice(n, 1));
        return this
    }, n.off = i("removeListener"), n.addListeners = function (t, e) {
        return this.manipulateListeners(!1, t, e)
    }, n.removeListeners = function (t, e) {
        return this.manipulateListeners(!0, t, e)
    }, n.manipulateListeners = function (t, e, i) {
        var n, o, r = t ? this.removeListener : this.addListener,
            s = t ? this.removeListeners : this.addListeners;
        if ("object" != typeof e || e instanceof RegExp)
            for (n = i.length; n--;) r.call(this, e, i[n]);
        else
            for (n in e) e.hasOwnProperty(n) && (o = e[n]) && ("function" == typeof o ? r.call(this, n, o) : s.call(this, n, o));
        return this
    }, n.removeEvent = function (t) {
        var e, i = typeof t,
            n = this._getEvents();
        if ("string" === i) delete n[t];
        else if ("object" === i)
            for (e in n) n.hasOwnProperty(e) && t.test(e) && delete n[e];
        else delete this._events;
        return this
    }, n.emitEvent = function (t, e) {
        var i, n, o, r, s = this.getListenersAsObject(t);
        for (o in s)
            if (s.hasOwnProperty(o))
                for (n = s[o].length; n--;) i = s[o][n], i.once === !0 && this.removeListener(t, i.listener), r = i.listener.apply(this, e || []), r === this._getOnceReturnValue() && this.removeListener(t, i.listener);
        return this
    }, n.trigger = i("emitEvent"), n.emit = function (t) {
        var e = Array.prototype.slice.call(arguments, 1);
        return this.emitEvent(t, e)
    }, n.setOnceReturnValue = function (t) {
        return this._onceReturnValue = t, this
    }, n._getOnceReturnValue = function () {
        return this.hasOwnProperty("_onceReturnValue") ? this._onceReturnValue : !0
    }, n._getEvents = function () {
        return this._events || (this._events = {})
    }, "function" == typeof define && define.amd ? define(function () {
        return t
    }) : "object" == typeof module && module.exports ? module.exports = t : this.EventEmitter = t
}.call(this),
function (t) {
    "use strict";

    function e() {}

    function i(t) {
        function i(e) {
            e.prototype.option || (e.prototype.option = function (e) {
                t.isPlainObject(e) && (this.options = t.extend(!0, this.options, e))
            })
        }

        function o(e, i) {
            t.fn[e] = function (o) {
                if ("string" == typeof o) {
                    for (var s = n.call(arguments, 1), a = 0, h = this.length; h > a; a++) {
                        var p = this[a],
                            u = t.data(p, e);
                        if (u)
                            if (t.isFunction(u[o]) && "_" !== o.charAt(0)) {
                                var f = u[o].apply(u, s);
                                if (void 0 !== f) return f
                            } else r("no such method '" + o + "' for " + e + " instance");
                            else r("cannot call methods on " + e + " prior to initialization; " + "attempted to call '" + o + "'")
                    }
                    return this
                }
                return this.each(function () {
                    var n = t.data(this, e);
                    n ? (n.option(o), n._init()) : (n = new i(this, o), t.data(this, e, n))
                })
            }
        }
        if (t) {
            var r = "undefined" == typeof console ? e : function (t) {
                    console.error(t)
                };
            t.bridget = function (t, e) {
                i(e), o(t, e)
            }
        }
    }
    var n = Array.prototype.slice;
    "function" == typeof define && define.amd ? define(["jquery"], i) : i(t.jQuery)
}(window),
function (t, e) {
    "use strict";

    function i(t, e) {
        return t[a](e)
    }

    function n(t) {
        if (!t.parentNode) {
            var e = document.createDocumentFragment();
            e.appendChild(t)
        }
    }

    function o(t, e) {
        n(t);
        for (var i = t.parentNode.querySelectorAll(e), o = 0, r = i.length; r > o; o++)
            if (i[o] === t) return !0;
        return !1
    }

    function r(t, e) {
        return n(t), i(t, e)
    }
    var s, a = function () {
            if (e.matchesSelector) return "matchesSelector";
            for (var t = ["webkit", "moz", "ms", "o"], i = 0, n = t.length; n > i; i++) {
                var o = t[i],
                    r = o + "MatchesSelector";
                if (e[r]) return r
            }
        }();
    if (a) {
        var h = document.createElement("div"),
            p = i(h, "div");
        s = p ? i : r
    } else s = o;
    "function" == typeof define && define.amd ? define(function () {
        return s
    }) : window.matchesSelector = s
}(this, Element.prototype),
function (t) {
    "use strict";

    function e(t, e) {
        for (var i in e) t[i] = e[i];
        return t
    }

    function i(t, i, n) {
        function r(t, e) {
            t && (this.element = t, this.layout = e, this.position = {
                x: 0,
                y: 0
            }, this._create())
        }
        var s = n("transition"),
            a = n("transform"),
            h = s && a,
            p = !! n("perspective"),
            u = {
                WebkitTransition: "webkitTransitionEnd",
                MozTransition: "transitionend",
                OTransition: "otransitionend",
                transition: "transitionend"
            }[s],
            f = ["transform", "transition", "transitionDuration", "transitionProperty"],
            d = function () {
                for (var t = {}, e = 0, i = f.length; i > e; e++) {
                    var o = f[e],
                        r = n(o);
                    r && r !== o && (t[o] = r)
                }
                return t
            }();
        e(r.prototype, t.prototype), r.prototype._create = function () {
            this.css({
                position: "absolute"
            })
        }, r.prototype.handleEvent = function (t) {
            var e = "on" + t.type;
            this[e] && this[e](t)
        }, r.prototype.getSize = function () {
            this.size = i(this.element)
        }, r.prototype.css = function (t) {
            var e = this.element.style;
            for (var i in t) {
                var n = d[i] || i;
                e[n] = t[i]
            }
        }, r.prototype.getPosition = function () {
            var t = o(this.element),
                e = this.layout.options,
                i = e.isOriginLeft,
                n = e.isOriginTop,
                r = parseInt(t[i ? "left" : "right"], 10),
                s = parseInt(t[n ? "top" : "bottom"], 10);
            r = isNaN(r) ? 0 : r, s = isNaN(s) ? 0 : s;
            var a = this.layout.size;
            r -= i ? a.paddingLeft : a.paddingRight, s -= n ? a.paddingTop : a.paddingBottom, this.position.x = r, this.position.y = s
        }, r.prototype.layoutPosition = function () {
            var t = this.layout.size,
                e = this.layout.options,
                i = {};
            e.isOriginLeft ? (i.left = this.position.x + t.paddingLeft + "px", i.right = "") : (i.right = this.position.x + t.paddingRight + "px", i.left = ""), e.isOriginTop ? (i.top = this.position.y + t.paddingTop + "px", i.bottom = "") : (i.bottom = this.position.y + t.paddingBottom + "px", i.top = ""), this.css(i), this.emitEvent("layout", [this])
        };
        var c = p ? function (t, e) {
                return "translate3d(" + t + "px, " + e + "px, 0)"
            } : function (t, e) {
                return "translate(" + t + "px, " + e + "px)"
            };
        r.prototype._transitionTo = function (t, e) {
            this.getPosition();
            var i = this.position.x,
                n = this.position.y,
                o = parseInt(t, 10),
                r = parseInt(e, 10),
                s = o === this.position.x && r === this.position.y;
            if (this.setPosition(t, e), s && !this.isTransitioning) return this.layoutPosition(), void 0;
            var a = t - i,
                h = e - n,
                p = {}, u = this.layout.options;
            a = u.isOriginLeft ? a : -a, h = u.isOriginTop ? h : -h, p.transform = c(a, h), this.transition({
                to: p,
                onTransitionEnd: this.layoutPosition,
                isCleaning: !0
            })
        }, r.prototype.goTo = function (t, e) {
            this.setPosition(t, e), this.layoutPosition()
        }, r.prototype.moveTo = h ? r.prototype._transitionTo : r.prototype.goTo, r.prototype.setPosition = function (t, e) {
            this.position.x = parseInt(t, 10), this.position.y = parseInt(e, 10)
        }, r.prototype._nonTransition = function (t) {
            this.css(t.to), t.isCleaning && this._removeStyles(t.to), t.onTransitionEnd && t.onTransitionEnd.call(this)
        }, r.prototype._transition = function (t) {
            var e = this.layout.options.transitionDuration;
            if (!parseFloat(e)) return this._nonTransition(t), void 0;
            var i = t.to,
                n = [];
            for (var o in i) n.push(o);
            var r = {};
            if (r.transitionProperty = n.join(","), r.transitionDuration = e, this.element.addEventListener(u, this, !1), (t.isCleaning || t.onTransitionEnd) && (this.onTransitionEnd = function () {
                t.isCleaning && this._removeStyles(i), t.onTransitionEnd && t.onTransitionEnd.call(this)
            }), t.from) {
                this.css(t.from);
                var s = this.element.offsetHeight;
                s = null
            }
            this.css(r), this.css(i), this.isTransitioning = !0
        }, r.prototype.transition = r.prototype[s ? "_transition" : "_nonTransition"], r.prototype.onwebkitTransitionEnd = function (t) {
            this.ontransitionend(t)
        }, r.prototype.onotransitionend = function (t) {
            this.ontransitionend(t)
        }, r.prototype.ontransitionend = function (t) {
            t.target === this.element && (this.removeTransitionStyles(), this.element.removeEventListener(u, this, !1), this.isTransitioning = !1, this.onTransitionEnd && (this.onTransitionEnd.call(this), delete this.onTransitionEnd), this.emitEvent("transitionEnd", [this]))
        }, r.prototype._removeStyles = function (t) {
            var e = {};
            for (var i in t) e[i] = "";
            this.css(e)
        };
        var l = {
            transitionProperty: "",
            transitionDuration: ""
        };
        return r.prototype.removeTransitionStyles = function () {
            this.css(l)
        }, r.prototype.removeElem = function () {
            this.element.parentNode.removeChild(this.element), this.emitEvent("remove", [this])
        }, r.prototype.remove = function () {
            if (!s || !parseFloat(this.layout.options.transitionDuration)) return this.removeElem(), void 0;
            var t = this;
            this.on("transitionEnd", function () {
                return t.removeElem(), !0
            }), this.hide()
        }, r.prototype.reveal = function () {
            delete this.isHidden, this.css({
                display: ""
            });
            var t = this.layout.options;
            this.transition({
                from: t.hiddenStyle,
                to: t.visibleStyle,
                isCleaning: !0
            })
        }, r.prototype.hide = function () {
            this.isHidden = !0, this.css({
                display: ""
            });
            var t = this.layout.options;
            this.transition({
                from: t.visibleStyle,
                to: t.hiddenStyle,
                isCleaning: !0,
                onTransitionEnd: function () {
                    this.css({
                        display: "none"
                    })
                }
            })
        }, r.prototype.destroy = function () {
            this.css({
                position: "",
                left: "",
                right: "",
                top: "",
                bottom: "",
                transition: "",
                transform: ""
            })
        }, r
    }
    var n = document.defaultView,
        o = n && n.getComputedStyle ? function (t) {
            return n.getComputedStyle(t, null)
        } : function (t) {
            return t.currentStyle
        };
    "function" == typeof define && define.amd ? define(["eventEmitter/EventEmitter", "get-size/get-size", "get-style-property/get-style-property"], i) : (t.Outlayer = {}, t.Outlayer.Item = i(t.EventEmitter, t.getSize, t.getStyleProperty))
}(window),
function (t) {
    "use strict";

    function e(t, e) {
        for (var i in e) t[i] = e[i];
        return t
    }

    function i(t) {
        return "[object Array]" === u.call(t)
    }

    function n(t) {
        var e = [];
        if (i(t)) e = t;
        else if (t && "number" == typeof t.length)
            for (var n = 0, o = t.length; o > n; n++) e.push(t[n]);
        else e.push(t);
        return e
    }

    function o(t) {
        return t.replace(/(.)([A-Z])/g, function (t, e, i) {
            return e + "-" + i
        }).toLowerCase()
    }

    function r(i, r, u, c, l, m) {
        function y(t, i) {
            if ("string" == typeof t && (t = s.querySelector(t)), !t || !f(t)) return a && a.error("Bad " + this.settings.namespace + " element: " + t), void 0;
            this.element = t, this.options = e({}, this.options), this.option(i);
            var n = ++v;
            this.element.outlayerGUID = n, _[n] = this, this._create(), this.options.isInitLayout && this.layout()
        }

        function g(t, i) {
            t.prototype[i] = e({}, y.prototype[i])
        }
        var v = 0,
            _ = {};
        return y.prototype.settings = {
            namespace: "outlayer",
            item: m
        }, y.prototype.options = {
            containerStyle: {
                position: "relative"
            },
            isInitLayout: !0,
            isOriginLeft: !0,
            isOriginTop: !0,
            isResizeBound: !0,
            transitionDuration: "0.4s",
            hiddenStyle: {
                opacity: 0,
                transform: "scale(0.001)"
            },
            visibleStyle: {
                opacity: 1,
                transform: "scale(1)"
            }
        }, e(y.prototype, u.prototype), y.prototype.option = function (t) {
            e(this.options, t)
        }, y.prototype._create = function () {
            this.reloadItems(), this.stamps = [], this.stamp(this.options.stamp), e(this.element.style, this.options.containerStyle), this.options.isResizeBound && this.bindResize()
        }, y.prototype.reloadItems = function () {
            this.items = this._getItems(this.element.children)
        }, y.prototype._getItems = function (t) {
            for (var e = this._filterFindItemElements(t), i = this.settings.item, n = [], o = 0, r = e.length; r > o; o++) {
                var s = e[o],
                    a = new i(s, this, this.options.itemOptions);
                n.push(a)
            }
            return n
        }, y.prototype._filterFindItemElements = function (t) {
            t = n(t);
            for (var e = this.options.itemSelector, i = [], o = 0, r = t.length; r > o; o++) {
                var s = t[o];
                if (f(s))
                    if (e) {
                        l(s, e) && i.push(s);
                        for (var a = s.querySelectorAll(e), h = 0, p = a.length; p > h; h++) i.push(a[h])
                    } else i.push(s)
            }
            return i
        }, y.prototype.getItemElements = function () {
            for (var t = [], e = 0, i = this.items.length; i > e; e++) t.push(this.items[e].element);
            return t
        }, y.prototype.layout = function () {
            this._resetLayout(), this._manageStamps();
            var t = void 0 !== this.options.isLayoutInstant ? this.options.isLayoutInstant : !this._isLayoutInited;
            this.layoutItems(this.items, t), this._isLayoutInited = !0
        }, y.prototype._init = y.prototype.layout, y.prototype._resetLayout = function () {
            this.getSize()
        }, y.prototype.getSize = function () {
            this.size = c(this.element)
        }, y.prototype._getMeasurement = function (t, e) {
            var i, n = this.options[t];
            n ? ("string" == typeof n ? i = this.element.querySelector(n) : f(n) && (i = n), this[t] = i ? c(i)[e] : n) : this[t] = 0
        }, y.prototype.layoutItems = function (t, e) {
            t = this._getItemsForLayout(t), this._layoutItems(t, e), this._postLayout()
        }, y.prototype._getItemsForLayout = function (t) {
            for (var e = [], i = 0, n = t.length; n > i; i++) {
                var o = t[i];
                o.isIgnored || e.push(o)
            }
            return e
        }, y.prototype._layoutItems = function (t, e) {
            if (!t || !t.length) return this.emitEvent("layoutComplete", [this, t]), void 0;
            this._itemsOn(t, "layout", function () {
                this.emitEvent("layoutComplete", [this, t])
            });
            for (var i = [], n = 0, o = t.length; o > n; n++) {
                var r = t[n],
                    s = this._getItemLayoutPosition(r);
                s.item = r, s.isInstant = e, i.push(s)
            }
            this._processLayoutQueue(i)
        }, y.prototype._getItemLayoutPosition = function () {
            return {
                x: 0,
                y: 0
            }
        }, y.prototype._processLayoutQueue = function (t) {
            for (var e = 0, i = t.length; i > e; e++) {
                var n = t[e];
                this._positionItem(n.item, n.x, n.y, n.isInstant)
            }
        }, y.prototype._positionItem = function (t, e, i, n) {
            n ? t.goTo(e, i) : t.moveTo(e, i)
        }, y.prototype._postLayout = function () {
            var t = this._getContainerSize();
            t && (this._setContainerMeasure(t.width, !0), this._setContainerMeasure(t.height, !1))
        }, y.prototype._getContainerSize = p, y.prototype._setContainerMeasure = function (t, e) {
            if (void 0 !== t) {
                var i = this.size;
                i.isBorderBox && (t += e ? i.paddingLeft + i.paddingRight + i.borderLeftWidth + i.borderRightWidth : i.paddingBottom + i.paddingTop + i.borderTopWidth + i.borderBottomWidth), t = Math.max(t, 0), this.element.style[e ? "width" : "height"] = t + "px"
            }
        }, y.prototype._itemsOn = function (t, e, i) {
            function n() {
                return o++, o === r && i.call(s), !0
            }
            for (var o = 0, r = t.length, s = this, a = 0, h = t.length; h > a; a++) {
                var p = t[a];
                p.on(e, n)
            }
        }, y.prototype.ignore = function (t) {
            var e = this.getItem(t);
            e && (e.isIgnored = !0)
        }, y.prototype.unignore = function (t) {
            var e = this.getItem(t);
            e && delete e.isIgnored
        }, y.prototype.stamp = function (t) {
            if (t = this._find(t)) {
                this.stamps = this.stamps.concat(t);
                for (var e = 0, i = t.length; i > e; e++) {
                    var n = t[e];
                    this.ignore(n)
                }
            }
        }, y.prototype.unstamp = function (t) {
            if (t = this._find(t))
                for (var e = 0, i = t.length; i > e; e++) {
                    var n = t[e],
                        o = d(this.stamps, n); - 1 !== o && this.stamps.splice(o, 1), this.unignore(n)
                }
        }, y.prototype._find = function (t) {
            return t ? ("string" == typeof t && (t = this.element.querySelectorAll(t)), t = n(t)) : void 0
        }, y.prototype._manageStamps = function () {
            if (this.stamps && this.stamps.length) {
                this._getBoundingRect();
                for (var t = 0, e = this.stamps.length; e > t; t++) {
                    var i = this.stamps[t];
                    this._manageStamp(i)
                }
            }
        }, y.prototype._getBoundingRect = function () {
            var t = this.element.getBoundingClientRect(),
                e = this.size;
            this._boundingRect = {
                left: t.left + e.paddingLeft + e.borderLeftWidth,
                top: t.top + e.paddingTop + e.borderTopWidth,
                right: t.right - (e.paddingRight + e.borderRightWidth),
                bottom: t.bottom - (e.paddingBottom + e.borderBottomWidth)
            }
        }, y.prototype._manageStamp = p, y.prototype._getElementOffset = function (t) {
            var e = t.getBoundingClientRect(),
                i = this._boundingRect,
                n = c(t),
                o = {
                    left: e.left - i.left - n.marginLeft,
                    top: e.top - i.top - n.marginTop,
                    right: i.right - e.right - n.marginRight,
                    bottom: i.bottom - e.bottom - n.marginBottom
                };
            return o
        }, y.prototype.handleEvent = function (t) {
            var e = "on" + t.type;
            this[e] && this[e](t)
        }, y.prototype.bindResize = function () {
            this.isResizeBound || (i.bind(t, "resize", this), this.isResizeBound = !0)
        }, y.prototype.unbindResize = function () {
            i.unbind(t, "resize", this), this.isResizeBound = !1
        }, y.prototype.onresize = function () {
            function t() {
                e.resize()
            }
            this.resizeTimeout && clearTimeout(this.resizeTimeout);
            var e = this;
            this.resizeTimeout = setTimeout(t, 100)
        }, y.prototype.resize = function () {
            var t = c(this.element),
                e = this.size && t;
            e && t.innerWidth === this.size.innerWidth || (this.layout(), delete this.resizeTimeout)
        }, y.prototype.addItems = function (t) {
            var e = this._getItems(t);
            if (e.length) return this.items = this.items.concat(e), e
        }, y.prototype.appended = function (t) {
            var e = this.addItems(t);
            e.length && (this.layoutItems(e, !0), this.reveal(e))
        }, y.prototype.prepended = function (t) {
            var e = this._getItems(t);
            if (e.length) {
                var i = this.items.slice(0);
                this.items = e.concat(i), this._resetLayout(), this.layoutItems(e, !0), this.reveal(e), this.layoutItems(i)
            }
        }, y.prototype.reveal = function (t) {
            if (t && t.length)
                for (var e = 0, i = t.length; i > e; e++) {
                    var n = t[e];
                    n.reveal()
                }
        }, y.prototype.hide = function (t) {
            if (t && t.length)
                for (var e = 0, i = t.length; i > e; e++) {
                    var n = t[e];
                    n.hide()
                }
        }, y.prototype.getItem = function (t) {
            for (var e = 0, i = this.items.length; i > e; e++) {
                var n = this.items[e];
                if (n.element === t) return n
            }
        }, y.prototype.getItems = function (t) {
            if (t && t.length) {
                for (var e = [], i = 0, n = t.length; n > i; i++) {
                    var o = t[i],
                        r = this.getItem(o);
                    r && e.push(r)
                }
                return e
            }
        }, y.prototype.remove = function (t) {
            t = n(t);
            var e = this.getItems(t);
            if (e && e.length) {
                this._itemsOn(e, "remove", function () {
                    this.emitEvent("removeComplete", [this, e])
                });
                for (var i = 0, o = e.length; o > i; i++) {
                    var r = e[i];
                    r.remove();
                    var s = d(this.items, r);
                    this.items.splice(s, 1)
                }
            }
        }, y.prototype.destroy = function () {
            var t = this.element.style;
            t.height = "", t.position = "", t.width = "";
            for (var e = 0, i = this.items.length; i > e; e++) {
                var n = this.items[e];
                n.destroy()
            }
            this.unbindResize(), delete this.element.outlayerGUID, h && h.removeData(this.element, this.settings.namespace)
        }, y.data = function (t) {
            var e = t && t.outlayerGUID;
            return e && _[e]
        }, y.create = function (t, i) {
            function n() {
                y.apply(this, arguments)
            }
            return e(n.prototype, y.prototype), g(n, "options"), g(n, "settings"), e(n.prototype.options, i), n.prototype.settings.namespace = t, n.data = y.data, n.Item = function () {
                m.apply(this, arguments)
            }, n.Item.prototype = new m, n.prototype.settings.item = n.Item, r(function () {
                for (var e = o(t), i = s.querySelectorAll(".js-" + e), r = "data-" + e + "-options", p = 0, u = i.length; u > p; p++) {
                    var f, d = i[p],
                        c = d.getAttribute(r);
                    try {
                        f = c && JSON.parse(c)
                    } catch (l) {
                        a && a.error("Error parsing " + r + " on " + d.nodeName.toLowerCase() + (d.id ? "#" + d.id : "") + ": " + l);
                        continue
                    }
                    var m = new n(d, f);
                    h && h.data(d, t, m)
                }
            }), h && h.bridget && h.bridget(t, n), n
        }, y.Item = m, y
    }
    var s = t.document,
        a = t.console,
        h = t.jQuery,
        p = function () {}, u = Object.prototype.toString,
        f = "object" == typeof HTMLElement ? function (t) {
            return t instanceof HTMLElement
        } : function (t) {
            return t && "object" == typeof t && 1 === t.nodeType && "string" == typeof t.nodeName
        }, d = Array.prototype.indexOf ? function (t, e) {
            return t.indexOf(e)
        } : function (t, e) {
            for (var i = 0, n = t.length; n > i; i++)
                if (t[i] === e) return i;
            return -1
        };
    "function" == typeof define && define.amd ? define(["eventie/eventie", "doc-ready/doc-ready", "eventEmitter/EventEmitter", "get-size/get-size", "matches-selector/matches-selector", "./item"], r) : t.Outlayer = r(t.eventie, t.docReady, t.EventEmitter, t.getSize, t.matchesSelector, t.Outlayer.Item)
}(window),
function (t) {
    "use strict";

    function e(t, e) {
        var n = t.create("masonry");
        return n.prototype._resetLayout = function () {
            this.getSize(), this._getMeasurement("columnWidth", "outerWidth"), this._getMeasurement("gutter", "outerWidth"), this.measureColumns();
            var t = this.cols;
            for (this.colYs = []; t--;) this.colYs.push(0);
            this.maxY = 0
        }, n.prototype.measureColumns = function () {
            if (this.getContainerWidth(), !this.columnWidth) {
                var t = this.items[0],
                    i = t && t.element;
                this.columnWidth = i && e(i).outerWidth || this.containerWidth
            }
            this.columnWidth += this.gutter, this.cols = Math.ceil((this.containerWidth + this.gutter) / this.columnWidth), this.cols = Math.max(this.cols, 1)
        }, n.prototype.getContainerWidth = function () {
            var t = this.options.isFitWidth ? this.element.parentNode : this.element,
                i = e(t);
            this.containerWidth = i && i.innerWidth
        }, n.prototype._getItemLayoutPosition = function (t) {
            t.getSize();
            var e = Math.ceil(t.size.outerWidth / this.columnWidth);
            e = Math.min(e, this.cols);
            for (var n = this._getColGroup(e), o = Math.min.apply(Math, n), r = i(n, o), s = {
                    x: this.columnWidth * r,
                    y: o
                }, a = o + t.size.outerHeight, h = this.cols + 1 - n.length, p = 0; h > p; p++) this.colYs[r + p] = a;
            return s
        }, n.prototype._getColGroup = function (t) {
            if (2 > t) return this.colYs;
            for (var e = [], i = this.cols + 1 - t, n = 0; i > n; n++) {
                var o = this.colYs.slice(n, n + t);
                e[n] = Math.max.apply(Math, o)
            }
            return e
        }, n.prototype._manageStamp = function (t) {
            var i = e(t),
                n = this._getElementOffset(t),
                o = this.options.isOriginLeft ? n.left : n.right,
                r = o + i.outerWidth,
                s = Math.floor(o / this.columnWidth);
            s = Math.max(0, s);
            var a = Math.floor(r / this.columnWidth);
            a = Math.min(this.cols - 1, a);
            for (var h = (this.options.isOriginTop ? n.top : n.bottom) + i.outerHeight, p = s; a >= p; p++) this.colYs[p] = Math.max(h, this.colYs[p])
        }, n.prototype._getContainerSize = function () {
            this.maxY = Math.max.apply(Math, this.colYs);
            var t = {
                height: this.maxY
            };
            return this.options.isFitWidth && (t.width = this._getContainerFitWidth()), t
        }, n.prototype._getContainerFitWidth = function () {
            for (var t = 0, e = this.cols; --e && 0 === this.colYs[e];) t++;
            return (this.cols - t) * this.columnWidth - this.gutter
        }, n.prototype.resize = function () {
            var t = this.containerWidth;
            this.getContainerWidth(), t !== this.containerWidth && this.layout()
        }, n
    }
    var i = Array.prototype.indexOf ? function (t, e) {
            return t.indexOf(e)
        } : function (t, e) {
            for (var i = 0, n = t.length; n > i; i++) {
                var o = t[i];
                if (o === e) return i
            }
            return -1
        };
    "function" == typeof define && define.amd ? define(["outlayer/outlayer", "get-size/get-size"], e) : t.Masonry = e(t.Outlayer, t.getSize)
}(window);