<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Таблица связей
            </h2>
            <p class="text-lg text-gray-600">Все созданные связи между модулями, компетенциями и дисциплинами</p>
        </div>

        <!-- Таблица -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <div v-else-if="groupedTableData.length === 0" class="text-center py-20">
            <p class="text-gray-500 text-lg">Нет данных для отображения</p>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-blue-50 to-indigo-50">
                        <tr>
                            <th class="px-3 py-6 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-16">
                                <div class="vertical-text whitespace-nowrap">
                                    Вид деятельности
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200">
                                Профессиональная компетенция
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                МДК / ОП
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template v-for="(moduleRow, moduleIndex) in groupedTableData" :key="moduleIndex">
                            <template v-for="(competency, compIndex) in moduleRow.competencies" :key="compIndex">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td v-if="compIndex === 0" :rowspan="moduleRow.competencies.length" class="px-3 py-4 border-r border-gray-100 align-top w-16">
                                        <div class="flex flex-col items-center justify-center h-full min-h-[60px]">
                                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md mb-2">
                                                {{ getModuleNumber(moduleRow.moduleId) }}
                                            </div>
                                            <div class="vertical-text text-xs font-medium text-gray-900 text-center">
                                                {{ moduleRow.moduleName }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 border-r border-gray-100">
                                        <div class="text-sm text-gray-900">{{ competency.competencyName }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <!-- МДК -->
                                            <span
                                                v-for="mdk in competency.mdks"
                                                :key="`mdk-${mdk.id}`"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 shadow-sm"
                                                :class="mdk.approved 
                                                    ? 'bg-gradient-to-r from-emerald-50 to-green-50 text-emerald-800 border border-emerald-300' 
                                                    : 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-800 border border-blue-200'"
                                            >
                                                <span v-if="mdk.approved" class="text-emerald-600 font-bold">✓</span>
                                                <span class="font-semibold">МДК:</span>
                                                {{ mdk.name }}
                                            </span>
                                            <!-- ОП -->
                                            <span
                                                v-for="op in competency.ops"
                                                :key="`op-${op.id}`"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 shadow-sm"
                                                :class="op.approved 
                                                    ? 'bg-gradient-to-r from-emerald-50 to-green-50 text-emerald-800 border border-emerald-300' 
                                                    : 'bg-gradient-to-r from-purple-50 to-pink-50 text-purple-800 border border-purple-200'"
                                            >
                                                <span v-if="op.approved" class="text-emerald-600 font-bold">✓</span>
                                                <span class="font-semibold">ОП:</span>
                                                {{ op.name }}
                                            </span>
                                            <span v-if="competency.mdks.length === 0 && competency.ops.length === 0" class="text-xs text-gray-400 italic">
                                                Нет связей
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Статистика -->
        <div v-if="!loading" class="space-y-6 mt-6">
            <!-- Неиспользованные МДК по модулям -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Неиспользованные МДК по видам деятельности
                </h3>
                <div v-if="unusedMdkByModule.length === 0" class="text-sm text-gray-500 italic">
                    Все МДК используются
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="item in unusedMdkByModule"
                        :key="item.moduleId"
                        class="flex items-center justify-between p-4 bg-orange-50 rounded-lg border border-orange-200"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                                {{ getModuleNumber(item.moduleId) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ item.moduleName }}</p>
                                <p class="text-sm text-gray-600">{{ item.count }} неиспользованных МДК</p>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-orange-600">{{ item.count }}</div>
                    </div>
                </div>
            </div>

            <!-- ОП без привязки к ПК -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    ОП без привязки к профессиональным компетенциям
                </h3>
                <div v-if="opsWithoutCompetencies.length === 0" class="text-sm text-gray-500 italic">
                    Все ОП привязаны к ПК
                </div>
                <div v-else class="flex flex-wrap gap-2">
                    <span
                        v-for="op in opsWithoutCompetencies"
                        :key="op.id"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-800 rounded-lg border border-red-200 text-sm font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ op.name }}
                    </span>
                </div>
            </div>

            <!-- ПК без привязок -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    ПК без привязки к МДК и ОП
                </h3>
                <div v-if="competenciesWithoutSubjects.length === 0" class="text-sm text-gray-500 italic">
                    Все ПК имеют привязки
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="comp in competenciesWithoutSubjects"
                        :key="comp.id"
                        class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                                {{ getModuleNumber(comp.id_module) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ comp.name }}</span>
                        </div>
                        <span class="text-xs text-gray-500">Нет связей</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const tableData = ref([]);
const modules = ref([]);
const allModulSubjects = ref([]);
const allOpSubjects = ref([]);
const allCompetencies = ref([]);

const groupedTableData = computed(() => {
    // Группируем по модулям
    const grouped = {};
    
    tableData.value.forEach(row => {
        if (!grouped[row.moduleId]) {
            grouped[row.moduleId] = {
                moduleId: row.moduleId,
                moduleName: row.moduleName,
                competencies: []
            };
        }
        
        grouped[row.moduleId].competencies.push({
            competencyId: row.competencyId,
            competencyName: row.competencyName,
            mdks: row.mdks,
            ops: row.ops
        });
    });
    
    // Преобразуем в массив и сортируем по номеру модуля
    return Object.values(grouped).sort((a, b) => {
        const indexA = modules.value.findIndex(m => m.id === a.moduleId);
        const indexB = modules.value.findIndex(m => m.id === b.moduleId);
        return indexA - indexB;
    });
});

const totalRowsCount = computed(() => {
    return groupedTableData.value.reduce((sum, moduleRow) => sum + moduleRow.competencies.length, 0);
});

// Неиспользованные МДК по модулям
const unusedMdkByModule = computed(() => {
    const usedMdkIds = new Set();
    tableData.value.forEach(row => {
        row.mdks.forEach(mdk => {
            usedMdkIds.add(mdk.id);
        });
    });

    const unusedByModule = {};
    
    allModulSubjects.value.forEach(mdk => {
        if (!usedMdkIds.has(mdk.id)) {
            if (!unusedByModule[mdk.id_module]) {
                unusedByModule[mdk.id_module] = {
                    moduleId: mdk.id_module,
                    moduleName: modules.value.find(m => m.id === mdk.id_module)?.name || 'Неизвестный модуль',
                    count: 0
                };
            }
            unusedByModule[mdk.id_module].count++;
        }
    });

    return Object.values(unusedByModule)
        .sort((a, b) => {
            const indexA = modules.value.findIndex(m => m.id === a.moduleId);
            const indexB = modules.value.findIndex(m => m.id === b.moduleId);
            return indexA - indexB;
        });
});

// ОП без привязки к ПК
const opsWithoutCompetencies = computed(() => {
    const usedOpIds = new Set();
    tableData.value.forEach(row => {
        row.ops.forEach(op => {
            usedOpIds.add(op.id);
        });
    });

    return allOpSubjects.value.filter(op => !usedOpIds.has(op.id));
});

// ПК без привязок
const competenciesWithoutSubjects = computed(() => {
    return allCompetencies.value.filter(comp => {
        const row = tableData.value.find(r => r.competencyId === comp.id);
        return !row || (row.mdks.length === 0 && row.ops.length === 0);
    });
});

const getModuleNumber = (moduleId) => {
    const index = modules.value.findIndex(m => m.id === moduleId);
    return index !== -1 ? index + 1 : '';
};

const fetchData = async () => {
    try {
        loading.value = true;

        const [modulsResponse, competenciesResponse, modulSubjectsResponse, opSubjectsResponse] = await Promise.all([
            axios.get('/api/moduls'),
            axios.get('/api/prof-competencies'),
            axios.get('/api/modulsubjects'),
            axios.get('/api/op-subjects')
        ]);

        modules.value = modulsResponse.data;
        allCompetencies.value = competenciesResponse.data;
        allModulSubjects.value = modulSubjectsResponse.data;
        allOpSubjects.value = opSubjectsResponse.data;

        // Формируем данные для таблицы
        const data = [];
        
        allCompetencies.value.forEach(competency => {
            const module = modules.value.find(m => m.id === competency.id_module);
            
            if (module) {
                data.push({
                    moduleId: module.id,
                    moduleName: module.name,
                    competencyId: competency.id,
                    competencyName: competency.name,
                    mdks: (competency.modul_subjects || []).map(mdk => ({
                        id: mdk.id,
                        name: mdk.name,
                        approved: mdk.pivot?.approved || false
                    })),
                    ops: (competency.op_subjects || []).map(op => ({
                        id: op.id,
                        name: op.name,
                        approved: op.pivot?.approved || false
                    }))
                });
            }
        });

        tableData.value = data;
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
        alert('Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
}
</style>


