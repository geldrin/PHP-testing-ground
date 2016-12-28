/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {Flow, VSQConfig} from "../Flow";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;

export abstract class BasePlugin {
  protected flow: Flow;
  protected root: JQuery;
  protected cfg: VSQConfig;
  protected player: Flowplayer;
  protected videoTags: Element[];

  constructor(flow: Flow) {
    this.flow = flow;
    this.root = flow.getRoot();
    this.cfg = flow.getConfig();
    this.player = flow.getPlayer();
    this.videoTags = flow.getVideoTags();
  }

  // non-abstract mert nem kell hogy mindenki implementalja
  protected configKey(key: string): string {
    throw new Error("Override configKey");
  }

  public abstract init(): void;
  public abstract destroy(): void;
}
