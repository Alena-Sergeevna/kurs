<template>
    <div>
        <!-- Заголовок страницы -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Управление связями
            </h2>
            <p class="text-lg text-gray-600">Привязывайте МДК и ОП к профессиональным компетенциям</p>
        </div>

        <!-- Индикатор загрузки -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
            <div class="relative">
                <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-8 h-8 bg-blue-600 rounded-full animate-pulse"></div>
                </div>
            </div>
            <p class="mt-6 text-gray-600 font-medium">Загрузка данных...</p>
        </div>

        <!-- Список модулей -->
        <div v-else class="space-y-8">
            <div
                v-for="modul in moduls"
                :key="modul.id"
                class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100"
            >
                <!-- Заголовок модуля -->
                <div class="relative px-8 py-6 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 overflow-hidden">
                    <div class="absolute inset-0 bg-black/5"></div>
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white">{{ modul.name }}</h3>
                        </div>
                        <div class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-semibold">
                            {{ modul.prof_competencies?.length || 0 }} компетенций
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6">
                    <div
                        v-for="(competency, idx) in modul.prof_competencies"
                        :key="competency.id"
                        class="mb-8 last:mb-0"
                    >
                        <!-- Заголовок компетенции -->
                        <div class="flex items-start justify-between mb-6 pb-6 border-b border-gray-100 last:border-b-0">
                            <div class="flex-1">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="mt-1 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold text-gray-900 mb-2 leading-tight">{{ competency.name }}</h4>
                                        <div class="flex items-center gap-6 mt-3">
                                            <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 rounded-lg">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                <span class="text-sm font-medium text-gray-700">
                                                    МДК: <span class="text-blue-600 font-bold">{{ competency.modul_subjects?.length || 0 }}</span>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 px-3 py-1.5 bg-purple-50 rounded-lg">
                                                <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                                <span class="text-sm font-medium text-gray-700">
                                                    ОП: <span class="text-purple-600 font-bold">{{ competency.op_subjects?.length || 0 }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4 flex items-center gap-3">
                                <button
                                    v-if="!isApproved(competency) && hasDraft(competency)"
                                    @click="approveRelations(competency.id)"
                                    class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Утвердить
                                </button>
                                <button
                                    v-if="!isApproved(competency)"
                                    @click="toggleEditMode(competency.id)"
                                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg v-if="editingCompetencyId !== competency.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ editingCompetencyId === competency.id ? 'Отмена' : 'Редактировать' }}
                                </button>
                                <div
                                    v-if="isApproved(competency)"
                                    class="flex items-center gap-3"
                                >
                                    <div class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl font-semibold shadow-lg flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Утверждено
                                    </div>
                                    <button
                                        @click="unapproveRelations(competency.id)"
                                        class="px-4 py-3 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                                        title="Разутвердить связи"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Текущие связи -->
                        <div v-if="editingCompetencyId !== competency.id" class="space-y-5">
                            <!-- МДК -->
                            <div>
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-600 rounded-full"></div>
                                    <h5 class="text-sm font-bold text-gray-700 uppercase tracking-wider">МДК</h5>
                                </div>
                                <div v-if="competency.modul_subjects && competency.modul_subjects.length > 0" class="space-y-2">
                                    <div
                                        v-for="subject in competency.modul_subjects"
                                        :key="subject.id"
                                        class="flex items-center justify-between p-3 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md"
                                        :class="subject.pivot?.approved
                                            ? 'bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-300'
                                            : 'bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 hover:border-blue-300'"
                                    >
                                        <span class="inline-flex items-center gap-2 text-sm font-medium"
                                            :class="subject.pivot?.approved ? 'text-emerald-800' : 'text-blue-800'"
                                        >
                                            <span v-if="subject.pivot?.approved" class="text-emerald-600 font-bold">✓</span>
                                            {{ subject.name }}
                                            <span v-if="hasDraftForRelation('modul', subject.id, competency.id)" 
                                                  class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold"
                                                  title="Есть оценка">
                                                ⭐
                                            </span>
                                        </span>
                                        <button
                                            v-if="subject.pivot?.approved"
                                            @click="openDraftEditor('modul', subject.id, subject.name, competency.id, competency.name, modul.id)"
                                            class="px-3 py-1.5 rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1"
                                            :class="hasDraftForRelation('modul', subject.id, competency.id)
                                                ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                            :title="hasDraftForRelation('modul', subject.id, competency.id) ? 'Редактировать оценку' : 'Оценить'"
                                        >
                                            <svg v-if="hasDraftForRelation('modul', subject.id, competency.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            {{ hasDraftForRelation('modul', subject.id, competency.id) ? 'Редактировать оценку' : 'Оценить' }}
                                            <span v-if="hasDraftForRelation('modul', subject.id, competency.id)" class="ml-1 text-xs">●</span>
                                        </button>
                                        <div v-else class="px-3 py-1.5 text-xs text-gray-400 italic">
                                            Сначала утвердите связь
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic pl-3">МДК не привязаны</div>
                            </div>

                            <!-- ОП -->
                            <div>
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-1 h-6 bg-gradient-to-b from-purple-500 to-purple-600 rounded-full"></div>
                                    <h5 class="text-sm font-bold text-gray-700 uppercase tracking-wider">ОП</h5>
                                </div>
                                <div v-if="competency.op_subjects && competency.op_subjects.length > 0" class="space-y-2">
                                    <div
                                        v-for="subject in competency.op_subjects"
                                        :key="subject.id"
                                        class="flex items-center justify-between p-3 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md"
                                        :class="subject.pivot?.approved
                                            ? 'bg-gradient-to-r from-emerald-50 to-green-50 border-2 border-emerald-300'
                                            : 'bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 hover:border-purple-300'"
                                    >
                                        <span class="inline-flex items-center gap-2 text-sm font-medium"
                                            :class="subject.pivot?.approved ? 'text-emerald-800' : 'text-purple-800'"
                                        >
                                            <span v-if="subject.pivot?.approved" class="text-emerald-600 font-bold">✓</span>
                                            {{ subject.name }}
                                            <span v-if="hasDraftForRelation('op', subject.id, competency.id)" 
                                                  class="px-1.5 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold"
                                                  title="Есть оценка">
                                                ⭐
                                            </span>
                                        </span>
                                        <button
                                            v-if="subject.pivot?.approved"
                                            @click="openDraftEditor('op', subject.id, subject.name, competency.id, competency.name, modul.id)"
                                            class="px-3 py-1.5 rounded-lg transition-all duration-200 text-xs font-medium shadow-md hover:shadow-lg flex items-center gap-1"
                                            :class="hasDraftForRelation('op', subject.id, competency.id)
                                                ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                                : 'bg-purple-500 hover:bg-purple-600 text-white'"
                                            :title="hasDraftForRelation('op', subject.id, competency.id) ? 'Редактировать оценку' : 'Оценить'"
                                        >
                                            <svg v-if="hasDraftForRelation('op', subject.id, competency.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            {{ hasDraftForRelation('op', subject.id, competency.id) ? 'Редактировать оценку' : 'Оценить' }}
                                            <span v-if="hasDraftForRelation('op', subject.id, competency.id)" class="ml-1 text-xs">●</span>
                                        </button>
                                        <div v-else class="px-3 py-1.5 text-xs text-gray-400 italic">
                                            Сначала утвердите связь
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic pl-3">ОП не привязаны</div>
                            </div>
                        </div>

                        <!-- Форма редактирования -->
                        <div v-else class="space-y-6 bg-gradient-to-br from-gray-50 to-blue-50/30 p-8 rounded-2xl border-2 border-blue-200/50 shadow-inner">
                            <div>
                                <label class="block text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <div class="w-1.5 h-5 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full"></div>
                                    МДК и ОП (можно выбрать несколько)
                                </label>
                                <MultiSelect
                                    v-model="editForms[competency.id].subjectIds"
                                    :items="getAvailableSubjects(modul.id)"
                                    placeholder="Выберите МДК или ОП..."
                                    :item-label="getSubjectLabel"
                                    item-value="id"
                                    :item-color="getSubjectColor"
                                />
                            </div>

                            <div class="flex justify-end gap-3 pt-6 border-t border-gray-300">
                                <button
                                    @click="cancelEdit(competency.id)"
                                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 rounded-xl transition-all duration-200 font-semibold shadow-md hover:shadow-lg border border-gray-200"
                                >
                                    Отмена
                                </button>
                                <button
                                    @click="saveRelations(competency.id)"
                                    class="px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Сохранить черновик
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="!modul.prof_competencies || modul.prof_competencies.length === 0" class="text-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-lg font-medium">Компетенции не найдены</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Компонент редактора черновиков -->
        <DraftEditor
            :show="showDraftEditor"
            :subject-type="draftEditorData.subjectType"
            :subject-id="draftEditorData.subjectId"
            :subject-name="draftEditorData.subjectName"
            :competency-id="draftEditorData.competencyId"
            :competency-name="draftEditorData.competencyName"
            :competency-module-id="draftEditorData.competencyModuleId"
            :available-competencies="availableCompetenciesList"
            :existing-draft="draftEditorData.existingDraft"
            :draft-batch-id="draftEditorData.draftBatchId"
            @close="closeDraftEditor"
            @saved="onDraftSaved"
            @openDidacticUnitDraftEditor="handleOpenDidacticUnitDraftEditor"
        />
        
        <!-- Редактор черновиков ДЕ -->
        <DidacticUnitDraftEditor
            :show="showDidacticUnitDraftEditor"
            :subject-type="didacticUnitDraftEditorData.subjectType"
            :subject-id="didacticUnitDraftEditorData.subjectId"
            :subject-name="didacticUnitDraftEditorData.subjectName"
            :competency-id="didacticUnitDraftEditorData.competencyId"
            :competency-name="didacticUnitDraftEditorData.competencyName"
            :current-units="didacticUnitDraftEditorData.currentUnits"
            :all-didactic-units="didacticUnitDraftEditorData.allDidacticUnits"
            :existing-draft="didacticUnitDraftEditorData.existingDraft"
            :draft-batch-id="didacticUnitDraftEditorData.draftBatchId"
            :is-move="didacticUnitDraftEditorData.isMove"
            :original-competency-id="didacticUnitDraftEditorData.originalCompetencyId"
            :original-subject-type="didacticUnitDraftEditorData.originalSubjectType"
            :original-subject-id="didacticUnitDraftEditorData.originalSubjectId"
            @close="closeDidacticUnitDraftEditor"
            @saved="onDidacticUnitDraftSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import MultiSelect from '../ui/MultiSelect.vue';
import DraftEditor from '../ui/DraftEditor.vue';
import DidacticUnitDraftEditor from '../ui/DidacticUnitDraftEditor.vue';
import { useErrorHandler } from '../../composables/useErrorHandler';
import { useDrafts } from '../../composables/useDrafts';

const { handleError } = useErrorHandler();
const { fetchDrafts, findSubjectCompetencyDraft, findDidacticUnitDraft } = useDrafts();

const moduls = ref([]);
const allModulSubjects = ref([]);
const allOpSubjects = ref([]);
const loading = ref(true);
const editingCompetencyId = ref(null);
const editForms = ref({});

// Для редактора черновиков
const showDraftEditor = ref(false);
const draftEditorData = ref({
    subjectType: '',
    subjectId: null,
    subjectName: '',
    competencyId: null,
    competencyName: '',
    competencyModuleId: null
});
const allCompetencies = ref([]);
const draftsMap = ref(new Map()); // Map для хранения черновиков: key = "subjectType_subjectId_competencyId"

// Computed для передачи компетенций в DraftEditor
const availableCompetenciesList = computed(() => {
    return allCompetencies.value || [];
});

// Проверка наличия черновика для связи
const hasDraftForRelation = (subjectType, subjectId, competencyId) => {
    const key = `${subjectType}_${subjectId}_${competencyId}`;
    return draftsMap.value.has(key);
};

// Получить черновик для связи
const getDraftForRelation = (subjectType, subjectId, competencyId) => {
    const key = `${subjectType}_${subjectId}_${competencyId}`;
    return draftsMap.value.get(key) || null;
};

const fetchData = async () => {
    try {
        loading.value = true;

        const [modulsResponse, competenciesResponse, modulSubjectsResponse, opSubjectsResponse] = await Promise.all([
            axios.get('/api/moduls'),
            axios.get('/api/prof-competencies'),
            axios.get('/api/modulsubjects'),
            axios.get('/api/op-subjects'),
        ]);

        const modulsData = modulsResponse.data;
        allCompetencies.value = competenciesResponse.data;
        allModulSubjects.value = modulSubjectsResponse.data;
        allOpSubjects.value = opSubjectsResponse.data;

        // Загружаем черновики и создаем карту
        try {
            const draftsResponse = await axios.get('/api/drafts');
            draftsMap.value.clear();
            if (draftsResponse.data && draftsResponse.data.length > 0) {
                // Загружаем детали черновиков параллельно
                const draftDetailsPromises = draftsResponse.data.slice(0, 20).map(draftSummary => 
                    axios.get(`/api/drafts/${draftSummary.draft_batch_id}`).catch(() => null)
                );
                const draftDetailsResults = await Promise.all(draftDetailsPromises);
                
                draftDetailsResults.forEach((draftDetails, index) => {
                    if (draftDetails?.data?.subject_competency_changes) {
                        draftDetails.data.subject_competency_changes.forEach(change => {
                            const key = `${change.original.subject_type}_${change.original.subject_id}_${change.original.prof_competency_id}`;
                            draftsMap.value.set(key, {
                                draftBatchId: draftsResponse.data[index].draft_batch_id,
                                action: change.action,
                                comment: change.comment
                            });
                        });
                    }
                });
            }
        } catch (error) {
            // Игнорируем ошибки загрузки черновиков
            console.warn('Ошибка загрузки черновиков:', error);
        }

        // Группируем компетенции по модулям
        moduls.value = modulsData.map(modul => ({
            ...modul,
            prof_competencies: allCompetencies.value.filter(comp => comp.id_module === modul.id)
        }));
    } catch (error) {
        handleError(error, 'Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

const isApproved = (competency) => {
    const hasModulSubjects = competency.modul_subjects && competency.modul_subjects.length > 0;
    const hasOpSubjects = competency.op_subjects && competency.op_subjects.length > 0;

    if (!hasModulSubjects && !hasOpSubjects) return false;

    const modulSubjectsApproved = hasModulSubjects
        ? competency.modul_subjects.every(s => s.pivot?.approved)
        : true;
    const opSubjectsApproved = hasOpSubjects
        ? competency.op_subjects.every(s => s.pivot?.approved)
        : true;

    return modulSubjectsApproved && opSubjectsApproved;
};

const getAvailableSubjects = (moduleId) => {
    // Фильтруем МДК по модулю
    const moduleModulSubjects = allModulSubjects.value.filter(ms => ms.id_module === moduleId);
    
    // Объединяем МДК и ОП с меткой типа
    const combinedSubjects = [
        ...moduleModulSubjects.map(ms => ({ ...ms, subjectType: 'mdk' })),
        ...allOpSubjects.value.map(op => ({ ...op, subjectType: 'op' }))
    ];
    
    return combinedSubjects;
};

const getSubjectLabel = (item) => {
    const prefix = item.subjectType === 'mdk' ? 'МДК: ' : 'ОП: ';
    return prefix + item.name;
};

const getSubjectColor = (item) => {
    return item.subjectType === 'mdk'
        ? 'bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 border-blue-300'
        : 'bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 border-purple-300';
};

const toggleEditMode = (competencyId) => {
    if (editingCompetencyId.value === competencyId) {
        editingCompetencyId.value = null;
        delete editForms.value[competencyId];
    } else {
        editingCompetencyId.value = competencyId;

        // Оптимизированный поиск: создаем Map для быстрого доступа (O(1) вместо O(n))
        let competency = null;
        let moduleId = null;
        
        for (const modul of moduls.value) {
            if (modul.prof_competencies) {
                // Используем find только один раз на модуль
                competency = modul.prof_competencies.find(c => c.id === competencyId);
                if (competency) {
                    moduleId = modul.id;
                    break;
                }
            }
        }

        if (competency) {
            // Объединяем выбранные МДК и ОП в один массив
            const selectedIds = [
                ...(competency.modul_subjects || []).map(s => s.id),
                ...(competency.op_subjects || []).map(s => s.id)
            ];
            
            editForms.value[competencyId] = {
                subjectIds: selectedIds,
                moduleId: moduleId
            };
        }
    }
};

const cancelEdit = (competencyId) => {
    editingCompetencyId.value = null;
    delete editForms.value[competencyId];
};

const saveRelations = async (competencyId) => {
    try {
        const form = editForms.value[competencyId];
        const selectedIds = Array.isArray(form.subjectIds) ? form.subjectIds.map(id => parseInt(id)) : [];
        
        // Получаем доступные предметы для этого модуля
        const availableSubjects = getAvailableSubjects(form.moduleId);
        
        // Разделяем на МДК и ОП
        const modulSubjectIds = [];
        const opSubjectIds = [];
        
        availableSubjects.forEach(subject => {
            if (selectedIds.includes(subject.id)) {
                if (subject.subjectType === 'mdk') {
                    modulSubjectIds.push(subject.id);
                } else {
                    opSubjectIds.push(subject.id);
                }
            }
        });

        // Сохраняем как черновик (approved = false)
        await axios.put(`/api/prof-competencies/${competencyId}/modul-subjects`, {
            modul_subject_ids: modulSubjectIds,
            approve: false
        });

        await axios.put(`/api/prof-competencies/${competencyId}/op-subjects`, {
            op_subject_ids: opSubjectIds,
            approve: false
        });

        await fetchData();
        cancelEdit(competencyId);
    } catch (error) {
        handleError(error, 'Ошибка сохранения связей');
    }
};

const approveRelations = async (competencyId) => {
    try {
        await axios.post(`/api/prof-competencies/${competencyId}/approve`);
        await fetchData();
    } catch (error) {
        handleError(error, 'Ошибка утверждения связей');
    }
};

const unapproveRelations = async (competencyId) => {
    try {
        await axios.post(`/api/prof-competencies/${competencyId}/unapprove`);
        await fetchData();
    } catch (error) {
        handleError(error, 'Ошибка разутверждения связей');
    }
};

const hasDraft = (competency) => {
    const hasModulSubjects = competency.modul_subjects && competency.modul_subjects.length > 0;
    const hasOpSubjects = competency.op_subjects && competency.op_subjects.length > 0;

    if (!hasModulSubjects && !hasOpSubjects) return false;

    // Проверяем, есть ли хотя бы одна неутвержденная связь
    const hasUnapprovedModul = hasModulSubjects
        ? competency.modul_subjects.some(s => !s.pivot?.approved)
        : false;
    const hasUnapprovedOp = hasOpSubjects
        ? competency.op_subjects.some(s => !s.pivot?.approved)
        : false;

    return hasUnapprovedModul || hasUnapprovedOp;
};

const openDraftEditor = async (subjectType, subjectId, subjectName, competencyId, competencyName, competencyModuleId) => {
    // Проверяем утверждение связи
    const competency = moduls.value
        .flatMap(m => m.prof_competencies || [])
        .find(c => c.id === competencyId);
    
    if (!competency) {
        handleError(new Error('Компетенция не найдена'), 'Ошибка');
        return;
    }
    
    // Проверяем утверждение конкретной связи
    const subject = subjectType === 'modul' 
        ? competency.modul_subjects?.find(s => s.id === subjectId)
        : competency.op_subjects?.find(s => s.id === subjectId);
    
    if (!subject || !subject.pivot?.approved) {
        handleError(new Error('Связь не утверждена'), 'Нельзя оценить неутвержденную связь');
        return;
    }
    
    // Проверяем существование черновика
    const existingDraft = await findSubjectCompetencyDraft(subjectType, subjectId, competencyId);
    
    draftEditorData.value = {
        subjectType,
        subjectId,
        subjectName,
        competencyId,
        competencyName,
        competencyModuleId,
        existingDraft: existingDraft || null,
        draftBatchId: existingDraft?.draft_batch_id || null
    };
    showDraftEditor.value = true;
};

const closeDraftEditor = () => {
    showDraftEditor.value = false;
};

const onDraftSaved = async () => {
    await fetchDrafts(); // Обновляем список черновиков
    // Обновляем карту черновиков
    await fetchData(); // Перезагружаем данные для обновления визуальных индикаторов
};

// Для редактора черновиков ДЕ
const showDidacticUnitDraftEditor = ref(false);
const didacticUnitDraftEditorData = ref({
    subjectType: '',
    subjectId: null,
    subjectName: '',
    competencyId: null,
    competencyName: '',
    currentUnits: [],
    allDidacticUnits: [],
    existingDraft: null,
    draftBatchId: null,
    isMove: false,
    originalCompetencyId: null,
    originalSubjectType: null,
    originalSubjectId: null
});

const handleOpenDidacticUnitDraftEditor = async (data) => {
    try {
        // Загружаем все ДЕ
        const unitsResponse = await axios.get('/api/didactic-units');
        const allDidacticUnits = unitsResponse.data || [];
        
        // Если это перенос (есть originalCompetencyId), загружаем ДЕ из старого ПК
        let currentUnits = [];
        if (data.originalCompetencyId && data.originalSubjectType && data.originalSubjectId) {
            // Загружаем ДЕ из старого ПК (откуда переносим)
            const originalKey = `${data.originalSubjectType}_${data.originalSubjectId}_${data.originalCompetencyId}`;
            try {
                const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
                    subjects: [{
                        subject_type: data.originalSubjectType,
                        subject_id: data.originalSubjectId,
                        competency_id: data.originalCompetencyId
                    }]
                });
                currentUnits = response.data[originalKey] || [];
                console.log('Loaded units from original PC:', currentUnits);
            } catch (e) {
                console.warn('Error loading units from original PC:', e);
                currentUnits = [];
            }
        } else {
            // Загружаем текущие ДЕ для этой связи (если есть)
            const key = `${data.subjectType}_${data.subjectId}_${data.competencyId}`;
            try {
                const response = await axios.post('/api/didactic-units/bulk-load-by-subjects', {
                    subjects: [{
                        subject_type: data.subjectType,
                        subject_id: data.subjectId,
                        competency_id: data.competencyId
                    }]
                });
                currentUnits = response.data[key] || [];
            } catch (e) {
                currentUnits = [];
            }
        }
        
        // Ищем существующий черновик ДЕ
        const existingDraft = await findDidacticUnitDraft(data.subjectType, data.subjectId, data.competencyId);
        
        didacticUnitDraftEditorData.value = {
            subjectType: data.subjectType,
            subjectId: data.subjectId,
            subjectName: data.subjectName,
            competencyId: data.competencyId, // Новый ПК
            competencyName: data.competencyName,
            currentUnits, // ДЕ из старого ПК (если перенос) или текущие ДЕ
            allDidacticUnits,
            existingDraft: existingDraft || null,
            draftBatchId: data.draftBatchId || null,
            // Информация о переносе
            isMove: !!(data.originalCompetencyId && data.originalSubjectType && data.originalSubjectId),
            originalCompetencyId: data.originalCompetencyId || null,
            originalSubjectType: data.originalSubjectType || null,
            originalSubjectId: data.originalSubjectId || null
        };
        
        showDidacticUnitDraftEditor.value = true;
    } catch (error) {
        handleError(error, 'Ошибка открытия редактора черновиков ДЕ');
    }
};

const closeDidacticUnitDraftEditor = () => {
    showDidacticUnitDraftEditor.value = false;
};

const onDidacticUnitDraftSaved = async () => {
    await fetchDrafts(); // Обновляем список черновиков
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
/* Component styles */
</style>
