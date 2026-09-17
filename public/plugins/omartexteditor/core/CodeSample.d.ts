export declare function insertCodeSample(root: HTMLElement, code: string, language: string): void;
export declare function updateCodeSample(pre: HTMLElement, code: string, language: string): void;
export declare function getCurrentCodeSample(node: Node): HTMLElement | null;
export declare function getCodeSampleData(pre: HTMLElement): {
    code: string;
    language: string;
};
