<template>
    <div id="app" class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex">
        <!-- Боковое меню -->
        <aside 
            class="bg-white/90 backdrop-blur-md shadow-xl border-r border-gray-200/50 transition-all duration-300 ease-in-out z-40"
            :class="sidebarOpen ? 'w-64' : 'w-0 overflow-hidden'"
        >
            <div class="h-full flex flex-col">
                <!-- Заголовок меню -->
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                Управление ДЕ
                            </h1>
                            <p class="text-xs text-gray-500">Система связей</p>
                        </div>
                    </div>
                </div>

                <!-- Навигация -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                    <button
                        @click="setCurrentView('relations')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'relations' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <span>Управление связями</span>
                    </button>
                    <button
                        @click="setCurrentView('table')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'table' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span>Таблица связей ПК</span>
                    </button>
                    <button
                        @click="setCurrentView('didactic')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'didactic' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Дидактические единицы</span>
                    </button>
                    <button
                        @click="setCurrentView('didactic-table')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'didactic-table' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        <span>Таблица ДЕ</span>
                    </button>
                    <button
                        @click="setCurrentView('duplicates')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'duplicates' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Анализ дубликатов</span>
                    </button>
                    <button
                        @click="setCurrentView('drafts')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg font-medium transition-all duration-200 text-left"
                        :class="currentView === 'drafts' 
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Черновики</span>
                    </button>
                </nav>
            </div>
        </aside>

        <!-- Основной контент -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Верхняя панель -->
            <nav class="bg-white/80 backdrop-blur-md shadow-lg border-b border-gray-200/50 sticky top-0 z-50">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <button
                            @click="toggleSidebar"
                            class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-bold text-gray-900">{{ getCurrentViewTitle() }}</h2>
                        </div>
                        <div class="w-10"></div> <!-- Для центрирования -->
                    </div>
                </div>
            </nav>

            <!-- Контент -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
                    <div class="px-4 sm:px-0">
                        <RelationsView v-if="currentView === 'relations'" />
                        <RelationsTable v-if="currentView === 'table'" />
                        <DidacticUnitsView v-if="currentView === 'didactic'" />
                        <DidacticUnitsTable v-if="currentView === 'didactic-table'" />
                        <DuplicatesAnalysisView v-if="currentView === 'duplicates'" />
                        <DraftsListView v-if="currentView === 'drafts'" />
                    </div>
                </div>
            </main>
        </div>

        <!-- Глобальные компоненты для уведомлений и подтверждений -->
        <ErrorNotification />
        <ConfirmDialog />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RelationsView from './views/RelationsView.vue';
import RelationsTable from './views/RelationsTable.vue';
import DidacticUnitsView from './views/DidacticUnitsView.vue';
import DidacticUnitsTable from './views/DidacticUnitsTable.vue';
import DuplicatesAnalysisView from './views/DuplicatesAnalysisView.vue';
import DraftsListView from './views/DraftsListView.vue';
import ErrorNotification from './ui/ErrorNotification.vue';
import ConfirmDialog from './ui/ConfirmDialog.vue';

const currentView = ref('relations');
const sidebarOpen = ref(true);

// Сохраняем текущий view в localStorage при изменении
const setCurrentView = (view) => {
    currentView.value = view;
    localStorage.setItem('currentView', view);
};

// Переключение бокового меню
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('sidebarOpen', sidebarOpen.value.toString());
};

// Получение заголовка текущего view
const getCurrentViewTitle = () => {
    const titles = {
        'relations': 'Управление связями',
        'table': 'Таблица связей ПК',
        'didactic': 'Дидактические единицы',
        'didactic-table': 'Таблица ДЕ',
        'duplicates': 'Анализ дубликатов',
        'drafts': 'Черновики'
    };
    return titles[currentView.value] || 'Управление ДЕ';
};

// Восстанавливаем view из localStorage при загрузке
onMounted(() => {
    const savedView = localStorage.getItem('currentView');
    if (savedView && ['relations', 'table', 'didactic', 'didactic-table', 'duplicates', 'drafts'].includes(savedView)) {
        currentView.value = savedView;
    }
    
    const savedSidebarState = localStorage.getItem('sidebarOpen');
    if (savedSidebarState !== null) {
        sidebarOpen.value = savedSidebarState === 'true';
    }
});
</script>

<style scoped>
/* Component styles */
</style>
