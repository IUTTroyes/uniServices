import { adjustColor, colorNameToRgb } from '@helpers/colors.js';

const EVENT_COLORS_BY_GROUP_TYPE: Record<string, string> = {
  CM: '#67cfff',
  TD: '#ffee33',
  TP: '#7aff85',
};

const normalizeDateToCalendarTimezone = (dateValue: string): Date => {
  const date = new Date(dateValue);
  return new Date(date.getTime() + date.getTimezoneOffset() * 60000);
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

export const applyOverlapMetadata = (mappedEvents: any[], selectedSemestre: boolean): any[] => {
  return mappedEvents.map(event => {
    const overlap = hasOverlap(event, mappedEvents);

    return {
      ...event,
      title: overlap ? event.codeModule : event.title,
      overlap,
      class: event.type === 'TD' && selectedSemestre ? 'td-event-spanning' : (event.type === 'CM' ? 'cm-event-spanning' : ''),
    };
  });
};
