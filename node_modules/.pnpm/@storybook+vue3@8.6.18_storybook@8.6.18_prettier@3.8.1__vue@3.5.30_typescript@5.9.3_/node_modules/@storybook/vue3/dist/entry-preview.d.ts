export { r as render, a as renderToCanvas } from './render-0377a2e9.js';
import { LegacyStoryFn, DecoratorFunction, BaseAnnotations } from 'storybook/internal/types';
import { V as VueRenderer } from './types-1ede6954.js';
import 'vue';

declare function decorateStory(storyFn: LegacyStoryFn<VueRenderer>, decorators: DecoratorFunction<VueRenderer>[]): LegacyStoryFn<VueRenderer>;

declare const mount: BaseAnnotations<VueRenderer>['mount'];

declare const parameters: {};

export { decorateStory as applyDecorators, mount, parameters };
