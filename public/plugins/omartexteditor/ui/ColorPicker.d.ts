export interface ColorPickerOptions {
    onPick: (color: string) => void;
    onClear?: () => void;
}
export declare class ColorPicker {
    private el;
    constructor(anchor: HTMLElement, options: ColorPickerOptions);
    private handleOutsideClick;
    private handleKeydown;
    close(): void;
}
