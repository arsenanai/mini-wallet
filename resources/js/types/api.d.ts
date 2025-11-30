import { Transaction } from './';

/**
 * API Contract for POST /api/transactions
 */
export interface TransferRequestPayload {
    receiver_email: string;
    amount: number;
}

export interface TransferSuccessResponse {
    message: string;
    transaction: Transaction; // We can reuse the existing Transaction type
}

export interface ValidationErrorResponse {
    message: string;
    errors: {
        receiver_email?: string[];
        amount?: string[];
        general?: string[]; // For non-field specific errors like insufficient funds
    };
}

/**
 * API Contract for GET /api/transactions
 */
export interface DashboardDataResponse {
    balance: string; // Backend sends monetary values as strings
    transactions: Paginated<Transaction>;
}
