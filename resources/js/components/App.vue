<template>
    <div id="app" class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <nav class="bg-white/80 backdrop-blur-md shadow-lg border-b border-gray-200/50 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                Управление ДЕ
                            </h1>
                            <p class="text-xs text-gray-500 mt-0.5">Система связей и компетенций</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="setCurrentView('relations')"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                            :class="currentView === 'relations' 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                        >
                            Управление связями
                        </button>
                        <button
                            @click="setCurrentView('table')"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                            :class="currentView === 'table' 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                        >
                            Таблица связей ПК
                        </button>
                        <button
                            @click="setCurrentView('didactic')"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                            :class="currentView === 'didactic' 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                        >
                            Дидактические единицы
                        </button>
                        <button
                            @click="setCurrentView('didactic-table')"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                            :class="currentView === 'didactic-table' 
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-lg' 
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                        >
                            Таблица ДЕ
                        </button>
                        <button
                            @click="setCurrentView('duplicates')"
                            class="px-4 py-2 rounded-lg font-medium transition-all duration-200"
                            :class="currentView === 'duplicates' 
                                ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-lg' 
                                : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                        >
                            Анализ дубликатов
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-8 sm:px-6 lg:px-8">
            <div class="px-4 sm:px-0">
                <RelationsView v-if="currentView === 'relations'" />
                <RelationsTable v-if="currentView === 'table'" />
                <DidacticUnitsView v-if="currentView === 'didactic'" />
                <DidacticUnitsTable v-if="currentView === 'didactic-table'" />
                <DuplicatesAnalysisView v-if="currentView === 'duplicates'" />
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RelationsView from './views/RelationsView.vue';
import RelationsTable from './views/RelationsTable.vue';
import DidacticUnitsView from './views/DidacticUnitsView.vue';
import DidacticUnitsTable from './views/DidacticUnitsTable.vue';
import DuplicatesAnalysisView from './views/DuplicatesAnalysisView.vue';

const currentView = ref('relations');

// Сохраняем текущий view в localStorage при изменении
const setCurrentView = (view) => {
    currentView.value = view;
    localStorage.setItem('currentView', view);
};

// Восстанавливаем view из localStorage при загрузке
onMounted(() => {
    const savedView = localStorage.getItem('currentView');
    if (savedView && ['relations', 'table', 'didactic', 'didactic-table', 'duplicates'].includes(savedView)) {
        currentView.value = savedView;
    }
});
</script>

<style scoped>
/* Component styles */
</style>
