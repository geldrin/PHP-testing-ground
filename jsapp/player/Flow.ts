/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
//import "es6-promise"; // majd ha kell

/** A flowplayer plugin implementacioert felel (dual-stream, reconnect stb) */
export default class Flow {
  public static engineName = "vsq";
  private static initDone = false;
  private static readonly MASTER = 0;
  private static readonly CONTENT = 1;

  private id: string;
  private engines: Element[] = [];
  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;
  private volumeLevel: number;
  private eventsInitialized = false;
  private timer: number;

  constructor(player: Flowplayer, root: Element) {
    Flow.log(arguments);
    this.player = player;
    this.cfg = player.conf.vsq as VSQConfig;
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

  private getType(type: string): string {
    return /mpegurl/i.test(type)? "application/x-mpegurl": type;
  }

  private createVideoTag(video: FlowVideo): Element {
    let autoplay = false;
    let preload = 'metadata';

    let ret: any = document.createElement('video');
    ret.src = video.src;
    ret.type = this.getType(video.type);
    ret.className = 'fp-engine';
    ret.autoplay = autoplay? 'autoplay': false;
    ret.preload = preload;
    ret.setAttribute('x-webkit-airplay', 'allow');

    return ret;
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

  private setupVideoEvents(video: FlowVideo): void {
    if (this.eventsInitialized)
      return;

    let master = this.engines[Flow.MASTER];
    let sources = jQuery(this.engines);
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

    let events = {
      // fired
      ended: 'finish',
      pause: 'pause',
      play: 'resume',
      progress: 'buffer',
      timeupdate: 'progress',
      volumechange: 'volume',
      ratechange: 'speed',
      //seeking: 'beforeseek',
      seeked: 'seek',
      // abort: 'resume',

      // not fired
      loadeddata: 'ready',
      // loadedmetadata: 0,
      // canplay: 0,

      // error events
      // load: 0,
      // emptied: 0,
      // empty: 0,
      error: 'error',
      dataunavailable: 'error'
    };

    let trigger = (event: string, arg: any) => {
      this.player.trigger(event, [this.player, arg]);
    };

    let arg: any = {};
    jQuery.each(events, (index: string, flowEvent: string): void => {
      let l = (e: Event) => {
        if (!e.target || jQuery(e.target).find('.fp-engine').length == 0)
          return;

        this.log(index, flowEvent, e);

        if (
             (!this.player.ready && flowEvent !== 'ready' && flowEvent !== 'error') ||
             !flowEvent ||
             this.root.find('video').length === 0
           )
          return;

        switch (flowEvent) {
          case "unload":
            this.player.unload();
            return;
          case "ready":
            arg = jQuery.extend(arg, video, {
              duration: master.duration,
              width: master.videoWidth,
              height: master.videoHeight,
              url: master.currentSrc,
              src: master.currentSrc
            });

            arg.seekable = false;
            try {
              if (
                  !this.player.live &&
                  (master.duration || master.seekable) &&
                  master.seekable.end(null)
                 )
                arg.seekable = true;
            } catch(_) {};

            this.timer = setInterval(() => {
              arg.buffer = master.buffered.end(null);
              if (!arg.buffer)
                return;

              if (
                  this.round(arg.buffer, 1000) < this.round(arg.duration, 1000) &&
                  !arg.buffered
                 ) {
                this.player.trigger("buffer", e);

              } else if (!arg.buffered) {
                arg.buffered = true;
                this.player.trigger("buffer", e).trigger("buffered", e);

                clearInterval(this.timer);
                this.timer = 0;
              }
            }, 250);
            break;
          default:
            throw new Error('unhandled event: ' + flowEvent);
        }

        trigger(flowEvent, arg);
      };
      this.root.get(0).addEventListener(index, l, true);
    });
  }

  public load(video: FlowVideo): void {
    // mihez fogjuk prependelni a videokat
    let root = this.root.find('.fp-player');

    // eloszor a content videot, mert mindig csak prependelunk
    // es igy lesz jo a sorrend
    if (this.cfg.secondarySources && !this.engines[Flow.CONTENT]) {
      // deep copy the video, and set its properties
      let secondVideo = jQuery.extend(true, {}, video);
      secondVideo.src = this.cfg.secondarySources[0].src;
      secondVideo.sources = this.cfg.secondarySources;

      // and insert it into the DOM
      this.engines[Flow.CONTENT] = this.createVideoTag(secondVideo);
      this.engines[Flow.CONTENT].load();
      let engine = jQuery(this.engines[Flow.CONTENT]);
      engine.addClass('vsq-content');
      root.prepend(engine);
    }

    if (!this.engines[Flow.MASTER]) {
      this.engines[Flow.MASTER] = this.createVideoTag(video);
      this.engines[Flow.MASTER].load();
      let engine = jQuery(this.engines[Flow.MASTER]);
      engine.addClass('vsq-master');
      root.prepend(engine);
    }
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
