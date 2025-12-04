<script setup lang="ts">
import { Paginated, Transaction, User } from '@/types';
import {
    ArrowDownCircleIcon,
    ArrowUpCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/solid';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

defineProps<{
    transactions: Paginated<Transaction>;
}>();
const currentUser = usePage().props.auth.user as User;

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};

const { t } = useI18n();

const transactionDetails = (transaction: Transaction) => {
    const isSender = transaction.sender.id === currentUser.id;
    const isCompleted = transaction.status === 'completed';

    if (isSender) {
        return {
            icon: isCompleted ? ArrowUpCircleIcon : XCircleIcon,
            iconClass: isCompleted ? 'text-red-500' : 'text-gray-500',
            title: t('dashboard.sent_to', {
                name: transaction.receiver.name,
            }),
            amount: `-${formatCurrency(Number(transaction.amount) + Number(transaction.commission_fee))}`,
            amountClass: 'text-red-600',
        };
    } else {
        return {
            icon: isCompleted ? ArrowDownCircleIcon : XCircleIcon,
            iconClass: isCompleted ? 'text-green-500' : 'text-gray-500',
            title: t('dashboard.received_from', {
                name: transaction.sender.name,
            }),
            amount: `+${formatCurrency(transaction.amount)}`,
            amountClass: 'text-green-600',
        };
    }
};
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900">
            {{ $t('dashboard.transaction_history') }}
        </h3>
        <div class="space-y-3" dusk="transaction-history">
            <div
                v-if="transactions.data.length === 0"
                class="rounded-md bg-gray-50 p-4 text-center text-sm text-gray-500"
            >
                {{ $t('dashboard.no_transactions') }}
            </div>
            <div
                v-for="transaction in transactions.data"
                :key="transaction.reference_id"
                :dusk="`transaction-${transaction.id}`"
                data-testid="transaction-item"
                class="flex items-center justify-between rounded-md border bg-white p-3"
            >
                <!-- Call transaction Details once and store the result -->
                <template v-if="transactionDetails(transaction)">
                    <div class="flex items-center gap-3">
                        <component
                            :is="transactionDetails(transaction).icon"
                            class="h-8 w-8"
                            :class="transactionDetails(transaction).iconClass"
                        />
                        <div>
                            <p class="font-semibold text-gray-800">
                                {{ transactionDetails(transaction).title }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ formatDate(transaction.created_at) }}
                            </p>
                        </div>
                    </div>
                    <p
                        class="font-mono text-lg font-semibold"
                        :class="transactionDetails(transaction).amountClass"
                    >
                        {{ transactionDetails(transaction).amount }}
                    </p>
                </template>
            </div>
        </div>
        <!-- Pagination links will be added later if needed -->
    </div>
</template>
