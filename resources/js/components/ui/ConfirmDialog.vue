<template>
    <Transition name="fade">
        <div
            v-if="isOpen"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
            @click.self="handleCancel"
        >
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ title }}</h3>
                    <p class="text-sm text-gray-600 mb-6">{{ message }}</p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="handleCancel"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            @click="handleConfirm"
                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                        >
                            Подтвердить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { useConfirmDialog } from '../../composables/useConfirmDialog';

const { isOpen, title, message, handleConfirm, handleCancel } = useConfirmDialog();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

