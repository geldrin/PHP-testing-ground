"use strict";
export default class Locale {
  private data: Object;
  constructor(data: Object) {
    this.data = data;
  }

  get(key: string): string {
    if (this.data[key])
      return String(this.data[key]);

    return key;
  }
}
