<template>
    <div class="nk-app-root overflow-hidden">
        <div class="nk-main">
            <Sidebar />
            <div
                class="nk-wrap xl:ps-72 [&>.nk-header]:xl:start-72 [&>.nk-header]:xl:w-[calc(100%-theme(spacing.72))] peer-[&.is-compact:not(.has-hover)]:xl:ps-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:start-[74px] peer-[&.is-compact:not(.has-hover)]:[&>.nk-header]:xl:w-[calc(100%-74px)] flex flex-col min-h-screen transition-all duration-300">

                <Header />

                <!-- pt-24 (96px) : l'équilibre parfait pour une navbar de 64px -->
                <div id="pagecontent" class="nk-content pt-24 px-4 sm:px-6 lg:px-8 bg-white dark:bg-slate-900">
                    <div :class="['container', container ? '' : 'max-w-none']">
                        <div class="relative w-full">
                            <!-- Skeleton Overlay -->
                            <transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="isLoading" key="skeleton" class="absolute inset-0 z-[40] bg-white dark:bg-slate-900 min-h-[500px]">
                                    <SkeletonPage :type="skeletonType" />
                                </div>
                            </transition>

                            <!-- Content Layer (Always mounted to allow onMounted hooks and charts to work) -->
                            <div :class="{'opacity-0 pointer-events-none': isLoading, 'opacity-100': !isLoading}" class="transition-opacity duration-300 w-full">
                                <slot />
                            </div>
                        </div>
                    </div>
                </div><!-- content -->

                <Footer />

            </div>
            <div
                class="sidebar-backdrop sidebar-toggle fixed inset-0 bg-slate-950/20 z-[1030] opacity-0 invisible pointer-events-none transition-opacity duration-300 lg:hidden">
            </div>
        </div>
    </div><!-- root -->
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import Sidebar from './Partials/Sidebar.vue';
import Header from './Partials/Header.vue';
import Footer from './Partials/Footer.vue';
import SkeletonPage from '@/Components/Skeletons/SkeletonPage.vue';
import { useLoadingStore } from '@/Composables/useLoadingStore';

const props = defineProps({
    container: {
        type: Boolean,
        default: false,
    }
});

const page = usePage();
const { isLoading } = useLoadingStore();

// Logic to determine skeleton type based on route or page props
const skeletonType = computed(() => {
    // 1. Check if page explicitly defines a skeleton type in props
    if (page.props.skeleton) return page.props.skeleton;

    // 2. Fallback to route name mapping
    const routeName = route().current();
    
    // Fallback if route name is not available or doesn't match
    if (!routeName) {
        const path = window.location.pathname;
        if (path.includes('/create') || path.includes('/edit')) return 'form';
        if (path.includes('/listes') || path.includes('/index')) return 'table';
        return 'default';
    }

    if (routeName.includes('.index') || routeName.includes('.listes')) return 'table';
    if (routeName.includes('.create') || routeName.includes('.edit')) return 'form';
    if (routeName === 'dashboard') return 'default';

    return 'default';
});

let removeListener = null;

onMounted(() => {
    removeListener = router.on('success', () => {
        const flash = page.props.flash;
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: flash.success,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        }
        if (flash?.error) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: flash.error,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
        }
    });
});

onUnmounted(() => {
    if (removeListener) removeListener();
});
</script>
