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
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const tableData = ref([]);
const modules = ref([]);

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

const fetchData = async () => {
    try {
        loading.value = true;

        const [modulsResponse, deDataResponse] = await Promise.all([
            axios.get('/api/moduls'),
            axios.get('/api/didactic-units-table')
        ]);

        modules.value = modulsResponse.data;
        tableData.value = deDataResponse.data;
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

