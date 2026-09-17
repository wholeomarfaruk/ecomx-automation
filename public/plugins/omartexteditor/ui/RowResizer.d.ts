export declare class RowResizer {
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
    private startDrag;
    destroy(): void;
}
