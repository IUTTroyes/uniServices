/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Poniedziałek",
  "Wtorek",
  "Środa",
  "Czwartek",
  "Piątek",
  "Sobota",
  "Niedziela"
];
const months = [
  "Styczeń",
  "Luty",
  "Marzec",
  "Kwiecień",
  "Maj",
  "Czerwiec",
  "Lipiec",
  "Sierpień",
  "Wrzesień",
  "Październik",
  "Listopad",
  "Grudzień"
];
const years = "Lata";
const year = "Rok";
const month = "Miesiąc";
const week = "Tydzień";
const day = "Dzień";
const today = "Dzisiaj";
const noEvent = "Brak wydarzeń";
const allDay = "Cały dzień";
const deleteEvent = "Usuń";
const createEvent = "Utwórz wydarzenie";
const dateFormat = "dddd, D MMMM YYYY";
const pl = {
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
  pl as default,
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
