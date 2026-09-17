export interface SpecialChar {
    char: string;
    name: string;
    code: string;
}
export declare const CHAR_MAP_LIST: SpecialChar[];
export declare function searchCharMap(query: string): SpecialChar[];
