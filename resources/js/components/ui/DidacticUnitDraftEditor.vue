<template>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        {{ existingDraft ? 'Редактирование оценки ДЕ' : 'Оценка дидактических единиц' }}
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
                    <h4 class="font-semibold text-gray-700 mb-2">
                        {{ props.isMove ? 'Перенос связи (ДЕ из старого ПК):' : 'Текущая связь:' }}
                    </h4>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded text-sm font-medium"
                            :class="props.subjectType === 'modul' 
                                ? 'bg-blue-100 text-blue-800' 
                                : 'bg-purple-100 text-purple-800'"
                        >
                            {{ props.subjectType === 'modul' ? 'МДК' : 'ОП' }}
                        </span>
                        <span class="font-medium">{{ props.subjectName }}</span>
                        <span class="text-gray-400">↔</span>
                        <span class="font-medium">{{ props.competencyName }}</span>
                        <span v-if="props.isMove" class="text-xs text-gray-500 italic">
                            (новый ПК)
                        </span>
                    </div>
                    <div v-if="props.isMove" class="mt-2 text-xs text-gray-600 italic">
                        ДЕ загружены из предыдущего ПК. Выберите, какие оставить, заменить или удалить.
                    </div>
                </div>

                <!-- Текущие ДЕ по типам -->
                <div v-if="currentUnits.length > 0" class="space-y-4">
                    <h4 class="font-semibold text-gray-700">Текущие дидактические единицы:</h4>
                    <div v-for="type in availableTypes" :key="type" class="border-l-4 border-blue-400 pl-4">
                        <div class="font-medium text-sm text-gray-600 mb-2">{{ getTypeLabel(type) }}:</div>
                        <div class="space-y-2">
                            <div
                                v-for="unit in getUnitsByType(type)"
                                :key="unit.id"
                                class="flex items-center justify-between p-2 bg-white rounded border border-gray-200"
                            >
                                <span class="text-sm text-gray-700">{{ unit.name }}</span>
                                <button
                                    @click="selectUnitForAction(unit)"
                                    class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    Изменить
                                </button>
                            </div>
                            <div v-if="getUnitsByType(type).length === 0" class="text-xs text-gray-400 italic">
                                Нет ДЕ
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500 italic">
                    Нет дидактических единиц для этой связи
                </div>

                <!-- Редактор действий для выбранной ДЕ -->
                <div v-if="selectedUnit" class="border-t pt-6 space-y-4">
                    <h4 class="font-semibold text-gray-700">Действие для ДЕ "{{ selectedUnit.name }}":</h4>
                    
                    <!-- Выбор действия -->
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftAction === 'keep' 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftAction"
                                value="keep"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium">Оставить</div>
                                <div class="text-sm text-gray-600">Оставить ДЕ без изменений</div>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftAction === 'replace' 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftAction"
                                value="replace"
                                class="mr-3"
                            />
                            <div class="flex-1">
                                <div class="font-medium">Заменить</div>
                                <div class="text-sm text-gray-600">Заменить на другую ДЕ</div>
                                <div v-if="draftAction === 'replace'" class="mt-2 space-y-2">
                                    <select
                                        v-model="newUnitId"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="">Выберите ДЕ или создайте новую</option>
                                        <option
                                            v-for="unit in availableUnitsForType"
                                            :key="unit.id"
                                            :value="unit.id"
                                        >
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                    <div class="text-sm text-gray-600">Или создайте новую:</div>
                                    <input
                                        v-model="newUnitName"
                                        type="text"
                                        placeholder="Название новой ДЕ"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    <select
                                        v-if="newUnitName"
                                        v-model="newUnitType"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="">{{ getTypeLabel(selectedUnit.type) }}</option>
                                        <option value="know">Знать</option>
                                        <option value="be_able">Уметь</option>
                                        <option v-if="subjectType === 'modul'" value="have_practical_experience">Иметь практический опыт</option>
                                    </select>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="draftAction === 'remove' 
                                ? 'border-red-500 bg-red-50' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="draftAction"
                                value="remove"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium text-red-700">Удалить</div>
                                <div class="text-sm text-gray-600">Удалить эту ДЕ из связи</div>
                            </div>
                        </label>
                    </div>

                    <!-- Комментарий -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Комментарий:
                        </label>
                        <textarea
                            v-model="comment"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Опишите причину изменения..."
                        ></textarea>
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
                        v-if="selectedUnit"
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
import { useDrafts } from '../../composables/useDrafts';

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
    currentUnits: {
        type: Array,
        default: () => []
    },
    allDidacticUnits: {
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
    },
    isMove: {
        type: Boolean,
        default: false
    },
    originalCompetencyId: {
        type: Number,
        default: null
    },
    originalSubjectType: {
        type: String,
        default: null
    },
    originalSubjectId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

const { handleError } = useErrorHandler();
const { findDidacticUnitDraft } = useDrafts();

const showModal = ref(false);
const selectedUnit = ref(null);
const draftAction = ref('keep');
const newUnitId = ref(null);
const newUnitName = ref('');
const newUnitType = ref('');
const comment = ref('');

const typeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

const getTypeLabel = (type) => {
    return typeMapping[type] || type;
};

const availableTypes = computed(() => {
    if (props.subjectType === 'modul') {
        return ['know', 'be_able', 'have_practical_experience'];
    }
    return ['know', 'be_able'];
});

const getUnitsByType = (type) => {
    return props.currentUnits.filter(unit => unit.type === type);
};

const availableUnitsForType = computed(() => {
    if (!selectedUnit.value) return [];
    return props.allDidacticUnits.filter(unit => unit.type === selectedUnit.value.type);
});

const canSave = computed(() => {
    if (draftAction.value === 'replace') {
        return newUnitId.value || (newUnitName.value && newUnitType.value);
    }
    return true;
});

watch(() => props.show, (newVal) => {
    showModal.value = newVal;
    if (newVal) {
        // Загружаем данные существующего черновика или сбрасываем
        if (props.existingDraft) {
            // TODO: Загрузить данные существующего черновика
        } else {
            resetForm();
        }
    }
});

const resetForm = () => {
    selectedUnit.value = null;
    draftAction.value = 'keep';
    newUnitId.value = null;
    newUnitName.value = '';
    newUnitType.value = '';
    comment.value = '';
};

const selectUnitForAction = (unit) => {
    selectedUnit.value = unit;
    draftAction.value = 'keep';
    newUnitId.value = null;
    newUnitName.value = '';
    newUnitType.value = unit.type;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
    emit('close');
};

const saveDraft = async () => {
    try {
        // Если draftBatchId не передан, генерируем новый UUID
        let draftBatchId = props.draftBatchId;
        if (!draftBatchId) {
            // Генерируем UUID v4
            draftBatchId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        
        const data = {
            draft_batch_id: draftBatchId,
            subject_type: props.subjectType,
            subject_id: props.subjectId,
            prof_competency_id: props.competencyId,
            original_didactic_unit_id: selectedUnit.value?.id || null,
            action: draftAction.value,
            comment: comment.value || null
        };

        if (draftAction.value === 'replace') {
            if (newUnitId.value) {
                data.new_didactic_unit_id = newUnitId.value;
            } else if (newUnitName.value && newUnitType.value) {
                data.new_didactic_unit_name = newUnitName.value;
                data.new_didactic_unit_type = newUnitType.value;
            }
        }

        console.log('Saving didactic unit draft:', data);
        const response = await axios.post('/api/drafts/didactic-unit', data);
        console.log('Didactic unit draft saved:', response.data);
        
        emit('saved');
        closeModal();
    } catch (error) {
        console.error('Error saving didactic unit draft:', error);
        handleError(error, 'Ошибка сохранения черновика ДЕ');
    }
};
</script>

<style scoped>
/* Component styles */
</style>

