/// <reference path="../defs/jquery/jquery.d.ts" />
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

  /** non-null false-y erteket vissza nincs kulcs */
  private getFromKey(config: Object, keys: string[]): Object {
    let key = keys.shift();
    if (key == null)
      return "";

    let ret = config[key];
    if (ret) {
      if (keys.length > 0)
        return this.getFromKey(ret, keys);

      return ret;
    }

    return "";
  }

  /** non-null false-y erteket vissza nincs kulcs */
  public get(key: string, def?: Object): Object {
    let keys = key.split('.');
    let ret = this.getFromKey(this.config, keys);
    if (ret != null)
      return ret;

    if (def)
      return def;

    return "";
  }
}
