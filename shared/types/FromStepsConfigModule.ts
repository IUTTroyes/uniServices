// front/apps/intranet/src/forms/types.ts

export type FormField = {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    gridClass?: string;
    options?: any;
    visibleIf?: (values: any) => boolean;
};

export type FormConfigSection = {
    title: string;
    fields: FormField[];
};

export type FormOptions = {
    readonly?: boolean;
    autosave?: boolean;
    autosaveUrl?: string;
    buttons?: Record<string, any>;
    onBeforeSubmit?: (values: any) => Promise<void> | void;
};

export type StepConfig = {
    label: string;
    formConfig: FormConfigSection;
    formOptions: FormOptions;
    initialValues: Record<string, any>;
};

export type StepsArray = StepConfig[];
