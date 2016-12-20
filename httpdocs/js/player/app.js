System.register("player/Config", [], function (exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var Config;
    return {
        setters: [],
        execute: function () {
            Config = (function () {
                function Config(data, flashConfig) {
                    if (!data || !flashConfig)
                        throw new Error('Invalid configuration passed');
                    this.flashConfig = flashConfig;
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
                Flash.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    params.unshift("[Flash]");
                    console.log.apply(console, params);
                };
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
                    swfobject.embedSWF(fileName, this.cfg.get('containerid'), this.cfg.get('width'), this.cfg.get('height'), '11.1.0', 'flash/swfobject/expressInstall.swf', this.cfg.getFlashConfig(), param, null, handleFlashLoad);
                };
                return Flash;
            }());
            exports_3("default", Flash);
        }
    };
});
System.register("Tools", [], function (exports_4, context_4) {
    "use strict";
    var __moduleName = context_4 && context_4.id;
    var Tools;
    return {
        setters: [],
        execute: function () {
            Tools = (function () {
                function Tools() {
                }
                Tools.parseParamsFromUrl = function () {
                    if (!location.search)
                        return {};
                    var query = location.search.substr(1);
                    var result = {};
                    query.split("&").forEach(function (part) {
                        var item = part.split("=");
                        if (item.length == 1)
                            return;
                        var name = decodeURIComponent(item[0]);
                        var value = decodeURIComponent(item[1]);
                        result[name] = value;
                    });
                    return result;
                };
                Tools.parseURLFromCSS = function (css) {
                    var match = css.match(/url\(["']?([^)]+?)['"]?\)/);
                    if (match)
                        return match[1];
                    return null;
                };
                Tools.getImageDimensions = function (url, cb) {
                    $('<img/>', {
                        load: function () {
                            cb(this.width, this.height);
                        },
                        src: url
                    });
                };
                Tools.setToStorage = function (key, value) {
                    var raw = JSON.stringify(value);
                    localStorage.setItem(key, raw);
                };
                Tools.getFromStorage = function (key, def) {
                    var raw = localStorage.getItem(key);
                    if (raw == null)
                        return def;
                    var data;
                    try {
                        data = JSON.parse(raw);
                    }
                    catch (_) {
                        return def;
                    }
                    return data;
                };
                return Tools;
            }());
            exports_4("default", Tools);
        }
    };
});
System.register("Escape", [], function (exports_5, context_5) {
    "use strict";
    var __moduleName = context_5 && context_5.id;
    var Escape;
    return {
        setters: [],
        execute: function () {
            Escape = (function () {
                function Escape() {
                }
                Escape.RE = function (text) {
                    return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
                };
                Escape.HTML = function (text) {
                    return text.
                        replace(/&/g, '&amp;').
                        replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function (text) {
                        var hi = text.charCodeAt(0);
                        var low = text.charCodeAt(1);
                        return '&#' + (((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000) + ';';
                    }).
                        replace(/([^\#-~| |!])/g, function (text) {
                        return '&#' + text.charCodeAt(0) + ';';
                    }).
                        replace(/</g, '&lt;').
                        replace(/>/g, '&gt;');
                };
                Escape.unescapeHTML = function (text) {
                    if (!text)
                        return '';
                    Escape.elem.innerHTML = text.replace(/</g, "&lt;");
                    return Escape.elem.textContent;
                };
                Escape.URL = function (text) {
                    return encodeURI(text);
                };
                Escape.unescapeURL = function (text) {
                    return decodeURI(text);
                };
                Escape.fileName = function (text, maxLength, allowedExtensions) {
                    text = $.trim(text);
                    text = text.replace(/[^a-zA-Z0-9_\-\.]/g, function (match) {
                        var ret = Escape.fileReplace[match];
                        if (!ret)
                            return '_';
                        return ret;
                    });
                    if (maxLength && text.length > maxLength)
                        return '';
                    if (allowedExtensions) {
                        var dotPos = text.lastIndexOf('.');
                        if (allowedExtensions.length != 0 && dotPos < 0)
                            return '';
                        var ext = text.substring(dotPos + 1);
                        var found = false;
                        for (var i = allowedExtensions.length - 1; i >= 0; i--) {
                            var okExt = allowedExtensions[i];
                            if (ext === okExt) {
                                found = true;
                                break;
                            }
                        }
                        if (!found)
                            return '';
                    }
                    return text;
                };
                return Escape;
            }());
            Escape.elem = document.createElement('pre');
            Escape.fileReplace = {
                'á': 'a', 'Á': 'A',
                'é': 'e', 'É': 'E',
                'í': 'i', 'Í': 'I',
                'ó': 'o', 'Ó': 'O',
                'ö': 'o', 'Ö': 'O',
                'ő': 'o', 'Ő': 'O',
                'ú': 'u', 'Ú': 'U',
                'ü': 'u', 'Ü': 'U',
                'ű': 'u', 'Ű': 'U'
            };
            exports_5("default", Escape);
        }
    };
});
System.register("player/Flow", ["Tools", "Escape"], function (exports_6, context_6) {
    "use strict";
    var __moduleName = context_6 && context_6.id;
    var Tools_1, Escape_1, Flow;
    return {
        setters: [
            function (Tools_1_1) {
                Tools_1 = Tools_1_1;
            },
            function (Escape_1_1) {
                Escape_1 = Escape_1_1;
            }
        ],
        execute: function () {
            Flow = (function () {
                function Flow(player, root) {
                    this.videoTags = [];
                    this.hlsEngines = [];
                    this.eventsInitialized = false;
                    this.activeQualityClass = "active";
                    this.mse = window.MediaSource || window.WebKitMediaSource;
                    this.maxLevel = 0;
                    Flow.log("constructor", arguments);
                    this.player = player;
                    this.cfg = player.conf.vsq || {};
                    this.hlsConf = jQuery.extend({
                        bufferWhilePaused: true,
                        smoothSwitching: true,
                        recoverMediaError: true
                    }, flowplayer.conf['hlsjs'], this.player.conf['hlsjs'], this.player.conf['clip']['hlsjs']);
                    this.root = jQuery(root);
                    this.selectedQuality = Tools_1.default.getFromStorage(this.configKey("quality"), "auto");
                    this.id = this.root.attr('data-flowplayer-instance-id');
                }
                Flow.prototype.getQualityIndex = function (quality) {
                    for (var i = this.cfg.labels.master.length - 1; i >= 0; i--) {
                        var label = this.cfg.labels.master[i];
                        if (label === quality)
                            return i;
                    }
                    return -1;
                };
                Flow.prototype.configKey = function (key) {
                    return 'vsq-player-' + key;
                };
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
                        ret[i] = elem[funcName].apply(elem, args);
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
                        volumechange: "volume"
                    };
                    var currentTime = masterTag.currentTime;
                    var arg = {};
                    jQuery.each(events, function (videoEvent, flowEvent) {
                        videoEvent = _this.eventName(videoEvent);
                        master.on(videoEvent, function (e) {
                            if (flowEvent.indexOf("progress") < 0)
                                _this.log("event", videoEvent, flowEvent, e);
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
                                            Hls.Events.BUFFER_FLUSHING,
                                            {
                                                startOffset: 0,
                                                endOffset: video.duration
                                            }
                                        ]);
                                        _this.log("maxLevel", _this.maxLevel);
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
                            }
                            if (arg === false)
                                return false;
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
                Flow.prototype.handleError = function (type, video) {
                    var tag = this.videoTags[type];
                    var code = tag.error.code;
                    if ((this.hlsConf.recoverMediaError && code === 3) ||
                        (this.hlsConf.recoverNetworkError && code === 2) ||
                        (this.hlsConf.recover && (code === 2 || code === 3)))
                        code = this.doRecover(this.player.conf, "error", code === 2);
                    var arg;
                    if (code !== undefined) {
                        arg = { code: code };
                        if (code > 2)
                            arg.video = jQuery.extend(video, { url: video.src });
                    }
                    else
                        return;
                    this.player.trigger("error", [this.player, arg]);
                };
                Flow.prototype.eventName = function (event) {
                    var postfix = '.' + Flow.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
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
                Flow.prototype.setupHLS = function (type, conf) {
                    var _this = this;
                    var hls = new Hls();
                    hls.on(Hls.Events.MEDIA_ATTACHED, function (event, data) {
                        hls.loadSource(conf.src);
                    });
                    hls.on(Hls.Events.MANIFEST_PARSED, function (event, data) {
                        hls.startLoad(hls.config.startPosition);
                        var startLevel = _this.getQualityIndex(_this.selectedQuality);
                        hls.startLevel = startLevel;
                        hls.loadLevel = startLevel;
                    });
                    hls.attachMedia(this.videoTags[type]);
                    this.hlsEngines[type] = hls;
                };
                Flow.prototype.load = function (video) {
                    var _this = this;
                    var root = this.root.find('.fp-player');
                    root.find('img').remove();
                    this.hlsConf = jQuery.extend(this.hlsConf, this.player.conf.hlsjs, this.player.conf.clip.hlsjs, video.hlsjs);
                    if (this.cfg.secondarySources) {
                        if (this.videoTags[Flow.CONTENT])
                            this.destroyVideoTag(Flow.CONTENT);
                        var secondVideo_1 = jQuery.extend(true, {}, video);
                        secondVideo_1.src = this.cfg.secondarySources[0].src;
                        secondVideo_1.sources = this.cfg.secondarySources;
                        this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo_1);
                        this.videoTags[Flow.CONTENT].load();
                        var engine_1 = jQuery(this.videoTags[Flow.CONTENT]);
                        engine_1.addClass('vsq-content');
                        engine_1.on(this.eventName("error"), function (e) {
                            _this.handleError(Flow.CONTENT, secondVideo_1);
                        });
                        root.prepend(engine_1);
                        this.setupHLS(Flow.CONTENT, secondVideo_1);
                    }
                    if (this.videoTags[Flow.MASTER])
                        this.destroyVideoTag(Flow.MASTER);
                    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
                    this.videoTags[Flow.MASTER].load();
                    var engine = jQuery(this.videoTags[Flow.MASTER]);
                    engine.addClass('vsq-master');
                    engine.on(this.eventName("error"), function (e) {
                        _this.handleError(Flow.MASTER, video);
                    });
                    root.prepend(engine);
                    this.setupHLS(Flow.MASTER, video);
                    this.setupVideoEvents(video);
                    this.initQuality();
                };
                Flow.prototype.pause = function () {
                    this.tagCall('pause');
                };
                Flow.prototype.resume = function () {
                    this.tagCall('play');
                };
                Flow.prototype.speed = function (speed) {
                    this.tagSet('playbackRate', speed);
                    this.player.trigger('speed', [this.player, speed]);
                };
                Flow.prototype.volume = function (volume) {
                    this.tagSet('volume', volume);
                };
                Flow.prototype.unload = function () {
                    this.root.find(".vsq-quality-selector").remove();
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
                    this.tagSet('currentTime', to);
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
                Flow.prototype.initQuality = function () {
                    var _this = this;
                    if (this.cfg.labels.master.length === 0)
                        return;
                    var levels = this.cfg.labels.master.slice(0);
                    levels.unshift("Auto");
                    var html = "<ul class=\"vsq-quality-selector\">";
                    for (var i = 0; i < levels.length; ++i) {
                        var label = levels[i];
                        var active = "";
                        if ((i === 0 && this.selectedQuality === "auto") ||
                            label === this.selectedQuality)
                            active = ' class="active"';
                        html += "<li" + active + " data-quality=\"" + label.toLowerCase() + "\">" + Escape_1.default.HTML(label) + "</li>";
                    }
                    html += "</ul>";
                    this.root.find(".fp-ui").append(html);
                    this.root.on(this.eventName("click"), ".vsq-quality-selector li", function (e) {
                        e.preventDefault();
                        var choice = jQuery(e.currentTarget);
                        if (choice.hasClass("active"))
                            return;
                        _this.root.find('.vsq-quality-selector li').removeClass("active");
                        choice.addClass("active");
                        var quality = choice.attr('data-quality');
                        Tools_1.default.setToStorage(_this.configKey("quality"), quality);
                        var level = _this.getQualityIndex(quality);
                        var smooth = _this.player.conf.smoothSwitching;
                        var paused = _this.videoTags[Flow.MASTER].paused;
                        if (!paused && !smooth)
                            jQuery(_this.videoTags[Flow.MASTER]).one(_this.eventName("pause"), function () {
                                _this.root.removeClass("is-paused");
                            });
                        if (smooth && !_this.player.poster)
                            _this.hlsSet('nextLevel', level);
                        else
                            _this.hlsSet('currentLevel', level);
                        if (paused)
                            _this.tagCall('play');
                    });
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
            exports_6("default", Flow);
        }
    };
});
System.register("player/Player", ["player/Flash", "player/Flow"], function (exports_7, context_7) {
    "use strict";
    var __moduleName = context_7 && context_7.id;
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
            exports_7("default", Player);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/Player"], function (exports_8, context_8) {
    "use strict";
    var __moduleName = context_8 && context_8.id;
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
                var fcCopy = $.extend(true, {}, flashconfig);
                var lCopy = $.extend(true, {}, l);
                $(function () {
                    var cfg = new Config_1.default(pcCopy, fcCopy);
                    var loc = new Locale_1.default(lCopy);
                    var player = new Player_1.default(cfg, loc);
                    player.init();
                });
            })(jQuery);
        }
    };
});
//# sourceMappingURL=app.js.map