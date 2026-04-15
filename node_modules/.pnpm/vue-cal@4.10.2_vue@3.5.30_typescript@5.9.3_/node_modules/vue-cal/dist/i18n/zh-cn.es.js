/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "星期一",
  "星期二",
  "星期三",
  "星期四",
  "星期五",
  "星期六",
  "星期日"
];
const weekDaysShort = [
  "一",
  "二",
  "三",
  "四",
  "五",
  "六",
  "日"
];
const months = [
  "一月",
  "二月",
  "三月",
  "四月",
  "五月",
  "六月",
  "七月",
  "八月",
  "九月",
  "十月",
  "十一月",
  "十二月"
];
const years = "年";
const year = "本年";
const month = "月";
const week = "周";
const day = "日";
const today = "今日";
const noEvent = "暂无活动";
const allDay = "整天";
const deleteEvent = "删除";
const createEvent = "新建活动";
const dateFormat = "YYYY MMMM D dddd";
const zhCn = {
  weekDays,
  weekDaysShort,
  months,
  years,
  year,
  month,
  week,
  day,
  today,
  noEvent,
  allDay,
  deleteEvent,
  createEvent,
  dateFormat
};
export {
  allDay,
  createEvent,
  dateFormat,
  day,
  zhCn as default,
  deleteEvent,
  month,
  months,
  noEvent,
  today,
  week,
  weekDays,
  weekDaysShort,
  year,
  years
};
