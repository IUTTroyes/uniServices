import { bundles } from '../bundles-registry';

let widgetsAreRegistered = false;

export const registerAllBundleWidgets = () => {
    if (widgetsAreRegistered) {
        return;
    }

    bundles.forEach((bundle) => {
        if (typeof bundle?.registerWidgets === 'function') {
            bundle.registerWidgets();
        }
    });

    widgetsAreRegistered = true;
};
