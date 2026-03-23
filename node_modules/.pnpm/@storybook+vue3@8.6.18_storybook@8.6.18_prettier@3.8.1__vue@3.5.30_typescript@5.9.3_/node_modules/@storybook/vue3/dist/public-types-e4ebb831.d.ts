import { Args, ComponentAnnotations, AnnotatedStoryFn, ArgsStoryFn, ArgsFromMeta, StoryAnnotations, StrictArgs, DecoratorFunction, LoaderFunction, StoryContext as StoryContext$1, ProjectAnnotations } from 'storybook/internal/types';
import { Simplify, SetOptional, Constructor, RemoveIndexSignature } from 'type-fest';
import { FunctionalComponent, VNodeChild } from 'vue';
import { ComponentProps, ComponentSlots } from 'vue-component-type-helpers';
import { V as VueRenderer } from './types-1ede6954.js';

/**
 * Metadata to configure the stories for a component.
 *
 * @see [Default export](https://storybook.js.org/docs/api/csf#default-export)
 */
type Meta<TCmpOrArgs = Args> = ComponentAnnotations<VueRenderer, ComponentPropsOrProps<TCmpOrArgs>>;
/**
 * Story function that represents a CSFv2 component example.
 *
 * @see [Named Story exports](https://storybook.js.org/docs/api/csf#named-story-exports)
 */
type StoryFn<TCmpOrArgs = Args> = AnnotatedStoryFn<VueRenderer, ComponentPropsOrProps<TCmpOrArgs>>;
/**
 * Story object that represents a CSFv3 component example.
 *
 * @see [Named Story exports](https://storybook.js.org/docs/api/csf#named-story-exports)
 */
type StoryObj<TMetaOrCmpOrArgs = Args> = TMetaOrCmpOrArgs extends {
    render?: ArgsStoryFn<VueRenderer, any>;
    component?: infer Component;
    args?: infer DefaultArgs;
} ? Simplify<ComponentPropsAndSlots<Component> & ArgsFromMeta<VueRenderer, TMetaOrCmpOrArgs>> extends infer TArgs ? StoryAnnotations<VueRenderer, TArgs, SetOptional<TArgs, Extract<keyof TArgs, keyof DefaultArgs>>> : never : StoryAnnotations<VueRenderer, ComponentPropsOrProps<TMetaOrCmpOrArgs>>;
type ExtractSlots<C> = AllowNonFunctionSlots<Partial<RemoveIndexSignature<ComponentSlots<C>>>>;
type AllowNonFunctionSlots<Slots> = {
    [K in keyof Slots]: Slots[K] | VNodeChild;
};
type ComponentPropsAndSlots<C> = ComponentProps<C> & ExtractSlots<C>;
type ComponentPropsOrProps<TCmpOrArgs> = TCmpOrArgs extends Constructor<any> ? ComponentPropsAndSlots<TCmpOrArgs> : TCmpOrArgs extends FunctionalComponent<any> ? ComponentPropsAndSlots<TCmpOrArgs> : TCmpOrArgs;
type Decorator<TArgs = StrictArgs> = DecoratorFunction<VueRenderer, TArgs>;
type Loader<TArgs = StrictArgs> = LoaderFunction<VueRenderer, TArgs>;
type StoryContext<TArgs = StrictArgs> = StoryContext$1<VueRenderer, TArgs>;
type Preview = ProjectAnnotations<VueRenderer>;

export { ComponentPropsAndSlots as C, Decorator as D, Loader as L, Meta as M, Preview as P, StoryFn as S, StoryObj as a, StoryContext as b };
