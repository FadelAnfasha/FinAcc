<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import { ref } from 'vue';

const createFormSection = ref<HTMLElement | null>(null);
// Auto scroll to create form
const scrollToCreateForm = () => {
    if (createFormSection.value) {
        createFormSection.value.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
};
</script>

<template>
    <Head title="Process Cost" />

    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Process Cost Calculation</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculation for each process for all product</p>
            </div>
            <!-- Header Section -->
            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Report Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <!-- Process Items Grid -->
            <section ref="ctXsqSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cycle Time x Sales Quantity</h2>
                <DataTable tableStyle="min-width: 50rem">
                    <Column field="no" header="No" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                    <Column field="bp_code" header="BP Code" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                    <Column field="bp_name" header="BP Name" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                    <Column field="type" header="Added at" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                    <Column field="updated_at" header="Updated at" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                    <Column field="action" header="Action" :headerStyle="{ backgroundColor: '#1588fa', color: 'black' }"></Column>
                </DataTable>
            </section>

            <section ref="baseCostSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Base Cost</h2>
                <DataTable tableStyle="min-width: 50rem">
                    <Column field="no" header="No" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="item_code" header="Item Code" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="size" header="Size" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="type" header="Type" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="updated_at" header="Updated at" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                    <Column field="action" header="Action" :headerStyle="{ backgroundColor: '#f58453', color: 'black' }"></Column>
                </DataTable>
            </section>

            <section ref="cppSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cost per Process</h2>
                <DataTable tableStyle="min-width: 50rem">
                    <Column field="no" header="No" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="item_code" header="Item Code" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="size" header="Size" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="type" header="Type" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="updated_at" header="Updated at" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                    <Column field="action" header="Action" :headerStyle="{ backgroundColor: '#53f586', color: 'black' }"></Column>
                </DataTable>
            </section>

            <section ref="pcSection" class="p-2">
                <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Process Cost</h2>
                <DataTable tableStyle="min-width: 50rem">
                    <Column field="no" header="No" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="item_code" header="Item Code" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="size" header="Size" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="type" header="Type" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="updated_at" header="Updated at" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                    <Column field="action" header="Action" :headerStyle="{ backgroundColor: '#b66eff', color: 'black' }"></Column>
                </DataTable>
            </section>
        </div>
    </AppLayout>
</template>
