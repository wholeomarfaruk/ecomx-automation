export interface KeyBinding {
    key: string;
    ctrl?: boolean;
    shift?: boolean;
    command: string;
    label: string;
}
export declare class Keymap {
    private bindings;
    add(binding: Omit<KeyBinding, 'label'>): void;
    match(e: KeyboardEvent): string | null;
    labelFor(command: string): string | null;
    list(): readonly KeyBinding[];
}
export declare function createDefaultKeymap(): Keymap;
