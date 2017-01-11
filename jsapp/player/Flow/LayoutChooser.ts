/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {Flow, VSQConfig} from "../Flow";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;
interface LayoutChooserRange {
  from: number;
  to: number;
  type: string;
}
interface LayoutChooserInfo {
  percent: number;
  type: string;
}

export default class LayoutChooser extends BasePlugin {
  private ranges: LayoutChooserRange[];
  private readonly PIPCONTENT  = 0;
  private readonly MASTERONLY  = 1;
  private readonly SPLIT       = 2;
  private readonly CONTENTONLY = 3;
  private readonly PIPMASTER   = 4;

  public init(): void {
    // nincs masik video, csak a full 100% szamit
    if (this.cfg.secondarySources.length === 0) {
      this.root.addClass('vsq-singlevideo');
      return;
    }

    if (this.root.find('.vsq-layoutchooser').length > 0)
      return;

    // a maximalis magassagot mindig allitani kell ha valtozik a szelesseg
    this.fixHeight();
    this.player.on("fullscreen fullscreen-exit", () => { this.fixHeight() });

    this.setupRatios();
    this.setupHTML();

    // az init utani elso trigger, mert a default ertek meg nem lett hasznalva
    this.trigger();
  }

  public destroy(): void {
    this.root.find(".vsq-layoutchooser").remove();
  }

  protected configKey(key: string): string {
    return 'vsq-player-layout-' + key;
  }

  private trigger(newVal?: string): void {
    let ratio = this.root.find('.vsq-layoutchooser input[name="ratio"]');
    if (newVal != null)
      ratio.val(newVal);

    ratio.change();
  }

  private fixHeight(): void {
    let maxHeight: number;
    if (this.root.hasClass('is-fullscreen'))
      maxHeight = jQuery(window).height();
    else
      maxHeight = this.root.height();

    jQuery(this.videoTags).css("maxHeight", maxHeight + 'px');
  }

  private setupRatios() {
    let maxRatio = 300;
    // 3% az ~10 step, ketto lesz belole, ugy kell vele szamolni
    let singleRatio = Math.floor(maxRatio * 0.034);
    // ket pip beallitas van ergo 50% lesz a vegen ~70 step per pip
    let pipRatio = Math.floor((maxRatio - singleRatio * 2) * 0.25);
    // a maradek az ~40 step kornyeke, jonak tunik
    let splitRatio = Math.floor(maxRatio - singleRatio * 2 - pipRatio * 2);

    this.ranges = [
      {
        'from': 0,
        'to': pipRatio,
        'type': 'pipContent'
      },
      {
        'from': pipRatio,
        'to': pipRatio + singleRatio,
        'type': 'masterOnly'
      },
      {
        'from': pipRatio + singleRatio,
        'to': pipRatio + singleRatio + splitRatio,
        'type': 'split'
      },
      {
        'from': pipRatio + singleRatio + splitRatio,
        'to': pipRatio + singleRatio + splitRatio + singleRatio,
        'type': 'contentOnly'
      },
      {
        'from': pipRatio + singleRatio + splitRatio + singleRatio,
        'to': pipRatio + singleRatio + splitRatio + singleRatio + pipRatio,
        'type': 'pipMaster'
      }
    ];
  }

  private getDefaultRatio(): number {
    return Math.floor(this.ranges[this.ranges.length - 1].to / 2);
  }

  private getMaxRatio(): number {
    return this.ranges[this.ranges.length - 1].to - 1;
  }

  private getMiddleRange(ix: number): string {
    let prevTo = 0;
    if (ix !== 0)
      prevTo = this.ranges[ix - 1].to;

    let range = this.ranges[ix];
    return '' + (prevTo + Math.floor((range.to - range.from) / 2));
  }

  private setupHTML(): void {
    // szamra "castolva" hogy ne kelljen html escapelni
    let ratio = 0 + Tools.getFromStorage(
      this.configKey("layoutRatio"),
      this.getDefaultRatio()
    );
    let max = this.getMaxRatio();

    let html = `
      <div class="vsq-layoutchooser">
        <input name="ratio" type="range" min="0" max="${max}" step="1" value="${ratio}"/>
        <ul>
          <li class="pip-content">PiP content</li>
          <li class="master-only">Master only</li>
          <li class="split">Split</li>
          <li class="content-only">Content only</li>
          <li class="pip-master">PiP master</li>
        </ul>
      </div>
    `;
    this.root.find(".fp-ui").append(html);

    this.root.on("click", ".vsq-layoutchooser .pip-content", (e: Event): void => {
      e.preventDefault();
      this.trigger(this.getMiddleRange(this.PIPCONTENT));
    });
    this.root.on("click", ".vsq-layoutchooser .master-only", (e: Event): void => {
      e.preventDefault();
      this.trigger('' + this.ranges[this.MASTERONLY].from);
    });
    this.root.on("click", ".vsq-layoutchooser .split", (e: Event): void => {
      e.preventDefault();
      this.trigger(this.getMiddleRange(this.SPLIT));
    });
    this.root.on("click", ".vsq-layoutchooser .content-only", (e: Event): void => {
      e.preventDefault();
      this.trigger('' + this.ranges[this.CONTENTONLY].from);
    });
    this.root.on("click", ".vsq-layoutchooser .pip-master", (e: Event): void => {
      e.preventDefault();
      this.trigger(this.getMiddleRange(this.PIPMASTER));
    });

    this.root.on("input change", '.vsq-layoutchooser input[name="ratio"]', (e: Event): void => {
      this.onChange(e);
    });
  }

  private getRangeForValue(val: number): LayoutChooserInfo {
    for (var i = this.ranges.length - 1; i >= 0; i--) {
      let range = this.ranges[i];
      if (val < range.from || val >= range.to)
        continue;

      let normalVal = val - range.to;
      let magnitude = range.from - range.to;
      return {
        'percent': normalVal / magnitude,
        'type': range.type
      };
    }

    throw new Error("Impossible");
  }

  private onChange(e: Event): void {
    let elem = jQuery(e.currentTarget);
    let val = parseInt(elem.val(), 10);
    let masterWidth: number = 50;
    let contentWidth: number = 50;
    let masterOnTop: null | boolean = true;

    // elmentjuk a beallitott erteket hogy refreshnel ugyanaz legyen
    Tools.setToStorage(this.configKey("layoutRatio"), val);

    if (val < 0 || val > this.getMaxRatio())
      throw new Error("Invalid value for layoutchooser");

    let info = this.getRangeForValue(val);
    // pip es split modban a minimalis nagysag 25%, annal sose legyunk kisebbek
    switch(info.type) {
      case "pipContent":
        masterWidth = 100;
        contentWidth = info.percent * 25;
        masterOnTop = false;
        break;
      case "masterOnly":
        masterWidth = 100;
        contentWidth = 0;
        masterOnTop = true;
        break;
      case "split":
        masterWidth = info.percent * 100;
        contentWidth = 100 - masterWidth;
        masterOnTop = null;
        break;
      case "contentOnly":
        masterWidth = 0;
        contentWidth = 100;
        masterOnTop = false;
        break;
      case "pipMaster":
        masterWidth = 100 - (info.percent * 25);
        contentWidth = 100;
        masterOnTop = true;
        break;
    }

    let masterLeft: 0 | "auto" = 0;
    let masterRight: 0 | "auto" = "auto";
    if (!this.cfg.contentOnRight) {
      masterLeft = "auto";
      masterRight = 0;
    }

    let masterZ = 10;
    let contentZ = 9;
    if (masterOnTop === false) {
      masterLeft = "auto";
      masterRight = 0;

      masterZ = 9;
      contentZ = 10;
    }
    if ( masterOnTop === true) {
      masterLeft = 0;
      masterRight = "auto";
      // a default z-index ertekek jok nekunk
    }

    let master = jQuery(this.videoTags[Flow.MASTER]);
    let content = jQuery(this.videoTags[Flow.CONTENT]);
    master.css({
      width: masterWidth + '%',
      zIndex: masterZ,
      left: masterLeft,
      right: masterRight
    });
    content.css({
      width: contentWidth + '%',
      zIndex: contentZ
    });
  }

}
