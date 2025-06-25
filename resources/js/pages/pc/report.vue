<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
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

const ctxsq = computed(() =>
    (page.props.ctxsq as any[]).map((ctXsq, index) => ({
        ...ctXsq,
        no: index + 1,
    })),
);
const processes = computed(() => page.props.processes as any[]);
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
                <div class="mx-26 mb-26">
                    <Tabs value="0">
                        <TabList>
                            <Tab value="0">Cycle Time x Sales Quantity</Tab>
                            <!-- <Tab value="1">Cycle Time</Tab>
                        <Tab value="2">Sales Quantity</Tab>
                        <Tab value="3">Wages Distribution</Tab> -->
                        </TabList>
                        <!-- Process Items Grid -->
                        <TabPanels>
                            <TabPanel value="0">
                                <section ref="ctXsqSection" class="p-2">
                                    <h2 class="mb-4 text-3xl font-semibold hover:text-indigo-500">Cycle Time x Sales Quantity</h2>

                                    <DataTable
                                        :value="ctxsq"
                                        tableStyle="min-width: 50rem"
                                        paginator
                                        :rows="10"
                                        removableSort
                                        class="text-md"
                                        filterDisplay="header"
                                        ref="dtBP"
                                    >
                                        <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                        <Column field="bp_code" sortable header="BP Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column field="bp_name" sortable header="BP Name" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                        <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                        <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                        <Column field="ctxsq.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column
                                            field="ctxsq.spinDisc"
                                            sortable
                                            header="Spin Disc"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.autoDisc"
                                            sortable
                                            header="Auto Disc"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.manualDisc"
                                            sortable
                                            header="Manual Disc"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.discLathe"
                                            sortable
                                            header="Disc Lathe"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.Total Disc"
                                            sortable
                                            header="Total Disc"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />

                                        <Column field="ctxsq.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column field="ctxsq.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column field="ctxsq.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                        <Column
                                            field="ctxsq.Total Rim"
                                            sortable
                                            header="Total Rim"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />

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
                                        <Column
                                            field="ctxsq.machining"
                                            sortable
                                            header="Machining"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.shotPeening"
                                            sortable
                                            header="Shotpeening"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />
                                        <Column
                                            field="ctxsq.Total Assy"
                                            sortable
                                            header="Total Assy"
                                            :headerStyle="headerStyle"
                                            :bodyStyle="bodyStyle"
                                        />

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
                        </TabPanels>
                        <
                    </Tabs>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
