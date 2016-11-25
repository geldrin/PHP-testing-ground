System.register("player/Config", [], function (exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var Config;
    return {
        setters: [],
        execute: function () {
            Config = (function () {
                function Config(data) {
                    this.flashConfig = data['flashplayer']['config'];
                }
                return Config;
            }());
            exports_1("default", Config);
        }
    };
});
System.register("player/Flash", [], function (exports_2, context_2) {
    "use strict";
    var __moduleName = context_2 && context_2.id;
    var Flash;
    return {
        setters: [],
        execute: function () {
            Flash = (function () {
                function Flash() {
                }
                return Flash;
            }());
            exports_2("default", Flash);
        }
    };
});
System.register("Locale", [], function (exports_3, context_3) {
    "use strict";
    var __moduleName = context_3 && context_3.id;
    var Locale;
    return {
        setters: [],
        execute: function () {
            Locale = (function () {
                function Locale(data) {
                    this.data = data;
                }
                Locale.prototype.get = function (key) {
                    if (this.data[key])
                        return this.data[key];
                    return key;
                };
                return Locale;
            }());
            exports_3("default", Locale);
        }
    };
});
System.register("player/Player", [], function (exports_4, context_4) {
    "use strict";
    var __moduleName = context_4 && context_4.id;
    var Player;
    return {
        setters: [],
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
                return Player;
            }());
            exports_4("default", Player);
        }
    };
});
System.register("player/app", ["Locale", "player/Config", "player/Player"], function (exports_5, context_5) {
    "use strict";
    var __moduleName = context_5 && context_5.id;
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
            $(function () {
                var cfg = new Config_1["default"](playerconfig);
                var loc = new Locale_1["default"](l);
                var player = new Player_1["default"](cfg, loc);
            });
        }
    };
});
//# sourceMappingURL=app.js.map