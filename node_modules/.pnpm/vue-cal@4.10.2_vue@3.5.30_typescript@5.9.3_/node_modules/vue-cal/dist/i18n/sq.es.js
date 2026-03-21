/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "E Hënë",
  "E Martë",
  "E Mërkurë",
  "E Enjte",
  "E Premte",
  "E Shtunë",
  "E Diel"
];
const weekDaysShort = [
  "Hë",
  "Ma",
  "Mr",
  "Enj",
  "Pr",
  "Sh",
  "D"
];
const months = [
  "Janar",
  "Shkurt",
  "Mars",
  "Prill",
  "Maj",
  "Qershor",
  "Korrik",
  "Gusht",
  "Shtator",
  "Tetor",
  "Nëntor",
  "Dhjetor"
];
const years = "Vitet";
const year = "Viti";
const month = "Muaji";
const week = "Java";
const day = "Dita";
const today = "Sot";
const noEvent = "Nuk ka event";
const allDay = "Tërë ditën";
const deleteEvent = "Fshijë";
const createEvent = "Krijo një event";
const dateFormat = "dddd D MMMM YYYY";
const sq = {
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
  sq as default,
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
