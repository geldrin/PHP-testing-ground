"use strict";
export default class Tools {
  public static parseParamsFromUrl(): Object {
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

  public static parseURLFromCSS(css: string): string {
    let match = css.match(/url\(["']?([^)]+?)['"]?\)/);
    if (match)
      return match[1];

    return "";
  }

  public static getImageDimensions(url: string, cb: (width: number, height: number) => void): void {
    $('<img/>', {
      load : function() {
        cb(this.width, this.height)
      },
      src: url
    });
  }

  public static setToStorage(key: string, value: any): void {
    let raw = JSON.stringify(value);
    localStorage.setItem(key, raw);
  }

  public static getFromStorage(key: string, def?: any): any {
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

  public static now(): number {
    return Date.now();
  }

  public static zeroPad(num: number): string {
    return num >= 10? "" + num: "0" + num;
  }

  public static formatDuration(seconds: number): string {
    let s = Math.max(seconds, 0);
    s = Math.floor(s);

    let h = Math.floor(seconds / 3600);
    let m = Math.floor(seconds / 60);

    s -= m * 60;
    if (h >= 1) {
      m -= h * 60;
      return h + ":" + Tools.zeroPad(m) + ":" + Tools.zeroPad(s);
    }

    return Tools.zeroPad(m) + ":" + Tools.zeroPad(s);
  }
}
