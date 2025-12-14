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
                        ДЕ загружены из предыдущего ПК (старой связи). Выберите, какие оставить без изменений, заменить на новые или удалить.
                    </div>
                    <div class="mt-2 text-xs text-blue-600 italic">
                        В черновике можно оценивать все ДЕ, даже утвержденные в исходной версии. Это новая версия связей.
                    </div>
                </div>

                <!-- Режим работы -->
                <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                    <h4 class="font-semibold text-gray-700 mb-3">Режим работы:</h4>
                    <div class="flex gap-4">
                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors flex-1"
                            :class="workMode === 'individual' 
                                ? 'border-blue-500 bg-white' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="workMode"
                                value="individual"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium">Оценить отдельно</div>
                                <div class="text-sm text-gray-600">Оценить каждую ДЕ отдельно</div>
                            </div>
                        </label>

                        <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer transition-colors flex-1"
                            :class="workMode === 'merge' 
                                ? 'border-purple-500 bg-white' 
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="radio"
                                v-model="workMode"
                                value="merge"
                                class="mr-3"
                            />
                            <div>
                                <div class="font-medium">Объединить несколько</div>
                                <div class="text-sm text-gray-600">Объединить несколько ДЕ в одну</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Режим объединения -->
                <div v-if="workMode === 'merge' && props.currentUnits && props.currentUnits.length > 0" class="space-y-4 border-t pt-6">
                    <h4 class="font-semibold text-gray-700">Выберите ДЕ для объединения (минимум 2):</h4>
                    <div v-for="type in availableTypes" :key="type" class="border-l-4 border-purple-400 pl-4">
                        <div class="font-medium text-sm text-gray-600 mb-2">{{ getTypeLabel(type) }}:</div>
                        <div class="space-y-2">
                            <label
                                v-for="unit in getUnitsByType(type)"
                                :key="unit.id"
                                class="flex items-start p-3 border-2 rounded-lg cursor-pointer transition-colors"
                                :class="selectedUnitIdsForMerge.includes(unit.id)
                                    ? 'border-purple-500 bg-purple-50'
                                    : 'border-gray-200 hover:border-gray-300'"
                            >
                                <input
                                    type="checkbox"
                                    :value="unit.id"
                                    v-model="selectedUnitIdsForMerge"
                                    class="mt-1 mr-3"
                                />
                                <div class="flex-1">
                                    <span class="text-sm text-gray-700">{{ unit.name }}</span>
                                </div>
                            </label>
                            <div v-if="getUnitsByType(type).length === 0" class="text-xs text-gray-400 italic">
                                Нет ДЕ
                            </div>
                        </div>
                    </div>

                    <!-- Форма создания объединенной ДЕ -->
                    <div v-if="selectedUnitIdsForMerge.length >= 2" class="bg-purple-50 rounded-lg p-4 border-2 border-purple-200 mt-4">
                        <h4 class="font-semibold text-gray-700 mb-3">Новая объединенная ДЕ:</h4>
                        
                        <!-- Предупреждение о разных типах -->
                        <div v-if="mergeTypeWarning" class="mb-3 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            {{ mergeTypeWarning }}
                        </div>
                        
                        <!-- Предупреждение о повторном объединении -->
                        <div v-if="hasAlreadyMergedUnits" class="mb-3 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            Внимание: Одна или несколько выбранных ДЕ уже объединены в другом черновике. Выберите другие ДЕ для объединения.
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Название:</label>
                                <input
                                    v-model="mergedUnit.name"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    placeholder="Введите название объединенной ДЕ"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Тип:</label>
                                <div class="p-2 bg-gray-100 border border-gray-300 rounded-md">
                                    <span class="text-sm font-medium text-gray-700">{{ getTypeLabel(getCommonTypeForMerge) }}</span>
                                    <span class="text-xs text-gray-500 ml-2 italic">(определяется автоматически на основе объединяемых ДЕ)</span>
                                </div>
                            </div>

                            <!-- Предпросмотр текстов объединяемых ДЕ -->
                            <div class="bg-white rounded p-3 border border-purple-200">
                                <div class="text-xs font-medium text-gray-600 mb-2">Тексты объединяемых ДЕ:</div>
                                <div class="space-y-1 text-sm text-gray-700">
                                    <div
                                        v-for="unit in selectedUnitsForMerge"
                                        :key="unit.id"
                                        class="pl-3 border-l-2 border-purple-300"
                                    >
                                        {{ unit.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Режим индивидуальной оценки -->
                <div v-if="workMode === 'individual'" class="space-y-4 border-t pt-6">
                    <h4 class="font-semibold text-gray-700">Оценка дидактических единиц:</h4>
                    <p class="text-sm text-gray-600 mb-4">Отметьте галочкой ДЕ, которые не требуют изменений, или выберите действие для ДЕ, которые нужно изменить.</p>
                    
                    <div v-if="!props.currentUnits || props.currentUnits.length === 0" class="text-sm text-gray-500 italic text-center py-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        Нет дидактических единиц для этой связи. Добавьте ДЕ на странице "Дидактические единицы".
                    </div>
                    
                    <template v-else>
                        <div v-for="type in availableTypes" :key="type" class="border-l-4 border-blue-400 pl-4">
                            <div class="font-medium text-sm text-gray-600 mb-2">{{ getTypeLabel(type) }}:</div>
                            <div class="space-y-2">
                                <div
                                    v-for="unit in getUnitsByType(type)"
                                    :key="unit.id"
                                    class="flex items-center justify-between p-3 bg-white rounded border-2 transition-colors"
                                    :class="getUnitEvaluationStatus(unit.id) === 'keep' 
                                        ? 'border-green-300 bg-green-50' 
                                        : getUnitEvaluationStatus(unit.id) && getUnitEvaluationStatus(unit.id) !== 'keep'
                                        ? 'border-yellow-300 bg-yellow-50' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                    style="pointer-events: auto;"
                                >
                                    <div class="flex items-center gap-3 flex-1" @click.stop>
                                        <label class="flex items-center cursor-pointer" @click.stop>
                                            <input
                                                type="checkbox"
                                                :checked="getUnitEvaluationStatus(unit.id) === 'keep'"
                                                @change="toggleUnitKeep(unit.id, $event.target.checked)"
                                                @click="(e) => e.stopPropagation()"
                                                class="w-5 h-5 text-green-600 rounded focus:ring-green-500 cursor-pointer"
                                            />
                                            <span class="ml-2 text-sm text-gray-700">{{ unit.name }}</span>
                                            <span v-if="unit.approved === true || unit.approved === 1 || unit.approved === '1'" class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-medium">
                                                Утверждена (в исходной версии)
                                            </span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2" @click.stop>
                                        <button
                                            @click="(e) => { e.stopPropagation(); selectUnitForAction(unit); }"
                                            class="px-3 py-1.5 text-xs rounded transition-colors cursor-pointer"
                                            :class="getUnitEvaluationStatus(unit.id) && getUnitEvaluationStatus(unit.id) !== 'keep'
                                                ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                        >
                                            <svg v-if="getUnitEvaluationStatus(unit.id) && getUnitEvaluationStatus(unit.id) !== 'keep'" class="w-3 h-3 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            {{ getUnitEvaluationStatus(unit.id) && getUnitEvaluationStatus(unit.id) !== 'keep' ? 'Изменить действие' : 'Выбрать действие' }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="getUnitsByType(type).length === 0" class="text-xs text-gray-400 italic">
                                    Нет ДЕ этого типа
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Редактор действий для выбранной ДЕ (индивидуальный режим) -->
                <div v-if="workMode === 'individual' && selectedUnit" class="border-t pt-6 space-y-4 bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-gray-700">Действие для ДЕ "{{ selectedUnit.name }}":</h4>
                        <button
                            @click="saveUnitAction"
                            class="px-3 py-1.5 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition-colors"
                        >
                            Сохранить действие
                        </button>
                    </div>
                    
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
                                    <div v-if="newUnitName && selectedUnit" class="mt-2 p-2 bg-gray-50 rounded border border-gray-200">
                                        <p class="text-xs text-gray-600">
                                            <span class="font-medium">Тип новой ДЕ:</span> 
                                            <span class="text-gray-800">{{ getTypeLabel(selectedUnit.type) }}</span>
                                            <span class="text-gray-500 italic ml-2">(совпадает с типом заменяемой ДЕ)</span>
                                        </p>
                                    </div>
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

                <!-- Комментарий (для режима объединения) -->
                <div v-if="workMode === 'merge' && selectedUnitIdsForMerge.length >= 2" class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Комментарий:
                    </label>
                    <textarea
                        v-model="mergeComment"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Опишите причину объединения..."
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
                        v-if="workMode === 'individual'"
                        @click="saveAllEvaluations"
                        :disabled="unitEvaluations.size === 0 && !selectedUnit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Сохранить все оценки ({{ unitEvaluations.size }})
                    </button>
                    <button
                        v-if="workMode === 'merge'"
                        @click="saveMergeDraft"
                        :disabled="!canSaveMerge || hasAlreadyMergedUnits"
                        class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Сохранить объединение в черновик
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
const workMode = ref('individual'); // 'individual' или 'merge'
const selectedUnit = ref(null);
const draftAction = ref('keep');
const newUnitId = ref(null);
const newUnitName = ref('');
const newUnitType = ref('');
const comment = ref('');

// Состояние оценки для каждой ДЕ: { unitId: { action: 'keep'|'replace'|'remove', ... } }
// Используем объект вместо Map для лучшей реактивности в Vue 3
const unitEvaluations = ref({});
const unitEvaluationsMap = computed({
    get: () => {
        const map = new Map();
        Object.entries(unitEvaluations.value).forEach(([key, value]) => {
            map.set(Number(key), value);
        });
        return map;
    },
    set: (newMap) => {
        const obj = {};
        newMap.forEach((value, key) => {
            obj[key] = value;
        });
        unitEvaluations.value = obj;
    }
});

// Для режима объединения
const selectedUnitIdsForMerge = ref([]);
const mergedUnit = ref({
    name: '',
    type: 'know'
});
const mergeComment = ref('');

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

const selectedUnitsForMerge = computed(() => {
    return props.currentUnits.filter(unit => selectedUnitIdsForMerge.value.includes(unit.id));
});

// Следим за изменением выбранных ДЕ для объединения и автоматически устанавливаем тип
watch(selectedUnitsForMerge, (units) => {
    if (units.length > 0) {
        const types = units.map(unit => unit.type);
        const uniqueTypes = [...new Set(types)];
        if (uniqueTypes.length === 1) {
            mergedUnit.value.type = types[0];
        }
    }
}, { deep: true });

// Определяем общий тип для объединения (все ДЕ должны быть одного типа)
const getCommonTypeForMerge = computed(() => {
    if (selectedUnitsForMerge.value.length === 0) {
        return 'know';
    }
    // Проверяем, что все выбранные ДЕ одного типа
    const types = selectedUnitsForMerge.value.map(unit => unit.type);
    const uniqueTypes = [...new Set(types)];
    if (uniqueTypes.length > 1) {
        // Если разные типы, возвращаем первый (но лучше предупредить пользователя)
        return types[0];
    }
    return types[0] || 'know';
});

// Предупреждение, если выбраны ДЕ разных типов
const mergeTypeWarning = computed(() => {
    if (selectedUnitsForMerge.value.length < 2) {
        return null;
    }
    const types = selectedUnitsForMerge.value.map(unit => unit.type);
    const uniqueTypes = [...new Set(types)];
    if (uniqueTypes.length > 1) {
        return 'Внимание: Выбраны ДЕ разных типов! Объединение возможно только для ДЕ одного типа.';
    }
    return null;
});

const canSave = computed(() => {
    if (draftAction.value === 'replace') {
        return newUnitId.value || (newUnitName.value && selectedUnit.value?.type);
    }
    return true;
});

// Проверка, что выбранные ДЕ еще не объединены
const hasAlreadyMergedUnits = computed(() => {
    if (selectedUnitIdsForMerge.value.length < 2) return false;
    
    // Проверяем, есть ли уже черновики объединения для этих ДЕ
    for (const unitId of selectedUnitIdsForMerge.value) {
        const evaluation = unitEvaluations.value[unitId];
        if (evaluation && evaluation.action === 'merge') {
            return true; // Одна из ДЕ уже объединена
        }
    }
    return false;
});

const canSaveMerge = computed(() => {
    return selectedUnitIdsForMerge.value.length >= 2 && 
           mergedUnit.value.name.trim().length > 0 &&
           !hasAlreadyMergedUnits.value;
});

// Получить статус оценки ДЕ
const getUnitEvaluationStatus = (unitId) => {
    const evaluation = unitEvaluations.value[unitId];
    const status = evaluation?.action || null;
    return status;
};

// Переключить галочку "Оставить" для ДЕ
const toggleUnitKeep = (unitId, checked) => {
    console.log('toggleUnitKeep called:', unitId, checked, 'current status:', getUnitEvaluationStatus(unitId));
    
    // В черновике можно оценивать любые ДЕ, даже утвержденные
    // Это новая версия связей, поэтому можно решать, оставлять ли старую ДЕ или нет
    
    if (checked) {
        // Устанавливаем действие "keep" для этой ДЕ - оставляем без изменений
        // Используем spread для правильной реактивности
        unitEvaluations.value = {
            ...unitEvaluations.value,
            [unitId]: {
                action: 'keep',
                original_didactic_unit_id: unitId
            }
        };
        console.log('Set keep for unit:', unitId, 'evaluations:', Object.keys(unitEvaluations.value).length);
    } else {
        // Удаляем оценку, пользователь должен выбрать действие (replace или remove)
        const newEvaluations = { ...unitEvaluations.value };
        delete newEvaluations[unitId];
        unitEvaluations.value = newEvaluations;
        console.log('Removed evaluation for unit:', unitId, 'evaluations:', Object.keys(unitEvaluations.value).length);
    }
};

// Загрузить существующие черновики для всех ДЕ (оптимизированная версия)
const loadExistingDrafts = async () => {
    if (!props.currentUnits || props.currentUnits.length === 0) return;
    
    try {
        // Загружаем все черновики одним запросом
        const response = await axios.get('/api/drafts');
        
        if (!response.data || response.data.length === 0) return;
        
        // Получаем уникальные draft_batch_id
        const uniqueBatchIds = [...new Set(response.data.map(d => d.draft_batch_id))];
        
        // Загружаем детали черновиков параллельно (только первые 20 для оптимизации)
        const draftDetailsPromises = uniqueBatchIds.slice(0, 20).map(batchId => 
            axios.get(`/api/drafts/${batchId}`).catch(() => null)
        );
        const draftDetailsResults = await Promise.all(draftDetailsPromises);
        
        console.log('loadExistingDrafts: searching for drafts for relation:', {
            subjectType: props.subjectType,
            subjectId: props.subjectId,
            competencyId: props.competencyId,
            isMove: props.isMove
        });
        
        // Обрабатываем результаты
        draftDetailsResults.forEach((draftDetails) => {
            if (!draftDetails?.data?.didactic_unit_changes) return;
            
            draftDetails.data.didactic_unit_changes.forEach(change => {
                // Проверяем, относится ли этот черновик к текущей связи
                // Для переноса (isMove) ищем черновики для НОВОЙ связи
                const matchesRelation = change.context.subject_type === props.subjectType &&
                    change.context.subject_id === props.subjectId &&
                    change.context.prof_competency_id === props.competencyId;
                
                if (matchesRelation) {
                    console.log('Found matching draft change:', change);
                    
                    // Обрабатываем как одиночные ДЕ, так и объединенные
                    if (change.original?.didactic_unit_id) {
                        // Одиночная ДЕ
                        const unitId = change.original.didactic_unit_id;
                        // Проверяем, что эта ДЕ есть в текущих ДЕ (для переноса это ДЕ из старой связи)
                        const unitExists = props.currentUnits.some(u => u.id === unitId);
                        if (unitExists) {
                            unitEvaluations.value[unitId] = {
                                action: change.action,
                                original_didactic_unit_id: unitId,
                                new_didactic_unit_id: change.new?.didactic_unit_id,
                                new_didactic_unit_name: change.new?.didactic_unit_name,
                                new_didactic_unit_type: change.new?.didactic_unit_type,
                                draft_batch_id: draftDetails.data.draft_batch_id || null,
                                comment: change.comment
                            };
                        }
                    } else if (change.original?.didactic_unit_ids && Array.isArray(change.original.didactic_unit_ids)) {
                        // Объединенные ДЕ - помечаем их как удаленные (merge)
                        change.original.didactic_unit_ids.forEach(unitId => {
                            // Проверяем, что эта ДЕ есть в текущих ДЕ
                            const unitExists = props.currentUnits.some(u => u.id === unitId);
                            if (unitExists) {
                                unitEvaluations.value[unitId] = {
                                    action: 'merge',
                                    original_didactic_unit_id: unitId,
                                    merged_into: change.new?.didactic_unit_name,
                                    draft_batch_id: draftDetails.data.draft_batch_id || null,
                                    comment: change.comment
                                };
                            }
                        });
                    }
                }
            });
        });
        console.log('loadExistingDrafts: loaded', Object.keys(unitEvaluations.value).length, 'evaluations for', props.currentUnits.length, 'units');
    } catch (error) {
        // Игнорируем ошибки загрузки
        console.error('Не удалось загрузить черновики:', error);
    }
};

watch(() => props.show, async (newVal) => {
    console.log('DidacticUnitDraftEditor watch show:', newVal, 'currentUnits:', props.currentUnits?.length, 'subjectType:', props.subjectType, 'subjectId:', props.subjectId, 'competencyId:', props.competencyId, 'isMove:', props.isMove);
    console.log('currentUnits array:', props.currentUnits);
    showModal.value = newVal;
    if (newVal) {
        // Очищаем предыдущие оценки
        unitEvaluations.value = {};
        
        // Сбрасываем форму
        resetForm();
        
        // Проверяем наличие currentUnits
        if (!props.currentUnits || props.currentUnits.length === 0) {
            console.warn('No currentUnits provided to DidacticUnitDraftEditor. Props:', {
                subjectType: props.subjectType,
                subjectId: props.subjectId,
                competencyId: props.competencyId,
                currentUnits: props.currentUnits,
                isMove: props.isMove
            });
        } else {
            console.log('Loading existing drafts for', props.currentUnits.length, 'units');
            // Загружаем существующие черновики для всех ДЕ
            await loadExistingDrafts();
        }
    } else {
        // При закрытии сбрасываем все
        unitEvaluations.value = {};
        selectedUnit.value = null;
    }
});

watch(() => workMode.value, () => {
    // При смене режима сбрасываем выбранные элементы
    if (workMode.value === 'individual') {
        selectedUnitIdsForMerge.value = [];
    } else {
        selectedUnit.value = null;
    }
});

const resetForm = () => {
    workMode.value = 'individual';
    selectedUnit.value = null;
    draftAction.value = 'keep';
    newUnitId.value = null;
    newUnitName.value = '';
    newUnitType.value = '';
    comment.value = '';
    selectedUnitIdsForMerge.value = [];
    mergedUnit.value = {
        name: '',
        type: 'know'
    };
    mergeComment.value = '';
};

const selectUnitForAction = (unit) => {
    selectedUnit.value = unit;
    
    // Загружаем существующую оценку, если есть
    const existingEvaluation = unitEvaluations.value[unit.id];
    if (existingEvaluation) {
        draftAction.value = existingEvaluation.action;
        newUnitId.value = existingEvaluation.new_didactic_unit_id || null;
        newUnitName.value = existingEvaluation.new_didactic_unit_name || '';
        newUnitType.value = existingEvaluation.new_didactic_unit_type || unit.type;
        comment.value = existingEvaluation.comment || '';
    } else {
        draftAction.value = 'keep';
        newUnitId.value = null;
        newUnitName.value = '';
        newUnitType.value = unit.type;
        comment.value = '';
    }
};

// Сохранить действие для выбранной ДЕ
const saveUnitAction = () => {
    if (!selectedUnit.value) return;
    
    // Если выбрано "keep", используем галочку
    if (draftAction.value === 'keep') {
        unitEvaluations.value[selectedUnit.value.id] = {
            action: 'keep',
            original_didactic_unit_id: selectedUnit.value.id
        };
    } else {
        const evaluation = {
            action: draftAction.value,
            original_didactic_unit_id: selectedUnit.value.id
        };
        
        if (draftAction.value === 'replace') {
            if (newUnitId.value) {
                evaluation.new_didactic_unit_id = newUnitId.value;
            } else if (newUnitName.value) {
                evaluation.new_didactic_unit_name = newUnitName.value;
                evaluation.new_didactic_unit_type = selectedUnit.value?.type || 'know';
            }
        }
        
        if (comment.value) {
            evaluation.comment = comment.value;
        }
        
        unitEvaluations.value[selectedUnit.value.id] = evaluation;
    }
    
    // Сбрасываем выбранную ДЕ
    selectedUnit.value = null;
    draftAction.value = 'keep';
    newUnitId.value = null;
    newUnitName.value = '';
    newUnitType.value = '';
    comment.value = '';
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
    emit('close');
};

const generateDraftBatchId = () => {
    let draftBatchId = props.draftBatchId;
    if (!draftBatchId) {
        // Генерируем UUID v4
        draftBatchId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    return draftBatchId;
};

// Сохранить все оценки ДЕ
const saveAllEvaluations = async () => {
    try {
        // Используем существующий draftBatchId, если есть, иначе создаем новый
        const draftBatchId = generateDraftBatchId();
        const promises = [];
        
        // Сохраняем оценки для всех ДЕ
        // Backend автоматически обновит существующие черновики или создаст новые
        for (const [unitId, evaluation] of Object.entries(unitEvaluations.value)) {
            const data = {
                draft_batch_id: draftBatchId,
                subject_type: props.subjectType,
                subject_id: props.subjectId,
                prof_competency_id: props.competencyId,
                original_didactic_unit_id: unitId,
                action: evaluation.action,
                comment: evaluation.comment || null
            };

            if (evaluation.action === 'replace') {
                if (evaluation.new_didactic_unit_id) {
                    data.new_didactic_unit_id = evaluation.new_didactic_unit_id;
                } else if (evaluation.new_didactic_unit_name) {
                    data.new_didactic_unit_name = evaluation.new_didactic_unit_name;
                    data.new_didactic_unit_type = evaluation.new_didactic_unit_type || 'know';
                }
            }

            promises.push(axios.post('/api/drafts/didactic-unit', data));
        }
        
        // Проверяем, что есть хотя бы одна оценка для сохранения
        if (promises.length === 0) {
            handleError(new Error('Нет оценок для сохранения'), 'Выберите хотя бы одну ДЕ для оценки');
            return;
        }
        
        await Promise.all(promises);
        
        emit('saved');
        closeModal();
    } catch (error) {
        console.error('Error saving didactic unit drafts:', error);
        handleError(error, 'Ошибка сохранения черновиков ДЕ');
    }
};

const saveDraft = async () => {
    // Используем новую функцию сохранения всех оценок
    await saveAllEvaluations();
};

const saveMergeDraft = async () => {
    try {
        const draftBatchId = generateDraftBatchId();
        
        const data = {
            draft_batch_id: draftBatchId,
            subject_type: props.subjectType,
            subject_id: props.subjectId,
            prof_competency_id: props.competencyId,
            original_didactic_unit_ids: selectedUnitIdsForMerge.value,
            new_didactic_unit_name: mergedUnit.value.name.trim(),
            new_didactic_unit_type: mergedUnit.value.type,
            action: 'merge',
            comment: mergeComment.value || null
        };

        console.log('Saving merge draft:', data);
        const response = await axios.post('/api/drafts/didactic-unit', data);
        console.log('Merge draft saved:', response.data);
        
        emit('saved');
        closeModal();
    } catch (error) {
        console.error('Error saving merge draft:', error);
        handleError(error, 'Ошибка сохранения объединения ДЕ');
    }
};
</script>

<style scoped>
/* Component styles */
</style>

