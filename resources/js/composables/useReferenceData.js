import { ref } from 'vue';
import axios from 'axios';
import { useErrorHandler } from './useErrorHandler';

/**
 * Композабл для загрузки справочных данных (модули, компетенции, ДЕ)
 * @returns {Object} Объект с реактивными данными и методами загрузки
 */
export function useReferenceData() {
    const { handleError } = useErrorHandler();

    const moduls = ref([]);
    const competencies = ref([]);
    const didacticUnits = ref([]);
    const loading = ref(false);

    const fetchModuls = async () => {
        try {
            const response = await axios.get('/api/moduls');
            moduls.value = response.data;
        } catch (error) {
            handleError(error, 'Ошибка загрузки модулей');
        }
    };

    const fetchCompetencies = async () => {
        try {
            const response = await axios.get('/api/prof-competencies');
            competencies.value = response.data;
        } catch (error) {
            handleError(error, 'Ошибка загрузки компетенций');
        }
    };

    const fetchDidacticUnits = async () => {
        try {
            const response = await axios.get('/api/didactic-units');
            didacticUnits.value = response.data;
        } catch (error) {
            handleError(error, 'Ошибка загрузки дидактических единиц');
        }
    };

    const fetchAll = async () => {
        loading.value = true;
        try {
            await Promise.all([
                fetchModuls(),
                fetchCompetencies(),
                fetchDidacticUnits()
            ]);
        } finally {
            loading.value = false;
        }
    };

    return {
        moduls,
        competencies,
        didacticUnits,
        loading,
        fetchModuls,
        fetchCompetencies,
        fetchDidacticUnits,
        fetchAll
    };
}

