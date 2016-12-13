"use strict";
export default class Locale {
  private data: any;
  constructor(data: any) {
    if(typeof data != 'object')
      throw new Error('Invalid locale passed');

    this.data = data;
  }

  get(key: string): string {
    if (this.data[key])
      return String(this.data[key]);

    return key;
  }
}
