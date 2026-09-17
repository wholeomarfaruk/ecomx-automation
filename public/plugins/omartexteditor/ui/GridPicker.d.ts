export interface GridPickerOptions {
    maxRows?: number;
    maxCols?: number;
    onPick: (rows: number, cols: number) => void;
}
export declare class GridPicker {
    private el;
    private cells;
    private label;
    constructor(anchor: HTMLElement, options: GridPickerOptions);
    private highlight;
    private handleOutsideClick;
    private handleKeydown;
    close(): void;
}
