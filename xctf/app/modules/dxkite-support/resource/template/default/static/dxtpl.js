/*! dxtpl by DXkite 2016-12-14 */
!function(window) {
    function statmentTest(test, code) {
        try {
            new Function(test);
        } catch (e) {
            return "throw " + e.name + "(" + _string(e.message) + ");{";
        }
        return code;
    }
    function parserHTML(html, compress) {
        var out = "";
        return html.match(/(?!^)\n/) ? _each(html.split("\n"), function(html) {
            html && (compress && (html = html.replace(/\s+/g, " ").replace(/<!--.*?-->/g, "")), 
            html && (out += ENGINE[1] + _string(html) + ENGINE[2], out += "\n"));
        }) : html && (out += ENGINE[1] + _string(html) + ENGINE[2]), out;
    }
    function parserCode(code) {
        var match;
        if (!(match = code.match(new RegExp(KEYWORD_PREG)))) return (match = code.match(/^!.*$/)) ? ENGINE[1] + "$_unit._echo(" + match[1] + ")" + ENGINE[2] : ENGINE[1] + "$_unit._escape(" + code + ")" + ENGINE[2];
        var command = match[1], param = match[2];
        switch (command) {
          case "include":
            return param = param.trim().split(" "), 1 === param.length && param.push("$_unit.value"), 
            param = param.join(","), ENGINE[1] + "$_unit._include(" + param + ")" + ENGINE[2];

          case "if":
            return statmentTest("if(" + param + "){}", "if (" + param + ") {");

          case "else":
            return (match = param.match(/^\s*if\s+(.*)/)) ? "} else if (" + match[1] + "){" : "}else{";

          case "/if":
          case "/while":
          case "/for":
            return "}";

          case "while":
            return statmentTest("while(" + param + "){}", "while (" + param + ") {");

          case "for":
            return statmentTest("for(" + param + "){}", "for (" + param + ") {");

          case "each":
            var match = param.match(/(\w+)\s+(?:(?:as(?:\s+(\w+)))?(?:(?:\s+:)?\s+(\w+))?)?/);
            if (match) {
                var each_param, value = match[1];
                return each_param = match[2] ? match[3] ? match[3] + "," + match[2] : match[2] : "value,index", 
                "$_unit._each(" + value + ",function(" + each_param + "){";
            }
            return 'throw SyntaxError("Null Each Value");$_unit._each(null,function(){';

          case "/each":
            return "});";
        }
    }
    function _string(code) {
        return "'" + code.replace(/('|\\)/g, "\\$1").replace(/\r/g, "\\r").replace(/\n/g, "\\n") + "'";
    }
    function is_array(obj) {
        return "[object Array]" === Object.prototype.toString.call(obj);
    }
    function reportError(name, content, line, e) {
        var name = name || "anonymous", report = "DxTPL Error:";
        if (console.group(report), content) {
            var codes = content.replace(/^\n/, "").split("\n"), start = line - 5 > 0 ? line - 5 : 1, end = line + 5 > codes.length ? codes.length : line + 5;
            console.error(e);
            for (var i = start; i < end; i++) i == line ? console.log(i + "|%c" + codes[line - 1] + "\t\t%c->\t\t%c" + e.name + ":" + e.message, "color:red;", "color:green;", "color:red;") : console.log(i + "|" + codes[i - 1]);
        } else console.log(content), console.log("%c" + report + e.message + "\t\t@" + name + ":" + line, "color:red;");
        console.groupEnd(report);
    }
    function compileTemplate(text, config) {
        var tpl = "";
        return text = text.replace(/^\n/, ""), _each(text.split(config.tagstart), function(value) {
            var split = value.split(config.tagend);
            1 === split.length ? tpl += parserHTML(split[0], config.compress) : (tpl += parserCode(split[0]), 
            tpl += parserHTML(split[1]));
        }), tpl;
    }
    function linkValue(source, value, strict) {
        var use_strict = void 0 === strict || strict, ext = [];
        ext.push("var $_unit=this," + ENGINE[0]);
        for (var index in value) ext.push(index + "=this.value." + index);
        var link_str = "";
        return use_strict && (link_str = '"use strict";'), link_str += ext.join(","), link_str += ";", 
        link_str += source + "return new String(" + ENGINE[3] + ");";
    }
    function renderTpl(selector, glovalue) {
        var nodes = document.querySelectorAll(selector);
        _arrayEach(nodes, function(node, index) {
            var value, source = node.innerHTML, config = default_config;
            if (node.dataset.init) try {
                var json = new Function("return " + node.dataset.init + ";");
                value = json();
            } catch (e) {
                reportError(selector + "[" + index + "]", null, 0, new Error("Unsupport json"));
            }
            if (node.dataset.config) try {
                var json = new Function("return " + node.dataset.config + ";"), conf = json();
                config = _objectCopy(config, conf);
            } catch (e) {
                reportError(selector + "[" + index + "]", null, 0, new Error("Unsupport json"));
            }
            value = _objectCopy(value, glovalue);
            var code = compileTemplate(source, config);
            node.innerHTML = render(selector, source, code, value, config.strict);
        });
    }
    function render(name, source, compiled_code, value, strict) {
        var html, runcode = linkValue(compiled_code, value, strict), caller = {
            _each: _each,
            _echo: _echo,
            _escape: _escape,
            _include: _include,
            value: value
        };
        try {
            var render = new Function(runcode);
            html = render.call(caller);
        } catch (e) {
            var match = new String(e.stack).match(/<anonymous>:(\d+):\d+/);
            if (match) {
                var line = match[1] - 1;
                reportError(name, source, line, e);
            } else {
                var name = name || "anonymous", match = new String(e.stack).match(/Function code:(\d+):\d+/);
                match ? console.error("DxTPL:Compile Error@" + name + " Line " + match[1]) : console.error("DxTPL:Compile Error@" + name);
            }
        }
        return html;
    }
    function getDOMcache(name, config) {
        var cache_parent = document.getElementById("template_caches");
        cache_parent || (cache_parent = document.createElement("div"), cache_parent.id = "template_caches", 
        cache_parent.style.display = "none", document.body.appendChild(cache_parent));
        var cache_name = "template_cache_" + name, tpl_cache = document.getElementById("template_cache_" + name);
        return tpl_cache || (tpl_cache = document.createElement("div"), tpl_cache.id = cache_name, 
        tpl_cache.innerText = compileTemplate(document.getElementById(name).innerHTML, config || default_config), 
        cache_parent.appendChild(tpl_cache)), tpl_cache.innerText;
    }
    function compile(id, config) {
        var tplId = id || config.id, anonymous = !1;
        if ("string" != typeof tplId) throw Error("Unsupport Template ID");
        var tpl = document.getElementById(tplId);
        return tpl ? config.source = tpl.innerHTML : (config.source = tplId, config.id = "anonymous", 
        anonymous = !0), config.code || (config.cache && !anonymous ? config.code = getDOMcache(tplId, config) : config.code = compileTemplate(config.source, config)), 
        config;
    }
    var default_config = {
        cache: !0,
        tagstart: "{",
        tagend: "}",
        compress: !0,
        strict: !0
    }, KEYWORD = "if,else,each,include,while,for", KEYWORD_PREG = "^\\s*((?:/)?(?:" + KEYWORD.split(",").join("|") + "))(.*)", ENGINE = "".trim ? [ "$_tpl_=''", "$_tpl_+=", ";", "$_tpl_" ] : [ "$_tpl_=[]", "$_tpl_.push(", ");", "$_tpl_.join('')" ], escape = {
        "<": "&#60;",
        ">": "&#62;",
        '"': "&#34;",
        "'": "&#39;",
        "&": "&#38;"
    }, _echo = function(value) {
        return new String(value);
    }, _escape = function(content) {
        return _echo(content).replace(/&(?![\w#]+;)|[<>"']/g, function(s) {
            return escape[s];
        });
    }, _each = function(value, callback) {
        if (is_array(value)) _arrayEach(value, callback); else for (var index in value) callback.call(value[index], value[index], index);
    }, _arrayEach = function(value, callback) {
        for (var index = 0; index < value.length; ++index) callback.call(value[index], value[index], index);
    }, _objectCopy = function(arrays) {
        for (var object = {}, i = 0; i < arguments.length; i++) for (var index in arguments[i]) object[index] = arguments[i][index];
        return object;
    }, _include = function(id, value) {
        return new Template(id).render(value);
    }, Template = function(name, config) {
        var conf = default_config;
        "string" == typeof name ? (conf = _objectCopy(conf, config), conf.id = name) : conf = _objectCopy(conf, name), 
        this.config(conf);
    };
    Template.prototype.config = function(config) {
        for (var index in config) this[index] = config[index];
        return this;
    }, Template.prototype.assign = function(name, value) {
        return this.value[name] = _objectCopy(this.value[name], value), this;
    }, Template.prototype.value = function(value) {
        return this.value = _objectCopy(this.value, value), this;
    }, Template.prototype.compile = function(id) {
        var config = _objectCopy(this, compile(id, this));
        return new Template(config);
    }, Template.prototype.render = function(value) {
        if (!this.source || !this.code) {
            var val = compile(this.id, this);
            this.config(val);
        }
        return render(this.id, this.source, this.code, value, this.strict);
    }, window.dxtpl = new Template(), window.Template = Template, window.renderTpl = renderTpl;
}(window);