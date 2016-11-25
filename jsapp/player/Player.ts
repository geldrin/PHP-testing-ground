/// <reference path="../jquery/jquery.d.ts" />
"use strict";
import Config from "./Config";
import Locale from "../Locale";

export default class Player {
  private cfg: Config;
  private l: Locale;

  constructor(cfg: Config, l: Locale) {
    if (!cfg)
      throw "Invalid config passed";
    if (!l)
      throw "Invalid locale passed";

    this.cfg = cfg;
    this.l = l;
  }
}
