/**
 * Created by NicolasGERARD on 11/18/2015.
 */
let WEBCODE = {
    appendLine: function (text) {
        let webConsoleLine = document.createElement("p");
        webConsoleLine.className = "webCodeConsoleLine";
        webConsoleLine.innerHTML = text;
        WEBCODE.appendChild(webConsoleLine);
    },
    appendChild: function (element) {
        document.querySelector("#webCodeConsole").appendChild(element);
    },
    print: function (v) {
        if (typeof v === 'undefined') {
            return "(Undefined)"; // Undefined == null, therefore it must be in first position
        } else if (Array.isArray(v)) {
            if (v.length === 0) {
                return "(Empty Array)";
            } else {
                return v;
            }
        } else if (typeof v === 'string') {
            if (v.length === 0) {
                return "(Empty String)"
            } else {
                return v;
            }
        } else if (v === null) {
            return "(null)";
        } else {
            return v;
        }
    },
    htmlEntities: function(str) {
        // from https://css-tricks.com/snippets/javascript/htmlentities-for-javascript/
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
};


window.console.log = function (input) {
    let s = "";
    if (typeof input === "object") {
        s = "{\n";
        let keys = Object.keys(input);
        for (let i = 0; i < keys.length; i++) {
            // &nbsp; = one space in HTML
            s += "&nbsp;&nbsp;" + keys[i] + " : " + input[keys[i]] + ";\n";
        }
        s += "}\n";
    } else {
        s = String(input);
    }
    s = s.replace(/\n/g, '<BR>')
    WEBCODE.appendLine(WEBCODE.htmlEntities(s));
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
            let tableElement = document.createElement("table");
            let theadElement = document.createElement("thead");
            let tbodyElement = document.createElement("tbody");
            let trElement = document.createElement("tr");
            let tdElement = document.createElement("td");

            tableElement.appendChild(theadElement);
            tableElement.appendChild(tbodyElement);
            theadElement.appendChild(trElement);


            for (let i = 0; i < input.length; i++) {

                let element = input[i];

                // First iteration, we pick the headers
                if (i === 0) {

                    if (typeof element === 'object') {
                        for (prop in element) {
                            var thElement = document.createElement("th");
                            thElement.innerHTML = WEBCODE.print(prop);
                            trElement.appendChild(thElement);
                        }
                    } else {
                        // Header
                        let thElement = document.createElement("th");
                        thElement.innerHTML = "Values";
                        trElement.appendChild(thElement);
                    }

                }

                let trElement = trElement.cloneNode(false);
                tbodyElement.appendChild(trElement);

                if (typeof input[0] === 'object') {
                    for (prop in element) {
                        var tdElement = tdElement.cloneNode(false);
                        tdElement.innerHTML = WEBCODE.print(element[prop]);
                        trElement.appendChild(tdElement);
                    }
                } else {
                    let tdElement = tdElement.cloneNode(false);
                    tdElement.innerHTML = WEBCODE.print(element);
                    trElement.appendChild(tdElement);
                }

            }
            WEBCODE.appendChild(tableElement);

        }
    }
};

