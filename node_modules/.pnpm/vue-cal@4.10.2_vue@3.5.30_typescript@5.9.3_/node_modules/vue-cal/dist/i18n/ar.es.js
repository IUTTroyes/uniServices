/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "الإثنين",
  "الثلاثاء",
  "الأربعاء",
  "الخميس",
  "الجمعة",
  "السبت",
  "الأحد"
];
const months = [
  "يناير",
  "فبراير",
  "مارس",
  "أبريل",
  "مايو",
  "يونيو",
  "يوليو",
  "أغسطس",
  "سبتمبر",
  "أكتوبر",
  "نوفمبر",
  " ديسمبر"
];
const years = "سنوات";
const year = "سنة";
const month = "شهر";
const week = "أسبوع";
const day = "يوم";
const today = "اليوم";
const noEvent = "لا حدث";
const allDay = "طوال اليوم";
const deleteEvent = "حذف";
const createEvent = "إنشاء حدث";
const dateFormat = "dddd D MMMM YYYY";
const ar = {
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
  ar as default,
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
