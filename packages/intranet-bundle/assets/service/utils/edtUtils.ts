// @ts-ignore
import { adjustColor, colorNameToRgb, darkenColor } from '@helpers';

const EVENT_COLORS_BY_GROUP_TYPE: Record<string, string> = {
  CM: '#67cfff',
  TD: '#ffee33',
  TP: '#7aff85',
};

const BADGE_SEVERITY_BY_TYPE: Record<string, string> = {
  ressource: 'primary',
  sae: 'warn',
  matiere: 'success',
};

export const normalizeDateToCalendarTimezone = (dateValue: string): Date => {
  const date = new Date(dateValue);
  return new Date(date.getTime() + date.getTimezoneOffset() * 60000);
};

export const formatDateAsDayMonthYear = (dateValue: Date | null): string | null => {
  if (!dateValue) {
    return null;
  }

  const year = dateValue.getFullYear();
  const month = String(dateValue.getMonth() + 1).padStart(2, '0');
  const day = String(dateValue.getDate()).padStart(2, '0');
  return `${day}-${month}-${year}`;
};

export const deriveAcademicYearBounds = (academicYear: any): { start: Date; end: Date } => {
  const now = new Date();
  const match = academicYear?.libelle ? academicYear.libelle.match(/(\d{4})\s*[-/]\s*(\d{4})/) : null;

  let startYear;
  let endYear;

  if (match) {
    startYear = Number.parseInt(match[1], 10);
    endYear = Number.parseInt(match[2], 10);
  } else {
    const month = now.getMonth();
    if (month >= 8) {
      startYear = now.getFullYear();
      endYear = now.getFullYear() + 1;
    } else {
      startYear = now.getFullYear() - 1;
      endYear = now.getFullYear();
    }
  }

  const start = new Date(startYear, 8, 1, 0, 0, 0, 0);
  const end = new Date(endYear, 7, 31, 23, 59, 59, 999);
  return { start, end };
};

export const clampDateInRange = (dateValue: Date | null, minDate: Date, maxDate: Date): Date | null => {
  if (!dateValue) {
    return dateValue;
  }

  if (dateValue < minDate) {
    return new Date(minDate);
  }

  if (dateValue > maxDate) {
    return new Date(maxDate);
  }

  return dateValue;
};

const getScheduleProperties = (event: any, selectedSemestre: boolean, schedules: any[]): Record<string, unknown> => {
  if (!selectedSemestre) {
    return {};
  }

  if (event.type === 'CM') {
    return {
      schedule: schedules.length > 0 ? schedules[0].id : event.groupe.id,
      isCmEvent: true,
      width: `${schedules.length * 100}%`,
    };
  }

  if (event.type === 'TD' && event.groupe.enfants && event.groupe.enfants.length > 0) {
    const tpChildren = event.groupe.enfants.filter(enfant => enfant.type === 'TP');

    if (tpChildren.length > 0) {
      return {
        schedule: tpChildren[0].id,
        isTdEvent: true,
      };
    }
  }

  return {
    schedule: event.groupe.id,
  };
};

const hasOverlap = (event: any, allEvents: any[]): boolean => {
  return allEvents.some(currentEvent =>
    (event.start < currentEvent.end && event.end > currentEvent.start) &&
    event !== currentEvent
  );
};

export const mapDepartementEvents = ({
  response,
  selectedSemestre,
  schedules,
  personnelId,
}: {
  response: any[];
  selectedSemestre: boolean;
  schedules: any[];
  personnelId: number;
}): any[] => {
  const now = new Date();

  return response.map(event => {
    const start = normalizeDateToCalendarTimezone(event.debut);
    const end = normalizeDateToCalendarTimezone(event.fin);
    const eventColor = EVENT_COLORS_BY_GROUP_TYPE[event.groupe?.type] || '#CCCCCC';

    return {
      ...event,
      ongoing: start <= now && end >= now,
      start,
      end,
      backgroundColor: adjustColor(colorNameToRgb(eventColor), 0.2, 0),
      location: event.salle,
      title: `${event.enseignement?.codeEnseignement} - ${event.libModule}`,
      type: event.type,
      groupe: event.groupe || { libelle: '**' },
      personnel: event.personnel,
      intervenantPhoto: event.personnel.photoName ?? null,
      overlap: false,
      eval: event.evaluation,
      intervenants: event.enseignement?.previsionnels
        .filter(intervenant => intervenant.personnel?.id !== personnelId)
        .map(intervenant => ({
          id: intervenant.id,
          display: intervenant.personnel?.display || 'Inconnu',
          photoName: intervenant.personnel?.photoName || null,
        })),
      ...getScheduleProperties(event, selectedSemestre, schedules),
    };
  });
};

export const mapPersonnelEvents = ({
  response,
  personnelId,
}: {
  response: any[];
  personnelId: number;
}): any[] => {
  const now = new Date();

  return response.map(event => {
    let eventTypeColor = event.couleur;

    if (event.groupe) {
      switch (event.groupe.type) {
        case 'CM':
          eventTypeColor = adjustColor(colorNameToRgb(event.couleur), 0.1, 0.2);
          break;
        case 'TD':
          eventTypeColor = adjustColor(colorNameToRgb(event.couleur), 0.3, 0.2);
          break;
        case 'TP':
          eventTypeColor = adjustColor(colorNameToRgb(event.couleur), 0, 0.2);
          break;
        default:
          eventTypeColor = adjustColor(colorNameToRgb(event.couleur), 0.8, 0.2);
      }
    }

    const start = normalizeDateToCalendarTimezone(event.debut);
    const end = normalizeDateToCalendarTimezone(event.fin);

    return {
      ...event,
      ongoing: start <= now && end >= now,
      start,
      end,
      backgroundColor: adjustColor(colorNameToRgb(event.couleur), 1, 0.2),
      typeColor: adjustColor(colorNameToRgb(eventTypeColor), 0.2, 0),
      location: event.salle,
      title: `${event.codeModule} - ${event.libModule}`,
      type: event.type,
      groupe: event.groupe || '**',
      personnel: event.personnel,
      intervenantPhoto: event.personnel.photoName ?? null,
      overlap: false,
      eval: event.evaluation,
      intervenants: (event.enseignement?.previsionnels || [])
        .filter(intervenant => intervenant.personnel?.id !== personnelId)
        .map(intervenant => ({
          id: intervenant.id,
          display: intervenant.personnel?.display || 'Inconnu',
          photoName: intervenant.personnel?.photoName || null,
        })),
    };
  });
};

type ApplyOverlapMetadataOptions = {
  selectedSemestre?: boolean;
  includeSpanningClass?: boolean;
};

export const applyOverlapMetadata = (
  mappedEvents: any[],
  selectedSemestreOrOptions: boolean | ApplyOverlapMetadataOptions = false,
): any[] => {
  const options = typeof selectedSemestreOrOptions === 'boolean'
    ? { selectedSemestre: selectedSemestreOrOptions }
    : selectedSemestreOrOptions;

  const selectedSemestre = options.selectedSemestre ?? false;
  const includeSpanningClass = options.includeSpanningClass ?? true;

  return mappedEvents.map(event => {
    const overlap = hasOverlap(event, mappedEvents);
    const className = includeSpanningClass
      ? (event.type === 'TD' && selectedSemestre ? 'td-event-spanning' : (event.type === 'CM' ? 'cm-event-spanning' : ''))
      : '';

    return {
      ...event,
      title: overlap ? event.codeModule : event.title,
      overlap,
      class: className,
    };
  });
};

export const getBadgeSeverityByType = (type: string): string => {
  return BADGE_SEVERITY_BY_TYPE[type] || 'info';
};

export const calculateTotalHours = (events: any[]): number => {
  return events.reduce((total, event) => {
    const start = new Date(event.start);
    const end = new Date(event.end);
    const duration = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    return total + duration;
  }, 0);
};

export const calculateHoursByType = (events: any[]): Array<{ type: string; heures: number }> => {
  const totalsByType: Record<string, number> = { CM: 0, TD: 0, TP: 0 };

  events.forEach(event => {
    const start = new Date(event.start);
    const end = new Date(event.end);
    const duration = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
    totalsByType[event.type] = (totalsByType[event.type] || 0) + duration;
  });

  const result: Array<{ type: string; heures: number }> = [];

  ['CM', 'TD', 'TP'].forEach(type => {
    result.push({
      type,
      heures: Math.round((totalsByType[type] || 0) * 100) / 100,
    });
    delete totalsByType[type];
  });

  Object.keys(totalsByType).forEach(type => {
    result.push({
      type,
      heures: Math.round(totalsByType[type] * 100) / 100,
    });
  });

  return result;
};

export const styleVueCalEvents = (): void => {
  const eventElements = document.querySelectorAll<HTMLElement>('.vuecal__event');

  eventElements.forEach(eventEl => {
    if (eventEl.style.backgroundColor) {
      eventEl.style.border = `2px solid ${adjustColor(darkenColor(eventEl.style.backgroundColor, 50), 0, 0.2)}`;
      eventEl.style.borderTop = `6px solid ${adjustColor(darkenColor(eventEl.style.backgroundColor, 60), 0, 0.2)}`;
      eventEl.style.overflow = 'auto';
      eventEl.style.scrollbarWidth = 'none';
      eventEl.style.cssText += '::-webkit-scrollbar { display: none; }';
      eventEl.style.opacity = '0.9';
    }
  });
};
