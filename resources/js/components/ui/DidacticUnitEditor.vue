<template>
    <div class="space-y-6">
        <div v-for="type in types" :key="type" class="space-y-3">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :class="typeColor[type]"></div>
                <span class="text-sm font-bold text-gray-700 uppercase">{{ type }}:</span>
            </div>
            
            <!-- Список выбранных единиц -->
            <div v-if="selectedUnits[type].length > 0" class="flex flex-wrap gap-2 mb-2">
                <span
                    v-for="(unit, index) in selectedUnits[type]"
                    :key="index"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium"
                    :class="typeColorClass[type]"
                >
                    {{ unit.name }}
                    <button
                        @click="removeUnit(type, index)"
                        class="text-gray-500 hover:text-gray-700 font-bold"
                    >
                        ×
                    </button>
                </span>
            </div>

            <!-- Режим ввода: переключатель между одним полем и массовым вводом -->
            <div class="flex items-center gap-2 mb-2">
                <button
                    @click="inputMode[type] = 'single'"
                    :class="inputMode[type] === 'single' 
                        ? 'bg-blue-500 text-white' 
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                >
                    Одна единица
                </button>
                <button
                    @click="inputMode[type] = 'bulk'"
                    :class="inputMode[type] === 'bulk' 
                        ? 'bg-blue-500 text-white' 
                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                >
                    Массовый ввод
                </button>
            </div>

            <!-- Режим одного поля ввода -->
            <div v-if="inputMode[type] === 'single'" class="relative">
                <input
                    v-model="searchQueries[type]"
                    type="text"
                    :placeholder="`Введите текст для ${type}...`"
                    class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="searchQueries[type] ? 'border-blue-300' : 'border-gray-200'"
                    @input="searchUnits(type)"
                    @focus="showSuggestions[type] = true"
                    @blur="handleBlur(type)"
                    @keyup.enter="addNewUnit(type)"
                />
                
                <!-- Подсказки -->
                <div
                    v-if="showSuggestions[type] && filteredSuggestions[type].length > 0"
                    class="absolute z-10 w-full mt-1 bg-white border-2 border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                >
                    <div
                        v-for="suggestion in filteredSuggestions[type]"
                        :key="suggestion.id"
                        @mousedown.prevent="useSuggestionText(type, suggestion.name)"
                        class="px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                    >
                        <div class="text-sm text-gray-700">{{ suggestion.name }}</div>
                        <div class="text-xs text-gray-400 mt-1">Нажмите, чтобы использовать текст</div>
                    </div>
                </div>

                <!-- Кнопка добавления одной единицы -->
                <button
                    v-if="searchQueries[type] && searchQueries[type].trim()"
                    @click="addNewUnit(type)"
                    class="w-full mt-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Добавить "{{ searchQueries[type] }}"
                </button>
            </div>

            <!-- Режим массового ввода -->
            <div v-else class="space-y-3">
                <div class="relative">
                    <textarea
                        v-model="bulkInputs[type]"
                        placeholder="Введите несколько единиц, каждую с новой строки..."
                        rows="8"
                        class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all font-mono text-sm resize-y"
                        :class="bulkInputs[type] ? 'border-blue-300' : 'border-gray-200'"
                    ></textarea>
                    <div class="mt-1 text-xs text-gray-500 space-y-1">
                        <div>💡 Скопируйте список единиц и вставьте сюда. Каждая строка будет отдельной единицей.</div>
                        <div class="bg-gray-100 px-2 py-1 rounded text-gray-600 font-mono">
                            Пример:<br>
                            Первая единица<br>
                            Вторая единица<br>
                            Третья единица
                        </div>
                    </div>
                </div>

                <!-- Предпросмотр единиц для добавления -->
                <div v-if="getBulkUnitsPreview(type).length > 0" class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="text-xs font-semibold text-gray-600 mb-2">
                        Будет добавлено единиц: {{ getBulkUnitsPreview(type).length }}
                    </div>
                    <div class="max-h-32 overflow-y-auto space-y-1">
                        <div
                            v-for="(unit, index) in getBulkUnitsPreview(type)"
                            :key="index"
                            class="text-xs text-gray-700 bg-white px-2 py-1 rounded border border-gray-200"
                        >
                            {{ index + 1 }}. {{ unit }}
                        </div>
                    </div>
                </div>

                <!-- Кнопка добавления всех единиц -->
                <button
                    v-if="getBulkUnitsPreview(type).length > 0"
                    @click="addBulkUnits(type)"
                    :disabled="isAddingBulk[type]"
                    class="w-full px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-lg transition-all duration-200 font-semibold shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
                >
                    <svg v-if="!isAddingBulk[type]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isAddingBulk[type] ? 'Добавление...' : `Добавить все (${getBulkUnitsPreview(type).length})` }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useErrorHandler } from '../../composables/useErrorHandler';

const { handleError } = useErrorHandler();

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    types: {
        type: Array,
        required: true
    },
    availableUnits: {
        type: Array,
        default: () => []
    },
    typeMapping: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const selectedUnits = ref({});
const searchQueries = ref({});
const showSuggestions = ref({});
const suggestions = ref({});
const inputMode = ref({}); // 'single' или 'bulk'
const bulkInputs = ref({}); // Массовый ввод для каждого типа
const isAddingBulk = ref({}); // Флаг загрузки для массового добавления

const typeColor = {
    'знать': 'bg-blue-500',
    'уметь': 'bg-purple-500',
    'иметь практический опыт': 'bg-green-500'
};

const typeColorClass = {
    'знать': 'bg-blue-100 text-blue-800 border border-blue-300',
    'уметь': 'bg-purple-100 text-purple-800 border border-purple-300',
    'иметь практический опыт': 'bg-green-100 text-green-800 border border-green-300'
};

// Инициализация
const initializeData = () => {
    props.types.forEach(type => {
        selectedUnits.value[type] = [];
        searchQueries.value[type] = '';
        showSuggestions.value[type] = false;
        suggestions.value[type] = [];
        inputMode.value[type] = 'single'; // По умолчанию режим одного поля
        bulkInputs.value[type] = '';
        isAddingBulk.value[type] = false;
    });
};

// Инициализация выбранных единиц из modelValue
const loadFromModelValue = (newValue) => {
    if (!newValue) {
        props.types.forEach(type => {
            selectedUnits.value[type] = [];
        });
        return;
    }
    
    props.types.forEach(type => {
        if (newValue[type] && Array.isArray(newValue[type])) {
            selectedUnits.value[type] = newValue[type].map(id => {
                const unit = props.availableUnits.find(u => u.id === id);
                return unit ? { id: unit.id, name: unit.name } : null;
            }).filter(Boolean);
        } else {
            selectedUnits.value[type] = [];
        }
    });
};

onMounted(() => {
    initializeData();
    loadFromModelValue(props.modelValue);
});

watch(() => props.modelValue, (newValue) => {
    // Убеждаемся, что selectedUnits инициализирован
    props.types.forEach(type => {
        if (!selectedUnits.value[type]) {
            selectedUnits.value[type] = [];
        }
    });
    
    if (!newValue) {
        props.types.forEach(type => {
            selectedUnits.value[type] = [];
        });
        return;
    }
    
    // Проверяем, действительно ли изменились значения, чтобы избежать циклов
    const currentIds = {};
    props.types.forEach(type => {
        const current = selectedUnits.value[type] || [];
        currentIds[type] = Array.isArray(current) ? current.map(u => u.id).sort().join(',') : '';
    });
    
    const newIds = {};
    props.types.forEach(type => {
        const ids = (newValue[type] && Array.isArray(newValue[type]) ? newValue[type] : []);
        newIds[type] = ids.sort().join(',');
    });
    
    const hasChanged = props.types.some(type => currentIds[type] !== newIds[type]);
    
    if (hasChanged) {
        loadFromModelValue(newValue);
    }
}, { immediate: true });

const filteredSuggestions = computed(() => {
    const result = {};
    props.types.forEach(type => {
        const query = searchQueries.value[type]?.toLowerCase() || '';
        const typeEn = props.typeMapping[type];
        const selected = selectedUnits.value[type] || [];
        const existingIds = Array.isArray(selected) ? selected.map(u => u.id) : [];
        
        result[type] = props.availableUnits
            .filter(unit => {
                return unit.type === typeEn 
                    && !existingIds.includes(unit.id)
                    && unit.name.toLowerCase().includes(query);
            })
            .slice(0, 5);
    });
    return result;
});

const searchUnits = (type) => {
    showSuggestions.value[type] = true;
};

const handleBlur = (type) => {
    setTimeout(() => {
        showSuggestions.value[type] = false;
    }, 200);
};

const useSuggestionText = (type, text) => {
    searchQueries.value[type] = text;
    showSuggestions.value[type] = false;
};

const addNewUnitFromText = async (type, text) => {
    if (!text || !text.trim()) return;

    const typeEn = props.typeMapping[type];
    const trimmedText = text.trim();
    
    // Всегда создаем новую единицу, даже если такая уже существует
    try {
        const response = await axios.post('/api/didactic-units', {
            type: typeEn,
            name: trimmedText
        });
        
        const newUnit = response.data;
        selectedUnits.value[type].push({
            id: newUnit.id,
            name: newUnit.name
        });
        updateModelValue();
    } catch (error) {
        console.error('Ошибка создания дидактической единицы:', error);
        handleError(error, 'Ошибка создания дидактической единицы');
        return;
    }
};

const addNewUnit = async (type) => {
    const text = searchQueries.value[type].trim();
    if (!text) return;

    await addNewUnitFromText(type, text);
    searchQueries.value[type] = '';
};

const removeUnit = (type, index) => {
    selectedUnits.value[type].splice(index, 1);
    updateModelValue();
};

const updateModelValue = () => {
    const newValue = {};
    props.types.forEach(type => {
        newValue[type] = selectedUnits.value[type].map(u => u.id);
    });
    emit('update:modelValue', newValue);
};

// Получить предпросмотр единиц из массового ввода
const getBulkUnitsPreview = (type) => {
    if (!bulkInputs.value[type]) return [];
    
    return bulkInputs.value[type]
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0);
};

// Добавить все единицы из массового ввода
const addBulkUnits = async (type) => {
    const units = getBulkUnitsPreview(type);
    if (units.length === 0) return;
    
    isAddingBulk.value[type] = true;
    
    try {
        const typeEn = props.typeMapping[type];
        const promises = units.map(text => 
            axios.post('/api/didactic-units', {
                type: typeEn,
                name: text.trim()
            }).then(response => ({ success: true, data: response.data }))
              .catch(error => ({ success: false, error, text: text.trim() }))
        );
        
        const results = await Promise.all(promises);
        
        // Разделяем успешные и неудачные
        const successful = results.filter(r => r.success).map(r => r.data);
        const failed = results.filter(r => !r.success);
        
        // Добавляем все успешно созданные единицы в выбранные
        successful.forEach(unit => {
            selectedUnits.value[type].push({
                id: unit.id,
                name: unit.name
            });
        });
        
        updateModelValue();
        
        // Очищаем поле массового ввода только если все успешно
        if (failed.length === 0) {
            bulkInputs.value[type] = '';
        } else {
            // Оставляем только неудачные единицы в поле ввода
            bulkInputs.value[type] = failed.map(f => f.text).join('\n');
        }
        
        // Показываем результаты
        if (successful.length > 0) {
            console.log(`Успешно добавлено ${successful.length} единиц типа "${type}"`);
        }
        if (failed.length > 0) {
            handleError(new Error(`Не удалось создать ${failed.length} единиц`), 
                `Ошибка создания ${failed.length} из ${units.length} единиц. Проверьте консоль для деталей.`);
            console.error('Неудачные единицы:', failed);
        }
    } catch (error) {
        console.error('Ошибка массового создания дидактических единиц:', error);
        handleError(error, `Ошибка создания единиц типа "${type}"`);
    } finally {
        isAddingBulk.value[type] = false;
    }
};

// Удаляем watch на selectedUnits, так как updateModelValue вызывается напрямую при изменениях
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

