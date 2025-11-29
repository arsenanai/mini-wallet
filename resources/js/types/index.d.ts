export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Transaction {
    id: number;
    reference_id: string;
    amount: number;
    commission_fee: number;
    type: 'transfer' | 'deposit';
    status: 'completed' | 'failed';
    created_at: string;
    sender: User;
    receiver: User;
}

export interface Paginated<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
