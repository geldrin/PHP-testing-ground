"use strict";
export default class Tools {
  static parseParamsFromUrl(): Object {
    if (!location.search)
      return {};

    let query = location.search.substr(1);
    let result = {};
    query.split("&").forEach(function(part) {
      let item = part.split("=");
      if (item.length == 1)
        return;

      let name = decodeURIComponent(item[0]);
      let value = decodeURIComponent(item[1]);
      result[name] = value;
    });
    return result;
  }

  static parseURLFromCSS(css: string): string {
    let match = css.match(/url\(["']?([^)]+?)['"]?\)/);
    if (match)
      return match[1];

    return null;
  }

  static getImageDimensions(url: string, cb) {
    $('<img/>', {
      load : function() {
        cb(this.width, this.height)
      },
      src: url
    });
  }
}
