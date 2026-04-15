import { WebRenderer, Canvas } from 'storybook/internal/types';
import { ConcreteComponent } from 'vue';

type StoryFnVueReturnType = ConcreteComponent<any>;
interface VueRenderer extends WebRenderer {
    component: Omit<ConcreteComponent<this['T']>, 'props'>;
    storyResult: StoryFnVueReturnType;
    mount: (Component?: StoryFnVueReturnType, options?: {
        props?: Record<string, any>;
        slots?: Record<string, any>;
    }) => Promise<Canvas>;
}

export { VueRenderer as V };
