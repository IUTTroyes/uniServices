// This file registers all active front-end bundles.
// It is read by the central Shell router to dynamically inject routes and menu items.

import auth from '../../auth-bundle/assets/manifest.ts';
import intranet from '../../intranet-bundle/assets/manifest.ts';
import unifolio from '../../unifolio-bundle/assets/manifest.ts';
import edt from '../../edt-bundle/assets/manifest.ts';
import helpdesk from '../../helpdesk-bundle/assets/manifest.ts';
import questionnaire from '../../questionnaire-bundle/assets/manifest.ts';
import stage from '../../stage-bundle/assets/manifest.ts';

export const bundles = [
  auth,
  intranet,
  unifolio,
  edt,
  helpdesk,
  questionnaire,
  stage
];
