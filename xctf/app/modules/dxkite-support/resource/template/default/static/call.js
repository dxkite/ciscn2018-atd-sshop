; /*! dx call */
window.dx = window.dx || {};
(function (dx) {
    // 全局严格模式
    "use strict";
    var call_id = 0;
    var thatDom = function () {
        var scripts = document.getElementsByTagName("script");
        return scripts[scripts.length - 1];
    }();

    function call(proxy, method, params, thatParent) {
        var that = thatParent || this;
        var ajax = new XMLHttpRequest;
        var url = null;
        var baseUrl = null;

        if (typeof params == 'undefined') {
            params = method;
            method = proxy;
            baseUrl = params.url || thatDom.dataset.api;
            url = baseUrl;
        } else {
            baseUrl = params.url || thatDom.dataset.api;
            url = baseUrl + '/' + proxy;
        }
        if (/\:\/\//.test(proxy)) {
            url = proxy;
        }
        ajax.addEventListener("readystatechange", function () {
            if (ajax.readyState == 4) {
                if (ajax.status == 200) {
                    var json = JSON.parse(ajax.responseText);
                    if (params.finish) {
                        params.finish.call(that, json);
                    }
                    if (typeof json.result != 'undefined') {
                        if (params.success) {
                            params.success.call(that, json.result);
                        }
                    } else {
                        if (params.error) {
                            params.error.call(that, json.error);
                        } else {
                            console.error(json.error.name + ":" + json.error.message, json);
                        }
                    }
                } else {
                    if (params.fail) {
                        params.fail.call(that, ajax);
                    } else {
                        console.error('server return ' + ajax.status);
                    }
                }
            }
        });

        function objectHasFile(args) {
            for (var index in args) {
                if (args[index] instanceof File || args[index] instanceof Blob) {
                    return true;
                }
            }
            return false;
        }

        var param = null;
        if (typeof params.args == 'undefined') {
            param = [];
        } else if (params.args instanceof Array) {
            param = params.args;
        } else {
            if (!(params.args instanceof FormData) && objectHasFile(params.args)) {
                var form = new FormData();
                for (var name in params.args) {
                    form.append(name, params.args[name]);
                }
                param = form;
            } else {
                param = params.args;
            }
        }

        if (param instanceof FormData) {
            ajax.open("POST", url + '?method=' + method);
            ajax.send(param);
        } else {
            ajax.open("POST", url);
            ajax.setRequestHeader("Content-Type", "application/json");
            ajax.send(JSON.stringify({
                method: method,
                params: param,
                id: call_id++,
            }));
        }
    }
    dx.xcall = call;

    dx.acall = function (name, method, args) {
        return new Promise((resolve, reject) => {
            if (typeof args == 'undefined') {
                if (typeof method == 'string') {
                    dx.xcall(name, method, {
                        args: [],
                        finish: resolve,
                        error: reject,
                        fail: reject,
                    });
                } else {
                    args = method;
                    method = name;
                    dx.xcall(method, {
                        args: args || [],
                        finish: resolve,
                        error: reject,
                        fail: reject,
                    });
                }

            } else {
                dx.xcall(name, method, {
                    args: args || [],
                    finish: resolve,
                    error: reject,
                    fail: reject,
                });
            }
        });
    }
})(window.dx);