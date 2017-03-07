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

  private static randomTimeout(retry: number): number {
    let ret = Math.pow(2, retry) * 1000;
    ret += Math.random() * 500;
    return Math.round(ret);
  }

  private static call(method: string, parameters: Object, failFast?: boolean): Promise<Object> {
    let retries = 0;
    return new Promise((resolve, reject) => {
      jQuery.ajax({
        url: VSQAPI.baseURL,
        type: method,
        data: {
          parameters: JSON.stringify(parameters)
        },
        dataType: 'json',
        cache: false,
        success: function(data: any) {
          resolve(data);
        },
        error: function(err: JQueryXHR) {
          // auto-retry de csak akkor ha network error
          if (failFast || err.readyState != 0 || retries >= 3) {
            reject(err);
            return;
          }

          // a jquery ajax request this-je!
          // azert mert igy lehet a legkonyebben ujraprobalni a requestet
          let self = this;
          let sleepFor = VSQAPI.randomTimeout(retries);
          setTimeout(function() {
            retries++;

            // retry
            jQuery.ajax(self);
          }, sleepFor);
        },
        timeout: 10000 // 10sec timeout
      });
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

  public static GET(module: string, method: string, args?: Object, failFast?: boolean): any {
    let parameters = this.prepareParams(module, method, args);
    return VSQAPI.call("GET", parameters, failFast);
  }

  public static POST(module: string, method: string, args?: Object, failFast?: boolean): any {
    let parameters = this.prepareParams(module, method, args);
    return VSQAPI.call("POST", parameters, failFast);
  }
}
