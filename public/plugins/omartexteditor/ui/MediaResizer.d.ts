import { ContextMenuItem } from './ContextMenu';
export declare class MediaResizer {
    private root;
    private handle;
    private menuButton;
    private activeWrapper;
    private onCommit;
    private menuBuilders;
    constructor(root: HTMLElement);
    onResizeCommit(cb: () => void): void;
    onMenuRequest(builder: (wrapper: HTMLElement) => ContextMenuItem[] | null): void;
    private handleHover;
    private handleOutsideMouseDown;
    private select;
    private deselect;
    private openMenu;
    private reposition;
    private startDrag;
    destroy(): void;
}
