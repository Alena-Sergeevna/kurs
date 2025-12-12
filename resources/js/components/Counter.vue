<template>
    <div class="bg-blue-50 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Счётчик</h3>
        <div class="flex items-center space-x-4">
            <button
                @click="decrement"
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
            >
                -
            </button>
            <span class="text-2xl font-bold text-gray-900">{{ count }}</span>
            <button
                @click="increment"
                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
            >
                +
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-2">
            Нажато раз: {{ clicks }}
        </p>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const count = ref(0);
const clicks = ref(0);

const increment = () => {
    count.value++;
    clicks.value++;
};

const decrement = () => {
    count.value--;
    clicks.value++;
};
</script>

<style scoped>
/* Component styles */
</style>

