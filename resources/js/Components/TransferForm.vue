<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { TransferRequestPayload, TransferSuccessResponse } from '@/types/api';
import { AxiosError, isAxiosError } from 'axios';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const emit = defineEmits<{
    (e: 'transfer-successful', data: TransferSuccessResponse): void;
}>();

const form = ref<TransferRequestPayload>({
    receiver_email: '',
    amount: null,
});

const { t } = useI18n();

const errors = ref({
    receiver_email: '',
    amount: '',
    general: '',
});

const processing = ref(false);
const recentlySuccessful = ref(false);

// Watch for the success state and automatically reset it after a delay.
// This is more declarative and reliable than a bare setTimeout in the submit handler.
watch(recentlySuccessful, (isSuccessful) => {
    if (isSuccessful) {
        setTimeout(() => (recentlySuccessful.value = false), 2000);
    }
});

// Frontend validation for the amount field for instant user feedback.
watch(
    () => form.value.amount,
    (newAmount) => {
        if (newAmount !== null && newAmount <= 0) {
            errors.value.amount = t('The amount must be greater than 0.');
        } else if (
            errors.value.amount === t('The amount must be greater than 0.')
        ) {
            errors.value.amount = ''; // Clear the error if the amount becomes valid
        }
    },
);

const submit = async () => {
    processing.value = true;
    recentlySuccessful.value = false;
    errors.value = { receiver_email: '', amount: '', general: '' };

    try {
        const response = await window.axios.post<TransferSuccessResponse>(
            route('api.transactions.store'),
            form.value,
        );

        // Emit the successful transfer data to the parent component
        emit('transfer-successful', response.data);

        form.value.receiver_email = '';
        form.value.amount = null;
        recentlySuccessful.value = true;
    } catch (error) {
        if (
            isAxiosError(error) &&
            error.response &&
            error.response.status === 422
        ) {
            const axiosError = error as AxiosError<{
                message: string;
                errors: Record<string, string[]>;
            }>;
            const responseData = axiosError.response?.data;
            if (responseData) {
                if (responseData.errors) {
                    const serverErrors = responseData.errors;
                    if (serverErrors.receiver_email) {
                        errors.value.receiver_email =
                            serverErrors.receiver_email[0];
                    }
                    if (serverErrors.amount) {
                        errors.value.amount = serverErrors.amount[0];
                    }
                } else if (responseData.message) {
                    errors.value.general = responseData.message;
                }
            }
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900">
            {{ $t('dashboard.send_money') }}
        </h3>
        <form
            class="space-y-6"
            dusk="transfer-form"
            novalidate
            @submit.prevent="submit"
        >
            <div>
                <InputLabel
                    for="receiver_email"
                    :value="$t('dashboard.recipient_email')"
                />
                <TextInput
                    id="receiver_email"
                    v-model="form.receiver_email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="email"
                />
                <InputError
                    class="mt-2"
                    :message="errors.receiver_email"
                    data-testid="receiver-email-error"
                />
            </div>

            <div>
                <InputLabel for="amount" :value="$t('dashboard.amount')" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="mt-1 block w-full"
                    required
                />
                <InputError
                    class="mt-2"
                    :message="errors.amount"
                    data-testid="amount-error"
                />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="processing" dusk="send-money-button">
                    {{ $t('dashboard.send_money') }}
                </PrimaryButton>

                <InputError
                    class="mt-2"
                    :message="errors.general"
                    data-testid="general-error"
                />

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="recentlySuccessful"
                        class="text-sm text-gray-600"
                        data-testid="success-message"
                    >
                        {{ $t('dashboard.transfer_successful') }}
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
