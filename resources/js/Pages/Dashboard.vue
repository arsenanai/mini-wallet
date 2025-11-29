<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Balance from '@/Components/Balance.vue';
import TransactionHistory from '@/Components/TransactionHistory.vue';
import TransferForm from '@/Components/TransferForm.vue';
import { Paginated, Transaction, User } from '@/types';

const props = defineProps<{
    balance: number;
    transactions: Paginated<Transaction>;
}>();

const user = usePage().props.auth.user as User;

// Create local reactive state from props to allow dynamic updates
const localBalance = ref(props.balance);
const localTransactions = ref(props.transactions);

onMounted(() => {
    window.Echo.private(`App.Models.User.${user.id}`).listen(
        'TransactionCompleted',
        (event: { balance: number; transaction: Transaction }) => {
            // Update balance with the new value from the event
            localBalance.value = event.balance;
            // Add the new transaction to the top of the list
            localTransactions.value.data.unshift(event.transaction);
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
                Mini Wallet Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Balance and Transfer Form Section -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div
                        class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                    >
                        <Balance :balance="localBalance" />
                    </div>
                    <!-- TransferForm Component will go here -->
                    <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <TransferForm />
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
