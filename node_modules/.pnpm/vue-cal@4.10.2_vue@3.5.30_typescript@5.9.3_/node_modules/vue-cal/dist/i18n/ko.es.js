/**
  * vue-cal v4.10.2
  * (c) 2025 Antoni Andre <antoniandre.web@gmail.com>
  * @license MIT
  */
const weekDays = [
  "월",
  "화",
  "수",
  "목",
  "금",
  "토",
  "일"
];
const months = [
  "1월",
  "2월",
  "3월",
  "4월",
  "5월",
  "6월",
  "7월",
  "8월",
  "9월",
  "10월",
  "11월",
  "12월"
];
const years = "년도";
const year = "연간";
const month = "월간";
const week = "주간";
const day = "일간";
const today = "오늘";
const noEvent = "일정 없음";
const allDay = "하루 종일";
const deleteEvent = "삭제";
const createEvent = "일정 추가";
const dateFormat = "YYYY년 MMMM D일 dddd요일";
const ko = {
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
  ko as default,
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
