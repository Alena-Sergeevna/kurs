<template>
    <div class="space-y-6">
        <!-- Заголовок -->
        <div class="mb-10">
            <h2 class="text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                Анализ дублирования дидактических единиц
            </h2>
            <p class="text-lg text-gray-600">Поиск одинаковых ДЕ</p>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Результаты -->
        <div v-else class="space-y-6">
            <!-- Статистика -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-lg border-2 border-blue-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-md">
                        <div class="text-sm font-medium text-gray-600 mb-1">Всего ДЕ</div>
                        <div class="text-3xl font-bold text-gray-900">{{ statistics.totalUnits }}</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-md">
                        <div class="text-sm font-medium text-gray-600 mb-1">Уникальных текстов</div>
                        <div class="text-3xl font-bold text-blue-600">{{ statistics.uniqueTexts }}</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-md">
                        <div class="text-sm font-medium text-gray-600 mb-1">Дубликатов</div>
                        <div class="text-3xl font-bold text-orange-600">{{ statistics.duplicatesCount }}</div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-md">
                        <div class="text-sm font-medium text-gray-600 mb-1">Неиспользуемых</div>
                        <div class="text-3xl font-bold text-red-600">{{ statistics.unusedCount }}</div>
                    </div>
                </div>
            </div>

            <!-- Неиспользуемые ДЕ -->
            <div v-if="unusedUnits.length > 0" class="bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl shadow-xl border-2 border-red-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-red-500 to-orange-500">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Неиспользуемые дидактические единицы
                    </h3>
                    <p class="text-red-100 text-sm mt-1">ДЕ, которые не связаны ни с одним МДК/ОП и ПК</p>
                </div>
                
                <div class="p-6 space-y-3">
                    <div
                        v-for="(unit, index) in paginatedUnusedUnits"
                        :key="unit.id"
                        class="bg-white rounded-lg p-4 border-l-4 border-red-500 shadow-md flex items-center justify-between"
                    >
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-red-600 font-bold">
                                {{ (unusedPage - 1) * itemsPerPage + index + 1 }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-semibold px-2 py-1 rounded"
                                        :class="getTypeClass(unit.type)"
                                    >
                                        {{ getTypeLabel(unit.type) }}
                                    </span>
                                    <h4 class="text-base font-bold text-gray-900">{{ unit.name }}</h4>
                                </div>
                                <div class="text-xs text-gray-500">ID: #{{ unit.id }}</div>
                            </div>
                        </div>
                        <button
                            @click="deleteUnit(unit.id)"
                            :disabled="deletingId === unit.id"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg v-if="deletingId !== unit.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ deletingId === unit.id ? 'Удаление...' : 'Удалить' }}
                        </button>
                    </div>

                    <!-- Пагинация для неиспользуемых -->
                    <div v-if="unusedUnits.length > itemsPerPage" class="flex items-center justify-between pt-4 border-t border-red-200 mt-4">
                        <div class="text-sm text-gray-600">
                            Показано {{ (unusedPage - 1) * itemsPerPage + 1 }} - {{ Math.min(unusedPage * itemsPerPage, unusedUnits.length) }} из {{ unusedUnits.length }}
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="unusedPage = Math.max(1, unusedPage - 1)"
                                :disabled="unusedPage === 1"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Предыдущая
                            </button>
                            <div class="flex items-center gap-1">
                                <template v-for="page in visibleUnusedPages" :key="page">
                                    <button
                                        v-if="page !== '...'"
                                        @click="unusedPage = page"
                                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                        :class="page === unusedPage
                                            ? 'bg-red-500 text-white'
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
                                @click="unusedPage = Math.min(totalUnusedPages, unusedPage + 1)"
                                :disabled="unusedPage === totalUnusedPages"
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

            <!-- Список дубликатов -->
            <div v-if="duplicates.length === 0" class="bg-white rounded-2xl shadow-xl border-2 border-green-200 p-12 text-center">
                <svg class="w-20 h-20 mx-auto mb-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Дубликатов не найдено!</h3>
                <p class="text-gray-600">Все дидактические единицы имеют уникальный текст</p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="(duplicate, index) in paginatedDuplicates"
                    :key="index"
                    class="bg-white rounded-xl shadow-lg border-l-4 border-orange-500 overflow-hidden relative"
                >
                    <!-- Номер дубликата -->
                    <div class="absolute top-4 right-4 w-8 h-8 bg-orange-500 text-white rounded-full flex items-center justify-center text-sm font-bold z-10">
                        {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                    </div>
                    <div class="p-6 pr-16">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-bold">
                                        {{ duplicate.duplicatesCount }}
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ duplicate.text }}</h3>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="text-xs font-semibold px-2 py-1 rounded"
                                        :class="getTypeClass(duplicate.type)"
                                    >
                                        {{ getTypeLabel(duplicate.type) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ duplicate.duplicatesCount }} {{ pluralize(duplicate.duplicatesCount, 'дубликат', 'дубликата', 'дубликатов') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Места использования дубликатов -->
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <div class="text-xs font-semibold text-gray-600 uppercase mb-3">Где используется ({{ duplicate.locations.length }} {{ pluralize(duplicate.locations.length, 'место', 'места', 'мест') }}):</div>
                            <div class="space-y-2">
                                <div
                                    v-for="(location, locIndex) in duplicate.locations"
                                    :key="locIndex"
                                    class="bg-white rounded-lg p-3 border border-gray-200"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-xs shadow-md">
                                            {{ getModuleNumber(location.moduleId) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 mb-2">{{ location.moduleName }}</div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-xs font-semibold px-2 py-1 rounded"
                                                    :class="location.subjectType === 'modul' 
                                                        ? 'bg-blue-100 text-blue-800' 
                                                        : 'bg-purple-100 text-purple-800'"
                                                >
                                                    {{ location.subjectType === 'modul' ? 'МДК' : 'ОП' }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900">{{ location.subjectName }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                <span class="font-medium">ПК:</span>
                                                <ul v-if="location.competencies.length > 1" class="mt-1 ml-4 list-disc space-y-0.5">
                                                    <li v-for="comp in location.competencies" :key="comp.id">
                                                        {{ comp.name }}
                                                    </li>
                                                </ul>
                                                <span v-else class="ml-1">{{ location.competencies[0]?.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Пагинация -->
                <div v-if="duplicates.length > itemsPerPage" class="flex items-center justify-between pt-4 border-t border-gray-200 mt-6">
                    <div class="text-sm text-gray-600">
                        Показано {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, duplicates.length) }} из {{ duplicates.length }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="currentPage = Math.max(1, currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Предыдущая
                        </button>
                        <div class="flex items-center gap-1">
                            <template v-for="page in visiblePages" :key="page">
                                <button
                                    v-if="page !== '...'"
                                    @click="currentPage = page"
                                    class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                    :class="page === currentPage
                                        ? 'bg-orange-500 text-white'
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
                            @click="currentPage = Math.min(totalPages, currentPage + 1)"
                            :disabled="currentPage === totalPages"
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
import { useErrorHandler } from '../../composables/useErrorHandler';
import { useConfirmDialog } from '../../composables/useConfirmDialog';

const { handleError } = useErrorHandler();
const { confirm } = useConfirmDialog();

const loading = ref(true);
const duplicates = ref([]);
const unusedUnits = ref([]);
const statistics = ref({
    totalUnits: 0,
    uniqueTexts: 0,
    duplicatesCount: 0,
    unusedCount: 0
});
const modules = ref([]);
const currentPage = ref(1);
const unusedPage = ref(1);
const itemsPerPage = 10;
const deletingId = ref(null);

const typeMapping = {
    'know': 'знать',
    'be_able': 'уметь',
    'have_practical_experience': 'иметь практический опыт'
};

// Пагинация
const totalPages = computed(() => {
    return Math.ceil(duplicates.value.length / itemsPerPage);
});

const paginatedDuplicates = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return duplicates.value.slice(start, end);
});

// Пагинация для неиспользуемых
const totalUnusedPages = computed(() => {
    return Math.ceil(unusedUnits.value.length / itemsPerPage);
});

const paginatedUnusedUnits = computed(() => {
    const start = (unusedPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return unusedUnits.value.slice(start, end);
});

const visibleUnusedPages = computed(() => {
    const total = totalUnusedPages.value;
    const current = unusedPage.value;
    const pages = [];
    
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
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

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages = [];
    
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
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

// Сбрасываем страницу при изменении количества страниц
watch(totalPages, (newTotal) => {
    if (currentPage.value > newTotal && newTotal > 0) {
        currentPage.value = 1;
    }
});

watch(totalUnusedPages, (newTotal) => {
    if (unusedPage.value > newTotal && newTotal > 0) {
        unusedPage.value = 1;
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

const getModuleNumber = (moduleId) => {
    const index = modules.value.findIndex(m => m.id === moduleId);
    return index !== -1 ? index + 1 : '';
};

const pluralize = (count, one, few, many) => {
    const mod10 = count % 10;
    const mod100 = count % 100;
    
    if (mod100 >= 11 && mod100 <= 19) return many;
    if (mod10 === 1) return one;
    if (mod10 >= 2 && mod10 <= 4) return few;
    return many;
};

const deleteUnit = async (id) => {
    const confirmed = await confirm({
        title: 'Удаление дидактической единицы',
        message: 'Вы уверены, что хотите удалить эту дидактическую единицу?'
    });
    
    if (!confirmed) {
        return;
    }

    try {
        deletingId.value = id;
        await axios.delete(`/api/didactic-units/${id}`);
        await fetchData(); // Перезагружаем данные
    } catch (error) {
        handleError(error, 'Ошибка удаления дидактической единицы');
    } finally {
        deletingId.value = null;
    }
};

const fetchData = async () => {
    try {
        loading.value = true;
        const [duplicatesResponse, modulesResponse] = await Promise.all([
            axios.get('/api/didactic-units-duplicates'),
            axios.get('/api/moduls')
        ]);
        
        statistics.value = duplicatesResponse.data.statistics;
        duplicates.value = duplicatesResponse.data.duplicates;
        unusedUnits.value = duplicatesResponse.data.unusedUnits || [];
        modules.value = modulesResponse.data;
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
/* Component styles */
</style>

