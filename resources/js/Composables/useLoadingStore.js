import { reactive, readonly, toRef } from 'vue';

const state = reactive({
    isLoading: false,
    startTime: null,
    minLoadingTime: 100, // ms (anti-flicker)
    debounceDelay: 50, // ms (don't show for extremely fast requests)
    startTimeoutId: null,
    stopTimeoutId: null
});

export function useLoadingStore() {
    const startLoading = () => {
        // console.log('[Loading] Start requested');
        // Clear any pending stop timeout (if we started a new request while finishing the previous one)
        if (state.stopTimeoutId) {
            clearTimeout(state.stopTimeoutId);
            state.stopTimeoutId = null;
        }
        
        // Clear any pending start timeout
        if (state.startTimeoutId) {
            clearTimeout(state.startTimeoutId);
        }

        state.startTime = Date.now();
        
        // Use a timeout to avoid showing the skeleton for fast requests
        state.startTimeoutId = setTimeout(() => {
            console.log('[Loading] Showing skeleton');
            state.isLoading = true;
            state.startTimeoutId = null;
        }, state.debounceDelay);
    };

    const stopLoading = () => {
        // console.log('[Loading] Stop requested');
        // Clear any pending start timeout
        if (state.startTimeoutId) {
            clearTimeout(state.startTimeoutId);
            state.startTimeoutId = null;
        }

        const elapsedTime = Date.now() - (state.startTime || Date.now());
        const remainingTime = Math.max(0, state.minLoadingTime - elapsedTime);

        if (state.isLoading) {
            // Ensure skeleton stays for at least minLoadingTime if it was shown
            state.stopTimeoutId = setTimeout(() => {
                console.log('[Loading] Hiding skeleton');
                state.isLoading = false;
                state.startTime = null;
                state.stopTimeoutId = null;
            }, remainingTime);
        } else {
            // If it wasn't shown yet (request was fast), just reset
            // console.log('[Loading] Resetting (was not shown)');
            state.isLoading = false;
            state.startTime = null;
        }
    };

    return {
        isLoading: toRef(state, 'isLoading'),
        startLoading,
        stopLoading
    };
}
