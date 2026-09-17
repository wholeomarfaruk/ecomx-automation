export declare const ALLOWED_TAGS: Set<string>;
export declare const ALLOWED_ATTRS: Set<string>;
export declare function isSafeUrl(url: string): boolean;
export declare const ALLOWED_IFRAME_SRC_PREFIXES: string[];
export declare const IFRAME_EMBED_PROVIDER_NAMES: string[];
export declare function isAllowedIframeSrc(src: string): boolean;
export declare function isSafeStyleValue(value: string): boolean;
export declare const BLOCK_TAGS: Set<string>;
export declare function isBlockTag(tagName: string): boolean;
