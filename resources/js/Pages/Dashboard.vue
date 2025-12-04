<script setup lang="ts">
import Balance from '@/Components/Balance.vue';
import TransactionHistory from '@/Components/TransactionHistory.vue';
import TransferForm from '@/Components/TransferForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Paginated, Transaction, User } from '@/types';
import { DashboardDataResponse } from '@/types/api';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<DashboardDataResponse>();

const user = usePage().props.auth.user as User;

// Create local reactive state from props to allow dynamic updates
const localBalance = ref(props.balance);
const localTransactions = ref<Paginated<Transaction>>(props.transactions);

const handleTransferSuccess = (data: {
    balance: string;
    transaction: Transaction;
}) => {
    localBalance.value = data.balance;
    localTransactions.value.data.unshift(data.transaction);
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

    channel.on('pusher:subscription_error', (status: number) => {
        console.error(
            `[Echo] Failed to subscribe to private channel with status: ${status}`,
        );
    });
    // --- END DEBUGGING ---

    channel.listen(
        '.TransactionCompleted', // Note: It's good practice to prefix the event name with a dot
        async (event: { balance: string; transaction: Transaction }) => {
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
