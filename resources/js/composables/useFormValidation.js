/**
 * Композабл для валидации форм на клиенте
 * @returns {Object} Объект с методами валидации
 */
export function useFormValidation() {
    /**
     * Валидация имени (не пустое, не слишком длинное)
     * @param {string} name - Имя для валидации
     * @param {number} maxLength - Максимальная длина (по умолчанию 255)
     * @returns {Object} { isValid: boolean, error: string }
     */
    const validateName = (name, maxLength = 255) => {
        if (!name || name.trim().length === 0) {
            return { isValid: false, error: 'Название не может быть пустым' };
        }
        if (name.length > maxLength) {
            return { isValid: false, error: `Название не должно превышать ${maxLength} символов` };
        }
        return { isValid: true, error: null };
    };

    /**
     * Валидация выбранного модуля
     * @param {number|string} moduleId - ID модуля
     * @returns {Object} { isValid: boolean, error: string }
     */
    const validateModule = (moduleId) => {
        if (!moduleId || moduleId === '') {
            return { isValid: false, error: 'Необходимо выбрать модуль' };
        }
        return { isValid: true, error: null };
    };

    /**
     * Валидация массива ID
     * @param {Array} ids - Массив ID
     * @param {string} fieldName - Название поля для сообщения об ошибке
     * @returns {Object} { isValid: boolean, error: string }
     */
    const validateIds = (ids, fieldName = 'Элементы') => {
        if (!Array.isArray(ids)) {
            return { isValid: false, error: `${fieldName} должны быть массивом` };
        }
        // Проверка на валидные ID (положительные числа)
        const invalidIds = ids.filter(id => !Number.isInteger(Number(id)) || Number(id) <= 0);
        if (invalidIds.length > 0) {
            return { isValid: false, error: `${fieldName} содержат невалидные ID` };
        }
        return { isValid: true, error: null };
    };

    /**
     * Валидация формы предмета (МДК/ОП)
     * @param {Object} form - Форма для валидации
     * @param {boolean} requireModule - Требуется ли модуль (для МДК)
     * @returns {Object} { isValid: boolean, errors: Object }
     */
    const validateSubjectForm = (form, requireModule = false) => {
        const errors = {};

        // Валидация имени
        const nameValidation = validateName(form.name);
        if (!nameValidation.isValid) {
            errors.name = nameValidation.error;
        }

        // Валидация модуля (если требуется)
        if (requireModule) {
            const moduleValidation = validateModule(form.id_module);
            if (!moduleValidation.isValid) {
                errors.id_module = moduleValidation.error;
            }
        }

        // Валидация компетенций (опционально)
        if (form.prof_competency_ids && form.prof_competency_ids.length > 0) {
            const compValidation = validateIds(form.prof_competency_ids, 'Компетенции');
            if (!compValidation.isValid) {
                errors.prof_competency_ids = compValidation.error;
            }
        }

        // Валидация дидактических единиц (опционально)
        if (form.didactic_unit_ids && form.didactic_unit_ids.length > 0) {
            const unitsValidation = validateIds(form.didactic_unit_ids, 'Дидактические единицы');
            if (!unitsValidation.isValid) {
                errors.didactic_unit_ids = unitsValidation.error;
            }
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    };

    return {
        validateName,
        validateModule,
        validateIds,
        validateSubjectForm
    };
}

