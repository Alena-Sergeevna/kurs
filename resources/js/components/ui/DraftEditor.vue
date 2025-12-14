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
                                <div class="text-sm text-gray-600">Оставить связь как есть</div>
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

const emit = defineEmits(['close', 'saved']);

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
        // Генерируем draft_batch_id, если его нет
        let draftBatchId = props.draftBatchId;
        if (!draftBatchId) {
            draftBatchId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        
        const data = {
            ...draftData.value,
            new_prof_competency_id: draftData.value.action === 'move' 
                ? draftData.value.new_prof_competency_id 
                : draftData.value.original_prof_competency_id,
            draft_batch_id: draftBatchId
        };

        console.log('Saving draft with data:', data);
        const response = await axios.post('/api/drafts/subject-competency', data);
        console.log('Draft saved, response:', response.data);
        
        emit('saved');
        closeModal();
    } catch (error) {
        console.error('Error saving draft:', error);
        handleError(error, 'Ошибка сохранения черновика');
    }
};

</script>

<style scoped>
/* Component styles */
</style>

