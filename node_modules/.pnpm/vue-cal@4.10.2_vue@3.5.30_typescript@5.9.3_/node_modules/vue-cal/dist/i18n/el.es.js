/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "Δευτέρα",
  "Τρίτη",
  "Τετάρτη",
  "Πέμπτη",
  "Παρασκευή",
  "Σάββατο",
  "Κυριακή"
];
const months = [
  "Ιανουάριος",
  "Φεβρουάριος",
  "Μάρτιος",
  "Απρίλιος",
  "Μάιος",
  "Ιούνιος",
  "Ιούλιος",
  "Αύγουστος",
  "Σεπτέμβριος",
  "Οκτώβριος",
  "Νοέμβριος",
  "Δεκέμβριος"
];
const monthsGenitive = [
  "Ιανουαρίου",
  "Φεβρουαρίου",
  "Μαρτίου",
  "Απριλίου",
  "Μαΐου",
  "Ιουνίου",
  "Ιουλίου",
  "Αυγούστου",
  "Σεπτεμβρίου",
  "Οκτωβρίου",
  "Νοεμβρίου",
  "Δεκεμβρίου"
];
const years = "Έτη";
const year = "Έτος";
const month = "Μήνα";
const week = "Εβδομάδα";
const day = "Ημέρα";
const today = "Σήμερα";
const noEvent = "Κανένα συμβάν";
const allDay = "Ημερήσιο συμβάν";
const deleteEvent = "Διαγραφή";
const createEvent = "Δημιουργία συμβάντος";
const dateFormat = "dddd D MMMMG YYYY";
const am = "π.μ.";
const pm = "μ.μ.";
const el = {
  weekDays,
  months,
  monthsGenitive,
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
  dateFormat,
  am,
  pm
};
export {
  allDay,
  am,
  createEvent,
  dateFormat,
  day,
  el as default,
  deleteEvent,
  month,
  months,
  monthsGenitive,
  noEvent,
  pm,
  today,
  week,
  weekDays,
  year,
  years
};
