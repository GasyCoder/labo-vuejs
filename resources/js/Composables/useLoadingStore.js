import { reactive, readonly } from 'vue';

const state = reactive({
    isLoading: false,
    startTime: null,
    minLoadingTime: 100, // ms (anti-flicker) - REDUCED
    debounceDelay: 50, // ms (don't show for very fast requests) - REDUCED
    timeoutId: null
});

export function useLoadingStore() {
    const startLoading = () => {
        // Clear any pending timeout
        if (state.timeoutId) clearTimeout(state.timeoutId);

        state.startTime = Date.now();
        
        // Use a timeout to avoid showing the skeleton for very fast requests
        state.timeoutId = setTimeout(() => {
            state.isLoading = true;
        }, state.debounceDelay);
    };

    const stopLoading = () => {
        if (state.timeoutId) clearTimeout(state.timeoutId);

        const elapsedTime = Date.now() - (state.startTime || Date.now());
        const remainingTime = Math.max(0, state.minLoadingTime - elapsedTime);

        // Ensure skeleton stays for at least minLoadingTime if it was shown
        if (state.isLoading) {
            setTimeout(() => {
                state.isLoading = false;
                state.startTime = null;
            }, remainingTime);
        } else {
            // If it wasn't shown yet (request was fast), just reset
            state.isLoading = false;
            state.startTime = null;
        }
    };

    return {
        isLoading: readonly(state).isLoading,
        startLoading,
        stopLoading
    };
}
