"use strict";

class Limit {
  private name: string;
  private duration: number;
  private lastTriggerDate: number;
  private callback: any;
  private timer: number | null;

  constructor(name: string, duration: number, callback: any) {
    this.name = name;
    this.duration = duration;
    this.callback = callback;
  }

  private call(): void {
    this.timer = null;
    this.lastTriggerDate = performance.now();
    if (this.callback instanceof Function)
      this.callback();
  }

  private enqueue(): void {
    if (this.timer !== null)
      return;

    this.timer = setTimeout(() => this.call(), this.duration);
  }

  public trigger(): boolean {
    let now = performance.now();
    if (now - this.lastTriggerDate < this.duration) {
      this.enqueue();
      return false;
    }

    this.call();
    return true;
  }
}

class Limits {
  [key: string]: Limit;
}

export default class RateLimiter {
  public static SECOND = 1000;
  private limits: Limits;

  constructor() {
    this.limits = new Limits();
  }

  public add(name: string, duration: number, callback: any) {
    this.limits[name] = new Limit(name, duration, callback);
  }

  public trigger(name: string): boolean {
    let limit = this.limits[name];
    if (limit == null)
      throw new Error("Limiter for " + name + " not found!");

    return limit.trigger();
  }
}