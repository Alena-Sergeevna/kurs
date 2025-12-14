<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Черновики изменений
            </h2>
            <p class="text-lg text-gray-600">Просмотр и управление черновиками изменений связей и дидактических единиц</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Список черновиков -->
        <div v-else-if="drafts.length === 0" class="text-center py-20 bg-white rounded-2xl shadow-lg">
            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Черновиков нет</h3>
            <p class="text-gray-600">Создайте черновик изменений на странице управления связями</p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="draft in drafts"
                :key="draft.draft_batch_id"
                class="bg-white rounded-xl shadow-lg border-l-4 border-blue-500 overflow-hidden"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="mb-3">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">
                                    {{ draft.description && draft.description.length > 0 ? draft.description[0] : 'Черновик изменений' }}
                                </h3>
                                <p class="text-sm text-gray-500 mb-2">
                                    Создан {{ formatDate(draft.created_at) }}
                                </p>
                                <div v-if="draft.description && draft.description.length > 1" class="text-sm text-gray-600 space-y-1">
                                    <div v-for="(desc, index) in draft.description.slice(1)" :key="index" class="pl-4 border-l-2 border-gray-200">
                                        {{ desc }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <span v-if="draft.subject_competency_count > 0" class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                                    Изменений связей: {{ draft.subject_competency_count }}
                                </span>
                                <span v-if="draft.didactic_unit_count > 0" class="px-2 py-1 bg-purple-100 text-purple-800 rounded">
                                    Изменений ДЕ: {{ draft.didactic_unit_count }}
                                </span>
                                <span v-if="draft.actions && draft.actions.length > 0" class="text-xs text-gray-500">
                                    Действия: {{ draft.actions.join(', ') }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="viewDraft(draft.draft_batch_id)"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Просмотреть
                            </button>
                            <button
                                @click="deleteDraft(draft.draft_batch_id)"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно просмотра черновика -->
        <DraftPreviewModal
            v-if="selectedDraftId"
            :show="showPreviewModal"
            :draft-batch-id="selectedDraftId"
            @close="closePreviewModal"
            @apply="applyDraft"
            @edit-didactic-unit="handleEditDidacticUnit"
        />

        <!-- Редактор черновиков ДЕ -->
        <DidacticUnitDraftEditor
            :show="showDidacticUnitDraftEditor"
            :subject-type="didacticUnitDraftEditorData.subjectType"
            :subject-id="didacticUnitDraftEditorData.subjectId"
            :subject-name="didacticUnitDraftEditorData.subjectName"
            :competency-id="didacticUnitDraftEditorData.competencyId"
            :competency-name="didacticUnitDraftEditorData.competencyName"
            :current-units="didacticUnitDraftEditorData.currentUnits"
            :all-didactic-units="didacticUnitDraftEditorData.allDidacticUnits"
            :existing-draft="didacticUnitDraftEditorData.existingDraft"
            :draft-batch-id="didacticUnitDraftEditorData.draftBatchId"
            :is-move="didacticUnitDraftEditorData.isMove || false"
            :original-competency-id="didacticUnitDraftEditorData.originalCompetencyId || null"
            @close="closeDidacticUnitDraftEditor"
            @saved="onDidacticUnitDraftSaved"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useDrafts } from '../../composables/useDrafts';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useErrorHandler } from '../../composables/useErrorHandler';
import DraftPreviewModal from '../ui/DraftPreviewModal.vue';
import DidacticUnitDraftEditor from '../ui/DidacticUnitDraftEditor.vue';

const { drafts, loading, fetchDrafts, deleteDraft: deleteDraftApi, applyDraft: applyDraftApi, findDidacticUnitDraft } = useDrafts();
const { confirm } = useConfirmDialog();
const { handleError } = useErrorHandler();

const selectedDraftId = ref(null);
const showPreviewModal = ref(false);

// Для редактора черновиков ДЕ
const showDidacticUnitDraftEditor = ref(false);
const didacticUnitDraftEditorData = ref({
    subjectType: '',
    subjectId: null,
    subjectName: '',
    competencyId: null,
    competencyName: '',
    currentUnits: [],
    allDidacticUnits: [],
    existingDraft: null,
    draftBatchId: null
});

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

const viewDraft = (draftBatchId) => {
    selectedDraftId.value = draftBatchId;
    showPreviewModal.value = true;
};

const closePreviewModal = () => {
    showPreviewModal.value = false;
    selectedDraftId.value = null;
};

const deleteDraft = async (draftBatchId) => {
    const confirmed = await confirm({
        title: 'Удаление черновика',
        message: 'Вы уверены, что хотите удалить этот черновик?'
    });

    if (!confirmed) return;

    try {
        await deleteDraftApi(draftBatchId);
    } catch (error) {
        // Ошибка уже обработана в композабле
    }
};

const applyDraft = async (draftBatchId) => {
    try {
        await applyDraftApi(draftBatchId);
        closePreviewModal();
    } catch (error) {
        // Ошибка уже обработана в композабле
    }
};

const openDraftDidacticUnitsPage = (data) => {
    // Сохраняем параметры в localStorage
    let subjectType, subjectId, subjectName, competencyId, competencyName, isMove, originalCompetencyId;
    
    if (data.change && data.change.context) {
        subjectType = data.change.context.subject_type;
        subjectId = data.change.context.subject_id;
        subjectName = data.change.context.subject_name || '';
        competencyId = data.change.context.prof_competency_id;
        competencyName = data.change.context.competency_name || '';
        isMove = data.change.action === 'move';
        originalCompetencyId = isMove && data.change.original 
            ? data.change.original.prof_competency_id 
            : null;
    } else {
        // Fallback если структура другая
        subjectType = data.subjectType;
        subjectId = data.subjectId;
        subjectName = data.subjectName || '';
        competencyId = data.competencyId;
        competencyName = data.competencyName || '';
        isMove = data.isMove || false;
        originalCompetencyId = data.originalCompetencyId || null;
    }
    
    const params = {
        draftBatchId: data.draftBatchId || null,
        subjectType,
        subjectId,
        subjectName,
        competencyId,
        competencyName,
        isMove,
        originalCompetencyId
    };
    
    localStorage.setItem('draftDidacticUnitsParams', JSON.stringify(params));
    window.dispatchEvent(new CustomEvent('change-view', { detail: 'draft-didactic-units' }));
};

const handleEditDidacticUnit = async (data) => {
    // Перенаправляем на новую страницу вместо открытия модалки
    openDraftDidacticUnitsPage(data);
    try {
        // Если передан change, используем его данные
        // Если change = null, значит нужно создать новую оценку
        let subjectType, subjectId, subjectName, competencyId, competencyName;
        
        if (data.change) {
            // Редактируем существующий черновик ДЕ
            const change = data.change;
            subjectType = change.context.subject_type;
            subjectId = change.context.subject_id;
            subjectName = change.context.subject_name || `ID: ${change.context.subject_id}`;
            competencyId = change.context.prof_competency_id;
            competencyName = change.context.competency_name || `ID: ${change.context.prof_competency_id}`;
        } else {
            // Если change есть, но нет context, значит это новая оценка
            // Данные должны быть в change.context
            if (data.change && data.change.context) {
                const context = data.change.context;
                subjectType = context.subject_type;
                subjectId = context.subject_id;
                subjectName = context.subject_name || `ID: ${context.subject_id}`;
                competencyId = context.prof_competency_id;
                competencyName = context.competency_name || `ID: ${context.prof_competency_id}`;
            } else {
                handleError(new Error('Выберите связь для оценки ДЕ'), 'Для добавления оценки ДЕ выберите связь из списка изменений');
                return;
            }
        }

        // Загружаем все ДЕ
        const unitsResponse = await axios.get('/api/didactic-units');
        const allDidacticUnits = unitsResponse.data || [];

        // Загружаем текущие ДЕ для этой связи
        // Если это перенос (move), загружаем ДЕ из старой связи
        let currentUnits = [];
        try {
            // Проверяем, есть ли в data информация о переносе
            let loadSubjectType = subjectType;
            let loadSubjectId = subjectId;
            let loadCompetencyId = competencyId;
            
            // Если это перенос, нужно загрузить ДЕ из исходной связи
            if (data.change && data.change.action === 'move' && data.change.original) {
                // Загружаем ДЕ из исходной связи
                loadSubjectType = data.change.original.subject_type || subjectType;
                loadSubjectId = data.change.original.subject_id || subjectId;
                loadCompetencyId = data.change.original.prof_competency_id || competencyId;
                
                console.log('Loading units from original relation for move:', {
                    original: { loadSubjectType, loadSubjectId, loadCompetencyId },
                    new: { subjectType, subjectId, competencyId }
                });
            }
            
            const key = `${loadSubjectType}_${loadSubjectId}_${loadCompetencyId}`;
            const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
                subjects: [{
                    subject_type: loadSubjectType,
                    subject_id: loadSubjectId,
                    competency_id: loadCompetencyId
                }]
            });
            currentUnits = response.data[key] || [];
            
            console.log('Loaded units for relation:', {
                subjectType: loadSubjectType,
                subjectId: loadSubjectId,
                competencyId: loadCompetencyId,
                unitsCount: currentUnits.length,
                isMove: data.change?.action === 'move',
                newRelation: { subjectType, subjectId, competencyId }
            });
        } catch (e) {
            console.error('Error loading units:', e);
            currentUnits = [];
        }

        // Ищем существующий черновик ДЕ
        const existingDraft = await findDidacticUnitDraft(subjectType, subjectId, competencyId);

        // Определяем, является ли это переносом
        const isMove = data.change?.action === 'move';
        const originalCompetencyId = isMove && data.change?.original 
            ? data.change.original.prof_competency_id 
            : null;

        didacticUnitDraftEditorData.value = {
            subjectType,
            subjectId,
            subjectName,
            competencyId,
            competencyName,
            currentUnits,
            allDidacticUnits,
            existingDraft: existingDraft || null,
            draftBatchId: data.draftBatchId || null,
            isMove: isMove,
            originalCompetencyId: originalCompetencyId
        };

        showDidacticUnitDraftEditor.value = true;
        // Закрываем модальное окно предпросмотра
        closePreviewModal();
    } catch (error) {
        handleError(error, 'Ошибка открытия редактора черновиков ДЕ');
    }
};

const closeDidacticUnitDraftEditor = () => {
    showDidacticUnitDraftEditor.value = false;
};

const onDidacticUnitDraftSaved = async () => {
    // Обновляем список черновиков
    await fetchDrafts();
    // Можно снова открыть предпросмотр, если нужно
    if (selectedDraftId.value) {
        showPreviewModal.value = true;
    }
};

onMounted(() => {
    fetchDrafts();
});
</script>

<style scoped>
/* Component styles */
</style>

