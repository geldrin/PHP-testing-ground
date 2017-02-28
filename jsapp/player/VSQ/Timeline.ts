/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Timeline extends BasePlugin {
  protected pluginName = "Timeline";

  constructor(vsq: VSQ) {
    super(vsq);
  }

  public load(): void {
    this.flowroot.on("click.vsq-tl", ".fp-timeline", (e) => {
      let x = e.pageX || e.clientX;
      console.log(x);
      // e.stopPropagation()
      //let delta = x - common.offset(timeline).left
      //let percentage = delta / common.width(timeline)
      //let seconds = percentage * api.video.duration;
      // TODO disable, css cursor, eat events, keep max viewed duration, param?
    });
  }

  public destroy(): void {
    this.flowroot.off(".vsq-tl");
  }
}
