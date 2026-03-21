/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "সোম",
  "মঙ্গল",
  "বুধ",
  "বৃহস্পতি",
  "শুক্র",
  "শনি",
  "রবি"
];
const months = [
  "জানুয়ারি",
  "ফেব্ুয়ারী",
  "মার্চ",
  "এপ্রিল",
  "মে",
  "জুন",
  "জুলাই",
  "অগাস্ট",
  "সেপ্টেম্বর",
  "অক্টোবর",
  "নভেম্বর",
  "ডিসেম্বর"
];
const years = "বছর";
const year = "বছর";
const month = "মাস";
const week = "সপ্তাহ";
const day = "দিন";
const today = "আজ";
const noEvent = "কার্যসূচী";
const allDay = "সারাদিন";
const deleteEvent = "মুছুন";
const createEvent = "কার্যসূচী তৈরি করুন";
const dateFormat = "dddd D MMMM YYYY";
const bn = {
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
  bn as default,
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
