export type NotificationType = 'info' | 'success' | 'error';
export interface NotificationOptions {
    message: string;
    type?: NotificationType;
    durationMs?: number;
}
export declare class Notification {
    private static container;
    private el;
    private timer;
    constructor(options: NotificationOptions);
    private static getContainer;
    close(): void;
}
