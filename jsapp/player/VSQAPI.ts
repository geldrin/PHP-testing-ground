/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import Tools from "../Tools";
import Escape from "../Escape";
import RateLimiter from "../RateLimiter";

export default class VSQAPI {
  private static baseURL: string;

  public static setBaseURL(url: string): void {
    VSQAPI.baseURL = url;
  }

  private static call(method: string, parameters: any): any {
    return new Promise((resolve, reject) => {
      let req = jQuery.ajax({
        url: VSQAPI.baseURL,
        method: method,
        data: {
          parameters: parameters
        },
        cache: false
      });

      req.done((data: any) => resolve(data));
      req.fail((err: any) => reject(err));
    });
  }

  public static GET(module: string, method: string, args?: any): any {
    let parameters = jQuery.extend({
      layer: "controller",
      module: module,
      method: method
    }, args || {});

    return VSQAPI.call("GET", parameters);
  }
}
