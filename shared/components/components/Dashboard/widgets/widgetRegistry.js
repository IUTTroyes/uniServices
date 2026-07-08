import DefaultWidget from './DefaultWidget.vue';

const widgetRegistry = {
    DefaultWidget,
};

export const registerWidgetComponent = (name, component) => {
    if (!name || !component) {
        return;
    }

    widgetRegistry[name] = component;
};

export const resolveWidgetComponent = (name) => widgetRegistry[name] || widgetRegistry.DefaultWidget;

export { widgetRegistry, DefaultWidget };
