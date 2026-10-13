import type { Section } from '@types';

/**
 * Calcule la durée estimée de réponse à un questionnaire en secondes.
 * Prend en compte les types de questions et le nombre d'éléments pour les sections configurables/dynamiques.
 */
export function calculateEstimatedTime(sections?: Section[] | null): number {
  if (!sections || !Array.isArray(sections)) return 0;

  let totalSeconds = 0;
  for (const section of sections) {
    let sectionSeconds = 0;

    // Vérifie si la section est configurable et récupère le nombre d'éléments
    const numElements = section.typeSection === 'configurable' && section.opt?.elements
      ? section.opt.elements.length
      : 1;

    for (const question of (section.questions || [])) {
      switch (question.typeQuestion) {
        case 'single_choice':
          sectionSeconds += 15;
          break;
        case 'multiple_choice':
          sectionSeconds += 25;
          break;
        case 'text_short':
          sectionSeconds += 30;
          break;
        case 'text_long':
          sectionSeconds += 60;
          break;
        case 'scale':
          sectionSeconds += 15;
          break;
        case 'matrix':
          sectionSeconds += 30;
          break;
        case 'ranking':
          sectionSeconds += 30;
          break;
        default:
          sectionSeconds += 15;
          break;
      }
    }
    totalSeconds += sectionSeconds * numElements;
  }
  return totalSeconds;
}

/**
 * Formate une durée en secondes en texte lisible (ex: "2 min 15 s", "3 min", "moins d'une minute").
 */
export function formatEstimatedTime(seconds?: number | null): string {
  if (!seconds || seconds <= 0 || seconds < 60) {
    return "moins d'une minute";
  }
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  if (remainingSeconds === 0) {
    return `${minutes} min`;
  }
  return `${minutes} min ${remainingSeconds} s`;
}
