export declare function extractIframeSrc(pastedText: string): string | null;
export declare function resolveEmbedSrc(pastedText: string): string | null;
export declare function insertEmbed(root: HTMLElement, pastedText: string): boolean;
export declare function getSelectedEmbed(root: HTMLElement): HTMLElement | null;
export declare function getEmbedSrc(wrapper: HTMLElement): string;
export declare function updateEmbed(wrapper: HTMLElement, pastedText: string): boolean;
export declare function removeEmbed(wrapper: HTMLElement): void;
