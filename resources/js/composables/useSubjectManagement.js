import { ref } from 'vue';
import axios from 'axios';
import { useErrorHandler } from './useErrorHandler';
import { useConfirmDialog } from './useConfirmDialog';
import { useFormValidation } from './useFormValidation';

/**
 * Композабл для управления предметами (МДК/ОП)
 * @param {Object} config - Конфигурация композабла
 * @param {string} config.apiEndpoint - Endpoint API для предметов
 * @param {string} config.subjectName - Название предмета для сообщений
 * @param {Function} [config.getFormData] - Функция преобразования формы перед отправкой
 * @param {Function} [config.getInitialForm] - Функция получения начальной формы
 * @param {Function} [config.transformFormForEdit] - Функция преобразования данных для редактирования
 * @returns {Object} Объект с реактивными данными и методами управления
 */
export function useSubjectManagement(config) {
    const {
        apiEndpoint,
        subjectName,
        getFormData = (form) => form,
        getInitialForm = () => ({ name: '', prof_competency_ids: [], didactic_unit_ids: [] }),
        transformFormForEdit = (subject) => ({
            name: subject.name,
            prof_competency_ids: subject.prof_competencies?.map(c => c.id) || [],
            didactic_unit_ids: subject.didactic_units?.map(u => u.id) || []
        })
    } = config;

    const { handleError } = useErrorHandler();
    const { confirm } = useConfirmDialog();
    const { validateSubjectForm } = useFormValidation();

    const subjects = ref([]);
    const loading = ref(true);
    const showModal = ref(false);
    const editingSubject = ref(null);
    const form = ref(getInitialForm());
    const formErrors = ref({});

    const fetchSubjects = async () => {
        try {
            loading.value = true;
            const response = await axios.get(apiEndpoint);
            subjects.value = response.data;
        } catch (error) {
            handleError(error, `Ошибка загрузки ${subjectName}`);
        } finally {
            loading.value = false;
        }
    };

    const saveSubject = async () => {
        // Валидация на клиенте перед отправкой
        const requireModule = config.getInitialForm().hasOwnProperty('id_module');
        const validation = validateSubjectForm(form.value, requireModule);
        
        if (!validation.isValid) {
            formErrors.value = validation.errors;
            // Показываем первую ошибку
            const firstError = Object.values(validation.errors)[0];
            handleError(new Error(firstError), 'Ошибка валидации формы');
            return;
        }

        // Очищаем ошибки при успешной валидации
        formErrors.value = {};

        try {
            const data = getFormData(form.value);

            if (editingSubject.value) {
                await axios.put(`${apiEndpoint}/${editingSubject.value.id}`, data);
            } else {
                await axios.post(apiEndpoint, data);
            }
            closeModal();
            fetchSubjects();
        } catch (error) {
            handleError(error, `Ошибка сохранения ${subjectName}`);
        }
    };

    const editSubject = (subject) => {
        editingSubject.value = subject;
        form.value = transformFormForEdit(subject);
        showModal.value = true;
    };

    const deleteSubject = async (id) => {
        const confirmed = await confirm({
            title: `Удаление ${subjectName}`,
            message: `Вы уверены, что хотите удалить этот ${subjectName}?`
        });
        
        if (!confirmed) return;
        
        try {
            await axios.delete(`${apiEndpoint}/${id}`);
            fetchSubjects();
        } catch (error) {
            handleError(error, `Ошибка удаления ${subjectName}`);
        }
    };

    const closeModal = () => {
        showModal.value = false;
        editingSubject.value = null;
        form.value = getInitialForm();
    };

    return {
        subjects,
        loading,
        showModal,
        editingSubject,
        form,
        formErrors,
        fetchSubjects,
        saveSubject,
        editSubject,
        deleteSubject,
        closeModal
    };
}

