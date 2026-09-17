export interface TourStep {
    selector: string;
    title: string;
    body: string;
}
export declare function hasTourBeenSeen(tourId: string): boolean;
export declare function markTourSeen(tourId: string): void;
export declare function resetTourSeen(tourId: string): void;
