import { describe, it, expect } from 'vitest';
import { formatDateCourt, formatDateLong, jourDate } from '@helpers/date.js';

describe('formatDateCourt', () => {
  it('returns the date in locale date string format', () => {
    expect(formatDateCourt('2023-10-10')).toBe('10/10/2023');
  });

  it('handles invalid date input gracefully', () => {
    expect(formatDateCourt('invalid-date')).toBe('Invalid Date');
  });

  it('handles empty date input gracefully', () => {
    expect(formatDateCourt('')).toBe('Invalid Date');
  });
});

describe('formatDateLong', () => {
  it('returns the date in long French format', () => {
    expect(formatDateLong('2023-10-10')).toBe('mardi 10 octobre 2023');
  });

  it('handles invalid date input gracefully', () => {
    expect(formatDateLong('invalid-date')).toBe('Invalid Date');
  });

  it('handles empty date input gracefully', () => {
    expect(formatDateLong('')).toBe('Invalid Date');
  });
});

describe('jourDate', () => {
  it('returns the weekday in French', () => {
    expect(jourDate('2023-10-10')).toBe('mardi');
  });

  it('handles invalid date input gracefully', () => {
    expect(jourDate('invalid-date')).toBe('Invalid Date');
  });

  it('handles empty date input gracefully', () => {
    expect(jourDate('')).toBe('Invalid Date');
  });
});
