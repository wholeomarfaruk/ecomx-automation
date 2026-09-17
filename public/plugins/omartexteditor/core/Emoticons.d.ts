export interface Emoji {
    char: string;
    name: string;
    keywords: string[];
}
export declare const EMOJI_LIST: Emoji[];
export declare function searchEmoji(query: string): Emoji[];
export declare function getRecentEmoji(): Emoji[];
export declare function recordRecentEmoji(char: string): void;
