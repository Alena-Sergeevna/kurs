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
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-bold">
                                    {{ draft.draft_batch_id.substring(0, 8) }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Черновик изменений</h3>
                                    <p class="text-sm text-gray-500">
                                        Создан {{ formatDate(draft.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span>
                                    <span class="font-medium">Связи:</span> {{ draft.subject_competency_count }}
                                </span>
                                <span>
                                    <span class="font-medium">ДЕ:</span> {{ draft.didactic_unit_count }}
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
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useDrafts } from '../../composables/useDrafts';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import DraftPreviewModal from '../ui/DraftPreviewModal.vue';

const { drafts, loading, fetchDrafts, deleteDraft: deleteDraftApi, applyDraft: applyDraftApi } = useDrafts();
const { confirm } = useConfirmDialog();

const selectedDraftId = ref(null);
const showPreviewModal = ref(false);

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

onMounted(() => {
    fetchDrafts();
});
</script>

<style scoped>
/* Component styles */
</style>

