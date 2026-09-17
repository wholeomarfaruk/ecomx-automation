import type { TourStep } from '../core/Onboarding';
export interface TourOptions {
    steps: TourStep[];
    onComplete: () => void;
}
export declare class Tour {
    private options;
    private overlayEl;
    private calloutEl;
    private currentIndex;
    private highlightedEl;
    constructor(options: TourOptions);
    private steps;
    private renderStep;
    private positionCallout;
    private clearHighlight;
    private handleKeydown;
    private finish;
}
