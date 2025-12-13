import { ref } from 'vue';

const isOpen = ref(false);
const title = ref('');
const message = ref('');
const confirmCallback = ref(null);
const cancelCallback = ref(null);

/**
 * Композабл для модальных окон подтверждения
 * @returns {Object} Объект с методами управления диалогами подтверждения
 */
export function useConfirmDialog() {
    const confirm = (options) => {
        return new Promise((resolve, reject) => {
            title.value = options.title || 'Подтверждение';
            message.value = options.message || 'Вы уверены?';
            isOpen.value = true;
            
            confirmCallback.value = () => {
                isOpen.value = false;
                resolve(true);
                // Очищаем callbacks
                confirmCallback.value = null;
                cancelCallback.value = null;
            };
            
            cancelCallback.value = () => {
                isOpen.value = false;
                resolve(false);
                // Очищаем callbacks
                confirmCallback.value = null;
                cancelCallback.value = null;
            };
        });
    };

    const handleConfirm = () => {
        if (confirmCallback.value) {
            confirmCallback.value();
        }
    };

    const handleCancel = () => {
        if (cancelCallback.value) {
            cancelCallback.value();
        }
    };

    return {
        isOpen,
        title,
        message,
        confirm,
        handleConfirm,
        handleCancel
    };
}

