import { ref } from 'vue';
import axios from 'axios';
import { useErrorHandler } from './useErrorHandler';

/**
 * Композабл для работы с черновиками
 */
export function useDrafts() {
    const { handleError } = useErrorHandler();
    
    const drafts = ref([]);
    const loading = ref(false);
    const currentDraft = ref(null);

    /**
     * Загрузить список всех черновиков
     */
    const fetchDrafts = async () => {
        try {
            loading.value = true;
            const response = await axios.get('/api/drafts');
            drafts.value = response.data;
        } catch (error) {
            handleError(error, 'Ошибка загрузки черновиков');
        } finally {
            loading.value = false;
        }
    };

    /**
     * Получить детали черновика
     */
    const getDraftDetails = async (draftBatchId) => {
        try {
            loading.value = true;
            const response = await axios.get(`/api/drafts/${draftBatchId}`);
            currentDraft.value = response.data;
            return response.data;
        } catch (error) {
            handleError(error, 'Ошибка загрузки деталей черновика');
            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Получить предпросмотр изменений
     */
    const getPreview = async (draftBatchId) => {
        try {
            const response = await axios.get(`/api/drafts/${draftBatchId}/preview`);
            return response.data;
        } catch (error) {
            handleError(error, 'Ошибка получения предпросмотра');
            throw error;
        }
    };

    /**
     * Применить черновик
     */
    const applyDraft = async (draftBatchId, changesSummary = null) => {
        try {
            loading.value = true;
            const response = await axios.post(`/api/drafts/${draftBatchId}/apply`, {
                changes_summary: changesSummary
            });
            await fetchDrafts(); // Обновляем список
            return response.data;
        } catch (error) {
            handleError(error, 'Ошибка применения черновика');
            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Удалить черновик
     */
    const deleteDraft = async (draftBatchId) => {
        try {
            await axios.delete(`/api/drafts/${draftBatchId}`);
            await fetchDrafts(); // Обновляем список
        } catch (error) {
            handleError(error, 'Ошибка удаления черновика');
            throw error;
        }
    };

    /**
     * Найти существующий черновик для связи ПК-МДК/ОП
     */
    const findSubjectCompetencyDraft = async (subjectType, subjectId, competencyId) => {
        try {
            const response = await axios.get('/api/drafts/find', {
                params: {
                    subject_type: subjectType,
                    subject_id: subjectId,
                    prof_competency_id: competencyId
                }
            });
            return response.data.draft;
        } catch (error) {
            handleError(error, 'Ошибка поиска черновика');
            return null;
        }
    };

    /**
     * Найти существующий черновик для ДЕ
     */
    const findDidacticUnitDraft = async (subjectType, subjectId, competencyId) => {
        try {
            // Пока используем тот же endpoint, но можно создать отдельный
            const response = await axios.get('/api/drafts/find', {
                params: {
                    subject_type: subjectType,
                    subject_id: subjectId,
                    prof_competency_id: competencyId,
                    type: 'didactic_unit'
                }
            });
            return response.data.draft;
        } catch (error) {
            // Если endpoint не поддерживает тип, возвращаем null
            return null;
        }
    };

    return {
        drafts,
        loading,
        currentDraft,
        fetchDrafts,
        getDraftDetails,
        getPreview,
        applyDraft,
        deleteDraft,
        findSubjectCompetencyDraft,
        findDidacticUnitDraft
    };
}

