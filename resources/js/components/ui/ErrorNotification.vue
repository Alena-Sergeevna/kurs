<template>
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed top-4 right-4 z-50 max-w-md w-full bg-red-50 border-l-4 border-red-500 rounded-lg shadow-lg p-4"
        >
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-red-800">{{ message }}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button
                        @click="clear"
                        class="inline-flex text-red-500 hover:text-red-700 focus:outline-none"
                    >
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { useErrorHandler } from '../../composables/useErrorHandler';

const { errorMessage, showError, clearError } = useErrorHandler();

const message = computed(() => errorMessage.value);
const show = computed(() => showError.value);

const clear = () => {
    clearError();
};
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

