<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    recipient_email: '',
    amount: null as number | null,
});

const submit = () => {
    form.post(route('api.transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900">
            {{ $t('dashboard.send_money') }}
        </h3>
        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <InputLabel
                    for="recipient_email"
                    :value="$t('dashboard.recipient_email')"
                />
                <TextInput
                    id="recipient_email"
                    v-model="form.recipient_email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="email"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.recipient_email"
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
                <InputError class="mt-2" :message="form.errors.amount" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{ $t('dashboard.send_money') }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        {{ $t('dashboard.transfer_successful') }}
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
