/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig} from "../VSQ";
import Locale from "../../Locale";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;

export abstract class BasePlugin {
  protected pluginName: string;
  protected vsq: VSQ;
  protected root: JQuery;
  protected cfg: VSQConfig;
  protected flow: Flowplayer;
  protected l: Locale;

  constructor(vsq: VSQ) {
    this.vsq = vsq;
    this.root = vsq.getRoot();
    this.cfg = vsq.getConfig();
    this.l = this.cfg.locale;
    this.flow = vsq.getPlayer();
  }

  protected log(...params: Object[]): void {
    if (!VSQ.debug)
      return;

    params.unshift(`[${this.pluginName}]`);
    console.log.apply(console, params);
  }

  // non-abstract mert nem kell hogy mindenki implementalja
  protected configKey(key: string): string {
    throw new Error("Override configKey");
  }

  protected eventName(event?: string): string {
    let postfix = '.' + VSQ.engineName;
    if (!event)
      return postfix;

    return event + postfix;
  }

  public setupHLS(hls: any, type: number): void {
    // direkt nem csinal semmit
  }

  // akarhanyszor meghivodhat!
  public abstract load(): void;
  public abstract destroy(): void;
}
