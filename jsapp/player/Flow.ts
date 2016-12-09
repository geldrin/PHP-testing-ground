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
  private api: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;

  constructor() {
  }

  private log(...params: Object[]): void {
    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  public init(): void {
    flowplayer((api: Flowplayer, root: Element): void => {
      this.api = api;
      this.root = $(root);

      let conf = api.conf || {};
      if (conf['vsq'] == null)
        return;

      this.cfg = conf['vsq'] as VSQConfig;

      if (this.cfg['secondarySources'])
        this.setupSources();
    });
  }

  private setupSources(): void {
  }
}
