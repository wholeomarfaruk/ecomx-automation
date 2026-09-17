export interface ContextMenuItem {
    label: string;
    onSelect: () => void;
    separator?: boolean;
}
export declare class ContextMenu {
    private el;
    constructor(x: number, y: number, items: ContextMenuItem[]);
    private clampToViewport;
    private handleOutsideClick;
    private handleKeydown;
    close(): void;
}
