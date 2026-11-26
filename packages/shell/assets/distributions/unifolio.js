// Distribution « UniFolio autonome » : doit lister les mêmes packages que back/config/distributions/unifolio.php.
import auth from '../../../auth-bundle/assets/manifest.ts';
import formation from '../../../formation-bundle/assets/manifest.ts';
import unifolio from '../../../unifolio-bundle/assets/manifest.ts';

export const bundles = [auth, formation, unifolio];
