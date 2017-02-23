/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQConfig} from "./VSQ";
import Tools from "../Tools";
import Escape from "../Escape";
import RateLimiter from "../RateLimiter";

export default class VSQAPI {
  private static baseURL: string;

  public static init(cfg: VSQConfig): void {
    VSQAPI.baseURL = cfg.apiurl;
  }

  private static call(method: string, parameters: Object): Promise<Object> {
    return new Promise((resolve, reject) => {
      let req = jQuery.ajax({
        url: VSQAPI.baseURL,
        type: method,
        data: {
          parameters: JSON.stringify(parameters)
        },
        dataType: 'json',
        cache: false
      });

      req.done((data: any) => resolve(data));
      req.fail((err: any) => reject(err));
    });
  }

  private static prepareParams(module: string, method: string, args?: Object): Object {
    let parameters = jQuery.extend({
      layer: "controller",
      module: module,
      method: method,
      format: 'json'
    }, args || {});

    return parameters;
  }

  public static GET(module: string, method: string, args?: Object): any {
    let parameters = this.prepareParams(module, method, args);
    return VSQAPI.call("GET", parameters);
  }

  public static POST(module: string, method: string, args?: Object): any {
    let parameters = this.prepareParams(module, method, args);
    return VSQAPI.call("POST", parameters);
  }
}
