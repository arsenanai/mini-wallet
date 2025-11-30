<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600" data-testid="input-error">
            {{ message }}
        </p>
    </div>
</template>
