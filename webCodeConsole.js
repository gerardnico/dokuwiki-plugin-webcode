/**
 * Created by NicolasGERARD on 11/18/2015.
 */
// As per https://www.dokuwiki.org/devel:javascript
// If the javascript file is not lib/plugins/*/script.js
// It will not be placed in the js

// This file is used in the iframe if the window console function
// is used in order to redirect the output in the HTML page
// in a div container

// Don't forget to increment the version number in the CONSTANT WEB_CONSOLE_JS_VERSION of basis.php


window.console.log = function (input) {
    if (typeof input == "object") {
        var s = "{\n";
        var keys = Object.keys(input);
        for (var i = 0; i < keys.length; i++) {
            // &nbsp; = one space in HTML
            s += "&nbsp;&nbsp;" + keys[i] + " : " + input[keys[i]] + ";\n";
        }
        s += "}\n";
    } else {
        s = String(input);
    }
    s = s.replace(/\n/g, '<BR>')
    var webConsoleLine = document.createElement("p");
    webConsoleLine.className = "webCodeConsoleLine";
    webConsoleLine.innerHTML = s;
    document.querySelector("#webCodeConsole").appendChild(webConsoleLine);
};

