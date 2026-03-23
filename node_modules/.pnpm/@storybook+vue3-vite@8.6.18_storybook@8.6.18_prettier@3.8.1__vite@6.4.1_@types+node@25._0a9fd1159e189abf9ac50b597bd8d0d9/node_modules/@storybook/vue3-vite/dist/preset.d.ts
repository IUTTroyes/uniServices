import { PresetProperty } from 'storybook/internal/types';
import { StorybookConfig } from './index.js';
import '@storybook/builder-vite';
import 'vue-component-meta';
import 'vue-docgen-api';

declare const core: PresetProperty<'core'>;
declare const viteFinal: StorybookConfig['viteFinal'];

export { core, viteFinal };
