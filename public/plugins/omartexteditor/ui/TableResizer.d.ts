export declare class TableResizer {
    private root;
    private handle;
    private moveHandle;
    private activeTable;
    private onCommit;
    private onMoveCommit;
    constructor(root: HTMLElement);
    onResizeCommit(cb: () => void): void;
    onMoveCommitCb(cb: () => void): void;
    private handleClick;
    private select;
    private deselect;
    private reposition;
    private startDrag;
    private explicitColumnWidths;
    private handleDragStart;
    private handleDragOver;
    private handleDrop;
    private findDropBlock;
    destroy(): void;
}
