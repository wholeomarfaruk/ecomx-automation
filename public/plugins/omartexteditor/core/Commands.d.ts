export type CommandHandler = (value?: string) => void;
export declare class Commands {
    private handlers;
    register(name: string, handler: CommandHandler): void;
    has(name: string): boolean;
    exec(name: string, value?: string): boolean;
}
