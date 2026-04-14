import { ref, reactive } from 'vue';

const state = reactive({
    show: false,
    title: '',
    message: '',
    type: 'confirm', // 'confirm' or 'alert'
    resolve: null,
});

export function useConfirm() {
    const showConfirm = (title, message) => {
        state.title = title;
        state.message = message;
        state.type = 'confirm';
        state.show = true;
        return new Promise((resolve) => {
            state.resolve = resolve;
        });
    };

    const showAlert = (title, message) => {
        state.title = title;
        state.message = message;
        state.type = 'alert';
        state.show = true;
        return new Promise((resolve) => {
            state.resolve = resolve;
        });
    };

    const confirmAction = (res) => {
        state.show = false;
        if (state.resolve) state.resolve(res);
    };

    return {
        state,
        showConfirm,
        showAlert,
        confirmAction
    };
}
