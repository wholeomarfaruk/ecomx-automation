export interface LinkAttrs {
    href: string;
    text?: string;
    title?: string;
    newTab?: boolean;
}
export declare function insertOrUpdateLink(root: HTMLElement, attrs: LinkAttrs): boolean;
export declare function removeLink(root: HTMLElement): boolean;
export declare function getCurrentLink(root: HTMLElement): HTMLAnchorElement | null;
export declare function isLinkActive(root: HTMLElement): boolean;
