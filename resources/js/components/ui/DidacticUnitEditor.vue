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

            <!-- Поле ввода с автодополнением -->
            <div class="relative">
                <input
                    v-model="searchQueries[type]"
                    type="text"
                    :placeholder="`Введите текст для ${type}...`"
                    class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="searchQueries[type] ? 'border-blue-300' : 'border-gray-200'"
                    @input="searchUnits(type)"
                    @focus="showSuggestions[type] = true"
                    @blur="handleBlur(type)"
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
            </div>

            <!-- Кнопка добавления -->
            <button
                v-if="searchQueries[type] && searchQueries[type].trim()"
                @click="addNewUnit(type)"
                class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Добавить "{{ searchQueries[type] }}"
            </button>
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

// Удаляем watch на selectedUnits, так как updateModelValue вызывается напрямую при изменениях
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>

