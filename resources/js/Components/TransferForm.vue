<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const form = ref({
    recipient_email: '',
    amount: null as number | null,
});

const errors = ref({
    recipient_email: '',
    amount: '',
});

const processing = ref(false);
const recentlySuccessful = ref(false);

const submit = async () => {
    processing.value = true;
    recentlySuccessful.value = false;
    errors.value = { recipient_email: '', amount: '' };

    try {
        await window.axios.post(route('api.transactions.store'), form.value);
        form.value.recipient_email = '';
        form.value.amount = null;
        recentlySuccessful.value = true;
        setTimeout(() => (recentlySuccessful.value = false), 2000);
    } catch (error: any) {
        if (error.response && error.response.status === 422) {
            const serverErrors = error.response.data.errors;
            if (serverErrors.recipient_email) {
                errors.value.recipient_email = serverErrors.recipient_email[0];
            }
            if (serverErrors.amount) {
                errors.value.amount = serverErrors.amount[0];
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
        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <InputLabel for="recipient_email" :value="$t('dashboard.recipient_email')" />
                <TextInput id="recipient_email" v-model="form.recipient_email" type="email" class="mt-1 block w-full"
                    required autocomplete="email" />
                <InputError class="mt-2" :message="errors.recipient_email" />
            </div>

            <div>
                <InputLabel for="amount" :value="$t('dashboard.amount')" />
                <TextInput id="amount" v-model="form.amount" type="number" step="0.01" min="0.01"
                    class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="errors.amount" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="processing">
                    {{ $t('dashboard.send_money') }}
                </PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="recentlySuccessful" class="text-sm text-gray-600">
                        {{ $t('dashboard.transfer_successful') }}
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
