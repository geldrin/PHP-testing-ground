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
                    this.maxLevel = 0;
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
                Flow.prototype.callOnArray = function (data, funcName, args) {
                    var ret = [];
                    for (var i = data.length - 1; i >= 0; i--) {
                        var elem = data[i];
                        if (elem == null)
                            continue;
                        ret[i] = data[funcName].apply(elem, args);
                    }
                    return ret;
                };
                Flow.prototype.setOnArray = function (data, property, value) {
                    var ret = [];
                    for (var i = data.length - 1; i >= 0; i--) {
                        var elem = data[i];
                        if (elem == null)
                            continue;
                        elem[property] = value;
                    }
                };
                Flow.prototype.hlsCall = function (funcName, args) {
                    return this.callOnArray(this.hlsEngines, funcName, args);
                };
                Flow.prototype.hlsSet = function (property, value) {
                    this.setOnArray(this.hlsEngines, property, value);
                };
                Flow.prototype.tagCall = function (funcName, args) {
                    return this.callOnArray(this.videoTags, funcName, args);
                };
                Flow.prototype.tagSet = function (property, value) {
                    this.setOnArray(this.videoTags, property, value);
                };
                Flow.prototype.getType = function (type) {
                    if (Flow.isHLSType(type))
                        return "application/x-mpegurl";
                    return type;
                };
                Flow.isHLSType = function (type) {
                    return type.toLowerCase().indexOf("mpegurl") > -1;
                };
                Flow.HLSQualitiesSupport = function (conf) {
                    var hlsQualities = (conf.clip && conf.clip.hlsQualities) || conf.hlsQualities;
                    return flowplayer.support.inlineVideo &&
                        (hlsQualities === true ||
                            (hlsQualities && hlsQualities.length));
                };
                Flow.canPlay = function (type, conf) {
                    var b = flowplayer.support.browser;
                    var wn = window.navigator;
                    var isIE11 = wn.userAgent.indexOf("Trident/7") > -1;
                    if (conf['vsq'] === false || conf.clip['vsq'] === false ||
                        conf['hlsjs'] === false || conf.clip['hlsjs'] === false)
                        return false;
                    if (Flow.isHLSType(type)) {
                        if (conf.hlsjs &&
                            conf.hlsjs.anamorphic &&
                            wn.platform.indexOf("Win") === 0 &&
                            b.mozilla && b.version.indexOf("44.") === 0)
                            return false;
                        return isIE11 || !b.safari;
                    }
                    return false;
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
                Flow.prototype.addPoster = function () {
                    var _this = this;
                    var master = jQuery(this.videoTags[Flow.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.root.addClass("is-poster");
                        _this.player.poster = true;
                    });
                };
                Flow.prototype.removePoster = function () {
                    var _this = this;
                    if (!this.player.poster)
                        return;
                    var master = jQuery(this.videoTags[Flow.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.root.removeClass("is-poster");
                        _this.player.poster = false;
                    });
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
                        videoEvent = _this.eventName(videoEvent);
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
                                            master.one(_this.eventName("play"), function () {
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
                            if (flowEvent === "ready" && _this.player.quality) {
                                var selectorIndex = void 0;
                                if (_this.player.quality === "abr")
                                    selectorIndex = 0;
                                else
                                    selectorIndex = _this.player.qualities.indexOf(_this.player.quality) + 1;
                                _this.root.find(".fp-quality-selector li").eq(selectorIndex).addClass(_this.activeQuality);
                            }
                        });
                    });
                    if (this.player.conf.poster) {
                        this.player.on(this.eventName("stop"), function () {
                            _this.addPoster();
                        });
                        if (this.player.live)
                            master.one(this.eventName("seeked"), function () {
                                _this.addPoster();
                            });
                    }
                    this.player.on(this.eventName("error"), function () {
                        _this.hlsCall('destroy');
                    });
                };
                Flow.prototype.setupHLSEvents = function (video) {
                    var _this = this;
                    var conf = jQuery.extend({}, this.hlsConf);
                    conf.autoStartLoad = false;
                    this.hlsEngines[Flow.MASTER] = new Hls(conf);
                    this.hlsEngines[Flow.MASTER].VSQType = Flow.MASTER;
                    this.hlsEngines[Flow.CONTENT] = new Hls(conf);
                    this.hlsEngines[Flow.CONTENT].VSQType = Flow.CONTENT;
                    var hlsEvents = Hls.Events;
                    jQuery.each(hlsEvents, function (eventName, hlsEvent) {
                        var shouldTrigger = _this.hlsConf.listeners && _this.hlsConf.listeners.indexOf(hlsEvent) > -1;
                        jQuery.each(_this.hlsEngines, function (hlsType, hls) {
                            hls.on(hlsEvent, function (e, data) {
                                var errorTypes = Hls.ErrorTypes;
                                var errorDetails = Hls.ErrorDetails;
                                switch (eventName) {
                                    case "MEDIA_ATTACHED":
                                        hls.loadSource(video.src);
                                        break;
                                    case "MANIFEST_PARSED":
                                        delete _this.player.quality;
                                        hls.startLoad(hls.config.startPosition);
                                        break;
                                    case "FRAG_LOADED":
                                        if (_this.hlsConf.bufferWhilePaused && !_this.player.live &&
                                            hls.autoLevelEnabled && hls.nextLoadLevel > _this.maxLevel)
                                            _this.maxLevel = hls.nextLoadLevel;
                                        break;
                                    case "FRAG_PARSING_METADATA":
                                        break;
                                    case "ERROR":
                                        var flowError = undefined;
                                        var errorObj = {};
                                        if (data.fatal || _this.hlsConf.strict) {
                                            switch (data.type) {
                                                case errorTypes.NETWORK_ERROR:
                                                    if (_this.hlsConf.recoverNetworkError)
                                                        _this.doRecover(_this.player.conf, data.type, true);
                                                    else if (data.frag && data.frag.url) {
                                                        errorObj.url = data.frag.url;
                                                        flowError = 2;
                                                    }
                                                    else
                                                        flowError = 4;
                                                    break;
                                                case errorTypes.MEDIA_ERROR:
                                                    if (_this.hlsConf.recoverMediaError)
                                                        flowError = _this.doRecover(_this.player.conf, data.type, false);
                                                    else
                                                        flowError = 3;
                                                    break;
                                                default:
                                                    hls.destroy();
                                                    flowError = 5;
                                                    break;
                                            }
                                            if (flowError !== undefined) {
                                                errorObj.code = flowError;
                                                if (flowError > 2) {
                                                    errorObj.video = jQuery.extend(video, {
                                                        url: data.url || video.src
                                                    });
                                                }
                                                _this.player.trigger("error", [_this.player, errorObj]);
                                            }
                                        }
                                        else {
                                            switch (data.details) {
                                                case errorDetails.BUFFER_STALLED_ERROR:
                                                case errorDetails.FRAG_LOOP_LOADING_ERROR:
                                                    _this.root.addClass('is-seeking');
                                                    jQuery(_this.videoTags).one(_this.eventName("timeupdate"), function () {
                                                        _this.root.removeClass('is-seeking');
                                                    });
                                                    break;
                                            }
                                        }
                                        break;
                                    default:
                                        throw new Error("unhandled hls eventname: " + eventName);
                                }
                                if (shouldTrigger && hlsType === Flow.MASTER)
                                    _this.player.trigger(e, [_this.player, data]);
                            });
                        });
                    });
                    jQuery.each(this.hlsEngines, function (hlsType, hls) {
                        var tag = _this.videoTags[hls.VSQType];
                        if (_this.hlsConf.adaptOnStartOnly) {
                            jQuery(tag).one(_this.eventName("timeupdate"), function () {
                                hls.loadLevel = hls.loadLevel;
                            });
                        }
                        hls.attachMedia(tag);
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
                Flow.prototype.eventName = function (event) {
                    var postfix = '.' + Flow.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
                };
                Flow.prototype.unload = function () {
                    var videoTags = jQuery(this.videoTags);
                    videoTags.remove();
                    this.hlsCall('destroy');
                    var listeners = this.eventName();
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
                    for (var i = 0; i < sources.length; ++i) {
                        var source = sources[i];
                        if (!Flow.isHLSType(source.type))
                            continue;
                        source.src = flowplayer.common.createAbsoluteUrl(source.src);
                        return source;
                    }
                    return null;
                };
                Flow.setup = function () {
                    if (Flow.initDone)
                        return;
                    var proxy = function (player, root) {
                        return new Flow(player, root);
                    };
                    proxy.engineName = Flow.engineName;
                    proxy.canPlay = Flow.canPlay;
                    flowplayer.engines.unshift(proxy);
                    flowplayer(function (api) {
                        if (Flow.HLSQualitiesSupport(api.conf) && Flow.canPlay("application/x-mpegurl", api.conf))
                            api.pluginQualitySelectorEnabled = true;
                        else
                            api.pluginQualitySelectorEnabled = false;
                    });
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