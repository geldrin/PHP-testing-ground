var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p];
    function __() { this.constructor = d; }
    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments)).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t;
    return { next: verb(0), "throw": verb(1), "return": verb(2) };
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = y[op[0] & 2 ? "return" : op[0] ? "throw" : "next"]) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [0, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
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
                    this.flowroot = vsq.getFlowRoot();
                    this.cfg = vsq.getConfig();
                    this.l = this.cfg.locale;
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
    var VSQ_2, BasePlugin_1, Tools_1, LayoutType, LayoutChooser;
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
            (function (LayoutType) {
                LayoutType[LayoutType["PIPCONTENT"] = 0] = "PIPCONTENT";
                LayoutType[LayoutType["MASTERONLY"] = 1] = "MASTERONLY";
                LayoutType[LayoutType["SPLIT"] = 2] = "SPLIT";
                LayoutType[LayoutType["CONTENTONLY"] = 3] = "CONTENTONLY";
                LayoutType[LayoutType["PIPMASTER"] = 4] = "PIPMASTER";
            })(LayoutType || (LayoutType = {}));
            LayoutChooser = (function (_super) {
                __extends(LayoutChooser, _super);
                function LayoutChooser(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "LayoutChooser";
                    if (LayoutChooser.instance != null)
                        throw new Error("LayoutChooser.instance already present");
                    LayoutChooser.instance = _this;
                    return _this;
                }
                LayoutChooser.prototype.load = function () {
                    var _this = this;
                    if (!this.vsq.hasMultipleVideos()) {
                        this.flowroot.addClass('vsq-singlevideo');
                        return;
                    }
                    if (this.flowroot.find('.vsq-layoutchooser').length > 0)
                        return;
                    this.fixHeight();
                    this.flow.on("fullscreen fullscreen-exit", function () { _this.fixHeight(); });
                    this.setupRatios();
                    this.setupHTML();
                    this.trigger();
                };
                LayoutChooser.prototype.destroy = function () {
                    this.flowroot.find(".vsq-layoutchooser").remove();
                };
                LayoutChooser.prototype.configKey = function (key) {
                    return 'vsq-player-layout-' + key;
                };
                LayoutChooser.prototype.trigger = function (newVal) {
                    var ratio = this.flowroot.find('.vsq-layoutchooser input[name="ratio"]');
                    if (newVal != null)
                        ratio.val(newVal);
                    ratio.change();
                };
                LayoutChooser.prototype.fixHeight = function () {
                    var maxHeight;
                    if (this.flowroot.hasClass('is-fullscreen'))
                        maxHeight = jQuery(window).height();
                    else
                        maxHeight = this.flowroot.height();
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
                            'type': LayoutType.PIPCONTENT
                        },
                        {
                            'from': pipRatio,
                            'to': pipRatio + singleRatio,
                            'type': LayoutType.MASTERONLY
                        },
                        {
                            'from': pipRatio + singleRatio,
                            'to': pipRatio + singleRatio + splitRatio,
                            'type': LayoutType.SPLIT
                        },
                        {
                            'from': pipRatio + singleRatio + splitRatio,
                            'to': pipRatio + singleRatio + splitRatio + singleRatio,
                            'type': LayoutType.CONTENTONLY
                        },
                        {
                            'from': pipRatio + singleRatio + splitRatio + singleRatio,
                            'to': pipRatio + singleRatio + splitRatio + singleRatio + pipRatio,
                            'type': LayoutType.PIPMASTER
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
                    this.flowroot.find(".fp-ui").append(html);
                    this.flowroot.on("click", ".vsq-layoutchooser .pip-content", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(LayoutType.PIPCONTENT));
                    });
                    this.flowroot.on("click", ".vsq-layoutchooser .master-only", function (e) {
                        e.preventDefault();
                        _this.trigger('' + _this.ranges[LayoutType.MASTERONLY].from);
                    });
                    this.flowroot.on("click", ".vsq-layoutchooser .split", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(LayoutType.SPLIT));
                    });
                    this.flowroot.on("click", ".vsq-layoutchooser .content-only", function (e) {
                        e.preventDefault();
                        _this.trigger('' + _this.ranges[LayoutType.CONTENTONLY].from);
                    });
                    this.flowroot.on("click", ".vsq-layoutchooser .pip-master", function (e) {
                        e.preventDefault();
                        _this.trigger(_this.getMiddleRange(LayoutType.PIPMASTER));
                    });
                    this.flowroot.on("input change", '.vsq-layoutchooser input[name="ratio"]', function (e) {
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
                    if (this.flowroot.hasClass('vsq-hidden-master') || this.flowroot.hasClass('vsq-hidden-content'))
                        return;
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
                        case LayoutType.PIPCONTENT:
                            masterWidth = 100;
                            contentWidth = info.percent * 50;
                            masterOnTop = false;
                            break;
                        case LayoutType.MASTERONLY:
                            masterWidth = 100;
                            contentWidth = 0;
                            masterOnTop = true;
                            break;
                        case LayoutType.SPLIT:
                            masterWidth = info.percent * 100;
                            contentWidth = 100 - masterWidth;
                            masterOnTop = null;
                            break;
                        case LayoutType.CONTENTONLY:
                            masterWidth = 0;
                            contentWidth = 100;
                            masterOnTop = false;
                            break;
                        case LayoutType.PIPMASTER:
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
                    var master = jQuery(tags[VSQ_2.VSQType.MASTER]);
                    var content = jQuery(tags[VSQ_2.VSQType.CONTENT]);
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
                LayoutChooser.resetSize = function () {
                    LayoutChooser.instance.resetSize();
                };
                LayoutChooser.prototype.resetSize = function () {
                    var tags = this.vsq.getVideoTags();
                    jQuery(tags).removeAttr('style');
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
                    _this.flowroot.on(_this.eventName("click"), ".vsq-quality-selector li", function (e) {
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
                        return this.vsq.getVideoInfo(VSQ_3.VSQType.MASTER)['vsq-labels'].slice(0);
                    if (this.vsq.longerType === VSQ_3.VSQType.CONTENT)
                        return this.vsq.getVideoInfo(VSQ_3.VSQType.CONTENT)['vsq-labels'].slice(0);
                    return this.vsq.getVideoInfo(VSQ_3.VSQType.MASTER)['vsq-labels'].slice(0);
                };
                QualityChooser.prototype.onClick = function (e) {
                    var _this = this;
                    e.preventDefault();
                    var choice = jQuery(e.currentTarget);
                    if (choice.hasClass("active"))
                        return;
                    this.flowroot.find('.vsq-quality-selector li').removeClass("active");
                    choice.addClass("active");
                    var quality = choice.attr('data-quality');
                    Tools_2.default.setToStorage(this.configKey("quality"), quality);
                    var masterLevel = this.getQualityIndex(VSQ_3.VSQType.MASTER, quality);
                    var smooth = this.flow.conf.smoothSwitching;
                    var tags = this.vsq.getVideoTags();
                    var paused = tags[VSQ_3.VSQType.MASTER].paused;
                    if (!paused && !smooth)
                        jQuery(tags[VSQ_3.VSQType.MASTER]).one(this.eventName("pause"), function () {
                            _this.flowroot.removeClass("is-paused");
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
                    this.flowroot.find('.vsq-quality-selector').remove();
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
                    this.flowroot.find(".fp-ui").append(html);
                };
                QualityChooser.prototype.destroy = function () {
                    this.flowroot.find(".vsq-quality-selector").remove();
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
                    if (type !== VSQ_3.VSQType.MASTER)
                        return;
                    hls.on(Hls.Events.LEVEL_SWITCH, function (event, data) {
                        _this.flowroot.find('.vsq-quality-selector li').removeClass("current");
                        var elem = _this.findQualityElem(data.level);
                        elem.addClass("current");
                    });
                };
                QualityChooser.prototype.findQualityElem = function (level) {
                    var ret = this.flowroot.find('.vsq-quality-selector li[data-level="' + level + '"]');
                    if (ret.length === 0)
                        throw new Error("No element found with the given level: " + level);
                    return ret;
                };
                QualityChooser.prototype.setLevelsForQuality = function (quality, prop) {
                    var engines = this.vsq.getHLSEngines();
                    var masterLevel = this.getQualityIndex(VSQ_3.VSQType.MASTER, quality);
                    this.log('setting master video level to', masterLevel, quality);
                    engines[VSQ_3.VSQType.MASTER][prop] = masterLevel;
                    if (!this.shouldLookAtSecondary())
                        return;
                    var secondaryLevel = this.getQualityIndex(VSQ_3.VSQType.CONTENT, quality);
                    this.log('setting content video level to', secondaryLevel, quality);
                    engines[VSQ_3.VSQType.CONTENT][prop] = secondaryLevel;
                };
                QualityChooser.prototype.getQualityIndex = function (type, quality) {
                    if (type === VSQ_3.VSQType.MASTER)
                        return this.getMasterQualityIndex(quality);
                    var masterLevel = this.getMasterQualityIndex(quality);
                    return this.getLevelForSecondary(masterLevel);
                };
                QualityChooser.prototype.getMasterQualityIndex = function (quality) {
                    var labels = this.vsq.getVideoInfo(VSQ_3.VSQType.MASTER)['vsq-labels'];
                    for (var i = labels.length - 1; i >= 0; i--) {
                        var label = labels[i];
                        if (label === quality)
                            return i;
                    }
                    return -1;
                };
                QualityChooser.prototype.getLevelForSecondary = function (masterLevel) {
                    var labels = this.vsq.getVideoInfo(VSQ_3.VSQType.CONTENT)['vsq-labels'];
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
System.register("player/VSQ/Modal", ["player/VSQ/BasePlugin", "Escape"], function (exports_9, context_9) {
    "use strict";
    var __moduleName = context_9 && context_9.id;
    var BasePlugin_3, Escape_2, Modal;
    return {
        setters: [
            function (BasePlugin_3_1) {
                BasePlugin_3 = BasePlugin_3_1;
            },
            function (Escape_2_1) {
                Escape_2 = Escape_2_1;
            }
        ],
        execute: function () {
            Modal = (function (_super) {
                __extends(Modal, _super);
                function Modal(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "Modal";
                    if (Modal.instance != null)
                        throw new Error("Modal.instance already present");
                    Modal.instance = _this;
                    _this.setupHTML();
                    return _this;
                }
                Modal.prototype.load = function () {
                };
                Modal.prototype.destroy = function () {
                };
                Modal.prototype.setupHTML = function () {
                    var html = "\n      <div class=\"vsq-modal\">\n        <div class=\"vsq-transient\">\n        </div>\n        <form class=\"vsq-login\">\n          <div class=\"row vsq-message\">\n          </div>\n          <div class=\"row vsq-email\">\n            <div class=\"label\">\n              <label for=\"email\">" + Escape_2.default.HTML(this.l.get('playeremail')) + "</label>\n            </div>\n            <div class=\"elem\">\n              <input name=\"email\" id=\"email\" type=\"text\"/>\n            </div>\n          </div>\n          <div class=\"row vsq-password\">\n            <div class=\"label\">\n              <label for=\"password\">" + Escape_2.default.HTML(this.l.get('playerpassword')) + "</label>\n            </div>\n            <div class=\"elem\">\n              <input name=\"password\" id=\"password\" type=\"password\"/>\n            </div>\n          </div>\n          <div class=\"row submit\">\n            <div class=\"elem\">\n              <input type=\"submit\" value=\"" + Escape_2.default.HTML(this.l.get('submitlogin')) + "\"/>\n            </div>\n          </div>\n        </form>\n      </div>\n    ";
                    this.root.append(html);
                };
                Modal.installLoginHandler = function (plugin) {
                    Modal.instance.installLoginHandler(plugin);
                };
                Modal.prototype.installLoginHandler = function (plugin) {
                    var _this = this;
                    this.root.on("submit", ".vsq-modal .vsq-login", function (e) {
                        e.preventDefault();
                        var form = _this.root.find(".vsq-modal .vsq-login");
                        var email = form.find('input[name=email]').val();
                        var password = form.find('input[name=password]').val();
                        plugin.onSubmit(email, password);
                    });
                };
                Modal.showError = function (html) {
                    Modal.instance.showError(html);
                };
                Modal.prototype.showError = function (html) {
                    var msg = this.root.find(".fp-message");
                    msg.find("h2").text('');
                    msg.find("p").html(html);
                    this.vsq.pause();
                    this.hideLogin();
                    this.root.addClass("is-error");
                };
                Modal.showLogin = function (messageHTML) {
                    Modal.instance.showLogin(messageHTML);
                };
                Modal.prototype.showLogin = function (messageHTML) {
                    this.root.find(".vsq-modal .vsq-message").html(messageHTML);
                    this.root.addClass("vsq-is-login");
                };
                Modal.hideLogin = function () {
                    Modal.instance.hideLogin();
                };
                Modal.prototype.hideLogin = function () {
                    this.root.removeClass("vsq-is-login");
                };
                Modal.showTransientMessage = function (html) {
                    Modal.instance.showTransientMessage(html);
                };
                Modal.prototype.showTransientMessage = function (msg) {
                    this.root.find(".vsq-modal .vsq-transient").text(msg);
                    this.root.addClass("vsq-transient-error");
                };
                Modal.hideTransientMessage = function () {
                    Modal.instance.hideLogin();
                };
                Modal.prototype.hideTransientMessage = function () {
                    this.root.removeClass("vsq-transient-error");
                };
                return Modal;
            }(BasePlugin_3.BasePlugin));
            exports_9("Modal", Modal);
        }
    };
});
System.register("RateLimiter", [], function (exports_10, context_10) {
    "use strict";
    var __moduleName = context_10 && context_10.id;
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
            exports_10("default", RateLimiter);
        }
    };
});
System.register("player/VSQAPI", [], function (exports_11, context_11) {
    "use strict";
    var __moduleName = context_11 && context_11.id;
    var VSQAPI;
    return {
        setters: [],
        execute: function () {
            VSQAPI = (function () {
                function VSQAPI() {
                }
                VSQAPI.init = function (cfg) {
                    VSQAPI.baseURL = cfg.apiurl;
                };
                VSQAPI.call = function (method, parameters) {
                    return new Promise(function (resolve, reject) {
                        var req = jQuery.ajax({
                            url: VSQAPI.baseURL,
                            type: method,
                            data: {
                                parameters: JSON.stringify(parameters)
                            },
                            dataType: 'json',
                            cache: false
                        });
                        req.done(function (data) { return resolve(data); });
                        req.fail(function (err) { return reject(err); });
                    });
                };
                VSQAPI.prepareParams = function (module, method, args) {
                    var parameters = jQuery.extend({
                        layer: "controller",
                        module: module,
                        method: method,
                        format: 'json'
                    }, args || {});
                    return parameters;
                };
                VSQAPI.GET = function (module, method, args) {
                    var parameters = this.prepareParams(module, method, args);
                    return VSQAPI.call("GET", parameters);
                };
                VSQAPI.POST = function (module, method, args) {
                    var parameters = this.prepareParams(module, method, args);
                    return VSQAPI.call("POST", parameters);
                };
                return VSQAPI;
            }());
            exports_11("default", VSQAPI);
        }
    };
});
System.register("player/VSQ/Pinger", ["player/VSQAPI", "player/VSQ/BasePlugin", "player/VSQ/Modal"], function (exports_12, context_12) {
    "use strict";
    var __moduleName = context_12 && context_12.id;
    var VSQAPI_1, BasePlugin_4, Modal_1, Pinger;
    return {
        setters: [
            function (VSQAPI_1_1) {
                VSQAPI_1 = VSQAPI_1_1;
            },
            function (BasePlugin_4_1) {
                BasePlugin_4 = BasePlugin_4_1;
            },
            function (Modal_1_1) {
                Modal_1 = Modal_1_1;
            }
        ],
        execute: function () {
            Pinger = (function (_super) {
                __extends(Pinger, _super);
                function Pinger(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "Pinger";
                    _this.log("scheduling request");
                    _this.schedule();
                    return _this;
                }
                Pinger.prototype.schedule = function () {
                    var _this = this;
                    if (this.timer !== null)
                        clearTimeout(this.timer);
                    this.timer = setTimeout(function () {
                        _this.ping();
                        _this.timer = null;
                        _this.schedule();
                    }, this.cfg.pingSeconds * 1000);
                };
                Pinger.prototype.handleError = function (message, errData) {
                    if (errData.invalidtoken || errData.sessionexpired) {
                        Modal_1.Modal.showError(message);
                        return;
                    }
                    if (!errData.loggedin) {
                        Modal_1.Modal.showLogin(message);
                        return;
                    }
                };
                Pinger.prototype.ping = function () {
                    return __awaiter(this, void 0, void 0, function () {
                        var data, errMessage, errData, err_1;
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0:
                                    _a.trys.push([0, 2, , 3]);
                                    return [4 /*yield*/, VSQAPI_1.default.POST("users", "ping", this.cfg.parameters)];
                                case 1:
                                    data = _a.sent();
                                    switch (data.result) {
                                        case "OK":
                                            if (data.data !== true)
                                                throw new Error("unexpected");
                                            break;
                                        default:
                                            errMessage = data.data;
                                            errData = data.extradata;
                                            this.handleError(errMessage, errData);
                                            break;
                                    }
                                    return [3 /*break*/, 3];
                                case 2:
                                    err_1 = _a.sent();
                                    Modal_1.Modal.showError(this.l.get('networkerror'));
                                    return [3 /*break*/, 3];
                                case 3: return [2 /*return*/];
                            }
                        });
                    });
                };
                Pinger.prototype.load = function () {
                };
                Pinger.prototype.destroy = function () {
                };
                return Pinger;
            }(BasePlugin_4.BasePlugin));
            exports_12("default", Pinger);
        }
    };
});
System.register("player/VSQ/Login", ["player/VSQAPI", "player/VSQ/BasePlugin", "player/VSQ/Modal"], function (exports_13, context_13) {
    "use strict";
    var __moduleName = context_13 && context_13.id;
    var VSQAPI_2, BasePlugin_5, Modal_2, Login;
    return {
        setters: [
            function (VSQAPI_2_1) {
                VSQAPI_2 = VSQAPI_2_1;
            },
            function (BasePlugin_5_1) {
                BasePlugin_5 = BasePlugin_5_1;
            },
            function (Modal_2_1) {
                Modal_2 = Modal_2_1;
            }
        ],
        execute: function () {
            Login = (function (_super) {
                __extends(Login, _super);
                function Login(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "Login";
                    _this.shown = false;
                    Modal_2.Modal.installLoginHandler(_this);
                    Modal_2.Modal.showLogin("");
                    return _this;
                }
                Login.prototype.login = function (params) {
                    return __awaiter(this, void 0, void 0, function () {
                        var data, errMessage, err_2;
                        return __generator(this, function (_a) {
                            switch (_a.label) {
                                case 0:
                                    _a.trys.push([0, 2, , 3]);
                                    return [4 /*yield*/, VSQAPI_2.default.POST("users", "authenticate", params)];
                                case 1:
                                    data = _a.sent();
                                    switch (data.result) {
                                        case "OK":
                                            if (data.data)
                                                Modal_2.Modal.hideLogin();
                                            else
                                                Modal_2.Modal.showLogin(this.l.get('loginfailed'));
                                            break;
                                        default:
                                            errMessage = data.data;
                                            Modal_2.Modal.showLogin(errMessage);
                                            break;
                                    }
                                    return [3 /*break*/, 3];
                                case 2:
                                    err_2 = _a.sent();
                                    Modal_2.Modal.showError(this.l.get('networkerror'));
                                    return [3 /*break*/, 3];
                                case 3: return [2 /*return*/];
                            }
                        });
                    });
                };
                Login.prototype.onSubmit = function (email, password) {
                    var params = jQuery.extend({
                        email: email,
                        password: password
                    }, this.cfg.parameters);
                    this.login(params);
                };
                Login.prototype.load = function () {
                };
                Login.prototype.destroy = function () {
                };
                return Login;
            }(BasePlugin_5.BasePlugin));
            exports_13("default", Login);
        }
    };
});
System.register("player/VSQ/ProgressReport", ["player/VSQ/BasePlugin"], function (exports_14, context_14) {
    "use strict";
    var __moduleName = context_14 && context_14.id;
    var BasePlugin_6, ProgressReport;
    return {
        setters: [
            function (BasePlugin_6_1) {
                BasePlugin_6 = BasePlugin_6_1;
            }
        ],
        execute: function () {
            ProgressReport = (function (_super) {
                __extends(ProgressReport, _super);
                function ProgressReport(vsq) {
                    var _this = _super.call(this, vsq) || this;
                    _this.pluginName = "ProgressReport";
                    return _this;
                }
                ProgressReport.prototype.load = function () {
                    this.flow.on("progress.vsq-pgr", function (e, flow, time) {
                    });
                    this.flow.on("seek.vsq-pgr", function (e, flow, time) {
                    });
                };
                ProgressReport.prototype.destroy = function () {
                    this.flow.off(".vsq-pgr");
                };
                return ProgressReport;
            }(BasePlugin_6.BasePlugin));
            exports_14("default", ProgressReport);
        }
    };
});
System.register("player/VSQHLS", ["player/VSQ", "RateLimiter"], function (exports_15, context_15) {
    "use strict";
    var __moduleName = context_15 && context_15.id;
    var VSQ_4, RateLimiter_1, VSQHLS;
    return {
        setters: [
            function (VSQ_4_1) {
                VSQ_4 = VSQ_4_1;
            },
            function (RateLimiter_1_1) {
                RateLimiter_1 = RateLimiter_1_1;
            }
        ],
        execute: function () {
            VSQHLS = (function () {
                function VSQHLS(vsq, type) {
                    this.vsq = vsq;
                    this.flowroot = vsq.getFlowRoot();
                    this.cfg = vsq.getConfig();
                    this.flow = vsq.getPlayer();
                    this.video = jQuery.extend(true, {}, vsq.getVideoInfo(type));
                    this.type = type;
                    this.initLimiter();
                    this.initHls(type);
                }
                VSQHLS.prototype.initHls = function (type) {
                    var _this = this;
                    this.hls = new Hls({
                        fragLoadingMaxRetry: 0,
                        manifestLoadingMaxRetry: 0,
                        levelLoadingMaxRetry: 0,
                        initialLiveManifestSize: 2
                    });
                    this.hls.on(Hls.Events.MEDIA_ATTACHED, function (evt, data) {
                        _this.onMediaAttached(evt, data);
                    });
                    this.hls.on(Hls.Events.MANIFEST_PARSED, function (evt, data) {
                        _this.onManifestParsed(evt, data);
                        _this.vsq.showTag(_this.type);
                    });
                    this.hls.on(Hls.Events.LEVEL_LOADED, function (evt, data) {
                        _this.log("level loaded, canceling ratelimits");
                        _this.limiter.cancel();
                        _this.vsq.showTag(_this.type);
                    });
                    this.hls.on(Hls.Events.ERROR, function (evt, data) {
                        _this.onError(evt, data);
                    });
                    this.hls.attachMedia(this.vsq.getVideoTags()[type]);
                };
                VSQHLS.prototype.initLimiter = function () {
                    var _this = this;
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
                };
                VSQHLS.prototype.log = function () {
                    var params = [];
                    for (var _i = 0; _i < arguments.length; _i++) {
                        params[_i] = arguments[_i];
                    }
                    if (!VSQ_4.VSQ.debug)
                        return;
                    params.unshift("[VSQHLS-" + this.type + "]");
                    console.log.apply(console, params);
                };
                VSQHLS.prototype.startLoad = function (at) {
                    this.hls.startLoad(at);
                };
                VSQHLS.prototype.stopLoad = function () {
                    this.hls.stopLoad();
                    this.flushBuffer();
                };
                VSQHLS.prototype.destroy = function () {
                    this.hls.destroy();
                };
                VSQHLS.prototype.flushBuffer = function () {
                    this.hls.trigger(Hls.Events.BUFFER_FLUSHING, {
                        startOffset: 0,
                        endOffset: Number.POSITIVE_INFINITY
                    });
                };
                VSQHLS.prototype.on = function (evt, cb) {
                    this.hls.on(evt, cb);
                };
                Object.defineProperty(VSQHLS.prototype, "startLevel", {
                    get: function () {
                        return this.hls.startLevel;
                    },
                    set: function (level) {
                        this.hls.startLevel = level;
                    },
                    enumerable: true,
                    configurable: true
                });
                Object.defineProperty(VSQHLS.prototype, "currentLevel", {
                    get: function () {
                        return this.hls.currentLevel;
                    },
                    set: function (level) {
                        this.hls.currentLevel = level;
                    },
                    enumerable: true,
                    configurable: true
                });
                VSQHLS.prototype.onMediaAttached = function (evt, data) {
                    this.hls.loadSource(this.video.src);
                };
                VSQHLS.prototype.onManifestParsed = function (evt, data) {
                    this.log("canceling ratelimits");
                    this.limiter.cancel();
                };
                VSQHLS.prototype.showSeeking = function () {
                    this.flowroot.removeClass('is-paused');
                    this.flowroot.addClass('is-seeking');
                };
                VSQHLS.prototype.onError = function (evt, data) {
                    this.log("error", evt, data);
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            switch (data.details) {
                                case Hls.ErrorDetails.MANIFEST_LOAD_ERROR:
                                    if (data.response && data.response.code === 403) {
                                        this.onAccessError(evt, data);
                                        return;
                                    }
                                    break;
                                case Hls.ErrorDetails.LEVEL_LOAD_ERROR:
                                    if (data.response && data.response.code === 404) {
                                        this.vsq.hideTag(this.type);
                                        this.onLevelLoadError(evt, data);
                                        return;
                                    }
                                    break;
                            }
                            this.vsq.hideTag(this.type);
                            this.limiter.trigger("onNetworkError");
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            this.onMediaError(evt, data);
                            return;
                    }
                    this.onUnhandledError(evt, data);
                };
                VSQHLS.prototype.onAccessError = function (evt, data) {
                    this.flow.trigger("error", [this.flow, { code: VSQ_4.VSQ.accessDeniedError }]);
                };
                VSQHLS.prototype.onLevelLoadError = function (evt, data) {
                    this.flushBuffer();
                    var level = data.context.level;
                    if (level != 0 && level <= this.video['vsq-labels'].length - 1)
                        this.hls.currentLevel = level - 1;
                    else
                        this.limiter.trigger("onNetworkError");
                };
                VSQHLS.prototype.onMediaError = function (evt, data) {
                    if (!data.fatal)
                        return;
                    this.flushBuffer();
                    this.limiter.trigger("onSwapAudioCodec");
                    this.limiter.trigger("onRecoverMedia");
                };
                VSQHLS.prototype.onUnhandledError = function (evt, data) {
                    if (!data.fatal)
                        return;
                    this.flushBuffer();
                    this.showSeeking();
                    this.flow.trigger("error", [this.flow, { code: 2 }]);
                };
                return VSQHLS;
            }());
            exports_15("default", VSQHLS);
        }
    };
});
System.register("player/VSQ", ["player/VSQ/LayoutChooser", "player/VSQ/QualityChooser", "player/VSQ/Modal", "player/VSQ/Pinger", "player/VSQ/Login", "player/VSQ/ProgressReport", "player/VSQHLS", "player/VSQAPI"], function (exports_16, context_16) {
    "use strict";
    var __moduleName = context_16 && context_16.id;
    var LayoutChooser_1, QualityChooser_1, Modal_3, Pinger_1, Login_1, ProgressReport_1, VSQHLS_1, VSQAPI_3, VSQ, VSQType;
    return {
        setters: [
            function (LayoutChooser_1_1) {
                LayoutChooser_1 = LayoutChooser_1_1;
            },
            function (QualityChooser_1_1) {
                QualityChooser_1 = QualityChooser_1_1;
            },
            function (Modal_3_1) {
                Modal_3 = Modal_3_1;
            },
            function (Pinger_1_1) {
                Pinger_1 = Pinger_1_1;
            },
            function (Login_1_1) {
                Login_1 = Login_1_1;
            },
            function (ProgressReport_1_1) {
                ProgressReport_1 = ProgressReport_1_1;
            },
            function (VSQHLS_1_1) {
                VSQHLS_1 = VSQHLS_1_1;
            },
            function (VSQAPI_3_1) {
                VSQAPI_3 = VSQAPI_3_1;
            }
        ],
        execute: function () {
            VSQ = (function () {
                function VSQ(flow, root) {
                    this.loadedCount = 0;
                    this.longerType = VSQType.MASTER;
                    this.videoTags = [];
                    this.videoInfo = [];
                    this.hlsEngines = [];
                    this.eventsInitialized = false;
                    this.readySent = false;
                    this.introOrOutro = false;
                    this.plugins = [];
                    VSQ.log("constructor", arguments);
                    this.flow = flow;
                    this.cfg = flow.conf.vsq || {};
                    this.l = this.cfg.locale;
                    VSQAPI_3.default.init(this.cfg);
                    this.flow.conf.errors.push(this.l.get('access_denied'));
                    VSQ.accessDeniedError = flow.conf.errors.length - 1;
                    this.hlsConf = jQuery.extend({
                        bufferWhilePaused: true,
                        smoothSwitching: true,
                        recoverMediaError: true
                    }, flowplayer.conf['hlsjs'], this.flow.conf['hlsjs'], this.flow.conf['clip']['hlsjs']);
                    VSQ.debug = !!this.cfg.debug;
                    this.root = jQuery("#player");
                    this.flowroot = this.root.find('.flowplayer');
                    this.id = this.flowroot.attr('data-flowplayer-instance-id');
                    if (!this.cfg.contentOnRight)
                        this.flowroot.addClass('vsq-contentleft');
                    this.plugins.push(new Modal_3.Modal(this));
                    if (this.cfg.needPing)
                        this.plugins.push(new Pinger_1.default(this));
                    if (this.cfg.needLogin)
                        this.plugins.push(new Login_1.default(this));
                    if (!this.cfg.isAudioOnly) {
                        this.plugins.push(new LayoutChooser_1.default(this));
                        this.plugins.push(new QualityChooser_1.default(this));
                    }
                    this.plugins.push(new ProgressReport_1.default(this));
                }
                VSQ.prototype.getRoot = function () {
                    return this.root;
                };
                VSQ.prototype.getFlowRoot = function () {
                    return this.flowroot;
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
                    this.flowroot.children('a[href*="flowplayer.org"]').hide();
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
                    var master = jQuery(this.videoTags[VSQType.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.flowroot.addClass("is-poster");
                        _this.flow.poster = true;
                    });
                };
                VSQ.prototype.removePoster = function () {
                    var _this = this;
                    if (!this.flow.poster)
                        return;
                    var master = jQuery(this.videoTags[VSQType.MASTER]);
                    master.one(this.eventName("timeupdate"), function () {
                        _this.flowroot.removeClass("is-poster");
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
                        return type === VSQType.MASTER;
                    return type === this.longerType;
                };
                VSQ.prototype.syncVideos = function () {
                    if (!this.hasMultipleVideos())
                        return;
                    var master = this.videoTags[VSQType.MASTER];
                    var content = this.videoTags[VSQType.CONTENT];
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
                        if (this.loadedCount != vidCount && !this.flow.live) {
                            e.stopImmediatePropagation();
                            return false;
                        }
                        if (this.flow.live && this.loadedCount == 2)
                            this.hlsCall('startLoad');
                        if (vidCount > 1 &&
                            this.videoTags[VSQType.CONTENT].duration > this.videoTags[VSQType.MASTER].duration)
                            this.longerType = VSQType.CONTENT;
                    }
                    else
                        this.longerType = VSQType.MASTER;
                    if (this.readySent)
                        return;
                    this.readySent = true;
                    var tag = this.videoTags[this.longerType];
                    var seekable;
                    if (tag.seekable.length > 0)
                        seekable = !!tag.seekable.end(0);
                    else
                        seekable = false;
                    var data = jQuery.extend(this.flow.video, {
                        duration: tag.duration,
                        seekable: seekable,
                        width: tag.videoWidth,
                        height: tag.videoHeight,
                        url: this.videoInfo[VSQType.MASTER].src
                    });
                    this.triggerFlow("ready", data);
                    if (this.flow.video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
                        this.tagCall('play');
                    return false;
                };
                VSQ.prototype.handlePlay = function (e) {
                    var tag = e.currentTarget;
                    if (tag.currentTime >= tag.duration)
                        tag.currentTime = 0;
                    var type = this.getTypeFromEvent(e);
                    if (type === VSQType.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    this.removePoster();
                    if (!this.hlsConf.bufferWhilePaused) {
                        if (this.introOrOutro)
                            this.hlsEngines[VSQType.MASTER].startLoad(tag.currentTime);
                        else
                            this.hlsCall('startLoad', [tag.currentTime]);
                    }
                    this.triggerFlow("resume", undefined);
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
                    this.triggerFlow("pause", undefined);
                };
                VSQ.prototype.handleEnded = function (e) {
                    if (!this.isLongerVideo(e)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var video = this.flow.video;
                    this.hlsCall('flushBuffer');
                    this.tagCall('pause');
                    if (this.introOrOutro && !video.is_last) {
                        this.flow.next();
                        if (video.index === 0 && this.cfg.masterIndex !== 0) {
                            this.flow.removePlaylistItem(0);
                            this.cfg.masterIndex--;
                        }
                    }
                    if (video.is_last)
                        this.triggerFlow("finish", undefined);
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
                    this.triggerFlow("buffer", buffer);
                };
                VSQ.prototype.handleRateChange = function (e) {
                    if (!this.isLongerVideo(e)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerFlow("speed", tag.playbackRate);
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
                    this.triggerFlow("seek", tag.currentTime);
                    return false;
                };
                VSQ.prototype.handleTimeUpdate = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if ((this.introOrOutro && type !== VSQType.MASTER) ||
                        (!this.introOrOutro && type !== this.longerType)) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = this.videoTags[this.longerType];
                    this.triggerFlow("progress", tag.currentTime);
                    this.syncVideos();
                };
                VSQ.prototype.handleVolumeChange = function (e) {
                    var type = this.getTypeFromEvent(e);
                    if (type === VSQType.CONTENT) {
                        e.stopImmediatePropagation();
                        return false;
                    }
                    var tag = e.currentTarget;
                    this.triggerFlow("volume", tag.volume);
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
                VSQ.prototype.triggerFlow = function (event, data) {
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
                        return VSQType.MASTER;
                    return VSQType.CONTENT;
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
                            jQuery(this.videoTags[VSQType.MASTER]).one(this.eventName("seeked"), function () {
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
                    this.hlsEngines[type] = new VSQHLS_1.default(this, type);
                    for (var i = this.plugins.length - 1; i >= 0; i--)
                        this.plugins[i].setupHLS(this.hlsEngines[type], type);
                };
                VSQ.prototype.load = function (video) {
                    var _this = this;
                    this.introOrOutro = true;
                    if ((video.index === 0 && video.is_last) || video.index === this.cfg.masterIndex)
                        this.introOrOutro = false;
                    if (video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
                        video.autoplay = true;
                    var root = this.flowroot.find('.fp-player');
                    root.find('img').remove();
                    this.hlsConf = jQuery.extend(this.hlsConf, this.flow.conf.hlsjs, this.flow.conf.clip.hlsjs, video.hlsjs);
                    if (video.index > this.cfg.masterIndex && this.videoTags[VSQType.CONTENT])
                        this.destroyVideoTag(VSQType.CONTENT);
                    if (video.index === this.cfg.masterIndex &&
                        this.hasMultipleVideos()) {
                        if (this.videoTags[VSQType.CONTENT])
                            this.destroyVideoTag(VSQType.CONTENT);
                        var secondVideo = jQuery.extend(true, {}, video);
                        secondVideo.src = this.cfg.secondarySources[0].src;
                        secondVideo['vsq-labels'] = this.cfg.secondarySources[0]['vsq-labels'];
                        secondVideo.sources = this.cfg.secondarySources;
                        this.videoInfo[VSQType.CONTENT] = secondVideo;
                        this.videoTags[VSQType.CONTENT] = this.createVideoTag(secondVideo);
                        this.videoTags[VSQType.CONTENT].load();
                        var engine_1 = jQuery(this.videoTags[VSQType.CONTENT]);
                        engine_1.addClass('vsq-content');
                        root.prepend(engine_1);
                        this.setupHLS(VSQType.CONTENT);
                    }
                    if (this.videoTags[VSQType.MASTER])
                        this.destroyVideoTag(VSQType.MASTER);
                    this.videoInfo[VSQType.MASTER] = video;
                    this.videoTags[VSQType.MASTER] = this.createVideoTag(video);
                    this.videoTags[VSQType.MASTER].load();
                    var engine = jQuery(this.videoTags[VSQType.MASTER]);
                    engine.addClass('vsq-master');
                    if (video.index !== this.cfg.masterIndex ||
                        !this.hasMultipleVideos())
                        engine.addClass("vsq-fullscale");
                    root.prepend(engine);
                    this.setupHLS(VSQType.MASTER);
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
                        this.videoTags[VSQType.MASTER].play();
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
                    this.flowroot.off(listeners);
                    videoTags.off(listeners);
                    for (var i = this.hlsEngines.length - 1; i >= 0; i--)
                        this.hlsEngines.pop();
                    for (var i = this.videoTags.length - 1; i >= 0; i--)
                        this.videoTags.pop();
                };
                VSQ.prototype.seek = function (to) {
                    if (!this.hasMultipleVideos()) {
                        this.videoTags[VSQType.MASTER].currentTime = to;
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
                VSQ.prototype.hideTag = function (type) {
                    var typ = type == VSQType.MASTER ? 'master' : 'content';
                    this.flowroot.addClass("vsq-hidden-" + type);
                    if (this.flowroot.hasClass("vsq-hidden-master vsq-hidden-content")) {
                        var msg = void 0;
                        if (this.flow.live)
                            msg = this.l.get('networkerror_live');
                        else
                            msg = this.l.get('networkerror_recordings');
                        Modal_3.Modal.showTransientMessage(msg);
                    }
                    LayoutChooser_1.default.resetSize();
                };
                VSQ.prototype.showTag = function (type) {
                    var typ = type == VSQType.MASTER ? 'master' : 'content';
                    this.flowroot.removeClass("vsq-hidden-" + type);
                    Modal_3.Modal.hideTransientMessage();
                    this.flowroot.find('.vsq-layoutchooser input[name="ratio"]').change();
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
            exports_16("VSQ", VSQ);
            (function (VSQType) {
                VSQType[VSQType["MASTER"] = 0] = "MASTER";
                VSQType[VSQType["CONTENT"] = 1] = "CONTENT";
            })(VSQType || (VSQType = {}));
            exports_16("VSQType", VSQType);
        }
    };
});
System.register("player/PlayerSetup", ["player/Flash", "player/VSQ"], function (exports_17, context_17) {
    "use strict";
    var __moduleName = context_17 && context_17.id;
    var Flash_1, VSQ_5, PlayerSetup;
    return {
        setters: [
            function (Flash_1_1) {
                Flash_1 = Flash_1_1;
            },
            function (VSQ_5_1) {
                VSQ_5 = VSQ_5_1;
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
                    VSQ_5.VSQ.setup();
                };
                return PlayerSetup;
            }());
            exports_17("default", PlayerSetup);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/PlayerSetup"], function (exports_18, context_18) {
    "use strict";
    var __moduleName = context_18 && context_18.id;
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