<template>
    <div class="space-y-6 relative">
        <!-- Заголовок с кнопкой назад -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <button
                    @click="goBack"
                    class="mb-4 flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>{{ isMove ? 'Вернуться к черновикам' : 'Вернуться к дидактическим единицам' }}</span>
                </button>
                <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                    {{ isMove ? 'Управление ДЕ для новой связи' : 'Управление ДЕ для связи' }}
                </h2>
                <p class="text-lg text-gray-600">
                    {{ subjectName }} ↔ {{ competencyName }}
                </p>
            </div>
        </div>

        <!-- Информация о связи -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 rounded-lg text-sm font-medium"
                    :class="subjectType === 'modul' 
                        ? 'bg-blue-100 text-blue-800' 
                        : 'bg-purple-100 text-purple-800'"
                >
                    {{ subjectType === 'modul' ? 'МДК' : 'ОП' }}
                </span>
                <span class="font-semibold text-lg">{{ subjectName }}</span>
                <span class="text-gray-400 text-xl">↔</span>
                <span class="font-semibold text-lg">{{ competencyName }}</span>
                <span v-if="isMove" class="ml-auto px-3 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">
                    Перенос из старого ПК
                </span>
            </div>
            <div v-if="isMove" class="mt-3 text-sm text-gray-600 italic">
                ДЕ загружены из предыдущего ПК (старой связи). Выберите, какие оставить без изменений, заменить на новые или удалить.
            </div>
            <div class="mt-3 text-sm text-blue-600 italic">
                В черновике можно оценивать все ДЕ, даже утвержденные в исходной версии. Это новая версия связей.
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Основной контент -->
        <div v-else class="space-y-6 relative">
            <!-- Режим работы -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-blue-200">
                <h3 class="font-semibold text-gray-700 mb-4">Режим работы:</h3>
                <div class="flex gap-4">
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors flex-1"
                        :class="workMode === 'individual' 
                            ? 'border-blue-500 bg-blue-50' 
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

                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors flex-1"
                        :class="workMode === 'merge' 
                            ? 'border-purple-500 bg-purple-50' 
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

            <!-- Режим индивидуальной оценки -->
            <div v-if="workMode === 'individual'" class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-4">Оценка дидактических единиц:</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Отметьте галочкой ДЕ, которые не требуют изменений, или выберите действие для ДЕ, которые нужно изменить.
                </p>

                <div v-if="!currentUnits || currentUnits.length === 0" class="text-sm text-gray-500 italic text-center py-8 bg-yellow-50 border border-yellow-200 rounded-lg">
                    Нет дидактических единиц для этой связи.
                </div>

                <template v-else>
                    <div v-for="type in availableTypes" :key="type" class="mb-6 border-l-4 border-blue-400 pl-4">
                        <div class="font-medium text-sm text-gray-600 mb-3">{{ getTypeLabel(type) }}:</div>
                        <div class="space-y-3">
                            <div
                                v-for="unit in getUnitsByType(type)"
                                :key="unit.id"
                                class="flex items-center justify-between p-4 rounded-lg border-2 transition-colors"
                                :class="getUnitCardClasses(unit.id)"
                            >
                                <div class="flex items-center gap-4 flex-1">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :checked="getUnitEvaluationStatus(unit.id) === 'keep'"
                                            @change="toggleUnitKeep(unit.id, $event.target.checked)"
                                            class="w-5 h-5 text-green-600 rounded focus:ring-green-500 cursor-pointer"
                                        />
                                        <span class="ml-3 text-sm font-medium text-gray-700">{{ unit.name }}</span>
                                        <span v-if="unit.approved === true || unit.approved === 1 || unit.approved === '1'" class="ml-3 px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                            Утверждена (в исходной версии)
                                        </span>
                                        <span 
                                            v-if="getUnitEvaluationStatus(unit.id)"
                                            class="ml-3 px-3 py-1 rounded text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': getUnitEvaluationStatus(unit.id) === 'keep',
                                                'bg-purple-100 text-purple-800': getUnitEvaluationStatus(unit.id) === 'replace',
                                                'bg-red-100 text-red-800': getUnitEvaluationStatus(unit.id) === 'remove',
                                                'bg-orange-100 text-orange-800': getUnitEvaluationStatus(unit.id) === 'merge'
                                            }"
                                        >
                                            {{ getActionLabel(getUnitEvaluationStatus(unit.id)) }}
                                            <span v-if="isUnitEvaluated(unit.id)" class="ml-1 text-xs">(сохранено)</span>
                                        </span>
                                    </label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="selectUnitForAction(unit)"
                                        class="px-4 py-2 text-sm rounded-lg transition-colors cursor-pointer font-medium"
                                        :class="{
                                            'bg-green-500 hover:bg-green-600 text-white': getUnitEvaluationStatus(unit.id) === 'keep',
                                            'bg-purple-500 hover:bg-purple-600 text-white': getUnitEvaluationStatus(unit.id) === 'replace',
                                            'bg-red-500 hover:bg-red-600 text-white': getUnitEvaluationStatus(unit.id) === 'remove',
                                            'bg-orange-500 hover:bg-orange-600 text-white': getUnitEvaluationStatus(unit.id) === 'merge',
                                            'bg-blue-500 hover:bg-blue-600 text-white': !getUnitEvaluationStatus(unit.id)
                                        }"
                                    >
                                        <svg v-if="getUnitEvaluationStatus(unit.id)" class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        {{ getUnitEvaluationStatus(unit.id) ? 'Изменить действие' : 'Выбрать действие' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

        <!-- Боковая панель редактора действий -->
        <div 
            v-if="workMode === 'individual' && selectedUnit" 
            class="fixed right-0 top-0 h-full w-96 bg-white shadow-2xl z-50 border-l-2 overflow-y-auto transition-transform duration-300"
            :class="{
                'border-green-400': draftAction === 'keep',
                'border-purple-400': draftAction === 'replace',
                'border-red-400': draftAction === 'remove',
                'border-blue-400': !draftAction || draftAction === ''
            }"
        >
            <div 
                class="p-6 h-full flex flex-col"
                :class="{
                    'bg-green-50': draftAction === 'keep',
                    'bg-purple-50': draftAction === 'replace',
                    'bg-red-50': draftAction === 'remove',
                    'bg-blue-50': !draftAction || draftAction === ''
                }"
            >
                <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-300">
                    <div>
                        <h4 class="font-bold text-xl text-gray-800 mb-1">Редактирование действия</h4>
                        <p class="text-sm font-medium text-gray-600">"{{ selectedUnit.name }}"</p>
                    </div>
                    <button
                        @click="selectedUnit = null"
                        class="text-gray-500 hover:text-gray-700 p-2 hover:bg-white rounded-lg transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-3">Выберите действие:</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                @click="draftAction = 'keep'"
                                class="p-4 rounded-lg border-2 transition-all font-medium"
                                :class="draftAction === 'keep'
                                    ? 'border-green-500 bg-green-100 text-green-800 shadow-md'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-green-300 hover:bg-green-50'"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Оставить</span>
                                </div>
                            </button>
                            <button
                                @click="draftAction = 'replace'"
                                class="p-4 rounded-lg border-2 transition-all font-medium"
                                :class="draftAction === 'replace'
                                    ? 'border-purple-500 bg-purple-100 text-purple-800 shadow-md'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-purple-300 hover:bg-purple-50'"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <span>Заменить</span>
                                </div>
                            </button>
                            <button
                                @click="draftAction = 'remove'"
                                class="p-4 rounded-lg border-2 transition-all font-medium"
                                :class="draftAction === 'remove'
                                    ? 'border-red-500 bg-red-100 text-red-800 shadow-md'
                                    : 'border-gray-200 bg-white text-gray-700 hover:border-red-300 hover:bg-red-50'"
                            >
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Удалить</span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Замена на существующую ДЕ -->
                    <div v-if="draftAction === 'replace'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Выберите существующую ДЕ или создайте новую:</label>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Использовать существующую ДЕ:</label>
                                <select
                                    v-model="newUnitId"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">-- Выберите ДЕ --</option>
                                    <option
                                        v-for="unit in availableUnitsForType"
                                        :key="unit.id"
                                        :value="unit.id"
                                    >
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="text-center text-gray-400">или</div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Создать новую ДЕ:</label>
                                <input
                                    v-model="newUnitName"
                                    type="text"
                                    placeholder="Название новой ДЕ"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Комментарий -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий (обязателен для удаления):</label>
                        <textarea
                            v-model="comment"
                            rows="3"
                            placeholder="Опишите причину изменения..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :required="draftAction === 'remove'"
                        ></textarea>
                    </div>

                    <div class="flex flex-col gap-3 pt-4 border-t-2 border-gray-300 mt-4 sticky bottom-0 bg-inherit pb-6">
                        <button
                            @click="saveUnitAction"
                            class="w-full px-6 py-3 rounded-lg transition-colors font-medium shadow-md"
                            :class="{
                                'bg-green-500 hover:bg-green-600 text-white': draftAction === 'keep',
                                'bg-purple-500 hover:bg-purple-600 text-white': draftAction === 'replace',
                                'bg-red-500 hover:bg-red-600 text-white': draftAction === 'remove',
                                'bg-blue-500 hover:bg-blue-600 text-white': !draftAction || draftAction === ''
                            }"
                            :disabled="!canSaveAction"
                        >
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Сохранить действие
                            </span>
                        </button>
                        <button
                            @click="selectedUnit = null"
                            class="w-full px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors font-medium"
                        >
                            Отмена
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Затемнение фона при открытом редакторе - убрано, чтобы не перекрывать контент -->

            <!-- Режим объединения -->
            <div v-if="workMode === 'merge' && currentUnits && currentUnits.length > 0" class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-4">Выберите ДЕ для объединения (минимум 2):</h3>
                <div v-for="type in availableTypes" :key="type" class="mb-6 border-l-4 border-orange-400 pl-4">
                    <div class="font-medium text-sm text-gray-600 mb-3">{{ getTypeLabel(type) }}:</div>
                    <div class="space-y-2">
                        <label
                            v-for="unit in getUnitsByType(type)"
                            :key="unit.id"
                            class="flex items-start p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="selectedUnitIdsForMerge.includes(unit.id)
                                ? 'border-orange-500 bg-orange-50'
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
                                <span 
                                    v-if="getUnitEvaluationStatus(unit.id) === 'merge'"
                                    class="ml-2 px-2 py-0.5 bg-orange-100 text-orange-800 rounded text-xs font-medium"
                                >
                                    Объединено
                                    <span v-if="isUnitEvaluated(unit.id)" class="ml-1">(сохранено)</span>
                                </span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Форма создания объединенной ДЕ -->
                <div v-if="selectedUnitIdsForMerge.length >= 2" class="bg-orange-50 rounded-lg p-6 border-2 border-orange-300 mt-6">
                    <h4 class="font-semibold text-gray-700 mb-4">Новая объединенная ДЕ:</h4>
                    
                    <div v-if="mergeTypeWarning" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                        {{ mergeTypeWarning }}
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Название:</label>
                            <input
                                v-model="mergedUnit.name"
                                type="text"
                                placeholder="Название объединенной ДЕ"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Тип:</label>
                            <input
                                :value="mergedUnit.type ? getTypeLabel(mergedUnit.type) : ''"
                                type="text"
                                disabled
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий:</label>
                            <textarea
                                v-model="mergeComment"
                                rows="3"
                                placeholder="Опишите причину объединения..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                            ></textarea>
                        </div>
                        <button
                            @click="saveMergeDraft"
                            :disabled="!canSaveMerge || hasAlreadyMergedUnits"
                            class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-md"
                        >
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Сохранить объединение
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Кнопка сохранения всех изменений -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-2 border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-1">Сохранение изменений</h4>
                        <p class="text-sm text-gray-600">
                            Оценено ДЕ: {{ Object.keys(unitEvaluations).length }} из {{ currentUnits?.length || 0 }}
                        </p>
                    </div>
                    <button
                        @click="saveAllEvaluations"
                        :disabled="Object.keys(unitEvaluations).length === 0"
                        class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Сохранить все изменения
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useErrorHandler } from '../../composables/useErrorHandler';
import { useDrafts } from '../../composables/useDrafts';
import axios from 'axios';

const { handleError } = useErrorHandler();
const { findDidacticUnitDraft, getDraftDetails } = useDrafts();

// Props из App.vue через localStorage или query params
const draftBatchId = ref(null);
const subjectType = ref(null);
const subjectId = ref(null);
const subjectName = ref('');
const competencyId = ref(null);
const competencyName = ref('');
const isMove = ref(false);
const originalCompetencyId = ref(null);

// Состояние
const loading = ref(true);
const workMode = ref('individual');
const currentUnits = ref([]);
const allDidacticUnits = ref([]);
const unitEvaluations = ref({});
const selectedUnit = ref(null);
const draftAction = ref('keep');
const newUnitId = ref(null);
const newUnitName = ref('');
const newUnitType = ref('');
const comment = ref('');

// Для режима объединения
const selectedUnitIdsForMerge = ref([]);
const mergedUnit = ref({
    name: '',
    type: 'know'
});
const mergeComment = ref('');

// Типы ДЕ
const typeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

const availableTypes = computed(() => {
    if (subjectType.value === 'modul') {
        return ['know', 'be_able', 'have_practical_experience'];
    }
    return ['know', 'be_able'];
});

const getTypeLabel = (type) => {
    return typeMapping[type] || type;
};

const getUnitsByType = (type) => {
    return currentUnits.value.filter(unit => unit.type === type);
};

const availableUnitsForType = computed(() => {
    if (!selectedUnit.value) return [];
    return allDidacticUnits.value.filter(unit => unit.type === selectedUnit.value.type);
});

const selectedUnitsForMerge = computed(() => {
    return currentUnits.value.filter(unit => selectedUnitIdsForMerge.value.includes(unit.id));
});

// Проверка, оценена ли ДЕ (сохранена в черновике)
const isUnitEvaluated = (unitId) => {
    const evaluation = unitEvaluations.value[unitId];
    return evaluation && evaluation.saved === true;
};

// Получить цвет для действия
const getActionColor = (action) => {
    switch (action) {
        case 'keep':
            return 'green';
        case 'replace':
            return 'purple';
        case 'remove':
            return 'red';
        case 'merge':
            return 'orange';
        default:
            return 'gray';
    }
};

// Получить классы для карточки ДЕ в зависимости от действия
const getUnitCardClasses = (unitId) => {
    const status = getUnitEvaluationStatus(unitId);
    if (!status) {
        return 'border-gray-200 hover:border-gray-300 bg-gray-50';
    }
    
    const color = getActionColor(status);
    const colorMap = {
        green: 'border-green-300 bg-green-50',
        purple: 'border-purple-300 bg-purple-50',
        red: 'border-red-300 bg-red-50',
        orange: 'border-orange-300 bg-orange-50',
        gray: 'border-gray-300 bg-gray-50'
    };
    
    return colorMap[color] || colorMap.gray;
};

// Получить текст для действия
const getActionLabel = (action) => {
    switch (action) {
        case 'keep':
            return 'Оставить без изменений';
        case 'replace':
            return 'Заменить на новую';
        case 'remove':
            return 'Удалить';
        case 'merge':
            return 'Объединено';
        default:
            return '';
    }
};

// Получить статус оценки ДЕ
const getUnitEvaluationStatus = (unitId) => {
    const evaluation = unitEvaluations.value[unitId];
    return evaluation?.action || null;
};

// Переключить галочку "Оставить"
const toggleUnitKeep = (unitId, checked) => {
    // Разрешаем редактирование даже сохраненных изменений
    if (checked) {
        unitEvaluations.value = {
            ...unitEvaluations.value,
            [unitId]: {
                action: 'keep',
                original_didactic_unit_id: unitId,
                saved: unitEvaluations.value[unitId]?.saved || false // Сохраняем статус сохранения
            }
        };
    } else {
        const newEvaluations = { ...unitEvaluations.value };
        delete newEvaluations[unitId];
        unitEvaluations.value = newEvaluations;
    }
};

// Выбрать ДЕ для действия
const selectUnitForAction = (unit) => {
    // Разрешаем редактирование даже сохраненных изменений
    selectedUnit.value = unit;
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

// Сохранить действие для ДЕ
const saveUnitAction = async () => {
    if (!selectedUnit.value) return;
    
    // Сохраняем статус сохранения, если он был
    const wasSaved = unitEvaluations.value[selectedUnit.value.id]?.saved || false;

    const evaluation = {
        action: draftAction.value,
        original_didactic_unit_id: selectedUnit.value.id,
        saved: wasSaved // Сохраняем предыдущий статус
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

    // Сохраняем в локальное состояние
    unitEvaluations.value = {
        ...unitEvaluations.value,
        [selectedUnit.value.id]: evaluation
    };

    // Сохраняем на сервер
    try {
        const batchId = draftBatchId.value || generateDraftBatchId();
        const data = {
            draft_batch_id: batchId,
            subject_type: subjectType.value,
            subject_id: subjectId.value,
            prof_competency_id: competencyId.value,
            original_didactic_unit_id: selectedUnit.value.id,
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

        // Backend автоматически обновит существующий черновик или создаст новый
        await axios.post('/api/drafts/didactic-unit', data);
        
        // Помечаем как сохраненную (обновляем статус)
        unitEvaluations.value = {
            ...unitEvaluations.value,
            [selectedUnit.value.id]: {
                ...unitEvaluations.value[selectedUnit.value.id],
                saved: true
            }
        };
        
        selectedUnit.value = null;
        draftAction.value = 'keep';
        newUnitId.value = null;
        newUnitName.value = '';
        comment.value = '';
    } catch (error) {
        handleError(error, 'Ошибка сохранения действия для ДЕ');
    }
};

const canSaveAction = computed(() => {
    if (draftAction.value === 'replace') {
        return newUnitId.value || (newUnitName.value && selectedUnit.value?.type);
    }
    if (draftAction.value === 'remove') {
        return comment.value.trim().length > 0;
    }
    return true;
});

// Объединение
watch(selectedUnitsForMerge, (units) => {
    if (units.length === 0) {
        mergedUnit.value.type = 'know';
        return;
    }
    
    const types = units.map(u => u.type);
    const uniqueTypes = [...new Set(types)];
    if (uniqueTypes.length === 1) {
        mergedUnit.value.type = types[0];
    } else {
        mergedUnit.value.type = types[0];
    }
});

const mergeTypeWarning = computed(() => {
    if (selectedUnitIdsForMerge.value.length < 2) return null;
    
    const units = selectedUnitsForMerge.value.map(id => 
        currentUnits.value.find(u => u.id === id)
    ).filter(Boolean);
    
    const types = units.map(u => u.type);
    const uniqueTypes = [...new Set(types)];
    if (uniqueTypes.length > 1) {
        return 'Внимание: Выбраны ДЕ разных типов! Объединение возможно только для ДЕ одного типа.';
    }
    return null;
});

const hasAlreadyMergedUnits = computed(() => {
    // Разрешаем редактирование объединений
    return false;
});

const canSaveMerge = computed(() => {
    return selectedUnitIdsForMerge.value.length >= 2 && 
           mergedUnit.value.name.trim().length > 0 &&
           !hasAlreadyMergedUnits.value;
});

const saveMergeDraft = async () => {
    try {
        const batchId = draftBatchId.value || generateDraftBatchId();
        
        const data = {
            draft_batch_id: batchId,
            subject_type: subjectType.value,
            subject_id: subjectId.value,
            prof_competency_id: competencyId.value,
            original_didactic_unit_ids: selectedUnitIdsForMerge.value,
            new_didactic_unit_name: mergedUnit.value.name.trim(),
            new_didactic_unit_type: mergedUnit.value.type,
            action: 'merge',
            comment: mergeComment.value || null
        };

        await axios.post('/api/drafts/didactic-unit', data);
        
        // Помечаем все объединенные ДЕ как сохраненные
        selectedUnitIdsForMerge.value.forEach(unitId => {
            unitEvaluations.value = {
                ...unitEvaluations.value,
                [unitId]: {
                    action: 'merge',
                    original_didactic_unit_id: unitId,
                    merged_into: mergedUnit.value.name,
                    saved: true
                }
            };
        });
        
        selectedUnitIdsForMerge.value = [];
        mergedUnit.value = {
            name: '',
            type: 'know'
        };
        mergeComment.value = '';
    } catch (error) {
        handleError(error, 'Ошибка сохранения объединения ДЕ');
    }
};

// Сохранить все оценки
const saveAllEvaluations = async () => {
    try {
        const batchId = draftBatchId.value || generateDraftBatchId();
        const promises = [];
        
        for (const [unitId, evaluation] of Object.entries(unitEvaluations.value)) {
            if (evaluation.saved) continue; // Пропускаем уже сохраненные
            
            const data = {
                draft_batch_id: batchId,
                subject_type: subjectType.value,
                subject_id: subjectId.value,
                prof_competency_id: competencyId.value,
                original_didactic_unit_id: Number(unitId),
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
        
        if (promises.length === 0) {
            handleError(new Error('Нет изменений для сохранения'), 'Все изменения уже сохранены');
            return;
        }
        
        await Promise.all(promises);
        
        // Помечаем все как сохраненные
        Object.keys(unitEvaluations.value).forEach(unitId => {
            unitEvaluations.value[unitId].saved = true;
        });
        
        // Обновляем данные
        await loadData();
    } catch (error) {
        handleError(error, 'Ошибка сохранения изменений');
    }
};

const generateDraftBatchId = () => {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

// Загрузка данных
const loadData = async () => {
    loading.value = true;
    try {
        // Загружаем параметры из localStorage
        const params = JSON.parse(localStorage.getItem('draftDidacticUnitsParams') || '{}');
        draftBatchId.value = params.draftBatchId;
        subjectType.value = params.subjectType;
        subjectId.value = params.subjectId;
        subjectName.value = params.subjectName || '';
        competencyId.value = params.competencyId;
        competencyName.value = params.competencyName || '';
        isMove.value = params.isMove || false;
        originalCompetencyId.value = params.originalCompetencyId;

        if (!subjectType.value || !subjectId.value || !competencyId.value) {
            handleError(new Error('Не указаны параметры'), 'Не указаны параметры для загрузки ДЕ');
            return;
        }

        // Загружаем все ДЕ
        const unitsResponse = await axios.get('/api/didactic-units');
        allDidacticUnits.value = unitsResponse.data || [];

        // Загружаем текущие ДЕ для этой связи
        let loadSubjectType = subjectType.value;
        let loadSubjectId = subjectId.value;
        let loadCompetencyId = competencyId.value;
        
        if (isMove.value && originalCompetencyId.value) {
            loadCompetencyId = originalCompetencyId.value;
        }
        
        const key = `${loadSubjectType}_${loadSubjectId}_${loadCompetencyId}`;
        const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
            subjects: [{
                subject_type: loadSubjectType,
                subject_id: loadSubjectId,
                competency_id: loadCompetencyId
            }]
        });
        currentUnits.value = response.data[key] || [];

        // Загружаем существующие оценки
        await loadExistingDrafts();
    } catch (error) {
        handleError(error, 'Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

const loadExistingDrafts = async () => {
    if (!currentUnits.value || currentUnits.value.length === 0) return;
    
    try {
        // Если есть draftBatchId, загружаем черновики из этого батча
        if (draftBatchId.value) {
            // Для новых связей (isMove) используем getDraftDetails (с фильтрацией)
            if (isMove.value) {
                const draftDetails = await getDraftDetails(draftBatchId.value);
                if (draftDetails?.didactic_unit_changes) {
                    processDraftChanges(draftDetails.didactic_unit_changes, draftBatchId.value);
                }
            } else {
                // Для старых связей загружаем все черновики ДЕ для этой связи напрямую (без фильтрации)
                const response = await axios.get('/api/drafts/didactic-units-for-relation', {
                    params: {
                        subject_type: subjectType.value,
                        subject_id: subjectId.value,
                        prof_competency_id: competencyId.value
                    }
                });
                
                if (response.data?.didactic_unit_changes) {
                    // Фильтруем только те черновики, которые НЕ относятся к новым связям (после переноса)
                    // Для этого проверяем, нет ли в батче переноса на текущую связь
                    const allBatchIds = [...new Set(response.data.didactic_unit_changes.map(c => c.draft_batch_id))];
                    
                    for (const batchId of allBatchIds) {
                        const batchDetails = await axios.get(`/api/drafts/${batchId}`).catch(() => null);
                        if (!batchDetails?.data) continue;
                        
                        // Проверяем, что это НЕ батч с переносом на текущую связь (т.е. не новая связь)
                        const hasMoveToThisRelation = batchDetails.data.subject_competency_changes?.some(change => 
                            change.action === 'move' && 
                            change.new?.subject_type === subjectType.value &&
                            change.new?.subject_id === subjectId.value &&
                            change.new?.prof_competency_id === competencyId.value
                        );
                        
                        // Если это батч с переносом на текущую связь, пропускаем (это новая связь)
                        if (hasMoveToThisRelation) {
                            continue;
                        }
                        
                        // Обрабатываем черновики из этого батча
                        const relevantChanges = response.data.didactic_unit_changes.filter(change => 
                            change.draft_batch_id === batchId
                        );
                        
                        if (relevantChanges.length > 0) {
                            processDraftChanges(relevantChanges, batchId);
                        }
                    }
                }
            }
            return;
        }
        
        // Для старых связей (без draftBatchId) загружаем все черновики ДЕ для этой связи напрямую
        const response = await axios.get('/api/drafts/didactic-units-for-relation', {
            params: {
                subject_type: subjectType.value,
                subject_id: subjectId.value,
                prof_competency_id: competencyId.value
            }
        });
        
        if (response.data?.didactic_unit_changes && response.data.didactic_unit_changes.length > 0) {
            // Фильтруем только те черновики, которые НЕ относятся к новым связям (после переноса)
            const allBatchIds = [...new Set(response.data.didactic_unit_changes.map(c => c.draft_batch_id))];
            
            for (const batchId of allBatchIds) {
                const batchDetails = await axios.get(`/api/drafts/${batchId}`).catch(() => null);
                if (!batchDetails?.data) continue;
                
                // Проверяем, что это НЕ батч с переносом на текущую связь (т.е. не новая связь)
                const hasMoveToThisRelation = batchDetails.data.subject_competency_changes?.some(change => 
                    change.action === 'move' && 
                    change.new?.subject_type === subjectType.value &&
                    change.new?.subject_id === subjectId.value &&
                    change.new?.prof_competency_id === competencyId.value
                );
                
                // Если это батч с переносом на текущую связь, пропускаем (это новая связь)
                if (hasMoveToThisRelation) {
                    continue;
                }
                
                // Обрабатываем черновики из этого батча
                const relevantChanges = response.data.didactic_unit_changes.filter(change => 
                    change.draft_batch_id === batchId
                );
                
                if (relevantChanges.length > 0) {
                    processDraftChanges(relevantChanges, batchId);
                    if (!draftBatchId.value) {
                        draftBatchId.value = batchId;
                    }
                }
            }
        }
    } catch (error) {
        console.error('Не удалось загрузить черновики:', error);
    }
};

const processDraftChanges = (changes, batchId = null) => {
    changes.forEach(change => {
        if (change.original?.didactic_unit_id) {
            const unitId = change.original.didactic_unit_id;
            const unitExists = currentUnits.value.some(u => u.id === unitId);
            if (unitExists) {
                unitEvaluations.value = {
                    ...unitEvaluations.value,
                    [unitId]: {
                        action: change.action,
                        original_didactic_unit_id: unitId,
                        new_didactic_unit_id: change.new?.didactic_unit_id,
                        new_didactic_unit_name: change.new?.didactic_unit_name,
                        new_didactic_unit_type: change.new?.didactic_unit_type,
                        draft_batch_id: batchId || draftBatchId.value || null,
                        comment: change.comment,
                        saved: true // Помечаем как уже сохраненную
                    }
                };
            }
        } else if (change.original?.didactic_unit_ids && Array.isArray(change.original.didactic_unit_ids)) {
            change.original.didactic_unit_ids.forEach(unitId => {
                const unitExists = currentUnits.value.some(u => u.id === unitId);
                if (unitExists) {
                    unitEvaluations.value = {
                        ...unitEvaluations.value,
                        [unitId]: {
                            action: 'merge',
                            original_didactic_unit_id: unitId,
                            merged_into: change.new?.didactic_unit_name,
                            draft_batch_id: batchId || draftBatchId.value || null,
                            comment: change.comment,
                            saved: true
                        }
                    };
                }
            });
        }
    });
};

const goBack = () => {
    localStorage.removeItem('draftDidacticUnitsParams');
    // Для новых связей (isMove) возвращаемся к черновикам, для старых - к дидактическим единицам
    const targetView = isMove.value ? 'drafts' : 'didactic';
    window.dispatchEvent(new CustomEvent('change-view', { detail: targetView }));
};

onMounted(() => {
    loadData();
});

// Слушаем событие изменения view
window.addEventListener('change-view', (event) => {
    if (event.detail === 'drafts') {
        // Логика переключения будет в App.vue
    }
});
</script>

<style scoped>
/* Component styles */
</style>

