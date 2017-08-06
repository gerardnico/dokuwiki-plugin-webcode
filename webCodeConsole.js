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

var WEBCODE = {
    appendLine: function (text) {
        var webConsoleLine = document.createElement("p");
        webConsoleLine.className = "webCodeConsoleLine";
        webConsoleLine.innerHTML = text;
        WEBCODE.appendChild(webConsoleLine);
    },
    appendChild: function (element) {
        document.querySelector("#webCodeConsole").appendChild(element);
    }
}


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
    WEBCODE.appendLine(s);
};

// Console table implementation
// https://developer.mozilla.org/en-US/docs/Web/API/Console/table
window.console.table = function (input) {
    if (Array.isArray(input) !== true) {

        WEBCODE.appendLine("The variable of the function console.table must be an array.");

    } else {
        if (input.length <= 0) {

            WEBCODE.appendLine("The variable of the console.table has no elements.");

        } else {
            // HTML Headers
            var tableElement = document.createElement("table");
            var theadElement = document.createElement("thead");
            var tbodyElement = document.createElement("tbody");
            var trElement = document.createElement("tr");
            var tdElement = document.createElement("td");

            tableElement.appendChild(theadElement);
            tableElement.appendChild(tbodyElement);
            theadElement.appendChild(trElement);


            for (var i = 0; i < input.length; i++) {

                var element = input[i];

                // First iteration, we pick the headers
                if (i == 0) {

                    if (typeof input[0] == 'object') {
                        for (prop in element) {
                            var thElement = document.createElement("th");
                            thElement.innerHTML = prop;
                            trElement.appendChild(thElement);
                        }
                    } else {
                        // Header
                        var thElement = document.createElement("th");
                        thElement.innerHTML = "Values";
                        trElement.appendChild(thElement);
                    }

                }

                var trElement = trElement.cloneNode(false);
                tbodyElement.appendChild(trElement);

                if (typeof input[0] == 'object') {
                    for (prop in element) {
                        var tdElement = tdElement.cloneNode(false);
                        tdElement.innerHTML = element[prop];
                        trElement.appendChild(tdElement);
                    }
                } else {
                    var tdElement = tdElement.cloneNode(false);
                    tdElement.innerHTML = element.toString();
                    trElement.appendChild(tdElement);
                }

            }
            WEBCODE.appendChild(tableElement);

        }
    }
};

