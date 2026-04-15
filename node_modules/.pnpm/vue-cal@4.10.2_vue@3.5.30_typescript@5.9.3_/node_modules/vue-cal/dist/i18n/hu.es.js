/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Hétfo",
  "Kedd",
  "Szerda",
  "Csütörtök",
  "Péntek",
  "Szombat",
  "Vasárnap"
];
const months = [
  "Január",
  "Február",
  "Március",
  "Április",
  "Május",
  "Június",
  "Július",
  "Augusztus",
  "Szeptember",
  "Október",
  "November",
  "December"
];
const years = "Évek";
const year = "Év";
const month = "Hónap";
const week = "Hét";
const day = "Nap";
const today = "Mai nap";
const noEvent = "Nincs esemény";
const allDay = "Egész nap";
const deleteEvent = "Esemény törlese";
const createEvent = "Esemény létrehozása";
const dateFormat = "dddd D MMMM YYYY";
const hu = {
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
  hu as default,
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
