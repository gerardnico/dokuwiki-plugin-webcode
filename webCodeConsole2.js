/**
 * Created by NicolasGERARD on 11/18/2015.
 */
// As per https://www.dokuwiki.org/devel:javascript
// If the javascript file is not lib/plugins/*/script.js
// It will not be placed in the js

// This file is used in the iframe if the window console function
// is used in order to redirect the output in the HTML page
// in a div container


console = {

    webConsole: document.querySelector("#webCodeConsole"),
    log: function (text) {
        var webConsoleLine = document.createElement("p");
        webConsoleLine.className = "webCodeConsoleLine";
        webConsoleLine.innerHTML = text;
        this.webConsole.appendChild(webConsoleLine);
    }

};
