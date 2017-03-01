/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Timeline extends BasePlugin {
  protected pluginName = "Timeline";
  private watched: number;
  private dragging = false;
  private offset: JQueryCoordinates;
  private size: number;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.cfg.position.seek)
      throw new Error("Timeline enabled yet disabling requested");

    // engedjuk rogton addig beletekerni ameddig megnezte
    this.watched = this.cfg.position.lastposition || 0;
    this.markProgress(this.watched);
  }

  private markProgress(watched: number): void {
    // TODO ui-ban fp-timeline ala beszurni es hasonloan az fp-bufferhez?
  }

  private getWatchedPercent(): number {
    let maxDur = this.vsq.getVideoTags()[VSQType.MASTER].duration;
    return this.watched / maxDur;
  }

  private handleEvent(e: any): void {
    let pageX = e.pageX || e.clientX;
    if (!pageX && e.originalEvent && e.originalEvent.touches && e.originalEvent.touches.length) {
      pageX = e.originalEvent.touches[0].pageX;
    }
    let delta = pageX - this.offset.left;
    delta = Math.max(0, Math.min(this.size, delta));

    let percentage = delta / this.size;
    console.log(percentage);
    if (percentage > this.getWatchedPercent()) {
      e.preventDefault();
      e.stopImmediatePropagation();
      // TODO nem cancelelodik a seek
    }
  }

  private handleAfterAnimation() {
    let locked = false;
    return (e: Event) => {
      this.handleEvent(e);
      locked = true;

      setTimeout(() => {
        locked = false;
      }, 100);
    };
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not handling timeline");
      return;
    }

    // TODO Modal.askQuestion akarja e fojtatni
    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.watched < time)
        this.watched = time;
    });

    let timeline = this.flowroot.find('.fp-timeline');
    let handle = this.handleAfterAnimation();
    this.flowroot.on("mousedown.vsq-tl touchstart.vsq-tl", ".fp-timeline", (e) => {
      this.dragging = true;

      // fullscreen miatt mindig ujra szamolni
      this.offset = timeline.offset();
      this.size = timeline.width();
      handle(e);

      jQuery(document).on("mousemove.vsq-tl touchmove.vsq-tl", (e) => {
        handle(e);
      });

      // TODO css cursor?
    });
    jQuery(document).on("mouseup.vsq-tl touchend.vsq-tl", (e) => {
      this.dragging = false;
      jQuery(document).off("mousemove.vsq-tl touchmove.vsq-tl");
    });
  }

  public destroy(): void {
    this.flowroot.off(".vsq-tl");
  }
}
