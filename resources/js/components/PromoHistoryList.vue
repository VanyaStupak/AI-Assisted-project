<script setup>
import { onMounted, ref, watch } from 'vue'
import api from '../api.js'
import ConfirmDialog from './ConfirmDialog.vue'

const props = defineProps({
    refreshKey: { type: Number, default: 0 },
})

const emit = defineEmits(['revoked'])

const claims = ref([])
const status = ref('idle') // idle | loading | error
const errorMessage = ref('')
const statusFilter = ref('')
const page = ref(1)
const meta = ref(null)
const revokingId = ref(null)
const claimPendingRevoke = ref(null)
const revokeError = ref('')

const STATUS_LABELS = {
    applied: 'Успішно застосовано',
    rejected: 'Відхилено',
    revoked: 'Скасовано',
}

const STATUS_BADGE_CLASSES = {
    applied: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    revoked: 'bg-neutral-200 text-neutral-600',
}

const REASON_LABELS = {
    not_found: 'Код не знайдено',
    expired: 'Термін дії минув',
    already_used: 'Вже використано раніше',
}

async function load() {
    status.value = 'loading'
    errorMessage.value = ''

    try {
        const params = { page: page.value }
        if (statusFilter.value) {
            params.status = statusFilter.value
        }

        const { data } = await api.get('/promo/history', { params })
        claims.value = data.data
        meta.value = data.meta
        status.value = 'idle'
    } catch (error) {
        status.value = 'error'
        errorMessage.value = error.response?.data?.message || 'Сталася помилка запиту.'
    }
}

function goToPage(newPage) {
    page.value = newPage
    load()
}

function askRevoke(claim) {
    revokeError.value = ''
    claimPendingRevoke.value = claim
}

function cancelRevoke() {
    if (revokingId.value) return
    claimPendingRevoke.value = null
}

async function confirmRevoke() {
    const claim = claimPendingRevoke.value
    if (!claim) return

    revokingId.value = claim.id
    revokeError.value = ''

    try {
        const { data } = await api.patch(`/promo/${claim.id}/revoke`)
        claim.status = 'revoked'
        claim.revoked_at = new Date().toISOString()
        emit('revoked', data)
        claimPendingRevoke.value = null
    } catch (error) {
        revokeError.value = error.response?.data?.message || 'Сталася помилка запиту.'
    } finally {
        revokingId.value = null
    }
}

watch(statusFilter, () => {
    page.value = 1
    load()
})

watch(() => props.refreshKey, () => {
    page.value = 1
    load()
})

onMounted(load)
</script>

<template>
    <div class="space-y-3 rounded-lg border border-neutral-200 p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-neutral-800">Історія промокодів</h2>
            <select
                v-model="statusFilter"
                class="w-full rounded border border-neutral-300 px-2 py-1.5 text-sm focus:border-neutral-500 focus:outline-none sm:w-auto sm:py-1"
            >
                <option value="">Усі статуси</option>
                <option value="applied">Успішно застосовано</option>
                <option value="rejected">Відхилено</option>
                <option value="revoked">Скасовано</option>
            </select>
        </div>

        <p v-if="status === 'loading'" class="text-sm text-neutral-500">Завантаження...</p>
        <p v-else-if="status === 'error'" class="text-sm text-red-600">{{ errorMessage }}</p>
        <p v-else-if="claims.length === 0" class="text-sm text-neutral-500">Історія порожня.</p>

        <ul v-else class="divide-y divide-neutral-100">
            <li v-for="claim in claims" :key="claim.id" class="flex flex-col gap-2 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <span class="font-mono font-medium">{{ claim.code }}</span>
                        <span class="text-xs text-neutral-400 sm:text-sm">{{ new Date(claim.created_at).toLocaleString('uk-UA') }}</span>
                    </div>
                    <div v-if="claim.reason" class="text-xs text-neutral-500">
                        {{ REASON_LABELS[claim.reason] || claim.reason }}
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end">
                    <span
                        v-if="claim.bonus_amount"
                        class="font-medium"
                        :class="claim.status === 'revoked' ? 'text-neutral-400 line-through' : 'text-green-600'"
                    >
                        +{{ claim.bonus_amount }}
                    </span>
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
                        :class="STATUS_BADGE_CLASSES[claim.status] || 'bg-neutral-100 text-neutral-600'"
                    >
                        {{ STATUS_LABELS[claim.status] || claim.status }}
                    </span>
                    <button
                        v-if="claim.status === 'applied'"
                        :disabled="revokingId === claim.id"
                        class="rounded border border-red-300 px-2 py-1 text-xs font-medium whitespace-nowrap text-red-600 hover:bg-red-50 disabled:opacity-50"
                        @click="askRevoke(claim)"
                    >
                        {{ revokingId === claim.id ? 'Скасування...' : 'Скасувати' }}
                    </button>
                </div>
            </li>
        </ul>

        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-center gap-2 pt-2">
            <button
                :disabled="meta.current_page <= 1"
                class="rounded border border-neutral-300 px-2 py-1 text-xs disabled:opacity-40"
                @click="goToPage(meta.current_page - 1)"
            >
                Назад
            </button>
            <span class="text-xs text-neutral-500">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
                :disabled="meta.current_page >= meta.last_page"
                class="rounded border border-neutral-300 px-2 py-1 text-xs disabled:opacity-40"
                @click="goToPage(meta.current_page + 1)"
            >
                Далі
            </button>
        </div>
    </div>

    <ConfirmDialog
        :open="claimPendingRevoke !== null"
        title="Скасування нарахування"
        :message="
            claimPendingRevoke
                ? `Скасувати нарахування ${claimPendingRevoke.bonus_amount} за промокодом «${claimPendingRevoke.code}»?`
                : ''
        "
        :error="revokeError"
        confirm-text="Скасувати нарахування"
        cancel-text="Залишити"
        :busy="revokingId !== null"
        @confirm="confirmRevoke"
        @cancel="cancelRevoke"
    />
</template>
