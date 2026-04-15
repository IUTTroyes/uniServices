/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "دوشنبه",
  "سه شنبه",
  "چهار شنبه",
  "پنج شنبه",
  "جمعه",
  "شنبه",
  "یک شنبه"
];
const months = [
  "ژانویه",
  "فوریه",
  "مارس",
  "آوریل",
  "می",
  "ژوئن",
  "ژوئیه",
  "اوت",
  "سپتامبر",
  "اکتبر",
  "نوامبر",
  "دسامبر"
];
const years = "سالها";
const year = "سال";
const month = "ماه";
const week = "هفته";
const day = "روز";
const today = "امروز";
const noEvent = "رویدادی نیست";
const allDay = "تمام روز";
const deleteEvent = "حذف";
const createEvent = "ایجاد یک رویداد";
const dateFormat = "dddd D MMMM YYYY";
const fa = {
  weekDays,
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
  fa as default,
  deleteEvent,
  month,
  months,
  noEvent,
  today,
  week,
  weekDays,
  year,
  years
};
