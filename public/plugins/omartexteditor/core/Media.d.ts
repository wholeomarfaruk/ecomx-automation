export type MediaKind = 'embed' | 'video' | 'audio';
export interface MediaAttrs {
    url: string;
    poster?: string;
    width?: string;
    height?: string;
}
export declare function toEmbedUrl(url: string): string | null;
export declare function isDirectVideoUrl(url: string): boolean;
export declare function isDirectAudioUrl(url: string): boolean;
export declare function classifyMediaUrl(url: string): MediaKind | null;
export declare function insertMediaEmbed(root: HTMLElement, attrs: MediaAttrs): boolean;
export declare function getSelectedMediaEmbed(root: HTMLElement): HTMLElement | null;
export declare function getMediaKind(wrapper: HTMLElement): MediaKind;
export declare function getEmbedUrl(wrapper: HTMLElement): string;
export declare function getMediaAttrs(wrapper: HTMLElement): MediaAttrs;
export declare function updateMediaEmbed(wrapper: HTMLElement, attrs: MediaAttrs): boolean;
export declare function removeMediaEmbed(wrapper: HTMLElement): void;
export type MediaAlign = 'left' | 'center' | 'right' | 'none';
export declare function setMediaAlign(wrapper: HTMLElement, align: MediaAlign): void;
export declare function getMediaAlign(wrapper: HTMLElement): MediaAlign;
export declare function setMediaWidth(wrapper: HTMLElement, widthPx: number): void;
export declare function setMediaFullWidth(wrapper: HTMLElement): void;
export declare function isMediaFullWidth(wrapper: HTMLElement): boolean;
