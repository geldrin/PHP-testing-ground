/// <reference path="../jquery/jquery.d.ts" />
"use strict";

export default class Config {
  private flashConfig: Object;
  private config: Object;

  constructor(data: Object) {
    this.flashConfig = data['flashplayer']['config'];
    this.config = data;
  }

  public getFlashConfig(): Object {
    return this.flashConfig;
  }

  private getFromKey(config: Object, keys: string[]): Object {
    let key = keys.shift();
    let ret = config[key];
    if (ret && keys.length > 0)
      return this.getFromKey(ret, keys);

    return ret;
  }

  public get(key: string, def?: Object): Object {
    let keys = key.split('.');
    let ret = this.getFromKey(this.config, keys);
    if (typeof ret !== "undefined")
      return ret;

    return def;
  }
}
