<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Междисциплинарные курсы (МДК)</h2>
            <button
                @click="showModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                + Добавить МДК
            </button>
        </div>

        <div v-if="loading" class="text-center py-8">Загрузка...</div>

        <div v-else class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                <li v-for="subject in subjects" :key="subject.id" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-900">{{ subject.name }}</h3>
                            <p class="text-sm text-gray-500">
                                Модуль: {{ subject.modul?.name || 'Не указан' }}
                            </p>
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
                        {{ editingSubject ? 'Редактировать МДК' : 'Создать МДК' }}
                    </h3>
                    <form @submit.prevent="saveSubject">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Название</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                :class="[
                                    'w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2',
                                    formErrors.name ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'
                                ]"
                            />
                            <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Модуль</label>
                            <select
                                v-model="form.id_module"
                                required
                                :class="[
                                    'w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2',
                                    formErrors.id_module ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'
                                ]"
                            >
                                <option value="">Выберите модуль</option>
                                <option v-for="modul in moduls" :key="modul.id" :value="modul.id">
                                    {{ modul.name }}
                                </option>
                            </select>
                            <p v-if="formErrors.id_module" class="mt-1 text-sm text-red-600">{{ formErrors.id_module }}</p>
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
import { onMounted } from 'vue';
import { useSubjectManagement } from '../../composables/useSubjectManagement';
import { useReferenceData } from '../../composables/useReferenceData';

const {
    subjects,
    loading,
    showModal,
    editingSubject,
    form,
    formErrors,
    fetchSubjects,
    saveSubject,
    editSubject,
    deleteSubject,
    closeModal
} = useSubjectManagement({
    apiEndpoint: '/api/modulsubjects',
    subjectName: 'МДК',
    getInitialForm: () => ({
        name: '',
        id_module: '',
        prof_competency_ids: [],
        didactic_unit_ids: []
    }),
    getFormData: (form) => ({
        name: form.name,
        id_module: form.id_module,
        prof_competency_ids: Array.from(form.prof_competency_ids).map(id => parseInt(id)),
        didactic_unit_ids: Array.from(form.didactic_unit_ids).map(id => parseInt(id))
    }),
    transformFormForEdit: (subject) => ({
        name: subject.name,
        id_module: subject.id_module,
        prof_competency_ids: subject.prof_competencies?.map(c => c.id) || [],
        didactic_unit_ids: subject.didactic_units?.map(u => u.id) || []
    })
});

const { moduls, competencies, didacticUnits, fetchModuls, fetchCompetencies, fetchDidacticUnits } = useReferenceData();

onMounted(async () => {
    await Promise.all([
        fetchSubjects(),
        fetchModuls(),
        fetchCompetencies(),
        fetchDidacticUnits()
    ]);
});
</script>

<style scoped>
/* Component styles */
</style>

