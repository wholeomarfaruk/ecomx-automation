import type { Editor } from '../core/Editor';
export interface ToolbarButtonDef {
    name: string;
    label: string;
    icon: string;
    command: string;
    commandValue?: string;
    isActive?: (editor: Editor) => boolean;
    onClick?: (editor: Editor, buttonEl: HTMLButtonElement) => void;
}
export interface ToolbarSelectDef {
    name: string;
    label: string;
    command: string;
    options: {
        label: string;
        value: string;
    }[];
    getCurrentValue?: (editor: Editor) => string | null;
}
export declare function registerToolbarButton(def: ToolbarButtonDef): void;
export declare function registerToolbarSelect(def: ToolbarSelectDef): void;
export declare class Toolbar {
    readonly el: HTMLElement;
    private editor;
    private buttons;
    private selects;
    constructor(editor: Editor, config: string);
    private render;
    private createButton;
    private createSelect;
    refresh(): void;
    destroy(): void;
}
