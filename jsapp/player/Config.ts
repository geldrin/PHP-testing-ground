/// <reference path="../jquery/jquery.d.ts" />
"use strict";

export default class Config {
  private flashConfig: Object;

  constructor(data: Object) {
    this.flashConfig = data['flashplayer']['config'];
  }
}
