import { ref } from 'vue';

export const isLoading = ref(false);
let loadingStartTime = 0;
const MIN_LOADING_TIME = 1200; // ms

export const startLoading = () => {
      isLoading.value = true;
      loadingStartTime = Date.now();
};

export const stopLoading = () => {
      const currentTime = Date.now();
      const elapsedTime = currentTime - loadingStartTime;
      const remainingTime = Math.max(0, MIN_LOADING_TIME - elapsedTime);

      setTimeout(() => {
            isLoading.value = false;
      }, remainingTime);
};
