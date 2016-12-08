System.register("debug/Debug", [], function (exports_1, context_1) {
    "use strict";
    var __moduleName = context_1 && context_1.id;
    var Debug;
    return {
        setters: [],
        execute: function () {
            Debug = (function () {
                function Debug($) {
                    this.$ = $;
                    this.url = BASE_URI + 'telemetry/exception';
                    this.stringify = (JSON || {}).stringify;
                }
                Debug.prototype.init = function () {
                    var _this = this;
                    if (!this.stringify)
                        return;
                    setTimeout(function () {
                        throw new Error("asd");
                    }, 5000);
                    TraceKit.report.subscribe(function (stack) {
                        _this.onError(stack);
                    });
                };
                Debug.prototype.onError = function (stack) {
                    var data = this.stringify(stack);
                    this.$.ajax({
                        url: this.url,
                        contentType: 'application/json',
                        data: data,
                        type: 'POST',
                        timeout: 5000
                    });
                };
                return Debug;
            }());
            exports_1("default", Debug);
        }
    };
});
System.register("debug/app", ["debug/Debug"], function (exports_2, context_2) {
    "use strict";
    var __moduleName = context_2 && context_2.id;
    var Debug_1;
    return {
        setters: [
            function (Debug_1_1) {
                Debug_1 = Debug_1_1;
            }
        ],
        execute: function () {
            (function ($) {
                var d = new Debug_1["default"]($);
                d.init();
            })(jQuery);
        }
    };
});
//# sourceMappingURL=app.js.map