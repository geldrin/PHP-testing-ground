"use strict";
export default class Escape {
  private static elem = document.createElement('pre');

  // http://stackoverflow.com/questions/3115150/how-to-escape-regular-expression-special-characters-using-javascript
  static RE(text: string): string {
    return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  }

  // https://github.com/angular/angular.js/blob/g3_v1_5/src/ngSanitize/sanitize.js#L407
  static HTML(text: string): string {
    return text.
      replace(/&/g, '&amp;').
      replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, (text: string) => {
        var hi = text.charCodeAt(0);
        var low = text.charCodeAt(1);
        return '&#' + (((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000) + ';';
      }).
      replace(/([^\#-~| |!])/g, (text: string) => {
        return '&#' + text.charCodeAt(0) + ';';
      }).
      replace(/</g, '&lt;').
      replace(/>/g, '&gt;');
  }

  // https://github.com/angular/angular.js/blob/v1.3.14/src/ngSanitize/sanitize.js#L419
  static unescapeHTML(text: string): string {
    if (!text)
      return '';

    Escape.elem.innerHTML = text.replace(/</g, "&lt;");
    return Escape.elem.textContent || "";
  }

  static URL(text: string): string {
    return encodeURI(text);
  }

  static unescapeURL(text: string): string {
    return decodeURI(text);
  }

  private static fileReplace = {
    'á': 'a', 'Á': 'A',
    'é': 'e', 'É': 'E',
    'í': 'i', 'Í': 'I',
    'ó': 'o', 'Ó': 'O',
    'ö': 'o', 'Ö': 'O',
    'ő': 'o', 'Ő': 'O',
    'ú': 'u', 'Ú': 'U',
    'ü': 'u', 'Ü': 'U',
    'ű': 'u', 'Ű': 'U'
  };
  static fileName(text: string, maxLength?: number, allowedExtensions?: string[]): string {
    text = $.trim(text);

    text = text.replace(/[^a-zA-Z0-9_\-\.]/g, (match: string) => {
      let ret = Escape.fileReplace[match];
      if (!ret)
        return '_';

      return ret;
    });

    if (maxLength && text.length > maxLength)
      return '';

    if (allowedExtensions) {
      // ha nincs benne kiterjesztes es kivancsiak vagyunk a kiterjesztesekre
      let dotPos = text.lastIndexOf('.');
      if (allowedExtensions.length != 0 && dotPos < 0)
        return '';

      let ext = text.substring(dotPos + 1);
      let found = false;
      for (let i = allowedExtensions.length - 1; i >= 0; i--) {
        let okExt = allowedExtensions[i];
        if (ext === okExt) {
          found = true;
          break;
        }
      }

      if (!found)
        return '';
    }

    return text;
  }
}
