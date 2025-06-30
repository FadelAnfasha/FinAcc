<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

function tbStyle(section: 'main' | 'rm' | 'pr' | 'wip' | 'fg') {
    let headerColor = '#758596';
    let bodyColor = '#c8cccc';

    switch (section) {
        case 'rm': // Raw Material
            headerColor = '#2c7a7b'; // teal
            bodyColor = '#e6fffa'; // light teal
            break;
        case 'pr': // Production Ready
            headerColor = '#6b46c1'; // purple
            bodyColor = '#faf5ff'; // light purple
            break;
        case 'wip': // Work in Progress
            headerColor = '#d69e2e'; // amber
            bodyColor = '#fffaf0'; // light amber
            break;
        case 'fg': // Finished Good
            headerColor = '#2b6cb0'; // blue
            bodyColor = '#ebf8ff'; // light blue
            break;
        case 'main':
        default:
            headerColor = '#758596';
            bodyColor = '#c8cccc';
            break;
    }

    return {
        headerStyle: { backgroundColor: headerColor, color: 'white' },
        bodyStyle: { backgroundColor: bodyColor, color: 'black' },
    };
}

const toast = useToast();
const page = usePage();
const dtBOM = ref();

const bom = computed(() =>
    (page.props.bom as any[]).map((bom, index) => ({
        ...bom,
        no: index + 1,
    })),
);

function exportCSV(type: 'bom') {
    let $type = null;
    let $filename = null;
    if (type === 'bom') {
        $type = dtBOM.value;
        $filename = 'Bill-of-Material';
    }
    if (!$type) return;

    const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

    $type.exportCSV({
        selectionOnly: false,
        filename: exportFilename, // ← akan digunakan jika PrimeVue 4.x mendukungnya
    });
}
</script>

<template>
    <Head title="Process Cost" />

    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculating Bill of Material</p>
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
            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Generate/Explode BOM</Tab>
                    </TabList>
                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="bom" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Bill of Material</h2>
                                    <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('bom')" />
                                </div>

                                <DataTable
                                    :value="bom"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBOM"
                                >
                                    <Column field="no" sortable header="#" v-bind="tbStyle('main')"></Column>
                                    <Column field="main.item_code" sortable header="Item Code" v-bind="tbStyle('main')"></Column>
                                    <Column field="main.description" sortable header="Name" v-bind="tbStyle('main')"></Column>
                                    <Column field="type_name" sortable header="Type" v-bind="tbStyle('main')"></Column>

                                    <Column field="disc.item_code" sortable header="Disc" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim.item_code" sortable header="Rim" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering.item_code" sortable header="Sidering" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>

                                    <Column field="pr_disc.item_code" sortable header="Pr Disc" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_disc.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_rim.item_code" sortable header="Pr Rim" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_rim.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_sr.item_code" sortable header="Pr Sidering" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_sr.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_assy.item_code" sortable header="Pr Assy" v-bind="tbStyle('pr')"></Column>
                                    <Column field="pr_assy.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="ced_w.item_code" sortable header="Pr CED_W" v-bind="tbStyle('pr')"></Column>
                                    <Column field="ced_w.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="ced_sr.item_code" sortable header="Pr CED_SR" v-bind="tbStyle('pr')"></Column>
                                    <Column field="ced_sr.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="tc_w.item_code" sortable header="Pr TC_W" v-bind="tbStyle('pr')"></Column>
                                    <Column field="tc_w.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="tc_sr.item_code" sortable header="Pr TC_SR" v-bind="tbStyle('pr')"></Column>
                                    <Column field="tc_sr.price" sortable header="Price" v-bind="tbStyle('pr')"></Column>
                                    <Column field="ced_w.item_code" sortable header="Packing" v-bind="tbStyle('pr')"></Column>
                                </DataTable>
                            </section>
                        </TabPanel>
                    </TabPanels>
                    <
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
