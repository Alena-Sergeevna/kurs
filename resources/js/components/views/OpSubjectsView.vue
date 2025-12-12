<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Общеобразовательные дисциплины (ОП)</h2>
            <button
                @click="showModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                + Добавить ОП
            </button>
        </div>

        <div v-if="loading" class="text-center py-8">Загрузка...</div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <li v-for="subject in subjects" :key="subject.id" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">{{ subject.name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Компетенции: {{ subject.prof_competencies?.length || 0 }},
                                Дидактические единицы: {{ subject.didactic_units?.length || 0 }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="editSubject(subject)"
                                class="text-blue-600 hover:text-blue-900"
                            >
                                Редактировать
                            </button>
                            <button
                                @click="deleteSubject(subject.id)"
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
                        {{ editingSubject ? 'Редактировать ОП' : 'Создать ОП' }}
                    </h3>
                    <form @submit.prevent="saveSubject">
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Компетенции</label>
                            <select
                                v-model="form.prof_competency_ids"
                                multiple
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option v-for="comp in competencies" :key="comp.id" :value="comp.id">
                                    {{ comp.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Удерживайте Ctrl для выбора нескольких</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Дидактические единицы</label>
                            <select
                                v-model="form.didactic_unit_ids"
                                multiple
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option v-for="unit in didacticUnits" :key="unit.id" :value="unit.id">
                                    [{{ unit.type }}] {{ unit.name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Удерживайте Ctrl для выбора нескольких</p>
                            <p class="text-xs text-gray-500 mt-1">Для ОП доступны только типы: знать, уметь</p>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const subjects = ref([]);
const competencies = ref([]);
const didacticUnits = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingSubject = ref(null);
const form = ref({
    name: '',
    prof_competency_ids: [],
    didactic_unit_ids: []
});

const filteredDidacticUnits = computed(() => {
    return didacticUnits.value.filter(unit => 
        unit.type === 'знать' || unit.type === 'уметь'
    );
});

const fetchSubjects = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/op-subjects');
        subjects.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки ОП:', error);
        alert('Ошибка загрузки ОП');
    } finally {
        loading.value = false;
    }
};

const fetchCompetencies = async () => {
    try {
        const response = await axios.get('/api/prof-competencies');
        competencies.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки компетенций:', error);
    }
};

const fetchDidacticUnits = async () => {
    try {
        const response = await axios.get('/api/didactic-units');
        didacticUnits.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки дидактических единиц:', error);
    }
};

const saveSubject = async () => {
    try {
        const data = {
            name: form.value.name,
            prof_competency_ids: Array.from(form.value.prof_competency_ids).map(id => parseInt(id)),
            didactic_unit_ids: Array.from(form.value.didactic_unit_ids).map(id => parseInt(id))
        };

        if (editingSubject.value) {
            await axios.put(`/api/op-subjects/${editingSubject.value.id}`, data);
        } else {
            await axios.post('/api/op-subjects', data);
        }
        closeModal();
        fetchSubjects();
    } catch (error) {
        console.error('Ошибка сохранения ОП:', error);
        alert('Ошибка сохранения ОП');
    }
};

const editSubject = (subject) => {
    editingSubject.value = subject;
    form.value = {
        name: subject.name,
        prof_competency_ids: subject.prof_competencies?.map(c => c.id) || [],
        didactic_unit_ids: subject.didactic_units?.map(u => u.id) || []
    };
    showModal.value = true;
};

const deleteSubject = async (id) => {
    if (!confirm('Вы уверены, что хотите удалить эту ОП?')) return;
    
    try {
        await axios.delete(`/api/op-subjects/${id}`);
        fetchSubjects();
    } catch (error) {
        console.error('Ошибка удаления ОП:', error);
        alert('Ошибка удаления ОП');
    }
};

const closeModal = () => {
    showModal.value = false;
    editingSubject.value = null;
    form.value = {
        name: '',
        prof_competency_ids: [],
        didactic_unit_ids: []
    };
};

onMounted(() => {
    fetchSubjects();
    fetchCompetencies();
    fetchDidacticUnits();
});
</script>

<style scoped>
/* Component styles */
</style>

