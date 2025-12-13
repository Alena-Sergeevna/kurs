<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Таблица связей дидактических единиц
            </h2>
            <p class="text-lg text-gray-600">Все созданные связи между модулями, компетенциями, дисциплинами и дидактическими единицами</p>
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
                            <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-64">
                                Профессиональная компетенция
                            </th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider border-r border-gray-200 w-64">
                                МДК / ОП
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Дидактические единицы
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template v-for="(moduleRow, moduleIndex) in groupedTableData" :key="moduleIndex">
                            <template v-for="(competencyRow, compIndex) in moduleRow.competencies" :key="compIndex">
                                <template v-for="(subjectRow, subjectIndex) in competencyRow.subjects" :key="subjectIndex">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td v-if="compIndex === 0 && subjectIndex === 0" :rowspan="getModuleRowspan(moduleRow)" class="px-3 py-4 border-r border-gray-100 align-top w-16">
                                            <div class="flex flex-col items-center justify-center h-full min-h-[60px]">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md mb-2">
                                                    {{ getModuleNumber(moduleRow.moduleId) }}
                                                </div>
                                                <div class="vertical-text text-xs font-medium text-gray-900 text-center">
                                                    {{ moduleRow.moduleName }}
                                                </div>
                                            </div>
                                        </td>
                                        <td v-if="subjectIndex === 0" :rowspan="competencyRow.subjects.length" class="px-4 py-4 border-r border-gray-100 align-top w-64 text-center">
                                            <div class="text-sm font-medium text-gray-900 break-words">{{ competencyRow.competencyName }}</div>
                                        </td>
                                        <td class="px-4 py-4 border-r border-gray-100 w-64">
                                            <div class="flex items-start gap-2">
                                                <span class="text-xs font-semibold px-2 py-1 rounded whitespace-nowrap flex-shrink-0"
                                                    :class="subjectRow.subjectType === 'modul' 
                                                        ? 'bg-blue-100 text-blue-800' 
                                                        : 'bg-purple-100 text-purple-800'"
                                                >
                                                    {{ subjectRow.subjectType === 'modul' ? 'МДК' : 'ОП' }}
                                                </span>
                                                <span class="text-sm text-gray-900 break-words">{{ subjectRow.subjectName }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-2">
                                                <template v-for="type in ['знать', 'уметь', 'иметь практический опыт']" :key="type">
                                                    <div v-if="subjectRow.didacticUnits[type] && subjectRow.didacticUnits[type].length > 0">
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <div class="w-2 h-2 rounded-full"
                                                                :class="type === 'знать' ? 'bg-blue-500' : type === 'уметь' ? 'bg-purple-500' : 'bg-green-500'"
                                                            ></div>
                                                            <span class="text-xs font-bold text-gray-600 uppercase">{{ type }}:</span>
                                                        </div>
                                                        <div class="pl-4 flex flex-wrap gap-1.5">
                                                            <span
                                                                v-for="unit in subjectRow.didacticUnits[type]"
                                                                :key="unit.id"
                                                                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
                                                            >
                                                                {{ unit.name }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div v-if="!hasAnyDidacticUnits(subjectRow)" class="text-xs text-gray-400 italic">
                                                    Нет дидактических единиц
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Анализ незаполненных ДЕ -->
        <div v-if="!loading && groupedTableData.length > 0" class="mt-8 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl shadow-xl border-2 border-yellow-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-yellow-500 to-orange-500">
                <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Анализ незаполненных дидактических единиц
                </h3>
                <p class="text-yellow-100 text-sm mt-1">Показывает, какие типы ДЕ не заполнены у каких ПК для каждого МДК/ОП</p>
            </div>
            
            <div class="p-6">
                <div v-if="missingAnalysis.length === 0" class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Все ДЕ заполнены!</h4>
                    <p class="text-gray-600">У всех МДК/ОП заполнены все типы дидактических единиц для всех ПК</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(item, index) in missingAnalysis"
                        :key="index"
                        class="bg-white rounded-lg p-4 border-l-4 border-yellow-500 shadow-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm shadow-md">
                                {{ getModuleNumber(item.moduleId) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 rounded"
                                        :class="item.subjectType === 'modul' 
                                            ? 'bg-blue-100 text-blue-800' 
                                            : 'bg-purple-100 text-purple-800'"
                                    >
                                        {{ item.subjectType === 'modul' ? 'МДК' : 'ОП' }}
                                    </span>
                                    <span class="text-base font-bold text-gray-900">{{ item.subjectName }}</span>
                                </div>
                                <div class="text-sm text-gray-700 mb-2">
                                    <span class="font-medium">ПК:</span> {{ item.competencyName }}
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="missingType in item.missingTypes"
                                        :key="missingType"
                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium"
                                        :class="getMissingTypeClass(missingType)"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Не заполнено: {{ missingType }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useErrorHandler } from '../../composables/useErrorHandler';

const { handleError } = useErrorHandler();

const loading = ref(true);
const tableData = ref([]);
const modules = ref([]);
const allCompetencies = ref([]);
const allModulSubjects = ref([]);
const allOpSubjects = ref([]);

const typeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

const groupedTableData = computed(() => {
    const grouped = {};
    
    tableData.value.forEach(row => {
        if (!grouped[row.moduleId]) {
            grouped[row.moduleId] = {
                moduleId: row.moduleId,
                moduleName: row.moduleName,
                competencies: {}
            };
        }
        
        const compKey = `${row.competencyId}`;
        if (!grouped[row.moduleId].competencies[compKey]) {
            grouped[row.moduleId].competencies[compKey] = {
                competencyId: row.competencyId,
                competencyName: row.competencyName,
                subjects: {}
            };
        }
        
        const subjectKey = `${row.subjectType}_${row.subjectId}`;
        if (!grouped[row.moduleId].competencies[compKey].subjects[subjectKey]) {
            grouped[row.moduleId].competencies[compKey].subjects[subjectKey] = {
                subjectId: row.subjectId,
                subjectName: row.subjectName,
                subjectType: row.subjectType,
                didacticUnits: {
                    'знать': [],
                    'уметь': [],
                    'иметь практический опыт': []
                }
            };
        }
        
        // Добавляем ДЕ по типу
        const typeRu = typeMapping[row.unitType] || row.unitType;
        if (grouped[row.moduleId].competencies[compKey].subjects[subjectKey].didacticUnits[typeRu]) {
            grouped[row.moduleId].competencies[compKey].subjects[subjectKey].didacticUnits[typeRu].push({
                id: row.unitId,
                name: row.unitName
            });
        }
    });
    
    // Преобразуем в массив и сортируем
    return Object.values(grouped)
        .map(moduleRow => ({
            ...moduleRow,
            competencies: Object.values(moduleRow.competencies)
                .map(compRow => ({
                    ...compRow,
                    subjects: Object.values(compRow.subjects)
                }))
        }))
        .sort((a, b) => {
            const indexA = modules.value.findIndex(m => m.id === a.moduleId);
            const indexB = modules.value.findIndex(m => m.id === b.moduleId);
            return indexA - indexB;
        });
});

const getModuleRowspan = (moduleRow) => {
    let count = 0;
    moduleRow.competencies.forEach(comp => {
        count += comp.subjects.length;
    });
    return count;
};

const hasAnyDidacticUnits = (subjectRow) => {
    return subjectRow.didacticUnits['знать'].length > 0 ||
           subjectRow.didacticUnits['уметь'].length > 0 ||
           subjectRow.didacticUnits['иметь практический опыт'].length > 0;
};

const getModuleNumber = (moduleId) => {
    const index = modules.value.findIndex(m => m.id === moduleId);
    return index !== -1 ? index + 1 : '';
};

// Анализ незаполненных ДЕ
const missingAnalysis = computed(() => {
    const missing = [];
    
    // Создаем Map для быстрого поиска ДЕ по ключу: subjectType_subjectId_competencyId
    const deMap = new Map();
    tableData.value.forEach(row => {
        const key = `${row.subjectType}_${row.subjectId}_${row.competencyId}`;
        if (!deMap.has(key)) {
            deMap.set(key, {
                'знать': [],
                'уметь': [],
                'иметь практический опыт': []
            });
        }
        const typeRu = typeMapping[row.unitType] || row.unitType;
        if (deMap.get(key)[typeRu]) {
            deMap.get(key)[typeRu].push(row.unitId);
        }
    });
    
    // Проходим по всем связям ПК с МДК/ОП
    allCompetencies.value.forEach(competency => {
        const module = modules.value.find(m => m.id === competency.id_module);
        if (!module) return;
        
        // Проверяем МДК
        if (competency.modul_subjects) {
            competency.modul_subjects.forEach(mdk => {
                const key = `modul_${mdk.id}_${competency.id}`;
                const deData = deMap.get(key) || {
                    'знать': [],
                    'уметь': [],
                    'иметь практический опыт': []
                };
                
                // Для МДК проверяем все 3 типа
                const requiredTypes = ['знать', 'уметь', 'иметь практический опыт'];
                const missingTypes = requiredTypes.filter(type => {
                    return !deData[type] || deData[type].length === 0;
                });
                
                if (missingTypes.length > 0) {
                    missing.push({
                        moduleId: module.id,
                        moduleName: module.name,
                        competencyId: competency.id,
                        competencyName: competency.name,
                        subjectId: mdk.id,
                        subjectName: mdk.name,
                        subjectType: 'modul',
                        missingTypes: missingTypes
                    });
                }
            });
        }
        
        // Проверяем ОП
        if (competency.op_subjects) {
            competency.op_subjects.forEach(op => {
                const key = `op_${op.id}_${competency.id}`;
                const deData = deMap.get(key) || {
                    'знать': [],
                    'уметь': [],
                    'иметь практический опыт': []
                };
                
                // Для ОП проверяем только 2 типа (без "иметь практический опыт")
                const requiredTypes = ['знать', 'уметь'];
                const missingTypes = requiredTypes.filter(type => {
                    return !deData[type] || deData[type].length === 0;
                });
                
                if (missingTypes.length > 0) {
                    missing.push({
                        moduleId: module.id,
                        moduleName: module.name,
                        competencyId: competency.id,
                        competencyName: competency.name,
                        subjectId: op.id,
                        subjectName: op.name,
                        subjectType: 'op',
                        missingTypes: missingTypes
                    });
                }
            });
        }
    });
    
    // Сортируем по модулю, затем по компетенции
    return missing.sort((a, b) => {
        const moduleIndexA = modules.value.findIndex(m => m.id === a.moduleId);
        const moduleIndexB = modules.value.findIndex(m => m.id === b.moduleId);
        if (moduleIndexA !== moduleIndexB) {
            return moduleIndexA - moduleIndexB;
        }
        return a.competencyId - b.competencyId;
    });
});

const getMissingTypeClass = (type) => {
    if (type === 'знать') return 'bg-blue-100 text-blue-800 border border-blue-300';
    if (type === 'уметь') return 'bg-purple-100 text-purple-800 border border-purple-300';
    return 'bg-green-100 text-green-800 border border-green-300';
};

const fetchData = async () => {
    try {
        loading.value = true;

        const [modulsResponse, deDataResponse, competenciesResponse] = await Promise.all([
            axios.get('/api/moduls'),
            axios.get('/api/didactic-units-table'),
            axios.get('/api/prof-competencies')
        ]);

        modules.value = modulsResponse.data;
        tableData.value = deDataResponse.data;
        allCompetencies.value = competenciesResponse.data;
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
.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
}
</style>

