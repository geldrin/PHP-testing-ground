/// <reference path="../jquery/jquery.d.ts" />
"use strict";

export default class Config {
  private flashConfig: Object;
  private config;
  constructor(data: Object) {
    this.flashConfig = data['flashplayer']['config'];
    delete(data['flashplayer']);
    this.config = data;
  }

  public getFlashConfig(): Object {
    return this.flashConfig;
  }

  public get(key: string, def?: string): Object {
    if (this.config[key])
      return this.config[key];

    return def;
  }
}
