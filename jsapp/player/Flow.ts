/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";

/* definialni hogy kell a vsq flowplayer confignak kineznie */
interface VSQSource {
  readonly type: string;
  readonly src: string;
}
interface VSQConfig {
  secondarySources?: VSQSource[];
}

/** A flowplayer plugin implementacioert felel (dual-stream, reconnect stb) */
export default class Flow {
  public static engineName = "vsq";
  private static initDone = false;

  private id: string;
  private engines: Element[];
  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;

  constructor(player: Flowplayer, root: Element) {
    Flow.log(arguments);
    this.player = player;
    this.root = jQuery(root);
    this.id = this.root.attr('data-flowplayer-instance-id');
  }

  private static log(...params: Object[]): void {
    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  private log(...params: Object[]): void {
    Flow.log(params);
  }

  private multiCall(funcName: string, args?: any): any {
    let ret: any = [];
    for (var i = this.engines.length - 1; i >= 0; i--) {
      let engine = this.engines[i];
      ret[i] = engine[funcName].apply(engine, args);
    }

    return ret;
  }

  private multiSet(property: string, value: any): void {
    for (var i = this.engines.length - 1; i >= 0; i--) {
      let engine = this.engines[i];
      engine[property] = value;
    }
  }

  public static canPlay(type: string, conf: Object): boolean {
    Flow.log(arguments);
    return true; // TODO
  }

  public load(video?: Element): void {
  }

  public pause(): void {
    this.multiCall('pause');
  }

  public resume(): void {
    this.multiCall('play');
  }

  public speed(speed: Number): void {
    this.multiSet('playbackRate', speed);
  }

  public volume(volume: Number): void {
    this.multiSet('volume', volume);
  }

  public unload(): void {
    // TODO
  }

  public seek(to: Number): void {
    try {
      let pausedState = this.player.paused;
      this.multiSet('currentTime', to);

      if (pausedState)
        this.pause();

   } catch (ignored) {}
  }

  public pick(sources: FlowSource[]): FlowSource {
    return sources[0];
  }

  public static setup(): void {
    if (Flow.initDone)
      return;

    let dummy = (player: Flowplayer, root: Element) => {
      return new Flow(player, root);
    };
    dummy.engineName = Flow.engineName;
    dummy.canPlay = Flow.canPlay;

    flowplayer.engines.unshift(dummy);
    Flow.initDone = true;
  }
}



