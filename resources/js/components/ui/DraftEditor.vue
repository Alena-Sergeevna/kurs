<template>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        {{ props.existingDraft ? 'Редактирование оценки' : 'Редактирование связи' }}
                    </h3>
                    <button
                        @click="closeModal"
                        class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Текущая связь -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-700 mb-2">Текущая связь:</h4>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded text-sm font-medium"
                            :class="draftData.original_subject_type === 'modul' 
                                ? 'bg-blue-100 text-blue-800' 
                                : 'bg-purple-100 text-purple-800'"
                        >
                            {{ draftData.original_subject_type === 'modul' ? 'МДК' : 'ОП' }}
                        </span>
                        <span class="font-medium">{{ draftData.subjectName }}</span>
                        <span class="text-gray-400">↔</span>
                        <span class="font-medium">{{ draftData.competencyName }}</span>
                    </div>
                </div>

                <!-- Выбор действия -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Что делать с этой связью?</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftData.action === 'keep' 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftData.action"
                                value="keep"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium">Оставить связь</div>
                                <div class="text-sm text-gray-600">Оставить как есть, но можно изменить ДЕ</div>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftData.action === 'move' 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftData.action"
                                value="move"
                                class="mr-3"
                            />
                            <div class="flex-1">
                                <div class="font-medium">Перенести в другой ПК</div>
                                <div class="text-sm text-gray-600">МДК/ОП не относится к этому ПК</div>
                                <select
                                    v-if="draftData.action === 'move'"
                                    v-model="draftData.new_prof_competency_id"
                                    class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Выберите ПК</option>
                                    <option
                                        v-for="comp in competenciesList"
                                        :key="comp.id"
                                        :value="comp.id"
                                    >
                                        {{ comp.name }}
                                    </option>
                                    <option v-if="competenciesList.length === 0" disabled>
                                        Нет доступных ПК
                                    </option>
                                </select>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftData.action === 'remove' 
                                ? 'border-red-500 bg-red-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftData.action"
                                value="remove"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium text-red-700">Удалить связь</div>
                                <div class="text-sm text-gray-600">МДК/ОП не относится к этому ПК</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Комментарий -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Комментарий (почему нужно изменить):
                    </label>
                    <textarea
                        v-model="draftData.comment"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Опишите причину изменения..."
                    ></textarea>
                </div>

                <!-- Кнопка оценки ДЕ (только для переноса) -->
                <div v-if="draftData.action === 'move' && draftData.new_prof_competency_id && draftData.new_prof_competency_id !== draftData.original_prof_competency_id">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-blue-900 mb-1">Оценка дидактических единиц</h4>
                                <p class="text-sm text-blue-700">После переноса можно оценить ДЕ для нового ПК</p>
                            </div>
                            <button
                                @click="openDidacticUnitDraftEditor"
                                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors text-sm font-medium flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                Оценить ДЕ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Кнопки -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button
                        @click="closeModal"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Отмена
                    </button>
                    <button
                        @click="saveDraft"
                        :disabled="!canSave"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Сохранить в черновик
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { useErrorHandler } from '../../composables/useErrorHandler';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    subjectType: {
        type: String,
        required: true
    },
    subjectId: {
        type: Number,
        default: null
    },
    subjectName: {
        type: String,
        default: ''
    },
    competencyId: {
        type: Number,
        default: null
    },
    competencyName: {
        type: String,
        required: true
    },
    competencyModuleId: {
        type: Number,
        default: null
    },
    availableCompetencies: {
        type: Array,
        default: () => []
    },
    existingDraft: {
        type: Object,
        default: null
    },
    draftBatchId: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'saved', 'openDidacticUnitDraftEditor']);

const { handleError } = useErrorHandler();

const showModal = ref(false);

// Computed для получения списка компетенций (только внутри того же модуля)
const competenciesList = computed(() => {
    if (!props.availableCompetencies || !Array.isArray(props.availableCompetencies)) {
        return [];
    }
    return props.availableCompetencies.filter(comp => 
        comp.id !== draftData.value.original_prof_competency_id && 
        comp.id_module === props.competencyModuleId
    );
});
const draftData = ref({
    original_subject_type: props.subjectType || '',
    original_subject_id: props.subjectId || null,
    original_prof_competency_id: props.competencyId || null,
    new_subject_type: props.subjectType || '',
    new_subject_id: props.subjectId || null,
    new_prof_competency_id: props.competencyId || null,
    action: 'keep',
    comment: '',
    subjectName: props.subjectName || '',
    competencyName: props.competencyName || ''
});

const canSave = computed(() => {
    if (draftData.value.action === 'move') {
        return draftData.value.new_prof_competency_id && 
               draftData.value.new_prof_competency_id !== draftData.value.original_prof_competency_id;
    }
    return true;
});

watch(() => props.show, (newVal) => {
    showModal.value = newVal;
    if (newVal) {
        // Загружаем данные существующего черновика или создаем новые
        if (props.existingDraft) {
            draftData.value = {
                original_subject_type: props.existingDraft.original_subject_type || props.subjectType || '',
                original_subject_id: props.existingDraft.original_subject_id || props.subjectId || null,
                original_prof_competency_id: props.existingDraft.original_prof_competency_id || props.competencyId || null,
                new_subject_type: props.existingDraft.new_subject_type || props.subjectType || '',
                new_subject_id: props.existingDraft.new_subject_id || props.subjectId || null,
                new_prof_competency_id: props.existingDraft.new_prof_competency_id || props.competencyId || null,
                action: props.existingDraft.action || 'keep',
                comment: props.existingDraft.comment || '',
                subjectName: props.subjectName || '',
                competencyName: props.competencyName || ''
            };
        } else {
            // Сброс данных при открытии
            draftData.value = {
                original_subject_type: props.subjectType || '',
                original_subject_id: props.subjectId || null,
                original_prof_competency_id: props.competencyId || null,
                new_subject_type: props.subjectType || '',
                new_subject_id: props.subjectId || null,
                new_prof_competency_id: props.competencyId || null,
                action: 'keep',
                comment: '',
                subjectName: props.subjectName || '',
                competencyName: props.competencyName || ''
            };
        }
    }
});

const closeModal = () => {
    showModal.value = false;
    emit('close');
};

const saveDraft = async () => {
    try {
        const data = {
            ...draftData.value,
            new_prof_competency_id: draftData.value.action === 'move' 
                ? draftData.value.new_prof_competency_id 
                : draftData.value.original_prof_competency_id,
            draft_batch_id: props.draftBatchId || undefined
        };

        const response = await axios.post('/api/drafts/subject-competency', data);
        const savedDraftBatchId = response.data?.draft?.draft_batch_id || props.draftBatchId;
        
        emit('saved');
        
        // Если это перенос, предлагаем оценить ДЕ
        if (draftData.value.action === 'move' && draftData.value.new_prof_competency_id) {
            emit('openDidacticUnitDraftEditor', {
                subjectType: draftData.value.new_subject_type || draftData.value.original_subject_type,
                subjectId: draftData.value.new_subject_id || draftData.value.original_subject_id,
                subjectName: draftData.value.subjectName,
                competencyId: draftData.value.new_prof_competency_id, // Новый ПК
                competencyName: getNewCompetencyName(),
                moduleId: props.competencyModuleId,
                draftBatchId: savedDraftBatchId,
                // Информация о старом ПК для загрузки ДЕ
                originalCompetencyId: draftData.value.original_prof_competency_id,
                originalSubjectType: draftData.value.original_subject_type,
                originalSubjectId: draftData.value.original_subject_id
            });
        }
        
        closeModal();
    } catch (error) {
        handleError(error, 'Ошибка сохранения черновика');
    }
};

const openDidacticUnitDraftEditor = () => {
    // Эмитим событие с данными для нового ПК
    emit('openDidacticUnitDraftEditor', {
        subjectType: draftData.value.new_subject_type || draftData.value.original_subject_type,
        subjectId: draftData.value.new_subject_id || draftData.value.original_subject_id,
        subjectName: draftData.value.subjectName,
        competencyId: draftData.value.new_prof_competency_id, // Новый ПК
        competencyName: getNewCompetencyName(),
        moduleId: props.competencyModuleId,
        draftBatchId: props.draftBatchId,
        // Информация о старом ПК для загрузки ДЕ
        originalCompetencyId: draftData.value.original_prof_competency_id,
        originalSubjectType: draftData.value.original_subject_type,
        originalSubjectId: draftData.value.original_subject_id
    });
};

const getNewCompetencyName = () => {
    if (!draftData.value.new_prof_competency_id) return '';
    const comp = props.availableCompetencies?.find(c => c.id === draftData.value.new_prof_competency_id);
    return comp?.name || '';
};
</script>

<style scoped>
/* Component styles */
</style>

