<script setup lang="ts">
import Balance from '@/Components/Balance.vue';
import TransactionHistory from '@/Components/TransactionHistory.vue';
import TransferForm from '@/Components/TransferForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Paginated, Transaction, User } from '@/types';
import { DashboardDataResponse, TransferSuccessResponse } from '@/types/api';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<DashboardDataResponse>();

const user = usePage().props.auth.user as User;

// Create local reactive state from props to allow dynamic updates
const localBalance = ref(props.balance);
const localTransactions = ref<Paginated<Transaction>>(props.transactions);

const handleTransferSuccess = async (
    data:
        | TransferSuccessResponse
        | { balance: string; transaction: Transaction },
) => {
    localBalance.value = data.balance;

    const newTransaction = data.transaction;

    // Immediately add the transaction to the top of the list for instant UI feedback.
    localTransactions.value.data.unshift(newTransaction);

    // If the transaction from the event is incomplete (e.g., from a broadcast),
    // fetch the full details from the API.
    if (!newTransaction.sender || !newTransaction.receiver) {
        try {
            const response = await window.axios.get<{ data: Transaction }>(
                route('api.transactions.show', {
                    transaction: newTransaction.id,
                }),
            );
            // The API resource wraps the data in a 'data' property.
            // We find the transaction in the local list and replace it with the full data.
            const index = localTransactions.value.data.findIndex(
                (t) => t.id === newTransaction.id,
            );
            if (index !== -1)
                localTransactions.value.data[index] = response.data.data;
        } catch (e) {
            // Log error but don't crash the UI. The incomplete transaction is already displayed.
            console.error('Failed to fetch full transaction details:', e);
        }
    }
};

onMounted(() => {
    const channel = window.Echo.private(`App.Models.User.${user.id}`);

    // --- REAL-TIME DEBUGGING ---
    console.log(
        `[Echo] Subscribing to private channel: App.Models.User.${user.id}`,
    );

    channel.on('pusher:subscription_succeeded', () => {
        console.log('[Echo] Successfully subscribed to the private channel!');
    });

    channel.on('pusher:subscription_error', (error: any) => {
        console.error(
            `[Echo] Failed to subscribe to private channel. Error:`,
            error,
        );
    });
    // --- END DEBUGGING ---

    channel.listen(
        // The event name must match the fully qualified class name of the event.
        // If `broadcastAs()` is defined in the event, use that name prefixed with a dot.
        // The leading dot tells Echo not to prepend the app's namespace.
        '.TransactionCompleted',
        async (event: { balance: string; transaction: Transaction }) => {
            console.log('[Echo] TransactionCompleted event received:', event);
            handleTransferSuccess(event);
        },
    );
});

onUnmounted(() => {
    window.Echo.leave(`App.Models.User.${user.id}`);
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $t('dashboard.header') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Balance and Transfer Form Section -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <Balance :balance="localBalance" />
                    </div>
                    <!-- TransferForm Component will go here -->
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <TransferForm
                            @transfer-successful="handleTransferSuccess"
                        />
                    </div>
                </div>
                <!-- TransactionHistory Component will go here -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <TransactionHistory :transactions="localTransactions" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
