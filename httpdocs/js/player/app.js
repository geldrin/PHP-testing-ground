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
System.register("player/VSQ/BasePlugin", ["player/VSQ"], function (exports_6, context_6) {
    "use strict";
    var __moduleName = context_6 && context_6.id;
    var VSQ_1, BasePlugin;
    return {
        setters: [
            function (VSQ_1_1) {
                VSQ_1 = VSQ_1_1;
            }
        ],
        execute: function () {
            BasePlugin = (function () {
                function BasePlugin(vsq) {
                    this.vsq = vsq;
                    this.root = vsq.getRoot();
                    this.cfg = vsq.getConfig();
                    this.flow = vsq.getPlayer();
                }
                BasePlugin.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    if (!VSQ_1.VSQ.debug)
                        return;
                    params.unshift("[" + this.pluginName + "]");
                    console.log.apply(console, params);
                };
                BasePlugin.prototype.configKey = function (key) {
                    throw new Error("Override configKey");
                };
                BasePlugin.prototype.eventName = function (event) {
                    var postfix = '.' + VSQ_1.VSQ.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
                };
                BasePlugin.prototype.setupHLS = function (hls, type) {
                };
                return BasePlugin;
            }());
            exports_6("BasePlugin", BasePlugin);
        }
    };
});
System.register("player/VSQ/LayoutChooser", ["player/VSQ", "player/VSQ/BasePlugin", "Tools"], function (exports_7, context_7) {
    "use strict";
    var __moduleName = context_7 && context_7.id;
    var VSQ_2, BasePlugin_1, Tools_1, LayoutChooser;
    return {
        setters: [
            function (VSQ_2_1) {
                VSQ_2 = VSQ_2_1;
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
                    _this.pluginName = "LayoutChooser";
                    _this.PIPCONTENT = 0;
                    _this.MASTERONLY = 1;
                    _this.SPLIT = 2;
                    _this.CONTENTONLY = 3;
                    _this.PIPMASTER = 4;
                    return _this;
                }
                LayoutChooser.prototype.load = function () {
                    var _this = this;
                    if (!this.vsq.hasMultipleVideos()) {
                        this.root.addClass('vsq-singlevideo');
                        return;
                    }
                    if (this.root.find('.vsq-layoutchooser').length > 0)
                        return;
                    this.fixHeight();
                    this.flow.on("fullscreen fullscreen-exit", function () { _this.fixHeight(); });
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
                    jQuery(this.vsq.getVideoTags()).css("maxHeight", maxHeight + 'px');
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
                            'percent': normalVal / magnitude,
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
                            contentWidth = info.percent * 50;
                            masterOnTop = false;
                            break;
                        case "masterOnly":
                            masterWidth = 100;
                            contentWidth = 0;
                            masterOnTop = true;
                            break;
                        case "split":
                            masterWidth = info.percent * 100;
                            contentWidth = 100 - masterWidth;
                            masterOnTop = null;
                            break;
                        case "contentOnly":
                            masterWidth = 0;
                            contentWidth = 100;
                            masterOnTop = false;
                            break;
                        case "pipMaster":
                            masterWidth = 50 - (info.percent * 50);
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
                    var tags = this.vsq.getVideoTags();
                    var master = jQuery(tags[VSQ_2.VSQ.MASTER]);
                    var content = jQuery(tags[VSQ_2.VSQ.CONTENT]);
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
System.register("player/VSQ/QualityChooser", ["player/VSQ", "player/VSQ/BasePlugin", "Tools", "Escape"], function (exports_8, context_8) {
    "use strict";
    var __moduleName = context_8 && context_8.id;
    var VSQ_3, BasePlugin_2, Tools_2, Escape_1, QualityChooser;
    return {
        setters: [
            function (VSQ_3_1) {
                VSQ_3 = VSQ_3_1;
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
                function QualityChooser(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "QualityChooser";
                    _this.selectedQuality = _this.getDefaultQuality();
                    _this.root.on(_this.eventName("click"), ".vsq-quality-selector li", function (e) {
                        _this.onClick(e);
                    });
                    return _this;
                }
                QualityChooser.prototype.shouldLookAtSecondary = function () {
                    var shouldLookAtSecondary = false;
                    if (!this.vsq.introOrOutro && this.vsq.hasMultipleVideos())
                        shouldLookAtSecondary = true;
                    return shouldLookAtSecondary;
                };
                QualityChooser.prototype.getLevels = function () {
                    if (!this.shouldLookAtSecondary())
                        return this.vsq.getVideoInfo(VSQ_3.VSQ.MASTER)['vsq-labels'].slice(0);
                    if (this.vsq.longerType === VSQ_3.VSQ.CONTENT)
                        return this.vsq.getVideoInfo(VSQ_3.VSQ.CONTENT)['vsq-labels'].slice(0);
                    return this.vsq.getVideoInfo(VSQ_3.VSQ.MASTER)['vsq-labels'].slice(0);
                };
                QualityChooser.prototype.onClick = function (e) {
                    var _this = this;
                    e.preventDefault();
                    var choice = jQuery(e.currentTarget);
                    if (choice.hasClass("active"))
                        return;
                    this.root.find('.vsq-quality-selector li').removeClass("active");
                    choice.addClass("active");
                    var quality = choice.attr('data-quality');
                    Tools_2.default.setToStorage(this.configKey("quality"), quality);
                    var masterLevel = this.getQualityIndex(VSQ_3.VSQ.MASTER, quality);
                    var smooth = this.flow.conf.smoothSwitching;
                    var tags = this.vsq.getVideoTags();
                    var paused = tags[VSQ_3.VSQ.MASTER].paused;
                    if (!paused && !smooth)
                        jQuery(tags[VSQ_3.VSQ.MASTER]).one(this.eventName("pause"), function () {
                            _this.root.removeClass("is-paused");
                        });
                    var hlsMethod = 'currentLevel';
                    if (smooth && !this.flow.poster)
                        hlsMethod = 'nextLevel';
                    this.setLevelsForQuality(quality, hlsMethod);
                    if (paused)
                        this.vsq.tagCall('play');
                };
                QualityChooser.prototype.load = function () {
                    var levels = this.getLevels();
                    this.log('qualities: ', levels);
                    levels.unshift("Auto");
                    this.root.find('.vsq-quality-selector').remove();
                    var html = "<ul class=\"vsq-quality-selector\">";
                    for (var i = 0; i < levels.length; ++i) {
                        var label = levels[i];
                        var active = "";
                        if ((i === 0 && this.selectedQuality === "auto") ||
                            label === this.selectedQuality)
                            active = ' class="active"';
                        html += "<li" + active + " data-level=\"" + (i - 1) + "\" data-quality=\"" + label.toLowerCase() + "\">" + Escape_1.default.HTML(label) + "</li>";
                    }
                    html += "</ul>";
                    this.root.find(".fp-ui").append(html);
                };
                QualityChooser.prototype.destroy = function () {
                    this.root.find(".vsq-quality-selector").remove();
                };
                QualityChooser.prototype.setupHLS = function (hls, type) {
                    var _this = this;
                    hls.on(Hls.Events.MANIFEST_PARSED, function (event, data) {
                        var startLevel = _this.getQualityIndex(type, _this.selectedQuality);
                        _this.log('manifest parsed for type: ', type, ' startLevel: ', startLevel);
                        hls.startLevel = startLevel;
                        hls.loadLevel = startLevel;
                        hls.startLoad(hls.config.startPosition);
                    });
                    if (type !== VSQ_3.VSQ.MASTER)
                        return;
                    hls.on(Hls.Events.LEVEL_SWITCH, function (event, data) {
                        _this.root.find('.vsq-quality-selector li').removeClass("current");
                        var elem = _this.findQualityElem(data.level);
                        elem.addClass("current");
                    });
                };
                QualityChooser.prototype.findQualityElem = function (level) {
                    var ret = this.root.find('.vsq-quality-selector li[data-level="' + level + '"]');
                    if (ret.length === 0)
                        throw new Error("No element found with the given level: " + level);
                    return ret;
                };
                QualityChooser.prototype.setLevelsForQuality = function (quality, method) {
                    var engines = this.vsq.getHLSEngines();
                    var masterLevel = this.getQualityIndex(VSQ_3.VSQ.MASTER, quality);
                    this.log('setting master video level to', masterLevel, quality);
                    engines[VSQ_3.VSQ.MASTER][method] = masterLevel;
                    if (!this.shouldLookAtSecondary())
                        return;
                    var secondaryLevel = this.getQualityIndex(VSQ_3.VSQ.CONTENT, quality);
                    this.log('setting content video level to', secondaryLevel, quality);
                    engines[VSQ_3.VSQ.CONTENT][method] = secondaryLevel;
                };
                QualityChooser.prototype.getQualityIndex = function (type, quality) {
                    if (type === VSQ_3.VSQ.MASTER)
                        return this.getMasterQualityIndex(quality);
                    var masterLevel = this.getMasterQualityIndex(quality);
                    return this.getLevelForSecondary(masterLevel);
                };
                QualityChooser.prototype.getMasterQualityIndex = function (quality) {
                    var labels = this.vsq.getVideoInfo(VSQ_3.VSQ.MASTER)['vsq-labels'];
                    for (var i = labels.length - 1; i >= 0; i--) {
                        var label = labels[i];
                        if (label === quality)
                            return i;
                    }
                    return -1;
                };
                QualityChooser.prototype.getLevelForSecondary = function (masterLevel) {
                    var labels = this.vsq.getVideoInfo(VSQ_3.VSQ.CONTENT)['vsq-labels'];
                    if (labels.length <= masterLevel)
                        return labels.length - 1;
                    return masterLevel;
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
System.register("RateLimiter", [], function (exports_9, context_9) {
    "use strict";
    var __moduleName = context_9 && context_9.id;
    var Limit, Limits, RateLimiter;
    return {
        setters: [],
        execute: function () {
            Limit = (function () {
                function Limit(name, duration, callback) {
                    this.name = name;
                    this.duration = duration;
                    this.callback = callback;
                }
                Limit.prototype.call = function () {
                    this.timer = null;
                    this.lastTriggerDate = performance.now();
                    if (this.callback instanceof Function)
                        this.callback();
                };
                Limit.prototype.enqueue = function () {
                    var _this = this;
                    if (this.timer !== null)
                        return;
                    this.timer = setTimeout(function () { return _this.call(); }, this.duration);
                };
                Limit.prototype.trigger = function () {
                    var now = performance.now();
                    if (now - this.lastTriggerDate < this.duration) {
                        this.enqueue();
                        return false;
                    }
                    this.call();
                    return true;
                };
                Limit.prototype.cancel = function () {
                    if (this.timer === null)
                        return;
                    clearTimeout(this.timer);
                    this.timer = null;
                };
                return Limit;
            }());
            Limits = (function () {
                function Limits() {
                }
                return Limits;
            }());
            RateLimiter = (function () {
                function RateLimiter() {
                    this.limits = new Limits();
                }
                RateLimiter.prototype.getByName = function (name) {
                    var limit = this.limits[name];
                    if (limit == null)
                        throw new Error("Limiter for " + name + " not found!");
                    return limit;
                };
                RateLimiter.prototype.add = function (name, duration, callback) {
                    this.limits[name] = new Limit(name, duration, callback);
                };
                RateLimiter.prototype.trigger = function (name) {
                    var limit = this.getByName(name);
                    return limit.trigger();
                };
                RateLimiter.prototype.cancel = function (name) {
                    if (name != null) {
                        this.getByName(name).cancel();
                        return;
                    }
                    for (var k in this.limits) {
                        if (!this.limits.hasOwnProperty(k))
                            continue;
                        var limit = this.limits[k];
                        limit.cancel();
                    }
                };
                return RateLimiter;
            }());
            RateLimiter.SECOND = 1000;
            exports_9("default", RateLimiter);
        }
    };
});
System.register("player/VSQHLS", ["RateLimiter"], function (exports_10, context_10) {
    "use strict";
    var __moduleName = context_10 && context_10.id;
    var RateLimiter_1, VSQHLS;
    return {
        setters: [
            function (RateLimiter_1_1) {
                RateLimiter_1 = RateLimiter_1_1;
            }
        ],
        execute: function () {
            VSQHLS = (function () {
                function VSQHLS(vsq, video) {
                    var _this = this;
                    this.pluginName = "VSQHLS";
                    this.vsq = vsq;
                    this.root = vsq.getRoot();
                    this.cfg = vsq.getConfig();
                    this.flow = vsq.getPlayer();
                    this.video = video;
                    this.limiter = new RateLimiter_1.default();
                    this.limiter.add("onNetworkError", 3 * RateLimiter_1.default.SECOND, function () {
                        _this.hls.startLoad();
                    });
                    this.limiter.add("onSwapAudioCodec", 3 * RateLimiter_1.default.SECOND, function () {
                        _this.hls.swapAudioCodec();
                    });
                    this.limiter.add("onRecoverMedia", 3 * RateLimiter_1.default.SECOND, function () {
                        _this.hls.recoverMediaError();
                    });
                }
                VSQHLS.prototype.load = function () {
                };
                VSQHLS.prototype.destroy = function () {
                };
                VSQHLS.prototype.setupHLS = function (type) {
                    var _this = this;
                    var hls = new Hls({
                        initialLiveManifestSize: 2
                    });
                    hls.on(Hls.Events.MEDIA_ATTACHED, function (event, data) {
                        hls.loadSource(video.src);
                    });
                    hls.on(Hls.Events.MANIFEST_PARSED, function () {
                        limiter.cancel();
                    });
                    hls.on(Hls.Events.ERROR, function (event, err) {
                        _this.log('hls error', event, err);
                        var shouldShowSeeking = err.fatal;
                        switch (err.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                if (err.response && err.response.code === 403) {
                                    _this.player.trigger("error", [_this.player, { code: _this.accessDeniedError }]);
                                    return;
                                }
                                if (err.details === Hls.ErrorDetails.LEVEL_LOAD_ERROR) {
                                }
                                limiter.trigger("onNetworkError");
                                return;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                if (err.fatal) {
                                    limiter.trigger("onSwapAudioCodec");
                                    limiter.trigger("onRecoverMedia");
                                }
                                return;
                            default:
                                if (!err.fatal)
                                    return;
                                break;
                        }
                        if (shouldShowSeeking) {
                            _this.root.removeClass('is-paused');
                            _this.root.addClass('is-seeking');
                        }
                        var arg = { code: 2 };
                        _this.player.trigger("error", [_this.player, arg]);
                    });
                    hls.attachMedia(this.videoTags[type]);
                };
                VSQHLS.prototype.onMediaAttached = function (event, data) {
                    this.hls.loadSource(this.video.src);
                };
                VSQHLS.prototype.onManifestParsed = function (event, data) {
                    this.log("canceling ratelimits");
                    this.limiter.cancel();
                };
                return VSQHLS;
            }());
            exports_10("default", VSQHLS);
        }
    };
});
System.register("player/VSQ", ["player/VSQ/LayoutChooser", "player/VSQ/QualityChooser", "RateLimiter"], function (exports_11, context_11) {
    "use strict";
    var __moduleName = context_11 && context_11.id;
    var LayoutChooser_1, QualityChooser_1, RateLimiter_2, VSQ;
    return {
        setters: [
            function (LayoutChooser_1_1) {
                LayoutChooser_1 = LayoutChooser_1_1;
            },
            function (QualityChooser_1_1) {
                QualityChooser_1 = QualityChooser_1_1;
            },
            function (RateLimiter_2_1) {
                RateLimiter_2 = RateLimiter_2_1;
            }
        ],
        execute: function () {
            VSQ = (function () {
                function VSQ(flow, root) {
                    this.loadedCount = 0;
                    this.longerType = 0;
                    this.videoTags = [];
                    this.videoInfo = [];
                    this.hlsEngines = [];
                    this.eventsInitialized = false;
                    this.introOrOutro = false;
                    this.rateLimits = [];
                    this.plugins = [];
                    VSQ.log("constructor", arguments);
                    this.flow = flow;
                    this.cfg = flow.conf.vsq || {};
                    this.l = this.cfg.locale;
                    this.flow.conf.errors.push(this.l.get('access_denied'));
                    this.accessDeniedError = flow.conf.errors.length - 1;
                    this.hlsConf = jQuery.extend({
                        bufferWhilePaused: true,
                        smoothSwitching: true,
                        recoverMediaError: true
                    }, flowplayer.conf['hlsjs'], this.flow.conf['hlsjs'], this.flow.conf['clip']['hlsjs']);
                    VSQ.debug = !!this.cfg.debug;
                    this.root = jQuery(root);
                    this.id = this.root.attr('data-flowplayer-instance-id');
                    if (!this.cfg.contentOnRight)
                        this.root.addClass('vsq-contentleft');
                    this.plugins.push(new LayoutChooser_1.default(this));
                    this.plugins.push(new QualityChooser_1.default(this));
                }
                VSQ.prototype.getRoot = function () {
                    return this.root;
                };
                VSQ.prototype.getConfig = function () {
                    return this.cfg;
                };
                VSQ.prototype.getPlayer = function () {
                    return this.flow;
                };
                VSQ.prototype.getVideoTags = function () {
                    return this.videoTags;
                };
                VSQ.prototype.getVideoInfo = function (type) {
                    return this.videoInfo[type];
                };
                VSQ.prototype.getHLSEngines = function () {
                    return this.hlsEngines;
                };
                VSQ.prototype.hideFlowLogo = function () {
                    this.root.children('a[href*="flowplayer.org"]').hide();
                };
                VSQ.prototype.configKey = function (key) {
                    return 'vsq-player-' + key;
                };
                VSQ.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    if (!VSQ.debug)
                        return;
                    params.unshift("[VSQ]");
                    console.log.apply(console, params);
                };
                VSQ.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    VSQ.log.apply(VSQ, params);
                };
                VSQ.prototype.callOnArray = function (data, funcName, args) {
                    var ret = [];
                    for (var i = data.length - 1; i >= 0; i--) {
                        var elem = data[i];
                        if (elem == null)
                            continue;
                        ret[i] = elem[funcName].apply(elem, args);
                    }
                    return ret;
                };
                VSQ.prototype.setOnArray = function (data, property, value) {
                    var ret = [];
                    for (var i = data.length - 1; i >= 0; i--) {
                        var elem = data[i];
                        if (elem == null)
                            continue;
                        elem[property] = value;
                    }
                };
                VSQ.prototype.hlsCall = function (funcName, args) {
                    return this.callOnArray(this.hlsEngines, funcName, args);
                };
                VSQ.prototype.hlsSet = function (property, value) {
                    this.setOnArray(this.hlsEngines, property, value);
                };
                VSQ.prototype.tagCall = function (funcName, args) {
                    return this.callOnArray(this.videoTags, funcName, args);
                };
                VSQ.prototype.tagSet = function (property, value) {
                    this.setOnArray(this.videoTags, property, value);
                };
                VSQ.prototype.getType = function (type) {
                    if (VSQ.isHLSType(type))
                        return "application/x-mpegurl";
                    return type;
                };
                VSQ.isHLSType = function (type) {
                    return type.toLowerCase().indexOf("mpegurl") > -1;
                };
                VSQ.canPlay = function (type, conf) {
                    var b = flowplayer.support.browser;
                    var wn = window.navigator;
                    var isIE11 = wn.userAgent.indexOf("Trident/7") > -1;
                    if (conf['vsq'] === false || conf.clip['vsq'] === false ||
                        conf['hlsjs'] === false || conf.clip['hlsjs'] === false)
                        return false;
                    if (VSQ.isHLSType(type)) {
                        if (conf.hlsjs &&
                            conf.hlsjs.anamorphic &&
                            wn.platform.indexOf("Win") === 0 &&
                            b.mozilla && b.version.indexOf("44.") === 0)
                            return false;
                        return isIE11 || !b.safari;
                    }
                    return false;
                };
                VSQ.prototype.addPoster = function () {
                    var _this = this;
                    var master = jQuery(this.videoTags[VSQ.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.root.addClass("is-poster");
                        _this.flow.poster = true;
                    });
                };
                VSQ.prototype.removePoster = function () {
                    var _this = this;
                    if (!this.flow.poster)
                        return;
                    var master = jQuery(this.videoTags[VSQ.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.root.removeClass("is-poster");
                        _this.flow.poster = false;
                    });
                };
                VSQ.prototype.hasMultipleVideos = function () {
                    if (this.introOrOutro)
                        return false;
                    return this.cfg.secondarySources.length !== 0;
                };
                VSQ.prototype.isLongerVideo = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (this.introOrOutro)
                        return type === VSQ.MASTER;
                    return type === this.longerType;
                };
                VSQ.prototype.syncVideos = function () {
                    if (!this.hasMultipleVideos())
                        return;
                    var master = this.videoTags[VSQ.MASTER];
                    var content = this.videoTags[VSQ.CONTENT];
                    if (master.currentTime == 0 || master.currentTime >= master.duration)
                        return;
                    if (content.currentTime == 0 || content.currentTime >= content.duration)
                        return;
                    if (this.flow.live) {
                        return;
                    }
                    if (Math.abs(master.currentTime - content.currentTime) > 0.2) {
                        this.log("syncing content to master");
                        content.currentTime = master.currentTime;
                    }
                };
                VSQ.prototype.handleLoadedData = function (e) {
                    if (this.flow.video.index === this.cfg.masterIndex) {
                        this.loadedCount++;
                        var vidCount = 1 + this.cfg.secondarySources.length;
                        if (this.loadedCount != vidCount) {
                            e.stopImmediatePropagation();
                            return false;
                        }
                        if (vidCount > 1 &&
                            this.videoTags[VSQ.CONTENT].duration > this.videoTags[VSQ.MASTER].duration)
                            this.longerType = VSQ.CONTENT;
                    }
                    else
                        this.longerType = VSQ.MASTER;
                    var tag = this.videoTags[this.longerType];
                    var data = jQuery.extend(this.flow.video, {
                        duration: tag.duration,
                        seekable: tag.seekable.end(0),
                        width: tag.videoWidth,
                        height: tag.videoHeight,
                        url: this.videoInfo[VSQ.MASTER].src
                    });
                    this.triggerPlayer("ready", data);
                    if (this.flow.video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
                        this.tagCall('play');
                    return false;
                };
                VSQ.prototype.handlePlay = function (e) {
                    var tag = e.currentTarget;
                    if (tag.currentTime >= tag.duration)
                        tag.currentTime = 0;
                    var type = this.getTypeFromEvent(e);
                    if (type === VSQ.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused) {
                        if (this.introOrOutro)
                            this.hlsEngines[VSQ.MASTER].startLoad(tag.currentTime);
                        else
                            this.hlsCall('startLoad', [tag.currentTime]);
                    }
                    this.triggerPlayer("resume", undefined);
                };
                VSQ.prototype.handlePause = function (e) {
                    var type = this.getTypeFromEvent(e);
                    var tag = e.currentTarget;
                    if (this.hasMultipleVideos() && type !== this.longerType &&
                        tag.currentTime >= tag.duration) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    if (type !== this.longerType) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused)
                        this.hlsCall('stopLoad');
                    this.triggerPlayer("pause", undefined);
                };
                VSQ.prototype.handleEnded = function (e) {
                    if (!this.isLongerVideo(e)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var video = this.flow.video;
                    this.hlsCall('trigger', [
                        Hls.Events.BUFFER_FLUSHING,
                        {
                            startOffset: 0,
                            endOffset: this.cfg.duration * 0.9
                        }
                    ]);
                    this.tagCall('pause');
                    if (this.introOrOutro && !video.is_last) {
                        this.flow.next();
                        if (video.index === 0 && this.cfg.masterIndex !== 0) {
                            this.flow.removePlaylistItem(0);
                            this.cfg.masterIndex--;
                        }
                    }
                    if (video.is_last)
                        this.triggerPlayer("finish", undefined);
                };
                VSQ.prototype.handleProgress = function (e) {
                    if (!this.isLongerVideo(e)) {
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
                    this.flow.video.buffer = buffer;
                    this.triggerPlayer("buffer", buffer);
                };
                VSQ.prototype.handleRateChange = function (e) {
                    if (!this.isLongerVideo(e)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerPlayer("speed", tag.playbackRate);
                };
                VSQ.prototype.handleSeeked = function (e) {
                    if (!this.isLongerVideo(e)) {
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
                VSQ.prototype.handleTimeUpdate = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if ((this.introOrOutro && type !== VSQ.MASTER) ||
                        (!this.introOrOutro && type !== this.longerType)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = this.videoTags[this.longerType];
                    this.triggerPlayer("progress", tag.currentTime);
                    this.syncVideos();
                };
                VSQ.prototype.handleVolumeChange = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === VSQ.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerPlayer("volume", tag.volume);
                };
                VSQ.prototype.handleError = function (e) {
                    e.stopImmediatePropagation();
                    var MEDIA_ERR_NETWORK = 2;
                    var MEDIA_ERR_DECODE = 3;
                    var type = this.getTypeFromEvent(e);
                    var err = this.videoTags[type].error.code || MEDIA_ERR_DECODE;
                    this.log(this.videoTags[type].error, e);
                    var arg = { code: err };
                    if (err > MEDIA_ERR_NETWORK)
                        arg.video = jQuery.extend(this.videoInfo[type], { url: this.videoInfo[type].src });
                    this.flow.trigger("error", [this.flow, arg]);
                    return false;
                };
                VSQ.prototype.triggerPlayer = function (event, data) {
                    if (event !== "buffer" && event !== "progress")
                        this.log("[flow event]", event, data);
                    this.flow.trigger(event, [this.flow, data]);
                    this.hideFlowLogo();
                };
                VSQ.prototype.getTypeFromEvent = function (e) {
                    var t = jQuery(e.currentTarget);
                    if (!t.is('.vsq-master, .vsq-content'))
                        throw new Error("Unknown event target");
                    if (t.is('.vsq-master'))
                        return VSQ.MASTER;
                    return VSQ.CONTENT;
                };
                VSQ.prototype.setupVideoEvents = function (video) {
                    var _this = this;
                    if (this.eventsInitialized)
                        return;
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
                    jQuery.each(events, function (videoEvent, flowEvent) {
                        videoEvent = _this.eventName(videoEvent);
                        sources.on(videoEvent, function (e) {
                            if (e.type !== "progress" && e.type !== "timeupdate")
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
                    if (this.flow.conf.poster) {
                        this.flow.on(this.eventName("stop"), function () {
                            _this.addPoster();
                        });
                        if (this.flow.live)
                            jQuery(this.videoTags[VSQ.MASTER]).one(this.eventName("seeked"), function () {
                                _this.addPoster();
                            });
                    }
                };
                VSQ.prototype.eventName = function (event) {
                    var postfix = '.' + VSQ.engineName;
                    if (!event)
                        return postfix;
                    return event + postfix;
                };
                VSQ.prototype.createVideoTag = function (video) {
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
                VSQ.prototype.destroyVideoTag = function (index) {
                    var tagElem = this.videoTags[index];
                    var elem = jQuery(tagElem);
                    elem.find('source').removeAttr('src');
                    elem.removeAttr('src');
                    tagElem.load();
                    elem.remove();
                    delete (this.videoTags[index]);
                };
                VSQ.prototype.setupHLS = function (type) {
                    var _this = this;
                    var video = this.videoInfo[type];
                    var hls = new Hls({
                        initialLiveManifestSize: 2
                    });
                    hls.on(Hls.Events.MEDIA_ATTACHED, function (event, data) {
                        hls.loadSource(video.src);
                    });
                    var limiter = new RateLimiter_2.default();
                    limiter.add("onNetworkError", 3 * RateLimiter_2.default.SECOND, function () {
                        hls.startLoad();
                    });
                    limiter.add("onSwapAudioCodec", 3 * RateLimiter_2.default.SECOND, function () {
                        hls.swapAudioCodec();
                    });
                    limiter.add("onRecoverMedia", 3 * RateLimiter_2.default.SECOND, function () {
                        hls.recoverMediaError();
                    });
                    hls.on(Hls.Events.MANIFEST_PARSED, function () {
                        _this.log("canceling ratelimits");
                        limiter.cancel();
                    });
                    hls.on(Hls.Events.ERROR, function (event, err) {
                        _this.log('hls error', event, err);
                        var shouldShowSeeking = err.fatal;
                        switch (err.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                if (err.response && err.response.code === 403) {
                                    _this.flow.trigger("error", [_this.flow, { code: _this.accessDeniedError }]);
                                    return;
                                }
                                if (err.details === Hls.ErrorDetails.LEVEL_LOAD_ERROR) {
                                }
                                limiter.trigger("onNetworkError");
                                return;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                if (err.fatal) {
                                    limiter.trigger("onSwapAudioCodec");
                                    limiter.trigger("onRecoverMedia");
                                }
                                return;
                            default:
                                if (!err.fatal)
                                    return;
                                break;
                        }
                        if (shouldShowSeeking) {
                            _this.root.removeClass('is-paused');
                            _this.root.addClass('is-seeking');
                        }
                        var arg = { code: 2 };
                        _this.flow.trigger("error", [_this.flow, arg]);
                    });
                    hls.attachMedia(this.videoTags[type]);
                    this.hlsEngines[type] = hls;
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].setupHLS(hls, type);
                };
                VSQ.prototype.load = function (video) {
                    var _this = this;
                    this.introOrOutro = true;
                    if ((video.index === 0 && video.is_last) || video.index === this.cfg.masterIndex)
                        this.introOrOutro = false;
                    if (video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
                        video.autoplay = true;
                    var root = this.root.find('.fp-player');
                    root.find('img').remove();
                    this.hlsConf = jQuery.extend(this.hlsConf, this.flow.conf.hlsjs, this.flow.conf.clip.hlsjs, video.hlsjs);
                    if (video.index > this.cfg.masterIndex && this.videoTags[VSQ.CONTENT])
                        this.destroyVideoTag(VSQ.CONTENT);
                    if (video.index === this.cfg.masterIndex &&
                        this.hasMultipleVideos()) {
                        if (this.videoTags[VSQ.CONTENT])
                            this.destroyVideoTag(VSQ.CONTENT);
                        var secondVideo = jQuery.extend(true, {}, video);
                        secondVideo.src = this.cfg.secondarySources[0].src;
                        secondVideo['vsq-labels'] = this.cfg.secondarySources[0]['vsq-labels'];
                        secondVideo.sources = this.cfg.secondarySources;
                        this.videoInfo[VSQ.CONTENT] = secondVideo;
                        this.videoTags[VSQ.CONTENT] = this.createVideoTag(secondVideo);
                        this.videoTags[VSQ.CONTENT].load();
                        var engine_1 = jQuery(this.videoTags[VSQ.CONTENT]);
                        engine_1.addClass('vsq-content');
                        root.prepend(engine_1);
                        this.setupHLS(VSQ.CONTENT);
                    }
                    if (this.videoTags[VSQ.MASTER])
                        this.destroyVideoTag(VSQ.MASTER);
                    this.videoInfo[VSQ.MASTER] = video;
                    this.videoTags[VSQ.MASTER] = this.createVideoTag(video);
                    this.videoTags[VSQ.MASTER].load();
                    var engine = jQuery(this.videoTags[VSQ.MASTER]);
                    engine.addClass('vsq-master');
                    if (video.index !== this.cfg.masterIndex ||
                        !this.hasMultipleVideos())
                        engine.addClass("vsq-fullscale");
                    root.prepend(engine);
                    this.setupHLS(VSQ.MASTER);
                    this.flow.on(this.eventName("error"), function () {
                        _this.unload();
                    });
                    this.setupVideoEvents(video);
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].load();
                    if (this.cfg.autoplay)
                        this.tagCall("play");
                };
                VSQ.prototype.pause = function () {
                    this.tagCall('pause');
                };
                VSQ.prototype.resume = function () {
                    if (this.introOrOutro) {
                        this.videoTags[VSQ.MASTER].play();
                        return;
                    }
                    this.tagCall('play');
                };
                VSQ.prototype.speed = function (speed) {
                    this.tagSet('playbackRate', speed);
                    this.flow.trigger('speed', [this.flow, speed]);
                };
                VSQ.prototype.volume = function (volume) {
                    this.tagSet('volume', volume);
                };
                VSQ.prototype.unload = function () {
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].destroy();
                    var videoTags = jQuery(this.videoTags);
                    videoTags.remove();
                    this.hlsCall('destroy');
                    var listeners = this.eventName();
                    this.flow.off(listeners);
                    this.root.off(listeners);
                    videoTags.off(listeners);
                    for (var i = this.hlsEngines.length - 1; i >= 0; i--)
                        this.hlsEngines.pop();
                    for (var i = this.videoTags.length - 1; i >= 0; i--)
                        this.videoTags.pop();
                };
                VSQ.prototype.seek = function (to) {
                    if (!this.hasMultipleVideos()) {
                        this.videoTags[VSQ.MASTER].currentTime = to;
                        return;
                    }
                    var tags = [];
                    var playing = false;
                    for (var i = this.videoTags.length - 1; i >= 0; i--) {
                        var tag = this.videoTags[i];
                        playing = playing || !tag.paused;
                        if (tag.duration > to)
                            tags.push(tag);
                        else {
                            tag.currentTime = tag.duration;
                        }
                    }
                    this.setOnArray(tags, 'currentTime', to);
                    if (playing)
                        this.callOnArray(tags, 'play', []);
                };
                VSQ.prototype.pick = function (sources) {
                    if (sources.length == 0)
                        throw new Error("Zero length FlowSources passed");
                    for (var i = 0; i < sources.length; ++i) {
                        var source = sources[i];
                        if (!VSQ.isHLSType(source.type))
                            continue;
                        source.src = flowplayer.common.createAbsoluteUrl(source.src);
                        return source;
                    }
                    return null;
                };
                VSQ.setup = function () {
                    if (VSQ.initDone)
                        return;
                    var proxy = function (flow, root) {
                        return new VSQ(flow, root);
                    };
                    proxy.engineName = VSQ.engineName;
                    proxy.canPlay = VSQ.canPlay;
                    flowplayer.engines.unshift(proxy);
                    VSQ.initDone = true;
                };
                return VSQ;
            }());
            VSQ.engineName = "vsq";
            VSQ.debug = false;
            VSQ.initDone = false;
            VSQ.MASTER = 0;
            VSQ.CONTENT = 1;
            exports_11("VSQ", VSQ);
        }
    };
});
System.register("player/PlayerSetup", ["player/Flash", "player/VSQ"], function (exports_12, context_12) {
    "use strict";
    var __moduleName = context_12 && context_12.id;
    var Flash_1, VSQ_4, PlayerSetup;
    return {
        setters: [
            function (Flash_1_1) {
                Flash_1 = Flash_1_1;
            },
            function (VSQ_4_1) {
                VSQ_4 = VSQ_4_1;
            }
        ],
        execute: function () {
            PlayerSetup = (function () {
                function PlayerSetup(cfg, l) {
                    if (!cfg)
                        throw "Invalid config passed";
                    if (!l)
                        throw "Invalid locale passed";
                    this.cfg = cfg;
                    this.l = l;
                }
                PlayerSetup.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    if (!this.cfg.get("flowplayer.vsq.debug"))
                        return;
                    params.unshift("[PlayerSetup]");
                    console.log.apply(console, params);
                };
                PlayerSetup.prototype.supportsVideo = function () {
                    var elem = document.createElement('video');
                    return !!elem.canPlayType;
                };
                PlayerSetup.prototype.initFlash = function () {
                    var flash = new Flash_1.default(this.cfg, this.l);
                    flash.embed();
                };
                PlayerSetup.prototype.init = function () {
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
                    this.initVSQ();
                };
                PlayerSetup.prototype.initVSQ = function () {
                    var _this = this;
                    this.initVSQPlugin();
                    this.flowInstance = flowplayer(this.container.get(0), this.cfg.get('flowplayer'));
                    this.flowInstance.on('load', function (e, api, video) {
                        _this.log('ready', e, api, video);
                    });
                };
                PlayerSetup.prototype.initVSQPlugin = function () {
                    VSQ_4.VSQ.setup();
                };
                return PlayerSetup;
            }());
            exports_12("default", PlayerSetup);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/PlayerSetup"], function (exports_13, context_13) {
    "use strict";
    var __moduleName = context_13 && context_13.id;
    var Locale_1, Config_1, PlayerSetup_1;
    return {
        setters: [
            function (Locale_1_1) {
                Locale_1 = Locale_1_1;
            },
            function (Config_1_1) {
                Config_1 = Config_1_1;
            },
            function (PlayerSetup_1_1) {
                PlayerSetup_1 = PlayerSetup_1_1;
            }
        ],
        execute: function () {
            (function ($) {
                var pcCopy = $.extend(true, {}, playerconfig);
                var fcCopy = $.extend(true, {}, flashconfig);
                var lCopy = $.extend(true, {}, l);
                $(function () {
                    var loc = new Locale_1.default(lCopy);
                    if (pcCopy.flowplayer)
                        pcCopy.flowplayer.vsq.locale = loc;
                    var cfg = new Config_1.default(pcCopy, fcCopy);
                    var player = new PlayerSetup_1.default(cfg, loc);
                    player.init();
                });
            })(jQuery);
        }
    };
});
//# sourceMappingURL=app.js.map