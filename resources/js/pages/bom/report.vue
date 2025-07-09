<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

const toast = useToast();
const page = usePage();
const dtBOM = ref();
const loading = ref(false);

const bom = computed(() =>
    (page.props.bom as any[]).map((bom, index) => ({
        ...bom,
        no: index + 1,
    })),
);

console.log(bom.value);

const filters = ref({
    'main.item_code': { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
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

const type = ['All', 'Disc', 'Side Ring', 'Wheel'];

function getTypeClass(priority: string): string | undefined {
    switch (priority) {
        case 'All':
            return 'secondary';
        case 'Disc':
            return 'bg-purple-400 text-purple-800';
        case 'Side Ring':
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

function exportCSV(type: 'bom') {
    if (type !== 'bom' || !dtBOM.value) return;
    const exportFilename = `Bill-of-Material-${new Date().toISOString().slice(0, 10)}.csv`;
    dtBOM.value.exportCSV({ selectionOnly: false, filename: exportFilename });
}

function updateReport(type: 'bom') {
    if (type == 'bom') {
        router.post(
            route('pc.updateBOM'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        group: 'br',
                        detail: `BOM Report updated successfully`,
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
    const BOM_update = ((page.props.bom as any[]) ?? []).map((BOM) => new Date(BOM.updated_at));
    const Max_BOMUpdate = BOM_update.length ? new Date(Math.max(...BOM_update.map((d) => d.getTime()))) : null;

    return [Max_BOMUpdate];
});
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

            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Generate/Explode BOM</Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Bill of Material</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div class="flex flex-col items-center gap-3">
                                                Last Update :
                                                <!-- <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span> -->
                                            </div>
                                        </div>

                                        <div class="flex flex-col items-center gap-3">
                                            <Button
                                                icon="pi pi-sync
"
                                                label=" Update Report?"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-cyan-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="updateReport('bom')"
                                            />
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-28 cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900"
                                                @click="exportCSV('bom')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="bom"
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
                                    :globalFilterFields="['main.item_code', 'type']"
                                    class="text-md"
                                    ref="dtBOM"
                                >
                                    <Column field="no" sortable header="#" :showFilterMenu="true" v-bind="tbStyle('main')"></Column>
                                    <Column field="main.item_code" header="Item Code" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
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

                                    <Column field="main.description" sortable header="Name" v-bind="tbStyle('main')"></Column>

                                    <Column field="disc.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc.item_code" sortable header="Disc" v-bind="tbStyle('rm')"></Column>
                                    <Column field="discXqty" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.discXqty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="rim.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim.item_code" sortable header="Rim" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rimXqty" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.rimXqty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sidering.quantity" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering.item_code" sortable header="Sidering" v-bind="tbStyle('rm')"></Column>
                                    <Column field="srXqty" sortable header="Price" v-bind="tbStyle('rm')">
                                        <template #body="{ data }">
                                            {{ Number(data.srXqty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc.item_code" sortable header="Pr Disc" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_disc" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_disc).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim.item_code" sortable header="Pr Rim" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_rim" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_rim).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sr.item_code" sortable header="Pr Sidering" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_sr" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_sr).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy.item_code" sortable header="Pr Assy" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_assy" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_assy).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ced_w.item_code" sortable header="Pr CED_W" v-bind="tbStyle('pr')" />
                                    <Column field="max_of_cedW" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_cedW).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="ced_sr.item_code" sortable header="Pr CED_SR" v-bind="tbStyle('pr')" />
                                    <Column field="max_of_cedSR" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_cedSR).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="tc_w.item_code" sortable header="Pr TC_W" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_tcW" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_tcW).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="tc_sr.item_code" sortable header="Pr TC_SR" v-bind="tbStyle('pr')"></Column>
                                    <Column field="max_of_tcSR" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_tcSR).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="max_of_packaging" sortable header="Price" v-bind="tbStyle('pr')">
                                        <template #body="{ data }">
                                            {{ Number(data.max_of_packaging).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc.item_code" sortable header="WiP Disc" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_disc_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_disc_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim.item_code" sortable header="WiP Rim" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_rim_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_rim_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sr.item_code" sortable header="WiP Sidering" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_sr_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_sr_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy.item_code" sortable header="WiP Assy" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_assy_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_assy_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW.item_code" sortable header="WiP CED W" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_cedW_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedW_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR.item_code" sortable header="WiP CED SR" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_cedSR_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_cedSR_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW.item_code" sortable header="WiP TC W" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_tcW_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcW_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR.item_code" sortable header="WiP TC SR" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_tcSR_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_tcSR_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve.item_code" sortable header="Valve" v-bind="tbStyle('wip')"></Column>
                                    <Column field="wip_valve_cost" sortable header="Cost" v-bind="tbStyle('wip')">
                                        <template #body="{ data }">
                                            {{ Number(data.wip_valve_cost).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="total_rm" sortable header="Total Raw Material" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_rm).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="total_pr" sortable header="Total Process" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total_pr).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="total" sortable header="Total" v-bind="tbStyle('fg')">
                                        <template #body="{ data }">
                                            {{ Number(data.total).toLocaleString('id-ID') }}
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
