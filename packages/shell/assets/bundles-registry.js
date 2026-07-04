// This file registers all active front-end bundles.
// It is read by the central Shell router to dynamically inject routes and menu items.

import auth from '../../auth-bundle/assets/index.js';
import intranet from '../../intranet-bundle/assets/index.js';
import unifolio from '../../unifolio-bundle/assets/index.js';
import edt from '../../edt-bundle/assets/index.js';
import helpdesk from '../../helpdesk-bundle/assets/index.js';
import questionnaire from '../../questionnaire-bundle/assets/index.js';
import stage from '../../stage-bundle/assets/index.js';

export const bundles = [
  auth,
  intranet,
  unifolio,
  edt,
  helpdesk,
  questionnaire,
  stage
];
