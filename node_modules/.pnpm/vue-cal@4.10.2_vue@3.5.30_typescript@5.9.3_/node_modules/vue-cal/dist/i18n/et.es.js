/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Esmaspäev",
  "Teisipäev",
  "Kolmapäev",
  "Neljapäev",
  "Reede",
  "Laupäev",
  "Pühapäev"
];
const months = [
  "Jaanuar",
  "Veebruar",
  "Märts",
  "Aprill",
  "Mai",
  "Juuni",
  "Juuli",
  "August",
  "September",
  "Oktoober",
  "November",
  "Detsember"
];
const years = "Aastad";
const year = "Aasta";
const month = "Kuu";
const week = "Nädal";
const day = "Päev";
const today = "Täna";
const noEvent = "Sündmus puudub";
const allDay = "Terve päev";
const deleteEvent = "Kustuta";
const createEvent = "Loo sündmus";
const dateFormat = "dddd D MMMM YYYY";
const et = {
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
  et as default,
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
