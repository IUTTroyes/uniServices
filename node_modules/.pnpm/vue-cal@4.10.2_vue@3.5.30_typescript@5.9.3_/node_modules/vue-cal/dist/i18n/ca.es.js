/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Dilluns",
  "Dimarts",
  "Dimecres",
  "Dijous",
  "Divendres",
  "Dissabte",
  "Diumenge"
];
const weekDaysShort = [
  "Dl",
  "Dt",
  "Dc",
  "Dj",
  "Dv",
  "Ds",
  "Dg"
];
const months = [
  "Gener",
  "Febrer",
  "Març",
  "Abril",
  "Maig",
  "Juny",
  "Juliol",
  "Agost",
  "Setembre",
  "Octubre",
  "Novembre",
  "Desembre"
];
const years = "Anys";
const year = "Any";
const month = "Mes";
const week = "Setmana";
const day = "Dia";
const today = "Avui";
const noEvent = "No hi ha esdeveniments";
const allDay = "Tot el dia";
const deleteEvent = "Eliminar";
const createEvent = "Crear un esdeveniment";
const dateFormat = "dddd D MMMM YYYY";
const ca = {
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
  ca as default,
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
