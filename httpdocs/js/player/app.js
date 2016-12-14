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
                    this.videoTags = [];
                    this.hlsEngines = [];
                    this.eventsInitialized = false;
                    Flow.log(arguments);
                    this.player = player;
                    this.cfg = player.conf.vsq;
                    this.hlsConf = jQuery.extend({
                        bufferWhilePaused: true,
                        smoothSwitching: true,
                        recoverMediaError: true
                    }, flowplayer.conf['hlsjs'], this.player.conf['hlsjs'], this.player.conf['clip']['hlsjs']);
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
                Flow.prototype.hlsCall = function (funcName, args) {
                    var ret = [];
                    for (var i = this.hlsEngines.length - 1; i >= 0; i--) {
                        var hls = this.hlsEngines[i];
                        if (hls == null)
                            continue;
                        ret[i] = hls[funcName].apply(hls, args);
                    }
                    return ret;
                };
                Flow.prototype.hlsSet = function (property, value) {
                    var ret = [];
                    for (var i = this.hlsEngines.length - 1; i >= 0; i--) {
                        var hls = this.hlsEngines[i];
                        if (hls == null)
                            continue;
                        hls[property] = value;
                    }
                };
                Flow.prototype.tagCall = function (funcName, args) {
                    var ret = [];
                    for (var i = this.videoTags.length - 1; i >= 0; i--) {
                        var engine = this.videoTags[i];
                        if (engine == null)
                            continue;
                        ret[i] = engine[funcName].apply(engine, args);
                    }
                    return ret;
                };
                Flow.prototype.tagSet = function (property, value) {
                    for (var i = this.videoTags.length - 1; i >= 0; i--) {
                        var engine = this.videoTags[i];
                        if (engine == null)
                            continue;
                        engine[property] = value;
                    }
                };
                Flow.prototype.getType = function (type) {
                    return /mpegurl/i.test(type) ? "application/x-mpegurl" : type;
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
                Flow.prototype.doRecover = function (conf, flowEvent, isNetworkError) {
                    if (conf.debug)
                        this.log('recovery.vsq', flowEvent);
                    this.root.removeClass('is-paused');
                    this.root.addClass('is-seeking');
                    if (isNetworkError)
                        this.hlsCall('startLoad');
                    else {
                        var now = performance.now();
                        if (!this.recoverMediaErrorDate || now - this.recoverMediaErrorDate > 3000) {
                            this.recoverMediaErrorDate = performance.now();
                            this.hlsCall('recoverMediaError');
                        }
                        else {
                            if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
                                this.swapAudioCodecDate = performance.now();
                                this.hlsCall('swapAudioCodec');
                                this.hlsCall('recoverMediaError');
                            }
                            else
                                return 3;
                        }
                    }
                    return undefined;
                };
                Flow.prototype.removePoster = function () {
                };
                Flow.prototype.setupVideoEvents = function (video) {
                    var _this = this;
                    if (this.eventsInitialized)
                        return;
                    var masterHLS = this.hlsEngines[Flow.MASTER];
                    var masterTag = this.videoTags[Flow.MASTER];
                    var master = jQuery(masterTag);
                    var sources = jQuery(this.videoTags);
                    var events = {
                        ended: "finish",
                        loadeddata: "ready",
                        pause: "pause",
                        play: "resume",
                        progress: "buffer",
                        ratechange: "speed",
                        seeked: "seek",
                        timeupdate: "progress",
                        volumechange: "volume",
                        error: "error"
                    };
                    var hlsEvents = Hls.Events;
                    var currentTime = masterTag.currentTime;
                    var arg = {};
                    jQuery.each(events, function (videoEvent, flowEvent) {
                        videoEvent += "." + Flow.engineName;
                        master.on(videoEvent, function (e) {
                            if (flowEvent.indexOf("progress") < 0)
                                _this.log(videoEvent, flowEvent, e);
                            var video = _this.player.video;
                            switch (flowEvent) {
                                case "ready":
                                    arg = jQuery.extend(arg, video, {
                                        duration: masterTag.duration,
                                        seekable: masterTag.seekable.end(null),
                                        width: masterTag.videoWidth,
                                        height: masterTag.videoHeight,
                                        url: video.src
                                    });
                                    break;
                                case "resume":
                                    _this.removePoster();
                                    if (!_this.hlsConf.bufferWhilePaused)
                                        _this.hlsCall('startLoad', [currentTime]);
                                    break;
                                case "seek":
                                    _this.removePoster();
                                    if (!_this.hlsConf.bufferWhilePaused && masterTag.paused) {
                                        _this.hlsCall('stopLoad');
                                        _this.tagCall('pause');
                                    }
                                    arg = currentTime;
                                    break;
                                case "pause":
                                    _this.removePoster();
                                    if (!_this.hlsConf.bufferWhilePaused)
                                        _this.hlsCall('stopLoad');
                                    break;
                                case "progress":
                                    arg = currentTime;
                                    break;
                                case "speed":
                                    arg = masterTag.playbackRate;
                                    break;
                                case "volume":
                                    arg = masterTag.volume;
                                    break;
                                case "buffer":
                                    var buffered = void 0;
                                    var buffer = 0;
                                    try {
                                        buffered = masterTag.buffered;
                                        buffer = buffered.end(null);
                                        if (currentTime) {
                                            for (var i = buffered.length - 1; i >= 0; i--) {
                                                var buffend = buffered.end(i);
                                                if (buffend >= currentTime)
                                                    buffer = buffend;
                                            }
                                        }
                                    }
                                    catch (_) { }
                                    ;
                                    video.buffer = buffer;
                                    arg = buffer;
                                    break;
                                case "finish":
                                    var flush_1 = false;
                                    if (_this.hlsConf.bufferWhilePaused && masterHLS.autoLevelEnabled &&
                                        (video.loop ||
                                            _this.player.conf.playlist.length < 2 ||
                                            _this.player.conf.advance == false)) {
                                        flush_1 = !masterHLS.levels[_this.maxLevel].details;
                                        if (!flush_1)
                                            masterHLS[_this.maxLevel].details.fragments.forEach(function (frag) {
                                                flush_1 = !!flush_1 || !frag.loadCounter;
                                            });
                                    }
                                    if (flush_1) {
                                        _this.hlsCall('trigger', [
                                            hlsEvents.BUFFER_FLUSHING,
                                            {
                                                startOffset: 0,
                                                endOffset: video.duration
                                            }
                                        ]);
                                        _this.log(_this.maxLevel);
                                        _this.hlsSet('nextLoadLevel', _this.maxLevel);
                                        _this.hlsCall('startLoad', [masterHLS.config.startPosition]);
                                        _this.maxLevel = 0;
                                        if (!video.loop) {
                                            master.one("play." + _this.engineName, function () {
                                                if (masterTag.currentTime >= masterTag.duration)
                                                    masterTag.currentTime = 0;
                                            });
                                        }
                                    }
                                    break;
                                case "error":
                                    var code = masterTag.error.code;
                                    if ((_this.hlsConf.recoverMediaError && code === 3) ||
                                        (_this.hlsConf.recoverNetworkError && code === 2) ||
                                        (_this.hlsConf.recover && (code === 2 || code === 3)))
                                        code = _this.doRecover(_this.player.conf, flowEvent, code === 2);
                                    arg = false;
                                    if (code !== undefined) {
                                        arg = { code: code };
                                        if (code > 2)
                                            arg.video = jQuery.extend(video, { url: video.src });
                                    }
                                    break;
                            }
                            if (arg === false)
                                return arg;
                            _this.player.trigger(flowEvent, [_this.player, arg]);
                        });
                    });
                };
                Flow.prototype.createVideoTag = function (video) {
                    var autoplay = false;
                    var ret = document.createElement('video');
                    ret.src = video.src;
                    ret.type = this.getType(video.type);
                    ret.className = 'fp-engine vsq-engine';
                    ret.autoplay = autoplay ? 'autoplay' : false;
                    ret.setAttribute('x-webkit-airplay', 'allow');
                    return ret;
                };
                Flow.prototype.destroyVideoTag = function (index) {
                    var tagElem = this.videoTags[index];
                    var elem = jQuery(tagElem);
                    elem.find('source').removeAttr('src');
                    elem.removeAttr('src');
                    tagElem.load();
                    elem.remove();
                };
                Flow.prototype.load = function (video) {
                    var root = this.root.find('.fp-player');
                    this.hlsConf = jQuery.extend(this.hlsConf, this.player.conf.hlsjs, this.player.conf.clip.hlsjs, video.hlsjs);
                    if (this.cfg.secondarySources) {
                        if (this.videoTags[Flow.CONTENT])
                            this.destroyVideoTag(Flow.CONTENT);
                        var secondVideo = jQuery.extend(true, {}, video);
                        secondVideo.src = this.cfg.secondarySources[0].src;
                        secondVideo.sources = this.cfg.secondarySources;
                        this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
                        this.videoTags[Flow.CONTENT].load();
                        var engine_1 = jQuery(this.videoTags[Flow.CONTENT]);
                        engine_1.addClass('vsq-content');
                        root.prepend(engine_1);
                    }
                    if (this.videoTags[Flow.MASTER])
                        this.destroyVideoTag(Flow.MASTER);
                    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
                    this.videoTags[Flow.MASTER].load();
                    var engine = jQuery(this.videoTags[Flow.MASTER]);
                    engine.addClass('vsq-master');
                    root.prepend(engine);
                    this.setupVideoEvents(video);
                };
                Flow.prototype.pause = function () {
                    this.multiCall('pause');
                };
                Flow.prototype.resume = function () {
                    this.multiCall('play');
                };
                Flow.prototype.speed = function (speed) {
                    this.multiSet('playbackRate', speed);
                    this.player.trigger('speed', [this.player, speed]);
                };
                Flow.prototype.volume = function (volume) {
                    this.multiSet('volume', volume);
                };
                Flow.prototype.unload = function () {
                    var videoTags = jQuery(this.videoTags);
                    videoTags.remove();
                    this.hlsCall('destroy');
                    var listeners = '.' + Flow.engineName;
                    this.player.off(listeners);
                    this.root.off(listeners);
                    videoTags.off(listeners);
                    for (var i = this.hlsEngines.length - 1; i >= 0; i--)
                        this.hlsEngines.pop();
                    for (var i = this.videoTags.length - 1; i >= 0; i--)
                        this.videoTags.pop();
                };
                Flow.prototype.seek = function (to) {
                    this.multiSet('currentTime', to);
                };
                Flow.prototype.pick = function (sources) {
                    if (sources.length == 0)
                        throw new Error("Zero length FlowSources passed");
                    return sources[0];
                };
                Flow.setup = function () {
                    if (Flow.initDone)
                        return;
                    var proxy = function (player, root) {
                        return new Flow(player, root);
                    };
                    proxy.engineName = Flow.engineName;
                    proxy.canPlay = Flow.canPlay;
                    flowplayer.videoTags.unshift(proxy);
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