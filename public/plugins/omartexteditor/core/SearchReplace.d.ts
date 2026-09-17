export interface SearchOptions {
    matchCase?: boolean;
    wholeWord?: boolean;
}
export interface SearchMatch {
    node: Text;
    start: number;
    end: number;
}
export declare function findAllMatches(root: HTMLElement, query: string, options?: SearchOptions): SearchMatch[];
export declare function highlightMatches(matches: SearchMatch[]): HTMLElement[];
export declare function clearHighlights(root: HTMLElement): void;
export declare function setActiveHighlight(marks: HTMLElement[], index: number): void;
export declare function replaceMatchAt(mark: HTMLElement, replacement: string): void;
export declare function replaceAll(root: HTMLElement, query: string, replacement: string, options?: SearchOptions): number;
