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

  public cancel(): void {
    if (this.timer === null)
      return;

    clearTimeout(this.timer);
    this.timer = null;
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

  private getByName(name: string): Limit {
    let limit = this.limits[name];
    if (limit == null)
      throw new Error("Limiter for " + name + " not found!");

    return limit;
  }

  public add(name: string, duration: number, callback: any) {
    this.limits[name] = new Limit(name, duration, callback);
  }

  public trigger(name: string): boolean {
    let limit = this.getByName(name);

    return limit.trigger();
  }

  public cancel(name?: string): void {
    if (name != null) {
      this.getByName(name).cancel();
      return;
    }

    for (let k in this.limits) {
      if (!this.limits.hasOwnProperty(k))
        continue;

      let limit = this.limits[k];
      limit.cancel();
    }
  }
}
