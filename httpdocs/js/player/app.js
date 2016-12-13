System.register("player/Config", [], function (exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var Config;
    return {
        setters: [],
        execute: function () {
            Config = (function () {
                function Config(data) {
                    if (!data || !data['flashplayer'] || !data['flashplayer']['config'])
                        throw new Error('Invalid configuration passed');
                    this.flashConfig = data['flashplayer']['config'];
                    this.config = data;
                }
                Config.prototype.getFlashConfig = function () {
                    return this.flashConfig;
                };
                Config.prototype.getFromKey = function (config, keys) {
                    var key = keys.shift();
                    if (key == null)
                        return "";
                    var ret = config[key];
                    if (ret) {
                        if (keys.length > 0)
                            return this.getFromKey(ret, keys);
                        return ret;
                    }
                    return "";
                };
                Config.prototype.get = function (key, def) {
                    var keys = key.split('.');
                    var ret = this.getFromKey(this.config, keys);
                    if (ret != null)
                        return ret;
                    if (def)
                        return def;
                    return "";
                };
                return Config;
            }());
            exports_1("default", Config);
        }
    };
});
System.register("Locale", [], function (exports_2, context_2) {
    "use strict";
    var __moduleName = context_2 && context_2.id;
    var Locale;
    return {
        setters: [],
        execute: function () {
            Locale = (function () {
                function Locale(data) {
                    if (typeof data != 'object')
                        throw new Error('Invalid locale passed');
                    this.data = data;
                }
                Locale.prototype.get = function (key) {
                    if (this.data[key])
                        return String(this.data[key]);
                    return key;
                };
                return Locale;
            }());
            exports_2("default", Locale);
        }
    };
});
System.register("player/Flash", [], function (exports_3, context_3) {
    "use strict";
    var __moduleName = context_3 && context_3.id;
    var Flash;
    return {
        setters: [],
        execute: function () {
            Flash = (function () {
                function Flash(cfg, l) {
                    if (!cfg)
                        throw "Invalid config passed";
                    if (!l)
                        throw "Invalid locale passed";
                    this.cfg = cfg;
                    this.l = l;
                }
                Flash.prototype.getFileName = function () {
                    var subtype = this.cfg.get('flashplayer.subtype');
                    var ver = this.cfg.get('version');
                    return "flash/VSQ" + subtype + "Player.swf?v=" + ver;
                };
                Flash.prototype.getParamRef = function (container, keys) {
                    var key = keys.shift();
                    if (key == null)
                        throw new Error("Invalid key");
                    var ret = container[key];
                    if (ret && keys.length > 0)
                        return this.getParamRef(ret, keys);
                    return ret;
                };
                Flash.prototype.embed = function () {
                    var fileName = this.getFileName();
                    var paramStr = String(this.cfg.get('flashplayer.params', 'flashdefaults.params'));
                    var param = this.getParamRef(window, paramStr.split('.'));
                    var config = JSON.parse(String(this.cfg.getFlashConfig()));
                    swfobject.embedSWF(fileName, this.cfg.get('containerid'), this.cfg.get('width'), this.cfg.get('height'), '11.1.0', 'flash/swfobject/expressInstall.swf', config, param, null, handleFlashLoad);
                };
                return Flash;
            }());
            exports_3("default", Flash);
        }
    };
});
System.register("player/Flow", [], function (exports_4, context_4) {
    "use strict";
    var __moduleName = context_4 && context_4.id;
    var Flow;
    return {
        setters: [],
        execute: function () {
            Flow = (function () {
                function Flow(player, root) {
                    this.engines = [];
                    this.eventsInitialized = false;
                    Flow.log(arguments);
                    this.player = player;
                    this.cfg = player.conf.vsq;
                    this.root = jQuery(root);
                    this.id = this.root.attr('data-flowplayer-instance-id');
                }
                Flow.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    params.unshift("[Flow]");
                    console.log.apply(console, params);
                };
                Flow.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    Flow.log(params);
                };
                Flow.prototype.multiCall = function (funcName, args) {
                    var ret = [];
                    for (var i = this.engines.length - 1; i >= 0; i--) {
                        var engine = this.engines[i];
                        ret[i] = engine[funcName].apply(engine, args);
                    }
                    return ret;
                };
                Flow.prototype.multiSet = function (property, value) {
                    for (var i = this.engines.length - 1; i >= 0; i--) {
                        var engine = this.engines[i];
                        engine[property] = value;
                    }
                };
                Flow.prototype.getType = function (type) {
                    return /mpegurl/i.test(type) ? "application/x-mpegurl" : type;
                };
                Flow.prototype.createVideoTag = function (video) {
                    var autoplay = false;
                    var preload = 'metadata';
                    var ret = document.createElement('video');
                    ret.src = video.src;
                    ret.type = this.getType(video.type);
                    ret.className = 'fp-engine';
                    ret.autoplay = autoplay ? 'autoplay' : false;
                    ret.preload = preload;
                    ret.setAttribute('x-webkit-airplay', 'allow');
                    return ret;
                };
                Flow.canPlay = function (type, conf) {
                    return true;
                };
                Flow.prototype.round = function (val, per) {
                    var percent = 100;
                    if (per)
                        percent = per;
                    return Math.round(val * percent) / percent;
                };
                Flow.prototype.setupVideoEvents = function (video) {
                    var _this = this;
                    if (this.eventsInitialized)
                        return;
                    var master = this.engines[Flow.MASTER];
                    var sources = jQuery(this.engines);
                    sources.on('error', function (e) {
                        try {
                            _this.player.trigger('error', [
                                _this.player,
                                { code: 4, video: video }
                            ]);
                        }
                        catch (_) { }
                    });
                    this.player.on('shutdown', function () {
                        sources.off();
                    });
                    var events = {
                        ended: 'finish',
                        pause: 'pause',
                        play: 'resume',
                        progress: 'buffer',
                        timeupdate: 'progress',
                        volumechange: 'volume',
                        ratechange: 'speed',
                        seeked: 'seek',
                        loadeddata: 'ready',
                        error: 'error',
                        dataunavailable: 'error'
                    };
                    var trigger = function (event, arg) {
                        _this.player.trigger(event, [_this.player, arg]);
                    };
                    var arg = {};
                    jQuery.each(events, function (index, flowEvent) {
                        var l = function (e) {
                            if (!e.target || jQuery(e.target).find('.fp-engine').length == 0)
                                return;
                            _this.log(index, flowEvent, e);
                            if ((!_this.player.ready && flowEvent !== 'ready' && flowEvent !== 'error') ||
                                !flowEvent ||
                                _this.root.find('video').length === 0)
                                return;
                            switch (flowEvent) {
                                case "unload":
                                    _this.player.unload();
                                    return;
                                case "ready":
                                    arg = jQuery.extend(arg, video, {
                                        duration: master.duration,
                                        width: master.videoWidth,
                                        height: master.videoHeight,
                                        url: master.currentSrc,
                                        src: master.currentSrc
                                    });
                                    arg.seekable = false;
                                    try {
                                        if (!_this.player.live &&
                                            (master.duration || master.seekable) &&
                                            master.seekable.end(null))
                                            arg.seekable = true;
                                    }
                                    catch (_) { }
                                    ;
                                    _this.timer = setInterval(function () {
                                        arg.buffer = master.buffered.end(null);
                                        if (!arg.buffer)
                                            return;
                                        if (_this.round(arg.buffer, 1000) < _this.round(arg.duration, 1000) &&
                                            !arg.buffered) {
                                            _this.player.trigger("buffer", e);
                                        }
                                        else if (!arg.buffered) {
                                            arg.buffered = true;
                                            _this.player.trigger("buffer", e).trigger("buffered", e);
                                            clearInterval(_this.timer);
                                            _this.timer = 0;
                                        }
                                    }, 250);
                                    break;
                                default:
                                    throw new Error('unhandled event: ' + flowEvent);
                            }
                            trigger(flowEvent, arg);
                        };
                        _this.root.get(0).addEventListener(index, l, true);
                    });
                };
                Flow.prototype.load = function (video) {
                    var root = this.root.find('.fp-player');
                    if (this.cfg.secondarySources && !this.engines[Flow.CONTENT]) {
                        var secondVideo = jQuery.extend(true, {}, video);
                        secondVideo.src = this.cfg.secondarySources[0].src;
                        secondVideo.sources = this.cfg.secondarySources;
                        this.engines[Flow.CONTENT] = this.createVideoTag(secondVideo);
                        this.engines[Flow.CONTENT].load();
                        var engine = jQuery(this.engines[Flow.CONTENT]);
                        engine.addClass('vsq-content');
                        root.prepend(engine);
                    }
                    if (!this.engines[Flow.MASTER]) {
                        this.engines[Flow.MASTER] = this.createVideoTag(video);
                        this.engines[Flow.MASTER].load();
                        var engine = jQuery(this.engines[Flow.MASTER]);
                        engine.addClass('vsq-master');
                        root.prepend(engine);
                    }
                };
                Flow.prototype.pause = function () {
                    this.multiCall('pause');
                };
                Flow.prototype.resume = function () {
                    this.multiCall('play');
                };
                Flow.prototype.speed = function (speed) {
                    this.multiSet('playbackRate', speed);
                };
                Flow.prototype.volume = function (volume) {
                    this.multiSet('volume', volume);
                };
                Flow.prototype.unload = function () {
                };
                Flow.prototype.seek = function (to) {
                    try {
                        var pausedState = this.player.paused;
                        this.multiSet('currentTime', to);
                        if (pausedState)
                            this.pause();
                    }
                    catch (ignored) { }
                };
                Flow.prototype.pick = function (sources) {
                    return sources[0];
                };
                Flow.setup = function () {
                    if (Flow.initDone)
                        return;
                    var dummy = function (player, root) {
                        return new Flow(player, root);
                    };
                    dummy.engineName = Flow.engineName;
                    dummy.canPlay = Flow.canPlay;
                    flowplayer.engines.unshift(dummy);
                    Flow.initDone = true;
                };
                return Flow;
            }());
            Flow.engineName = "vsq";
            Flow.initDone = false;
            Flow.MASTER = 0;
            Flow.CONTENT = 1;
            exports_4("default", Flow);
        }
    };
});
System.register("player/Player", ["player/Flash", "player/Flow"], function (exports_5, context_5) {
    "use strict";
    var __moduleName = context_5 && context_5.id;
    var Flash_1, Flow_1, Player;
    return {
        setters: [
            function (Flash_1_1) {
                Flash_1 = Flash_1_1;
            },
            function (Flow_1_1) {
                Flow_1 = Flow_1_1;
            }
        ],
        execute: function () {
            Player = (function () {
                function Player(cfg, l) {
                    if (!cfg)
                        throw "Invalid config passed";
                    if (!l)
                        throw "Invalid locale passed";
                    this.cfg = cfg;
                    this.l = l;
                }
                Player.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    params.unshift("[Player]");
                    console.log.apply(console, params);
                };
                Player.prototype.supportsVideo = function () {
                    var elem = document.createElement('video');
                    return !!elem.canPlayType;
                };
                Player.prototype.initFlash = function () {
                    var flash = new Flash_1.default(this.cfg, this.l);
                    flash.embed();
                };
                Player.prototype.init = function () {
                    this.container = jQuery('#' + this.cfg.get('containerid'));
                    if (this.container.length == 0) {
                        this.log("container not found");
                        return;
                    }
                    if (!this.cfg.get('flowplayer')) {
                        this.initFlash();
                        return;
                    }
                    if (!this.supportsVideo()) {
                        this.log("falling back to flash");
                        this.initFlash();
                        return;
                    }
                    this.initFlow();
                };
                Player.prototype.initFlow = function () {
                    var _this = this;
                    this.initFlowPlugin();
                    this.flowInstance = flowplayer(this.container.get(0), this.cfg.get('flowplayer'));
                    this.flowInstance.on('load', function (e, api, video) {
                        _this.log('ready', e, api, video);
                    });
                };
                Player.prototype.initFlowPlugin = function () {
                    Flow_1.default.setup();
                };
                return Player;
            }());
            exports_5("default", Player);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/Player"], function (exports_6, context_6) {
    "use strict";
    var __moduleName = context_6 && context_6.id;
    var Locale_1, Config_1, Player_1;
    return {
        setters: [
            function (Locale_1_1) {
                Locale_1 = Locale_1_1;
            },
            function (Config_1_1) {
                Config_1 = Config_1_1;
            },
            function (Player_1_1) {
                Player_1 = Player_1_1;
            }
        ],
        execute: function () {
            (function ($) {
                var pcCopy = $.extend(true, {}, playerconfig);
                var lCopy = $.extend(true, {}, l);
                $(function () {
                    var cfg = new Config_1.default(pcCopy);
                    var loc = new Locale_1.default(lCopy);
                    var player = new Player_1.default(cfg, loc);
                    player.init();
                });
            })(jQuery);
        }
    };
});
//# sourceMappingURL=app.js.map