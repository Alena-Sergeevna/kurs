<template>
    <div class="bg-green-50 p-4 rounded-lg mt-4">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Форма сообщения</h3>
        <form @submit.prevent="submitMessage" class="space-y-3">
            <div>
                <input
                    v-model="message"
                    type="text"
                    placeholder="Введите сообщение..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>
            <button
                type="submit"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
            >
                Отправить
            </button>
        </form>
        <div v-if="messages.length > 0" class="mt-4">
            <h4 class="font-medium text-gray-900 mb-2">Сообщения:</h4>
            <ul class="space-y-1">
                <li
                    v-for="(msg, index) in messages"
                    :key="index"
                    class="text-sm text-gray-700 bg-white p-2 rounded"
                >
                    {{ msg }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const message = ref('');
const messages = ref([]);

const submitMessage = () => {
    if (message.value.trim()) {
        messages.value.push(message.value);
        message.value = '';
    }
};
</script>

<style scoped>
/* Component styles */
</style>

