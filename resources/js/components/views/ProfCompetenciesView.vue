<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Профессиональные компетенции</h2>
            <button
                @click="showModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                + Добавить компетенцию
            </button>
        </div>

        <div v-if="loading" class="text-center py-8">Загрузка...</div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <li v-for="competency in competencies" :key="competency.id" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">{{ competency.name }}</h3>
                            <p class="text-sm text-gray-500">
                                Модуль: {{ competency.modul?.name || 'Не указан' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                МДК: {{ competency.modul_subjects?.length || 0 }},
                                ОП: {{ competency.op_subjects?.length || 0 }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editCompetency(competency)"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Редактировать
                            </button>
                            <button
                                @click="deleteCompetency(competency.id)"
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
                        {{ editingCompetency ? 'Редактировать компетенцию' : 'Создать компетенцию' }}
                    </h3>
                    <form @submit.prevent="saveCompetency">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Название</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Модуль</label>
                            <select
                                v-model="form.id_module"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Выберите модуль</option>
                                <option v-for="modul in moduls" :key="modul.id" :value="modul.id">
                                    {{ modul.name }}
                                </option>
                            </select>
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

const competencies = ref([]);
const moduls = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingCompetency = ref(null);
const form = ref({
    name: '',
    id_module: ''
});

const fetchCompetencies = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/prof-competencies');
        competencies.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки компетенций:', error);
        alert('Ошибка загрузки компетенций');
    } finally {
        loading.value = false;
    }
};

const fetchModuls = async () => {
    try {
        const response = await axios.get('/api/moduls');
        moduls.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки модулей:', error);
    }
};

const saveCompetency = async () => {
    try {
        if (editingCompetency.value) {
            await axios.put(`/api/prof-competencies/${editingCompetency.value.id}`, form.value);
        } else {
            await axios.post('/api/prof-competencies', form.value);
        }
        closeModal();
        fetchCompetencies();
    } catch (error) {
        console.error('Ошибка сохранения компетенции:', error);
        alert('Ошибка сохранения компетенции');
    }
};

const editCompetency = (competency) => {
    editingCompetency.value = competency;
    form.value = {
        name: competency.name,
        id_module: competency.id_module
    };
    showModal.value = true;
};

const deleteCompetency = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить эту компетенцию?')) return;
    
    try {
        await axios.delete(`/api/prof-competencies/${id}`);
        fetchCompetencies();
    } catch (error) {
        console.error('Ошибка удаления компетенции:', error);
        alert('Ошибка удаления компетенции');
    }
};

const closeModal = () => {
    showModal.value = false;
    editingCompetency.value = null;
    form.value = {
        name: '',
        id_module: ''
    };
};

onMounted(() => {
    fetchCompetencies();
    fetchModuls();
});
</script>

<style scoped>
/* Component styles */
</style>

