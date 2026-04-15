/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Ponedjeljak",
  "Utorak",
  "Srijeda",
  "Četvrtak",
  "Petak",
  "Subota",
  "Nedjelja"
];
const months = [
  "Siječanj",
  "Veljača",
  "Ožujak",
  "Travanj",
  "Svibanj",
  "Lipanj",
  "Srpanj",
  "Kolovoz",
  "Rujan",
  "Listopad",
  "Studeni",
  "Prosinac"
];
const years = "Godine";
const year = "Godina";
const month = "Mjesec";
const week = "Tjedan";
const day = "Dan";
const today = "Današnji dan";
const noEvent = "Nema događaja";
const allDay = "Cijeli dan";
const deleteEvent = "Obriši";
const createEvent = "Kreiraj događaj";
const dateFormat = "dddd D MMMM YYYY";
const hr = {
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
  hr as default,
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
