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

        <!-- Анализ отсутствующих ДЕ -->
        <div v-if="!loading && analysisData.length > 0" class="mt-12 bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl shadow-xl border-2 border-amber-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-500">
                <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Анализ отсутствующих дидактических единиц
                </h3>
                <p class="text-amber-100 text-sm mt-1">Список ПК, МДК и ОП, к которым не добавлены ДЕ или отсутствуют определенные типы</p>
            </div>
            
            <div class="p-6 space-y-4">
                <div
                    v-for="(item, index) in paginatedAnalysisData"
                    :key="index"
                    class="bg-white rounded-lg p-4 border-l-4 shadow-md"
                    :class="item.subjectType === 'modul' ? 'border-blue-500' : 'border-purple-500'"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold px-2 py-1 rounded"
                                    :class="item.subjectType === 'modul' 
                                        ? 'bg-blue-100 text-blue-800' 
                                        : 'bg-purple-100 text-purple-800'"
                                >
                                    {{ item.subjectType === 'modul' ? 'МДК' : 'ОП' }}
                                </span>
                                <span class="font-bold text-gray-900">{{ item.subjectName }}</span>
                            </div>
                            <div class="text-sm text-gray-600 ml-4">
                                <span class="font-medium">ПК:</span> {{ item.competencyName }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4 space-y-2">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Отсутствуют типы ДЕ:</div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="missingType in item.missingTypes"
                                :key="missingType"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border-2"
                                :class="getTypeClass(missingType)"
                            >
                                <div class="w-2 h-2 rounded-full" :class="getTypeDotClass(missingType)"></div>
                                {{ missingType }}
                            </span>
                        </div>
                        <div v-if="item.missingTypes.length === 0" class="text-xs text-green-600 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Все типы ДЕ добавлены
                        </div>
                    </div>
                </div>
                
                <div v-if="analysisData.length === 0" class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-lg font-medium">Все ДЕ добавлены!</p>
                    <p class="text-sm mt-1">Нет отсутствующих дидактических единиц</p>
                </div>
                
                <!-- Пагинация -->
                <div v-if="analysisData.length > 0 && totalAnalysisPages > 1" class="flex items-center justify-between pt-4 border-t border-amber-200 mt-6">
                    <div class="text-sm text-gray-600">
                        Показано {{ (currentAnalysisPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentAnalysisPage * itemsPerPage, analysisData.length) }} из {{ analysisData.length }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="currentAnalysisPage = Math.max(1, currentAnalysisPage - 1)"
                            :disabled="currentAnalysisPage === 1"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Предыдущая
                        </button>
                        <div class="flex items-center gap-1">
                            <template v-for="page in visibleAnalysisPages" :key="page">
                                <button
                                    v-if="page !== '...'"
                                    @click="currentAnalysisPage = page"
                                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                    :class="page === currentAnalysisPage
                                        ? 'bg-amber-500 text-white'
                                        : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else
                                    class="px-2 py-2 text-sm text-gray-500"
                                >
                                    {{ page }}
                                </span>
                            </template>
                        </div>
                        <button
                            @click="currentAnalysisPage = Math.min(totalAnalysisPages, currentAnalysisPage + 1)"
                            :disabled="currentAnalysisPage === totalAnalysisPages"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Следующая
                            <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const loading = ref(true);
const tableData = ref([]);
const modules = ref([]);
const competencies = ref([]);
const modulSubjects = ref([]);
const opSubjects = ref([]);
const currentAnalysisPage = ref(1);
const itemsPerPage = 10;

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

// Анализ отсутствующих ДЕ
const analysisData = computed(() => {
    const analysis = [];
    const allTypes = ['знать', 'уметь', 'иметь практический опыт'];
    
    // Создаем карту существующих ДЕ по ключу: subjectType_subjectId_competencyId
    const existingDE = {};
    tableData.value.forEach(row => {
        const key = `${row.subjectType}_${row.subjectId}_${row.competencyId}`;
        if (!existingDE[key]) {
            existingDE[key] = {
                'знать': [],
                'уметь': [],
                'иметь практический опыт': []
            };
        }
        const typeRu = typeMapping[row.unitType] || row.unitType;
        if (existingDE[key][typeRu]) {
            existingDE[key][typeRu].push(row.unitId);
        }
    });
    
    // Проверяем все связи ПК с МДК и ОП
    competencies.value.forEach(competency => {
        // Проверяем МДК
        if (competency.modul_subjects && competency.modul_subjects.length > 0) {
            competency.modul_subjects.forEach(mdk => {
                const key = `modul_${mdk.id}_${competency.id}`;
                const existing = existingDE[key] || {
                    'знать': [],
                    'уметь': [],
                    'иметь практический опыт': []
                };
                
                const missingTypes = allTypes.filter(type => 
                    existing[type].length === 0
                );
                
                if (missingTypes.length > 0) {
                    analysis.push({
                        subjectType: 'modul',
                        subjectId: mdk.id,
                        subjectName: mdk.name,
                        competencyId: competency.id,
                        competencyName: competency.name,
                        missingTypes: missingTypes
                    });
                }
            });
        }
        
        // Проверяем ОП
        if (competency.op_subjects && competency.op_subjects.length > 0) {
            competency.op_subjects.forEach(op => {
                const key = `op_${op.id}_${competency.id}`;
                const existing = existingDE[key] || {
                    'знать': [],
                    'уметь': [],
                    'иметь практический опыт': []
                };
                
                // Для ОП проверяем только "знать" и "уметь"
                const requiredTypes = ['знать', 'уметь'];
                const missingTypes = requiredTypes.filter(type => 
                    existing[type].length === 0
                );
                
                if (missingTypes.length > 0) {
                    analysis.push({
                        subjectType: 'op',
                        subjectId: op.id,
                        subjectName: op.name,
                        competencyId: competency.id,
                        competencyName: competency.name,
                        missingTypes: missingTypes
                    });
                }
            });
        }
    });
    
    return analysis;
});

const getTypeClass = (type) => {
    if (type === 'знать') return 'bg-blue-50 text-blue-700 border-blue-300';
    if (type === 'уметь') return 'bg-purple-50 text-purple-700 border-purple-300';
    return 'bg-green-50 text-green-700 border-green-300';
};

const getTypeDotClass = (type) => {
    if (type === 'знать') return 'bg-blue-500';
    if (type === 'уметь') return 'bg-purple-500';
    return 'bg-green-500';
};

// Пагинация для анализа
const totalAnalysisPages = computed(() => {
    return Math.ceil(analysisData.value.length / itemsPerPage);
});

const paginatedAnalysisData = computed(() => {
    const start = (currentAnalysisPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return analysisData.value.slice(start, end);
});

const visibleAnalysisPages = computed(() => {
    const total = totalAnalysisPages.value;
    const current = currentAnalysisPage.value;
    const pages = [];
    
    if (total <= 7) {
        // Если страниц мало, показываем все
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        // Показываем первую, последнюю и текущую с соседями
        pages.push(1);
        
        if (current > 3) {
            pages.push('...');
        }
        
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        if (current < total - 2) {
            pages.push('...');
        }
        
        pages.push(total);
    }
    
    return pages.filter((page, index, arr) => {
        if (page === '...') return true;
        return arr.indexOf(page) === index;
    });
});

const fetchData = async () => {
    try {
        loading.value = true;

        const [modulsResponse, deDataResponse, competenciesResponse, modulSubjectsResponse, opSubjectsResponse] = await Promise.all([
            axios.get('/api/moduls'),
            axios.get('/api/didactic-units-table'),
            axios.get('/api/prof-competencies'),
            axios.get('/api/modulsubjects'),
            axios.get('/api/op-subjects')
        ]);

        modules.value = modulsResponse.data;
        tableData.value = deDataResponse.data;
        competencies.value = competenciesResponse.data;
        modulSubjects.value = modulSubjectsResponse.data;
        opSubjects.value = opSubjectsResponse.data;
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
        alert('Ошибка загрузки данных');
    } finally {
        loading.value = false;
    }
};

// Сбрасываем страницу при изменении количества страниц
watch(totalAnalysisPages, (newTotal) => {
    if (currentAnalysisPage.value > newTotal && newTotal > 0) {
        currentAnalysisPage.value = 1;
    }
});

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

