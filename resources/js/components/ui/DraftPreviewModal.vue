<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Предпросмотр изменений</h3>
                    <button
                        @click="close"
                        class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div v-if="previewLoading" class="flex justify-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>

                <div v-else>
                    <!-- Сообщение если нет изменений -->
                    <div v-if="!preview || (!preview.subject_competency_changes?.length && !preview.didactic_unit_changes?.length)" 
                         class="text-center py-10 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">Нет изменений для отображения</p>
                        <p class="text-sm mt-2">Черновик пуст или данные не загружены</p>
                    </div>

                    <!-- Изменения связей ПК-МДК/ОП -->
                    <div v-if="preview?.subject_competency_changes?.length > 0" class="mb-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-3">Изменения связей:</h4>
                        <div class="space-y-3">
                            <div
                                v-for="change in preview.subject_competency_changes"
                                :key="change.id"
                                class="bg-gray-50 rounded-lg p-4 border-l-4"
                                :class="getActionColorClass(change.action)"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-sm font-semibold px-2 py-1 rounded"
                                        :class="getActionBadgeClass(change.action)"
                                    >
                                        {{ getActionLabel(change.action) }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-700">
                                    <div v-if="change.action === 'move'">
                                        Перенос из ПК #{{ change.original.prof_competency_id }} 
                                        в ПК #{{ change.new.prof_competency_id }}
                                    </div>
                                    <div v-else-if="change.action === 'remove'">
                                        Удаление связи ПК #{{ change.original.prof_competency_id }}
                                    </div>
                                    <div v-else>
                                        Оставить связь ПК #{{ change.original.prof_competency_id }}
                                    </div>
                                </div>
                                <div v-if="change.comment" class="mt-2 text-sm text-gray-600 italic">
                                    "{{ change.comment }}"
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Изменения ДЕ -->
                    <div v-if="preview?.didactic_unit_changes?.length > 0">
                        <h4 class="text-lg font-bold text-gray-900 mb-3">Изменения дидактических единиц:</h4>
                        <div class="space-y-3">
                            <div
                                v-for="change in preview.didactic_unit_changes"
                                :key="change.id"
                                class="bg-gray-50 rounded-lg p-4 border-l-4"
                                :class="getActionColorClass(change.action)"
                            >
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-sm font-semibold px-2 py-1 rounded"
                                        :class="getActionBadgeClass(change.action)"
                                    >
                                        {{ getActionLabel(change.action) }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-700 space-y-1">
                                    <div v-if="change.action === 'merge'">
                                        Объединение ДЕ: {{ change.original.didactic_unit_ids?.join(', ') }}
                                        → Новая ДЕ: "{{ change.new.didactic_unit_name }}"
                                    </div>
                                    <div v-else-if="change.action === 'replace'">
                                        Замена ДЕ #{{ change.original.didactic_unit_id }}
                                        → {{ change.new.didactic_unit_id ? `ДЕ #${change.new.didactic_unit_id}` : `"${change.new.didactic_unit_name}"` }}
                                    </div>
                                    <div v-else-if="change.action === 'move'">
                                        Перенос ДЕ #{{ change.original.didactic_unit_id }}
                                        в другую связь
                                    </div>
                                    <div v-else-if="change.action === 'create'">
                                        Создание новой ДЕ: "{{ change.new.didactic_unit_name }}"
                                    </div>
                                    <div v-else-if="change.action === 'remove'">
                                        Удаление ДЕ #{{ change.original.didactic_unit_id }}
                                    </div>
                                </div>
                                <div v-if="change.comment" class="mt-2 text-sm text-gray-600 italic">
                                    "{{ change.comment }}"
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Кнопки -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button
                        @click="close"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Закрыть
                    </button>
                    <button
                        @click="apply"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Утвердить и применить
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDrafts } from '../../composables/useDrafts';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    draftBatchId: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close', 'apply']);

const { getPreview } = useDrafts();

const preview = ref(null);
const previewLoading = ref(false);

watch([() => props.show, () => props.draftBatchId], async ([newShow, newBatchId]) => {
    if (newShow && newBatchId) {
        await loadPreview();
    }
});

const loadPreview = async () => {
    try {
        previewLoading.value = true;
        preview.value = null; // Сбрасываем предыдущие данные
        
        const data = await getPreview(props.draftBatchId);
        
        // Отладочная информация
        console.log('Preview data received:', data);
        console.log('Subject competency changes:', data?.subject_competency_changes);
        console.log('Didactic unit changes:', data?.didactic_unit_changes);
        
        // Убеждаемся, что данные в правильном формате
        if (data && typeof data === 'object') {
            preview.value = {
                subject_competency_changes: Array.isArray(data.subject_competency_changes) 
                    ? data.subject_competency_changes 
                    : [],
                didactic_unit_changes: Array.isArray(data.didactic_unit_changes) 
                    ? data.didactic_unit_changes 
                    : []
            };
        } else {
            preview.value = {
                subject_competency_changes: [],
                didactic_unit_changes: []
            };
        }
        
        console.log('Preview value set:', preview.value);
    } catch (error) {
        console.error('Error loading preview:', error);
        preview.value = {
            subject_competency_changes: [],
            didactic_unit_changes: []
        };
        // Ошибка уже обработана в композабле
    } finally {
        previewLoading.value = false;
    }
};

const getActionLabel = (action) => {
    const labels = {
        'keep': 'Оставить',
        'move': 'Перенести',
        'remove': 'Удалить',
        'replace': 'Заменить',
        'merge': 'Объединить',
        'create': 'Создать',
    };
    return labels[action] || action;
};

const getActionColorClass = (action) => {
    if (action === 'remove') return 'border-red-500';
    if (action === 'move' || action === 'replace') return 'border-yellow-500';
    if (action === 'merge' || action === 'create') return 'border-purple-500';
    return 'border-blue-500';
};

const getActionBadgeClass = (action) => {
    if (action === 'remove') return 'bg-red-100 text-red-800';
    if (action === 'move' || action === 'replace') return 'bg-yellow-100 text-yellow-800';
    if (action === 'merge' || action === 'create') return 'bg-purple-100 text-purple-800';
    return 'bg-blue-100 text-blue-800';
};

const close = () => {
    emit('close');
};

const apply = () => {
    emit('apply', props.draftBatchId);
};
</script>

<style scoped>
/* Component styles */
</style>

