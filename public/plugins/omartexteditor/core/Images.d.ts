export interface ImageAttrs {
    src: string;
    alt?: string;
    width?: string;
    height?: string;
    align?: 'left' | 'center' | 'right' | 'none';
}
export declare function insertOrUpdateImage(root: HTMLElement, attrs: ImageAttrs, existing?: HTMLImageElement | null): boolean;
export declare function setImageAlign(img: HTMLImageElement, align: ImageAttrs['align']): void;
export declare function getImageAlign(img: HTMLImageElement): NonNullable<ImageAttrs['align']>;
export declare function removeImage(img: HTMLImageElement): void;
export declare function getSelectedImage(root: HTMLElement): HTMLImageElement | null;
export declare function readImageFileAsDataUrl(file: File): Promise<string>;
