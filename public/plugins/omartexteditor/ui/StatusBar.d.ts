export declare class StatusBar {
    readonly el: HTMLElement;
    private segments;
    private branding;
    constructor();
    setSegment(name: string, text: string): void;
    setSegmentContent(name: string, children: Node[]): void;
    private getOrCreateSegment;
    destroy(): void;
}
