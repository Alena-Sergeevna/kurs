<template>
    <div class="relative" ref="containerRef">
        <div
            @click="toggleDropdown"
            class="w-full px-4 py-3.5 bg-white border-2 rounded-xl shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flex items-center justify-between min-h-[52px] transition-all duration-200"
            :class="isOpen 
                ? 'ring-2 ring-blue-500 border-blue-500 shadow-md' 
                : 'border-gray-200 hover:border-gray-300 hover:shadow-md'"
        >
            <div class="flex flex-wrap gap-2 flex-1">
                <span
                    v-if="selectedItems.length === 0"
                    class="text-gray-400 text-sm"
                >
                    {{ placeholder }}
                </span>
                <span
                    v-for="item in selectedItems"
                    :key="item.id"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 shadow-sm hover:shadow-md"
                    :class="getItemColor(item)"
                >
                    {{ getItemLabel(item) }}
                    <button
                        v-if="!readonly"
                        @click.stop="removeItem(item.id)"
                        class="ml-1 text-gray-500 hover:text-gray-700 font-bold transition-colors"
                    >
                        ×
                    </button>
                </span>
            </div>
            <svg
                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute z-50 w-full mt-2 bg-white border-2 border-gray-200 rounded-xl shadow-2xl max-h-72 overflow-hidden"
            >
                <div class="p-3">
                    <input
                        v-if="searchable"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Поиск..."
                        class="w-full px-4 py-2.5 mb-2 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-sm"
                        @click.stop
                        @focus.stop
                    />
                    <div class="max-h-56 overflow-y-auto custom-scrollbar">
                        <div
                            v-for="item in filteredItems"
                            :key="item.id"
                            @click="toggleItem(item)"
                            class="px-4 py-3 cursor-pointer rounded-lg flex items-center gap-3 transition-all duration-150 group"
                            :class="isSelected(item.id) 
                                ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200' 
                                : 'hover:bg-gray-50 border-2 border-transparent'"
                        >
                            <div class="relative flex items-center">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(item.id)"
                                    class="w-5 h-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all cursor-pointer"
                                    @click.stop
                                    @change="toggleItem(item)"
                                />
                            </div>
                            <span class="text-sm font-medium text-gray-900 flex-1">{{ getItemLabel(item) }}</span>
                            <svg 
                                v-if="isSelected(item.id)"
                                class="w-5 h-5 text-blue-600" 
                                fill="currentColor" 
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div v-if="filteredItems.length === 0" class="px-4 py-8 text-sm text-gray-500 text-center">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Ничего не найдено
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    items: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Выберите элементы...'
    },
    searchable: {
        type: Boolean,
        default: true
    },
    itemLabel: {
        type: [String, Function],
        default: 'name'
    },
    itemValue: {
        type: String,
        default: 'id'
    },
    itemColor: {
        type: [String, Function],
        default: 'bg-blue-100 text-blue-800 border-blue-300'
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);

const selectedItems = computed(() => {
    return props.items.filter(item => 
        props.modelValue.includes(item[props.itemValue])
    );
});

const filteredItems = computed(() => {
    if (!props.searchable || !searchQuery.value) {
        return props.items;
    }
    const query = searchQuery.value.toLowerCase();
    return props.items.filter(item => {
        const label = getItemLabel(item).toLowerCase();
        return label.includes(query);
    });
});

const getItemLabel = (item) => {
    return typeof props.itemLabel === 'function' 
        ? props.itemLabel(item) 
        : item[props.itemLabel];
};

const getItemColor = (item) => {
    return typeof props.itemColor === 'function'
        ? props.itemColor(item)
        : props.itemColor;
};

const isSelected = (id) => {
    return props.modelValue.includes(id);
};

const toggleItem = (item) => {
    if (props.readonly) return;
    
    const itemId = item[props.itemValue];
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(itemId);
    
    if (index > -1) {
        newValue.splice(index, 1);
    } else {
        newValue.push(itemId);
    }
    
    emit('update:modelValue', newValue);
};

const removeItem = (id) => {
    if (props.readonly) return;
    
    const newValue = props.modelValue.filter(itemId => itemId !== id);
    emit('update:modelValue', newValue);
};

const toggleDropdown = () => {
    if (props.readonly) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
    }
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
