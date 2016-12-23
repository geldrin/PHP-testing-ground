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
                    return "";
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
                    return Escape.elem.textContent || "";
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
                    this.loadedCount = 0;
                    this.longerType = 0;
                    this.videoTags = [];
                    this.videoInfo = [];
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
                    Flow.log.apply(Flow, params);
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
                Flow.prototype.syncVideos = function () {
                    if (this.cfg.secondarySources.length === 0)
                        return;
                    var master = this.videoTags[Flow.MASTER];
                    var content = this.videoTags[Flow.CONTENT];
                    if (master.currentTime == 0 || master.currentTime >= master.duration)
                        return;
                    if (content.currentTime == 0 || content.currentTime >= content.duration)
                        return;
                    if (Math.abs(master.currentTime - content.currentTime) > 0.5) {
                        this.log("syncing videos to master");
                        content.currentTime = master.currentTime;
                    }
                };
                Flow.prototype.handleLoadedData = function (e) {
                    this.loadedCount++;
                    var vidCount = 1 + this.cfg.secondarySources.length;
                    if (this.loadedCount != vidCount) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    if (vidCount > 1 &&
                        this.videoTags[Flow.CONTENT].duration > this.videoTags[Flow.MASTER].duration)
                        this.longerType = Flow.CONTENT;
                    var tag = this.videoTags[this.longerType];
                    var data = jQuery.extend(this.player.video, {
                        duration: this.cfg.duration,
                        seekable: tag.seekable.end(0),
                        width: tag.videoWidth,
                        height: tag.videoHeight,
                        url: this.videoInfo[Flow.MASTER].src
                    });
                    this.triggerPlayer("ready", data);
                    return false;
                };
                Flow.prototype.handlePlay = function (e) {
                    var tag = e.currentTarget;
                    if (tag.currentTime >= tag.duration)
                        tag.currentTime = 0;
                    var type = this.getTypeFromEvent(e);
                    if (type === Flow.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused)
                        this.hlsCall('startLoad', [tag.currentTime]);
                    this.triggerPlayer("resume", undefined);
                };
                Flow.prototype.handlePause = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === Flow.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused)
                        this.hlsCall('stopLoad');
                    this.triggerPlayer("pause", undefined);
                };
                Flow.prototype.handleEnded = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type !== this.longerType) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var video = this.player.video;
                    var tag = this.videoTags[this.longerType];
                    this.hlsCall('trigger', [
                        Hls.Events.BUFFER_FLUSHING,
                        {
                            startOffset: 0,
                            endOffset: this.cfg.duration
                        }
                    ]);
                    this.tagCall('pause');
                    this.triggerPlayer("finish", undefined);
                };
                Flow.prototype.handleProgress = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type !== this.longerType) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = this.videoTags[this.longerType];
                    var buffer = 0;
                    try {
                        var buffered = tag.buffered;
                        buffer = buffered.end(0);
                        if (tag.currentTime) {
                            for (var i = buffered.length - 1; i >= 0; i--) {
                                var buffend = buffered.end(i);
                                if (buffend >= tag.currentTime)
                                    buffer = buffend;
                            }
                        }
                    }
                    catch (_) { }
                    ;
                    this.player.video.buffer = buffer;
                    this.triggerPlayer("buffer", buffer);
                };
                Flow.prototype.handleRateChange = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === Flow.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerPlayer("speed", tag.playbackRate);
                };
                Flow.prototype.handleSeeked = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === Flow.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused && tag.paused) {
                        this.hlsCall('stopLoad');
                        this.tagCall('pause');
                    }
                    this.triggerPlayer("seek", tag.currentTime);
                    return false;
                };
                Flow.prototype.handleTimeUpdate = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type !== this.longerType) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = this.videoTags[this.longerType];
                    this.triggerPlayer("progress", tag.currentTime);
                    this.syncVideos();
                };
                Flow.prototype.handleVolumeChange = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === Flow.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerPlayer("volume", tag.volume);
                };
                Flow.prototype.handleError = function (e) {
                    e.stopImmediatePropagation();
                    var MEDIA_ERR_NETWORK = 2;
                    var MEDIA_ERR_DECODE = 3;
                    var type = this.getTypeFromEvent(e);
                    var err = this.videoTags[type].error.code;
                    if ((this.hlsConf.recoverMediaError && err === MEDIA_ERR_DECODE) ||
                        (this.hlsConf.recoverNetworkError && err === MEDIA_ERR_NETWORK) ||
                        (this.hlsConf.recover && (err === MEDIA_ERR_NETWORK || err === MEDIA_ERR_DECODE))) {
                        this.root.removeClass('is-paused');
                        this.root.addClass('is-seeking');
                        var hls = this.hlsEngines[type];
                        if (err === MEDIA_ERR_NETWORK) {
                            hls.startLoad();
                            return false;
                        }
                        var now = performance.now();
                        if (!this.recoverMediaErrorDate || now - this.recoverMediaErrorDate > 3000) {
                            this.recoverMediaErrorDate = performance.now();
                            hls.recoverMediaError();
                            return false;
                        }
                        else {
                            if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
                                this.swapAudioCodecDate = performance.now();
                                hls.swapAudioCodec();
                                hls.recoverMediaError();
                                return false;
                            }
                            else
                                err = MEDIA_ERR_DECODE;
                        }
                    }
                    var arg = { code: err };
                    if (err > MEDIA_ERR_NETWORK)
                        arg.video = jQuery.extend(this.videoInfo[type], { url: this.videoInfo[type].src });
                    this.player.trigger("error", [this.player, arg]);
                };
                Flow.prototype.triggerPlayer = function (event, data) {
                    this.log("[flow event]", event, data);
                    this.player.trigger(event, [this.player, data]);
                };
                Flow.prototype.getTypeFromEvent = function (e) {
                    var t = jQuery(e.currentTarget);
                    if (!t.is('.vsq-master, .vsq-content'))
                        throw new Error("Unknown event target");
                    if (t.is('.vsq-master'))
                        return Flow.MASTER;
                    return Flow.CONTENT;
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
                        sources.on(videoEvent, function (e) {
                            _this.log("event", videoEvent, flowEvent, e);
                            switch (videoEvent) {
                                case "loadeddata.vsq":
                                    return _this.handleLoadedData(e);
                                case "play.vsq":
                                    return _this.handlePlay(e);
                                case "pause.vsq":
                                    return _this.handlePause(e);
                                case "ended.vsq":
                                    return _this.handleEnded(e);
                                case "progress.vsq":
                                    return _this.handleProgress(e);
                                case "ratechange.vsq":
                                    return _this.handleRateChange(e);
                                case "seeked.vsq":
                                    return _this.handleSeeked(e);
                                case "timeupdate.vsq":
                                    return _this.handleTimeUpdate(e);
                                case "volumechange.vsq":
                                    return _this.handleVolumeChange(e);
                                case "error.vsq":
                                    return _this.handleError(e);
                                default:
                                    throw new Error("unhandled event: " + videoEvent);
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
                };
                Flow.prototype.eventName = function (event) {
                    var postfix = '.' + Flow.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
                };
                Flow.prototype.createVideoTag = function (video) {
                    var ret = document.createElement('video');
                    ret.src = video.src;
                    ret.className = 'fp-engine vsq-engine';
                    ret.setAttribute('type', this.getType(video.type));
                    if (this.cfg.autoplay) {
                        ret.autoplay = true;
                        ret.setAttribute('autoplay', 'autoplay');
                    }
                    else
                        ret.autoplay = false;
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
                Flow.prototype.setupHLS = function (type) {
                    var _this = this;
                    var video = this.videoInfo[type];
                    var hls = new Hls();
                    hls.on(Hls.Events.MEDIA_ATTACHED, function (event, data) {
                        hls.loadSource(video.src);
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
                    if (this.cfg.secondarySources.length !== 0) {
                        if (this.videoTags[Flow.CONTENT])
                            this.destroyVideoTag(Flow.CONTENT);
                        var secondVideo = jQuery.extend(true, {}, video);
                        secondVideo.src = this.cfg.secondarySources[0].src;
                        secondVideo.sources = this.cfg.secondarySources;
                        this.videoInfo[Flow.CONTENT] = secondVideo;
                        this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
                        this.videoTags[Flow.CONTENT].load();
                        var engine_1 = jQuery(this.videoTags[Flow.CONTENT]);
                        engine_1.addClass('vsq-content');
                        root.prepend(engine_1);
                        this.setupHLS(Flow.CONTENT);
                    }
                    if (this.videoTags[Flow.MASTER])
                        this.destroyVideoTag(Flow.MASTER);
                    this.videoInfo[Flow.MASTER] = video;
                    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
                    this.videoTags[Flow.MASTER].load();
                    var engine = jQuery(this.videoTags[Flow.MASTER]);
                    engine.addClass('vsq-master');
                    root.prepend(engine);
                    this.setupHLS(Flow.MASTER);
                    this.player.on(this.eventName("error"), function () {
                        _this.unload();
                    });
                    this.setupVideoEvents(video);
                    this.initQuality();
                    this.initLayoutChooser();
                    if (this.cfg.autoplay)
                        this.tagCall("play");
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
                Flow.prototype.initLayoutChooser = function () {
                    var _this = this;
                    if (this.cfg.secondarySources.length === 0) {
                        this.root.addClass('vsq-singlevideo');
                        return;
                    }
                    if (this.root.find('.vsq-layoutchooser').length > 0)
                        return;
                    var trigger = function (newVal) {
                        var ratio = _this.root.find('.vsq-layoutchooser input[name="ratio"]');
                        if (newVal != null)
                            ratio.val(newVal);
                        ratio.change();
                    };
                    this.player.on("fullscreen fullscreen-exit", function () {
                        var maxHeight;
                        if (_this.root.hasClass('is-fullscreen'))
                            maxHeight = jQuery(window).height();
                        else
                            maxHeight = _this.root.height();
                        jQuery(_this.videoTags).css("maxHeight", maxHeight + 'px');
                    }).trigger('fullscreen-exit');
                    var ratio = 0 + Tools_1.default.getFromStorage(this.configKey("layoutRatio"), 150);
                    var html = "\n      <div class=\"vsq-layoutchooser\">\n        <input name=\"ratio\" type=\"range\" min=\"0\" max=\"300\" step=\"1\" value=\"" + ratio + "\"/>\n        <ul>\n          <li class=\"pip-content\">PiP content</li>\n          <li class=\"master-only\">Master only</li>\n          <li class=\"split\">Split</li>\n          <li class=\"content-only\">Content only</li>\n          <li class=\"pip-master\">PiP master</li>\n        </ul>\n      </div>\n    ";
                    this.root.find(".fp-ui").append(html);
                    this.root.on("click", ".vsq-layoutchooser .pip-content", function (e) {
                        e.preventDefault();
                        trigger('40');
                    });
                    this.root.on("click", ".vsq-layoutchooser .master-only", function (e) {
                        e.preventDefault();
                        trigger('80');
                    });
                    this.root.on("click", ".vsq-layoutchooser .split", function (e) {
                        e.preventDefault();
                        trigger('150');
                    });
                    this.root.on("click", ".vsq-layoutchooser .content-only", function (e) {
                        e.preventDefault();
                        trigger('190');
                    });
                    this.root.on("click", ".vsq-layoutchooser .pip-master", function (e) {
                        e.preventDefault();
                        trigger('260');
                    });
                    this.root.on("input change", '.vsq-layoutchooser input[name="ratio"]', function (e) {
                        var elem = jQuery(e.currentTarget);
                        var val = parseInt(elem.val(), 10);
                        var masterWidth = 50;
                        var contentWidth = 50;
                        var masterOnTop = true;
                        Tools_1.default.setToStorage(_this.configKey("layoutRatio"), val);
                        if (val < 0 || val > 300)
                            throw new Error("Invalid value for layoutchooser");
                        if (val >= 0 && val < 80) {
                            masterWidth = 100;
                            contentWidth = (val / 80) * 100;
                            masterOnTop = false;
                        }
                        if (val >= 80 && val < 110) {
                            masterWidth = 100;
                            contentWidth = 0;
                            masterOnTop = true;
                        }
                        if (val >= 110 && val < 190) {
                            var n = val - 110;
                            masterWidth = (n / 80) * 100;
                            contentWidth = 100 - masterWidth;
                            masterOnTop = null;
                        }
                        if (val >= 190 && val < 220) {
                            masterWidth = 0;
                            contentWidth = 100;
                            masterOnTop = false;
                        }
                        if (val >= 220 && val < 300) {
                            var n = val - 220;
                            masterWidth = (n / 80) * 100;
                            contentWidth = 100;
                            masterOnTop = true;
                        }
                        var masterLeft = 0;
                        var masterRight = "auto";
                        var contentLeft = "auto";
                        var contentRight = 0;
                        var masterZ = 10;
                        var contentZ = 9;
                        if (masterOnTop === false) {
                            masterLeft = "auto";
                            masterRight = 0;
                            masterZ = 9;
                            contentZ = 10;
                        }
                        if (masterOnTop === true) {
                            masterLeft = 0;
                            masterRight = "auto";
                        }
                        var master = jQuery(_this.videoTags[Flow.MASTER]);
                        var content = jQuery(_this.videoTags[Flow.CONTENT]);
                        master.css({
                            width: masterWidth + '%',
                            zIndex: masterZ,
                            left: masterLeft,
                            right: masterRight
                        });
                        content.css({
                            width: contentWidth + '%',
                            zIndex: contentZ,
                            left: contentLeft,
                            right: contentRight
                        });
                    });
                    trigger();
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