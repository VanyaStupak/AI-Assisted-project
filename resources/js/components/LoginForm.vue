<script setup>
import { ref } from 'vue'
import api, { setToken } from '../api.js'

const emit = defineEmits(['logged-in'])

const email = ref('player@example.com')
const password = ref('password')
const status = ref('idle') // idle | loading | error
const errorMessage = ref('')

async function submit() {
    status.value = 'loading'
    errorMessage.value = ''

    try {
        const { data } = await api.post('/login', { email: email.value, password: password.value })

        setToken(data.token)
        status.value = 'idle'
        emit('logged-in', data.user)
    } catch (error) {
        status.value = 'error'
        errorMessage.value =
            error.response?.data?.errors?.email?.[0] || error.response?.data?.message || 'Сталася помилка запиту.'
    }
}
</script>

<template>
    <form class="mx-auto max-w-sm space-y-4 rounded-lg border border-neutral-200 p-4 shadow-sm sm:p-6" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-neutral-800">Вхід</h2>

        <div>
            <label class="mb-1 block text-sm text-neutral-600">Email</label>
            <input
                v-model="email"
                type="email"
                required
                class="w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-500 focus:outline-none"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm text-neutral-600">Пароль</label>
            <input
                v-model="password"
                type="password"
                required
                class="w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-neutral-500 focus:outline-none"
            />
        </div>

        <p v-if="status === 'error'" class="text-sm text-red-600">{{ errorMessage }}</p>

        <button
            type="submit"
            :disabled="status === 'loading'"
            class="w-full rounded bg-neutral-800 px-3 py-2 text-sm font-medium text-white hover:bg-neutral-700 disabled:opacity-50"
        >
            {{ status === 'loading' ? 'Вхід...' : 'Увійти' }}
        </button>
    </form>
</template>
