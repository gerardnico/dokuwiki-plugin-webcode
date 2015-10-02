# dokuwiki-plugin-webcode

## Usage

The [Webocde Dokuwiki plugin](https://www.dokuwiki.org/plugin:webcode)  renders the output of CSS and HTML block code.

By enclosing the code blocks by a webcode block, the plugin will add the result after.

See the webcode plugin page [here](https://www.dokuwiki.org/plugin:webcode)

## Example

See the plugin in action [here](http://gerardnico.com/wiki/dokuwiki/webcode).

![The illustration](https://github.com/gerardnico/dokuwiki-plugin-webcode/blob/master/images/webcode_plugin_illustration.png "Webcode Illustration")

## Installation

Install the plugin using:

  * the [Plugin Manager](https://www.dokuwiki.org/plugin:plugin)
  * [manually](https://www.dokuwiki.org/plugin:Plugins) with the [download URL](http://github.com/gerardnico/dokuwiki-plugin-webcode/zipball/master), which points to latest version of the plugin.


## Syntax

```xml
<webcode width=100% frameborder=0 height=250px>

    // wiki syntax with css or html block code.
    Full dokuwiki syntax are permitted between blocks

    // css code block
    <code css>
    </code>

    // html code block
    <code html>
    </code>

    // An xml block may be use in place of an html one
    <code xml>
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

The actual code block supported are:

  * code css
  * code html or code xml. Xml will be seen as HTML.

## Configuration and Settings
None

## Technically

Technically, the plugin:

  * parses the content between the two webocde tag,
  * extracts the css and html code
  * and adds after the last webcode tag an [iframe](https://docs.webplatform.org/wiki/html/elements/iframe).


