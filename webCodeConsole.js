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


console = {

    webConsole: document.querySelector("#webCodeConsole"),
    log: function (input) {
        var webConsoleLine = document.createElement("p");
        webConsoleLine.className = "webCodeConsoleLine";
        webConsoleLine.innerHTML = String(input);
        this.webConsole.appendChild(webConsoleLine);
    }

};

