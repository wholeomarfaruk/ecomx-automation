export declare class ColumnResizer {
    private root;
    private handles;
    private onCommit;
    private resizeObserver;
    constructor(root: HTMLElement);
    onResizeCommit(cb: () => void): void;
    refresh(): void;
    private repositionAll;
    private clearHandles;
    private attachHandles;
    private positionHandle;
    private lockColumnWidths;
    private startDrag;
    destroy(): void;
}
