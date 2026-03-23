import { ArgsStoryFn, StoryContext, RenderContext } from 'storybook/internal/types';
import { App } from 'vue';
import { V as VueRenderer } from './types-1ede6954.js';

declare const render: ArgsStoryFn<VueRenderer>;
declare const setup: (fn: (app: App, storyContext?: StoryContext<VueRenderer>) => unknown) => void;
declare function renderToCanvas({ storyFn, forceRemount, showMain, showException, storyContext, id }: RenderContext<VueRenderer>, canvasElement: VueRenderer['canvasElement']): Promise<() => void>;

export { renderToCanvas as a, render as r, setup as s };
