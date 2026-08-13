<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Підтвердження' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Підтвердити' },
    cancelText: { type: String, default: 'Скасувати' },
    busy: { type: Boolean, default: false },
    error: { type: String, default: '' },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/40 p-4"
            @click.self="!busy && emit('cancel')"
        >
            <div class="w-full max-w-sm rounded-lg bg-white p-4 shadow-lg sm:p-6">
                <h3 class="text-base font-semibold text-neutral-800">{{ title }}</h3>
                <p class="mt-2 text-sm text-neutral-600">{{ message }}</p>
                <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        :disabled="busy"
                        class="w-full rounded border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 disabled:opacity-50 sm:w-auto"
                        @click="emit('cancel')"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        type="button"
                        :disabled="busy"
                        class="w-full rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 sm:w-auto"
                        @click="emit('confirm')"
                    >
                        {{ busy ? 'Зачекайте...' : confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
