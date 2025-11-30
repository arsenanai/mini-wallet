export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Transaction {
    id: string; // This is a UUID from the API response
    reference_id: string;
    amount: number;
    commission_fee: number;
    type: 'transfer' | 'deposit';
    status: 'pending' | 'completed' | 'failed';
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
        links: { url: string | null; label: string; active: boolean }[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface Page {
    auth: { user: User };
}
