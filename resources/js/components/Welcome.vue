<template>
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                Добро пожаловать в Laravel + Vue.js!
            </h2>
            <p class="text-gray-600 mb-4">
                Это пример компонента Vue.js, интегрированного с Laravel.
            </p>
            <div class="space-y-4">
                <Counter />
                <MessageForm />
            </div>
        </div>
    </div>
</template>

<script setup>
import Counter from './Counter.vue';
import MessageForm from './MessageForm.vue';
</script>

<style scoped>
/* Component styles */
</style>

