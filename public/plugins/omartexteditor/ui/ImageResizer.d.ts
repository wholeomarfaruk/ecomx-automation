export declare class ImageResizer {
    private root;
    private handle;
    private activeImg;
    private onCommit;
    constructor(root: HTMLElement);
    onResizeCommit(cb: () => void): void;
    private handleClick;
    private select;
    private deselect;
    private reposition;
    private startDrag;
    destroy(): void;
}
