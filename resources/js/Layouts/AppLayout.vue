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
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                            mode="out-in"
                        >
                            <div v-if="isLoading" key="skeleton">
                                <SkeletonPage :type="skeletonType" />
                            </div>
                            <div v-else key="content">
                                <slot />
                            </div>
                        </transition>
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
    if (!routeName) return 'default';

    if (routeName.endsWith('.index') || routeName.endsWith('.listes')) return 'table';
    if (routeName.endsWith('.create') || routeName.endsWith('.edit')) return 'form';

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
