<script setup>
import { ref } from 'vue'
import api, { clearToken, getToken } from './api.js'
import LoginForm from './components/LoginForm.vue'
import PromoClaimForm from './components/PromoClaimForm.vue'
import PromoHistoryList from './components/PromoHistoryList.vue'

const user = ref(null)
const checkingSession = ref(true)
const historyRefreshKey = ref(0)

async function restoreSession() {
    if (!getToken()) {
        checkingSession.value = false
        return
    }

    try {
        const { data } = await api.get('/user')
        user.value = data
    } catch {
        clearToken()
    } finally {
        checkingSession.value = false
    }
}

function onLoggedIn(loggedInUser) {
    user.value = loggedInUser
}

function onClaimed(data) {
    if (user.value) {
        user.value.balance = data.balance
    }
    historyRefreshKey.value += 1
}

function onRevoked(data) {
    if (user.value) {
        user.value.balance = data.balance
    }
}

async function logout() {
    try {
        await api.post('/logout')
    } catch {
        // ignore network errors on logout
    }
    clearToken()
    user.value = null
}

restoreSession()
</script>

<template>
    <div class="mx-auto max-w-xl space-y-4 px-3 py-6 sm:space-y-6 sm:px-4 sm:py-10">
        <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-lg font-bold text-neutral-900 sm:text-xl">Бонуси за промокодом</h1>
            <div v-if="user" class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <span class="text-neutral-600">{{ user.name }} · Баланс: <strong>{{ user.balance }}</strong></span>
                <button class="text-neutral-500 underline" @click="logout">Вийти</button>
            </div>
        </header>

        <p v-if="checkingSession" class="text-sm text-neutral-500">Завантаження...</p>

        <LoginForm v-else-if="!user" @logged-in="onLoggedIn" />

        <template v-else>
            <PromoClaimForm @claimed="onClaimed" @settled="historyRefreshKey += 1" />
            <PromoHistoryList :refresh-key="historyRefreshKey" @revoked="onRevoked" />
        </template>
    </div>
</template>
