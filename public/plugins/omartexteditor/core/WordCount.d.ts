export interface CountResult {
    words: number;
    characters: number;
    charactersNoSpaces: number;
}
export declare function countText(root: HTMLElement): CountResult;
