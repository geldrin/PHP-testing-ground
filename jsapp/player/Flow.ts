/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
//import "es6-promise"; // majd ha kell

declare var Hls: any;

/** A flowplayer plugin implementacioert felel (dual-stream, reconnect stb) */
export default class Flow {
  public static engineName = "vsq";
  private static initDone = false;
  private static readonly MASTER = 0;
  private static readonly CONTENT = 1;

  private id: string;
  private videoTags: Element[] = [];
  private hlsEngines: any[] = [];
  private hlsConf: any;

  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;
  private volumeLevel: number;
  private eventsInitialized = false;
  private timer: number;

  private maxLevel: number; // hls specific
  private recoverMediaErrorDate: number;
  private swapAudioCodecDate: number;

  constructor(player: Flowplayer, root: Element) {
    Flow.log(arguments);
    this.player = player;
    this.cfg = player.conf.vsq as VSQConfig;
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
    this.id = this.root.attr('data-flowplayer-instance-id');
  }

  private static log(...params: Object[]): void {
    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  private log(...params: Object[]): void {
    Flow.log(params);
  }

  // TODO refaktor copypastes
  private hlsCall(funcName: string, args?: any): any {
    let ret: any = [];
    for (let i = this.hlsEngines.length - 1; i >= 0; i--) {
      let hls = this.hlsEngines[i];
      if (hls == null)
        continue;

      ret[i] = hls[funcName].apply(hls, args);
    }

    return ret;
  }

  private hlsSet(property: string, value: any): void {
    let ret: any = [];
    for (let i = this.hlsEngines.length - 1; i >= 0; i--) {
      let hls = this.hlsEngines[i];
      if (hls == null)
        continue;

      hls[property] = value;
    }
  }

  private tagCall(funcName: string, args?: any): any {
    let ret: any = [];
    for (let i = this.videoTags.length - 1; i >= 0; i--) {
      let engine = this.videoTags[i];
      if (engine == null)
        continue;

      ret[i] = engine[funcName].apply(engine, args);
    }

    return ret;
  }

  private tagSet(property: string, value: any): void {
    for (let i = this.videoTags.length - 1; i >= 0; i--) {
      let engine = this.videoTags[i];
      if (engine == null)
        continue;

      engine[property] = value;
    }
  }

  private getType(type: string): string {
    return /mpegurl/i.test(type)? "application/x-mpegurl": type;
  }

  public static canPlay(type: string, conf: Object): boolean {
    return true; // TODO
  }

  private round(val: number, per?: number) {
    let percent = 100;
    if (per)
      percent = per;

    return Math.round(val * percent) / percent;
  }

  // TODO de-magicnumber
  private doRecover(conf: any, flowEvent: string, isNetworkError: boolean): number | undefined {
    if (conf.debug)
      this.log('recovery.vsq', flowEvent);

    this.root.removeClass('is-paused');
    this.root.addClass('is-seeking');
    if (isNetworkError)
      this.hlsCall('startLoad');
    else {
      var now = performance.now();
      if (!this.recoverMediaErrorDate || now - this.recoverMediaErrorDate > 3000) {
        this.recoverMediaErrorDate = performance.now();
        this.hlsCall('recoverMediaError');
      } else {
        if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
          this.swapAudioCodecDate = performance.now();
          this.hlsCall('swapAudioCodec');
          this.hlsCall('recoverMediaError');
        } else
          return 3;
      }
    }

    return undefined;
  }

  private removePoster(): void {
    // TODO
  }

  private setupVideoEvents(video: FlowVideo): void {
    if (this.eventsInitialized)
      return;

    let masterHLS = this.hlsEngines[Flow.MASTER];
    let masterTag = this.videoTags[Flow.MASTER];
    let master = jQuery(masterTag);
    let sources = jQuery(this.videoTags);
    /*
    sources.on('error', (e) => {
      try {
        this.player.trigger('error', [
          this.player,
          {code: 4, video: video}
        ]);
      } catch(_) {}
    });

    this.player.on('shutdown', () => {
      sources.off();
    });
    */

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
      volumechange: "volume",
      error       : "error"
    };
    let hlsEvents = Hls.Events;

    let currentTime: number = masterTag.currentTime;
    let arg: any = {};
    jQuery.each(events, (videoEvent: string, flowEvent: string): void => {
      videoEvent += "." + Flow.engineName;

      master.on(videoEvent, (e: Event) => {
        if (flowEvent.indexOf("progress") < 0)
          this.log(videoEvent, flowEvent, e);

        let video = this.player.video;
        switch(flowEvent) {
          case "ready":
            arg = jQuery.extend(arg, video, {
              duration: masterTag.duration,
              seekable: masterTag.seekable.end(null),
              width: masterTag.videoWidth,
              height: masterTag.videoHeight,
              url: video.src
            });
            break;

          case "resume":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused)
              this.hlsCall('startLoad', [currentTime]);
            break;

          case "seek":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused && masterTag.paused) {
              this.hlsCall('stopLoad');
              this.tagCall('pause');
            }
            arg = currentTime;
            break;

          case "pause":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused)
              this.hlsCall('stopLoad');
            break;

          case "progress":
            arg = currentTime;
            break;

          case "speed":
            arg = masterTag.playbackRate;
            break;

          case "volume":
            arg = masterTag.volume;
            break;

          case "buffer":
            let buffered: any;
            let buffer: number = 0;
            try {
              buffered = masterTag.buffered;
              buffer = buffered.end(null);
              if (currentTime) {
                for (var i = buffered.length - 1; i >= 0; i--) {
                  let buffend = buffered.end(i);
                  if (buffend >= currentTime)
                    buffer = buffend;
                }
              }
            } catch(_) {};

            video.buffer = buffer;
            arg = buffer;
            break;

          case "finish":
            let flush = false;

            if (
                 this.hlsConf.bufferWhilePaused && masterHLS.autoLevelEnabled &&
                 (
                   video.loop ||
                   this.player.conf.playlist.length < 2 ||
                   this.player.conf.advance == false
                 )
               ) {
              flush = !masterHLS.levels[this.maxLevel].details;
              if (!flush)
                masterHLS[this.maxLevel].details.fragments.forEach((frag: any) => {
                  flush = !!flush || !frag.loadCounter;
                });
              }

            if (flush) {
              this.hlsCall('trigger', [
                hlsEvents.BUFFER_FLUSHING,
                {
                  startOffset: 0,
                  endOffset: video.duration
                }
              ]);

              this.log(this.maxLevel);
              this.hlsSet('nextLoadLevel', this.maxLevel);
              this.hlsCall('startLoad', [masterHLS.config.startPosition]);
              this.maxLevel = 0;

              if (!video.loop) {
                // hack to prevent Chrome engine from hanging
                master.one("play." + this.engineName, () => {
                  if (masterTag.currentTime >= masterTag.duration)
                    masterTag.currentTime = 0;
                });
              }
            }
            break;

          case "error":
            let code = masterTag.error.code;
            if (
                 (this.hlsConf.recoverMediaError && code === 3) ||
                 (this.hlsConf.recoverNetworkError && code === 2) ||
                 (this.hlsConf.recover && (code === 2 || code === 3))
               )
              code = this.doRecover(this.player.conf, flowEvent, code === 2);

            arg = false;
            if (code !== undefined) {
              arg = {code: code};
              if (code > 2)
                arg.video = jQuery.extend(video, {url: video.src});
            }
            break;
        }

        if (arg === false)
          return arg;

        this.player.trigger(flowEvent, [this.player, arg]);
      });
    });
  }

  private createVideoTag(video: FlowVideo): Element {
    let autoplay = false;

    let ret: any = document.createElement('video');
    ret.src = video.src;
    ret.type = this.getType(video.type);
    ret.className = 'fp-engine vsq-engine';
    ret.autoplay = autoplay? 'autoplay': false;
    ret.setAttribute('x-webkit-airplay', 'allow');

    return ret;
  }

  private destroyVideoTag(index: number): void {
    let tagElem = this.videoTags[index];

    let elem = jQuery(tagElem);
    elem.find('source').removeAttr('src');
    elem.removeAttr('src');

    // warning squelch
    (tagElem as any).load();
    elem.remove();
  }

  public load(video: FlowVideo): void {
    // mihez fogjuk prependelni a videokat
    let root = this.root.find('.fp-player');
    this.hlsConf = jQuery.extend(
      this.hlsConf,
      this.player.conf.hlsjs,
      this.player.conf.clip.hlsjs,
      video.hlsjs
    );

    // eloszor a content videot, mert mindig csak prependelunk
    // es igy lesz jo a sorrend
    if (this.cfg.secondarySources) {
      if (this.videoTags[Flow.CONTENT])
        this.destroyVideoTag(Flow.CONTENT);

      // deep copy the video, and set its properties
      let secondVideo = jQuery.extend(true, {}, video);
      secondVideo.src = this.cfg.secondarySources[0].src;
      secondVideo.sources = this.cfg.secondarySources;

      // and insert it into the DOM
      this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
      this.videoTags[Flow.CONTENT].load();
      let engine = jQuery(this.videoTags[Flow.CONTENT]);
      engine.addClass('vsq-content');
      root.prepend(engine);
    }

    if (this.videoTags[Flow.MASTER])
      this.destroyVideoTag(Flow.MASTER);

    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
    this.videoTags[Flow.MASTER].load();
    let engine = jQuery(this.videoTags[Flow.MASTER]);
    engine.addClass('vsq-master');
    root.prepend(engine);

    this.setupVideoEvents(video);
  }

  public pause(): void {
    this.multiCall('pause');
  }

  public resume(): void {
    this.multiCall('play');
  }

  public speed(speed: Number): void {
    this.multiSet('playbackRate', speed);
    this.player.trigger('speed', [this.player, speed]);
  }

  public volume(volume: Number): void {
    this.multiSet('volume', volume);
  }

  public unload(): void {
    let videoTags = jQuery(this.videoTags);
    videoTags.remove();

    this.hlsCall('destroy');

    let listeners = '.' + Flow.engineName;
    this.player.off(listeners);
    this.root.off(listeners);
    videoTags.off(listeners);

    for (let i = this.hlsEngines.length - 1; i >= 0; i--)
      this.hlsEngines.pop();

    for (let i = this.videoTags.length - 1; i >= 0; i--)
      this.videoTags.pop();
  }

  public seek(to: Number): void {
    this.multiSet('currentTime', to);
  }

  public pick(sources: FlowSource[]): FlowSource {
    if (sources.length == 0)
      throw new Error("Zero length FlowSources passed");

    return sources[0];
  }

  public static setup(): void {
    if (Flow.initDone)
      return;

    let proxy: any = (player: Flowplayer, root: Element) => {
      return new Flow(player, root);
    };
    proxy.engineName = Flow.engineName;
    proxy.canPlay = Flow.canPlay;

    flowplayer.videoTags.unshift(proxy);
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
  readonly src: string;
}
interface FlowVideo {
  hlsjs: FlowHLSConfig;
  sources: FlowSource[];
  title: string;
  type: string;
  src: string;
  autoplay: boolean;
}
interface VSQConfig {
  secondarySources?: FlowSource[];
}
