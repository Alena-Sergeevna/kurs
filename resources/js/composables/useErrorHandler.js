import { ref } from 'vue';

const errorMessage = ref('');
const showError = ref(false);

/**
 * Композабл для централизованной обработки ошибок
 * @returns {Object} Объект с методами обработки ошибок
 */
export function useErrorHandler() {
    const handleError = (error, defaultMessage = 'Произошла ошибка') => {
        console.error('Ошибка:', error);
        
        let message = defaultMessage;
        
        if (error.response) {
            // Ошибка от сервера
            if (error.response.data?.message) {
                message = error.response.data.message;
            } else if (error.response.data?.error) {
                message = error.response.data.error;
            } else if (error.response.status === 422) {
                message = 'Ошибка валидации данных';
            } else if (error.response.status === 404) {
                message = 'Ресурс не найден';
            } else if (error.response.status === 500) {
                message = 'Внутренняя ошибка сервера';
            }
        } else if (error.message) {
            message = error.message;
        }
        
        errorMessage.value = message;
        showError.value = true;
        
        // Автоматически скрываем ошибку через 5 секунд
        setTimeout(() => {
            showError.value = false;
        }, 5000);
    };

    const clearError = () => {
        showError.value = false;
        errorMessage.value = '';
    };

    return {
        errorMessage,
        showError,
        handleError,
        clearError
    };
}

