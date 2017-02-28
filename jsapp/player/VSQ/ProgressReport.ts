/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class ProgressReport extends BasePlugin {
  protected pluginName = "ProgressReport";

  constructor(vsq: VSQ) {
    super(vsq);
  }

  public load(): void {
    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      // TODO ratelimit, sequential reports
    });
    this.flow.on("seek.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      // TODO
    });
  }

  public destroy(): void {
    // TODO?
    this.flow.off(".vsq-pgr");
  }
}
