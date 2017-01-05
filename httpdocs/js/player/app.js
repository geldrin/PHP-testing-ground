var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
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
System.register("player/Flow/BasePlugin", ["player/Flow"], function (exports_6, context_6) {
    "use strict";
    var __moduleName = context_6 && context_6.id;
    var Flow_1, BasePlugin;
    return {
        setters: [
            function (Flow_1_1) {
                Flow_1 = Flow_1_1;
            }
        ],
        execute: function () {
            BasePlugin = (function () {
                function BasePlugin(flow) {
                    this.videoTags = [];
                    this.flow = flow;
                    this.root = flow.getRoot();
                    this.cfg = flow.getConfig();
                    this.player = flow.getPlayer();
                    this.videoTags = flow.getVideoTags();
                }
                BasePlugin.prototype.configKey = function (key) {
                    throw new Error("Override configKey");
                };
                BasePlugin.prototype.eventName = function (event) {
                    var postfix = '.' + Flow_1.Flow.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
                };
                BasePlugin.prototype.setupHLS = function (hls) {
                };
                return BasePlugin;
            }());
            exports_6("BasePlugin", BasePlugin);
        }
    };
});
System.register("player/Flow/LayoutChooser", ["player/Flow", "player/Flow/BasePlugin", "Tools"], function (exports_7, context_7) {
    "use strict";
    var __moduleName = context_7 && context_7.id;
    var Flow_2, BasePlugin_1, Tools_1, LayoutChooser;
    return {
        setters: [
            function (Flow_2_1) {
                Flow_2 = Flow_2_1;
            },
            function (BasePlugin_1_1) {
                BasePlugin_1 = BasePlugin_1_1;
            },
            function (Tools_1_1) {
                Tools_1 = Tools_1_1;
            }
        ],
        execute: function () {
            LayoutChooser = (function (_super) {
                __extends(LayoutChooser, _super);
                function LayoutChooser() {
                    var _this = _super.apply(this, arguments) || this;
                    _this.PIPCONTENT = 0;
                    _this.MASTERONLY = 1;
                    _this.SPLIT = 2;
                    _this.CONTENTONLY = 3;
                    _this.PIPMASTER = 4;
                    return _this;
                }
                LayoutChooser.prototype.init = function () {
                    var _this = this;
                    if (this.cfg.secondarySources.length === 0) {
                        this.root.addClass('vsq-singlevideo');
                        return;
                    }
                    if (this.root.find('.vsq-layoutchooser').length > 0)
                        return;
                    this.fixHeight();
                    this.player.on("fullscreen fullscreen-exit", function () { _this.fixHeight(); });
                    this.setupRatios();
                    this.setupHTML();
                    this.trigger();
                };
                LayoutChooser.prototype.destroy = function () {
                    this.root.find(".vsq-layoutchooser").remove();
                };
                LayoutChooser.prototype.configKey = function (key) {
                    return 'vsq-player-layout-' + key;
                };
                LayoutChooser.prototype.trigger = function (newVal) {
                    var ratio = this.root.find('.vsq-layoutchooser input[name="ratio"]');
                    if (newVal != null)
                        ratio.val(newVal);
                    ratio.change();
                };
                LayoutChooser.prototype.fixHeight = function () {
                    var maxHeight;
                    if (this.root.hasClass('is-fullscreen'))
                        maxHeight = jQuery(window).height();
                    else
                        maxHeight = this.root.height();
                    jQuery(this.videoTags).css("maxHeight", maxHeight + 'px');
                };
                LayoutChooser.prototype.setupRatios = function () {
                    var maxRatio = 300;
                    var singleRatio = Math.floor(maxRatio * 0.034);
                    var pipRatio = Math.floor((maxRatio - singleRatio * 2) * 0.25);
                    var splitRatio = Math.floor(maxRatio - singleRatio * 2 - pipRatio * 2);
                    this.ranges = [
                        {
                            'from': 0,
                            'to': pipRatio,
                            'type': 'pipContent'
                        },
                        {
                            'from': pipRatio,
                            'to': pipRatio + singleRatio,
                            'type': 'masterOnly'
                        },
                        {
                            'from': pipRatio + singleRatio,
                            'to': pipRatio + singleRatio + splitRatio,
                            'type': 'split'
                        },
                        {
                            'from': pipRatio + singleRatio + splitRatio,
                            'to': pipRatio + singleRatio + splitRatio + singleRatio,
                            'type': 'contentOnly'
                        },
                        {
                            'from': pipRatio + singleRatio + splitRatio + singleRatio,
                            'to': pipRatio + singleRatio + splitRatio + singleRatio + pipRatio,
                            'type': 'pipMaster'
                        }
                    ];
                };
                LayoutChooser.prototype.getDefaultRatio = function () {
                    return Math.floor(this.ranges[this.ranges.length - 1].to / 2);
                };
                LayoutChooser.prototype.getMaxRatio = function () {
                    return this.ranges[this.ranges.length - 1].to - 1;
                };
                LayoutChooser.prototype.getMiddleRange = function (ix) {
                    var prevTo = 0;
                    if (ix !== 0)
                        prevTo = this.ranges[ix - 1].to;
                    var range = this.ranges[ix];
                    return '' + (prevTo + Math.floor((range.to - range.from) / 2));
                };
                LayoutChooser.prototype.setupHTML = function () {
                    var _this = this;
                    var ratio = 0 + Tools_1.default.getFromStorage(this.configKey("layoutRatio"), this.getDefaultRatio());
                    var max = this.getMaxRatio();
                    var html = "\n      <div class=\"vsq-layoutchooser\">\n        <input name=\"ratio\" type=\"range\" min=\"0\" max=\"" + max + "\" step=\"1\" value=\"" + ratio + "\"/>\n        <ul>\n          <li class=\"pip-content\">PiP content</li>\n          <li class=\"master-only\">Master only</li>\n          <li class=\"split\">Split</li>\n          <li class=\"content-only\">Content only</li>\n          <li class=\"pip-master\">PiP master</li>\n        </ul>\n      </div>\n    ";
                    this.root.find(".fp-ui").append(html);
                    this.root.on("click", ".vsq-layoutchooser .pip-content", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(_this.PIPCONTENT));
                    });
                    this.root.on("click", ".vsq-layoutchooser .master-only", function (e) {
                        e.preventDefault();
                        _this.trigger('' + _this.ranges[_this.MASTERONLY].from);
                    });
                    this.root.on("click", ".vsq-layoutchooser .split", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(_this.SPLIT));
                    });
                    this.root.on("click", ".vsq-layoutchooser .content-only", function (e) {
                        e.preventDefault();
                        _this.trigger('' + _this.ranges[_this.CONTENTONLY].from);
                    });
                    this.root.on("click", ".vsq-layoutchooser .pip-master", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(_this.PIPMASTER));
                    });
                    this.root.on("input change", '.vsq-layoutchooser input[name="ratio"]', function (e) {
                        _this.onChange(e);
                    });
                };
                LayoutChooser.prototype.getRangeForValue = function (val) {
                    for (var i = this.ranges.length - 1; i >= 0; i--) {
                        var range = this.ranges[i];
                        if (val < range.from || val >= range.to)
                            continue;
                        var normalVal = val - range.to;
                        var magnitude = range.from - range.to;
                        return {
                            'percent': (normalVal / magnitude) * 100,
                            'type': range.type
                        };
                    }
                    throw new Error("Impossible");
                };
                LayoutChooser.prototype.onChange = function (e) {
                    var elem = jQuery(e.currentTarget);
                    var val = parseInt(elem.val(), 10);
                    var masterWidth = 50;
                    var contentWidth = 50;
                    var masterOnTop = true;
                    Tools_1.default.setToStorage(this.configKey("layoutRatio"), val);
                    if (val < 0 || val > this.getMaxRatio())
                        throw new Error("Invalid value for layoutchooser");
                    var info = this.getRangeForValue(val);
                    switch (info.type) {
                        case "pipContent":
                            masterWidth = 100;
                            contentWidth = info.percent;
                            masterOnTop = false;
                            break;
                        case "masterOnly":
                            masterWidth = 100;
                            contentWidth = 0;
                            masterOnTop = true;
                            break;
                        case "split":
                            masterWidth = info.percent;
                            contentWidth = 100 - masterWidth;
                            masterOnTop = null;
                            break;
                        case "contentOnly":
                            masterWidth = 0;
                            contentWidth = 100;
                            masterOnTop = false;
                            break;
                        case "pipMaster":
                            masterWidth = 100 - info.percent;
                            contentWidth = 100;
                            masterOnTop = true;
                            break;
                    }
                    var masterLeft = 0;
                    var masterRight = "auto";
                    if (!this.cfg.contentOnRight) {
                        masterLeft = "auto";
                        masterRight = 0;
                    }
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
                    var master = jQuery(this.videoTags[Flow_2.Flow.MASTER]);
                    var content = jQuery(this.videoTags[Flow_2.Flow.CONTENT]);
                    master.css({
                        width: masterWidth + '%',
                        zIndex: masterZ,
                        left: masterLeft,
                        right: masterRight
                    });
                    content.css({
                        width: contentWidth + '%',
                        zIndex: contentZ
                    });
                };
                return LayoutChooser;
            }(BasePlugin_1.BasePlugin));
            exports_7("default", LayoutChooser);
        }
    };
});
System.register("player/Flow/QualityChooser", ["player/Flow", "player/Flow/BasePlugin", "Tools", "Escape"], function (exports_8, context_8) {
    "use strict";
    var __moduleName = context_8 && context_8.id;
    var Flow_3, BasePlugin_2, Tools_2, Escape_1, QualityChooser;
    return {
        setters: [
            function (Flow_3_1) {
                Flow_3 = Flow_3_1;
            },
            function (BasePlugin_2_1) {
                BasePlugin_2 = BasePlugin_2_1;
            },
            function (Tools_2_1) {
                Tools_2 = Tools_2_1;
            },
            function (Escape_1_1) {
                Escape_1 = Escape_1_1;
            }
        ],
        execute: function () {
            QualityChooser = (function (_super) {
                __extends(QualityChooser, _super);
                function QualityChooser(flow) {
                    var _this = _super.call(this, flow) || this;
                    _this.selectedQuality = _this.getDefaultQuality();
                    return _this;
                }
                QualityChooser.prototype.init = function () {
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
                        Tools_2.default.setToStorage(_this.configKey("quality"), quality);
                        var level = _this.getQualityIndex(quality);
                        var smooth = _this.player.conf.smoothSwitching;
                        var paused = _this.videoTags[Flow_3.Flow.MASTER].paused;
                        if (!paused && !smooth)
                            jQuery(_this.videoTags[Flow_3.Flow.MASTER]).one(_this.eventName("pause"), function () {
                                _this.root.removeClass("is-paused");
                            });
                        if (smooth && !_this.player.poster)
                            _this.flow.hlsSet('nextLevel', level);
                        else
                            _this.flow.hlsSet('currentLevel', level);
                        if (paused)
                            _this.flow.tagCall('play');
                    });
                };
                QualityChooser.prototype.destroy = function () {
                    this.root.find(".vsq-quality-selector").remove();
                };
                QualityChooser.prototype.setupHLS = function (hls) {
                    var _this = this;
                    hls.on(Hls.Events.MANIFEST_PARSED, function (event, data) {
                        hls.startLoad(hls.config.startPosition);
                        var startLevel = _this.getQualityIndex(_this.selectedQuality);
                        hls.startLevel = startLevel;
                        hls.loadLevel = startLevel;
                    });
                };
                QualityChooser.prototype.getQualityIndex = function (quality) {
                    for (var i = this.cfg.labels.master.length - 1; i >= 0; i--) {
                        var label = this.cfg.labels.master[i];
                        if (label === quality)
                            return i;
                    }
                    return -1;
                };
                QualityChooser.prototype.getDefaultQuality = function () {
                    return Tools_2.default.getFromStorage(this.configKey("quality"), "auto");
                };
                QualityChooser.prototype.configKey = function (key) {
                    return 'vsq-player-qualitychooser-' + key;
                };
                return QualityChooser;
            }(BasePlugin_2.BasePlugin));
            exports_8("default", QualityChooser);
        }
    };
});
System.register("player/Flow", ["player/Flow/LayoutChooser", "player/Flow/QualityChooser"], function (exports_9, context_9) {
    "use strict";
    var __moduleName = context_9 && context_9.id;
    var LayoutChooser_1, QualityChooser_1, Flow;
    return {
        setters: [
            function (LayoutChooser_1_1) {
                LayoutChooser_1 = LayoutChooser_1_1;
            },
            function (QualityChooser_1_1) {
                QualityChooser_1 = QualityChooser_1_1;
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
                    this.mse = MediaSource || WebKitMediaSource;
                    this.maxLevel = 0;
                    this.plugins = [];
                    Flow.log("constructor", arguments);
                    this.player = player;
                    this.cfg = player.conf.vsq || {};
                    this.hlsConf = jQuery.extend({
                        bufferWhilePaused: true,
                        smoothSwitching: true,
                        recoverMediaError: true
                    }, flowplayer.conf['hlsjs'], this.player.conf['hlsjs'], this.player.conf['clip']['hlsjs']);
                    Flow.debug = !!this.cfg.debug;
                    this.root = jQuery(root);
                    this.id = this.root.attr('data-flowplayer-instance-id');
                    if (!this.cfg.contentOnRight)
                        this.root.addClass('vsq-contentleft');
                    this.plugins.push(new LayoutChooser_1.default(this));
                    this.plugins.push(new QualityChooser_1.default(this));
                }
                Flow.prototype.getRoot = function () {
                    return this.root;
                };
                Flow.prototype.getConfig = function () {
                    return this.cfg;
                };
                Flow.prototype.getPlayer = function () {
                    return this.player;
                };
                Flow.prototype.getVideoTags = function () {
                    return this.videoTags;
                };
                Flow.prototype.hideFlowLogo = function () {
                    this.root.children('a[href*="flowplayer.org"]').hide();
                };
                Flow.prototype.configKey = function (key) {
                    return 'vsq-player-' + key;
                };
                Flow.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    if (!Flow.debug)
                        return;
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
                    if (this.player.live)
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
                    this.hideFlowLogo();
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
                    var hls = new Hls({
                        initialLiveManifestSize: 2
                    });
                    hls.on(Hls.Events.MEDIA_ATTACHED, function (event, data) {
                        hls.loadSource(video.src);
                    });
                    hls.on(Hls.Events.ERROR, function (event, err) {
                        if (err.type !== Hls.ErrorTypes.NETWORK_ERROR)
                            return;
                        if (err.response == null || err.response.code !== 403)
                            return;
                        var arg = { code: 2 };
                        _this.player.trigger("error", [_this.player, arg]);
                    });
                    hls.attachMedia(this.videoTags[type]);
                    this.hlsEngines[type] = hls;
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].setupHLS(hls);
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
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].init();
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
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].destroy();
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
                Flow.setup = function () {
                    if (Flow.initDone)
                        return;
                    var proxy = function (player, root) {
                        return new Flow(player, root);
                    };
                    proxy.engineName = Flow.engineName;
                    proxy.canPlay = Flow.canPlay;
                    flowplayer.engines.unshift(proxy);
                    Flow.initDone = true;
                };
                return Flow;
            }());
            Flow.engineName = "vsq";
            Flow.debug = false;
            Flow.initDone = false;
            Flow.MASTER = 0;
            Flow.CONTENT = 1;
            exports_9("Flow", Flow);
        }
    };
});
System.register("player/Player", ["player/Flash", "player/Flow"], function (exports_10, context_10) {
    "use strict";
    var __moduleName = context_10 && context_10.id;
    var Flash_1, Flow_4, Player;
    return {
        setters: [
            function (Flash_1_1) {
                Flash_1 = Flash_1_1;
            },
            function (Flow_4_1) {
                Flow_4 = Flow_4_1;
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
                    Flow_4.Flow.setup();
                };
                return Player;
            }());
            exports_10("default", Player);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/Player"], function (exports_11, context_11) {
    "use strict";
    var __moduleName = context_11 && context_11.id;
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