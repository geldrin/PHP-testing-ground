/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
//import "es6-promise"; // majd ha kell
import Tools from "../Tools";
import Escape from "../Escape";

declare var Hls: any;

/**
 * A flowplayer plugin implementacioert felel (dual-stream, reconnect stb)
 * Typescript rewrite of:
 * https://github.com/flowplayer/flowplayer-hlsjs/tree/06687f55ea4ad83a83515a9d9daf591def4377df
 */
export default class Flow {
  public static engineName = "vsq";
  private static initDone = false;
  private static readonly MASTER = 0;
  private static readonly CONTENT = 1;

  private id: string;
  private loadedCount = 0;
  private longerType: 0 | 1 = 0; // alapbol Flow.MASTER
  private videoTags: HTMLVideoElement[] = [];
  private videoInfo: FlowVideo[] = [];
  private hlsEngines: any[] = [];
  private hlsConf: any;

  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;
  private volumeLevel: number;
  private eventsInitialized = false;
  private timer: number;
  // a kivalasztott quality label, default 'auto';
  private selectedQuality: string;

  private activeQualityClass = "active";
  private mse = window.MediaSource || window.WebKitMediaSource;
  private maxLevel: number = 0; // hls specific
  private recoverMediaErrorDate: number;
  private swapAudioCodecDate: number;

  constructor(player: Flowplayer, root: Element) {
    Flow.log("constructor", arguments);
    this.player = player;
    this.cfg = player.conf.vsq as VSQConfig || {};
    this.hlsConf = jQuery.extend({
        bufferWhilePaused: true,
        smoothSwitching: true,
        recoverMediaError: true
      },
      flowplayer.conf['hlsjs'],
      this.player.conf['hlsjs'],
      this.player.conf['clip']['hlsjs'],
    );

    this.root = jQuery(root);
    this.selectedQuality = Tools.getFromStorage(this.configKey("quality"), "auto");
    this.id = this.root.attr('data-flowplayer-instance-id');
  }

  private getQualityIndex(quality: string): number {
    // az alap otlet hogy a playernek a konfiguracioban atadott sorrend
    // korrelal a quality verziok sorrendjevel, igy kozvetlenul beallithato
    // ez az index a hls-nek
    for (var i = this.cfg.labels.master.length - 1; i >= 0; i--) {
      let label = this.cfg.labels.master[i];
      if (label === quality)
        return i;
    }

    // default auto, -1 for automatic level selection
    return -1;
  }

  private configKey(key: string): string {
    return 'vsq-player-' + key;
  }

  private static log(...params: Object[]): void {
    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  private log(...params: Object[]): void {
    Flow.log(...params);
  }

  private callOnArray(data: any[], funcName: string, args?: any): any {
    let ret: any = [];
    for (let i = data.length - 1; i >= 0; i--) {
      let elem = data[i];
      if (elem == null)
        continue;

      ret[i] = elem[funcName].apply(elem, args);
    }

    return ret;
  }

  private setOnArray(data: any[], property: string, value: any): void {
    let ret: any = [];
    for (let i = data.length - 1; i >= 0; i--) {
      let elem = data[i];
      if (elem == null)
        continue;

      elem[property] = value;
    }
  }

  private hlsCall(funcName: string, args?: any): any {
    return this.callOnArray(this.hlsEngines, funcName, args);
  }

  private hlsSet(property: string, value: any): void {
    this.setOnArray(this.hlsEngines, property, value);
  }

  private tagCall(funcName: string, args?: any): any {
    return this.callOnArray(this.videoTags, funcName, args);
  }

  private tagSet(property: string, value: any): void {
    this.setOnArray(this.videoTags, property, value);
  }

  private getType(type: string): string {
    if (Flow.isHLSType(type))
      return "application/x-mpegurl";

    return type;
  }

  private static isHLSType(type: string): boolean {
    return type.toLowerCase().indexOf("mpegurl") > -1;
  }
  private static HLSQualitiesSupport(conf: any): boolean {
    let hlsQualities = (conf.clip && conf.clip.hlsQualities) || conf.hlsQualities;

    return flowplayer.support.inlineVideo &&
      (hlsQualities === true ||
      (hlsQualities && hlsQualities.length))
    ;
  }

  public static canPlay(type: string, conf: Object): boolean {
    let b = flowplayer.support.browser;
    let wn = window.navigator;
    let isIE11 = wn.userAgent.indexOf("Trident/7") > -1;

    // engine disabled for player or clip
    if (
         conf['vsq'] === false || conf.clip['vsq'] === false ||
         conf['hlsjs'] === false || conf.clip['hlsjs'] === false
       )
      return false;

    if (Flow.isHLSType(type)) {
      // https://bugzilla.mozilla.org/show_bug.cgi?id=1244294
      if (
          conf.hlsjs &&
          conf.hlsjs.anamorphic &&
          wn.platform.indexOf("Win") === 0 &&
          b.mozilla && b.version.indexOf("44.") === 0
         )
        return false;

      // https://github.com/dailymotion/hls.js/issues/9
      return isIE11 || !b.safari;
    }

    return false;
  }

  private addPoster(): void {
    let master = jQuery(this.videoTags[Flow.MASTER]);
    master.one(this.eventName("timeupdate"), () => {
      this.root.addClass("is-poster");
      this.player.poster = true;
    });
  }

  private removePoster(): void {
    if (!this.player.poster)
      return;

    let master = jQuery(this.videoTags[Flow.MASTER]);
    master.one(this.eventName("timeupdate"), () => {
      this.root.removeClass("is-poster");
      this.player.poster = false;
    });
  }

  private syncVideos(): void {
    if (this.cfg.secondarySources.length === 0)
      return;

    let master = this.videoTags[Flow.MASTER];
    let content = this.videoTags[Flow.CONTENT];

    // ha az egyik felvetelt mar befejeztuk
    if (master.currentTime == 0 || master.currentTime >= master.duration)
      return;
    if (content.currentTime == 0 || content.currentTime >= content.duration)
      return;

    // ha az elteres a ketto kozott tobb mint X masodperc
    // akkor mindig a master felvetelhez igazodunk
    if (Math.abs(master.currentTime - content.currentTime) > 0.5) {
      this.log("syncing videos to master");
      content.currentTime = master.currentTime;
    }
  }

  private handleLoadedData(e: Event): boolean | undefined {
    this.loadedCount++;
    // master mindig van, content nem biztos
    let vidCount = 1 + this.cfg.secondarySources.length;

    if (this.loadedCount != vidCount) {
      e.stopImmediatePropagation();
      return false;
    }

    // mivel a default longerType ertek a Flow.MASTER igy csak egy esetet kell nezni
    if (
        vidCount > 1 &&
        this.videoTags[Flow.CONTENT].duration > this.videoTags[Flow.MASTER].duration
       )
      this.longerType = Flow.CONTENT;

    let tag = this.videoTags[this.longerType];
    let data = jQuery.extend(this.player.video, {
      duration: this.cfg.duration,
      seekable: tag.seekable.end(0),
      width: tag.videoWidth, // TODO ezeket mire hasznalja a flowplayer
      height: tag.videoHeight,
      // az src mindig ugyanaz lesz, hiaba master vagy content
      url: this.videoInfo[Flow.MASTER].src
    });

    this.triggerPlayer("ready", data);
    // TODO beallitani a megfelelo quality selectorban a valtozatot active-nak
    return false;
  }

  private handlePlay(e: Event): boolean | undefined {
    let tag = e.currentTarget as HTMLVideoElement;
    // ismeretlen bug, de kezeljuk le
    // hack to prevent Chrome engine from hanging
    if (tag.currentTime >= tag.duration)
      tag.currentTime = 0;

    let type = this.getTypeFromEvent(e);
    // a lenyeg csak az hogy egyszer fusson le
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused)
      this.hlsCall('startLoad', [tag.currentTime]);

    this.triggerPlayer("resume", undefined);
  }

  private handlePause(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused)
      this.hlsCall('stopLoad');
    this.triggerPlayer("pause", undefined);
  }

  private handleEnded(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type !== this.longerType) {
      e.stopImmediatePropagation();
      return false;
    }

    let video = this.player.video;
    let tag = this.videoTags[this.longerType];

    this.hlsCall('trigger', [
      Hls.Events.BUFFER_FLUSHING,
      {
        startOffset: 0,
        endOffset: this.cfg.duration
      }
    ]);

    //this.hlsCall('startLoad', [0]);
    this.tagCall('pause');
    this.triggerPlayer("finish", undefined);
  }

  private handleProgress(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type !== this.longerType) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = this.videoTags[this.longerType];
    let buffer: number = 0;
    try {
      let buffered = tag.buffered;
      buffer = buffered.end(0);
      if (tag.currentTime) {
        for (var i = buffered.length - 1; i >= 0; i--) {
          let buffend = buffered.end(i);
          if (buffend >= tag.currentTime)
            buffer = buffend;
        }
      }
    } catch(_) {};

    this.player.video.buffer = buffer;
    this.triggerPlayer("buffer", buffer);
  }

  private handleRateChange(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.triggerPlayer("speed", tag.playbackRate);
  }

  private handleSeeked(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused && tag.paused) {
      this.hlsCall('stopLoad');
      this.tagCall('pause');
    }

    this.triggerPlayer("seek", tag.currentTime);
    return false;
  }

  private handleTimeUpdate(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type !== this.longerType) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = this.videoTags[this.longerType];
    this.triggerPlayer("progress", tag.currentTime);

    this.syncVideos();
  }

  private handleVolumeChange(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.triggerPlayer("volume", tag.volume);
  }

  private handleError(e: Event): boolean | undefined {
    e.stopImmediatePropagation();
    const MEDIA_ERR_NETWORK = 2;
    const MEDIA_ERR_DECODE = 3;

    let type = this.getTypeFromEvent(e);
    let err = this.videoTags[type].error.code;

    // egyatalan olyan hiba amivel tudunk kezdeni valamit?
    if (
         (this.hlsConf.recoverMediaError && err === MEDIA_ERR_DECODE) ||
         (this.hlsConf.recoverNetworkError && err === MEDIA_ERR_NETWORK) ||
         (this.hlsConf.recover && (err === MEDIA_ERR_NETWORK || err === MEDIA_ERR_DECODE))
       ) {
      this.root.removeClass('is-paused');
      this.root.addClass('is-seeking');

      let hls = this.hlsEngines[type];
      if (err === MEDIA_ERR_NETWORK) {
        hls.startLoad();
        return false;
      }

      let now = performance.now();
      if (!this.recoverMediaErrorDate || now - this.recoverMediaErrorDate > 3000) {
        this.recoverMediaErrorDate = performance.now();
        hls.recoverMediaError();
        return false;
      } else {
        if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
          this.swapAudioCodecDate = performance.now();
          hls.swapAudioCodec();
          hls.recoverMediaError();
          return false;
        } else
          err = MEDIA_ERR_DECODE;
      }
    }

    let arg: any = {code: err};
    if (err > MEDIA_ERR_NETWORK)
      arg.video = jQuery.extend(this.videoInfo[type], {url: this.videoInfo[type].src});

    this.player.trigger("error", [this.player, arg]);
  }

  private triggerPlayer(event: string, data: any): void {
    this.log("[flow event]", event, data);
    this.player.trigger(event, [this.player, data]);
  }

  private getTypeFromEvent(e: Event): number {
    let t = jQuery(e.currentTarget);
    if (!t.is('.vsq-master, .vsq-content'))
      throw new Error("Unknown event target");

    if (t.is('.vsq-master'))
      return Flow.MASTER;

    return Flow.CONTENT;
  }

  private setupVideoEvents(video: FlowVideo): void {
    if (this.eventsInitialized)
      return;

    let masterHLS = this.hlsEngines[Flow.MASTER];
    let masterTag = this.videoTags[Flow.MASTER];
    let master = jQuery(masterTag);
    let sources = jQuery(this.videoTags);

    // video event -> flowplayer event
    let events = {
      ended       : "finish",
      loadeddata  : "ready",
      pause       : "pause",
      play        : "resume",
      progress    : "buffer",
      ratechange  : "speed",
      seeked      : "seek",
      timeupdate  : "progress",
      volumechange: "volume"
    };

    let currentTime: number = masterTag.currentTime;
    let arg: any = {};

    jQuery.each(events, (videoEvent: string, flowEvent: string): void => {
      videoEvent = this.eventName(videoEvent);
      sources.on(videoEvent, (e: Event): boolean | undefined => {
        this.log("event", videoEvent, flowEvent, e);
        switch(videoEvent) {
          case "loadeddata.vsq":
            return this.handleLoadedData(e);
          case "play.vsq":
            return this.handlePlay(e);
          case "pause.vsq":
            return this.handlePause(e);
          case "ended.vsq":
            return this.handleEnded(e);
          case "progress.vsq":
            return this.handleProgress(e);
          case "ratechange.vsq":
            return this.handleRateChange(e);
          case "seeked.vsq":
            return this.handleSeeked(e);
          case "timeupdate.vsq":
            return this.handleTimeUpdate(e);
          case "volumechange.vsq":
            return this.handleVolumeChange(e);
          case "error.vsq":
            return this.handleError(e);
          default:
            throw new Error("unhandled event: " + videoEvent);
        }
      });
    });

    if (this.player.conf.poster) {
      this.player.on(this.eventName("stop"), () => {
        this.addPoster();
      });

      // ha live akkor postert vissza
      // amit varunk: az autoplay mindig false, ergo a postert kirakhatjuk
      if (this.player.live)
        master.one(this.eventName("seeked"), () => {
          this.addPoster();
        });
    }

  }

  private eventName(event?: string): string {
    let postfix = '.' + Flow.engineName;
    if (!event)
      return postfix;

    return event + postfix;
  }

  private createVideoTag(video: FlowVideo): HTMLVideoElement {

    let ret: HTMLVideoElement = document.createElement('video');
    ret.src = video.src;
    ret.className = 'fp-engine vsq-engine';

    ret.setAttribute('type', this.getType(video.type));

    if (this.cfg.autoplay) {
      ret.autoplay = true;
      ret.setAttribute('autoplay', 'autoplay');
    } else
      ret.autoplay = false;

    ret.setAttribute('x-webkit-airplay', 'allow');

    return ret;
  }

  private destroyVideoTag(index: number): void {
    let tagElem = this.videoTags[index];

    let elem = jQuery(tagElem);
    elem.find('source').removeAttr('src');
    elem.removeAttr('src');

    tagElem.load();
    elem.remove();
  }

  private setupHLS(type: number): void {
    let video = this.videoInfo[type];
    let hls = new Hls();

    hls.on(Hls.Events.MEDIA_ATTACHED, (event: string, data: any): void => {
      hls.loadSource(video.src);
    });
    hls.on(Hls.Events.MANIFEST_PARSED, (event: string, data: any): void => {
      hls.startLoad(hls.config.startPosition);

      // azt varja hogy a contentnek is ugyanazok a qualityjai lesznek,
      // nem biztos hogy igaz, TODO
      let startLevel = this.getQualityIndex(this.selectedQuality);
      hls.startLevel = startLevel;
      hls.loadLevel = startLevel;
    });

    hls.attachMedia(this.videoTags[type]);
    this.hlsEngines[type] = hls;
  }

  public load(video: FlowVideo): void {
    // mihez fogjuk prependelni a videokat
    let root = this.root.find('.fp-player');
    root.find('img').remove();

    this.hlsConf = jQuery.extend(
      this.hlsConf,
      this.player.conf.hlsjs,
      this.player.conf.clip.hlsjs,
      video.hlsjs
    );

    // eloszor a content videot, mert mindig csak prependelunk
    // es igy lesz jo a sorrend
    if (this.cfg.secondarySources.length !== 0) {
      if (this.videoTags[Flow.CONTENT])
        this.destroyVideoTag(Flow.CONTENT);

      // deep copy the video, and set its properties
      let secondVideo = jQuery.extend(true, {}, video);
      secondVideo.src = this.cfg.secondarySources[0].src;
      secondVideo.sources = this.cfg.secondarySources;
      this.videoInfo[Flow.CONTENT] = secondVideo;

      // and insert it into the DOM
      this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
      this.videoTags[Flow.CONTENT].load();
      let engine = jQuery(this.videoTags[Flow.CONTENT]);
      engine.addClass('vsq-content');
      root.prepend(engine);

      this.setupHLS(Flow.CONTENT);
    }

    if (this.videoTags[Flow.MASTER])
      this.destroyVideoTag(Flow.MASTER);

    this.videoInfo[Flow.MASTER] = video;
    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
    this.videoTags[Flow.MASTER].load();
    let engine = jQuery(this.videoTags[Flow.MASTER]);
    engine.addClass('vsq-master');
    root.prepend(engine);
    this.setupHLS(Flow.MASTER);

    this.player.on(this.eventName("error"), () => {
      this.unload();
    });

    this.setupVideoEvents(video);
    this.initQuality();
    this.initLayoutChooser();

    if (this.cfg.autoplay)
      this.tagCall("play");
  }

  public pause(): void {
    this.tagCall('pause');
  }

  public resume(): void {
    this.tagCall('play');
  }

  public speed(speed: Number): void {
    this.tagSet('playbackRate', speed);
    this.player.trigger('speed', [this.player, speed]);
  }

  public volume(volume: Number): void {
    this.tagSet('volume', volume);
  }

  public unload(): void {
    this.root.find(".vsq-quality-selector").remove();
    let videoTags = jQuery(this.videoTags);
    videoTags.remove();

    this.hlsCall('destroy');

    let listeners = this.eventName();
    this.player.off(listeners);
    this.root.off(listeners);
    videoTags.off(listeners);

    for (let i = this.hlsEngines.length - 1; i >= 0; i--)
      this.hlsEngines.pop();

    for (let i = this.videoTags.length - 1; i >= 0; i--)
      this.videoTags.pop();
  }

  public seek(to: Number): void {
    this.tagSet('currentTime', to);
  }

  public pick(sources: FlowSource[]): FlowSource | null {
    if (sources.length == 0)
      throw new Error("Zero length FlowSources passed");

    for (let i = 0; i < sources.length; ++i) {
      let source = sources[i];
      if (!Flow.isHLSType(source.type))
        continue;

      source.src = flowplayer.common.createAbsoluteUrl(source.src);
      return source;
    }

    return null;
  }

  private initQuality(): void {
    if (this.cfg.labels.master.length === 0)
      return;

    // copy quality array, assemble HTML
    let levels = this.cfg.labels.master.slice(0);
    levels.unshift("Auto");

    let html = `<ul class="vsq-quality-selector">`;
    for (var i = 0; i < levels.length; ++i) {
      let label = levels[i];
      let active = "";
      if (
           (i === 0 && this.selectedQuality === "auto") ||
           label === this.selectedQuality
         )
        active = ' class="active"';

      html += `<li${active} data-quality="${label.toLowerCase()}">${Escape.HTML(label)}</li>`;
    }
    html += `</ul>`;
    this.root.find(".fp-ui").append(html);

    this.root.on(this.eventName("click"), ".vsq-quality-selector li", (e: Event): void => {
      e.preventDefault();

      let choice = jQuery(e.currentTarget);
      if (choice.hasClass("active"))
        return;

      this.root.find('.vsq-quality-selector li').removeClass("active");
      choice.addClass("active");

      let quality = choice.attr('data-quality');
      Tools.setToStorage(this.configKey("quality"), quality);

      let level  = this.getQualityIndex(quality);
      let smooth = this.player.conf.smoothSwitching;
      let paused = this.videoTags[Flow.MASTER].paused;

      if (!paused && !smooth)
        jQuery(this.videoTags[Flow.MASTER]).one(this.eventName("pause"), () => {
          this.root.removeClass("is-paused");
        });

      if (smooth && !this.player.poster)
        this.hlsSet('nextLevel', level);
      else
        this.hlsSet('currentLevel', level);

      if (paused)
        this.tagCall('play');
    });
  }

  private initLayoutChooser(): void {
    // nincs masik video, csak a full 100% szamit
    if (this.cfg.secondarySources.length === 0) {
      this.root.addClass('vsq-singlevideo');
      return;
    }

    if (this.root.find('.vsq-layoutchooser').length > 0)
      return;

    let trigger = (newVal?: string) => {
      let ratio = this.root.find('.vsq-layoutchooser input[name="ratio"]');
      if (newVal != null)
        ratio.val(newVal);

      ratio.change();
    };

    let maxHeight = this.root.height();
    // szamra "castolva" hogy ne kelljen html escapelni
    let ratio = 0 + Tools.getFromStorage(this.configKey("layoutRatio"), 150);

    // a 0-300 rangeben igy alakulnak a rangek:
    // 0-80 - pip content
    // 80-110 - master only
    // 110-190 - split
    // 190-220 - content only
    // 220-300 - pip master
    let html = `
      <div class="vsq-layoutchooser">
        <input name="ratio" type="range" min="0" max="300" step="1" value="${ratio}"/>
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
      trigger('40');
    });
    this.root.on("click", ".vsq-layoutchooser .master-only", (e: Event): void => {
      e.preventDefault();
      trigger('80');
    });
    this.root.on("click", ".vsq-layoutchooser .split", (e: Event): void => {
      e.preventDefault();
      trigger('150');
    });
    this.root.on("click", ".vsq-layoutchooser .content-only", (e: Event): void => {
      e.preventDefault();
      trigger('190');
    });
    this.root.on("click", ".vsq-layoutchooser .pip-master", (e: Event): void => {
      e.preventDefault();
      trigger('260');
    });

    this.root.on("input change", '.vsq-layoutchooser input[name="ratio"]', (e: Event): void => {
      let elem = jQuery(e.currentTarget);
      let val = parseInt(elem.val(), 10);
      let masterWidth: number = 50;
      let contentWidth: number = 50;
      let masterOnTop: null | boolean = true;

      // elmentjuk a beallitott erteket hogy refreshnel ugyanaz legyen
      Tools.setToStorage(this.configKey("layoutRatio"), val);

      if (val < 0 || val > 300)
        throw new Error("Invalid value for layoutchooser");

      // pip content
      if (val >= 0 && val < 80) {
        masterWidth = 100;
        contentWidth = (val / 80) * 100;
        masterOnTop = false;
      }

      // master only
      if (val >= 80 && val < 110) {
        masterWidth = 100;
        contentWidth = 0;
        masterOnTop = true;
      }

      // split
      if (val >= 110 && val < 190) {
        let n = val - 110;
        masterWidth = (n / 80) * 100;
        contentWidth = 100 - masterWidth;
        masterOnTop = null;
      }

      // content only
      if (val >= 190 && val < 220) {
        masterWidth = 0;
        contentWidth = 100;
        masterOnTop = false;
      }

      // pip master
      if (val >= 220 && val < 300) {
        let n = val - 220;
        masterWidth = (n / 80) * 100;
        contentWidth = 100;
        masterOnTop = true;
      }

      let masterLeft: 0 | "auto" = 0;
      let masterRight: 0 | "auto" = "auto";
      let contentLeft: 0 | "auto" = "auto";
      let contentRight: 0 | "auto" = 0;
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
        maxHeight: maxHeight + 'px',
        zIndex: masterZ,
        left: masterLeft,
        right: masterRight
      });
      content.css({
        width: contentWidth + '%',
        maxHeight: maxHeight + 'px',
        zIndex: contentZ,
        left: contentLeft,
        right: contentRight
      });
    });

    // az init utani elso trigger, mert a default ertek meg nem lett hasznalva
    trigger();
  }

  public static setup(): void {
    if (Flow.initDone)
      return;

    let proxy: any = (player: Flowplayer, root: Element) => {
      return new Flow(player, root);
    };
    proxy.engineName = Flow.engineName;
    proxy.canPlay = Flow.canPlay;

    flowplayer.engines.unshift(proxy);

    flowplayer((api: Flowplayer): void => {
      // to take precedence over VOD quality selector
      if (Flow.HLSQualitiesSupport(api.conf) && Flow.canPlay("application/x-mpegurl", api.conf))
        api.pluginQualitySelectorEnabled = true;
      else
        api.pluginQualitySelectorEnabled = false;
    });

    Flow.initDone = true;
  }
}

interface Listeners {
  [index: string]: ((e: Event) => void);
}

/* definialni hogy kell a vsq flowplayer confignak kineznie */
interface FlowHLSConfig {
  recoverMediaError: boolean;
  recoverNetworkError: boolean;
  smoothSwitching: boolean;
  strict: boolean;
}
interface FlowSource {
  readonly type: string;
  src: string;
}
interface FlowVideo {
  hlsjs: FlowHLSConfig;
  sources: FlowSource[];
  title: string;
  type: string;
  src: string;
  autoplay: boolean;
}
interface VSQLabels {
  master: string[];
  content: string[];
}
interface VSQConfig {
  duration: number;
  autoplay: boolean;
  secondarySources: FlowSource[];
  labels: VSQLabels;
}
