/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";
import RateLimiter from "../../RateLimiter";

export default class Timeline extends BasePlugin {
  protected pluginName = "Timeline";
  private watched: number;
  private offset: JQueryCoordinates;
  private size: number;
  private slider: FlowTimeline;
  private progress: JQuery;
  private limiter: RateLimiter;
  private tooltip: JQuery;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.cfg.position.seek)
      throw new Error("Timeline enabled yet disabling requested");

    this.flowroot.addClass("vsq-limitedseek");
    let html = `
      <div class="vsq-progress"></div>
    `;
    this.progress = jQuery(html);
    this.flowroot.find('.fp-timeline .fp-buffer').after(this.progress);
    this.tooltip = this.flowroot.find(".fp-timeline-tooltip");

    // engedjuk rogton addig beletekerni ameddig megnezte
    this.watched = this.cfg.position.lastposition || 0;
    this.markProgress();

    this.slider = this.flow.sliders.timeline;
    this.flow.on("ready", () => {
      // manualisan lekezeljuk hogy hova mehet a timeline
      this.slider.disable(true);
      this.slider.disableAnimation(true);
    });

    this.limiter = new RateLimiter();
    this.limiter.add("handle", (e: Event) => {
      this.handleEvent(e);
    }, 100, true);
  }

  private markProgress(): void {
    let percentage = this.getWatchedPercent() * 100;
    this.progress.css('width', percentage + '%');
  }

  private getWatchedPercent(): number {
    let maxDur = this.flow.video.duration;
    return this.watched / maxDur;
  }

  private getDeltaFromEvent(e: any, offset?: JQueryCoordinates): number {
    let pageX = e.pageX || e.clientX;
    if (!pageX && e.originalEvent && e.originalEvent.touches && e.originalEvent.touches.length) {
      pageX = e.originalEvent.touches[0].pageX;
    }

    if (offset == null)
      offset = this.offset;

    let delta = pageX - offset.left;
    return delta;
  }

  private getPercentageFromEvent(e: any, offset?: JQueryCoordinates, size?: number): number {
    if (size == null)
      size = this.size;

    let delta = this.getDeltaFromEvent(e, offset);
    delta = Math.max(0, Math.min(size, delta));

    let percentage = delta / size;
    return percentage;
  }

  private handleEvent(e: any): void {
    let percentage = this.getPercentageFromEvent(e);

    if (percentage <= this.getWatchedPercent()) {
      this.log("seeking to: ", percentage);
      this.slider.disable(false);
      this.slider.slide(percentage, 1, true);
      this.slider.disable(true);
    } else {
      this.log("out of bounds: ", percentage);
    }
  }

  private async handleResume(video: FlowVideo) {
    if (this.watched < 10)
      return;

    let from = Tools.formatDuration(this.watched);
    let question = this.l.get("player_shouldresume");
    question = question.replace(/%from%/gi, from);

    let shouldResume = await Modal.askQuestion(
      question,
      this.l.get('yes'),
      this.l.get('no'),
      Modal.QUESTION_TRUE_FIRST
    );

    if (shouldResume) {
      // itt nem kell seekelnunk mert a default hogy onnan probaljuk
      // kezdeni ahol abbahagyta a user
      // de a biztonsag kedveert leelenorizzuk ezt
      if (video.time != this.watched) {
        this.log("Have to seek! video time was", video.time, this.watched);
        this.vsq.seek(from);
      }
      this.vsq.resume();
    } else {
      this.vsq.seek(0);
      this.vsq.resume();
    }
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not handling timeline");
      return;
    }

    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.watched > time)
        return;

      this.watched = time;
      this.markProgress();
    });

    let timeline = this.flowroot.find('.fp-timeline');
    this.flowroot.on("mousedown.vsq-tl touchstart.vsq-tl", ".fp-timeline", (e) => {
      // fullscreen miatt mindig ujra szamolni
      this.offset = timeline.offset();
      this.size = timeline.width();
      this.limiter.trigger("handle", e);

      jQuery(document).on("mousemove.vsq-tl touchmove.vsq-tl", (e) => {
        this.limiter.trigger("handle", e);
      });
    });

    jQuery(document).on("mouseup.vsq-tl touchend.vsq-tl", (e) => {
      jQuery(document).off("mousemove.vsq-tl touchmove.vsq-tl");
    });

    this.flowroot.on("mousemove.vsq-tl-tlt", ".fp-timeline", (e) => {
      let offset = timeline.offset();
      let size = timeline.width();

      let percentage = this.getPercentageFromEvent(e, offset, size);
      if (percentage == 0)
        return;

      let str: string;
      if (percentage <= this.getWatchedPercent()) {
        let seconds = percentage * this.flow.video.duration;
        str = Tools.formatDuration(seconds);
      } else
        str = this.l.get("player_seekbardisabled");

      this.tooltip.text(str);
      let width = this.tooltip.outerWidth(true);

      let left = this.getDeltaFromEvent(e, offset) - width / 2;
      if (left < 0)
        left = 0;

      if (left > size - width)
        this.tooltip.css({
          left: "auto",
          right: "0px"
        });
      else
        this.tooltip.css({
          left: left + "px",
          right: "auto"
        });
    });

    this.flow.on("ready.vsq-tl", (e: Event, api: Flowplayer, video: FlowVideo) => {
      this.handleResume(video);
    });
  }

  public destroy(): void {
    this.flowroot.off(".vsq-tl .vsq-tl-tlt");
    this.flow.off(".vsq-tl");
    this.slider.disable(false);
  }
}
