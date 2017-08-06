# dokuwiki-plugin-webcode

## Usage

The [Webcode Dokuwiki plugin](https://www.dokuwiki.org/plugin:webcode)  renders the output of:

  * CSS
  * HTML
  * [Javascript](#javascript) or [Babel](#babel)

code block.

By enclosing the [code blocks](https://www.dokuwiki.org/wiki:syntax#code_blocks) by a `<webcode>` block, the plugin will add the result after the last webcode tag.

See the webcode plugin page on Dokuwiki [here](https://www.dokuwiki.org/plugin:webcode)

## Example

See the plugin in action [here](http://gerardnico.com/wiki/dokuwiki/webcode).

## Illustration

![The illustration](images/webcode_plugin_illustration.png "Webcode Illustration")

## Installation

Install the plugin using:

  * the [Plugin Manager](https://www.dokuwiki.org/plugin:plugin)
  * [manually](https://www.dokuwiki.org/plugin:Plugins) with the [download URL](http://github.com/gerardnico/dokuwiki-plugin-webcode/zipball/master), which points to latest version of the plugin.


## Syntax

```xml

<webcode name="A Name" width=100% frameborder=0 height=250px externalResources="//d3js.org/d3.v3.min.js,https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <!-- wiki syntax with css or html block code. -->
    Full dokuwiki syntax are permitted between blocks

    <!-- css code block -->
    <code css>
    </code>

    <!-- html code block -->
    <code html>
    </code>

    <!-- An xml block may be use in place of an html one -->
    <code xml>
    </code>

    <!-- javascript or babel code block -->
    <code javascript> <!-- or <code babel> -->
    </code>

</webcode>
```

The allowed webcode attributes are:

   * the following attributes of the [iframe element](https://docs.webplatform.org/wiki/html/elements/iframe)
      * name. It will be added as a suffix
      * frameborder (default to 0)
      * width (default to 100%)
      * height
      * scrolling
   * externalResources: a comma separated list of external resources. (Ie an URL of a Css or Js file, generally a CDN)


The actual [code blocks](https://www.dokuwiki.org/wiki:syntax#code_blocks) supported are:

  * code html (or code xml if html is not present). Xml will be seen as XHTML.
  * code css
  * code javascript or babel (but not both)


## Language Support
### Javascript Console

  * The [console.log function](https://developer.mozilla.org/en-US/docs/Web/API/Console/log) will be rendered and therefore visible in a console area (Gray box).
  * The [console.table function](https://developer.mozilla.org/en-US/docs/Web/API/Console/table) is supported only for a collection of objects or primitives. There is no second argument.

### Babel

When a code block use [Babel](https://babeljs.io/) as language, the plugin will add the 
[babel.min.js](https://unpkg.com/babel-standalone@6/babel.min.js) version 6 to the external resources.

You cannot have a Babel and a Javascript Block.

## Technically

Technically, the plugin:

  * parses the content between the two `<webcode>` tag,
  * extracts the html, css and javascript code,
  * adds after the last webcode tag an [iframe](https://docs.webplatform.org/wiki/html/elements/iframe),
  * and a button that permits to play with the code on [JsFiddle](https://jsfiddle.net)


## Changes
### 2017-08-05
  * The 'console.table' function is partially supported
  * The 's' variable leaked from the window.console.log function of the webCodeConsole.js
### 2017-06-07
  * The height of the Iframe is now dynamically calculated. No need to give this attribute anymore if you want to see the whole output.
### 2017-04-28
  * Added [Babel](https://babeljs.io/) support
  * Bugs (Https call to Fiddle in place of Http, externalAttributes Resources in the action bar in place of include, Xml was not replaced by HTML code)
  * Cache bursting implementation for the weCodeConsole.(js|css) file so that they are not cached for an new version.
  * New lines (\n) are now supported in the javascript console.log function.
  * Object of one level are now supported in the javascript console.log function.
  * [JSFiddle bug 726](https://github.com/jsfiddle/jsfiddle-issues/issues/726) makes the resources order not consistent. Solution: More than one resources will be then added in the HTML script element.
### 2017-10-1
  * Two block of the same code are now concatenated
  * Jquery is no more used. It was used for the javascript part of the console functionality.
  * XML is now seen as HTML
  * The ''name'' 
