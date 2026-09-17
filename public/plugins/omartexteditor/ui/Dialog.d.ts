export interface DialogField {
    name: string;
    label: string;
    type: 'text' | 'checkbox' | 'select' | 'textarea' | 'note';
    defaultValue?: string | boolean;
    options?: {
        label: string;
        value: string;
    }[];
}
export interface DialogConfig {
    title: string;
    fields: DialogField[];
    onSubmit: (values: Record<string, string | boolean>) => void;
    onCancel?: () => void;
    submitLabel?: string;
}
export declare class Dialog {
    private config;
    private overlayEl;
    private dialogEl;
    private inputs;
    private previouslyFocused;
    constructor(config: DialogConfig);
    private render;
    private renderField;
    private focusFirstField;
    private handleKeydown;
    private trapFocus;
    private collectValues;
    private submit;
    private cancel;
    private close;
}
export interface PickerDialogConfig {
    title: string;
    searchPlaceholder?: string;
    onClose?: () => void;
}
export declare class PickerDialog {
    private config;
    readonly bodyEl: HTMLElement;
    readonly searchInput: HTMLInputElement | null;
    private overlayEl;
    private dialogEl;
    private previouslyFocused;
    constructor(config: PickerDialogConfig);
    private handleKeydown;
    close(): void;
}
