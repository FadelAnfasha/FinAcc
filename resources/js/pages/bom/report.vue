<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
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
import { computed, ref } from 'vue';

const page = usePage();
const dtBOM = ref();
const loading = ref(false);

const activeTabValue = ref('0');
const type = ['Disc', 'Sidering', 'Wheel'];
const lastMaster = computed(() => page.props.lastUpdate as any);

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

const props = defineProps({
    auth: Object,
});

const bom = computed(() =>
    (page.props.bom as any[]).map((bom, index) => {
        const typeChar: string = bom.item_code?.charAt(3) ?? '';
        const typeMap: Record<string, string> = {
            D: 'Disc',
            N: 'Sidering',
            W: 'Wheel',
            R: 'Rim',
        };
        const type_name = typeMap[typeChar] ?? bom.item_code;

        return {
            ...bom,
            no: index + 1,
            type_name,
        };
    }),
);

const filtersBOM = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type_name: { value: null, matchMode: FilterMatchMode.EQUALS },
    description: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

function exportCSV(type: 'BOM') {
    if (type === 'BOM' && dtBOM.value) {
        const exportFilename = `Bill-of-Material-${new Date().toISOString().slice(0, 10)}.csv`;
        dtBOM.value.exportCSV({ selectionOnly: false, filename: exportFilename });
    }
}

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}
</script>

<template>
    <Head title="Bill of Material" />
    <AppLayout>
        <div class="p-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Arrange Bill of Material from raw data</p>
            </div>

            <div class="mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-1 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Report Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTabValue">
                    <TabList>
                        <Tab value="0">BOM</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section class="p-2">
                                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <!-- Title -->
                                    <h2 class="text-3xl font-semibold text-gray-900 dark:text-white">Bill Of Material</h2>

                                    <!-- Main Controls Container -->
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                        <!-- Export and Update Report Buttons -->
                                        <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export Report"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-orange-700 sm:w-auto"
                                                @click="exportCSV('BOM')"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Update Info -->
                                <div class="mb-4 text-right text-gray-700 dark:text-gray-300">
                                    <div>
                                        Last Update :
                                        <span class="text-red-300">{{ lastMaster ? formatlastUpdate(lastMaster) : '-' }}</span>
                                    </div>
                                </div>

                                <DataTable
                                    :value="bom"
                                    tableStyle="min-width: 10rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[5, 10, 20, 50]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filtersBOM"
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
                                                    :showClear="true"
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

                                    <Column field="description" header="Name" :showFilterMenu="false" sortable v-bind="tbStyle('main')">
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.description ?? 'N/A' }}
                                        </template>
                                    </Column>

                                    <Column field="disc_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="disc_code" sortable header="Disc" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.disc_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="rim_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="rim_code" sortable header="Rim" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.rim_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="sidering_qty" sortable header="Qty" v-bind="tbStyle('rm')"></Column>
                                    <Column field="sidering_code" sortable header="Sidering" v-bind="tbStyle('rm')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.sidering_code || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_disc" sortable header="Pr Disc" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_rim" sortable header="Pr Rim" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_sidering" sortable header="Pr Sidering" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_assy" sortable header="Pr Assy" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedW" sortable header="Pr CED W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_cedSR" sortable header="Pr CED SR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcW" sortable header="Pr Topcoat W" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_tcSR" sortable header="Pr tcSR" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="pr_TA" sortable header="Pr TA" v-bind="tbStyle('pr')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.pr_TA || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_disc" sortable header="WiP Disc" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_disc || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_rim" sortable header="WiP Rim" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_rim || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_sidering" sortable header="WiP Sidering" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_sidering || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_assy" sortable header="WiP Assy" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_assy || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedW" sortable header="WiP CED W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_cedSR" sortable header="WiP CED SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_cedSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcW" sortable header="WiP Topcoat W" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcW || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tcSR" sortable header="WiP Topcoat SR" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tcSR || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_valve" sortable header="WiP Valve" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_valve || '-' }}
                                        </template>
                                    </Column>

                                    <Column field="wip_tyre" sortable header="WiP Tyre" v-bind="tbStyle('wip')">
                                        <template #body="slotProps">
                                            {{ slotProps.data.wip_tyre || '-' }}
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
