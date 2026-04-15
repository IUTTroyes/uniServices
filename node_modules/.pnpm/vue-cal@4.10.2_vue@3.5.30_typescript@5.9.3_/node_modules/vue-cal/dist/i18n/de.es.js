/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Montag",
  "Dienstag",
  "Mittwoch",
  "Donnerstag",
  "Freitag",
  "Samstag",
  "Sonntag"
];
const months = [
  "Januar",
  "Februar",
  "März",
  "April",
  "Mai",
  "Juni",
  "Juli",
  "August",
  "September",
  "Oktober",
  "November",
  "Dezember"
];
const years = "Jahre";
const year = "Jahr";
const month = "Monat";
const week = "Woche";
const day = "Tag";
const today = "Heute";
const noEvent = "Keine Events";
const allDay = "Ganztägig";
const deleteEvent = "Löschen";
const createEvent = "Event erstellen";
const dateFormat = "dddd D MMMM YYYY";
const de = {
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
  de as default,
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
