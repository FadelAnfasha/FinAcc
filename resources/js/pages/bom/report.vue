<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, ref } from 'vue';

const toast = useToast();
const page = usePage();
const dtSC = ref();
const loading = ref(false);

const sc = computed(() =>
    (page.props.sc as any[]).map((sc, index) => {
        const typeChar: string = sc.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? sc.item_code;

        return {
            ...sc,
            no: index + 1,
            type_name,
        };
    }),
);

const ac = computed(() =>
    (page.props.ac as any[]).map((ac, index) => {
        const typeChar: string = ac.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? ac.item_code;

        return {
            ...ac,
            no: index + 1,
            type_name,
        };
    }),
);

const filters = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

function tbStyle(section: 'main' | 'rm' | 'pr' | 'wip' | 'fg') {
    const styles = {
        main: { header: '#758596', body: '#c8cccc' },
        rm: { header: '#2c7a7b', body: '#e6fffa' },
        pr: { header: '#6b46c1', body: '#faf5ff' },
        wip: { header: '#d69e2e', body: '#fffaf0' },
        fg: { header: '#2b6cb0', body: '#ebf8ff' },
    };
    const color = styles[section] || styles.main;
    return {
        headerStyle: { backgroundColor: color.header, color: 'white' },
        bodyStyle: { backgroundColor: color.body, color: 'black' },
    };
}

// const bom = ref<any[]>([]);

const type = ['All', 'Disc', 'Sidering', 'Wheel'];

function getTypeClass(priority: string): string | undefined {
    switch (priority) {
        case 'All':
            return 'secondary';
        case 'Disc':
            return 'bg-purple-400 text-purple-800';
        case 'Sidering':
            return 'bg-blue-300 text-blue-800';
        case 'Wheel':
            return 'bg-orange-400 text-orange-800';
        default:
            return undefined;
    }
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// onMounted(() => {
//     bom.value = (page.props.bom as any[]).map((bom, index) => {
//         const getQty = (item: any, field: string) => Number(item?.[field] ?? 0);
//         const ceil2 = (val: number) => Math.ceil(val * 100) / 100;
//         const pc = bom.main?.process_cost ?? {};

//         const disc_qty = getQty(bom.disc, 'quantity');
//         const disc_price = getQty(bom, 'disc_price');
//         const discXqty = ceil2(disc_qty * disc_price);

//         const rim_qty = getQty(bom.rim, 'quantity');
//         const rim_price = getQty(bom, 'rim_price');
//         const rimXqty = ceil2(rim_qty * rim_price);

//         const sr_qty = getQty(bom.sidering, 'quantity');
//         const sr_price = getQty(bom, 'sr_price');
//         const srXqty = ceil2(sr_qty * sr_price);

//         const pr_disc = Number(pc.max_of_disc ?? 0);
//         const pr_rim = Number(pc.max_of_rim ?? 0);
//         const pr_sr = Number(pc.max_of_sidering ?? 0);
//         const pr_assy = Number(pc.max_of_assy ?? 0);
//         const pr_ced = Number(pc.max_of_ced ?? 0);
//         const pr_tc = Number(pc.max_of_topcoat ?? 0);
//         const pr_packaging = Number(pc.max_of_packaging ?? 0);

//         const pr_cedW = ceil2((pr_ced * 5) / 7);
//         const pr_cedSR = ceil2((pr_ced * 2) / 7);
//         const pr_tcW = ceil2((pr_tc * 5) / 7);
//         const pr_tcSR = ceil2((pr_tc * 2) / 7);

//         const wip_disc_cost = ceil2(discXqty + pr_disc);
//         const wip_rim_cost = ceil2(rimXqty + pr_rim);
//         const wip_sr_cost = ceil2(srXqty + pr_sr);
//         const wip_assy_cost = ceil2(wip_disc_cost + wip_rim_cost + pr_assy);
//         const wip_cedW_cost = ceil2(wip_assy_cost + pr_cedW);
//         const wip_cedSR_cost = ceil2(wip_sr_cost + pr_cedSR);
//         const wip_tcW_cost = ceil2(wip_cedW_cost + pr_tcW);
//         const wip_tcSR_cost = ceil2(wip_cedSR_cost + pr_tcSR);

//         const valveCode = bom.wip_valve?.item_code?.trim();
//         let wip_valve_cost = 0;
//         if (valveCode === 'CGP089') wip_valve_cost = 25815;
//         else if (valveCode === 'CGP064') wip_valve_cost = 14985;
//         else if (valveCode === 'CGP064/CGP118') wip_valve_cost = 5099850;

//         const total_rm = ceil2(discXqty + rimXqty + srXqty);
//         const total_pr = ceil2(pr_disc + pr_rim + pr_sr + pr_assy + pr_cedW + pr_cedSR + pr_tcW + pr_tcSR + pr_packaging + wip_valve_cost);
//         const total = ceil2(total_rm + total_pr);

//         return {
//             ...bom,
//             no: index + 1,
//             discXqty,
//             rimXqty,
//             srXqty,
//             max_of_cedW: pr_cedW,
//             max_of_cedSR: pr_cedSR,
//             max_of_tcW: pr_tcW,
//             max_of_tcSR: pr_tcSR,
//             wip_disc_cost,
//             wip_rim_cost,
//             wip_sr_cost,
//             wip_assy_cost,
//             wip_cedW_cost,
//             wip_cedSR_cost,
//             wip_tcW_cost,
//             wip_tcSR_cost,
//             wip_valve_cost,
//             total_rm,
//             total_pr,
//             total,
//         };
//     });
// });

function exportCSV(type: 'standardCost' | 'actualCost') {
    if (type !== 'standardCost' || !dtSC.value) return;
    const exportFilename = `Bill-of-Material-${new Date().toISOString().slice(0, 10)}.csv`;
    dtSC.value.exportCSV({ selectionOnly: false, filename: exportFilename });
}

function updateReport(type: 'standardCost' | 'actualCost') {
    if (type == 'standardCost') {
        router.post(
            route('pc.updateSC'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Standard Cost updated successfully`,
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
    } else if (type == 'actualCost') {
        router.post(
            route('pc.updateAC'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `Actual Cost updated successfully`,
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

const lastUpdate = computed(() => {
    const SC_update = ((page.props.sc as any[]) ?? []).map((SC) => new Date(SC.updated_at));
    const Max_SCUpdate = SC_update.length ? new Date(Math.max(...SC_update.map((d) => d.getTime()))) : null;

    const AC_update = ((page.props.ac as any[]) ?? []).map((AC) => new Date(AC.updated_at));
    const Max_ACUpdate = AC_update.length ? new Date(Math.max(...AC_update.map((d) => d.getTime()))) : null;

    return [Max_SCUpdate, Max_ACUpdate];
});

const lastMaster = computed(() => page.props.lastMaster as any);

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const updateReportDialog = ref(false);
const updateConstDialog = ref(false);
type UpdateStatus = 'idle' | 'updating' | 'done';
const updateStatus = ref<UpdateStatus>('idle');
const userName = computed(() => page.props.auth?.user?.name ?? '');
const updateType = ref<'standardCost' | 'actualCost' | 'opgin' | null>(null);

function showUpdateDialog(type: 'standardCost' | 'actualCost' | 'opgin') {
    updateType.value = type;
    updateStatus.value = 'idle';

    if (updateType.value === 'opgin') {
        nextTick(() => {
            tempOpex.value = opexDef.value;
            tempProgin.value = proginDef.value;
            updateConstDialog.value = true;
        });
    } else {
        nextTick(() => {
            updateReportDialog.value = true;
        });
    }
}

const saveOpexProgin = () => {
    if (tempOpex.value !== null && tempProgin.value !== null) {
        opexDef.value = tempOpex.value;
        proginDef.value = tempProgin.value;
    }
    updateConstDialog.value = false;
};

const cancelOpexProgin = () => {
    updateConstDialog.value = false; // Tutup dialog
};

function confirmUpdate() {
    if (!updateType.value) return;

    updateStatus.value = 'updating';
    const type = updateType.value;

    const routes = {
        standardCost: 'bom.updateSC',
        actualCost: 'bom.updateAC',
        opgin: 'bom.updateOpGin',
    };

    const messages = {
        standardCost: 'Standard Cost',
        actualCost: 'Actual Cost',
        opgin: 'OPEX / Profit Margin',
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

function closeDialog() {
    updateReportDialog.value = false;
    updateStatus.value = 'idle';
    updateType.value = null;
}

function openPreviewTab(item_code: string, opex: number, progin: number, previewType: string) {
    let previewUrl;

    // Memeriksa nilai `previewType` untuk menentukan route yang benar
    if (previewType === 'actualCost') {
        previewUrl = route('preview.ac', {
            item_code: item_code,
            opex: opex,
            progin: progin,
        });
    } else {
        // Default ke 'standardCost' jika tidak ada atau tidak cocok
        previewUrl = route('preview.sc', {
            item_code: item_code,
            opex: opex,
            progin: progin,
        });
    }

    // Buka URL di tab baru
    window.open(previewUrl, '_blank');
}

let opexDef = ref(6);
let proginDef = ref(5);
const tempOpex = ref<number | null>(null);
const tempProgin = ref<number | null>(null);
</script>

<template>
    <Head title="Bill of Material" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculating Bill of Material</p>
            </div>

            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Report Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <Dialog v-model:visible="updateReportDialog" header="Update Confirmation" modal class="w-[30rem]" :closable="false" @hide="closeDialog">
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
                            <table v-if="updateType === 'standardCost'" class="w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Data</th>
                                        <th class="border-b border-gray-700 px-4 py-2 font-semibold text-gray-400">Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Material Price</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[0] ? formatlastUpdate(lastMaster[0]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Valve Price</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[1] ? formatlastUpdate(lastMaster[1]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Bill of Material</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[2] ? formatlastUpdate(lastMaster[2]) : '-' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="border-b border-gray-800 px-4 py-2">Process Cost</td>
                                        <td class="border-b border-gray-800 px-4 py-2">
                                            <span class="text-red-300">{{ lastMaster[3] ? formatlastUpdate(lastMaster[3]) : '-' }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Cancel"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                @click="closeDialog"
                            />
                            <Button
                                label=" Yes, Update"
                                icon="pi pi-check"
                                unstyled
                                class="w-36 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                                :loading="updateStatus.value === 'updating'"
                                @click="confirmUpdate"
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

                        <div class="flex justify-end pt-4">
                            <Button label="Close" icon="pi pi-times" @click="closeDialog" />
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

            <Dialog v-model:visible="updateConstDialog" header="Edit OPEX & Profit Margin" modal class="w-[25rem]" :closable="false">
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label for="tempOpex">OPEX (%):</label>
                        <InputNumber v-model="tempOpex" inputId="tempOpex" suffix="%" fluid />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="tempProgin">Profit Margin (%):</label>
                        <InputNumber v-model="tempProgin" inputId="tempProgin" suffix="%" fluid />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6">
                    <Button
                        label=" Cancel"
                        icon="pi pi-times"
                        unstyled
                        class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                        @click="cancelOpexProgin"
                    />
                    <Button
                        label=" Save"
                        icon="pi pi-check"
                        unstyled
                        class="w-28 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                        @click="saveOpexProgin"
                    />
                </div>
            </Dialog>

            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Standard Cost 2025</Tab>
                        <Tab value="1">Actual Cost</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 hover:text-indigo-500 md:mb-0 dark:text-white">
                                        Standard Cost
                                    </h2>

                                    <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-start md:justify-end">
                                        <div class="flex flex-col items-center gap-4 sm:flex-row md:w-auto">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-28"
                                                @click="exportCSV('standardCost')"
                                            />
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-36"
                                                @click="showUpdateDialog('standardCost')"
                                            />
                                        </div>

                                        <div class="flex flex-col items-center gap-4 sm:flex-row md:w-auto">
                                            <div class="flex items-center gap-2">
                                                <label for="opex" class="whitespace-nowrap">OPEX :</label>
                                                <InputNumber v-model="opexDef" inputId="percent" suffix="%" fluid disabled />
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label for="progin" class="whitespace-nowrap">Profit Margin :</label>
                                                <InputNumber v-model="proginDef" inputId="percent" suffix="%" fluid disabled />
                                            </div>
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Update Value?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-emerald-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700 sm:w-36"
                                                @click="showUpdateDialog('opgin')"
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
                                    :value="sc"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description']"
                                    class="text-md"
                                    ref="dtBOM"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>

                                    <Column field="item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type_name" :showFilterMenu="false" sortable header="Type" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="type"
                                                    placeholder="Select priority"
                                                    class="w-40"
                                                    @change="
                                                        () => {
                                                            if (filterModel.value === 'All') {
                                                                filterModel.value = null;
                                                            }
                                                            filterCallback();
                                                        }
                                                    "
                                                >
                                                    <!-- Selected value -->
                                                    <template #value="{ value }">
                                                        <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500">
                                                            Select priority
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(value)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(value) }}
                                                        </span>
                                                    </template>

                                                    <!-- Dropdown options -->
                                                    <template #option="{ option }">
                                                        <span
                                                            v-if="option === 'All'"
                                                            class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                                        >
                                                            All
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(option)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(option) }}
                                                        </span>
                                                    </template>
                                                </Select>
                                            </div>
                                        </template></Column
                                    >

                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="disc_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="rim_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>

                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pack_price" sortable header="Packing Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pack_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_valve_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_raw_material" sortable header="Total Raw Material" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_process" sortable header="Total Process" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="data">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-tooltip="'Preview Product'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="openPreviewTab(data.data.item_code, opexDef, proginDef, 'standardCost')"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 hover:text-indigo-500 md:mb-0 dark:text-white">
                                        Actual Cost
                                    </h2>

                                    <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-start md:justify-end">
                                        <div class="flex w-full flex-col items-center gap-4 sm:flex-row md:w-auto">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-28"
                                                @click="exportCSV('actualCost')"
                                            />
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-cyan-700 sm:w-36"
                                                @click="showUpdateDialog('actualCost')"
                                            />
                                        </div>

                                        <div class="flex w-full flex-col items-center gap-4 sm:flex-row md:w-auto">
                                            <div class="flex items-center gap-2">
                                                <label for="opex" class="whitespace-nowrap">OPEX :</label>
                                                <InputNumber v-model="opexDef" inputId="percent" suffix="%" fluid class="w-28" disabled />
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label for="progin" class="whitespace-nowrap">Profit Margin :</label>
                                                <InputNumber v-model="proginDef" inputId="percent" suffix="%" fluid class="w-28" disabled />
                                            </div>
                                            <Button
                                                icon="pi pi-sync"
                                                label=" Update Value?"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-emerald-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700 sm:w-36"
                                                @click="showUpdateDialog('opgin')"
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
                                    :value="ac"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code', 'type_name', 'description']"
                                    class="text-md"
                                    ref="dtBOM"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>

                                    <Column field="item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            />
                                        </template>
                                    </Column>

                                    <Column field="type_name" :showFilterMenu="false" sortable header="Type" v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <div class="flex justify-center">
                                                <Select
                                                    v-model="filterModel.value"
                                                    :options="type"
                                                    placeholder="Select priority"
                                                    class="w-40"
                                                    @change="
                                                        () => {
                                                            if (filterModel.value === 'All') {
                                                                filterModel.value = null;
                                                            }
                                                            filterCallback();
                                                        }
                                                    "
                                                >
                                                    <!-- Selected value -->
                                                    <template #value="{ value }">
                                                        <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500">
                                                            Select priority
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(value)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(value) }}
                                                        </span>
                                                    </template>

                                                    <!-- Dropdown options -->
                                                    <template #option="{ option }">
                                                        <span
                                                            v-if="option === 'All'"
                                                            class="inline-block w-full rounded-full bg-gray-100 px-2 py-1 text-center text-xs font-semibold text-gray-800"
                                                        >
                                                            All
                                                        </span>
                                                        <span
                                                            v-else
                                                            :class="getTypeClass(option)"
                                                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                                        >
                                                            {{ capitalize(option) }}
                                                        </span>
                                                    </template>
                                                </Select>
                                            </div>
                                        </template></Column
                                    >

                                    <Column field="bom.description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="disc_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="rim_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="sidering_price" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_rim_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_sidering_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_assy_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_cedW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_cedSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_tcW_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="pr_tcSR_price" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pr_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pack_price" sortable header="Packing Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.pack_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_disc_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_disc_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_rim_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_rim_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_sidering_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_sidering_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_assy_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_assy_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_cedW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_cedSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_tcW_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcW_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_tcSR_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcSR_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>
                                    <Column field="wip_valve_price" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_valve_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_raw_material" sortable header="Total Raw Material" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_raw_material).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_process" sortable header="Total Process" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_process).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total" sortable header="Total" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" v-bind="tbStyle('fg')">
                                        <template #body="data">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-tooltip="'Preview Product'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="openPreviewTab(data.data.item_code, opexDef, proginDef, 'actualCost')"
                                                />
                                            </div>
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
