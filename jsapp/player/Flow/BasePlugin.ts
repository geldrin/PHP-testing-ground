/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {Flow, VSQConfig} from "../Flow";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;

export abstract class BasePlugin {
  protected pluginName: string;
  protected flow: Flow;
  protected root: JQuery;
  protected cfg: VSQConfig;
  protected player: Flowplayer;

  constructor(flow: Flow) {
    this.flow = flow;
    this.root = flow.getRoot();
    this.cfg = flow.getConfig();
    this.player = flow.getPlayer();
  }

  protected log(...params: Object[]): void {
    if (!Flow.debug)
      return;

    params.unshift(`[${this.pluginName}]`);
    console.log.apply(console, params);
  }

  // non-abstract mert nem kell hogy mindenki implementalja
  protected configKey(key: string): string {
    throw new Error("Override configKey");
  }

  protected eventName(event?: string): string {
    let postfix = '.' + Flow.engineName;
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
