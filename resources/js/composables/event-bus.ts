import mitt from 'mitt'

export const FILE_UPLOAD_STARTED = 'FILE_UPLOAD_STARTED';
export const SHOW_ERROR_DIALOG = 'SHOW_ERROR_DIALOG';
export const SHOW_NOTIFICATION = 'SHOW_NOTIFICATION';

export const emitter = mitt()


export function showSuccessNotification(message: string) {
    emitter.emit(SHOW_NOTIFICATION, { type: 'success', message });
}

export function showErrorNotification(message: string) {
    emitter.emit(SHOW_NOTIFICATION, { type: 'error', message });
}
