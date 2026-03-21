/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Ponedeljek",
  "Torek",
  "Sreda",
  "Četrtek",
  "Petek",
  "Sobota",
  "Nedelja"
];
const months = [
  "Januar",
  "Februar",
  "Marec",
  "April",
  "Maj",
  "Junij",
  "Julij",
  "Avgust",
  "September",
  "Oktober",
  "November",
  "December"
];
const years = "Leta";
const year = "Leto";
const month = "Mesec";
const week = "Teden";
const day = "Dan";
const today = "Danes";
const noEvent = "Ni dogodkov";
const allDay = "Cel dan";
const deleteEvent = "Odstrani";
const createEvent = "Ustvari dogodek";
const dateFormat = "dddd MMMM D, YYYY";
const sl = {
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
  sl as default,
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
