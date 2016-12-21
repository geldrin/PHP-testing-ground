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

    return "";
  }

  static getImageDimensions(url: string, cb: (width: number, height: number) => void): void {
    $('<img/>', {
      load : function() {
        cb(this.width, this.height)
      },
      src: url
    });
  }

  static setToStorage(key: string, value: any): void {
    let raw = JSON.stringify(value);
    localStorage.setItem(key, raw);
  }

  static getFromStorage(key: string, def?: any): any {
    let raw = localStorage.getItem(key);
    if (raw == null)
      return def;

    let data: any;
    try {
      data = JSON.parse(raw);
    } catch(_) {
      return def;
    }

    return data;
  }
}
