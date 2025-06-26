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

const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };
const toast = useToast();
const page = usePage();
const dtCTXSQ = ref();
const dtBASE = ref();
const dtCPP = ref();
const dtPC = ref();

const ctxsq = computed(() =>
    (page.props.ctxsq as any[]).map((ctXsq, index) => ({
        ...ctXsq,
        no: index + 1,
    })),
);

const base = computed(() =>
    (page.props.base as any[]).map((base, index) => ({
        ...base,
        no: index + 1,
    })),
);

const cpp = computed(() =>
    (page.props.cpp as any[]).map((cpp, index) => ({
        ...cpp,
        no: index + 1,
    })),
);

const pc = computed(() =>
    (page.props.processCost as any[]).map((pc, index) => ({
        ...pc,
        no: index + 1,
        max_of_disc: pc.process_cost['Max of Disc'],
        max_of_rim: pc.process_cost['Max of Rim'],
        max_of_sidering: pc.process_cost['Max of Sidering'],
        max_of_assy: pc.process_cost['Max of Assy'],
        max_of_ced: pc.process_cost['Max of CED'],
        max_of_topcoat: pc.process_cost['Max of Topcoat'],
        max_of_packaging: pc.process_cost['Max of Packaging'],
        max_of_total: pc.process_cost['Max of Total'], // ← perbaikan di sini
    })),
);

function exportCSV(type: 'ctxsq' | 'base' | 'cpp' | 'pc') {
    let $type = null;
    let $filename = null;
    if (type === 'ctxsq') {
        $type = dtCTXSQ.value;
        $filename = 'Cycle-Time-x-Sales-Quantity';
    } else if (type === 'base') {
        $type = dtBASE.value;
        $filename = 'Base-Cost';
    } else if (type === 'cpp') {
        $type = dtCPP.value;
        $filename = 'Cost-per-Process';
    } else if (type === 'pc') {
        $type = dtPC.value;
        $filename = 'Process-Cost';
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
            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Cycle Time x Sales Quantity</Tab>
                        <Tab value="1">Base Cost</Tab>
                        <Tab value="2">Cost per Process</Tab>
                        <Tab value="3">Process Cost</Tab>
                    </TabList>
                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="ctXsqSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Cycle Time x Sales Quantity</h2>
                                    <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('ctxsq')" />
                                </div>

                                <DataTable
                                    :value="ctxsq"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtCTXSQ"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ctxsq.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.spinDisc" sortable header="Spin Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="ctxsq.manualDisc"
                                        sortable
                                        header="Manual Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column field="ctxsq.discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.Total Disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ctxsq.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.Total Rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ctxsq.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="ctxsq.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="ctxsq.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="ctxsq.shotPeening"
                                        sortable
                                        header="Shotpeening"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column field="ctxsq.Total Assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ctxsq.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="ctxsq.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="ctxsq.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column
                                        field="ctxsq.packing_dom"
                                        sortable
                                        header="Packing DOM"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="ctxsq.packing_exp"
                                        sortable
                                        header="Packing EXP"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="baseSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Base Cost</h2>
                                    <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('base')" />
                                </div>

                                <DataTable
                                    :value="base"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBASE"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="basecost.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.spinDisc" sortable header="Spin Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="basecost.manualDisc"
                                        sortable
                                        header="Manual Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="basecost.discLathe"
                                        sortable
                                        header="Disc Lathe"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="basecost.Total Disc"
                                        sortable
                                        header="Total Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="basecost.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="basecost.Total Rim"
                                        sortable
                                        header="Total Rim"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="basecost.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="basecost.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="basecost.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="basecost.machining"
                                        sortable
                                        header="Machining"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="basecost.shotPeening"
                                        sortable
                                        header="Shotpeening"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="basecost.Total Assy"
                                        sortable
                                        header="Total Assy"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="basecost.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="basecost.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="basecost.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column
                                        field="basecost.packing_dom"
                                        sortable
                                        header="Packing DOM"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="basecost.packing_exp"
                                        sortable
                                        header="Packing EXP"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                </DataTable>
                                <!-- <pre>{{ base.value[0] }}</pre> -->
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="baseSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Cost per Process</h2>
                                    <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('cpp')" />
                                </div>

                                <DataTable
                                    :value="cpp"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtCPP"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="cpp.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.spinDisc" sortable header="Spin Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.Total Disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="cpp.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.Total Rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="cpp.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="cpp.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="cpp.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.Total Assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="cpp.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="cpp.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />

                                    <Column field="cpp.packing_dom" sortable header="Packing DOM" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="cpp.packing_exp" sortable header="Packing EXP" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                </DataTable>
                                <!-- <pre>{{ base.value[0] }}</pre> -->
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="processCost" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Process Cost</h2>
                                    <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('pc')" />
                                </div>

                                <DataTable
                                    :value="pc"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtPC"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="max_of_disc" sortable header="Max of Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="max_of_rim" sortable header="Max of Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="max_of_sidering"
                                        sortable
                                        header="Max of Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column field="max_of_assy" sortable header="Max of Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="max_of_ced" sortable header="Max of CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="max_of_topcoat"
                                        sortable
                                        header="Max of Topcoat"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column
                                        field="max_of_packaging"
                                        sortable
                                        header="Max of Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    />
                                    <Column field="max_of_total" sortable header="Max of Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
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
