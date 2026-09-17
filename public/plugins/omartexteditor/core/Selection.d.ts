export declare function getCurrentRange(): Range | null;
export declare function setRange(range: Range): void;
export declare function isSelectionInside(root: HTMLElement): boolean;
export declare function closestAncestor(root: HTMLElement, predicate: (el: Element) => boolean): Element | null;
export interface SelectionBookmark {
    startPath: number[];
    startOffset: number;
    endPath: number[];
    endOffset: number;
}
export declare function bookmarkSelection(root: HTMLElement): SelectionBookmark | null;
export declare function restoreSelection(root: HTMLElement, bookmark: SelectionBookmark): boolean;
