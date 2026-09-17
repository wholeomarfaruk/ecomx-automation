type Handler = (payload?: unknown) => void;
export declare class EventBus {
    private listeners;
    on(event: string, handler: Handler): void;
    off(event: string, handler: Handler): void;
    fire(event: string, payload?: unknown): void;
    destroy(): void;
}
export {};
