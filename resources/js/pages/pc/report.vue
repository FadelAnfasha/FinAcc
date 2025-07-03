<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
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

const filters = ref({
    bp_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const loading = ref(false);

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
console.log(base);

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
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'item_code']"
                                    ref="dtCTXSQ"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column
                                        field="bp_code"
                                        header="BP Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search BP code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="bp_name" header="BP Name" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.bp_name.length > 20 ? data.bp_name.slice(0, 20) + '…' : data.bp_name }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type" header="Type" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.type.length > 20 ? data.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="ctxsq.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.Total Disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Disc']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.Total Rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Rim']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Sidering']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.Total Assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Assy']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Painting']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.packing_dom"
                                        sortable
                                        header="Packing Domestic"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.packing_exp"
                                        sortable
                                        header="Packing Export"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.ctxsq.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.Total Packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Packaging']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="ctxsq.Total Packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total Packaging']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ctxsq.Total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['ctxsq']['Total']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
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
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'item_code']"
                                    ref="dtBASE"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column
                                        field="bp_code"
                                        header="BP Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search BP code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="bp_name" header="BP Name" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.bp_name.length > 20 ? data.bp_name.slice(0, 20) + '…' : data.bp_name }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type" header="Type" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.type.length > 20 ? data.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="basecost.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.spinDisc"
                                        sortable
                                        header="Spinning Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.manualDisc"
                                        sortable
                                        header="Manual Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.Total Disc"
                                        sortable
                                        header="Total Disc"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Disc']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.Total Rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Rim']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Sidering']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.shotPeening"
                                        sortable
                                        header="Shotpeening"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.Total Assy"
                                        sortable
                                        header="Total Assy"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Assy']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Painting']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.packing_dom"
                                        sortable
                                        header="Packing Domestic"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.packing_exp"
                                        sortable
                                        header="Packing Export"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.basecost.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="basecost.Total Packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total Packaging']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="basecost.Total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['basecost']['Total']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
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
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['bp_code', 'item_code']"
                                    ref="dtCPP"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column
                                        field="bp_code"
                                        header="BP Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search BP code"
                                                class="w-full"
                                            /> </template
                                    ></Column>

                                    <Column field="bp_name" header="BP Name" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.bp_name.length > 20 ? data.bp_name.slice(0, 20) + '…' : data.bp_name }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type" header="Type" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.type.length > 20 ? data.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="quantity" sortable header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="cpp.blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.Total Disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Disc']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.Total Rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Rim']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="cpp.Total Sidering"
                                        sortable
                                        header="Total Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Sidering']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.Total Assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Assy']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="cpp.Total Painting"
                                        sortable
                                        header="Total Painting"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Painting']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="cpp.packing_dom"
                                        sortable
                                        header="Packing Domestic"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="cpp.packing_exp"
                                        sortable
                                        header="Packing Export"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.cpp.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="cpp.Total Packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total Packaging']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="cpp.Total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data['cpp']['Total']).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
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
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    ref="dtPC"
                                >
                                    <Column field="no" sortable header="No" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column
                                        field="item_code"
                                        header="Item Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search Item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="max_of_disc" sortable header="Max of Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_disc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_rim" sortable header="Max of Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_rim).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="max_of_sidering"
                                        sortable
                                        header="Max of Sidering"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_sidering).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_assy" sortable header="Max of Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_assy).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_ced" sortable header="Max of Ced" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_topcoat" sortable header="Max of Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="max_of_packaging"
                                        sortable
                                        header="Max of Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_packaging).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_total" sortable header="Max of Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
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
