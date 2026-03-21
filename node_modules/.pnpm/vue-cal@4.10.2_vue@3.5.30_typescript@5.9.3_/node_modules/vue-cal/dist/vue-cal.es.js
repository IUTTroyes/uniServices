var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
import { openBlock, createElementBlock, Fragment, renderList, normalizeClass, normalizeStyle, createVNode, Transition, withCtx, createElementVNode, renderSlot, toDisplayString, createCommentVNode, createTextVNode, resolveComponent, createBlock, resolveDynamicComponent, createSlots, withKeys, withModifiers, TransitionGroup, normalizeProps, mergeProps } from "vue";
const __variableDynamicImportRuntimeHelper = (glob, path, segs) => {
  const v = glob[path];
  if (v) {
    return typeof v === "function" ? v() : Promise.resolve(v);
  }
  return new Promise((_, reject) => {
    (typeof queueMicrotask === "function" ? queueMicrotask : setTimeout)(
      reject.bind(
        null,
        new Error(
          "Unknown variable dynamic import: " + path + (path.split("/").length !== segs ? ". Note that variables only represent file names one level deep." : "")
        )
      )
    );
  });
};
let now, todayDate, todayF, self;
let _dateObject = {};
let _timeObject = {};
class DateUtils {
  constructor(texts, noPrototypes = false) {
    __publicField(this, "texts", {});
    /**
     * Simply takes a Date and returns the associated time in minutes (sum of hours + minutes).
     *
     * @param {Date} date the JavaScript Date to extract minutes from.
     * @return {Number} the number of minutes (total of hours plus minutes).
     */
    __publicField(this, "dateToMinutes", (date) => date.getHours() * 60 + date.getMinutes());
    self = this;
    this._texts = texts;
    if (!noPrototypes && Date && !Date.prototype.addDays) this._initDatePrototypes();
  }
  _initDatePrototypes() {
    Date.prototype.addDays = function(days) {
      return self.addDays(this, days);
    };
    Date.prototype.subtractDays = function(days) {
      return self.subtractDays(this, days);
    };
    Date.prototype.addHours = function(hours) {
      return self.addHours(this, hours);
    };
    Date.prototype.subtractHours = function(hours) {
      return self.subtractHours(this, hours);
    };
    Date.prototype.addMinutes = function(minutes) {
      return self.addMinutes(this, minutes);
    };
    Date.prototype.subtractMinutes = function(minutes) {
      return self.subtractMinutes(this, minutes);
    };
    Date.prototype.getWeek = function() {
      return self.getWeek(this);
    };
    Date.prototype.isToday = function() {
      return self.isToday(this);
    };
    Date.prototype.isLeapYear = function() {
      return self.isLeapYear(this);
    };
    Date.prototype.format = function(format = "YYYY-MM-DD") {
      return self.formatDate(this, format);
    };
    Date.prototype.formatTime = function(format = "HH:mm") {
      return self.formatTime(this, format);
    };
  }
  removePrototypes() {
    delete Date.prototype.addDays;
    delete Date.prototype.subtractDays;
    delete Date.prototype.addHours;
    delete Date.prototype.subtractHours;
    delete Date.prototype.addMinutes;
    delete Date.prototype.subtractMinutes;
    delete Date.prototype.getWeek;
    delete Date.prototype.isToday;
    delete Date.prototype.isLeapYear;
    delete Date.prototype.format;
    delete Date.prototype.formatTime;
  }
  updateTexts(texts) {
    this._texts = texts;
  }
  // Cache Today's date (to a maximum) for better isToday() performances. Formatted without leading 0.
  // We still need to update Today's date when Today changes without page refresh.
  _todayFormatted() {
    if (todayDate !== (/* @__PURE__ */ new Date()).getDate()) {
      now = /* @__PURE__ */ new Date();
      todayDate = now.getDate();
      todayF = `${now.getFullYear()}-${now.getMonth()}-${now.getDate()}`;
    }
    return todayF;
  }
  // UTILITIES.
  // ====================================================================
  addDays(date, days) {
    const d = new Date(date.valueOf());
    d.setDate(d.getDate() + days);
    return d;
  }
  subtractDays(date, days) {
    const d = new Date(date.valueOf());
    d.setDate(d.getDate() - days);
    return d;
  }
  addHours(date, hours) {
    const d = new Date(date.valueOf());
    d.setHours(d.getHours() + hours);
    return d;
  }
  subtractHours(date, hours) {
    const d = new Date(date.valueOf());
    d.setHours(d.getHours() - hours);
    return d;
  }
  addMinutes(date, minutes) {
    const d = new Date(date.valueOf());
    d.setMinutes(d.getMinutes() + minutes);
    return d;
  }
  subtractMinutes(date, minutes) {
    const d = new Date(date.valueOf());
    d.setMinutes(d.getMinutes() - minutes);
    return d;
  }
  getWeek(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil(((d - yearStart) / 864e5 + 1) / 7);
  }
  isToday(date) {
    return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}` === this._todayFormatted();
  }
  isLeapYear(date) {
    const year = date.getFullYear();
    return !(year % 400) || year % 100 && !(year % 4);
  }
  // Returns today if it's FirstDayOfWeek (Monday or Sunday) or previous FirstDayOfWeek otherwise.
  getPreviousFirstDayOfWeek(date = null, weekStartsOnSunday) {
    const prevFirstDayOfWeek = date && new Date(date.valueOf()) || /* @__PURE__ */ new Date();
    const dayModifier = weekStartsOnSunday ? 7 : 6;
    prevFirstDayOfWeek.setDate(prevFirstDayOfWeek.getDate() - (prevFirstDayOfWeek.getDay() + dayModifier) % 7);
    return prevFirstDayOfWeek;
  }
  /**
   * Converts a string to a Javascript Date object. If a Date object is passed, return it as is.
   *
   * @param {String | Date} date the string to convert to Date.
   * @return {Date} the equivalent Javascript Date object.
   */
  stringToDate(date) {
    if (date instanceof Date) return date;
    if (date.length === 10) date += " 00:00";
    return new Date(date.replace(/-/g, "/"));
  }
  /**
   * Count the number of days this date range spans onto.
   * E.g. countDays(2019-11-02 18:00, 2019-11-03 02:00) = 2
   *
   * @param {String | Date} start the start date
   * @param {String | Date} end the end date
   * @return {Integer} The number of days this date range involves
   */
  countDays(start, end) {
    if (typeof start === "string") start = start.replace(/-/g, "/");
    if (typeof end === "string") end = end.replace(/-/g, "/");
    start = new Date(start).setHours(0, 0, 0, 0);
    end = new Date(end).setHours(0, 0, 1, 0);
    const timezoneDiffMs = (new Date(end).getTimezoneOffset() - new Date(start).getTimezoneOffset()) * 60 * 1e3;
    return Math.ceil((end - start - timezoneDiffMs) / (24 * 3600 * 1e3));
  }
  /**
   * Take 2 dates and check if within the same time step (useful in overlapping events).
   *
   * @return {Boolean} `true` if their time is included in the same time step,
   *                   this means these 2 dates are very close.
   */
  datesInSameTimeStep(date1, date2, timeStep) {
    return Math.abs(date1.getTime() - date2.getTime()) <= timeStep * 60 * 1e3;
  }
  // ====================================================================
  // FORMATTERS.
  // ====================================================================
  /**
   * Formats a date/time to the given format and returns the formatted string.
   *
   * @param {Date} date a JavaScript Date object to format.
   * @param {String} format the wanted format.
   * @param {Object} texts Optional: the localized texts object to override the vue-cal one in this._texts.
   *                       This becomes useful when showing multiple instances with different languages,
   *                       like in the documentation page.
   * @return {String} the formatted date.
   */
  formatDate(date, format = "YYYY-MM-DD", texts = null) {
    if (!texts) texts = this._texts;
    if (!format) format = "YYYY-MM-DD";
    if (format === "YYYY-MM-DD") return this.formatDateLite(date);
    _dateObject = {};
    _timeObject = {};
    const dateObj = {
      YYYY: () => this._hydrateDateObject(date, texts).YYYY,
      YY: () => this._hydrateDateObject(date, texts).YY(),
      M: () => this._hydrateDateObject(date, texts).M,
      MM: () => this._hydrateDateObject(date, texts).MM(),
      MMM: () => this._hydrateDateObject(date, texts).MMM(),
      MMMM: () => this._hydrateDateObject(date, texts).MMMM(),
      MMMMG: () => this._hydrateDateObject(date, texts).MMMMG(),
      D: () => this._hydrateDateObject(date, texts).D,
      DD: () => this._hydrateDateObject(date, texts).DD(),
      S: () => this._hydrateDateObject(date, texts).S(),
      d: () => this._hydrateDateObject(date, texts).d,
      dd: () => this._hydrateDateObject(date, texts).dd(),
      ddd: () => this._hydrateDateObject(date, texts).ddd(),
      dddd: () => this._hydrateDateObject(date, texts).dddd(),
      HH: () => this._hydrateTimeObject(date, texts).HH,
      H: () => this._hydrateTimeObject(date, texts).H,
      hh: () => this._hydrateTimeObject(date, texts).hh,
      h: () => this._hydrateTimeObject(date, texts).h,
      am: () => this._hydrateTimeObject(date, texts).am,
      AM: () => this._hydrateTimeObject(date, texts).AM,
      mm: () => this._hydrateTimeObject(date, texts).mm,
      m: () => this._hydrateTimeObject(date, texts).m
    };
    return format.replace(/(\{[a-zA-Z]+\}|[a-zA-Z]+)/g, (m, contents) => {
      const result = dateObj[contents.replace(/\{|\}/g, "")];
      return result !== void 0 ? result() : contents;
    });
  }
  // More performant function to convert a Date to `YYYY-MM-DD` formatted string only.
  formatDateLite(date) {
    const m = date.getMonth() + 1;
    const d = date.getDate();
    return `${date.getFullYear()}-${m < 10 ? "0" : ""}${m}-${d < 10 ? "0" : ""}${d}`;
  }
  /**
   * Formats a time (from Date or number of mins) to the given format and returns the formatted string.
   *
   * @param {Date | Number} date a JavaScript Date object or a time in minutes.
   * @param {String} format the wanted format.
   * @param {Object} texts Optional: the localized texts object to override the vue-cal one in this._texts.
   *                       This becomes useful when showing multiple instances with different languages,
   *                       like in the documentation page.
   * @param {Boolean} round if time is 23:59:59, rounds up to 24:00 for formatting only.
   * @return {String} the formatted time.
   */
  formatTime(date, format = "HH:mm", texts = null, round = false) {
    let shouldRound = false;
    if (round) {
      const [h, m, s] = [date.getHours(), date.getMinutes(), date.getSeconds()];
      if (h + m + s === 23 + 59 + 59) shouldRound = true;
    }
    if (date instanceof Date && format === "HH:mm") return shouldRound ? "24:00" : this.formatTimeLite(date);
    _timeObject = {};
    if (!texts) texts = this._texts;
    const timeObj = this._hydrateTimeObject(date, texts);
    const formatted = format.replace(/(\{[a-zA-Z]+\}|[a-zA-Z]+)/g, (m, contents) => {
      const result = timeObj[contents.replace(/\{|\}/g, "")];
      return result !== void 0 ? result : contents;
    });
    return shouldRound ? formatted.replace("23:59", "24:00") : formatted;
  }
  /**
   * Formats a time to 'HH:mm' from a Date and returns the formatted string.
   *
   * @param {Date} date a JavaScript Date object to format.
   * @return {String} the formatted time.
   */
  formatTimeLite(date) {
    const h = date.getHours();
    const m = date.getMinutes();
    return `${(h < 10 ? "0" : "") + h}:${(m < 10 ? "0" : "") + m}`;
  }
  _nth(d) {
    if (d > 3 && d < 21) return "th";
    switch (d % 10) {
      case 1:
        return "st";
      case 2:
        return "nd";
      case 3:
        return "rd";
      default:
        return "th";
    }
  }
  _hydrateDateObject(date, texts) {
    if (_dateObject.D) return _dateObject;
    const YYYY = date.getFullYear();
    const M = date.getMonth() + 1;
    const D = date.getDate();
    const day = date.getDay();
    const dayNumber = (day - 1 + 7) % 7;
    _dateObject = {
      // Year.
      YYYY,
      // 2019.
      YY: () => YYYY.toString().substring(2),
      // 19.
      // Month.
      M,
      // 1 to 12.
      MM: () => (M < 10 ? "0" : "") + M,
      // 01 to 12.
      MMM: () => texts.months[M - 1].substring(0, 3),
      // Jan to Dec.
      MMMM: () => texts.months[M - 1],
      // January to December.
      MMMMG: () => (texts.monthsGenitive || texts.months)[M - 1],
      // January to December in genitive form (Greek...)
      // Day.
      D,
      // 1 to 31.
      DD: () => (D < 10 ? "0" : "") + D,
      // 01 to 31.
      S: () => this._nth(D),
      // st, nd, rd, th.
      // Day of the week.
      d: dayNumber + 1,
      // 1 to 7 with 7 = Sunday.
      dd: () => texts.weekDays[dayNumber][0],
      // M to S.
      ddd: () => texts.weekDays[dayNumber].substr(0, 3),
      // Mon to Sun.
      dddd: () => texts.weekDays[dayNumber]
      // Monday to Sunday.
    };
    return _dateObject;
  }
  _hydrateTimeObject(date, texts) {
    if (_timeObject.am) return _timeObject;
    let H, m;
    if (date instanceof Date) {
      H = date.getHours();
      m = date.getMinutes();
    } else {
      H = Math.floor(date / 60);
      m = Math.floor(date % 60);
    }
    const h = H % 12 ? H % 12 : 12;
    const am = (texts || { am: "am", pm: "pm" })[H === 24 || H < 12 ? "am" : "pm"];
    _timeObject = {
      H,
      h,
      HH: (H < 10 ? "0" : "") + H,
      hh: (h < 10 ? "0" : "") + h,
      am,
      AM: am.toUpperCase(),
      m,
      mm: (m < 10 ? "0" : "") + m
    };
    return _timeObject;
  }
  // ====================================================================
}
const minutesInADay$2 = 24 * 60;
class CellUtils {
  constructor(vuecal) {
    __publicField(this, "_vuecal", null);
    /**
     * Select a cell and go to narrower view on double click or single click according to vuecalProps option.
     *
     * @param {Boolean} force Force switching to narrower view.
     * @param {Date} date The selected cell date at the exact time where it was clicked (through cursor coords).
     * @param {Integer} split The selected cell split if any.
     */
    __publicField(this, "selectCell", (force = false, date, split) => {
      this._vuecal.$emit("cell-click", split ? { date, split } : date);
      if (this._vuecal.clickToNavigate || force) this._vuecal.switchToNarrowerView();
      else if (this._vuecal.dblclickToNavigate && "ontouchstart" in window) {
        this._vuecal.domEvents.dblTapACell.taps++;
        setTimeout(() => this._vuecal.domEvents.dblTapACell.taps = 0, this._vuecal.domEvents.dblTapACell.timeout);
        if (this._vuecal.domEvents.dblTapACell.taps >= 2) {
          this._vuecal.domEvents.dblTapACell.taps = 0;
          this._vuecal.switchToNarrowerView();
          this._vuecal.$emit("cell-dblclick", split ? { date, split } : date);
        }
      }
    });
    /**
     * Select a cell and go to narrower view on enter.
     *
     * @param {Boolean} force Force switching to narrower view.
     * @param {Date} date The selected cell date at the exact time where it was clicked (through cursor coords).
     * @param {Integer} split The selected cell split if any.
     */
    __publicField(this, "keyPressEnterCell", (date, split) => {
      this._vuecal.$emit("cell-keypress-enter", split ? { date, split } : date);
      this._vuecal.switchToNarrowerView();
    });
    /**
     * Get the coordinates of the mouse cursor from the cells wrapper referential (`ref="cells"`).
     *
     * @todo Cache bounding box & update it on resize.
     * @param {Object} e the native DOM event object.
     * @return {Object} containing { x: {Number}, y: {Number} }
     */
    __publicField(this, "getPosition", (e) => {
      const { left, top } = this._vuecal.cellsEl.getBoundingClientRect();
      const { clientX, clientY } = "ontouchstart" in window && e.touches ? e.touches[0] : e;
      return { x: clientX - left, y: clientY - top };
    });
    /**
     * Get the number of minutes from the top to the mouse cursor.
     * Returns a constrained time between 0 and 24 * 60.
     *
     * @param {Object} e the native DOM event object.
     * @return {Object} containing { minutes: {Number}, cursorCoords: { x: {Number}, y: {Number} } }
     */
    __publicField(this, "minutesAtCursor", (e) => {
      let minutes = 0;
      let cursorCoords = { x: 0, y: 0 };
      const { timeStep, timeCellHeight, timeFrom } = this._vuecal.$props;
      if (typeof e === "number") minutes = e;
      else if (typeof e === "object") {
        cursorCoords = this.getPosition(e);
        minutes = Math.round(cursorCoords.y * timeStep / parseInt(timeCellHeight) + timeFrom);
      }
      return { minutes: Math.max(Math.min(minutes, minutesInADay$2), 0), cursorCoords };
    });
    this._vuecal = vuecal;
  }
}
const defaultEventDuration = 2;
const minutesInADay$1 = 24 * 60;
let ud;
let _cellOverlaps, _comparisonArray;
class EventUtils {
  constructor(vuecal, dateUtils2) {
    __publicField(this, "_vuecal", null);
    __publicField(this, "eventDefaults", {
      _eid: null,
      start: "",
      // Externally given formatted date & time or Date object.
      startTimeMinutes: 0,
      end: "",
      // Externally given formatted date & time or Date object.
      endTimeMinutes: 0,
      title: "",
      content: "",
      background: false,
      allDay: false,
      segments: null,
      repeat: null,
      daysCount: 1,
      deletable: true,
      deleting: false,
      titleEditable: true,
      resizable: true,
      resizing: false,
      draggable: true,
      dragging: false,
      draggingStatic: false,
      // Controls the CSS class of the static clone while dragging.
      focused: false,
      class: ""
    });
    this._vuecal = vuecal;
    ud = dateUtils2;
  }
  /**
   * Create an event at the given date and time, and allow overriding
   * event attributes through the eventOptions object.
   *
   * @param {Date | String} dateTime The date and time of the new event start.
   * @param {Number} duration the event duration in minutes.
   * @param {Object} eventOptions some options to override the `eventDefaults` - optional.
   */
  createAnEvent(dateTime, duration, eventOptions) {
    if (typeof dateTime === "string") dateTime = ud.stringToDate(dateTime);
    if (!(dateTime instanceof Date)) return false;
    const startTimeMinutes = ud.dateToMinutes(dateTime);
    duration = duration * 1 || defaultEventDuration * 60;
    const endTimeMinutes = startTimeMinutes + duration;
    const end = ud.addMinutes(new Date(dateTime), duration);
    if (eventOptions.end) {
      if (typeof eventOptions.end === "string") eventOptions.end = ud.stringToDate(eventOptions.end);
      eventOptions.endTimeMinutes = ud.dateToMinutes(eventOptions.end);
    }
    const event = {
      ...this.eventDefaults,
      _eid: `${this._vuecal._.uid}_${this._vuecal.eventIdIncrement++}`,
      start: dateTime,
      startTimeMinutes,
      end,
      endTimeMinutes,
      segments: null,
      ...eventOptions
    };
    if (typeof this._vuecal.onEventCreate === "function") {
      if (!this._vuecal.onEventCreate(event, () => this.deleteAnEvent(event))) return;
    }
    if (event.startDateF !== event.endDateF) {
      event.daysCount = ud.countDays(event.start, event.end);
    }
    this._vuecal.mutableEvents.push(event);
    this._vuecal.addEventsToView([event]);
    this._vuecal.emitWithEvent("event-create", event);
    this._vuecal.$emit("event-change", { event: this._vuecal.cleanupEvent(event), originalEvent: null });
    return event;
  }
  /**
   * Add an event segment (= day) to a multiple-day event.
   *
   * @param {Object} e the multiple-day event to add segment in.
   */
  addEventSegment(e) {
    if (!e.segments) {
      e.segments = {};
      e.segments[ud.formatDateLite(e.start)] = {
        start: e.start,
        startTimeMinutes: e.startTimeMinutes,
        endTimeMinutes: minutesInADay$1,
        isFirstDay: true,
        isLastDay: false
      };
    }
    const previousSegment = e.segments[ud.formatDateLite(e.end)];
    if (previousSegment) {
      previousSegment.isLastDay = false;
      previousSegment.endTimeMinutes = minutesInADay$1;
    }
    const start = ud.addDays(e.end, 1);
    const formattedDate = ud.formatDateLite(start);
    start.setHours(0, 0, 0, 0);
    e.segments[formattedDate] = {
      start,
      startTimeMinutes: 0,
      endTimeMinutes: e.endTimeMinutes,
      isFirstDay: false,
      isLastDay: true
    };
    e.end = ud.addMinutes(start, e.endTimeMinutes);
    e.daysCount = Object.keys(e.segments).length;
    return formattedDate;
  }
  /**
   * Remove an event segment (= day) from a multiple-day event.
   *
   * @param {Object} e the multiple-day event to remove segments from.
   */
  removeEventSegment(e) {
    let segmentsCount = Object.keys(e.segments).length;
    if (segmentsCount <= 1) return ud.formatDateLite(e.end);
    delete e.segments[ud.formatDateLite(e.end)];
    segmentsCount--;
    const end = ud.subtractDays(e.end, 1);
    const formattedDate = ud.formatDateLite(end);
    const previousSegment = e.segments[formattedDate];
    if (!segmentsCount) e.segments = null;
    else if (previousSegment) {
      previousSegment.isLastDay = true;
      previousSegment.endTimeMinutes = e.endTimeMinutes;
    } else ;
    e.daysCount = segmentsCount || 1;
    e.end = end;
    return formattedDate;
  }
  /**
   * Create 1 segment per day of the given event, but only within the current view.
   * (It won't create segments for all the days in view that are not in the event!)
   *
   * An event segment is a piece of event per day that contains more day-specific data.
   *
   * @param {Object} e the multiple-day event to create segments for.
   * @param {Date} viewStartDate the starting date of the view.
   * @param {Date} viewEndDate the ending date of the view.
   */
  createEventSegments(e, viewStartDate, viewEndDate) {
    const viewStartTimestamp = viewStartDate.getTime();
    const viewEndTimestamp = viewEndDate.getTime();
    let eventStart = e.start.getTime();
    let eventEnd = e.end.getTime();
    let repeatedEventStartFound = false;
    let timestamp, end, eventStartAtMidnight;
    if (!e.end.getHours() && !e.end.getMinutes()) eventEnd -= 1e3;
    e.segments = {};
    if (!e.repeat) {
      timestamp = Math.max(viewStartTimestamp, eventStart);
      end = Math.min(viewEndTimestamp, eventEnd);
    } else {
      timestamp = viewStartTimestamp;
      end = Math.min(
        viewEndTimestamp,
        e.repeat.until ? ud.stringToDate(e.repeat.until).getTime() : viewEndTimestamp
      );
    }
    while (timestamp <= end) {
      let createSegment = false;
      const nextMidnight = ud.addDays(new Date(timestamp), 1).setHours(0, 0, 0, 0);
      let isFirstDay, isLastDay, start, formattedDate;
      if (e.repeat) {
        const tmpDate = new Date(timestamp);
        const tmpDateFormatted = ud.formatDateLite(tmpDate);
        if (repeatedEventStartFound || e.occurrences && e.occurrences[tmpDateFormatted]) {
          if (!repeatedEventStartFound) {
            eventStart = e.occurrences[tmpDateFormatted].start;
            eventStartAtMidnight = new Date(eventStart).setHours(0, 0, 0, 0);
            eventEnd = e.occurrences[tmpDateFormatted].end;
          }
          repeatedEventStartFound = true;
          createSegment = true;
        }
        isFirstDay = timestamp === eventStartAtMidnight;
        isLastDay = tmpDateFormatted === ud.formatDateLite(new Date(eventEnd));
        start = new Date(isFirstDay ? eventStart : timestamp);
        formattedDate = ud.formatDateLite(start);
        if (isLastDay) repeatedEventStartFound = false;
      } else {
        createSegment = true;
        isFirstDay = timestamp === eventStart;
        isLastDay = end === eventEnd && nextMidnight > end;
        start = isFirstDay ? e.start : new Date(timestamp);
        formattedDate = ud.formatDateLite(isFirstDay ? e.start : start);
      }
      if (createSegment) {
        e.segments[formattedDate] = {
          start,
          startTimeMinutes: isFirstDay ? e.startTimeMinutes : 0,
          endTimeMinutes: isLastDay ? e.endTimeMinutes : minutesInADay$1,
          isFirstDay,
          isLastDay
        };
      }
      timestamp = nextMidnight;
    }
    return e;
  }
  /**
   * Delete an event.
   *
   * @param {Object} event the calendar event to delete.
   */
  deleteAnEvent(event) {
    this._vuecal.emitWithEvent("event-delete", event);
    this._vuecal.mutableEvents = this._vuecal.mutableEvents.filter((e) => e._eid !== event._eid);
    this._vuecal.view.events = this._vuecal.view.events.filter((e) => e._eid !== event._eid);
  }
  // EVENT OVERLAPS.
  // ===================================================================
  // Will recalculate all the overlaps of the current cell OR split.
  // cellEvents will contain only the current split events if in a split.
  checkCellOverlappingEvents(cellEvents, options) {
    _comparisonArray = cellEvents.slice(0);
    _cellOverlaps = {};
    cellEvents.forEach((e) => {
      _comparisonArray.shift();
      if (!_cellOverlaps[e._eid]) _cellOverlaps[e._eid] = { overlaps: [], start: e.start, position: 0 };
      _cellOverlaps[e._eid].position = 0;
      _comparisonArray.forEach((e2) => {
        if (!_cellOverlaps[e2._eid]) _cellOverlaps[e2._eid] = { overlaps: [], start: e2.start, position: 0 };
        const eventIsInRange = this.eventInRange(e2, e.start, e.end);
        const eventsInSameTimeStep = options.overlapsPerTimeStep ? ud.datesInSameTimeStep(e.start, e2.start, options.timeStep) : 1;
        if (!e.background && !e.allDay && !e2.background && !e2.allDay && eventIsInRange && eventsInSameTimeStep) {
          _cellOverlaps[e._eid].overlaps.push(e2._eid);
          _cellOverlaps[e._eid].overlaps = [...new Set(_cellOverlaps[e._eid].overlaps)];
          _cellOverlaps[e2._eid].overlaps.push(e._eid);
          _cellOverlaps[e2._eid].overlaps = [...new Set(_cellOverlaps[e2._eid].overlaps)];
          _cellOverlaps[e2._eid].position++;
        } else {
          let pos1, pos2;
          if ((pos1 = (_cellOverlaps[e._eid] || { overlaps: [] }).overlaps.indexOf(e2._eid)) > -1) _cellOverlaps[e._eid].overlaps.splice(pos1, 1);
          if ((pos2 = (_cellOverlaps[e2._eid] || { overlaps: [] }).overlaps.indexOf(e._eid)) > -1) _cellOverlaps[e2._eid].overlaps.splice(pos2, 1);
          _cellOverlaps[e2._eid].position--;
        }
      });
    });
    let longestStreak = 0;
    for (const id in _cellOverlaps) {
      const item = _cellOverlaps[id];
      const overlapsRow = item.overlaps.map((id2) => ({ id: id2, start: _cellOverlaps[id2].start }));
      overlapsRow.push({ id, start: item.start });
      overlapsRow.sort((a, b) => a.start < b.start ? -1 : a.start > b.start ? 1 : a.id > b.id ? -1 : 1);
      item.position = overlapsRow.findIndex((e) => e.id === id);
      longestStreak = Math.max(this.getOverlapsStreak(item, _cellOverlaps), longestStreak);
    }
    return [_cellOverlaps, longestStreak];
  }
  /**
   * Overlaps streak is the longest horizontal set of simultaneous events.
   * This is determining the width of each events in this streak.
   * E.g. 3 overlapping events [1, 2, 3]; 1 overlaps 2 & 3; 2 & 3 don't overlap;
   *      => streak = 2; each width = 50% not 33%.
   *
   * @param {Object} event The current event we are checking among all the events of the current cell.
   * @param {Object} cellOverlaps An indexed array of all the events overlaps for the current cell.
   * @return {Number} The number of simultaneous event for this event.
   */
  getOverlapsStreak(event, cellOverlaps = {}) {
    let streak = event.overlaps.length + 1;
    let removeFromStreak = [];
    event.overlaps.forEach((id) => {
      if (!removeFromStreak.includes(id)) {
        const overlapsWithoutSelf = event.overlaps.filter((id2) => id2 !== id);
        overlapsWithoutSelf.forEach((id3) => {
          if (!cellOverlaps[id3].overlaps.includes(id)) removeFromStreak.push(id3);
        });
      }
    });
    removeFromStreak = [...new Set(removeFromStreak)];
    streak -= removeFromStreak.length;
    return streak;
  }
  /**
   * Tells whether an event is in a given date range, even partially.
   *
   * @param {Object} event The event to test.
   * @param {Date} start The start of range date object.
   * @param {Date} end The end of range date object.
   * @return {Boolean} true if in range, even partially.
   */
  eventInRange(event, start, end) {
    if (event.allDay || !this._vuecal.time) {
      const startTimestamp2 = new Date(event.start).setHours(0, 0, 0, 0);
      const endTimestamp2 = new Date(event.end).setHours(23, 59, 0, 0);
      return endTimestamp2 >= new Date(start).setHours(0, 0, 0, 0) && startTimestamp2 <= new Date(end).setHours(0, 0, 0, 0);
    }
    const startTimestamp = event.start.getTime();
    const endTimestamp = event.end.getTime();
    return startTimestamp < end.getTime() && endTimestamp > start.getTime();
  }
}
const _hoisted_1$5 = { class: "vuecal__flex vuecal__weekdays-headings" };
const _hoisted_2$3 = ["onClick"];
const _hoisted_3$3 = {
  class: "vuecal__flex weekday-label",
  grow: ""
};
const _hoisted_4$3 = { class: "full" };
const _hoisted_5$3 = { class: "small" };
const _hoisted_6$2 = { class: "xsmall" };
const _hoisted_7$2 = { key: 0 };
const _hoisted_8$2 = {
  key: 0,
  class: "vuecal__flex vuecal__split-days-headers",
  grow: ""
};
function render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$5, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($options.headings, (heading, i) => {
      return openBlock(), createElementBlock(Fragment, { key: i }, [
        !heading.hide ? (openBlock(), createElementBlock("div", {
          key: 0,
          class: normalizeClass(["vuecal__flex vuecal__heading", { today: heading.today, clickable: $options.cellHeadingsClickable }]),
          style: normalizeStyle($options.weekdayCellStyles),
          onClick: ($event) => $options.view.id === "week" && $options.selectCell(heading.date, $event),
          onDblclick: _cache[0] || (_cache[0] = ($event) => $options.view.id === "week" && $options.vuecal.dblclickToNavigate && $props.switchToNarrowerView())
        }, [
          createVNode(Transition, {
            name: `slide-fade--${$props.transitionDirection}`,
            appear: $options.vuecal.transitions
          }, {
            default: withCtx(() => [
              (openBlock(), createElementBlock("div", {
                class: "vuecal__flex",
                column: "",
                key: $options.vuecal.transitions ? `${i}-${heading.dayOfMonth}` : false
              }, [
                createElementVNode("div", _hoisted_3$3, [
                  renderSlot(_ctx.$slots, "weekday-heading", {
                    heading: $options.cleanupHeading(heading),
                    view: $options.view
                  }, () => [
                    createElementVNode("span", _hoisted_4$3, toDisplayString(heading.full), 1),
                    createElementVNode("span", _hoisted_5$3, toDisplayString(heading.small), 1),
                    createElementVNode("span", _hoisted_6$2, toDisplayString(heading.xsmall), 1),
                    heading.dayOfMonth ? (openBlock(), createElementBlock("span", _hoisted_7$2, " " + toDisplayString(heading.dayOfMonth), 1)) : createCommentVNode("", true)
                  ])
                ]),
                $options.vuecal.hasSplits && $options.vuecal.stickySplitLabels ? (openBlock(), createElementBlock("div", _hoisted_8$2, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.vuecal.daySplits, (split, i2) => {
                    return openBlock(), createElementBlock("div", {
                      class: normalizeClass(["day-split-header", split.class || false]),
                      key: i2
                    }, [
                      renderSlot(_ctx.$slots, "split-label", {
                        split,
                        view: $options.view
                      }, () => [
                        createTextVNode(toDisplayString(split.label), 1)
                      ])
                    ], 2);
                  }), 128))
                ])) : createCommentVNode("", true)
              ]))
            ]),
            _: 2
          }, 1032, ["name", "appear"])
        ], 46, _hoisted_2$3)) : createCommentVNode("", true)
      ], 64);
    }), 128))
  ]);
}
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$5 = {
  inject: ["vuecal", "utils", "view"],
  props: {
    transitionDirection: { type: String, default: "right" },
    weekDays: { type: Array, default: () => [] },
    switchToNarrowerView: { type: Function, default: () => {
    } }
  },
  methods: {
    selectCell(date, DOMEvent) {
      if (date.getTime() !== this.view.selectedDate.getTime()) {
        this.view.selectedDate = date;
      }
      this.utils.cell.selectCell(false, date, DOMEvent);
    },
    cleanupHeading: (heading) => ({
      label: heading.full,
      date: heading.date,
      ...heading.today ? { today: heading.today } : {}
    })
  },
  computed: {
    headings() {
      if (!["month", "week"].includes(this.view.id)) return [];
      let todayFound = false;
      const headings = this.weekDays.map((cell, i) => {
        const date = this.utils.date.addDays(this.view.startDate, this.vuecal.startWeekOnSunday && this.vuecal.hideWeekends ? i - 1 : i);
        return {
          hide: cell.hide,
          full: cell.label,
          // If defined in i18n file, weekDaysShort overrides default truncation of
          // week days when does not fit on screen or with small/xsmall options.
          small: cell.short || cell.label.substr(0, 3),
          xsmall: cell.short || cell.label.substr(0, 1),
          // Only for week view.
          ...this.view.id === "week" ? {
            dayOfMonth: date.getDate(),
            date,
            today: !todayFound && this.utils.date.isToday(date) && !todayFound++
          } : {}
        };
      });
      return headings;
    },
    cellWidth() {
      return 100 / (7 - this.weekDays.reduce((total, day) => total + day.hide, 0));
    },
    weekdayCellStyles() {
      return {
        ...this.vuecal.hideWeekdays.length ? { width: `${this.cellWidth}%` } : {}
      };
    },
    cellHeadingsClickable() {
      return this.view.id === "week" && (this.vuecal.clickToNavigate || this.vuecal.dblclickToNavigate);
    }
  }
};
const WeekdaysHeadings = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", render$5]]);
const _hoisted_1$4 = { class: "vuecal__header" };
const _hoisted_2$2 = {
  key: 0,
  class: "vuecal__flex vuecal__menu",
  role: "tablist",
  "aria-label": "Calendar views navigation"
};
const _hoisted_3$2 = ["onDragenter", "onDragleave", "onClick", "aria-label"];
const _hoisted_4$2 = {
  key: 1,
  class: "vuecal__title-bar"
};
const _hoisted_5$2 = ["aria-label"];
const _hoisted_6$1 = {
  class: "vuecal__flex vuecal__title",
  grow: ""
};
const _hoisted_7$1 = ["aria-label"];
const _hoisted_8$1 = {
  key: 0,
  class: "vuecal__flex vuecal__split-days-headers"
};
function render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_weekdays_headings = resolveComponent("weekdays-headings");
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    !$props.options.hideViewSelector ? (openBlock(), createElementBlock("div", _hoisted_2$2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.viewProps.views, (v, id) => {
        return openBlock(), createElementBlock(Fragment, { key: id }, [
          v.enabled ? (openBlock(), createElementBlock("button", {
            key: 0,
            class: normalizeClass(["vuecal__view-btn", { "vuecal__view-btn--active": $options.view.id === id, "vuecal__view-btn--highlighted": _ctx.highlightedControl === id }]),
            type: "button",
            onDragenter: ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragEnter($event, id, _ctx.$data),
            onDragleave: ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragLeave($event, id, _ctx.$data),
            onClick: ($event) => $options.switchView(id, null, true),
            "aria-label": `${v.label} view`
          }, toDisplayString(v.label), 43, _hoisted_3$2)) : createCommentVNode("", true)
        ], 64);
      }), 128))
    ])) : createCommentVNode("", true),
    !$props.options.hideTitleBar ? (openBlock(), createElementBlock("div", _hoisted_4$2, [
      createElementVNode("button", {
        class: normalizeClass(["vuecal__arrow vuecal__arrow--prev", { "vuecal__arrow--highlighted": _ctx.highlightedControl === "previous" }]),
        type: "button",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.previous && $options.previous(...args)),
        onDragenter: _cache[1] || (_cache[1] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragEnter($event, "previous", _ctx.$data)),
        onDragleave: _cache[2] || (_cache[2] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragLeave($event, "previous", _ctx.$data)),
        "aria-label": `Previous ${$options.view.id}`
      }, [
        renderSlot(_ctx.$slots, "arrow-prev")
      ], 42, _hoisted_5$2),
      createElementVNode("div", _hoisted_6$1, [
        createVNode(Transition, {
          name: $props.options.transitions ? `slide-fade--${$options.transitionDirection}` : ""
        }, {
          default: withCtx(() => [
            (openBlock(), createBlock(resolveDynamicComponent($options.broaderView ? "button" : "span"), {
              type: !!$options.broaderView && "button",
              key: `${$options.view.id}${$options.view.startDate.toString()}`,
              onClick: _cache[3] || (_cache[3] = ($event) => !!$options.broaderView && $options.switchToBroaderView()),
              "aria-label": !!$options.broaderView && `Go to ${$options.broaderView} view`
            }, {
              default: withCtx(() => [
                renderSlot(_ctx.$slots, "title")
              ]),
              _: 3
            }, 8, ["type", "aria-label"]))
          ]),
          _: 3
        }, 8, ["name"])
      ]),
      $props.options.todayButton ? (openBlock(), createElementBlock("button", {
        key: 0,
        class: normalizeClass(["vuecal__today-btn", { "vuecal__today-btn--highlighted": _ctx.highlightedControl === "today" }]),
        type: "button",
        onClick: _cache[4] || (_cache[4] = (...args) => $options.goToToday && $options.goToToday(...args)),
        onDragenter: _cache[5] || (_cache[5] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragEnter($event, "today", _ctx.$data)),
        onDragleave: _cache[6] || (_cache[6] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragLeave($event, "today", _ctx.$data)),
        "aria-label": "Today"
      }, [
        renderSlot(_ctx.$slots, "today-button")
      ], 34)) : createCommentVNode("", true),
      createElementVNode("button", {
        class: normalizeClass(["vuecal__arrow vuecal__arrow--next", { "vuecal__arrow--highlighted": _ctx.highlightedControl === "next" }]),
        type: "button",
        onClick: _cache[7] || (_cache[7] = (...args) => $options.next && $options.next(...args)),
        onDragenter: _cache[8] || (_cache[8] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragEnter($event, "next", _ctx.$data)),
        onDragleave: _cache[9] || (_cache[9] = ($event) => $props.editEvents.drag && $options.dnd && $options.dnd.viewSelectorDragLeave($event, "next", _ctx.$data)),
        "aria-label": `Next ${$options.view.id}`
      }, [
        renderSlot(_ctx.$slots, "arrow-next")
      ], 42, _hoisted_7$1)
    ])) : createCommentVNode("", true),
    $props.viewProps.weekDaysInHeader ? (openBlock(), createBlock(_component_weekdays_headings, {
      key: 2,
      "week-days": $props.weekDays,
      "transition-direction": $options.transitionDirection,
      "switch-to-narrower-view": $props.switchToNarrowerView
    }, createSlots({ _: 2 }, [
      _ctx.$slots["weekday-heading"] ? {
        name: "weekday-heading",
        fn: withCtx(({ heading, view }) => [
          renderSlot(_ctx.$slots, "weekday-heading", {
            heading,
            view
          })
        ]),
        key: "0"
      } : void 0,
      _ctx.$slots["split-label"] ? {
        name: "split-label",
        fn: withCtx(({ split }) => [
          renderSlot(_ctx.$slots, "split-label", {
            split,
            view: $options.view
          })
        ]),
        key: "1"
      } : void 0
    ]), 1032, ["week-days", "transition-direction", "switch-to-narrower-view"])) : createCommentVNode("", true),
    createVNode(Transition, {
      name: `slide-fade--${$options.transitionDirection}`
    }, {
      default: withCtx(() => [
        $options.showDaySplits ? (openBlock(), createElementBlock("div", _hoisted_8$1, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($props.daySplits, (split, i) => {
            return openBlock(), createElementBlock("div", {
              class: normalizeClass(["day-split-header", split.class || false]),
              key: i
            }, [
              renderSlot(_ctx.$slots, "split-label", {
                split,
                view: $options.view.id
              }, () => [
                createTextVNode(toDisplayString(split.label), 1)
              ])
            ], 2);
          }), 128))
        ])) : createCommentVNode("", true)
      ]),
      _: 3
    }, 8, ["name"])
  ]);
}
const _sfc_main$4 = {
  inject: ["vuecal", "previous", "next", "switchView", "updateSelectedDate", "modules", "view"],
  components: { WeekdaysHeadings },
  props: {
    // Vuecal main component options (props).
    options: { type: Object, default: () => ({}) },
    editEvents: { type: Object, required: true },
    hasSplits: { type: [Boolean, Number], default: false },
    daySplits: { type: Array, default: () => [] },
    viewProps: { type: Object, default: () => ({}) },
    weekDays: { type: Array, default: () => [] },
    switchToNarrowerView: { type: Function, default: () => {
    } }
  },
  data: () => ({
    highlightedControl: null
  }),
  methods: {
    goToToday() {
      this.updateSelectedDate(new Date((/* @__PURE__ */ new Date()).setHours(0, 0, 0, 0)));
    },
    switchToBroaderView() {
      this.transitionDirection = "left";
      if (this.broaderView) this.switchView(this.broaderView);
    }
  },
  computed: {
    transitionDirection: {
      get() {
        return this.vuecal.transitionDirection;
      },
      set(direction) {
        this.vuecal.transitionDirection = direction;
      }
    },
    broaderView() {
      const { enabledViews } = this.vuecal;
      return enabledViews[enabledViews.indexOf(this.view.id) - 1];
    },
    showDaySplits() {
      return this.view.id === "day" && this.hasSplits && this.options.stickySplitLabels && !this.options.minSplitWidth;
    },
    // Drag & drop module.
    dnd() {
      return this.modules.dnd;
    }
  }
};
const Header = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", render$4]]);
const _hoisted_1$3 = ["draggable"];
function render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["vuecal__event", $options.eventClasses]),
    style: normalizeStyle($options.eventStyles),
    tabindex: "0",
    onFocus: _cache[4] || (_cache[4] = (...args) => $options.focusEvent && $options.focusEvent(...args)),
    onKeypress: _cache[5] || (_cache[5] = withKeys(withModifiers((...args) => $options.onEnterKeypress && $options.onEnterKeypress(...args), ["stop"]), ["enter"])),
    onMouseenter: _cache[6] || (_cache[6] = (...args) => $options.onMouseEnter && $options.onMouseEnter(...args)),
    onMouseleave: _cache[7] || (_cache[7] = (...args) => $options.onMouseLeave && $options.onMouseLeave(...args)),
    onTouchstart: _cache[8] || (_cache[8] = withModifiers((...args) => $options.onTouchStart && $options.onTouchStart(...args), ["stop"])),
    onMousedown: _cache[9] || (_cache[9] = ($event) => $options.onMouseDown($event)),
    onMouseup: _cache[10] || (_cache[10] = (...args) => $options.onMouseUp && $options.onMouseUp(...args)),
    onTouchend: _cache[11] || (_cache[11] = (...args) => $options.onMouseUp && $options.onMouseUp(...args)),
    onTouchmove: _cache[12] || (_cache[12] = (...args) => $options.onTouchMove && $options.onTouchMove(...args)),
    onDblclick: _cache[13] || (_cache[13] = (...args) => $options.onDblClick && $options.onDblClick(...args)),
    draggable: $options.draggable,
    onDragstart: _cache[14] || (_cache[14] = ($event) => $options.draggable && $options.onDragStart($event)),
    onDragend: _cache[15] || (_cache[15] = ($event) => $options.draggable && $options.onDragEnd())
  }, [
    $options.vuecal.editEvents.delete && $props.event.deletable ? (openBlock(), createElementBlock("div", {
      key: 0,
      class: "vuecal__event-delete",
      onClick: _cache[0] || (_cache[0] = withModifiers((...args) => $options.deleteEvent && $options.deleteEvent(...args), ["stop"])),
      onTouchstart: _cache[1] || (_cache[1] = withModifiers((...args) => $options.touchDeleteEvent && $options.touchDeleteEvent(...args), ["stop"]))
    }, toDisplayString($options.vuecal.texts.deleteEvent), 33)) : createCommentVNode("", true),
    renderSlot(_ctx.$slots, "event", {
      event: $props.event,
      view: $options.view.id
    }),
    $options.resizable ? (openBlock(), createElementBlock("div", {
      key: 1,
      class: "vuecal__event-resize-handle",
      contenteditable: "false",
      onMousedown: _cache[2] || (_cache[2] = withModifiers((...args) => $options.onResizeHandleMouseDown && $options.onResizeHandleMouseDown(...args), ["stop", "prevent"])),
      onTouchstart: _cache[3] || (_cache[3] = withModifiers((...args) => $options.onResizeHandleMouseDown && $options.onResizeHandleMouseDown(...args), ["stop", "prevent"]))
    }, null, 32)) : createCommentVNode("", true)
  ], 46, _hoisted_1$3);
}
const _sfc_main$3 = {
  inject: ["vuecal", "utils", "modules", "view", "domEvents", "editEvents"],
  props: {
    cellFormattedDate: { type: String, default: "" },
    event: { type: Object, default: () => ({}) },
    cellEvents: { type: Array, default: () => [] },
    overlaps: { type: Array, default: () => [] },
    // If multiple simultaneous events, the events are placed from left to right from the
    // one starting first to the last. (See utils/event.js > checkCellOverlappingEvents)
    eventPosition: { type: Number, default: 0 },
    overlapsStreak: { type: Number, default: 0 },
    allDay: { type: Boolean, default: false }
    // Is the event displayed in the all-day bar.
  },
  data: () => ({
    // Event touch detection with 30px threshold.
    touch: {
      dragThreshold: 30,
      // px.
      startX: 0,
      startY: 0,
      // Detect if the event touch start + touch end was a drag or a tap.
      // If it was a drag, don't call the event-click handler.
      dragged: false
    }
  }),
  methods: {
    /**
     * On event mousedown.
     * Do not prevent propagation to trigger cell mousedown which highlights the cell if not highlighted.
     */
    onMouseDown(e, touch = false) {
      if ("ontouchstart" in window && !touch) return false;
      const { clickHoldAnEvent, focusAnEvent, resizeAnEvent, dragAnEvent } = this.domEvents;
      if (focusAnEvent._eid === this.event._eid && clickHoldAnEvent._eid === this.event._eid) {
        return true;
      }
      this.focusEvent();
      clickHoldAnEvent._eid = null;
      if (this.vuecal.editEvents.delete && this.event.deletable) {
        clickHoldAnEvent.timeoutId = setTimeout(() => {
          if (!resizeAnEvent._eid && !dragAnEvent._eid) {
            clickHoldAnEvent._eid = this.event._eid;
            this.event.deleting = true;
          }
        }, clickHoldAnEvent.timeout);
      }
    },
    /**
     * The mouseup handler is global (whole document) and initialized in index.vue on mounted.
     * It handles the mouseup on cell, events, and everything.
     * All mouseup on event, should be put there to avoid conflicts with other cases.
     * This function is also called on touchend on the event.
     */
    onMouseUp(e) {
      if (this.domEvents.focusAnEvent._eid === this.event._eid && !this.touch.dragged) {
        this.domEvents.focusAnEvent.mousedUp = true;
      }
      this.touch.dragged = false;
    },
    onMouseEnter(e) {
      e.preventDefault();
      this.vuecal.emitWithEvent("event-mouse-enter", this.event);
    },
    onMouseLeave(e) {
      e.preventDefault();
      this.vuecal.emitWithEvent("event-mouse-leave", this.event);
    },
    // Detect if user taps on an event or drags it. If dragging, don't fire the event-click handler (if any).
    onTouchMove(e) {
      if (typeof this.vuecal.onEventClick !== "function") return;
      const { clientX, clientY } = e.touches[0];
      const { startX, startY, dragThreshold } = this.touch;
      if (Math.abs(clientX - startX) > dragThreshold || Math.abs(clientY - startY) > dragThreshold) {
        this.touch.dragged = true;
      }
    },
    onTouchStart(e) {
      this.touch.startX = e.touches[0].clientX;
      this.touch.startY = e.touches[0].clientY;
      this.onMouseDown(e, true);
    },
    onEnterKeypress(e) {
      if (typeof this.vuecal.onEventClick === "function") return this.vuecal.onEventClick(this.event, e);
    },
    onDblClick(e) {
      if (typeof this.vuecal.onEventDblclick === "function") return this.vuecal.onEventDblclick(this.event, e);
    },
    onDragStart(e) {
      this.dnd && this.dnd.eventDragStart(e, this.event);
    },
    onDragEnd() {
      this.dnd && this.dnd.eventDragEnd(this.event);
    },
    onResizeHandleMouseDown() {
      this.focusEvent();
      this.domEvents.dragAnEvent._eid = null;
      this.domEvents.resizeAnEvent = Object.assign(this.domEvents.resizeAnEvent, {
        _eid: this.event._eid,
        start: (this.segment || this.event).start,
        split: this.event.split || null,
        segment: !!this.segment && this.utils.date.formatDateLite(this.segment.start),
        originalEnd: new Date((this.segment || this.event).end),
        originalEndTimeMinutes: this.event.endTimeMinutes
      });
      this.event.resizing = true;
    },
    deleteEvent(touch = false) {
      if ("ontouchstart" in window && !touch) return false;
      this.utils.event.deleteAnEvent(this.event);
    },
    touchDeleteEvent(event) {
      this.deleteEvent(true);
    },
    cancelDeleteEvent() {
      this.event.deleting = false;
    },
    focusEvent() {
      const { focusAnEvent } = this.domEvents;
      const focusedEvent = focusAnEvent._eid;
      if (focusedEvent === this.event._eid) return;
      else if (focusedEvent) {
        const event = this.view.events.find((e) => e._eid === focusedEvent);
        if (event) event.focused = false;
      }
      this.vuecal.cancelDelete();
      this.vuecal.emitWithEvent("event-focus", this.event);
      focusAnEvent._eid = this.event._eid;
      this.event.focused = true;
    }
  },
  computed: {
    eventDimensions() {
      const { startTimeMinutes, endTimeMinutes } = this.segment || this.event;
      let minutesFromTop = startTimeMinutes - this.vuecal.timeFrom;
      const top = Math.max(Math.round(minutesFromTop * this.vuecal.timeCellHeight / this.vuecal.timeStep), 0);
      minutesFromTop = Math.min(endTimeMinutes, this.vuecal.timeTo) - this.vuecal.timeFrom;
      const bottom = Math.round(minutesFromTop * this.vuecal.timeCellHeight / this.vuecal.timeStep);
      const height = Math.max(bottom - top, 5);
      return { top, height };
    },
    eventStyles() {
      if (this.event.allDay || !this.vuecal.time || !this.event.endTimeMinutes || this.view.id === "month" || this.allDay) return {};
      let width = 100 / Math.min(this.overlaps.length + 1, this.overlapsStreak);
      let left = 100 / (this.overlaps.length + 1) * this.eventPosition;
      if (this.vuecal.minEventWidth && width < this.vuecal.minEventWidth) {
        width = this.vuecal.minEventWidth;
        left = (100 - this.vuecal.minEventWidth) / this.overlaps.length * this.eventPosition;
      }
      const { top, height } = this.eventDimensions;
      return {
        top: `${top}px`,
        height: `${height}px`,
        width: `${width}%`,
        left: this.event.left && `${this.event.left}px` || `${left}%`
      };
    },
    // Don't rely on global variables otherwise whenever it would change all the events would be redrawn.
    eventClasses() {
      const { isFirstDay, isLastDay } = this.segment || {};
      return {
        [this.event.class]: !!this.event.class,
        "vuecal__event--focus": this.event.focused,
        "vuecal__event--resizing": this.event.resizing,
        "vuecal__event--background": this.event.background,
        "vuecal__event--deletable": this.event.deleting,
        "vuecal__event--all-day": this.event.allDay,
        // Only apply the dragging class on the event copy that is being dragged.
        "vuecal__event--dragging": !this.event.draggingStatic && this.event.dragging,
        // Only apply the static class on the event original that remains static while a copy is being dragged.
        // Sometimes when dragging fast the static class would get stuck and events stays invisible,
        // So if dragging is false disable the static class as well.
        "vuecal__event--static": this.event.dragging && this.event.draggingStatic,
        // Multiple days events.
        "vuecal__event--multiple-days": !!this.segment,
        "event-start": this.segment && isFirstDay && !isLastDay,
        "event-middle": this.segment && !isFirstDay && !isLastDay,
        "event-end": this.segment && isLastDay && !isFirstDay
      };
    },
    // When multiple-day events, a segment is a portion of event spanning on 1 day.
    segment() {
      return this.event.segments && this.event.segments[this.cellFormattedDate] || null;
    },
    draggable() {
      const { draggable, background, daysCount } = this.event;
      return this.vuecal.editEvents.drag && draggable && !background && daysCount === 1;
    },
    resizable() {
      const { editEvents, time } = this.vuecal;
      return editEvents.resize && this.event.resizable && time && !this.allDay && (!this.segment || this.segment && this.segment.isLastDay) && this.view.id !== "month";
    },
    // Drag & drop module.
    dnd() {
      return this.modules.dnd;
    }
  }
};
const Event = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", render$3]]);
const _hoisted_1$2 = ["data-split", "aria-label", "onTouchstart", "onMousedown", "onDragover", "onDrop"];
const _hoisted_2$1 = {
  key: 0,
  class: "cell-time-labels"
};
const _hoisted_3$1 = ["innerHTML"];
const _hoisted_4$1 = {
  key: 2,
  class: "vuecal__cell-events"
};
const _hoisted_5$1 = ["title"];
function render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_event = resolveComponent("event");
  return openBlock(), createBlock(TransitionGroup, {
    class: normalizeClass(["vuecal__cell", $options.cellClasses]),
    name: `slide-fade--${$options.transitionDirection}`,
    tag: "div",
    appear: $props.options.transitions,
    style: normalizeStyle($options.cellStyles)
  }, {
    default: withCtx(() => [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.splitsCount ? $options.splits : 1, (split, i) => {
        return openBlock(), createElementBlock("div", {
          class: normalizeClass(["vuecal__flex vuecal__cell-content", $options.splitsCount && $options.splitClasses(split)]),
          key: $props.options.transitions ? `${$options.view.id}-${$props.data.content}-${i}` : i,
          "data-split": $options.splitsCount ? split.id : false,
          column: "",
          tabindex: "0",
          "aria-label": $props.data.content,
          onFocus: _cache[0] || (_cache[0] = ($event) => $options.onCellFocus($event)),
          onKeypress: _cache[1] || (_cache[1] = withKeys(($event) => $options.onCellkeyPressEnter($event), ["enter"])),
          onTouchstart: ($event) => !$options.isDisabled && $options.onCellTouchStart($event, $options.splitsCount ? split.id : null),
          onMousedown: ($event) => !$options.isDisabled && $options.onCellMouseDown($event, $options.splitsCount ? split.id : null),
          onClick: _cache[2] || (_cache[2] = ($event) => !$options.isDisabled && $options.onCellClick($event)),
          onDblclick: _cache[3] || (_cache[3] = ($event) => !$options.isDisabled && $options.onCellDblClick($event)),
          onContextmenu: _cache[4] || (_cache[4] = ($event) => !$options.isDisabled && $props.options.cellContextmenu && $options.onCellContextMenu($event)),
          onDragenter: _cache[5] || (_cache[5] = ($event) => !$options.isDisabled && $props.editEvents.drag && $options.dnd && $options.dnd.cellDragEnter($event, _ctx.$data, $props.data.startDate)),
          onDragover: ($event) => !$options.isDisabled && $props.editEvents.drag && $options.dnd && $options.dnd.cellDragOver($event, _ctx.$data, $props.data.startDate, $options.splitsCount ? split.id : null),
          onDragleave: _cache[6] || (_cache[6] = ($event) => !$options.isDisabled && $props.editEvents.drag && $options.dnd && $options.dnd.cellDragLeave($event, _ctx.$data, $props.data.startDate)),
          onDrop: ($event) => !$options.isDisabled && $props.editEvents.drag && $options.dnd && $options.dnd.cellDragDrop($event, _ctx.$data, $props.data.startDate, $options.splitsCount ? split.id : null)
        }, [
          $props.options.showTimeInCells && $props.options.time && $options.isWeekOrDayView && !$props.allDay ? (openBlock(), createElementBlock("div", _hoisted_2$1, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.vuecal.timeCells, (cell, i2) => {
              return openBlock(), createElementBlock("span", {
                class: "cell-time-label",
                key: i2
              }, toDisplayString(cell.label), 1);
            }), 128))
          ])) : createCommentVNode("", true),
          $options.isWeekOrDayView && !$props.allDay && $options.specialHours.length ? (openBlock(true), createElementBlock(Fragment, { key: 1 }, renderList($options.specialHours, (block, i2) => {
            return openBlock(), createElementBlock("div", {
              class: normalizeClass(["vuecal__special-hours", `vuecal__special-hours--day${block.day} ${block.class}`]),
              style: normalizeStyle(`height: ${block.height}px;top: ${block.top}px`)
            }, [
              block.label ? (openBlock(), createElementBlock("div", {
                key: 0,
                class: "special-hours-label",
                innerHTML: block.label
              }, null, 8, _hoisted_3$1)) : createCommentVNode("", true)
            ], 6);
          }), 256)) : createCommentVNode("", true),
          renderSlot(_ctx.$slots, "cell-content", {
            events: $options.events,
            selectCell: ($event) => $options.selectCell($event, true),
            split: $options.splitsCount ? split : false
          }),
          $options.eventsCount && ($options.isWeekOrDayView || $options.view.id === "month" && $props.options.eventsOnMonthView) ? (openBlock(), createElementBlock("div", _hoisted_4$1, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.splitsCount ? split.events : $options.events, (event, j) => {
              return openBlock(), createBlock(_component_event, {
                key: j,
                "cell-formatted-date": $props.data.formattedDate,
                event,
                "all-day": $props.allDay,
                "cell-events": $options.splitsCount ? split.events : $options.events,
                overlaps: (($options.splitsCount ? split.overlaps[event._eid] : _ctx.cellOverlaps[event._eid]) || []).overlaps,
                "event-position": (($options.splitsCount ? split.overlaps[event._eid] : _ctx.cellOverlaps[event._eid]) || []).position,
                "overlaps-streak": $options.splitsCount ? split.overlapsStreak : _ctx.cellOverlapsStreak
              }, {
                event: withCtx(({ event: event2, view }) => [
                  renderSlot(_ctx.$slots, "event", {
                    view,
                    event: event2
                  })
                ]),
                _: 2
              }, 1032, ["cell-formatted-date", "event", "all-day", "cell-events", "overlaps", "event-position", "overlaps-streak"]);
            }), 128))
          ])) : createCommentVNode("", true)
        ], 42, _hoisted_1$2);
      }), 128)),
      $options.timelineVisible ? (openBlock(), createElementBlock("div", {
        class: "vuecal__now-line",
        style: normalizeStyle(`top: ${$options.todaysTimePosition}px`),
        key: $props.options.transitions ? `${$options.view.id}-now-line` : "now-line",
        title: $options.utils.date.formatTime($options.vuecal.now)
      }, null, 12, _hoisted_5$1)) : createCommentVNode("", true)
    ]),
    _: 3
  }, 8, ["class", "name", "appear", "style"]);
}
const _sfc_main$2 = {
  inject: ["vuecal", "utils", "modules", "view", "domEvents"],
  components: { Event },
  props: {
    // Vue-cal main component options (props).
    options: { type: Object, default: () => ({}) },
    editEvents: { type: Object, required: true },
    data: { type: Object, required: true },
    cellSplits: { type: Array, default: () => [] },
    minTimestamp: { type: [Number, null], default: null },
    maxTimestamp: { type: [Number, null], default: null },
    cellWidth: { type: [Number, Boolean], default: false },
    allDay: { type: Boolean, default: false }
  },
  data: () => ({
    cellOverlaps: {},
    cellOverlapsStreak: 1,
    // Largest amount of simultaneous events in cell.
    // On mouse down, save the time at cursor so it can be reused on cell focus event
    // where there is no cursor coords.
    timeAtCursor: null,
    highlighted: false,
    // On event drag over.
    highlightedSplit: null
  }),
  methods: {
    getSplitAtCursor({ target }) {
      const targetIsSplit = target.classList.contains("vuecal__cell-split");
      let split = targetIsSplit ? target : this.vuecal.findAncestor(target, "vuecal__cell-split");
      if (split) {
        split = split.attributes["data-split"].value;
        if (parseInt(split).toString() === split.toString()) split = parseInt(split);
      }
      return split || null;
    },
    splitClasses(split) {
      return {
        "vuecal__cell-split": true,
        "vuecal__cell-split--highlighted": this.highlightedSplit === split.id,
        [split.class]: !!split.class
      };
    },
    checkCellOverlappingEvents() {
      if (this.options.time && this.eventsCount && !this.splitsCount) {
        if (this.eventsCount === 1) {
          this.cellOverlaps = [];
          this.cellOverlapsStreak = 1;
        } else [this.cellOverlaps, this.cellOverlapsStreak] = this.utils.event.checkCellOverlappingEvents(this.events, this.options);
      }
    },
    isDOMElementAnEvent(el) {
      return this.vuecal.isDOMElementAnEvent(el);
    },
    /**
     * Select a cell and go to narrower view on double click or single click according to vuecalProps option.
     */
    selectCell(DOMEvent, force = false) {
      const split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
      this.utils.cell.selectCell(force, this.timeAtCursor, split);
      this.timeAtCursor = null;
    },
    onCellkeyPressEnter(DOMEvent) {
      if (!this.isSelected) this.onCellFocus(DOMEvent);
      const split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
      this.utils.cell.keyPressEnterCell(this.timeAtCursor, split);
      this.timeAtCursor = null;
    },
    /**
     * On cell focus, from tab key or from mousedown, highlight the cell and emit
     * the cell-focus event with the date of the cell start when focusing from tab or
     * the date & time at cursor if click/touch.
     */
    onCellFocus(DOMEvent) {
      if (!this.isSelected && !this.isDisabled) {
        this.isSelected = this.data.startDate;
        const split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
        const date = this.timeAtCursor || this.data.startDate;
        this.vuecal.$emit("cell-focus", split ? { date, split } : date);
      }
    },
    onCellMouseDown(DOMEvent, split = null, touch = false) {
      if ("ontouchstart" in window && !touch) return false;
      if (!this.isSelected) this.onCellFocus(DOMEvent);
      const { clickHoldACell, focusAnEvent } = this.domEvents;
      this.domEvents.cancelClickEventCreation = false;
      clickHoldACell.eventCreated = false;
      this.timeAtCursor = new Date(this.data.startDate);
      const { minutes, cursorCoords: { y } } = this.vuecal.minutesAtCursor(DOMEvent);
      this.timeAtCursor.setMinutes(minutes);
      const mouseDownOnEvent = this.isDOMElementAnEvent(DOMEvent.target);
      if (!mouseDownOnEvent && focusAnEvent._eid) {
        (this.view.events.find((e) => e._eid === focusAnEvent._eid) || {}).focused = false;
      }
      if (this.editEvents.create && !mouseDownOnEvent) this.setUpEventCreation(DOMEvent, y);
    },
    setUpEventCreation(DOMEvent, startCursorY) {
      if (this.options.dragToCreateEvent && ["week", "day"].includes(this.view.id)) {
        const { dragCreateAnEvent } = this.domEvents;
        dragCreateAnEvent.startCursorY = startCursorY;
        dragCreateAnEvent.split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
        dragCreateAnEvent.start = this.timeAtCursor;
        if (this.options.snapToTime) {
          let timeMinutes = this.timeAtCursor.getHours() * 60 + this.timeAtCursor.getMinutes();
          const plusHalfSnapTime = timeMinutes + this.options.snapToTime / 2;
          timeMinutes = plusHalfSnapTime - plusHalfSnapTime % this.options.snapToTime;
          dragCreateAnEvent.start.setHours(0, timeMinutes, 0, 0);
        }
      } else if (this.options.cellClickHold && ["month", "week", "day"].includes(this.view.id)) {
        this.setUpCellHoldTimer(DOMEvent);
      }
    },
    // When click & holding a cell, and if allowed, set a timeout to create an event (can be cancelled).
    setUpCellHoldTimer(DOMEvent) {
      const { clickHoldACell } = this.domEvents;
      clickHoldACell.cellId = `${this.vuecal._.uid}_${this.data.formattedDate}`;
      clickHoldACell.split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
      clickHoldACell.timeoutId = setTimeout(() => {
        if (clickHoldACell.cellId && !this.domEvents.cancelClickEventCreation) {
          const { _eid } = this.utils.event.createAnEvent(
            this.timeAtCursor,
            null,
            clickHoldACell.split ? { split: clickHoldACell.split } : {}
          );
          clickHoldACell.eventCreated = _eid;
        }
      }, clickHoldACell.timeout);
    },
    onCellTouchStart(DOMEvent, split = null) {
      this.onCellMouseDown(DOMEvent, split, true);
    },
    onCellClick(DOMEvent) {
      if (!this.isDOMElementAnEvent(DOMEvent.target)) this.selectCell(DOMEvent);
    },
    onCellDblClick(DOMEvent) {
      const date = new Date(this.data.startDate);
      date.setMinutes(this.vuecal.minutesAtCursor(DOMEvent).minutes);
      const split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
      this.vuecal.$emit("cell-dblclick", split ? { date, split } : date);
      if (this.options.dblclickToNavigate) this.vuecal.switchToNarrowerView();
    },
    onCellContextMenu(DOMEvent) {
      DOMEvent.stopPropagation();
      DOMEvent.preventDefault();
      const date = new Date(this.data.startDate);
      const { cursorCoords, minutes } = this.vuecal.minutesAtCursor(DOMEvent);
      date.setMinutes(minutes);
      const split = this.splitsCount ? this.getSplitAtCursor(DOMEvent) : null;
      this.vuecal.$emit("cell-contextmenu", { date, ...cursorCoords, ...split || {}, e: DOMEvent });
    }
  },
  computed: {
    // Drag & drop module.
    dnd() {
      return this.modules.dnd;
    },
    nowInMinutes() {
      return this.utils.date.dateToMinutes(this.vuecal.now);
    },
    isBeforeMinDate() {
      return this.minTimestamp !== null && this.minTimestamp > this.data.endDate.getTime();
    },
    isAfterMaxDate() {
      return this.maxTimestamp && this.maxTimestamp < this.data.startDate.getTime();
    },
    // Is the current cell disabled or not.
    isDisabled() {
      const { disableDays } = this.options;
      const { isYearsOrYearView } = this.vuecal;
      if (disableDays.length && disableDays.includes(this.data.formattedDate) && !isYearsOrYearView) return true;
      return this.isBeforeMinDate || this.isAfterMaxDate;
    },
    // Is the current cell selected or not.
    isSelected: {
      get() {
        let selected = false;
        const { selectedDate } = this.view;
        if (this.view.id === "years") {
          selected = selectedDate.getFullYear() === this.data.startDate.getFullYear();
        } else if (this.view.id === "year") {
          selected = selectedDate.getFullYear() === this.data.startDate.getFullYear() && selectedDate.getMonth() === this.data.startDate.getMonth();
        } else selected = selectedDate.getTime() === this.data.startDate.getTime();
        return selected;
      },
      set(date) {
        this.view.selectedDate = date;
        this.vuecal.$emit("update:selected-date", this.view.selectedDate);
      }
    },
    // Cache result for performance.
    isWeekOrDayView() {
      return ["week", "day"].includes(this.view.id);
    },
    transitionDirection() {
      return this.vuecal.transitionDirection;
    },
    specialHours() {
      return this.data.specialHours.map((block) => {
        let { from, to } = block;
        from = Math.max(from, this.options.timeFrom);
        to = Math.min(to, this.options.timeTo);
        return {
          ...block,
          height: (to - from) * this.timeScale,
          top: (from - this.options.timeFrom) * this.timeScale
        };
      });
    },
    events() {
      const { startDate: cellStart, endDate: cellEnd } = this.data;
      let events = [];
      if (!(["years", "year"].includes(this.view.id) && !this.options.eventsCountOnYearView)) {
        events = this.view.events.slice(0);
        if (this.view.id === "month") {
          events.push(...this.view.outOfScopeEvents);
        }
        events = events.filter((e) => this.utils.event.eventInRange(e, cellStart, cellEnd));
        if (this.options.showAllDayEvents && this.view.id !== "month") events = events.filter((e) => !!e.allDay === this.allDay);
        if (this.options.time && this.isWeekOrDayView && !this.allDay) {
          const { timeFrom, timeTo } = this.options;
          events = events.filter((e) => {
            const segment = e.daysCount > 1 && e.segments[this.data.formattedDate] || {};
            const singleDayInRange = e.daysCount === 1 && e.startTimeMinutes < timeTo && e.endTimeMinutes > timeFrom;
            const multipleDayInRange = e.daysCount > 1 && (segment.startTimeMinutes < timeTo && segment.endTimeMinutes > timeFrom);
            const recurrMultDayInRange = false;
            return e.allDay || singleDayInRange || multipleDayInRange || recurrMultDayInRange;
          });
        }
        if (this.options.time && this.isWeekOrDayView && !(this.options.showAllDayEvents && this.allDay)) {
          events.sort((a, b) => a.start < b.start ? -1 : 1);
        }
        if (!this.cellSplits.length) this.$nextTick(this.checkCellOverlappingEvents);
      }
      return events;
    },
    eventsCount() {
      return this.events.length;
    },
    splits() {
      return this.cellSplits.map((item, i) => {
        const events = this.events.filter((e) => e.split === item.id);
        const [overlaps, streak] = this.utils.event.checkCellOverlappingEvents(events.filter((e) => !e.background && !e.allDay), this.options);
        return {
          ...item,
          overlaps,
          overlapsStreak: streak,
          events
        };
      });
    },
    splitsCount() {
      return this.splits.length;
    },
    cellClasses() {
      return {
        [this.data.class]: !!this.data.class,
        "vuecal__cell--current": this.data.current,
        // E.g. Current year in years view.
        "vuecal__cell--today": this.data.today,
        "vuecal__cell--out-of-scope": this.data.outOfScope,
        "vuecal__cell--before-min": this.isDisabled && this.isBeforeMinDate,
        "vuecal__cell--after-max": this.isDisabled && this.isAfterMaxDate,
        "vuecal__cell--disabled": this.isDisabled,
        "vuecal__cell--selected": this.isSelected,
        "vuecal__cell--highlighted": this.highlighted,
        "vuecal__cell--has-splits": this.splitsCount,
        "vuecal__cell--has-events": this.eventsCount
      };
    },
    cellStyles() {
      return {
        // cellWidth is only applied when hiding weekdays on month and week views.
        ...this.cellWidth ? { width: `${this.cellWidth}%` } : {}
      };
    },
    timelineVisible() {
      const { time, timeTo } = this.options;
      return this.data.today && this.isWeekOrDayView && time && !this.allDay && this.nowInMinutes <= timeTo;
    },
    todaysTimePosition() {
      if (!this.data.today || !this.options.time) return;
      const minutesFromTop = this.nowInMinutes - this.options.timeFrom;
      return Math.round(minutesFromTop * this.timeScale);
    },
    timeScale() {
      return this.options.timeCellHeight / this.options.timeStep;
    }
  }
};
const Cell = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", render$2]]);
const _hoisted_1$1 = {
  key: 0,
  class: "vuecal__all-day-text",
  style: { "width": "3em" }
};
function render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vuecal_cell = resolveComponent("vuecal-cell");
  return openBlock(), createElementBlock("div", {
    class: "vuecal__flex vuecal__all-day",
    style: normalizeStyle($props.cellOrSplitMinWidth && { height: $props.height })
  }, [
    !$props.cellOrSplitMinWidth ? (openBlock(), createElementBlock("div", _hoisted_1$1, [
      createElementVNode("span", null, toDisplayString($props.label), 1)
    ])) : createCommentVNode("", true),
    createElementVNode("div", {
      class: normalizeClass(["vuecal__flex vuecal__cells", `${$options.view.id}-view`]),
      grow: "",
      style: normalizeStyle($props.cellOrSplitMinWidth ? `min-width: ${$props.cellOrSplitMinWidth}px` : "")
    }, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.cells, (cell, i) => {
        return openBlock(), createBlock(_component_vuecal_cell, {
          key: i,
          options: $props.options,
          "edit-events": $options.editEvents,
          data: cell,
          "all-day": true,
          "cell-width": $props.options.hideWeekdays.length && ($options.vuecal.isWeekView || $options.vuecal.isMonthView) && $options.vuecal.cellWidth,
          "min-timestamp": $props.options.minTimestamp,
          "max-timestamp": $props.options.maxTimestamp,
          "cell-splits": $props.daySplits
        }, {
          event: withCtx(({ event, view }) => [
            renderSlot(_ctx.$slots, "event", {
              view,
              event
            })
          ]),
          _: 2
        }, 1032, ["options", "edit-events", "data", "cell-width", "min-timestamp", "max-timestamp", "cell-splits"]);
      }), 128))
    ], 6)
  ], 4);
}
const _sfc_main$1 = {
  inject: ["vuecal", "view", "editEvents"],
  components: { "vuecal-cell": Cell },
  props: {
    // Vue-cal main component options (props).
    options: { type: Object, required: true },
    cells: { type: Array, required: true },
    label: { type: String, required: true },
    daySplits: { type: Array, default: () => [] },
    shortEvents: { type: Boolean, default: true },
    height: { type: String, default: "" },
    cellOrSplitMinWidth: { type: Number, default: null }
  },
  computed: {
    hasCellOrSplitWidth() {
      return !!(this.options.minCellWidth || this.daySplits.length && this.options.minSplitWidth);
    }
  }
};
const AllDayBar = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", render$1]]);
const _hoisted_1 = ["lang"];
const _hoisted_2 = { class: "default" };
const _hoisted_3 = {
  key: 0,
  class: "vuecal__flex vuecal__body",
  grow: ""
};
const _hoisted_4 = ["onBlur", "innerHTML"];
const _hoisted_5 = ["innerHTML"];
const _hoisted_6 = ["innerHTML"];
const _hoisted_7 = {
  class: "vuecal__flex",
  row: "",
  grow: ""
};
const _hoisted_8 = {
  key: 0,
  class: "vuecal__time-column"
};
const _hoisted_9 = { class: "vuecal__time-cell-label" };
const _hoisted_10 = {
  key: 1,
  class: "vuecal__flex vuecal__week-numbers",
  column: ""
};
const _hoisted_11 = ["wrap", "column"];
const _hoisted_12 = ["onBlur", "innerHTML"];
const _hoisted_13 = ["innerHTML"];
const _hoisted_14 = ["innerHTML"];
const _hoisted_15 = ["wrap"];
const _hoisted_16 = ["innerHTML"];
const _hoisted_17 = ["innerHTML"];
const _hoisted_18 = {
  key: 2,
  class: "vuecal__cell-events-count"
};
const _hoisted_19 = {
  key: 3,
  class: "vuecal__no-event"
};
const _hoisted_20 = ["onBlur", "innerHTML"];
const _hoisted_21 = ["innerHTML"];
const _hoisted_22 = {
  key: 2,
  class: "vuecal__event-time"
};
const _hoisted_23 = { key: 0 };
const _hoisted_24 = {
  key: 1,
  class: "days-to-end"
};
const _hoisted_25 = ["innerHTML"];
const _hoisted_26 = {
  key: 0,
  class: "vuecal__scrollbar-check"
};
function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vuecal_header = resolveComponent("vuecal-header");
  const _component_all_day_bar = resolveComponent("all-day-bar");
  const _component_weekdays_headings = resolveComponent("weekdays-headings");
  const _component_vuecal_cell = resolveComponent("vuecal-cell");
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["vuecal__flex vuecal", $options.cssClasses]),
    column: "",
    ref: "vuecal",
    lang: $props.locale
  }, [
    createVNode(_component_vuecal_header, {
      options: _ctx.$props,
      "edit-events": $options.editEvents,
      "view-props": { views: $options.views, weekDaysInHeader: $options.weekDaysInHeader },
      "week-days": $options.weekDays,
      "has-splits": $options.hasSplits,
      "day-splits": $options.daySplits,
      "switch-to-narrower-view": $options.switchToNarrowerView
    }, createSlots({
      "arrow-prev": withCtx(() => [
        renderSlot(_ctx.$slots, "arrow-prev", {}, () => [
          _cache[0] || (_cache[0] = createTextVNode(" ")),
          _cache[1] || (_cache[1] = createElementVNode("i", { class: "angle" }, null, -1)),
          _cache[2] || (_cache[2] = createTextVNode(" "))
        ])
      ]),
      "arrow-next": withCtx(() => [
        renderSlot(_ctx.$slots, "arrow-next", {}, () => [
          _cache[3] || (_cache[3] = createTextVNode(" ")),
          _cache[4] || (_cache[4] = createElementVNode("i", { class: "angle" }, null, -1)),
          _cache[5] || (_cache[5] = createTextVNode(" "))
        ])
      ]),
      "today-button": withCtx(() => [
        renderSlot(_ctx.$slots, "today-button", {}, () => [
          createElementVNode("span", _hoisted_2, toDisplayString($data.texts.today), 1)
        ])
      ]),
      title: withCtx(() => [
        renderSlot(_ctx.$slots, "title", {
          title: $options.viewTitle,
          view: $data.view
        }, () => [
          createTextVNode(toDisplayString($options.viewTitle), 1)
        ])
      ]),
      _: 2
    }, [
      _ctx.$slots["weekday-heading"] ? {
        name: "weekday-heading",
        fn: withCtx(({ heading, view }) => [
          renderSlot(_ctx.$slots, "weekday-heading", {
            heading,
            view
          })
        ]),
        key: "0"
      } : void 0,
      _ctx.$slots["split-label"] ? {
        name: "split-label",
        fn: withCtx(({ split }) => [
          renderSlot(_ctx.$slots, "split-label", {
            split,
            view: $data.view.id
          })
        ]),
        key: "1"
      } : void 0
    ]), 1032, ["options", "edit-events", "view-props", "week-days", "has-splits", "day-splits", "switch-to-narrower-view"]),
    !$props.hideBody ? (openBlock(), createElementBlock("div", _hoisted_3, [
      createVNode(Transition, {
        name: `slide-fade--${$data.transitionDirection}`,
        appear: $props.transitions
      }, {
        default: withCtx(() => [
          (openBlock(), createElementBlock("div", {
            class: "vuecal__flex",
            style: { "min-width": "100%" },
            key: $props.transitions ? $data.view.id : false,
            column: ""
          }, [
            $props.showAllDayEvents && $options.hasTimeColumn && (!$options.cellOrSplitMinWidth || $options.isDayView && !$props.minSplitWidth) ? (openBlock(), createBlock(_component_all_day_bar, normalizeProps(mergeProps({ key: 0 }, $options.allDayBar)), {
              event: withCtx(({ event, view }) => [
                renderSlot(_ctx.$slots, "event", {
                  view,
                  event
                }, () => [
                  $options.editEvents.title && event.titleEditable ? (openBlock(), createElementBlock("div", {
                    key: 0,
                    class: "vuecal__event-title vuecal__event-title--edit",
                    contenteditable: "",
                    onBlur: ($event) => $options.onEventTitleBlur($event, event),
                    innerHTML: event.title
                  }, null, 40, _hoisted_4)) : event.title ? (openBlock(), createElementBlock("div", {
                    key: 1,
                    class: "vuecal__event-title",
                    innerHTML: event.title
                  }, null, 8, _hoisted_5)) : createCommentVNode("", true),
                  event.content && !$options.hasShortEvents && !$options.isShortMonthView ? (openBlock(), createElementBlock("div", {
                    key: 2,
                    class: "vuecal__event-content",
                    innerHTML: event.content
                  }, null, 8, _hoisted_6)) : createCommentVNode("", true)
                ])
              ]),
              _: 3
            }, 16)) : createCommentVNode("", true),
            createElementVNode("div", {
              class: normalizeClass(["vuecal__bg", { vuecal__flex: !$options.hasTimeColumn }]),
              column: ""
            }, [
              createElementVNode("div", _hoisted_7, [
                $options.hasTimeColumn ? (openBlock(), createElementBlock("div", _hoisted_8, [
                  $props.showAllDayEvents && $options.cellOrSplitMinWidth && !($options.isDayView && !$props.minSplitWidth) ? (openBlock(), createElementBlock("div", {
                    key: 0,
                    class: "vuecal__all-day-text",
                    style: normalizeStyle({ height: $options.allDayBar.height })
                  }, [
                    createElementVNode("span", null, toDisplayString($data.texts.allDay), 1)
                  ], 4)) : createCommentVNode("", true),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.timeCells, (cell, i) => {
                    return openBlock(), createElementBlock("div", {
                      class: "vuecal__time-cell",
                      key: i,
                      style: normalizeStyle(`height: ${$props.timeCellHeight}px`)
                    }, [
                      renderSlot(_ctx.$slots, "time-cell", {
                        hours: cell.hours,
                        minutes: cell.minutes
                      }, () => [
                        _cache[6] || (_cache[6] = createElementVNode("span", { class: "vuecal__time-cell-line" }, null, -1)),
                        createElementVNode("span", _hoisted_9, toDisplayString(cell.label), 1)
                      ])
                    ], 4);
                  }), 128))
                ])) : createCommentVNode("", true),
                $props.showWeekNumbers && $options.isMonthView ? (openBlock(), createElementBlock("div", _hoisted_10, [
                  (openBlock(), createElementBlock(Fragment, null, renderList(6, (i) => {
                    return createElementVNode("div", {
                      class: "vuecal__flex vuecal__week-number-cell",
                      key: i,
                      grow: ""
                    }, [
                      renderSlot(_ctx.$slots, "week-number-cell", {
                        week: $options.getWeekNumber(i - 1)
                      }, () => [
                        createTextVNode(toDisplayString($options.getWeekNumber(i - 1)), 1)
                      ])
                    ]);
                  }), 64))
                ])) : createCommentVNode("", true),
                createElementVNode("div", {
                  class: normalizeClass(["vuecal__flex vuecal__cells", `${$data.view.id}-view`]),
                  grow: "",
                  wrap: !$options.cellOrSplitMinWidth || !$options.isWeekView,
                  column: !!$options.cellOrSplitMinWidth
                }, [
                  $options.cellOrSplitMinWidth && $options.isWeekView ? (openBlock(), createBlock(_component_weekdays_headings, {
                    key: 0,
                    "transition-direction": $data.transitionDirection,
                    "week-days": $options.weekDays,
                    "switch-to-narrower-view": $options.switchToNarrowerView,
                    style: normalizeStyle($options.cellOrSplitMinWidth ? `min-width: ${$options.cellOrSplitMinWidth}px` : "")
                  }, createSlots({ _: 2 }, [
                    _ctx.$slots["weekday-heading"] ? {
                      name: "weekday-heading",
                      fn: withCtx(({ heading, view }) => [
                        renderSlot(_ctx.$slots, "weekday-heading", {
                          heading,
                          view
                        })
                      ]),
                      key: "0"
                    } : void 0,
                    _ctx.$slots["split-label"] ? {
                      name: "split-label",
                      fn: withCtx(({ split }) => [
                        renderSlot(_ctx.$slots, "split-label", {
                          split,
                          view: $data.view.id
                        })
                      ]),
                      key: "1"
                    } : void 0
                  ]), 1032, ["transition-direction", "week-days", "switch-to-narrower-view", "style"])) : $options.hasSplits && $props.stickySplitLabels && $props.minSplitWidth ? (openBlock(), createElementBlock("div", {
                    key: 1,
                    class: "vuecal__flex vuecal__split-days-headers",
                    style: normalizeStyle($options.cellOrSplitMinWidth ? `min-width: ${$options.cellOrSplitMinWidth}px` : "")
                  }, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($options.daySplits, (split, i) => {
                      return openBlock(), createElementBlock("div", {
                        class: normalizeClass(["day-split-header", split.class || false]),
                        key: i
                      }, [
                        renderSlot(_ctx.$slots, "split-label", {
                          split,
                          view: $data.view.id
                        }, () => [
                          createTextVNode(toDisplayString(split.label), 1)
                        ])
                      ], 2);
                    }), 128))
                  ], 4)) : createCommentVNode("", true),
                  $props.showAllDayEvents && $options.hasTimeColumn && ($options.isWeekView && $options.cellOrSplitMinWidth || $options.isDayView && $options.hasSplits && $props.minSplitWidth) ? (openBlock(), createBlock(_component_all_day_bar, normalizeProps(mergeProps({ key: 2 }, $options.allDayBar)), {
                    event: withCtx(({ event, view }) => [
                      renderSlot(_ctx.$slots, "event", {
                        view,
                        event
                      }, () => [
                        $options.editEvents.title && event.titleEditable ? (openBlock(), createElementBlock("div", {
                          key: 0,
                          class: "vuecal__event-title vuecal__event-title--edit",
                          contenteditable: "",
                          onBlur: ($event) => $options.onEventTitleBlur($event, event),
                          innerHTML: event.title
                        }, null, 40, _hoisted_12)) : event.title ? (openBlock(), createElementBlock("div", {
                          key: 1,
                          class: "vuecal__event-title",
                          innerHTML: event.title
                        }, null, 8, _hoisted_13)) : createCommentVNode("", true),
                        event.content && !$options.hasShortEvents && !$options.isShortMonthView ? (openBlock(), createElementBlock("div", {
                          key: 2,
                          class: "vuecal__event-content",
                          innerHTML: event.content
                        }, null, 8, _hoisted_14)) : createCommentVNode("", true)
                      ])
                    ]),
                    _: 3
                  }, 16)) : createCommentVNode("", true),
                  createElementVNode("div", {
                    class: "vuecal__flex",
                    ref: (el) => $data.cellsEl = el,
                    grow: "",
                    wrap: !$options.cellOrSplitMinWidth || !$options.isWeekView,
                    style: normalizeStyle($options.cellOrSplitMinWidth ? `min-width: ${$options.cellOrSplitMinWidth}px` : "")
                  }, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($options.viewCells, (cell, i) => {
                      return openBlock(), createBlock(_component_vuecal_cell, {
                        key: i,
                        options: _ctx.$props,
                        "edit-events": $options.editEvents,
                        data: cell,
                        "cell-width": $props.hideWeekdays.length && ($options.isWeekView || $options.isMonthView) && $options.cellWidth,
                        "min-timestamp": $options.minTimestamp,
                        "max-timestamp": $options.maxTimestamp,
                        "cell-splits": $options.hasSplits && $options.daySplits || []
                      }, {
                        "cell-content": withCtx(({ events, split, selectCell }) => [
                          renderSlot(_ctx.$slots, "cell-content", {
                            cell,
                            view: $data.view,
                            goNarrower: selectCell,
                            events
                          }, () => [
                            split && !$props.stickySplitLabels ? (openBlock(), createElementBlock("div", {
                              key: 0,
                              class: "split-label",
                              innerHTML: split.label
                            }, null, 8, _hoisted_16)) : createCommentVNode("", true),
                            cell.content ? (openBlock(), createElementBlock("div", {
                              key: 1,
                              class: "vuecal__cell-date",
                              innerHTML: cell.content
                            }, null, 8, _hoisted_17)) : createCommentVNode("", true),
                            ($options.isMonthView && !$props.eventsOnMonthView || $options.isYearsOrYearView && $props.eventsCountOnYearView) && events.length ? (openBlock(), createElementBlock("div", _hoisted_18, [
                              renderSlot(_ctx.$slots, "events-count", {
                                view: $data.view,
                                events
                              }, () => [
                                createTextVNode(toDisplayString(events.length), 1)
                              ])
                            ])) : createCommentVNode("", true),
                            !$options.cellOrSplitHasEvents(events, split) && $options.isWeekOrDayView ? (openBlock(), createElementBlock("div", _hoisted_19, [
                              renderSlot(_ctx.$slots, "no-event", {}, () => [
                                createTextVNode(toDisplayString($data.texts.noEvent), 1)
                              ])
                            ])) : createCommentVNode("", true)
                          ])
                        ]),
                        event: withCtx(({ event, view }) => [
                          renderSlot(_ctx.$slots, "event", {
                            view,
                            event
                          }, () => [
                            $options.editEvents.title && event.titleEditable ? (openBlock(), createElementBlock("div", {
                              key: 0,
                              class: "vuecal__event-title vuecal__event-title--edit",
                              contenteditable: "",
                              onBlur: ($event) => $options.onEventTitleBlur($event, event),
                              innerHTML: event.title
                            }, null, 40, _hoisted_20)) : event.title ? (openBlock(), createElementBlock("div", {
                              key: 1,
                              class: "vuecal__event-title",
                              innerHTML: event.title
                            }, null, 8, _hoisted_21)) : createCommentVNode("", true),
                            $props.time && !event.allDay && !($options.isMonthView && (event.allDay || $props.showAllDayEvents === "short")) && !$options.isShortMonthView ? (openBlock(), createElementBlock("div", _hoisted_22, [
                              createTextVNode(toDisplayString($data.utils.date.formatTime(event.start, $options.TimeFormat)), 1),
                              event.endTimeMinutes ? (openBlock(), createElementBlock("span", _hoisted_23, " - " + toDisplayString($data.utils.date.formatTime(event.end, $options.TimeFormat, null, true)), 1)) : createCommentVNode("", true),
                              event.daysCount > 1 && (event.segments[cell.formattedDate] || {}).isFirstDay ? (openBlock(), createElementBlock("small", _hoisted_24, " +" + toDisplayString(event.daysCount - 1) + toDisplayString(($data.texts.day[0] || "").toLowerCase()), 1)) : createCommentVNode("", true)
                            ])) : createCommentVNode("", true),
                            event.content && !($options.isMonthView && event.allDay && $props.showAllDayEvents === "short") && !$options.isShortMonthView ? (openBlock(), createElementBlock("div", {
                              key: 3,
                              class: "vuecal__event-content",
                              innerHTML: event.content
                            }, null, 8, _hoisted_25)) : createCommentVNode("", true)
                          ])
                        ]),
                        "no-event": withCtx(() => [
                          renderSlot(_ctx.$slots, "no-event", {}, () => [
                            createTextVNode(toDisplayString($data.texts.noEvent), 1)
                          ])
                        ]),
                        _: 2
                      }, 1032, ["options", "edit-events", "data", "cell-width", "min-timestamp", "max-timestamp", "cell-splits"]);
                    }), 128))
                  ], 12, _hoisted_15)
                ], 10, _hoisted_11)
              ])
            ], 2)
          ]))
        ]),
        _: 3
      }, 8, ["name", "appear"]),
      !$data.ready ? (openBlock(), createElementBlock("div", _hoisted_26, _cache[7] || (_cache[7] = [
        createElementVNode("div", null, null, -1)
      ]))) : createCommentVNode("", true)
    ])) : createCommentVNode("", true)
  ], 10, _hoisted_1);
}
const minutesInADay = 24 * 60;
const textsDefaults = {
  weekDays: Array(7).fill(""),
  weekDaysShort: [],
  months: Array(12).fill(""),
  years: "",
  year: "",
  month: "",
  week: "",
  day: "",
  today: "",
  noEvent: "",
  allDay: "",
  deleteEvent: "",
  createEvent: "",
  dateFormat: "dddd MMMM D, YYYY",
  am: "am",
  pm: "pm"
};
const validViews = ["years", "year", "month", "week", "day"];
const dateUtils = new DateUtils(textsDefaults);
const _sfc_main = {
  name: "vue-cal",
  components: { "vuecal-cell": Cell, "vuecal-header": Header, WeekdaysHeadings, AllDayBar },
  // By Vue design, passing props loses the reactivity unless it's a method or reactive OBJECT.
  provide() {
    return {
      vuecal: this,
      utils: this.utils,
      modules: this.modules,
      // Methods.
      previous: this.previous,
      next: this.next,
      switchView: this.switchView,
      updateSelectedDate: this.updateSelectedDate,
      editEvents: this.editEvents,
      // Objects.
      view: this.view,
      domEvents: this.domEvents
    };
  },
  props: {
    activeView: { type: String, default: "week" },
    // Only used if there are daySplits with minSplitWidth, to add the same height top spacer on time column.
    allDayBarHeight: { type: [String, Number], default: "25px" },
    cellClickHold: { type: Boolean, default: true },
    cellContextmenu: { type: Boolean, default: false },
    clickToNavigate: { type: Boolean, default: false },
    dblclickToNavigate: { type: Boolean, default: true },
    disableDatePrototypes: { type: Boolean, default: false },
    disableDays: { type: Array, default: () => [] },
    disableViews: { type: Array, default: () => [] },
    dragToCreateEvent: { type: Boolean, default: true },
    // Start a drag creation after dragging a certain amount of pixels.
    // This prevents drag creation by mistake when you want to navigate.
    dragToCreateThreshold: { type: Number, default: 15 },
    editableEvents: { type: [Boolean, Object], default: false },
    events: { type: Array, default: () => [] },
    eventsCountOnYearView: { type: Boolean, default: false },
    eventsOnMonthView: { type: [Boolean, String], default: false },
    hideBody: { type: Boolean, default: false },
    hideTitleBar: { type: Boolean, default: false },
    hideViewSelector: { type: Boolean, default: false },
    hideWeekdays: { type: Array, default: () => [] },
    hideWeekends: { type: Boolean, default: false },
    locale: { type: [String, Object], default: "en" },
    maxDate: { type: [String, Date], default: "" },
    minCellWidth: { type: Number, default: 0 },
    minDate: { type: [String, Date], default: "" },
    minEventWidth: { type: Number, default: 0 },
    minSplitWidth: { type: Number, default: 0 },
    onEventClick: { type: [Function, null], default: null },
    onEventCreate: { type: [Function, null], default: null },
    onEventDblclick: { type: [Function, null], default: null },
    overlapsPerTimeStep: { type: Boolean, default: false },
    resizeX: { type: Boolean, default: false },
    selectedDate: { type: [String, Date], default: "" },
    showAllDayEvents: { type: [Boolean, String], default: false },
    showTimeInCells: { type: Boolean, default: false },
    showWeekNumbers: { type: [Boolean, String], default: false },
    snapToTime: { type: Number, default: 0 },
    small: { type: Boolean, default: false },
    specialHours: { type: Object, default: () => ({}) },
    splitDays: { type: Array, default: () => [] },
    startWeekOnSunday: { type: Boolean, default: false },
    stickySplitLabels: { type: Boolean, default: false },
    time: { type: Boolean, default: true },
    timeCellHeight: { type: Number, default: 40 },
    // In pixels.
    timeFormat: { type: String, default: "" },
    timeFrom: { type: Number, default: 0 },
    // In minutes.
    timeStep: { type: Number, default: 60 },
    // In minutes.
    timeTo: { type: Number, default: minutesInADay },
    // In minutes.
    todayButton: { type: Boolean, default: false },
    transitions: { type: Boolean, default: true },
    twelveHour: { type: Boolean, default: false },
    watchRealTime: { type: Boolean, default: false },
    // Expensive, so only trigger on demand.
    xsmall: { type: Boolean, default: false }
  },
  data() {
    return {
      ready: false,
      // Is vue-cal ready.
      // Make texts reactive before a locale is loaded.
      texts: { ...textsDefaults },
      utils: {
        // Remove prototypes ASAP if the user wants so.
        date: (this.disableDatePrototypes ? dateUtils.removePrototypes() : false) || dateUtils,
        cell: null,
        // Note: Destructuring class method loses the `this` context and Vue Cal becomes inaccessible
        // from the event utils function. Don't do:
        // const { eventInRange, createEventSegments } = this.utils.event
        event: null
      },
      modules: { dnd: null },
      cellsEl: null,
      // At any time this object will be filled with current view, visible events and selected date.
      view: {
        id: "",
        title: "",
        startDate: null,
        endDate: null,
        firstCellDate: null,
        lastCellDate: null,
        selectedDate: null,
        // All the events are stored in the mutableEvents array, but subset of visible ones are passed
        // Into the current view for fast lookup and manipulation.
        events: []
      },
      eventIdIncrement: 1,
      // Internal unique id.
      // Preset at now date on load, but updated every minute if watchRealTime,
      // or updated at least on each cells rerender, in order to keep Today's date accurate.
      now: /* @__PURE__ */ new Date(),
      // Useful when watchRealTime = true, 2 timeouts: 1 to snap to round minutes, then 1 every minute.
      timeTickerIds: [null, null],
      // All the possible events/cells interractions:
      // e.g. focus, click, click & hold, resize, drag & drop (coming).
      domEvents: {
        resizeAnEvent: {
          _eid: null,
          // Only one at a time.
          start: null,
          split: null,
          segment: null,
          originalEndTimeMinutes: 0,
          originalEnd: null,
          end: null,
          startCell: null,
          endCell: null
        },
        dragAnEvent: {
          // Only one at a time, only needed for vuecal dragging-event class.
          _eid: null
        },
        dragCreateAnEvent: {
          startCursorY: null,
          start: null,
          // The cell date where we start the drag.
          split: null,
          event: null
        },
        focusAnEvent: {
          _eid: null,
          // Only one at a time.
          // Useful to detect a full click (mousedown + mouseup on same event).
          // E.g. Only call onEventClick function (if any) on full click.
          mousedUp: false
        },
        clickHoldAnEvent: {
          _eid: null,
          // Only one at a time.
          timeout: 1200,
          // Hold for 1.2s to delete an event.
          timeoutId: null
        },
        dblTapACell: {
          taps: 0,
          timeout: 500
          // Allowed latency between first and second click.
        },
        clickHoldACell: {
          cellId: null,
          split: null,
          timeout: 1200,
          // Hold for 1.2s to create an event.
          timeoutId: null,
          eventCreated: false
        },
        // A single click can trigger event creation if the user decides so.
        // But prevent this to happen on click & hold, on event click and on resize event.
        cancelClickEventCreation: false
      },
      // The events source of truth.
      // An array of mutable events updated each time given external events array changes.
      mutableEvents: [],
      // Transition when switching view. left when going toward the past, right when going toward future.
      transitionDirection: "right"
    };
  },
  methods: {
    /**
     * Only import locale on demand to keep a small library weight.
     *
     * @param {String|Object} locale the language user whishes to have on vue-cal.
     */
    async loadLocale(locale) {
      if (typeof this.locale === "object") {
        this.texts = Object.assign({}, textsDefaults, locale);
        this.utils.date.updateTexts(this.texts);
        return;
      }
      const texts = await __variableDynamicImportRuntimeHelper(/* @__PURE__ */ Object.assign({ "./i18n/ar.json": () => import("./i18n/ar.es.js"), "./i18n/bg.json": () => import("./i18n/bg.es.js"), "./i18n/bn.json": () => import("./i18n/bn.es.js"), "./i18n/bs.json": () => import("./i18n/bs.es.js"), "./i18n/ca.json": () => import("./i18n/ca.es.js"), "./i18n/cs.json": () => import("./i18n/cs.es.js"), "./i18n/da.json": () => import("./i18n/da.es.js"), "./i18n/de.json": () => import("./i18n/de.es.js"), "./i18n/el.json": () => import("./i18n/el.es.js"), "./i18n/en.json": () => import("./i18n/en.es.js"), "./i18n/es.json": () => import("./i18n/es.es.js"), "./i18n/et.json": () => import("./i18n/et.es.js"), "./i18n/fa.json": () => import("./i18n/fa.es.js"), "./i18n/fi.json": () => import("./i18n/fi.es.js"), "./i18n/fr.json": () => import("./i18n/fr.es.js"), "./i18n/he.json": () => import("./i18n/he.es.js"), "./i18n/hr.json": () => import("./i18n/hr.es.js"), "./i18n/hu.json": () => import("./i18n/hu.es.js"), "./i18n/id.json": () => import("./i18n/id.es.js"), "./i18n/is.json": () => import("./i18n/is.es.js"), "./i18n/it.json": () => import("./i18n/it.es.js"), "./i18n/ja.json": () => import("./i18n/ja.es.js"), "./i18n/ka.json": () => import("./i18n/ka.es.js"), "./i18n/ko.json": () => import("./i18n/ko.es.js"), "./i18n/lt.json": () => import("./i18n/lt.es.js"), "./i18n/mn.json": () => import("./i18n/mn.es.js"), "./i18n/nl.json": () => import("./i18n/nl.es.js"), "./i18n/no.json": () => import("./i18n/no.es.js"), "./i18n/pl.json": () => import("./i18n/pl.es.js"), "./i18n/pt-br.json": () => import("./i18n/pt-br.es.js"), "./i18n/pt-pt.json": () => import("./i18n/pt-pt.es.js"), "./i18n/ro.json": () => import("./i18n/ro.es.js"), "./i18n/ru.json": () => import("./i18n/ru.es.js"), "./i18n/sk.json": () => import("./i18n/sk.es.js"), "./i18n/sl.json": () => import("./i18n/sl.es.js"), "./i18n/sq.json": () => import("./i18n/sq.es.js"), "./i18n/sr.json": () => import("./i18n/sr.es.js"), "./i18n/sv.json": () => import("./i18n/sv.es.js"), "./i18n/tr.json": () => import("./i18n/tr.es.js"), "./i18n/uk.json": () => import("./i18n/uk.es.js"), "./i18n/vi.json": () => import("./i18n/vi.es.js"), "./i18n/zh-cn.json": () => import("./i18n/zh-cn.es.js"), "./i18n/zh-hk.json": () => import("./i18n/zh-hk.es.js") }), `./i18n/${locale}.json`, 3);
      this.texts = Object.assign({}, textsDefaults, texts);
      this.utils.date.updateTexts(this.texts);
    },
    /**
     * Only import drag and drop module on demand to keep a small library weight.
     */
    loadDragAndDrop() {
      import("./drag-and-drop.es.js").then((response) => {
        const { DragAndDrop } = response;
        this.modules.dnd = new DragAndDrop(this);
      }).catch(() => console.warn("Vue Cal: Missing drag & drop module."));
    },
    /**
     * Checks that the given view is in the array of valid views or use 'week' otherwise.
     * Then check the view is enabled or use the first enabled view instead.
     * Raises error and warning if needed.
     *
     * @param {String} view The view to validate.
     * @return {String} a valid view.
     */
    validateView(view) {
      if (!validViews.includes(view)) {
        console.error(`Vue Cal: invalid active-view parameter provided: "${view}".
A valid view must be one of: ${validViews.join(", ")}.`);
        view = "week";
      }
      if (!this.enabledViews.includes(view)) {
        console.warn(`Vue Cal: the provided active-view "${view}" is disabled. Using the "${this.enabledViews[0]}" view instead.`);
        view = this.enabledViews[0];
      }
      return view;
    },
    /**
     * On click/dblclick of a cell go to a narrower view if available.
     * E.g. Click on month cell takes you to week view if not hidden, otherwise on day view if not hidden.
     *
     * @param {String | Date} date A starting date for the view, if none, fallbacks to the selected date,
     *                             If also empty fallbacks to the current view start date.
     */
    switchToNarrowerView(date = null) {
      this.transitionDirection = "right";
      const view = this.enabledViews[this.enabledViews.indexOf(this.view.id) + 1];
      if (view) this.switchView(view, date);
    },
    /**
     * Switches to the specified view on view selector click, or programmatically form external call (via $refs).
     * If a date is given, it will be selected and if the view does not contain it, it will go to that date.
     *
     * @param {String} view the view to go to. Among `years`, `year`, `month`, `week`, `day`.
     * @param {String | Date} date A starting date for the view, if none, fallbacks to the selected date,
     *                             If also empty fallbacks to the current view start date.
     * @param {Boolean} fromViewSelector to know if the caller is the built-in view selector.
     */
    switchView(view, date = null, fromViewSelector = false) {
      view = this.validateView(view);
      const ud2 = this.utils.date;
      const viewDateBeforeChange = this.view.startDate && this.view.startDate.getTime();
      if (this.transitions && fromViewSelector) {
        if (this.view.id === view) return;
        const views = this.enabledViews;
        this.transitionDirection = views.indexOf(this.view.id) > views.indexOf(view) ? "left" : "right";
      }
      const oldView = this.view.id;
      this.view.events = [];
      this.view.id = view;
      this.view.firstCellDate = null;
      this.view.lastCellDate = null;
      if (!date) date = this.view.selectedDate || this.view.startDate;
      switch (view) {
        case "years": {
          this.view.startDate = new Date(Math.floor(date.getFullYear() / 25) * 25 || 2e3, 0, 1);
          this.view.endDate = new Date(this.view.startDate.getFullYear() + 25, 0, 1);
          this.view.endDate.setSeconds(-1);
          break;
        }
        case "year": {
          this.view.startDate = new Date(date.getFullYear(), 0, 1);
          this.view.endDate = new Date(date.getFullYear() + 1, 0, 1);
          this.view.endDate.setSeconds(-1);
          break;
        }
        case "month": {
          this.view.startDate = new Date(date.getFullYear(), date.getMonth(), 1);
          this.view.endDate = new Date(date.getFullYear(), date.getMonth() + 1, 1);
          this.view.endDate.setSeconds(-1);
          let startDate = new Date(this.view.startDate);
          if (startDate.getDay() !== (this.startWeekOnSunday ? 0 : 1)) {
            startDate = ud2.getPreviousFirstDayOfWeek(startDate, this.startWeekOnSunday);
          }
          this.view.firstCellDate = startDate;
          this.view.lastCellDate = ud2.addDays(startDate, 41);
          this.view.lastCellDate.setHours(23, 59, 59, 0);
          if (this.hideWeekends) {
            if ([0, 6].includes(this.view.firstCellDate.getDay())) {
              const daysToAdd = this.view.firstCellDate.getDay() === 6 && !this.startWeekOnSunday ? 2 : 1;
              this.view.firstCellDate = ud2.addDays(this.view.firstCellDate, daysToAdd);
            }
            if ([0, 6].includes(this.view.startDate.getDay())) {
              const daysToAdd = this.view.startDate.getDay() === 6 ? 2 : 1;
              this.view.startDate = ud2.addDays(this.view.startDate, daysToAdd);
            }
            if ([0, 6].includes(this.view.lastCellDate.getDay())) {
              const daysToSubtract = this.view.lastCellDate.getDay() === 0 && !this.startWeekOnSunday ? 2 : 1;
              this.view.lastCellDate = ud2.subtractDays(this.view.lastCellDate, daysToSubtract);
            }
            if ([0, 6].includes(this.view.endDate.getDay())) {
              const daysToSubtract = this.view.endDate.getDay() === 0 ? 2 : 1;
              this.view.endDate = ud2.subtractDays(this.view.endDate, daysToSubtract);
            }
          }
          break;
        }
        case "week": {
          date = ud2.getPreviousFirstDayOfWeek(date, this.startWeekOnSunday);
          const weekDaysCount = this.hideWeekends ? 5 : 7;
          this.view.startDate = this.hideWeekends && this.startWeekOnSunday ? ud2.addDays(date, 1) : date;
          this.view.startDate.setHours(0, 0, 0, 0);
          this.view.endDate = ud2.addDays(date, weekDaysCount);
          this.view.endDate.setSeconds(-1);
          break;
        }
        case "day": {
          this.view.startDate = date;
          this.view.startDate.setHours(0, 0, 0, 0);
          this.view.endDate = new Date(date);
          this.view.endDate.setHours(23, 59, 59, 0);
          break;
        }
      }
      this.addEventsToView();
      const viewDate = this.view.startDate && this.view.startDate.getTime();
      if (oldView === view && viewDate === viewDateBeforeChange) return;
      this.$emit("update:activeView", view);
      if (this.ready) {
        const startDate = this.view.startDate;
        const params = {
          view,
          startDate,
          endDate: this.view.endDate,
          ...this.isMonthView ? {
            firstCellDate: this.view.firstCellDate,
            lastCellDate: this.view.lastCellDate,
            outOfScopeEvents: this.view.outOfScopeEvents.map(this.cleanupEvent)
          } : {},
          events: this.view.events.map(this.cleanupEvent),
          ...this.isWeekView ? { week: ud2.getWeek(this.startWeekOnSunday ? ud2.addDays(startDate, 1) : startDate) } : {}
        };
        this.$emit("view-change", params);
      }
    },
    /**
     * Shorthand function for external call (via $refs).
     */
    previous() {
      this.previousNext(false);
    },
    /**
     * Shorthand function for external call (via $refs).
     */
    next() {
      this.previousNext();
    },
    /**
     * On click on previous or next arrow, update the calendar visible date range.
     *
     * @param {Boolean} next
     */
    previousNext(next = true) {
      const ud2 = this.utils.date;
      this.transitionDirection = next ? "right" : "left";
      const modifier = next ? 1 : -1;
      let firstCellDate = null;
      const { startDate, id: viewId } = this.view;
      switch (viewId) {
        case "years":
          firstCellDate = new Date(startDate.getFullYear() + 25 * modifier, 0, 1);
          break;
        case "year":
          firstCellDate = new Date(startDate.getFullYear() + 1 * modifier, 1, 1);
          break;
        case "month":
          firstCellDate = new Date(startDate.getFullYear(), startDate.getMonth() + 1 * modifier, 1);
          break;
        case "week":
          firstCellDate = ud2[next ? "addDays" : "subtractDays"](ud2.getPreviousFirstDayOfWeek(startDate, this.startWeekOnSunday), 7);
          break;
        case "day":
          firstCellDate = ud2[next ? "addDays" : "subtractDays"](startDate, 1);
          const weekDay = firstCellDate.getDay();
          const weekDayIndex = this.startWeekOnSunday ? weekDay : (weekDay || 7) - 1;
          const isDayHidden = this.weekDays[weekDayIndex].hide;
          if (isDayHidden) {
            const daysWithIndex = this.weekDays.map((day, i) => ({ ...day, i }));
            let daysFromDate = 0;
            if (next) {
              [...daysWithIndex.slice(weekDayIndex), ...daysWithIndex].find((day) => {
                daysFromDate++;
                return !day.hide;
              }).i;
              daysFromDate--;
            } else {
              [...daysWithIndex, ...daysWithIndex.slice(0, weekDayIndex)].reverse().find((day) => {
                daysFromDate++;
                return !day.hide;
              }).i;
            }
            firstCellDate = ud2[next ? "addDays" : "subtractDays"](firstCellDate, daysFromDate);
          }
          break;
      }
      if (firstCellDate) this.switchView(viewId, firstCellDate);
    },
    /**
     * Add events (subset from mutableEvents) to the current view (in `this.view.events`).
     * This is done for performance, so that all the cells have a quick lookup of only what's needed.
     *
     * @param {Array} events
     */
    addEventsToView(events = []) {
      const ue = this.utils.event;
      const { startDate, endDate, firstCellDate, lastCellDate } = this.view;
      if (!events.length) this.view.events = [];
      events = events.length ? events : [...this.mutableEvents];
      if (!events || this.isYearsOrYearView && !this.eventsCountOnYearView) return;
      let filteredEvents = events.filter((e) => ue.eventInRange(e, startDate, endDate));
      if (!this.isYearsOrYearView && !(this.isMonthView && !this.eventsOnMonthView)) {
        filteredEvents = filteredEvents.map((e) => {
          return e.daysCount > 1 ? ue.createEventSegments(e, firstCellDate || startDate, lastCellDate || endDate) : e;
        });
      }
      this.view.events.push(...filteredEvents);
      if (this.isMonthView) {
        this.view.outOfScopeEvents = [];
        events.forEach((e) => {
          if (ue.eventInRange(e, firstCellDate, startDate) || ue.eventInRange(e, endDate, lastCellDate)) {
            if (!this.view.events.some((e2) => e2._eid === e._eid)) this.view.outOfScopeEvents.push(e);
          }
        });
      }
    },
    /**
     * find a DOM ancestor of a given DOM node `el` matching given class name.
     *
     * @param {Object} el a DOM node to find ancestor from.
     * @param {String} Class the CSS class name of the ancestor.
     * @return {Object} The matched DOM node or null if no match.
     */
    findAncestor(el, Class) {
      while ((el = el.parentElement) && !el.classList.contains(Class)) {
      }
      return el;
    },
    /**
     * Tells whether a clicked DOM node is or is within a calendar event.
     *
     * @param {Object} el a DOM node to check if event.
     * @return {Boolean} true if the given DOM node is - or is in - an event.
     */
    isDOMElementAnEvent(el) {
      return el.classList.contains("vuecal__event") || this.findAncestor(el, "vuecal__event");
    },
    /**
     * Capture mousemove anywhere in the page.
     * If resizing an event was started earlier, this will update event end.
     * If resizing was not started, this method is calculation is avoided with a premature return.
     * Notes: Event resizing is started in cell component (onMouseDown) but place onMouseMove & onMouseUp
     *        handlers in the single-instance parent for performance.
     *
     * @param {Object} e the native DOM event object.
     */
    onMouseMove(e) {
      const { resizeAnEvent, dragAnEvent, dragCreateAnEvent } = this.domEvents;
      if (resizeAnEvent._eid === null && dragAnEvent._eid === null && !dragCreateAnEvent.start) return;
      e.preventDefault();
      if (resizeAnEvent._eid) this.eventResizing(e);
      else if (this.dragToCreateEvent && dragCreateAnEvent.start) this.eventDragCreation(e);
    },
    /**
     * Capture mouseup anywhere in the page, not only on a cell or event.
     * Then end up any resize, drag & drop, click & hold or event or cell.
     * Notes: Mouseup can never cancel a click with preventDefault or stopPropagation,
     *        But it always happens before the click event.
     *
     * @param {Object} e the native DOM event object.
     */
    onMouseUp(e) {
      const {
        focusAnEvent,
        resizeAnEvent,
        clickHoldAnEvent,
        clickHoldACell,
        dragCreateAnEvent
      } = this.domEvents;
      const { _eid: isClickHoldingEvent } = clickHoldAnEvent;
      const { _eid: wasResizing } = resizeAnEvent;
      let hasResized = false;
      const { event: dragCreatedEvent, start: dragCreateStarted } = dragCreateAnEvent;
      const mouseUpOnEvent = this.isDOMElementAnEvent(e.target);
      const eventClicked = focusAnEvent.mousedUp;
      focusAnEvent.mousedUp = false;
      if (mouseUpOnEvent) this.domEvents.cancelClickEventCreation = true;
      if (clickHoldACell.eventCreated) return;
      if (wasResizing) {
        const { originalEnd, originalEndTimeMinutes, endTimeMinutes } = resizeAnEvent;
        const event = this.view.events.find((e2) => e2._eid === resizeAnEvent._eid);
        hasResized = endTimeMinutes && endTimeMinutes !== originalEndTimeMinutes;
        if (event && event.end.getTime() !== originalEnd.getTime()) {
          const mutableEvent = this.mutableEvents.find((e2) => e2._eid === resizeAnEvent._eid);
          mutableEvent.endTimeMinutes = event.endTimeMinutes;
          mutableEvent.end = event.end;
          const cleanEvent = this.cleanupEvent(event);
          const originalEvent = {
            ...this.cleanupEvent(event),
            end: originalEnd,
            endTimeMinutes: event.originalEndTimeMinutes
          };
          this.$emit("event-duration-change", { event: cleanEvent, oldDate: resizeAnEvent.originalEnd, originalEvent });
          this.$emit("event-change", { event: cleanEvent, originalEvent });
        }
        if (event) event.resizing = false;
        resizeAnEvent._eid = null;
        resizeAnEvent.start = null;
        resizeAnEvent.split = null;
        resizeAnEvent.segment = null;
        resizeAnEvent.originalEndTimeMinutes = null;
        resizeAnEvent.originalEnd = null;
        resizeAnEvent.endTimeMinutes = null;
        resizeAnEvent.startCell = null;
        resizeAnEvent.endCell = null;
      } else if (dragCreateStarted) {
        if (dragCreatedEvent) {
          this.emitWithEvent("event-drag-create", dragCreatedEvent);
          dragCreateAnEvent.event.resizing = false;
        }
        dragCreateAnEvent.start = null;
        dragCreateAnEvent.split = null;
        dragCreateAnEvent.event = null;
      }
      if (!mouseUpOnEvent && !wasResizing) this.unfocusEvent();
      if (clickHoldAnEvent.timeoutId && !isClickHoldingEvent) {
        clearTimeout(clickHoldAnEvent.timeoutId);
        clickHoldAnEvent.timeoutId = null;
      }
      if (clickHoldACell.timeoutId) {
        clearTimeout(clickHoldACell.timeoutId);
        clickHoldACell.timeoutId = null;
      }
      const eventClickHandler = typeof this.onEventClick === "function";
      if (eventClicked && !hasResized && !isClickHoldingEvent && !dragCreatedEvent && eventClickHandler) {
        let event = this.view.events.find((e2) => e2._eid === focusAnEvent._eid);
        if (!event && this.isMonthView) event = this.view.outOfScopeEvents.find((e2) => e2._eid === focusAnEvent._eid);
        return event && this.onEventClick(event, e);
      }
    },
    /**
     * Capture `escape` keypress when delete button is visible, and cancel deletion.
     *
     * @param {Object} e the native DOM event object.
     */
    onKeyUp(e) {
      if (e.keyCode === 27) this.cancelDelete();
    },
    /**
     * On mousemove while resising an event.
     *
     * @param {Object} e the native DOM event object.
     */
    eventResizing(e) {
      const { resizeAnEvent } = this.domEvents;
      const event = this.view.events.find((e2) => e2._eid === resizeAnEvent._eid) || { segments: {} };
      const { minutes, cursorCoords } = this.minutesAtCursor(e);
      const segment = event.segments && event.segments[resizeAnEvent.segment];
      const { date: ud2, event: ue } = this.utils;
      const newEndTimeMins = Math.max(minutes, this.timeFrom + 1, (segment || event).startTimeMinutes + 1);
      event.endTimeMinutes = resizeAnEvent.endTimeMinutes = newEndTimeMins;
      if (this.snapToTime) {
        const plusHalfSnapTime = event.endTimeMinutes + this.snapToTime / 2;
        event.endTimeMinutes = plusHalfSnapTime - plusHalfSnapTime % this.snapToTime;
      }
      if (segment) segment.endTimeMinutes = event.endTimeMinutes;
      event.end.setHours(0, event.endTimeMinutes, event.endTimeMinutes === minutesInADay ? -1 : 0, 0);
      if (this.resizeX && this.isWeekView) {
        event.daysCount = ud2.countDays(event.start, event.end);
        const cells = this.cellsEl;
        const cellWidth = cells.offsetWidth / cells.childElementCount;
        const endCell = Math.floor(cursorCoords.x / cellWidth);
        if (resizeAnEvent.startCell === null) resizeAnEvent.startCell = endCell - (event.daysCount - 1);
        if (resizeAnEvent.endCell !== endCell) {
          resizeAnEvent.endCell = endCell;
          const newEnd = ud2.addDays(event.start, endCell - resizeAnEvent.startCell);
          const newDaysCount = Math.max(ud2.countDays(event.start, newEnd), 1);
          if (newDaysCount !== event.daysCount) {
            let lastSegmentFormattedDate = null;
            if (newDaysCount > event.daysCount) lastSegmentFormattedDate = ue.addEventSegment(event);
            else lastSegmentFormattedDate = ue.removeEventSegment(event);
            resizeAnEvent.segment = lastSegmentFormattedDate;
            event.endTimeMinutes += 1e-3;
          }
        }
      }
      this.$emit("event-resizing", { _eid: event._eid, end: event.end, endTimeMinutes: event.endTimeMinutes });
    },
    /**
     * On mousemove while dragging to create an event.
     *
     * @param {Object} e the native DOM event object.
     */
    eventDragCreation(e) {
      const { dragCreateAnEvent } = this.domEvents;
      const { start, startCursorY, split } = dragCreateAnEvent;
      const timeAtCursor = new Date(start);
      const { minutes, cursorCoords: { y } } = this.minutesAtCursor(e);
      if (!dragCreateAnEvent.event && Math.abs(startCursorY - y) < this.dragToCreateThreshold) return;
      if (!dragCreateAnEvent.event) {
        dragCreateAnEvent.event = this.utils.event.createAnEvent(start, 1, { split });
        if (!dragCreateAnEvent.event) {
          dragCreateAnEvent.start = null;
          dragCreateAnEvent.split = null;
          dragCreateAnEvent.event = null;
          return;
        }
        dragCreateAnEvent.event.resizing = true;
      } else {
        timeAtCursor.setHours(0, minutes, minutes === minutesInADay ? -1 : 0, 0);
        if (this.snapToTime) {
          let timeMinutes = timeAtCursor.getHours() * 60 + timeAtCursor.getMinutes();
          const plusHalfSnapTime = timeMinutes + this.snapToTime / 2;
          timeMinutes = plusHalfSnapTime - plusHalfSnapTime % this.snapToTime;
          timeAtCursor.setHours(0, timeMinutes, 0, 0);
        }
        const dragFromBottom = start < timeAtCursor;
        const { event } = dragCreateAnEvent;
        event.start = dragFromBottom ? start : timeAtCursor;
        event.end = dragFromBottom ? timeAtCursor : start;
        event.startTimeMinutes = event.start.getHours() * 60 + event.start.getMinutes();
        event.endTimeMinutes = event.end.getHours() * 60 + event.end.getMinutes();
      }
    },
    /**
     * Unfocus an event (e.g. when clicking outside of focused event).
     */
    unfocusEvent() {
      const { focusAnEvent, clickHoldAnEvent } = this.domEvents;
      const event = this.view.events.find((e) => e._eid === (focusAnEvent._eid || clickHoldAnEvent._eid));
      focusAnEvent._eid = null;
      clickHoldAnEvent._eid = null;
      if (event) {
        event.focused = false;
        event.deleting = false;
      }
    },
    /**
     * Cancel an event deletion (e.g. when clicking outside of visible delete button).
     */
    cancelDelete() {
      const { clickHoldAnEvent } = this.domEvents;
      if (clickHoldAnEvent._eid) {
        const event = this.view.events.find((e) => e._eid === clickHoldAnEvent._eid);
        if (event) event.deleting = false;
        clickHoldAnEvent._eid = null;
        clickHoldAnEvent.timeoutId = null;
      }
    },
    /**
     * After editing an event title (if `this.editable`), save the new string into the event object
     * and emit event to the outside world.
     *
     * @param {Object} e the native DOM event object.
     * @param {Object} event the vue-cal event object.
     */
    onEventTitleBlur(e, event) {
      if (event.title === e.target.innerHTML) return;
      const oldTitle = event.title;
      event.title = e.target.innerHTML;
      const cleanEvent = this.cleanupEvent(event);
      this.$emit("event-title-change", { event: cleanEvent, oldTitle });
      this.$emit("event-change", { event: cleanEvent, originalEvent: { ...cleanEvent, title: oldTitle } });
    },
    /**
     * The `mutableEvents` array of events is the source of truth.
     * It is first populated from the `events` prop and every time the `events` prop changes.
     * When the user updates an event through interractions, the event gets updated here.
     * Notes: mutableEvents couldn't be a computed variable based on this.events, because we add
     *        items to the array. (Cannot mutate props)
     */
    updateMutableEvents() {
      const ud2 = this.utils.date;
      this.mutableEvents = [];
      this.events.forEach((event) => {
        const start = typeof event.start === "string" ? ud2.stringToDate(event.start) : event.start;
        const startDateF = ud2.formatDateLite(start);
        const startTimeMinutes = ud2.dateToMinutes(start);
        let end = null;
        if (typeof event.end === "string" && event.end.includes("24:00")) {
          end = new Date(event.end.replace(" 24:00", ""));
          end.setHours(23, 59, 59, 0);
        } else end = typeof event.end === "string" ? ud2.stringToDate(event.end) : event.end;
        let endDateF = ud2.formatDateLite(end);
        let endTimeMinutes = ud2.dateToMinutes(end);
        if (!endTimeMinutes || endTimeMinutes === minutesInADay) {
          if (!this.time || typeof event.end === "string" && event.end.length === 10) {
            end.setHours(23, 59, 59, 0);
          } else end.setSeconds(end.getSeconds() - 1);
          endDateF = ud2.formatDateLite(end);
          endTimeMinutes = minutesInADay;
        }
        const multipleDays = startDateF !== endDateF;
        event = Object.assign({ ...this.utils.event.eventDefaults }, event, {
          // Keep the event ids scoped to this calendar instance.
          _eid: `${this._.uid}_${this.eventIdIncrement++}`,
          segments: multipleDays ? {} : null,
          start,
          startTimeMinutes,
          end,
          endTimeMinutes,
          daysCount: multipleDays ? ud2.countDays(start, end) : 1,
          class: event.class
        });
        this.mutableEvents.push(event);
      });
    },
    /**
     * Get the number of minutes from the top to the mouse cursor.
     *
     * @param {Object} e the native DOM event object.
     * @return {Object} containing { minutes: {Number}, cursorCoords: { x: {Number}, y: {Number} } }
     */
    minutesAtCursor(e) {
      return this.utils.cell.minutesAtCursor(e);
    },
    /**
     * Creates a new event in vue-cal memory (in the mutableEvents array) starting at the given date & time.
     * Proxy method to allow call from cell click & hold or external call (via $refs).
     * Notes: Event duration is by default 2 hours. You can override the event end through eventOptions.
     *
     * @param {String | Date} dateTime date & time at which the event will start.
     * @param {Number} duration the event duration in minutes.
     * @param {Object} eventOptions an object of options to override the event creation defaults.
     *                              (can be any key allowed in an event object)
     * @return {Object} the created event.
     */
    createEvent(dateTime, duration, eventOptions = {}) {
      return this.utils.event.createAnEvent(dateTime, duration, eventOptions);
    },
    /**
     * Remove all the vue-cal private vars from the event (before returning it through $emit()).
     *
     * @param {Object} event the event object to cleanup.
     */
    cleanupEvent(event) {
      event = { ...event };
      const discardProps = [
        "segments",
        "deletable",
        "deleting",
        "titleEditable",
        "resizable",
        "resizing",
        "draggable",
        "dragging",
        "draggingStatic",
        "focused"
      ];
      discardProps.forEach((prop) => {
        if (prop in event) delete event[prop];
      });
      if (!event.repeat) delete event.repeat;
      return event;
    },
    /**
     * Emits an event (custom DOM event) to the outside world.
     * This event has an event name and a clean calendar event as a parameter.
     *
     * @param {String} eventName the name of the custom emitted event (e.g. `event-focus`).
     * @param {Object} event the event to return to the outside world.
     */
    emitWithEvent(eventName, event) {
      this.$emit(eventName, this.cleanupEvent(event));
    },
    /**
     * Update the selected date:
     * - on created, from given selectedDate prop
     * - on click/dblClick of another cell
     * - from external call (via $refs)
     * - when the given selectedDate prop changes.
     * If date is not in the view, the view will change to show it.
     *
     * @param {String | Date} date The date to select.
     */
    updateSelectedDate(date) {
      if (date && typeof date === "string") date = this.utils.date.stringToDate(date);
      else date = new Date(date);
      if (date && date instanceof Date) {
        const { selectedDate } = this.view;
        if (selectedDate) this.transitionDirection = selectedDate.getTime() > date.getTime() ? "left" : "right";
        date.setHours(0, 0, 0, 0);
        if (!selectedDate || selectedDate.getTime() !== date.getTime()) this.view.selectedDate = date;
        this.switchView(this.view.id);
      }
      this.$emit("update:selected-date", this.view.selectedDate);
    },
    /**
     * Double checks the week number is correct. Read below to understand!
     * this is a wrapper around the `getWeek()` function for performance:
     * As this is called multiple times from the template and cannot be in computed since there is
     * a parameter, this wrapper function avoids the `getWeek()` function call 5 times out of 6
     * using the computed `firstCellDateWeekNumber`.
     *
     * Reason why:
     * Getting the week number is not that straightforward as there might be a 53rd week in the year.
     * Whenever the year starts on a Thursday or any leap year starting on a Wednesday, this week will be 53.
     *
     * @param {Number} weekFromFirstCell Number from 0 to 6.
     */
    getWeekNumber(weekFromFirstCell) {
      const ud2 = this.utils.date;
      const firstCellWeekNumber = this.firstCellDateWeekNumber;
      const currentWeekNumber = firstCellWeekNumber + weekFromFirstCell;
      const modifier = this.startWeekOnSunday ? 1 : 0;
      if (currentWeekNumber > 52) {
        return ud2.getWeek(ud2.addDays(this.view.firstCellDate, 7 * weekFromFirstCell + modifier));
      } else return currentWeekNumber;
    },
    /**
     * Only if watchRealTime is true.
     * Pull the current time from user machine every minute to keep vue-cal accurate even when idle.
     * This will redraw the now line every minute and ensure that Today's date is always accurate.
     */
    timeTick() {
      this.now = /* @__PURE__ */ new Date();
      this.timeTickerIds[1] = setTimeout(this.timeTick, 60 * 1e3);
    },
    /**
     * Updates the localized texts in use in the Date prototypes. (E.g. new Date().format())
     * Callable from outside of Vue Cal.
     */
    updateDateTexts() {
      this.utils.date.updateTexts(this.texts);
    },
    /**
     * On Windows devices, the .vuecal__bg's vertical scrollbar takes space and pushes the content.
     * This function will also push the weekdays-headings and all-day bar to have them properly aligned.
     * The calculated style will be placed in the document head in a style tag so it's only done once
     * (the scrollbar width never changes).
     * Ref. https://github.com/antoniandre/vue-cal-v4/issues/221
     */
    alignWithScrollbar() {
      if (document.getElementById("vuecal-align-with-scrollbar")) return;
      const bg = this.$refs.vuecal.getElementsByClassName("vuecal__scrollbar-check")[0];
      const scrollbarWidth = bg.offsetWidth - bg.children[0].offsetWidth;
      if (scrollbarWidth) {
        const style = document.createElement("style");
        style.id = "vuecal-align-with-scrollbar";
        style.type = "text/css";
        style.innerHTML = `.vuecal--view-with-time .vuecal__weekdays-headings,.vuecal--view-with-time .vuecal__all-day {padding-right: ${scrollbarWidth}px}`;
        document.head.appendChild(style);
      }
    },
    /**
     * Tells wether there are events in the given cell or split and returns a Boolean.
     * This function simplifies the template.
     *
     * @param {Array} events The cell events.
     * @param {Object|Boolean} split The current split object if any or false.
     * @return {Boolean} true if there are events, false otherwise.
     */
    cellOrSplitHasEvents(events, split = null) {
      return events.length && (!split && events.length || split && events.some((e) => e.split === split.id));
    }
  },
  created() {
    this.utils.cell = new CellUtils(this);
    this.utils.event = new EventUtils(this, this.utils.date);
    this.loadLocale(this.locale);
    if (this.editEvents.drag) this.loadDragAndDrop();
    this.updateMutableEvents(this.events);
    this.view.id = this.currentView;
    if (this.selectedDate) this.updateSelectedDate(this.selectedDate);
    else {
      this.view.selectedDate = /* @__PURE__ */ new Date();
      this.switchView(this.currentView);
    }
    if (this.time && this.watchRealTime) {
      this.timeTickerIds[0] = setTimeout(this.timeTick, (60 - this.now.getSeconds()) * 1e3);
    }
  },
  mounted() {
    const ud2 = this.utils.date;
    const hasTouch = "ontouchstart" in window;
    const { resize, drag, create, delete: deletable, title } = this.editEvents;
    const hasEventClickHandler = this.onEventClick && typeof this.onEventClick === "function";
    if (resize || drag || create || deletable || title || hasEventClickHandler) {
      window.addEventListener(hasTouch ? "touchend" : "mouseup", this.onMouseUp);
    }
    if (resize || drag || create && this.dragToCreateEvent) {
      window.addEventListener(hasTouch ? "touchmove" : "mousemove", this.onMouseMove, { passive: false });
    }
    if (title) window.addEventListener("keyup", this.onKeyUp);
    if (hasTouch) {
      this.$refs.vuecal.oncontextmenu = function(e) {
        e.preventDefault();
        e.stopPropagation();
      };
    }
    if (!this.hideBody) this.alignWithScrollbar();
    const startDate = this.view.startDate;
    const params = {
      view: this.view.id,
      startDate,
      endDate: this.view.endDate,
      ...this.isMonthView ? { firstCellDate: this.view.firstCellDate, lastCellDate: this.view.lastCellDate } : {},
      events: this.view.events.map(this.cleanupEvent),
      ...this.isWeekView ? { week: ud2.getWeek(this.startWeekOnSunday ? ud2.addDays(startDate, 1) : startDate) } : {}
    };
    this.$emit("ready", params);
    this.ready = true;
  },
  beforeUnmount() {
    const hasTouch = "ontouchstart" in window;
    window.removeEventListener(hasTouch ? "touchmove" : "mousemove", this.onMouseMove, { passive: false });
    window.removeEventListener(hasTouch ? "touchend" : "mouseup", this.onMouseUp);
    window.removeEventListener("keyup", this.onKeyUp);
    if (this.timeTickerIds[0]) clearTimeout(this.timeTickerIds[0]);
    if (this.timeTickerIds[1]) clearTimeout(this.timeTickerIds[1]);
    this.timeTickerIds = [null, null];
  },
  computed: {
    editEvents() {
      if (this.editableEvents && typeof this.editableEvents === "object") {
        return {
          title: !!this.editableEvents.title,
          drag: !!this.editableEvents.drag,
          resize: !!this.editableEvents.resize,
          create: !!this.editableEvents.create,
          delete: !!this.editableEvents.delete
        };
      }
      return {
        title: !!this.editableEvents,
        drag: !!this.editableEvents,
        resize: !!this.editableEvents,
        create: !!this.editableEvents,
        delete: !!this.editableEvents
      };
    },
    views() {
      return {
        years: { label: this.texts.years, enabled: !this.disableViews.includes("years") },
        year: { label: this.texts.year, enabled: !this.disableViews.includes("year") },
        month: { label: this.texts.month, enabled: !this.disableViews.includes("month") },
        week: { label: this.texts.week, enabled: !this.disableViews.includes("week") },
        day: { label: this.texts.day, enabled: !this.disableViews.includes("day") }
      };
    },
    currentView() {
      return this.validateView(this.activeView);
    },
    enabledViews() {
      return Object.keys(this.views).filter((view) => this.views[view].enabled);
    },
    hasTimeColumn() {
      return this.time && this.isWeekOrDayView;
    },
    isShortMonthView() {
      return this.isMonthView && this.eventsOnMonthView === "short";
    },
    firstCellDateWeekNumber() {
      const ud2 = this.utils.date;
      const date = this.view.firstCellDate;
      return ud2.getWeek(this.startWeekOnSunday ? ud2.addDays(date, 1) : date);
    },
    // For week & day views.
    timeCells() {
      const timeCells = [];
      for (let i = this.timeFrom, max = this.timeTo; i < max; i += this.timeStep) {
        timeCells.push({
          hours: Math.floor(i / 60),
          minutes: i % 60,
          label: this.utils.date.formatTime(i, this.TimeFormat),
          // The texts (3rd param) are given on Vue Cal init.
          value: i
        });
      }
      return timeCells;
    },
    TimeFormat() {
      return this.timeFormat || (this.twelveHour ? "h:mm{am}" : "HH:mm");
    },
    // Filter out the day splits that are hidden.
    daySplits() {
      return (this.splitDays.filter((item) => !item.hide) || []).map((item, i) => ({ ...item, id: item.id || i + 1 }));
    },
    // Whether the current view has days splits.
    hasSplits() {
      return this.daySplits.length && this.isWeekOrDayView;
    },
    hasShortEvents() {
      return this.showAllDayEvents === "short";
    },
    // Returns the min cell width or the min split width if any.
    cellOrSplitMinWidth() {
      let minWidth = null;
      if (this.hasSplits && this.minSplitWidth) minWidth = this.visibleDaysCount * this.minSplitWidth * this.daySplits.length;
      else if (this.minCellWidth && this.isWeekView) minWidth = this.visibleDaysCount * this.minCellWidth;
      return minWidth;
    },
    allDayBar() {
      let height = this.allDayBarHeight || null;
      if (height && !isNaN(height)) height += "px";
      return {
        cells: this.viewCells,
        options: this.$props,
        label: this.texts.allDay,
        shortEvents: this.hasShortEvents,
        daySplits: this.hasSplits && this.daySplits || [],
        cellOrSplitMinWidth: this.cellOrSplitMinWidth,
        height
      };
    },
    minTimestamp() {
      let date = null;
      if (this.minDate && typeof this.minDate === "string") date = this.utils.date.stringToDate(this.minDate);
      else if (this.minDate && this.minDate instanceof Date) date = this.minDate;
      return date ? date.getTime() : null;
    },
    maxTimestamp() {
      let date = null;
      if (this.maxDate && typeof this.maxDate === "string") date = this.utils.date.stringToDate(this.maxDate);
      else if (this.maxDate && this.maxDate instanceof Date) date = this.maxDate;
      return date ? date.getTime() : null;
    },
    weekDays() {
      let { weekDays, weekDaysShort = [] } = this.texts;
      weekDays = weekDays.slice(0).map((day, i) => ({
        label: day,
        ...weekDaysShort.length ? { short: weekDaysShort[i] } : {},
        hide: this.hideWeekends && i >= 5 || this.hideWeekdays.length && this.hideWeekdays.includes(i + 1)
      }));
      if (this.startWeekOnSunday) weekDays.unshift(weekDays.pop());
      return weekDays;
    },
    weekDaysInHeader() {
      return this.isMonthView || // hasSplits check is important here in case the user toggles the splits but keep minSplitWidth.
      this.isWeekView && !this.minCellWidth && !(this.hasSplits && this.minSplitWidth);
    },
    months() {
      return this.texts.months.map((month) => ({ label: month }));
    },
    // Validate and fill up the special hours object once for all at root level and not in cell.
    specialDayHours() {
      if (!this.specialHours || !Object.keys(this.specialHours).length) return {};
      return Array(7).fill("").map((cell, i) => {
        let day = this.specialHours[i + 1] || [];
        if (!Array.isArray(day)) day = [day];
        cell = [];
        day.forEach(({ from, to, class: Class, label }, j) => {
          cell[j] = {
            day: i + 1,
            from: ![null, void 0].includes(from) ? from * 1 : null,
            to: ![null, void 0].includes(to) ? to * 1 : null,
            class: Class || "",
            label: label || ""
          };
        });
        return cell;
      });
    },
    viewTitle() {
      const ud2 = this.utils.date;
      let title = "";
      const date = this.view.startDate;
      const year = date.getFullYear();
      const month = date.getMonth();
      switch (this.view.id) {
        case "years": {
          title = this.texts.years;
          break;
        }
        case "year": {
          title = year;
          break;
        }
        case "month": {
          title = `${this.months[month].label} ${year}`;
          break;
        }
        case "week": {
          const lastDayOfWeek = this.view.endDate;
          const y1 = date.getFullYear();
          let m1 = this.texts.months[date.getMonth()];
          if (this.xsmall) m1 = m1.substring(0, 3);
          let formattedMonthYear = `${m1} ${y1}`;
          if (lastDayOfWeek.getMonth() !== date.getMonth()) {
            const y2 = lastDayOfWeek.getFullYear();
            let m2 = this.texts.months[lastDayOfWeek.getMonth()];
            if (this.xsmall) m2 = m2.substring(0, 3);
            if (y1 === y2) formattedMonthYear = `${m1} - ${m2} ${y1}`;
            else {
              if (this.small) formattedMonthYear = `${m1.substring(0, 3)} ${y1} - ${m2.substring(0, 3)} ${y2}`;
              else formattedMonthYear = `${m1} ${y1} - ${m2} ${y2}`;
            }
          }
          title = `${this.texts.week} ${ud2.getWeek(this.startWeekOnSunday ? ud2.addDays(date, 1) : date)} (${formattedMonthYear})`;
          break;
        }
        case "day": {
          title = this.utils.date.formatDate(date, this.texts.dateFormat, this.texts);
          break;
        }
      }
      return title;
    },
    viewCells() {
      const ud2 = this.utils.date;
      let cells = [];
      let fromYear = null;
      let todayFound = false;
      if (!this.watchRealTime) this.now = /* @__PURE__ */ new Date();
      const now2 = this.now;
      switch (this.view.id) {
        case "years": {
          fromYear = this.view.startDate.getFullYear();
          cells = Array.apply(null, Array(25)).map((cell, i) => {
            const startDate = new Date(fromYear + i, 0, 1);
            const endDate = new Date(fromYear + i + 1, 0, 1);
            endDate.setSeconds(-1);
            return {
              startDate,
              formattedDate: ud2.formatDateLite(startDate),
              endDate,
              content: fromYear + i,
              current: fromYear + i === now2.getFullYear()
            };
          });
          break;
        }
        case "year": {
          fromYear = this.view.startDate.getFullYear();
          cells = Array.apply(null, Array(12)).map((cell, i) => {
            const startDate = new Date(fromYear, i, 1);
            const endDate = new Date(fromYear, i + 1, 1);
            endDate.setSeconds(-1);
            return {
              startDate,
              formattedDate: ud2.formatDateLite(startDate),
              endDate,
              content: this.xsmall ? this.months[i].label.substr(0, 3) : this.months[i].label,
              current: i === now2.getMonth() && fromYear === now2.getFullYear()
            };
          });
          break;
        }
        case "month": {
          const month = this.view.startDate.getMonth();
          const firstCellDate = new Date(this.view.firstCellDate);
          todayFound = false;
          cells = Array.apply(null, Array(42)).map((cell, i) => {
            const startDate = ud2.addDays(firstCellDate, i);
            const endDate = new Date(startDate);
            endDate.setHours(23, 59, 59, 0);
            const isToday = !todayFound && ud2.isToday(startDate) && !todayFound++;
            return {
              startDate,
              formattedDate: ud2.formatDateLite(startDate),
              endDate,
              content: startDate.getDate(),
              today: isToday,
              outOfScope: startDate.getMonth() !== month,
              class: `vuecal__cell--day${startDate.getDay() || 7}`
            };
          });
          if (this.hideWeekends || this.hideWeekdays.length) {
            cells = cells.filter((cell) => {
              const day = cell.startDate.getDay() || 7;
              return !(this.hideWeekends && day >= 6 || this.hideWeekdays.length && this.hideWeekdays.includes(day));
            });
          }
          break;
        }
        case "week": {
          todayFound = false;
          const firstDayOfWeek = this.view.startDate;
          const weekDays = this.weekDays;
          cells = weekDays.map((cell, i) => {
            const startDate = ud2.addDays(firstDayOfWeek, this.startWeekOnSunday && this.hideWeekends ? i - 1 : i);
            const endDate = new Date(startDate);
            endDate.setHours(23, 59, 59, 0);
            const dayOfWeek = (startDate.getDay() || 7) - 1;
            return {
              startDate,
              formattedDate: ud2.formatDateLite(startDate),
              endDate,
              // To increase performance skip checking isToday if today already found.
              today: !todayFound && ud2.isToday(startDate) && !todayFound++,
              specialHours: this.specialDayHours[dayOfWeek] || []
            };
          }).filter((cell, i) => !weekDays[i].hide);
          break;
        }
        case "day": {
          const startDate = this.view.startDate;
          const endDate = new Date(this.view.startDate);
          endDate.setHours(23, 59, 59, 0);
          const dayOfWeek = (startDate.getDay() || 7) - 1;
          cells = [{
            startDate,
            formattedDate: ud2.formatDateLite(startDate),
            endDate,
            today: ud2.isToday(startDate),
            specialHours: this.specialDayHours[dayOfWeek] || []
          }];
          break;
        }
      }
      return cells;
    },
    // Only when hiding weekdays on month and week views.
    visibleDaysCount() {
      if (this.isDayView) return 1;
      return 7 - this.weekDays.reduce((total, day) => total + day.hide, 0);
    },
    cellWidth() {
      return 100 / this.visibleDaysCount;
    },
    cssClasses() {
      const { resizeAnEvent, dragAnEvent, dragCreateAnEvent } = this.domEvents;
      return {
        [`vuecal--${this.view.id}-view`]: true,
        [`vuecal--${this.locale}`]: this.locale,
        "vuecal--no-time": !this.time,
        "vuecal--view-with-time": this.hasTimeColumn,
        "vuecal--week-numbers": this.showWeekNumbers && this.isMonthView,
        "vuecal--twelve-hour": this.twelveHour,
        "vuecal--click-to-navigate": this.clickToNavigate,
        "vuecal--hide-weekends": this.hideWeekends,
        "vuecal--split-days": this.hasSplits,
        "vuecal--sticky-split-labels": this.hasSplits && this.stickySplitLabels,
        "vuecal--overflow-x": this.minCellWidth && this.isWeekView || this.hasSplits && this.minSplitWidth,
        "vuecal--small": this.small,
        "vuecal--xsmall": this.xsmall,
        "vuecal--resizing-event": resizeAnEvent._eid,
        "vuecal--drag-creating-event": dragCreateAnEvent.event,
        "vuecal--dragging-event": dragAnEvent._eid,
        "vuecal--events-on-month-view": this.eventsOnMonthView,
        "vuecal--short-events": this.isMonthView && this.eventsOnMonthView === "short",
        "vuecal--has-touch": typeof window !== "undefined" && "ontouchstart" in window
      };
    },
    isYearsOrYearView() {
      return ["years", "year"].includes(this.view.id);
    },
    isYearsView() {
      return this.view.id === "years";
    },
    isYearView() {
      return this.view.id === "year";
    },
    isMonthView() {
      return this.view.id === "month";
    },
    isWeekOrDayView() {
      return ["week", "day"].includes(this.view.id);
    },
    isWeekView() {
      return this.view.id === "week";
    },
    isDayView() {
      return this.view.id === "day";
    }
  },
  watch: {
    events: {
      // To be able to detect an event attribute change, it has to be first initialized with a value.
      handler(events, oldEvents) {
        this.updateMutableEvents(events);
        this.addEventsToView();
      },
      deep: true
    },
    locale(locale) {
      this.loadLocale(locale);
    },
    selectedDate(date) {
      this.updateSelectedDate(date);
    },
    activeView(newVal) {
      this.switchView(newVal);
    }
  }
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", render]]);
export {
  index as default
};
