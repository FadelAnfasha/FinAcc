<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
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
        max_of_disc: pc.max_of_disc,
        max_of_rim: pc.max_of_rim,
        max_of_sidering: pc.max_of_sidering,
        max_of_assy: pc.max_of_assy,
        max_of_ced: pc.max_of_ced,
        max_of_topcoat: pc.max_of_topcoat,
        max_of_packaging: pc.max_of_packaging,
        max_of_total: pc.max_of_total, // ← perbaikan di sini
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

const lastUpdate = computed(() => {
    // Business Partners
    const CTxSQ_update = ((page.props.ctxsq as any[]) ?? []).map((CTxSQ) => new Date(CTxSQ.updated_at));
    const Max_CTxSQUpdate = CTxSQ_update.length ? new Date(Math.max(...CTxSQ_update.map((d) => d.getTime()))) : null;

    // Cycle Times
    const BaseCost_update = ((page.props.base as any[]) ?? []).map((BaseCost) => new Date(BaseCost.updated_at));
    const Max_BaseCostUpdate = BaseCost_update.length ? new Date(Math.max(...BaseCost_update.map((d) => d.getTime()))) : null;

    // Sales Quantities
    const CPP_update = ((page.props.cpp as any[]) ?? []).map((CPP) => new Date(CPP.updated_at));
    const Max_CPPUpdate = CPP_update.length ? new Date(Math.max(...CPP_update.map((d) => d.getTime()))) : null;

    // Sales Quantities
    const PC_update = ((page.props.processCost as any[]) ?? []).map((PC) => new Date(PC.updated_at));
    const Max_PCUpdate = PC_update.length ? new Date(Math.max(...PC_update.map((d) => d.getTime()))) : null;

    return [Max_CTxSQUpdate, Max_BaseCostUpdate, Max_CPPUpdate, Max_PCUpdate];
});

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

function updateReport(type: 'ctxsq' | 'base' | 'cpp' | 'pc') {
    if (type == 'ctxsq') {
        router.post(
            route('pc.updateCTxSQ'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `CT x SQ Report updated successfully`,
                        life: 3000,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error',
                        group: 'br',
                        // detail: `Failed to delete data with ${editedData.value.bp_code} and ${editedData.value.item_code}`,
                        life: 3000,
                    });
                },
            },
        );
    } else if (type == 'base') {
        router.post(
            route('pc.updateBaseCost'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Base Cost Report updated successfully`,
                        life: 3000,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error',
                        group: 'br',
                        // detail: `Failed to delete data with ${editedData.value.bp_code} and ${editedData.value.item_code}`,
                        life: 3000,
                    });
                },
            },
        );
    } else if (type == 'cpp') {
        router.post(
            route('pc.updateCPP'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Cost per Process Report updated successfully`,
                        life: 3000,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error',
                        group: 'br',
                        // detail: `Failed to delete data with ${editedData.value.bp_code} and ${editedData.value.item_code}`,
                        life: 3000,
                    });
                },
            },
        );
    } else if (type == 'pc') {
        router.post(
            route('pc.updatePC'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Cost per Process Report updated successfully`,
                        life: 3000,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'warn',
                        summary: 'Error',
                        group: 'br',
                        // detail: `Failed to delete data with ${editedData.value.bp_code} and ${editedData.value.item_code}`,
                        life: 3000,
                    });
                },
            },
        );
    }
}
</script>

<template>
    <Head title="Process Cost" />

    <AppLayout>
        <Toast></Toast>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Process Cost Calculation</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Unit cost product calculation.</p>
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
                                    <div class="flex gap-4">
                                        <div>
                                            <div class="flex flex-col items-center gap-3">
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-3">
                                            <Button
                                                icon="pi pi-sync
"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="updateReport('ctxsq')"
                                            />
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="exportCSV('ctxsq')"
                                            />
                                        </div>
                                    </div>
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
                                            {{ data.bp.bp_name.length > 20 ? data.bp.bp_name.slice(0, 20) + '…' : data.bp_name }}
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
                                            {{ data.item.type.length > 20 ? data.item.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="total_packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.total_packaging).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="baseSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Base Cost</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div class="flex flex-col items-center gap-3">
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-3">
                                            <Button
                                                icon="pi pi-sync
"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="updateReport('base')"
                                            />
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="exportCSV('base')"
                                            />
                                        </div>
                                    </div>
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
                                            {{ data.bp.bp_name.length > 20 ? data.bp.bp_name.slice(0, 20) + '…' : data.bp_name }}
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
                                            {{ data.item.type.length > 20 ? data.item.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="total_packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.total_packaging).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="cppSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Cost per Process</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div class="flex flex-col items-center gap-3">
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-3">
                                            <Button
                                                icon="pi pi-sync
"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="updateReport('cpp')"
                                            />
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="exportCSV('cpp')"
                                            />
                                        </div>
                                    </div>
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
                                            {{ data.bp.bp_name.length > 20 ? data.bp.bp_name.slice(0, 20) + '…' : data.bp_name }}
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
                                            {{ data.item.type.length > 20 ? data.item.type.slice(0, 20) + '…' : data.type }}
                                        </template>
                                    </Column>

                                    <Column field="blanking" sortable header="Blanking" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.blanking).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="total_packaging"
                                        sortable
                                        header="Total Packaging"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="{ data }">
                                            {{ Number(data.total_packaging).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="processCost" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Process Cost</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div class="flex flex-col items-center gap-3">
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-3">
                                            <Button
                                                icon="pi pi-sync
"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="updateReport('pc')"
                                            />
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="exportCSV('pc')"
                                            />
                                        </div>
                                    </div>
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
