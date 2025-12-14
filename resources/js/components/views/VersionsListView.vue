<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Версии изменений
            </h2>
            <p class="text-lg text-gray-600">Просмотр истории утвержденных версий системы</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Список версий -->
        <div v-else-if="versions.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-lg">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Версий нет</h3>
            <p class="text-gray-600">Версии будут создаваться автоматически при применении черновиков</p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="version in versions"
                :key="version.id"
                class="bg-white rounded-xl shadow-lg border-l-4 border-green-500 overflow-hidden"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="mb-3">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-2xl font-bold text-gray-900">
                                        Версия {{ version.version_number }}
                                    </h3>
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                        Утверждена
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mb-2">
                                    Утверждена {{ formatDate(version.approved_at || version.created_at) }}
                                </p>
                                <div v-if="version.changes_summary" class="text-sm text-gray-700 space-y-1 mt-3">
                                    <div v-if="version.changes_summary.subject_competency_changes" class="pl-4 border-l-2 border-blue-200">
                                        <div class="font-medium text-gray-800 mb-1">Изменения связей:</div>
                                        <div v-for="(change, index) in version.changes_summary.subject_competency_changes" :key="index" class="text-gray-600">
                                            {{ formatSubjectCompetencyChange(change) }}
                                        </div>
                                    </div>
                                    <div v-if="version.changes_summary.didactic_unit_changes" class="pl-4 border-l-2 border-purple-200 mt-2">
                                        <div class="font-medium text-gray-800 mb-1">Изменения ДЕ:</div>
                                        <div v-for="(change, index) in version.changes_summary.didactic_unit_changes" :key="index" class="text-gray-600">
                                            {{ formatDidacticUnitChange(change) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="viewVersion(version.id)"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Просмотреть
                            </button>
                            <button
                                @click="revertVersion(version.id)"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Отменить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно просмотра версии -->
        <div
            v-if="showVersionModal && selectedVersion"
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
            @click.self="closeVersionModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">
                        Версия {{ selectedVersion.version_number }}
                    </h3>
                    <button
                        @click="closeVersionModal"
                        class="text-gray-500 hover:text-gray-700 p-2 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Информация о версии:</h4>
                            <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
                                <div><span class="font-medium">Номер версии:</span> {{ selectedVersion.version_number }}</div>
                                <div><span class="font-medium">Утверждена:</span> {{ formatDate(selectedVersion.approved_at || selectedVersion.created_at) }}</div>
                                <div v-if="selectedVersion.draft_batch_id">
                                    <span class="font-medium">ID черновика:</span> {{ selectedVersion.draft_batch_id }}
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedVersion.changes_summary">
                            <h4 class="font-semibold text-gray-700 mb-2">Краткое описание изменений:</h4>
                            <div class="bg-blue-50 rounded-lg p-4 space-y-3">
                                <div v-if="selectedVersion.changes_summary.subject_competency_changes">
                                    <div class="font-medium text-gray-800 mb-2">Изменения связей:</div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(change, index) in selectedVersion.changes_summary.subject_competency_changes"
                                            :key="index"
                                            class="bg-white rounded p-3 text-sm"
                                        >
                                            {{ formatSubjectCompetencyChange(change) }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="selectedVersion.changes_summary.didactic_unit_changes">
                                    <div class="font-medium text-gray-800 mb-2">Изменения ДЕ:</div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(change, index) in selectedVersion.changes_summary.didactic_unit_changes"
                                            :key="index"
                                            class="bg-white rounded p-3 text-sm"
                                        >
                                            {{ formatDidacticUnitChange(change) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Статистика:</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ selectedVersion.snapshot_after?.subject_competencies?.length || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600">Связей после</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-2xl font-bold text-purple-600">
                                        {{ selectedVersion.snapshot_after?.didactic_units?.length || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600">ДЕ после</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useErrorHandler } from '../../composables/useErrorHandler';
import axios from 'axios';

const { handleError } = useErrorHandler();

const versions = ref([]);
const loading = ref(true);
const showVersionModal = ref(false);
const selectedVersion = ref(null);

const loadVersions = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/versions');
        versions.value = response.data || [];
    } catch (error) {
        handleError(error, 'Ошибка загрузки версий');
    } finally {
        loading.value = false;
    }
};

const viewVersion = async (versionId) => {
    try {
        const response = await axios.get(`/api/versions/${versionId}`);
        selectedVersion.value = response.data;
        showVersionModal.value = true;
    } catch (error) {
        handleError(error, 'Ошибка загрузки деталей версии');
    }
};

const closeVersionModal = () => {
    showVersionModal.value = false;
    selectedVersion.value = null;
};

const revertVersion = async (versionId) => {
    if (!confirm('Вы уверены, что хотите отменить эту версию? Состояние системы будет восстановлено до предыдущей версии, и будет создан черновик для отмены изменений.')) {
        return;
    }
    
    try {
        const response = await axios.post(`/api/versions/${versionId}/revert`);
        if (response && response.data && response.data.success) {
            // Показываем успешное сообщение
            const successMessage = response.data.message || 'Версия успешно отменена. Черновик создан.';
            console.log('Успех:', successMessage);
            // Можно использовать alert или другой способ показа сообщения
            // alert(successMessage);
            
            // Перезагружаем список версий
            await loadVersions();
            // Переходим к черновикам
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('change-view', { detail: 'drafts' }));
            }, 1000);
        } else {
            const errorMessage = response?.data?.message || 'Неизвестная ошибка при отмене версии';
            handleError(new Error(errorMessage), 'Ошибка отмены версии');
        }
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error?.message || 'Ошибка отмены версии';
        handleError(error, errorMessage);
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatSubjectCompetencyChange = (change) => {
    if (!change) return '';
    const subjectType = change.subject_type === 'modul' ? 'МДК' : 'ОП';
    const subjectName = change.subject_name || `ID: ${change.subject_id}`;
    const competencyName = change.competency_name || `ID: ${change.prof_competency_id}`;
    
    if (change.action === 'move') {
        const newCompetencyName = change.new_competency_name || `ID: ${change.new_prof_competency_id}`;
        return `${subjectType}: ${subjectName} → ПК: ${change.original_competency_name || competencyName} → ${newCompetencyName}`;
    } else if (change.action === 'remove') {
        return `Удаление: ${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    } else {
        return `${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    }
};

const formatDidacticUnitChange = (change) => {
    if (!change) return '';
    const subjectType = change.context?.subject_type === 'modul' ? 'МДК' : 'ОП';
    const subjectName = change.context?.subject_name || `ID: ${change.context?.subject_id}`;
    const competencyName = change.context?.competency_name || `ID: ${change.context?.prof_competency_id}`;
    
    if (change.action === 'merge') {
        return `Объединение ДЕ в "${change.new?.didactic_unit_name}" для ${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    } else if (change.action === 'replace') {
        const originalName = change.original?.didactic_unit_name || `ID: ${change.original?.didactic_unit_id}`;
        const newName = change.new?.didactic_unit_name || `ID: ${change.new?.didactic_unit_id}`;
        return `Замена ДЕ "${originalName}" на "${newName}" для ${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    } else if (change.action === 'create') {
        return `Создание ДЕ "${change.new?.didactic_unit_name}" для ${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    } else if (change.action === 'remove') {
        const originalName = change.original?.didactic_unit_name || `ID: ${change.original?.didactic_unit_id}`;
        return `Удаление ДЕ "${originalName}" для ${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    } else {
        return `${subjectType}: ${subjectName} ↔ ПК: ${competencyName}`;
    }
};

onMounted(() => {
    loadVersions();
});
</script>

<style scoped>
/* Стили для этой страницы */
</style>

