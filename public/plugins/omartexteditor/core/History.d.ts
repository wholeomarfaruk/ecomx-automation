import type { SelectionBookmark } from './Selection';
export interface HistoryEntry {
    html: string;
    bookmark: SelectionBookmark | null;
}
export declare class History {
    private stack;
    private index;
    private lastPushTime;
    constructor(initialHtml: string, initialBookmark?: SelectionBookmark | null);
    push(html: string, bookmark?: SelectionBookmark | null, coalesce?: boolean): void;
    canUndo(): boolean;
    canRedo(): boolean;
    undo(): HistoryEntry | null;
    redo(): HistoryEntry | null;
}
