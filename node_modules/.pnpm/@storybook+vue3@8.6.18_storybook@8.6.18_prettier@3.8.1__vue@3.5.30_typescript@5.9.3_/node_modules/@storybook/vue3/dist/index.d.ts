export { s as setup } from './render-0377a2e9.js';
import { M as Meta } from './public-types-e4ebb831.js';
export { C as ComponentPropsAndSlots, D as Decorator, L as Loader, P as Preview, b as StoryContext, S as StoryFn, a as StoryObj } from './public-types-e4ebb831.js';
import { NamedOrDefaultProjectAnnotations, NormalizedProjectAnnotations, ProjectAnnotations, Args, StoryAnnotationsOrFn, ComposedStoryFn, Store_CSFExports, StoriesWithPartialProps } from 'storybook/internal/types';
export { ArgTypes, Args, Parameters, StrictArgs } from 'storybook/internal/types';
import { V as VueRenderer } from './types-1ede6954.js';
import 'vue';
import 'type-fest';
import 'vue-component-type-helpers';

type JSXAble<TElement> = TElement & {
    new (...args: any[]): any;
    $props: any;
};
type MapToJSXAble<T> = {
    [K in keyof T]: JSXAble<T[K]>;
};
/**
 * Function that sets the globalConfig of your Storybook. The global config is the preview module of
 * your .storybook folder.
 *
 * It should be run a single time, so that your global config (e.g. decorators) is applied to your
 * stories when using `composeStories` or `composeStory`.
 *
 * Example:
 *
 * ```jsx
 * // setup-file.js
 * import { setProjectAnnotations } from '@storybook/vue3';
 * import projectAnnotations from './.storybook/preview';
 *
 * setProjectAnnotations(projectAnnotations);
 * ```
 *
 * @param projectAnnotations - E.g. (import projectAnnotations from '../.storybook/preview')
 */
declare function setProjectAnnotations(projectAnnotations: NamedOrDefaultProjectAnnotations<any> | NamedOrDefaultProjectAnnotations<any>[]): NormalizedProjectAnnotations<VueRenderer>;
declare const vueProjectAnnotations: ProjectAnnotations<VueRenderer>;
/**
 * Function that will receive a story along with meta (e.g. a default export from a .stories file)
 * and optionally projectAnnotations e.g. (import * from '../.storybook/preview) and will return a
 * composed component that has all args/parameters/decorators/etc combined and applied to it.
 *
 * It's very useful for reusing a story in scenarios outside of Storybook like unit testing.
 *
 * Example:
 *
 * ```jsx
 * import { render } from '@testing-library/vue';
 * import { composeStory } from '@storybook/vue3';
 * import Meta, { Primary as PrimaryStory } from './Button.stories';
 *
 * const Primary = composeStory(PrimaryStory, Meta);
 *
 * test('renders primary button with Hello World', () => {
 *   const { getByText } = render(Primary, { props: { label: 'Hello world' } });
 *   expect(getByText(/Hello world/i)).not.toBeNull();
 * });
 * ```
 *
 * @param story
 * @param componentAnnotations - E.g. (import Meta from './Button.stories')
 * @param [projectAnnotations] - E.g. (import * as projectAnnotations from '../.storybook/preview')
 *   this can be applied automatically if you use `setProjectAnnotations` in your setup files.
 * @param [exportsName] - In case your story does not contain a name and you want it to have a name.
 */
declare function composeStory<TArgs extends Args = Args>(story: StoryAnnotationsOrFn<VueRenderer, TArgs>, componentAnnotations: Meta<TArgs | any>, projectAnnotations?: ProjectAnnotations<VueRenderer>, exportsName?: string): JSXAble<ComposedStoryFn<VueRenderer, Partial<TArgs>>>;
/**
 * Function that will receive a stories import (e.g. `import * as stories from './Button.stories'`)
 * and optionally projectAnnotations (e.g. `import * from '../.storybook/preview`) and will return
 * an object containing all the stories passed, but now as a composed component that has all
 * args/parameters/decorators/etc combined and applied to it.
 *
 * It's very useful for reusing stories in scenarios outside of Storybook like unit testing.
 *
 * Example:
 *
 * ```jsx
 * import { render } from '@testing-library/vue';
 * import { composeStories } from '@storybook/vue3';
 * import * as stories from './Button.stories';
 *
 * const { Primary, Secondary } = composeStories(stories);
 *
 * test('renders primary button with Hello World', () => {
 *   const { getByText } = render(Primary, { props: { label: 'Hello world' } });
 *   expect(getByText(/Hello world/i)).not.toBeNull();
 * });
 * ```
 *
 * @param csfExports - E.g. (import * as stories from './Button.stories')
 * @param [projectAnnotations] - E.g. (import * as projectAnnotations from '../.storybook/preview')
 *   this can be applied automatically if you use `setProjectAnnotations` in your setup files.
 */
declare function composeStories<TModule extends Store_CSFExports<VueRenderer, any>>(csfExports: TModule, projectAnnotations?: ProjectAnnotations<VueRenderer>): MapToJSXAble<Omit<StoriesWithPartialProps<VueRenderer, TModule>, keyof Store_CSFExports>>;

export { Meta, VueRenderer, composeStories, composeStory, setProjectAnnotations, vueProjectAnnotations };
