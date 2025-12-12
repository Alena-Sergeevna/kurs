<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div
                        v-if="isOpen"
                        class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col"
                    >
                        <!-- Заголовок -->
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-xl font-bold text-gray-900">{{ title }}</h3>
                            <button
                                @click="close"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Контент -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <slot></slot>
                        </div>

                        <!-- Футер -->
                        <div v-if="showFooter" class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                            <slot name="footer">
                                <button
                                    @click="close"
                                    class="px-6 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg border border-gray-200"
                                >
                                    Отмена
                                </button>
                            </slot>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Модальное окно'
    },
    showFooter: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:isOpen', 'close']);

const close = () => {
    emit('update:isOpen', false);
    emit('close');
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

