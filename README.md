# dokuwiki-plugin-webcode

## Usage

The [Webocde Dokuwiki plugin](https://www.dokuwiki.org/plugin:webcode)  renders the output of:

  * CSS
  * HTML
  * and Javascript

code block.

By enclosing the [code blocks](https://www.dokuwiki.org/wiki:syntax#code_blocks) by a `<webcode>` block, the plugin will add the result after the last webcode tag.

See the webcode plugin page on Dokuwiki [here](https://www.dokuwiki.org/plugin:webcode)

## Example

See the plugin in action [here](http://gerardnico.com/wiki/dokuwiki/webcode).

## Illustration

![The illustration](https://github.com/gerardnico/dokuwiki-plugin-webcode/blob/master/images/webcode_plugin_illustration.png "Webcode Illustration")

## Installation

Install the plugin using:

  * the [Plugin Manager](https://www.dokuwiki.org/plugin:plugin)
  * [manually](https://www.dokuwiki.org/plugin:Plugins) with the [download URL](http://github.com/gerardnico/dokuwiki-plugin-webcode/zipball/master), which points to latest version of the plugin.


## Syntax

```xml

<webcode width=100% frameborder=0 height=250px>

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

    <!-- javascript code block -->
    <code javascript>
    </code>

</webcode>
```
The allowed webcode attributes are the attributes of the [iframe element](https://docs.webplatform.org/wiki/html/elements/iframe)

The most well known are:

  * frameborder (default to 0)
  * width
  * height

The scale of the attributes values may be:

  * %
  * or px (pixel)

The actual [code blocks](https://www.dokuwiki.org/wiki:syntax#code_blocks) supported are:

  * code html or code xml. Xml will be seen as XHTML.
  * code css
  * code javascript


## Configuration and Settings
None

## Technically

Technically, the plugin:

  * parses the content between the two `<webcode>` tag,
  * extracts the html, css and javascript code,
  * adds after the last webcode tag an [iframe](https://docs.webplatform.org/wiki/html/elements/iframe),
  * and a button that permits to play with the code on [JsFiddle](https://jsfiddle.net)


