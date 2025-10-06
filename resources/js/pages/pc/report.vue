<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, ref } from 'vue';

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

const props = defineProps({
    auth: Object,
});

const loading = ref(false);

const ctxsq = computed(() =>
    (page.props.ctxsq as any[]).map((ctXsq, index) => ({
        ...ctXsq,
        no: index + 1,
    })),
);

interface Total {
    blanking: number;
    spinDisc: number;
    autoDisc: number;
    manualDisc: number;
    discLathe: number;
    total_disc: number;
    rim1: number;
    rim2: number;
    rim3: number;
    total_rim: number;
    coiler: number;
    forming: number;
    total_sidering: number;
    assy1: number;
    assy2: number;
    machining: number;
    shotPeening: number;
    total_assy: number;
    ced: number;
    topcoat: number;
    total_painting: number;
    packing_dom: number;
    packing_exp: number;
    total_packaging: number;
    total: number;
}

const ctxsqTotal = computed(() => page.props.ctxsqTotal as Total);

const base = computed(() =>
    (page.props.base as any[]).map((base, index) => ({
        ...base,
        no: index + 1,
    })),
);

const baseTotal = computed(() => page.props.baseTotal as Total);

const cpp = computed(() =>
    (page.props.cpp as any[]).map((cpp, index) => ({
        ...cpp,
        no: index + 1,
    })),
);
const cppTotal = computed(() => page.props.cppTotal as Total);

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

interface pcTotal {
    max_of_disc: number;
    max_of_rim: number;
    max_of_sidering: number;
    max_of_assy: number;
    max_of_ced: number;
    max_of_topcoat: number;
    max_of_packaging: number;
    max_of_total: number;
}

const pcTotal = computed(() => page.props.pcTotal as pcTotal);

const isUpdating = computed(() => updateStatus.value === 'updating');

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

const lastMaster = computed(() => page.props.lastUpdate as any);

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const updateReportDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const userName = computed(() => page.props.auth?.user?.name ?? '');
const updateType = ref<'ctxsq' | 'base' | 'cpp' | 'pc' | null>(null);

function showUpdateDialog(type: 'ctxsq' | 'base' | 'cpp' | 'pc') {
    updateType.value = type;
    updateStatus.value = 'idle';
    nextTick(() => {
        updateReportDialog.value = true;
    });
}

function confirmUpdate() {
    if (!updateType.value) return;

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        ctxsq: 'pc.updateCTxSQ',
        base: 'pc.updateBaseCost',
        cpp: 'pc.updateCPP',
        pc: 'pc.updatePC',
    };

    const messages = {
        ctxsq: 'CT x SQ Report',
        base: 'Base Cost Report',
        cpp: 'Cost per Process Report',
        pc: 'Cost per Component Report',
    };

    router.post(
        route(routes[type]),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                updateStatus.value = 'done';
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `${messages[type]} updated successfully`,
                    life: 3000,
                });
            },
            onError: () => {
                updateStatus.value = 'idle';
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to update ${messages[type]}`,
                    life: 3000,
                });
            },
        },
    );
}

function closeDialog(type: 'pc' | null) {
    // Sesuaikan tipe data 'type'
    if (type === 'pc') {
        router.visit(route('bom.report')); // Mengarahkan ke rute 'bom.route'
        // Atau router.get(route('bom.route'));
    }
    // Logika untuk menutup dialog tetap dijalankan setelah (atau jika bukan 'pc')
    updateReportDialog.value = false;
    updateStatus.value = 'idle';
    updateType.value = null;
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

            <Dialog
                v-model:visible="updateReportDialog"
                header="Update Confirmation"
                modal
                class="w-[30rem]"
                :closable="false"
                @hide="closeDialog(null)"
            >
                <!-- Idle state -->
                <template v-if="updateStatus === 'idle'">
                    <div class="space-y-4">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>
                        <p>Are you sure you want to update the report?</p>
                        <p class="mt-6 mb-2 font-semibold">Make sure this data is up to date:</p>
                        <div class="overflow-x-auto">
                            <table v-if="updateType === 'ctxsq'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Cycle Time</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[0] ? formatlastUpdate(lastMaster[0]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Sales Quantity</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[1] ? formatlastUpdate(lastMaster[1]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table v-if="updateType === 'base'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Activity Quantity</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Wages Distribution</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[2] ? formatlastUpdate(lastMaster[2]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table v-if="updateType === 'cpp'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Convertion Cost</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table v-if="updateType === 'pc'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Cost per Process</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : 'Empty' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Cancel"
                                icon="pi pi-times"
                                @click="closeDialog(null)"
                                unstyled
                                class="w-48 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                            />
                            <Button
                                label=" Yes, Update"
                                icon="pi pi-check"
                                :loading="isUpdating"
                                @click="confirmUpdate"
                                unstyled
                                class="w-48 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                            />
                        </div>
                    </div>
                </template>

                <!-- Done state -->
                <template v-else-if="updateStatus === 'done'">
                    <div class="space-y-4">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>
                        <p>
                            <strong class="text-green-500">Finished</strong> updating the report.<br />
                            It’s now safe to close this window.
                        </p>

                        <div class="flex justify-end gap-2 pt-4">
                            <Button
                                label=" Close"
                                icon="pi pi-times"
                                @click="closeDialog(null)"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                            />
                            <Button
                                v-if="updateType === 'pc'"
                                label=" Explode BOM"
                                icon="pi pi-map"
                                @click="closeDialog('pc')"
                                unstyled
                                class="w-40 cursor-pointer rounded-xl bg-cyan-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700"
                            />
                        </div>
                    </div>
                </template>

                <!-- Updating state (optional animation) -->
                <template v-else>
                    <div class="flex flex-col items-center space-y-4 text-center">
                        <i class="pi pi-spin pi-spinner text-4xl text-primary" />
                        <p class="font-medium">Updating report...</p>
                    </div>
                </template>
            </Dialog>

            <!-- Process Items Grid -->
            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Activity Quantity</Tab>
                        <Tab value="1">Convertion Cost</Tab>
                        <Tab value="2">Cost per Process</Tab>
                        <Tab value="3">Process Cost</Tab>
                    </TabList>
                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="ctXsqSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Cycle Time x Sales Quantity</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('ctxsq')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label="Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="showUpdateDialog('ctxsq')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
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
                                    showFooter
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
                                            {{ data.bp?.bp_name?.length > 20 ? data.bp.bp_name.slice(0, 20) + '…' : data.bp?.bp_name || '-' }}
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

                                    <Column field="type" sortable header="Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{
                                                data.item?.type
                                                    ? data.item.type.length > 20
                                                        ? data.item.type.slice(0, 20) + '…'
                                                        : data.item.type
                                                    : '-'
                                            }}
                                        </template>
                                    </Column>

                                    <Column field="blanking" header="Blanking" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.blanking).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.blanking).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.spinDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.autoDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.manualDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.discLathe).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_disc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.rim1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.rim2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.rim3).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_rim).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.coiler).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.forming).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_sidering).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.assy1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.assy2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.machining).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.shotPeening).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_assy).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.ced).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.topcoat).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_painting).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.packing_dom).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.packing_exp).toLocaleString('id-ID') : '-' }}</strong>
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
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total_packaging).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ ctxsqTotal ? Number(ctxsqTotal.total).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="baseSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Convertion Cost / Product</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('base')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="showUpdateDialog('base')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
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
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.blanking).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.spinDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.autoDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.manualDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.discLathe).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_disc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.rim1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.rim2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.rim3).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_rim).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.coiler).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.forming).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_sidering).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.assy1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.assy2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.machining).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.shotPeening).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_assy).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.ced).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.topcoat).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_painting).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.packing_dom).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.packing_exp).toLocaleString('id-ID') : '-' }}</strong>
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
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total_packaging).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ baseTotal ? Number(baseTotal.total).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="cppSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Total Convertion Cost / Unit</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('cpp')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="showUpdateDialog('cpp')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : '-' }}</span>
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
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.blanking).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="spinDisc" sortable header="Spinning Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.spinDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.spinDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="autoDisc" sortable header="Auto Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.autoDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.autoDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="manualDisc" sortable header="Manual Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.manualDisc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.manualDisc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="discLathe" sortable header="Disc Lathe" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.discLathe).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.discLathe).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_disc" sortable header="Total Disc" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_disc).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_disc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim1" sortable header="Rim 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.rim1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim2" sortable header="Rim 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.rim2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="rim3" sortable header="Rim 3" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.rim3).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.rim3).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_rim" sortable header="Total Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rim).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_rim).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="coiler" sortable header="Coiler" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.coiler).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.coiler).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="forming" sortable header="Forming" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.forming).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.forming).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_sidering" sortable header="Total Sidering" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_sidering).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_sidering).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy1" sortable header="Assy 1" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy1).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.assy1).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="assy2" sortable header="Assy 2" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.assy2).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.assy2).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="machining" sortable header="Machining" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.machining).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.machining).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="shotPeening" sortable header="Shotpeening" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.shotPeening).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.shotPeening).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_assy" sortable header="Total Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_assy).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_assy).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="ced" sortable header="CED" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.ced).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.ced).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="topcoat" sortable header="Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.topcoat).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.topcoat).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total_painting" sortable header="Total Painting" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total_painting).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_painting).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_dom" sortable header="Packing Domestic" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_dom).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.packing_dom).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="packing_exp" sortable header="Packing Export" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.packing_exp).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.packing_exp).toLocaleString('id-ID') : '-' }}</strong>
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
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total_packaging).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ cppTotal ? Number(cppTotal.total).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="processCost" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Process Cost / Product</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('pc')"
                                            />
                                            <Button
                                                v-if="auth?.user?.permissions?.includes('Update_Report')"
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="showUpdateDialog('pc')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span>
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
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_disc).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="max_of_rim" sortable header="Max of Rim" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_rim).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_rim).toLocaleString('id-ID') : '-' }}</strong>
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
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_sidering).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="max_of_assy" sortable header="Max of Assy" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_assy).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_assy).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="max_of_ced" sortable header="Max of Ced" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_ced).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_ced).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="max_of_topcoat" sortable header="Max of Topcoat" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_topcoat).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_topcoat).toLocaleString('id-ID') : '-' }}</strong>
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
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_packaging).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>

                                    <Column field="max_of_total" sortable header="Max of Total" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_total).toLocaleString('id-ID') }}
                                        </template>
                                        <template #footer>
                                            <strong>{{ pcTotal ? Number(pcTotal.max_of_total).toLocaleString('id-ID') : '-' }}</strong>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>
