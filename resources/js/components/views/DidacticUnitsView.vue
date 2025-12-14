<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Управление дидактическими единицами
            </h2>
            <p class="text-lg text-gray-600">Добавляйте дидактические единицы к МДК и ОП по видам деятельности</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Список модулей -->
        <div v-else class="space-y-6">
            <div
                v-for="module in modules"
                :key="module.id"
                class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden"
            >
                <!-- Заголовок модуля -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white font-bold text-lg shadow-md">
                            {{ getModuleNumber(module.id) }}
                        </div>
                        <h3 class="text-xl font-bold text-white">{{ module.name }}</h3>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- МДК -->
                    <div v-if="getModulSubjects(module.id).length > 0" class="space-y-4">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                            <h4 class="text-lg font-bold text-gray-800 uppercase tracking-wider">МДК</h4>
                        </div>
                        <div
                            v-for="mdk in getModulSubjects(module.id)"
                            :key="mdk.id"
                            class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200 shadow-sm"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <h5 class="text-lg font-semibold text-gray-900">{{ mdk.name }}</h5>
                                <button
                                    @click="toggleEditMode('mdk', mdk.id)"
                                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg flex items-center gap-2"
                                >
                                    <svg v-if="editingSubjectId !== mdk.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ editingSubjectId === mdk.id ? 'Отмена' : 'Редактировать' }}
                                </button>
                            </div>

                            <!-- Текущие дидактические единицы по ПК -->
                            <div v-if="editingSubjectId !== mdk.id" class="space-y-4">
                                <div v-if="getCompetenciesForSubject('mdk', mdk.id).length === 0" class="text-sm text-gray-400 italic">
                                    МДК не привязана к ПК
                                </div>
                                <div v-else v-for="competency in getCompetenciesForSubject('mdk', mdk.id)" :key="competency.id" class="space-y-3 border-l-4 border-blue-400 pl-4">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold text-gray-800">{{ competency.name }}</div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="isCompetencyApproved('mdk', mdk.id, competency.id) && hasDidacticUnits('modul', mdk.id, competency.id) && !areDidacticUnitsApproved('modul', mdk.id, competency.id)"
                                                @click="approveDidacticUnits('modul', mdk.id, competency.id)"
                                                class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1 cursor-pointer"
                                                title="Утвердить ДЕ"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Утвердить ДЕ
                                            </button>
                                            <button
                                                v-if="isCompetencyApproved('mdk', mdk.id, competency.id) && areDidacticUnitsApproved('modul', mdk.id, competency.id)"
                                                @click="openDidacticUnitDraftEditor('modul', mdk.id, mdk.name, competency.id, competency.name, getModuleIdForSubject('mdk', mdk.id))"
                                                class="px-3 py-1.5 rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1"
                                                :class="hasDidacticUnitDraft('modul', mdk.id, competency.id)
                                                    ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                    : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                                :title="hasDidacticUnitDraft('modul', mdk.id, competency.id) ? 'Редактировать оценку ДЕ' : 'Оценить ДЕ'"
                                            >
                                                <svg v-if="hasDidacticUnitDraft('modul', mdk.id, competency.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                </svg>
                                                {{ hasDidacticUnitDraft('modul', mdk.id, competency.id) ? 'Редактировать оценку' : 'Оценить ДЕ' }}
                                            </button>
                                            <div v-if="!isCompetencyApproved('mdk', mdk.id, competency.id)" class="px-3 py-1.5 text-xs text-gray-400 italic">
                                                Сначала утвердите связь
                                            </div>
                                            <div v-else-if="!areDidacticUnitsApproved('modul', mdk.id, competency.id) && !hasDidacticUnits('modul', mdk.id, competency.id)" class="px-3 py-1.5 text-xs text-gray-400 italic">
                                                Добавьте ДЕ
                                            </div>
                                        </div>
                                    </div>
                                    <div v-for="type in ['знать', 'уметь', 'иметь практический опыт']" :key="type" class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            <span class="text-xs font-bold text-gray-600 uppercase">{{ type }}:</span>
                                        </div>
                                        <div class="pl-4 space-y-1">
                                            <div
                                                v-for="unit in getDidacticUnitsByTypeAndCompetency(mdk.id, 'mdk', competency.id, type)"
                                                :key="unit.id"
                                                class="text-xs text-gray-700 bg-white px-2 py-1 rounded border border-gray-200 flex items-center gap-2"
                                            >
                                                <span>{{ unit.name }}</span>
                                                <span v-if="hasDidacticUnitDraft('modul', mdk.id, competency.id)" 
                                                      class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold"
                                                      title="Есть оценка">
                                                    ⭐
                                                </span>
                                            </div>
                                            <div v-if="getDidacticUnitsByTypeAndCompetency(mdk.id, 'mdk', competency.id, type).length === 0" class="text-xs text-gray-400 italic">
                                                Нет ДЕ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ОП -->
                    <div v-if="getOpSubjects(module.id).length > 0" class="space-y-4">
                        <div class="flex items-center gap-2 mb-4 mt-6">
                            <div class="w-1 h-6 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full"></div>
                            <h4 class="text-lg font-bold text-gray-800 uppercase tracking-wider">ОП</h4>
                        </div>
                        <div
                            v-for="op in getOpSubjects(module.id)"
                            :key="op.id"
                            class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200 shadow-sm"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <h5 class="text-lg font-semibold text-gray-900">{{ op.name }}</h5>
                                <button
                                    @click="toggleEditMode('op', op.id)"
                                    class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg flex items-center gap-2"
                                >
                                    <svg v-if="editingSubjectId !== op.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ editingSubjectId === op.id ? 'Отмена' : 'Редактировать' }}
                                </button>
                            </div>

                            <!-- Текущие дидактические единицы по ПК -->
                            <div v-if="editingSubjectId !== op.id" class="space-y-4">
                                <div v-if="getCompetenciesForSubject('op', op.id).length === 0" class="text-sm text-gray-400 italic">
                                    ОП не привязана к ПК
                                </div>
                                <div v-else v-for="competency in getCompetenciesForSubject('op', op.id)" :key="competency.id" class="space-y-3 border-l-4 border-purple-400 pl-4">
                                    <div class="flex items-center justify-between">
                                        <div class="font-semibold text-gray-800">{{ competency.name }}</div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="isCompetencyApproved('op', op.id, competency.id) && hasDidacticUnits('op', op.id, competency.id) && !areDidacticUnitsApproved('op', op.id, competency.id)"
                                                @click="approveDidacticUnits('op', op.id, competency.id)"
                                                class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1 cursor-pointer"
                                                title="Утвердить ДЕ"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Утвердить ДЕ
                                            </button>
                                            <button
                                                v-if="isCompetencyApproved('op', op.id, competency.id) && areDidacticUnitsApproved('op', op.id, competency.id)"
                                                @click="openDidacticUnitDraftEditor('op', op.id, op.name, competency.id, competency.name, getModuleIdForSubject('op', op.id))"
                                                class="px-3 py-1.5 rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1"
                                                :class="hasDidacticUnitDraft('op', op.id, competency.id)
                                                    ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                    : 'bg-purple-500 hover:bg-purple-600 text-white'"
                                                :title="hasDidacticUnitDraft('op', op.id, competency.id) ? 'Редактировать оценку ДЕ' : 'Оценить ДЕ'"
                                            >
                                                <svg v-if="hasDidacticUnitDraft('op', op.id, competency.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                                </svg>
                                                {{ hasDidacticUnitDraft('op', op.id, competency.id) ? 'Редактировать оценку' : 'Оценить ДЕ' }}
                                            </button>
                                            <div v-if="!isCompetencyApproved('op', op.id, competency.id)" class="px-3 py-1.5 text-xs text-gray-400 italic">
                                                Сначала утвердите связь
                                            </div>
                                            <div v-else-if="!areDidacticUnitsApproved('op', op.id, competency.id) && !hasDidacticUnits('op', op.id, competency.id)" class="px-3 py-1.5 text-xs text-gray-400 italic">
                                                Добавьте ДЕ
                                            </div>
                                        </div>
                                    </div>
                                    <div v-for="type in ['знать', 'уметь']" :key="type" class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                            <span class="text-xs font-bold text-gray-600 uppercase">{{ type }}:</span>
                                        </div>
                                        <div class="pl-4 space-y-1">
                                            <div
                                                v-for="unit in getDidacticUnitsByTypeAndCompetency(op.id, 'op', competency.id, type)"
                                                :key="unit.id"
                                                class="text-xs text-gray-700 bg-white px-2 py-1 rounded border border-gray-200 flex items-center gap-2"
                                            >
                                                <span>{{ unit.name }}</span>
                                                <span v-if="hasDidacticUnitDraft('op', op.id, competency.id)" 
                                                      class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold"
                                                      title="Есть оценка">
                                                    ⭐
                                                </span>
                                            </div>
                                            <div v-if="getDidacticUnitsByTypeAndCompetency(op.id, 'op', competency.id, type).length === 0" class="text-xs text-gray-400 italic">
                                                Нет ДЕ
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно редактирования -->
        <Modal
            :is-open="isModalOpen"
            :title="modalTitle"
            @update:is-open="isModalOpen = $event"
            @close="closeModal"
        >
            <div v-if="currentEditingId" class="space-y-6">
                <!-- Выбор ПК -->
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-2">
                        Выберите профессиональную компетенцию:
                    </label>
                    <select
                        v-model="selectedCompetencyId"
                        @change="loadDidacticUnitsForCompetency(currentEditingId, currentEditingType, selectedCompetencyId)"
                        class="w-full px-4 py-3 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">-- Выберите ПК --</option>
                        <option
                            v-for="comp in getCompetenciesForSubject(currentEditingType, currentEditingId)"
                            :key="comp.id"
                            :value="comp.id"
                        >
                            {{ comp.name }}
                        </option>
                    </select>
                </div>

                <!-- Редактор ДЕ -->
                <div v-if="selectedCompetencyId">
                    <DidacticUnitEditor
                        v-model="editForms[currentEditingId]"
                        :types="currentEditingTypes"
                        :available-units="didacticUnits"
                        :type-mapping="typeMapping"
                    />
                </div>
                <div v-else class="text-sm text-gray-500 italic text-center py-4">
                    Выберите профессиональную компетенцию для редактирования дидактических единиц
                </div>
            </div>
            <template #footer>
                <button
                    @click="closeModal"
                    class="px-6 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg border border-gray-200"
                >
                    Отмена
                </button>
                <button
                    v-if="selectedCompetencyId"
                    @click="saveDidacticUnits(currentEditingType, currentEditingId)"
                    class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-lg hover:shadow-xl"
                >
                    Сохранить
                </button>
            </template>
        </Modal>

        <!-- Редактор черновиков ДЕ - теперь используется отдельная страница для всех связей -->
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Modal from '../ui/Modal.vue';
import DidacticUnitEditor from '../ui/DidacticUnitEditor.vue';
// DidacticUnitDraftEditor больше не используется - используем отдельную страницу для всех связей
import { useErrorHandler } from '../../composables/useErrorHandler';
import { useDrafts } from '../../composables/useDrafts';

const { handleError } = useErrorHandler();
const { findDidacticUnitDraft } = useDrafts();

const loading = ref(true);
const modules = ref([]);
const modulSubjects = ref([]);
const opSubjects = ref([]);
const didacticUnits = ref([]);
const competencies = ref([]);
const editingSubjectId = ref(null);
const editingSubjectType = ref(null);
const editForms = ref({});
const isModalOpen = ref(false);
const currentEditingId = ref(null);
const currentEditingType = ref(null);
const currentEditingTypes = ref([]);
const modalTitle = ref('');
const selectedCompetencyId = ref(null);

const getModuleNumber = (moduleId) => {
    const index = modules.value.findIndex(m => m.id === moduleId);
    return index !== -1 ? index + 1 : '';
};

const getModulSubjects = (moduleId) => {
    return modulSubjects.value.filter(ms => ms.id_module === moduleId);
};

const getOpSubjects = (moduleId) => {
    // Получаем все ПК этого модуля
    const moduleCompetencies = competencies.value.filter(c => c.id_module === moduleId);
    const competencyIds = moduleCompetencies.map(c => c.id);
    
    // Получаем ОП, которые связаны с этими ПК
    const relatedOpIds = new Set();
    moduleCompetencies.forEach(competency => {
        (competency.op_subjects || []).forEach(op => {
            relatedOpIds.add(op.id);
        });
    });
    
    // Возвращаем ОП, которые связаны с ПК этого модуля
    return opSubjects.value.filter(op => relatedOpIds.has(op.id));
};

// Маппинг русских названий на английские значения
const typeMapping = {
    'знать': 'know',
    'уметь': 'be_able',
    'иметь практический опыт': 'have_practical_experience'
};

const reverseTypeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

const getCompetenciesForSubject = (subjectType, subjectId) => {
    const subject = subjectType === 'mdk' 
        ? modulSubjects.value.find(s => s.id === subjectId)
        : opSubjects.value.find(s => s.id === subjectId);
    
    if (!subject) return [];
    
    if (subjectType === 'mdk') {
        return (subject.prof_competencies || []).map(c => ({
            id: c.id,
            name: c.name,
            pivot: c.pivot // Сохраняем pivot данные для проверки утверждения
        }));
    } else {
        // Для ОП находим ПК через компетенции
        const relatedCompetencies = [];
        competencies.value.forEach(comp => {
            if (comp.op_subjects && comp.op_subjects.some(op => op.id === subjectId)) {
                const op = comp.op_subjects.find(op => op.id === subjectId);
                relatedCompetencies.push({
                    id: comp.id,
                    name: comp.name,
                    pivot: op?.pivot // Сохраняем pivot данные для проверки утверждения
                });
            }
        });
        return relatedCompetencies;
    }
};

const didacticUnitsByCompetency = ref({});
const didacticUnitDraftsMap = ref(new Map()); // Map для хранения черновиков ДЕ: key = "subjectType_subjectId_competencyId"

// Проверка наличия черновика ДЕ для связи
const hasDidacticUnitDraft = (subjectType, subjectId, competencyId) => {
    const key = `${subjectType}_${subjectId}_${competencyId}`;
    return didacticUnitDraftsMap.value.has(key);
};

// Проверка утверждения связи ПК-МДК/ОП
const isCompetencyApproved = (subjectType, subjectId, competencyId) => {
    if (subjectType === 'mdk') {
        const subject = modulSubjects.value.find(s => s.id === subjectId);
        if (!subject || !subject.prof_competencies) return false;
        const comp = subject.prof_competencies.find(c => c.id === competencyId);
        if (!comp) return false;
        // Проверяем pivot данные - может быть объект или значение напрямую
        const pivot = comp.pivot;
        if (!pivot) return false;
        // Проверяем разные форматы данных
        const approved = pivot.approved !== undefined ? pivot.approved : (comp.approved !== undefined ? comp.approved : false);
        return approved === true || approved === 1 || approved === '1';
    } else {
        // Для ОП проверяем через компетенции
        const competency = competencies.value.find(c => c.id === competencyId);
        if (!competency || !competency.op_subjects) return false;
        const op = competency.op_subjects.find(s => s.id === subjectId);
        if (!op) return false;
        // Проверяем pivot данные - может быть объект или значение напрямую
        const pivot = op.pivot;
        if (!pivot) return false;
        // Проверяем разные форматы данных
        const approved = pivot.approved !== undefined ? pivot.approved : (op.approved !== undefined ? op.approved : false);
        return approved === true || approved === 1 || approved === '1';
    }
};

// Проверка утверждения всех ДЕ для связи
const areDidacticUnitsApproved = (subjectType, subjectId, competencyId) => {
    const key = `${subjectType}_${subjectId}_${competencyId}`;
    const units = didacticUnitsByCompetency.value[key] || [];
    if (units.length === 0) return false; // Если нет ДЕ, считаем что не утверждено
    // Проверяем разные форматы данных (boolean, int, string)
    return units.every(unit => {
        const approved = unit.approved;
        return approved === true || approved === 1 || approved === '1';
    });
};

// Проверка наличия ДЕ для связи
const hasDidacticUnits = (subjectType, subjectId, competencyId) => {
    const key = `${subjectType}_${subjectId}_${competencyId}`;
    const units = didacticUnitsByCompetency.value[key] || [];
    return units.length > 0;
};

// Утвердить ДЕ для связи
const approveDidacticUnits = async (subjectType, subjectId, competencyId) => {
    try {
        await axios.post('/api/didactic-units/approve', {
            subject_type: subjectType,
            subject_id: subjectId,
            prof_competency_id: competencyId
        });
        await loadDidacticUnitsForDisplay();
    } catch (error) {
        handleError(error, 'Ошибка утверждения ДЕ');
    }
};

// Разутвердить ДЕ для связи
const unapproveDidacticUnits = async (subjectType, subjectId, competencyId) => {
    try {
        await axios.post('/api/didactic-units/unapprove', {
            subject_type: subjectType,
            subject_id: subjectId,
            prof_competency_id: competencyId
        });
        await loadDidacticUnitsForDisplay();
    } catch (error) {
        handleError(error, 'Ошибка разутверждения ДЕ');
    }
};

// Получить ID модуля для предмета
const getModuleIdForSubject = (subjectType, subjectId) => {
    if (subjectType === 'mdk') {
        const subject = modulSubjects.value.find(s => s.id === subjectId);
        return subject?.id_module || null;
    } else {
        // Для ОП находим через компетенции
        const competency = competencies.value.find(c => 
            c.op_subjects?.some(op => op.id === subjectId)
        );
        return competency?.id_module || null;
    }
};

// Редактор черновиков ДЕ - теперь используется отдельная страница для всех связей

const openDidacticUnitDraftEditor = async (subjectType, subjectId, subjectName, competencyId, competencyName, moduleId) => {
    // Проверяем утверждение связи
    if (!isCompetencyApproved(subjectType === 'modul' ? 'mdk' : 'op', subjectId, competencyId)) {
        handleError(new Error('Связь не утверждена'), 'Нельзя оценить ДЕ для неутвержденной связи');
        return;
    }
    
    try {
        const apiSubjectType = subjectType === 'modul' ? 'modul' : 'op';
        
        // Ищем существующий черновик - если есть, используем его draft_batch_id
        const existingDraft = await findDidacticUnitDraft(apiSubjectType, subjectId, competencyId);
        
        // Загружаем текущие ДЕ для этой связи
        const key = `${apiSubjectType}_${subjectId}_${competencyId}`;
        let currentUnits = didacticUnitsByCompetency.value[key] || [];
        
        // Если ДЕ нет в кэше, загружаем их
        if (currentUnits.length === 0) {
            try {
                const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
                    subjects: [{
                        subject_type: apiSubjectType,
                        subject_id: subjectId,
                        competency_id: competencyId
                    }]
                });
                currentUnits = response.data[key] || [];
            } catch (e) {
                console.warn('Error loading units:', e);
                currentUnits = [];
            }
        }
        
        // Перенаправляем на отдельную страницу для оценки ДЕ
        // Для старых связей (isMove: false) - это существующая связь, не новая
        const params = {
            draftBatchId: existingDraft?.draft_batch_id || null,
            subjectType: apiSubjectType,
            subjectId: subjectId,
            subjectName: subjectName,
            competencyId: competencyId,
            competencyName: competencyName,
            isMove: false, // Старая связь, не перенос
            originalCompetencyId: null
        };
        localStorage.setItem('draftDidacticUnitsParams', JSON.stringify(params));
        window.dispatchEvent(new CustomEvent('change-view', { detail: 'draft-didactic-units' }));
    } catch (error) {
        handleError(error, 'Ошибка открытия редактора черновиков ДЕ');
    }
};

// Функции для модалки больше не используются - используем отдельную страницу

// Загрузка черновиков ДЕ
const loadDidacticUnitDrafts = async () => {
    try {
        const draftsResponse = await axios.get('/api/drafts').catch(() => ({ data: [] }));
        didacticUnitDraftsMap.value.clear();
        
        if (draftsResponse.data && draftsResponse.data.length > 0) {
            // Загружаем детали черновиков параллельно
            const draftDetailsPromises = draftsResponse.data.slice(0, 20).map(draftSummary => 
                axios.get(`/api/drafts/${draftSummary.draft_batch_id}`).catch(() => null)
            );
            const draftDetailsResults = await Promise.all(draftDetailsPromises);
            
            draftDetailsResults.forEach((draftDetails, index) => {
                if (draftDetails?.data?.didactic_unit_changes) {
                    draftDetails.data.didactic_unit_changes.forEach(change => {
                        const key = `${change.context.subject_type}_${change.context.subject_id}_${change.context.prof_competency_id}`;
                        didacticUnitDraftsMap.value.set(key, {
                            draftBatchId: draftsResponse.data[index].draft_batch_id,
                            action: change.action,
                            comment: change.comment
                        });
                    });
                }
            });
        }
    } catch (error) {
        console.warn('Ошибка загрузки черновиков ДЕ:', error);
    }
};

const getDidacticUnitsByTypeAndCompetency = (subjectId, subjectType, competencyId, typeRu) => {
    // Преобразуем 'mdk' в 'modul' для соответствия ключам из API
    const apiSubjectType = subjectType === 'mdk' ? 'modul' : subjectType;
    const key = `${apiSubjectType}_${subjectId}_${competencyId}`;
    const units = didacticUnitsByCompetency.value[key] || [];
    const typeEn = typeMapping[typeRu];
    return units.filter(unit => unit.type === typeEn);
};

// Загрузка ДЕ для отображения (оптимизированная версия с одним запросом)
const loadDidacticUnitsForDisplay = async () => {
    try {
        // Собираем все комбинации subject_type, subject_id, competency_id
        const subjectsToLoad = [];
        
        // Добавляем МДК
        modulSubjects.value.forEach(mdk => {
            if (mdk.prof_competencies) {
                mdk.prof_competencies.forEach(comp => {
                    subjectsToLoad.push({
                        subject_type: 'modul',
                        subject_id: mdk.id,
                        competency_id: comp.id
                    });
                });
            }
        });
        
        // Добавляем ОП
        opSubjects.value.forEach(op => {
            const relatedComps = getCompetenciesForSubject('op', op.id);
            relatedComps.forEach(comp => {
                subjectsToLoad.push({
                    subject_type: 'op',
                    subject_id: op.id,
                    competency_id: comp.id
                });
            });
        });
        
        // Если нет данных для загрузки, выходим
        if (subjectsToLoad.length === 0) {
            return;
        }
        
        // Один запрос вместо множественных
        const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
            subjects: subjectsToLoad
        });
        
        // Заполняем didacticUnitsByCompetency данными из ответа
        Object.keys(response.data).forEach(key => {
            didacticUnitsByCompetency.value[key] = response.data[key];
        });
        
        // Загружаем черновики ДЕ
        await loadDidacticUnitDrafts();
    } catch (error) {
        handleError(error, 'Ошибка загрузки ДЕ');
        // Инициализируем пустые массивы при ошибке
        modulSubjects.value.forEach(mdk => {
            if (mdk.prof_competencies) {
                mdk.prof_competencies.forEach(comp => {
                    const key = `modul_${mdk.id}_${comp.id}`;
                    if (!didacticUnitsByCompetency.value[key]) {
                        didacticUnitsByCompetency.value[key] = [];
                    }
                });
            }
        });
        opSubjects.value.forEach(op => {
            const relatedComps = getCompetenciesForSubject('op', op.id);
            relatedComps.forEach(comp => {
                const key = `op_${op.id}_${comp.id}`;
                if (!didacticUnitsByCompetency.value[key]) {
                    didacticUnitsByCompetency.value[key] = [];
                }
            });
        });
    }
};

const getDidacticUnitsByType = (units, typeRu) => {
    const typeEn = typeMapping[typeRu];
    return units.filter(unit => unit.type === typeEn);
};

const getAvailableDidacticUnits = (typeRu) => {
    const typeEn = typeMapping[typeRu];
    return didacticUnits.value.filter(unit => unit.type === typeEn);
};

const getPlaceholder = (typeRu) => {
    return `Выберите единицы типа "${typeRu}"...`;
};

const toggleEditMode = async (type, subjectId) => {
    const subject = type === 'mdk' 
        ? modulSubjects.value.find(s => s.id === subjectId)
        : opSubjects.value.find(s => s.id === subjectId);
    
    if (subject) {
        editingSubjectId.value = subjectId;
        editingSubjectType.value = type;
        currentEditingId.value = subjectId;
        currentEditingType.value = type;
        currentEditingTypes.value = type === 'mdk' 
            ? ['знать', 'уметь', 'иметь практический опыт']
            : ['знать', 'уметь'];
        modalTitle.value = `${type === 'mdk' ? 'МДК' : 'ОП'}: ${subject.name}`;
        selectedCompetencyId.value = null;
        
        // Инициализируем форму пустой
        editForms.value[subjectId] = {
            'знать': [],
            'уметь': [],
            'иметь практический опыт': type === 'mdk' ? [] : []
        };
        
        isModalOpen.value = true;
    }
};

// Загрузка ДЕ при выборе ПК
const loadDidacticUnitsForCompetency = async (subjectId, subjectType, competencyId) => {
    if (!subjectId || !subjectType || !competencyId) return;
    
    try {
        const endpoint = subjectType === 'mdk' 
            ? `/api/modulsubjects/${subjectId}?prof_competency_id=${competencyId}`
            : `/api/op-subjects/${subjectId}?prof_competency_id=${competencyId}`;
        
        const response = await axios.get(endpoint);
        const subject = response.data;
        
        // Обновляем форму с загруженными ДЕ
        if (editForms.value[subjectId]) {
            editForms.value[subjectId] = {
                'знать': (subject.didactic_units_by_pk || [])
                    .filter(u => u.type === 'know')
                    .map(u => u.id),
                'уметь': (subject.didactic_units_by_pk || [])
                    .filter(u => u.type === 'be_able')
                    .map(u => u.id),
                'иметь практический опыт': subjectType === 'mdk' 
                    ? (subject.didactic_units_by_pk || [])
                        .filter(u => u.type === 'have_practical_experience')
                        .map(u => u.id)
                    : []
            };
        }
    } catch (error) {
        handleError(error, 'Ошибка загрузки ДЕ');
        // Инициализируем пустую форму при ошибке
        if (editForms.value[subjectId]) {
            editForms.value[subjectId] = {
                'знать': [],
                'уметь': [],
                'иметь практический опыт': subjectType === 'mdk' ? [] : []
            };
        }
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    editingSubjectId.value = null;
    editingSubjectType.value = null;
    currentEditingId.value = null;
    currentEditingType.value = null;
    currentEditingTypes.value = [];
    modalTitle.value = '';
    selectedCompetencyId.value = null;
};

const cancelEdit = (subjectId) => {
    closeModal();
    delete editForms.value[subjectId];
};

const saveDidacticUnits = async (type, subjectId) => {
    if (!selectedCompetencyId.value) {
        handleError(new Error('Не выбрана профессиональная компетенция'), 'Выберите профессиональную компетенцию');
        return;
    }

    try {
        const form = editForms.value[subjectId];
        
        // Собираем все выбранные дидактические единицы
        const allUnitIds = [
            ...(form['знать'] || []),
            ...(form['уметь'] || []),
            ...(form['иметь практический опыт'] || [])
        ];

        const endpoint = type === 'mdk' 
            ? `/api/modulsubjects/${subjectId}/didactic-units`
            : `/api/op-subjects/${subjectId}/didactic-units`;

        await axios.put(endpoint, {
            prof_competency_id: selectedCompetencyId.value,
            didactic_unit_ids: allUnitIds
        });

        await fetchData();
        closeModal();
    } catch (error) {
        handleError(error, 'Ошибка сохранения дидактических единиц');
    }
};

const fetchData = async () => {
    try {
        loading.value = true;

        // Используем оптимизированные запросы с eager loading
        const [modulsResponse, modulSubjectsResponse, opSubjectsResponse, didacticUnitsResponse, competenciesResponse] = await Promise.all([
            axios.get('/api/moduls'), // Уже включает modulSubjects и profCompetencies с eager loading
            axios.get('/api/modulsubjects'), // Нужен для детальной информации
            axios.get('/api/op-subjects'), // Нужен для детальной информации
            axios.get('/api/didactic-units'),
            axios.get('/api/prof-competencies') // Уже включает modulSubjects и opSubjects с eager loading
        ]);

        modules.value = modulsResponse.data;
        modulSubjects.value = modulSubjectsResponse.data;
        opSubjects.value = opSubjectsResponse.data;
        didacticUnits.value = didacticUnitsResponse.data;
        competencies.value = competenciesResponse.data;
        
        // Загружаем ДЕ по ПК для отображения (оптимизированная версия с одним запросом)
        await loadDidacticUnitsForDisplay();
    } catch (error) {
        handleError(error, 'Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
/* Дополнительные стили при необходимости */
</style>
