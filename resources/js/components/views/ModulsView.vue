<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Модули</h2>
            <button
                @click="showModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                + Добавить модуль
            </button>
        </div>

        <div v-if="loading" class="text-center py-8">Загрузка...</div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <li v-for="modul in moduls" :key="modul.id" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ modul.name }}</h3>
                            <p class="text-sm text-gray-500">
                                МДК: {{ modul.modul_subjects?.length || 0 }},
                                Компетенции: {{ modul.prof_competencies?.length || 0 }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editModul(modul)"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Редактировать
                            </button>
                            <button
                                @click="deleteModul(modul.id)"
                                class="text-red-600 hover:text-red-900"
                            >
                                Удалить
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Modal для создания/редактирования -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ editingModul ? 'Редактировать модуль' : 'Создать модуль' }}
                    </h3>
                    <form @submit.prevent="saveModul">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Название</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                            >
                                Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const moduls = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingModul = ref(null);
const form = ref({ name: '' });

const fetchModuls = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/moduls');
        moduls.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки модулей:', error);
        alert('Ошибка загрузки модулей');
    } finally {
        loading.value = false;
    }
};

const saveModul = async () => {
    try {
        if (editingModul.value) {
            await axios.put(`/api/moduls/${editingModul.value.id}`, form.value);
        } else {
            await axios.post('/api/moduls', form.value);
        }
        closeModal();
        fetchModuls();
    } catch (error) {
        console.error('Ошибка сохранения модуля:', error);
        alert('Ошибка сохранения модуля');
    }
};

const editModul = (modul) => {
    editingModul.value = modul;
    form.value = { name: modul.name };
    showModal.value = true;
};

const deleteModul = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить этот модуль?')) return;
    
    try {
        await axios.delete(`/api/moduls/${id}`);
        fetchModuls();
    } catch (error) {
        console.error('Ошибка удаления модуля:', error);
        alert('Ошибка удаления модуля');
    }
};

const closeModal = () => {
    showModal.value = false;
    editingModul.value = null;
    form.value = { name: '' };
};

onMounted(() => {
    fetchModuls();
});
</script>

<style scoped>
/* Component styles */
</style>

