import { D as Decorator } from './public-types-e4ebb831.js';
import { ArgTypesExtractor } from 'storybook/internal/docs-tools';
import { ArgTypesEnhancer } from 'storybook/internal/types';
import { V as VueRenderer } from './types-1ede6954.js';
import 'type-fest';
import 'vue';
import 'vue-component-type-helpers';

declare const parameters: {
    docs: {
        story: {
            inline: boolean;
        };
        extractArgTypes: ArgTypesExtractor;
        extractComponentDescription: (component?: any) => string;
    };
};
declare const decorators: Decorator[];
declare const argTypesEnhancers: ArgTypesEnhancer<VueRenderer>[];

export { argTypesEnhancers, decorators, parameters };
