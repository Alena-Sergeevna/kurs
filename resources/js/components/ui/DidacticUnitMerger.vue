<template>
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Объединение дидактических единиц</h3>
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
                <!-- Выбор ДЕ для объединения -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Выберите ДЕ для объединения (минимум 2):
                    </label>
                    <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        <label
                            v-for="unit in availableUnits"
                            :key="unit.id"
                            class="flex items-start p-3 border-2 rounded-lg cursor-pointer transition-colors"
                            :class="selectedUnitIds.includes(unit.id)
                                ? 'border-purple-500 bg-purple-50'
                                : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input
                                type="checkbox"
                                :value="unit.id"
                                v-model="selectedUnitIds"
                                class="mt-1 mr-3"
                            />
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold px-2 py-1 rounded"
                                        :class="getTypeClass(unit.type)"
                                    >
                                        {{ getTypeLabel(unit.type) }}
                                    </span>
                                    <span class="font-medium">{{ unit.name }}</span>
                                </div>
                                <div class="text-xs text-gray-500">ID: #{{ unit.id }}</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Новая объединенная ДЕ -->
                <div v-if="selectedUnitIds.length >= 2" class="bg-purple-50 rounded-lg p-4 border-2 border-purple-200">
                    <h4 class="font-semibold text-gray-700 mb-3">Новая объединенная ДЕ:</h4>
                    
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
                            <select
                                v-model="mergedUnit.type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                <option value="know">знать</option>
                                <option value="be_able">уметь</option>
                                <option value="have_practical_experience">иметь практический опыт</option>
                            </select>
                        </div>

                        <!-- Предпросмотр текстов объединяемых ДЕ -->
                        <div class="bg-white rounded p-3 border border-purple-200">
                            <div class="text-xs font-medium text-gray-600 mb-2">Тексты объединяемых ДЕ:</div>
                            <div class="space-y-1 text-sm text-gray-700">
                                <div
                                    v-for="unit in selectedUnits"
                                    :key="unit.id"
                                    class="pl-3 border-l-2 border-purple-300"
                                >
                                    {{ unit.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Комментарий -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Комментарий:
                    </label>
                    <textarea
                        v-model="comment"
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
                        @click="saveMerge"
                        :disabled="!canSave"
                        class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
    availableUnits: {
        type: Array,
        default: () => []
    },
    subjectType: {
        type: String,
        required: true
    },
    subjectId: {
        type: Number,
        required: true
    },
    competencyId: {
        type: Number,
        required: true
    },
    draftBatchId: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'saved']);

const { handleError } = useErrorHandler();

const showModal = ref(false);
const selectedUnitIds = ref([]);
const mergedUnit = ref({
    name: '',
    type: 'know'
});
const comment = ref('');

const typeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

const selectedUnits = computed(() => {
    return props.availableUnits.filter(unit => selectedUnitIds.value.includes(unit.id));
});

const canSave = computed(() => {
    return selectedUnitIds.value.length >= 2 && 
           mergedUnit.value.name.trim().length > 0;
});

watch(() => props.show, (newVal) => {
    showModal.value = newVal;
    if (newVal) {
        selectedUnitIds.value = [];
        mergedUnit.value = {
            name: '',
            type: 'know'
        };
        comment.value = '';
    }
});

const getTypeClass = (type) => {
    if (type === 'know') return 'bg-blue-100 text-blue-800';
    if (type === 'be_able') return 'bg-purple-100 text-purple-800';
    return 'bg-green-100 text-green-800';
};

const getTypeLabel = (type) => {
    return typeMapping[type] || type;
};

const closeModal = () => {
    showModal.value = false;
    emit('close');
};

const saveMerge = async () => {
    try {
        // Создаем черновик для объединения
        const draftData = {
            draft_batch_id: props.draftBatchId,
            subject_type: props.subjectType,
            subject_id: props.subjectId,
            prof_competency_id: props.competencyId,
            original_didactic_unit_ids: selectedUnitIds.value,
            new_didactic_unit_name: mergedUnit.value.name,
            new_didactic_unit_type: mergedUnit.value.type,
            action: 'merge',
            comment: comment.value
        };

        await axios.post('/api/drafts/didactic-unit', draftData);
        
        emit('saved');
        closeModal();
    } catch (error) {
        handleError(error, 'Ошибка сохранения объединения');
    }
};
</script>

<style scoped>
/* Component styles */
</style>

