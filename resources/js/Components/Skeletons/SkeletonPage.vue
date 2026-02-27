<template>
    <div class="animate-in fade-in duration-300">
        <div v-if="type === 'table'">
            <SkeletonCard v-if="withCards" />
            <SkeletonTable />
        </div>
        <div v-else-if="type === 'form'">
            <SkeletonForm />
        </div>
        <div v-else class="space-y-6">
            <div class="flex justify-between items-center mb-8">
                <SkeletonBase className="h-8 w-64" />
                <SkeletonBase className="h-10 w-40" />
            </div>
            <SkeletonCard />
            <SkeletonTable />
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';
import SkeletonTable from './SkeletonTable.vue';
import SkeletonForm from './SkeletonForm.vue';
import SkeletonCard from './SkeletonCard.vue';

defineProps({
    type: {
        type: String,
        default: 'default' // 'table', 'form', 'default'
    },
    withCards: {
        type: Boolean,
        default: true
    }
});
</script>
