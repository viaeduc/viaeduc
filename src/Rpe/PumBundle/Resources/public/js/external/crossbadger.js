// =========================================================================
// CROSSBADGER - Badger counter checker across pages - v0.0.1
// =========================================================================

/*
    localStorage example:
    - _xbdgr_t_2000_masterID = _string_
    - _xbdgr_t_2000_ttl = _timestamp_
    - _xbdgr_t_2000_timers = ['notifications', 'inbox']
    - _xbdgr_t_notifications = {
        'lastCheck': _timestamp_,
        'value': 0
    }
 */

;(function($, window, document) {
    'use strict';

    var Crossbadger = function(options) {
        if ('undefined' === typeof options) {
            if ('undefined' !== typeof window._xbadgr_options) {
                options = window._xbadgr_options;
            } else {
                options = {};
            }
        }

        // User defined options
        var b = $(document.body);
        // Check if request url is defined
        if ('undefined' === typeof options.badgeRequestUrl) {
            if ('undefined' === typeof b.data('xbdgr_requesturl')) {
                console.error('Crossbadger-js: the badge request url is not defined. Add "data-xbdgr_requesturl" on <body> with the url to use.');
                return false;
            } else {
                options['badgeRequestUrl'] = b.data('xbdgr_requesturl');
            }
        }

        // Badge request param
        if ('undefined' !== typeof b.data('xbdgr_requestparam')) {
            options['badgeRequestFieldParam'] = b.data('xbdgr_requestparam');
        } else if ('undefined' === typeof options.badgeRequestFieldParam || options.badgeRequestFieldParam === this.defaults.badgeRequestFieldParam) {
            console.info('Crossbadger-js: request param is not defined, [' + this.defaults.badgeRequestFieldParam + '] will be used instead.');
        }

        this.options = $.extend( this.defaults, options );

        this._init();
    };
    Crossbadger.prototype = {
        defaults: {
            bindingClass           : '.crossbadger-js',    // The class to put on the element
            badgeRequestUrl        : null,                 // The url to request badge counters
            badgeRequestFieldParam : 'fields',             // The param name with comma-separated badge names
            dataPrefix             : 'xbdgr',              // Data attribute prefix (ex: data-xbdgr_id)
            storagePrefix          : '_xbdgr_',            // Prefix used for sessionStorage vars keys
            defaultTimeCheck       : 60000,                // Default time for loop check
            defaultTtl             : 2,                    // Multiplier for the timer value to prevent lost master timer
            timeCheckVariation     : 10                    // Delay for timecheck (in percent)
        },

        state: {
            id        : null,
            initCheck : -1,
        },

        masterTimers: [],
        localTimers: {},
        timersIntervals: {},

        _init: function() {
            // Generate instance ID
            this.state.id = this._uniqId();
            console.info('Crossbadger-js: instance ID is [' + this.state.id + ']');

            // Set lastCheck
            this.state.initCheck = new Date() / 1000 | 0;

            // Browse elements
            this.items = this._setTimersStorage($(this.options.bindingClass));

            // Unload event
            window.onunload = $.proxy(this._unload, this);

            // Set loops
            this._setTimers();
        },

        _execTimer: function(timer) {
            if ('undefined' !== typeof timer) {
                // Check masterID
                var masterID  = this._storageGet('t_' + timer + '_masterID'),
                    ttl       = this._storageGet('t_' + timer + '_ttl'),
                    timeCheck = new Date() / 1000 | 0;

                // Set masterID is none set
                if (null === masterID || this._ttlExpired((timeCheck - ttl), timer)) {
                    this._storageSet('t_' + timer + '_masterID', this.state.id);
                    this._storageSet('t_' + timer + '_ttl', timeCheck);
                }

                // Check if master or slave
                if (this.state.id === masterID) {
                    // MASTER => AJAX
                    var ids         = this._storageGet('t_' + timer + '_timers').join(','),
                        queryParams = {};

                    queryParams[this.options.badgeRequestFieldParam] = ids;

                    $.ajax({
                        type: 'GET',
                        data: queryParams,
                        url: this.options.badgeRequestUrl,
                        cache: false,
                        dataType: 'json',
                        success: $.proxy(function(data) {
                            if (data.fields) {
                                // Update ttl
                                this._storageSet('t_' + timer + '_ttl', timeCheck);

                                $.each(data.fields, $.proxy(function(id, obj){
                                    if (this.items[id]) {
                                        // Empty string if "nozero" option
                                        if (1 === this.items[id].data(this.options.dataPrefix + '_nozero') && '0' === obj.count) {
                                            this.items[id].text('');
                                        } else {
                                            this.items[id].text(obj.count);
                                        }
                                        this.localTimers[timer].values[id] = obj.count;
                                    }
                                    // We store in localStorage the checked value
                                    this._storageSet('tv_' + id, {
                                        'lastCheck': obj.time,
                                        'value': obj.count
                                    });
                                }, this));
                            }
                        }, this)
                    });
                } else {
                    // SLAVE => STORAGE ONLY
                    $.each(this.localTimers[timer].id, $.proxy(function(k, id){
                        var v = this._storageGet('tv_' + id).value;
                        if (1 === this.items[id].data(this.options.dataPrefix + '_nozero') && '0' === v) {
                            this.items[id].text('');
                        } else {
                            this.items[id].text(v);
                        }
                        this.localTimers[timer].values[id] = v;
                    }, this));
                }
            }

            return true;
        },

        _setTimers: function() {
            $.each(this.localTimers, $.proxy(function(t, o){
                this.timersIntervals[t] = setInterval($.proxy(function() { this._execTimer(t); }, this), t);
            }, this));
        },

        _setTimersStorage: function(elements) {
            var items = {};

            // Browse current page items
            $.each(elements, $.proxy(function(k, item){
                var $item           = $(item),
                    id              = $item.data(this.options.dataPrefix + '_id'),
                    t               = $item.data(this.options.dataPrefix + '_time') || this.options.defaultTimeCheck,
                    v               = $item.text();

                // If item is correctly set
                if (id) {
                    // Create a timer entry for grouping
                    if ('undefined' == typeof this.localTimers[t]) {
                        this.localTimers[t] = {
                            'id': [],
                            'values': {}
                        };
                    }

                    // Timer entry
                    this.localTimers[t]['id'].push(id);
                    this.localTimers[t]['values'][id] = v;
                }

                items[id] = $item;
            }, this));

            // Browse localTimer to compare with Storage
            $.each(this.localTimers, $.proxy(function(t, group){
                var m = this._storageGet('t_' + t + '_masterID'),
                    ttl = this._storageGet('t_' + t + '_ttl'),
                    s = this._storageGet('t_' + t + '_timers') || [];

                if (0 === s.length || null === m || this._ttlExpired((this.state.initCheck - ttl), t)) {
                    this._storageSet('t_' + t + '_masterID', this.state.id);
                    this._storageSet('t_' + t + '_ttl', this.state.initCheck);
                    this.masterTimers.push(t);
                }

                $.each(group.id, $.proxy(function(k, id){
                    // If timer doesn't exists, we add it
                    if (-1 == s.indexOf(id)) {
                        s.push(id);
                        this._storageSet('tv_' + id, {
                            'lastCheck': this.state.initCheck,
                            'value': group.values[id]
                        });
                    } else {
                        var timerValue = this._storageGet('tv_' + id);

                        // If timer already exists, but last check is old, we update it
                        if (this._hasToBeChecked(timerValue.lastCheck, t)) {
                            this._storageSet('tv_' + id, {
                                'lastCheck': this.state.initCheck,
                                'value': group.values[id]
                            });
                        }
                    }
                }, this));

                // Set storage
                this._storageSet('t_' + t + '_timers', s);
            }, this));

            return items;
        },

        _ttlExpired: function(timeDiff, timer) {
            return timeDiff >= ((timer/1000) * this.options.defaultTtl);
        },

        _hasToBeChecked: function(time, timeCheck) {
            if ('undefined' == typeof timeCheck) {
                timeCheck = this.options.defaultTimeCheck;
            }

            return (this.state.initCheck - time) >= (timeCheck * (1 + this.options.timeCheckVariation/100));
        },

        _unload: function(e) {
            $.each(this.masterTimers, $.proxy(function(k, g){
                this._storageRemove('t_' + g + '_masterID');
                this._storageRemove('t_' + g + '_ttl');
            }, this));
        },

        _storageGet: function(name) {
            var d = localStorage.getItem(this.options.storagePrefix + name);
            try {
                d = JSON.parse(d);
            } catch(e) {
                // not a JSON string
            }
            return d;
        },

        _storageSet: function(name, value) {
            var d = value;

            if ('object' == typeof d) {
                d = JSON.stringify(value);
            }

            return localStorage.setItem(this.options.storagePrefix + name, d);
        },

        _storageRemove: function(name) {
            return localStorage.removeItem(this.options.storagePrefix + name);
        },

        _uniqId: function() {
            var t = new Date() / 1000 | 0,
                u = window.location.href,
                r = Math.floor(Math.random() * (10 - 1)) + 1;

            return (t + u + r).hashCode();
        }
    };

    // A simple string to hash function if none already exists
    if (typeof String.prototype.hashCode == 'undefined') {
        String.prototype.hashCode = function(){
            var hash = 0;
            if (this.length == 0) return hash;
            for (var i = 0; i < this.length; i++) {
                var singleChar = this.charCodeAt(i);
                hash = ((hash<<5)-hash)+singleChar;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash;
        }

    }

    $(document).ready(function(){
        window.Crossbadger = new Crossbadger();
    });

})(window.jQuery, window, document);