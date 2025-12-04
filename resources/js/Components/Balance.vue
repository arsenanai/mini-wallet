<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    balance: string;
}>();

const formattedBalance = computed(() => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD', // This can be made dynamic later if needed
    }).format(parseFloat(props.balance));
});
</script>

<template>
    <div class="space-y-2">
        <h3 class="text-lg font-medium text-gray-500">
            {{ $t('dashboard.balance') }}
        </h3>
        <p class="text-4xl font-bold text-gray-800" dusk="balance-amount">
            {{ formattedBalance }}
        </p>
    </div>
</template>
