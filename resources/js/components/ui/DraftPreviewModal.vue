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
                    <div v-if="!preview || (preview && (!preview.subject_competency_changes || preview.subject_competency_changes.length === 0) && (!preview.didactic_unit_changes || preview.didactic_unit_changes.length === 0))" 
                         class="text-center py-10 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">Нет изменений для отображения</p>
                        <p class="text-sm mt-2">Черновик пуст или данные не загружены</p>
                        <p class="text-xs mt-2 text-gray-400">Draft Batch ID: {{ draftBatchId }}</p>
                        <div v-if="preview" class="mt-4 text-xs text-gray-400">
                            <p>Subject changes: {{ preview.subject_competency_changes?.length || 0 }}</p>
                            <p>Unit changes: {{ preview.didactic_unit_changes?.length || 0 }}</p>
                        </div>
                    </div>

                    <!-- Изменения связей ПК-МДК/ОП -->
                    <div v-if="preview && preview.subject_competency_changes && preview.subject_competency_changes.length > 0" class="mb-6">
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
                                <div class="text-sm text-gray-700 space-y-1">
                                    <div>
                                        <span class="font-medium">{{ change.original.subject_type === 'modul' ? 'МДК' : 'ОП' }}:</span> 
                                        {{ change.original.subject_name || `ID: ${change.original.subject_id}` }}
                                    </div>
                                    <div v-if="change.action === 'move'">
                                        <div class="space-y-1">
                                            <div>
                                                <span class="font-medium text-gray-500">Исходная связь:</span> 
                                                ПК "{{ change.original.competency_name || `ID: ${change.original.prof_competency_id}` }}"
                                            </div>
                                            <div>
                                                <span class="font-medium text-blue-600">Новая связь:</span> 
                                                ПК "{{ change.new.competency_name || `ID: ${change.new.prof_competency_id}` }}"
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="change.action === 'remove'">
                                        <span class="font-medium">Удаление связи:</span> 
                                        ПК "{{ change.original.competency_name || `ID: ${change.original.prof_competency_id}` }}"
                                    </div>
                                    <div v-else>
                                        <span class="font-medium">Оставить связь:</span> 
                                        ПК "{{ change.original.competency_name || `ID: ${change.original.prof_competency_id}` }}"
                                    </div>
                                </div>
                                <div v-if="change.comment" class="mt-2 text-sm text-gray-600 italic">
                                    "{{ change.comment }}"
                                </div>
                                <!-- Кнопка для управления ДЕ (только для новых связей после переноса) -->
                                <div v-if="change.action === 'move'" class="mt-3 pt-3 border-t border-gray-200">
                                    <button
                                        @click="openDidacticUnitEditor(change)"
                                        class="px-3 py-1.5 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors flex items-center gap-1.5"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Оценить ДЕ для новой связи
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Изменения ДЕ -->
                    <div v-if="preview && preview.didactic_unit_changes && preview.didactic_unit_changes.length > 0">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-lg font-bold text-gray-900">Изменения дидактических единиц:</h4>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="change in preview.didactic_unit_changes"
                                :key="change.id"
                                class="bg-gray-50 rounded-lg p-4 border-l-4"
                                :class="getActionColorClass(change.action)"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-semibold px-2 py-1 rounded"
                                        :class="getActionBadgeClass(change.action)"
                                    >
                                        {{ getActionLabel(change.action) }}
                                    </span>
                                    <button
                                        @click="editDidacticUnitDraft(change)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors flex items-center gap-1"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Редактировать
                                    </button>
                                </div>
                                <div class="text-sm text-gray-700 space-y-1">
                                    <div>
                                        <span class="font-medium">{{ change.context.subject_type === 'modul' ? 'МДК' : 'ОП' }}:</span> 
                                        {{ change.context.subject_name || `ID: ${change.context.subject_id}` }}
                                    </div>
                                    <div>
                                        <span class="font-medium">ПК:</span> 
                                        {{ change.context.competency_name || `ID: ${change.context.prof_competency_id}` }}
                                    </div>
                                    <div v-if="change.action === 'merge'" class="mt-2">
                                        Объединение ДЕ: {{ change.original.didactic_unit_ids?.join(', ') }}
                                        → Новая ДЕ: "{{ change.new.didactic_unit_name }}"
                                    </div>
                                    <div v-else-if="change.action === 'replace'" class="mt-2">
                                        Замена ДЕ #{{ change.original.didactic_unit_id }}
                                        → {{ change.new.didactic_unit_id ? `ДЕ #${change.new.didactic_unit_id}` : `"${change.new.didactic_unit_name}"` }}
                                    </div>
                                    <div v-else-if="change.action === 'move'" class="mt-2">
                                        Перенос ДЕ #{{ change.original.didactic_unit_id }}
                                        в другую связь
                                    </div>
                                    <div v-else-if="change.action === 'create'" class="mt-2">
                                        Создание новой ДЕ: "{{ change.new.didactic_unit_name }}"
                                    </div>
                                    <div v-else-if="change.action === 'remove'" class="mt-2">
                                        Удаление ДЕ #{{ change.original.didactic_unit_id }}
                                    </div>
                                </div>
                                <div v-if="change.comment" class="mt-2 text-sm text-gray-600 italic">
                                    "{{ change.comment }}"
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка для добавления новой оценки ДЕ (всегда видна, если есть preview) -->
                    <div v-if="preview" class="mt-4 p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                        <div>
                            <h4 class="font-semibold text-blue-900 mb-2">Оценка дидактических единиц</h4>
                            <p class="text-sm text-blue-700 mb-3">Добавить оценку ДЕ для связи из этого черновика</p>
                            
                            <!-- Если есть изменения связей, показываем выбор (только новые связи) -->
                            <div v-if="preview.subject_competency_changes && preview.subject_competency_changes.length > 0" class="flex items-center gap-2">
                                <select
                                    v-model="selectedRelationForNewDraft"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                >
                                    <option value="">Выберите новую связь для оценки ДЕ</option>
                                    <optgroup 
                                        v-for="change in preview.subject_competency_changes.filter(c => c.action === 'move')"
                                        :key="change.id"
                                        :label="`Перенос: ${change.original.subject_type === 'modul' ? 'МДК' : 'ОП'}: ${change.original.subject_name || `ID: ${change.original.subject_id}`}`"
                                    >
                                        <!-- Только новая связь (после переноса) -->
                                        <option
                                            v-if="change.new.prof_competency_id !== change.original.prof_competency_id"
                                            :value="JSON.stringify({
                                                subject_type: change.new.subject_type || change.original.subject_type,
                                                subject_id: change.new.subject_id || change.original.subject_id,
                                                subject_name: change.new.subject_name || change.original.subject_name || `ID: ${change.new.subject_id || change.original.subject_id}`,
                                                prof_competency_id: change.new.prof_competency_id,
                                                competency_name: change.new.competency_name || `ID: ${change.new.prof_competency_id}`
                                            })"
                                        >
                                            Новая связь: {{ change.new.subject_type === 'modul' ? 'МДК' : 'ОП' }} ↔ ПК: {{ change.new.competency_name || `ID: ${change.new.prof_competency_id}` }}
                                        </option>
                                    </optgroup>
                                </select>
                                <button
                                    @click="openNewDidacticUnitDraftEditor"
                                    :disabled="!selectedRelationForNewDraft"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors text-sm font-medium flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Добавить оценку ДЕ
                                </button>
                            </div>
                            
                            <!-- Если нет изменений связей, показываем сообщение -->
                            <div v-else class="text-sm text-gray-600">
                                Нет изменений связей для оценки ДЕ
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

const emit = defineEmits(['close', 'apply', 'edit-didactic-unit']);

const { getPreview } = useDrafts();

const preview = ref(null);
const previewLoading = ref(false);
const selectedRelationForNewDraft = ref('');

const loadPreview = async () => {
    try {
        previewLoading.value = true;
        preview.value = null; // Сбрасываем предыдущие данные
        
        if (!props.draftBatchId) {
            console.warn('No draftBatchId provided');
            preview.value = {
                subject_competency_changes: [],
                didactic_unit_changes: []
            };
            previewLoading.value = false;
            return;
        }
        
        const data = await getPreview(props.draftBatchId);
        
        // Убеждаемся, что данные в правильном формате
        if (data && typeof data === 'object') {
            const subjectChanges = data.subject_competency_changes;
            const unitChanges = data.didactic_unit_changes;
            
            // Убеждаемся, что данные правильно обработаны
            const finalSubjectChanges = Array.isArray(subjectChanges) 
                ? subjectChanges 
                : (subjectChanges ? [subjectChanges] : []);
            const finalUnitChanges = Array.isArray(unitChanges) 
                ? unitChanges 
                : (unitChanges ? [unitChanges] : []);
            
            preview.value = {
                subject_competency_changes: finalSubjectChanges,
                didactic_unit_changes: finalUnitChanges
            };
        } else {
            preview.value = {
                subject_competency_changes: [],
                didactic_unit_changes: []
            };
        }
    } catch (error) {
        preview.value = {
            subject_competency_changes: [],
            didactic_unit_changes: []
        };
        // Ошибка уже обработана в композабле
    } finally {
        previewLoading.value = false;
    }
};

// Загружаем данные при открытии модального окна
watch(() => props.show, async (newShow) => {
    if (newShow && props.draftBatchId) {
        await loadPreview();
    } else if (!newShow) {
        // Сбрасываем данные при закрытии
        preview.value = null;
        selectedRelationForNewDraft.value = '';
    }
}, { immediate: true });

// Также следим за изменением draftBatchId
watch(() => props.draftBatchId, async (newBatchId) => {
    if (props.show && newBatchId) {
        await loadPreview();
    }
});

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

const editDidacticUnitDraft = (change) => {
    emit('edit-didactic-unit', {
        change,
        draftBatchId: props.draftBatchId
    });
};

const openDidacticUnitEditor = (change) => {
    console.log('openDidacticUnitEditor called with change:', change);
    
    // Для переноса используем новую связь, но передаем информацию о переносе
    const relation = change.action === 'move' ? change.new : change.original;
    
    console.log('Using relation:', relation);
    
    // Сохраняем параметры и переключаемся на новую страницу
    const params = {
        draftBatchId: props.draftBatchId,
        subjectType: relation.subject_type,
        subjectId: relation.subject_id,
        subjectName: relation.subject_name || '',
        competencyId: relation.prof_competency_id,
        competencyName: relation.competency_name || '',
        isMove: change.action === 'move',
        originalCompetencyId: change.action === 'move' && change.original 
            ? change.original.prof_competency_id 
            : null
    };
    
    console.log('Saving params to localStorage:', params);
    localStorage.setItem('draftDidacticUnitsParams', JSON.stringify(params));
    
    // Закрываем модальное окно сначала
    emit('close');
    
    // Затем переключаемся на новую страницу (с небольшой задержкой, чтобы модалка успела закрыться)
    setTimeout(() => {
        console.log('Dispatching change-view event');
        window.dispatchEvent(new CustomEvent('change-view', { detail: 'draft-didactic-units' }));
    }, 100);
};

const openNewDidacticUnitDraftEditor = () => {
    if (!selectedRelationForNewDraft.value) {
        return;
    }
    
    try {
        const relation = JSON.parse(selectedRelationForNewDraft.value);
        
        console.log('openNewDidacticUnitDraftEditor called with relation:', relation);
        
        // Сохраняем параметры и переключаемся на новую страницу
        const params = {
            draftBatchId: props.draftBatchId,
            subjectType: relation.subject_type,
            subjectId: relation.subject_id,
            subjectName: relation.subject_name || '',
            competencyId: relation.prof_competency_id,
            competencyName: relation.competency_name || '',
            isMove: false,
            originalCompetencyId: null
        };
        
        console.log('Saving params to localStorage:', params);
        localStorage.setItem('draftDidacticUnitsParams', JSON.stringify(params));
        
        selectedRelationForNewDraft.value = '';
        
        // Закрываем модальное окно сначала
        emit('close');
        
        // Затем переключаемся на новую страницу (с небольшой задержкой, чтобы модалка успела закрыться)
        setTimeout(() => {
            console.log('Dispatching change-view event');
            window.dispatchEvent(new CustomEvent('change-view', { detail: 'draft-didactic-units' }));
        }, 100);
    } catch (error) {
        console.error('Error parsing relation:', error);
    }
};
</script>

<style scoped>
/* Component styles */
</style>

