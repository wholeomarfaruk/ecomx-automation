export declare function insertTable(root: HTMLElement, rows: number, cols: number, withHeader?: boolean): void;
export declare function getCurrentCell(node: Node): HTMLTableCellElement | null;
export declare function getCurrentTable(node: Node): HTMLTableElement | null;
export declare function insertRow(table: HTMLTableElement, afterRow: HTMLTableRowElement, before?: boolean): void;
export declare function deleteRow(row: HTMLTableRowElement): void;
export declare function insertColumn(table: HTMLTableElement, atIndex: number, before?: boolean): void;
export declare function deleteColumn(table: HTMLTableElement, atIndex: number): void;
export declare function deleteTable(table: HTMLTableElement): void;
export declare function mergeCellRight(cell: HTMLTableCellElement): boolean;
export declare function splitCell(cell: HTMLTableCellElement): boolean;
export declare function toggleHeaderRow(table: HTMLTableElement): void;
export declare function navigateCell(table: HTMLTableElement, cell: HTMLTableCellElement, forward: boolean): boolean;
export declare const COLUMN_RESIZE_MIN_PX = 30;
export declare function resizeColumn(table: HTMLTableElement, colIndex: number, widthPx: number): void;
export declare function resizeRow(table: HTMLTableElement, rowIndex: number, heightPx: number): void;
export interface CellPosition {
    row: number;
    col: number;
}
export declare function getCellPosition(table: HTMLTableElement, cell: HTMLTableCellElement): CellPosition | null;
export declare function getCellRange(table: HTMLTableElement, cellA: HTMLTableCellElement, cellB: HTMLTableCellElement): HTMLTableCellElement[];
export declare function mergeCellRange(table: HTMLTableElement, cells: HTMLTableCellElement[]): HTMLTableCellElement | null;
export declare function deleteRows(table: HTMLTableElement, cells: HTMLTableCellElement[]): void;
export declare function deleteColumns(table: HTMLTableElement, cells: HTMLTableCellElement[]): void;
