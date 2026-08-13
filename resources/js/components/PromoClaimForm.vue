<script setup>
import { ref } from 'vue';
import api from '../api.js';

const emit = defineEmits(['claimed', 'settled']);

const code = ref('');
const status = ref('idle'); // idle | loading | success | error
const message = ref('');

async function submit() {
    if (!code.value.trim()) {
        status.value = 'error';
        message.value = 'Поле не заповнене.';
        return;
    }

    status.value = 'loading';
    message.value = '';

    try {
        const { data } = await api.post('/promo/claim', { code: code.value });

        status.value = 'success';
        message.value = `${data.message} Нараховано: ${data.bonus_amount}. Новий баланс: ${data.balance}.`;
        code.value = '';
        emit('claimed', data);
    } catch (error) {
        status.value = 'error';

        const response = error.response;
        if (response?.status === 422 && response.data?.errors?.code) {
            message.value = response.data.errors.code[0];
        } else {
            message.value = response?.data?.message || 'Сталася помилка запиту.';
            emit('settled');
        }
    }
}
</script>

<template>
    <form class="space-y-3 rounded-lg border border-neutral-200 p-4 shadow-sm sm:p-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-neutral-800">Активація промокоду</h2>

        <div class="flex flex-col gap-2 sm:flex-row">
            <input
                v-model="code"
                type="text"
                placeholder="Введіть промокод"
                class="min-w-0 flex-1 rounded border border-neutral-300 px-3 py-2 text-sm uppercase focus:border-neutral-500 focus:outline-none"
            />
            <button
                type="submit"
                :disabled="status === 'loading'"
                class="w-full shrink-0 rounded bg-neutral-800 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 disabled:opacity-50 sm:w-auto"
            >
                {{ status === 'loading' ? 'Перевірка...' : 'Застосувати' }}
            </button>
        </div>

        <p v-if="status === 'success'" class="text-sm text-green-600">{{ message }}</p>
        <p v-else-if="status === 'error'" class="text-sm text-red-600">{{ message }}</p>
    </form>
</template>
