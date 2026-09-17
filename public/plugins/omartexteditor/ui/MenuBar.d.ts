import type { Editor } from '../core/Editor';
export interface MenuItemDef {
    label: string;
    command?: string;
    commandValue?: string;
    action?: (editor: Editor, itemEl: HTMLElement) => void;
    separator?: boolean;
    submenu?: MenuItemDef[];
    isActive?: (editor: Editor) => boolean;
}
export interface MenuDef {
    label: string;
    items: MenuItemDef[];
}
export declare class MenuBar {
    private editor;
    private menus;
    readonly el: HTMLElement;
    private openMenu;
    constructor(editor: Editor, menus: MenuDef[]);
    private renderMenuLabel;
    private openDropdown;
    private buildDropdown;
    private handleOutsideClick;
    private handleKeydown;
    private closeAll;
    destroy(): void;
}
