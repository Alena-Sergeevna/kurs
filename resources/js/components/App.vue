<template>
    <div id="app" class="min-h-screen bg-gray-50">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ title }}
                </h1>
            </div>
        </header>
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <Welcome />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Welcome from './Welcome.vue';

const title = ref('Laravel + Vue.js');
</script>

<style scoped>
/* Component styles */
</style>

