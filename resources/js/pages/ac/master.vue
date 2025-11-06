<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import axios from 'axios';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, nextTick, ref, Ref, watch } from 'vue';

const dtACMAT = ref();
const dtACSQTY = ref();
const toast = useToast();
const page = usePage();
const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };
const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'acmat' | null>(null);
const destroyType = ref<'acmat' | null>(null);
const headerType = ref<any>({});
const editedData = ref<any>({});
const destroyedData = ref<any>({});
const showImportDialog: Ref<boolean> = ref(false);
const importName = ref<any>({});
const selectedFile = ref<File | null>(null);
const importType = ref<'acmat' | 'acsqty' | null>(null);
const notImported = ref(true);
const fileUploaderACMAT = ref<any>(null);
const fileUploaderACSQTY = ref<any>(null);
const uploadProgress = ref(0);
const activeTabValue = ref('0');

const isUploading = ref(false);
const loading = ref(false);

const filterACMAT = ref({
    item_code: { value: null, matchMode: 'contains' },
    'bom.description': { value: null, matchMode: 'contains' },
});

const filterACSQTY = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'bom.description': { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const actualMaterial = computed(() =>
    ((page.props.actualMaterial as any[]) ?? []).map((actualmat, index) => ({
        ...actualmat,
        no: index + 1,
        created_at_formatted: formatDate(actualmat.created_at),
        updated_at_formatted: formatDate(actualmat.updated_at),
    })),
);

const actualSalesQty = computed(() =>
    ((page.props.actualSalesQty as any[]) ?? []).map((acsqty, index) => ({
        ...acsqty,
        no: index + 1,
        created_at_formatted: formatDate(acsqty.created_at),
        updated_at_formatted: formatDate(acsqty.updated_at),
    })),
);

const type = computed(() => page.props.type as string | undefined);
const getDefaultActiveTab = (): string | null => {
    // Ambil nilai dari computed property 'type'
    const currentType = type.value;

    switch (currentType) {
        case 'actualMaterial':
            return '2';

        default:
            return '0';
    }
};

const activeTab = ref<string | null>(getDefaultActiveTab());
const userName = computed(() => page.props.auth?.user?.name ?? '');

interface ComponentItem {
    item_code: string;
    description: string;
    // Tambahkan field lain jika diperlukan
}

const lastUpdate = computed(() => {
    const acmat_update = ((page.props.actualMaterial as any[]) ?? []).map((acmat) => new Date(acmat.updated_at));
    const Max_acmatUpdate = acmat_update.length ? new Date(Math.max(...acmat_update.map((d) => d.getTime()))) : null;

    const actualSalesQty_update = ((page.props.actualSalesQty as any[]) ?? []).map((actualSalesQty) => new Date(actualSalesQty.updated_at));
    const Max_actualSalesQtyUpdate = actualSalesQty_update.length ? new Date(Math.max(...actualSalesQty_update.map((d) => d.getTime()))) : null;

    return [Max_acmatUpdate, Max_actualSalesQtyUpdate];
});

const dataSource = [
    'Share Others/Finacc/Bill of Material/Actual Material Price/actualMat_master.csv',
    'Share Others/Finacc/Bill of Material/Bill of Material (BOM)/actualSalesQty_master.csv',
];

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const componentItems = ref((page.props.component as ComponentItem[]) ?? []);
const showComponent = ref(componentItems.value.length > 0);

interface FinishGood {
    item_code?: string;
    [key: string]: any;
}

const finishGood = ref<FinishGood>(page.props.finish_good ?? {});

watch(
    () => page.props.component,
    (newVal) => {
        componentItems.value = (newVal as ComponentItem[]) ?? [];
        showComponent.value = componentItems.value.length > 0;

        finishGood.value = page.props.finish_good ?? {};
    },
    { immediate: true },
);

function closeComponentDialog() {
    showComponent.value = false;
    componentItems.value = [];
    finishGood.value = {}; // ✅ kosongkan dengan objek kosong
}

function handleCSVImport(event: FileUploadUploaderEvent, type: 'acmat' | 'acsqty') {
    let file: File | undefined;

    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }

    if (!file) return;

    const expectedNames = {
        acmat: 'actualMat_master.csv',
        acsqty: 'actualSalesQty_master.csv',
    };

    const expectedFileName = expectedNames[type];

    if (file.name !== expectedFileName) {
        toast.add({
            severity: 'error',
            summary: 'File name missmatch!',
            detail: `⚠️ Expected: ${expectedFileName}, but got: ${file.name}`,
            life: 4000,
            group: 'br',
        });
        selectedFile.value = null;

        nextTick(() => {
            if (type === 'acmat') fileUploaderACMAT.value?.clear();
            if (type === 'acsqty') fileUploaderACSQTY.value?.clear();
        });
        return;
    }

    selectedFile.value = file;
    importType.value = type;
    importName.value = file.name;

    nextTick(() => {
        showImportDialog.value = true;
    });
}

function cancelCSVimport(type: 'acmat' | 'acsqty') {
    showImportDialog.value = false;
    selectedFile.value = null;

    nextTick(() => {
        if (type === 'acmat') fileUploaderACMAT.value?.clear();
        if (type === 'acsqty') fileUploaderACSQTY.value?.clear();
    });
}

function confirmUpload(type: 'acmat' | 'acsqty') {
    if (!selectedFile.value || !importType.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    const routes = {
        acmat: 'acmat.import',
        acsqty: 'acsqty.import',
    };

    isUploading.value = true;
    uploadProgress.value = 0;
    startPollingProgress(type);

    router.post(route(routes[importType.value]), formData, {
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            isUploading.value = false;
            toast.add({
                severity: 'success',
                summary: 'Import Success',
                detail: `${importName.value} imported successfully`,
                life: 3000,
                group: 'br',
            });
            selectedFile.value = null;

            nextTick(() => {
                if (type === 'acmat') fileUploaderACMAT.value?.clear();
                if (type === 'acsqty') fileUploaderACSQTY.value?.clear();
            });
        },

        onError: () => {
            isUploading.value = false;
            showImportDialog.value = false;

            toast.add({
                severity: 'error',
                summary: 'Import Failed',
                detail: 'There was an error importing the CSV',
                life: 3000,
                group: 'br',
            });

            selectedFile.value = null;
            nextTick(() => {
                if (type === 'acmat') fileUploaderACMAT.value?.clear();
                if (type === 'acsqty') fileUploaderACSQTY.value?.clear();
            });
        },
    });
}

function resetImportState() {
    uploadProgress.value = 0;
    selectedFile.value = null;
    notImported.value = true;
}

function startPollingProgress(type: 'acmat' | 'acsqty') {
    uploadProgress.value = 0;

    const endpointMap = {
        stamat: '/finacc/standardMat/import-progress',
        acmat: '/finacc/actualMat/import-progress',
        pack: '/finacc/pack/import-progress',
        proc: '/finacc/proc/import-progress',
        valve: '/finacc/valve/import-progress',
        bom: '/finacc/bom/import-progress',
        acsqty: '/finacc/acsqty/import-progress',
    };

    const interval = setInterval(async () => {
        try {
            const res = await axios.get(endpointMap[type]);
            uploadProgress.value = res.data.progress;

            if (uploadProgress.value >= 100) {
                clearInterval(interval);
                notImported.value = false; // ✅ sekarang bisa ganti tombol menjadi "Close"
            }
        } catch (err) {
            console.error(`Error polling ${type} import progress:`, err);
            clearInterval(interval);
        }
    }, 1000);
}

function exportCSV(type: 'acmat' | 'acsqty') {
    let $type = null;
    let $filename = null;
    if (type === 'acmat') {
        $type = dtACMAT.value;
        $filename = 'actual-mat';
    } else if (type === 'acsqty') {
        $type = dtACSQTY.value;
        $filename = 'Actual Sales Quantity';
    }
    if (!$type) return;

    const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

    $type.exportCSV({
        selectionOnly: false,
        filename: exportFilename,
    });
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const yy = String(date.getFullYear());
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function handleSave() {
    if (editType.value === 'acmat') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('acmat.update', item_code), editedData.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    group: 'br',
                    detail: `Data ${editedData.value.item_code} updated successfully`,
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete data with ${editedData.value.item_code}`,
                    life: 3000,
                });
            },
        });
    }
}

interface ImportResult {
    addedItems: string[];
    invalidItems: { item_code: string; price: string; description: string; reason: string }[];
}

const importResult = computed(() => {
    // Memberikan petunjuk tipe data kepada page.props.importResult
    const result = page.props.importResult as ImportResult;

    // Menambahkan pemeriksaan tipe data untuk memastikan `result` adalah objek
    if (!result || typeof result !== 'object') {
        return {
            addedItems: [],
            invalidItems: [],
        };
    }

    // Perbaikan untuk addedItems:
    // Mengambil item (yang berupa string bp_code) dari array
    const addedItems = (result.addedItems || []).map((item, index) => ({
        no: index + 1,
        item_code: item, // Menyimpan nilai string bp_code ke properti bp_code
    }));

    // Perbaikan untuk invalidItems:
    // Setiap item adalah objek { 'bp_code': '...', 'reason': '...' }
    // Kita perlu mengambil properti bp_code dan reason dari setiap objek
    const invalidItems = (result.invalidItems || []).map((item, index) => ({
        no: index + 1,
        item_code: item.item_code,
        price: item.price,
        description: item.description,
        reason: item.reason,
    }));

    return {
        addedItems: addedItems,
        invalidItems: invalidItems,
    };
});

const props = defineProps({
    auth: Object,
});
</script>

<template>
    <Toast group="br" position="bottom-right" />
    <Head title="Actual Master Data" />

    <AppLayout>
        <div class="m-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Actual Material Price & Sales Quantity</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Actual Material Price & Sales Master Data</p>
            </div>

            <!-- Header Section -->
            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Master Data Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>

                <Dialog
                    v-model:visible="showImportDialog"
                    header="Import Confirmation"
                    modal
                    class="w-[30rem]"
                    :closable="false"
                    @hide="resetImportState"
                >
                    <Transition name="fade" class="mb-3">
                        <div v-if="uploadProgress > 0" class="pt-2">
                            <span>Progress : </span>
                            <ProgressBar :value="uploadProgress" showValue />
                        </div>
                    </Transition>

                    <div class="space-y-4" v-if="notImported">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>

                        <p>
                            Are you sure you want to import
                            <strong class="text-blue-500">{{ importName }}</strong
                            >?
                        </p>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Cancel"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                :disabled="isUploading"
                                @click="cancelCSVimport(importType!)"
                            />
                            <Button
                                label=" Yes, Import"
                                icon="pi pi-check"
                                unstyled
                                class="w-40 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700"
                                :loading="isUploading"
                                @click="() => confirmUpload(importType!)"
                            />
                        </div>
                    </div>

                    <div class="space-y-4" v-if="!notImported">
                        <p>
                            Hi <span class="text-red-400">{{ userName }}</span
                            >,
                        </p>

                        <p>
                            Import
                            <strong class="text-green-500">Finish</strong>, it's safe to close window.
                        </p>

                        <div class="flex justify-end gap-3 pt-4">
                            <Button
                                label=" Close"
                                icon="pi pi-times"
                                unstyled
                                class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                                :disabled="isUploading"
                                @click="
                                    () => {
                                        showImportDialog = false;
                                        resetImportState();
                                    }
                                "
                            />
                        </div>
                    </div>
                </Dialog>

                <Dialog v-model:visible="showComponent" :header="`Components of ${finishGood.item_code}`" modal class="w-[60rem]" :closable="false">
                    <DataTable :value="componentItems" responsiveLayout="scroll">
                        <div class="mr-2 mb-2 flex justify-end">
                            <Button label="Close" severity="warn" @click="closeComponentDialog" />
                        </div>
                        <Column header="#" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                            <template #body="{ index }">
                                {{ index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" header="Item Code" style="white-space: nowrap" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="description" header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="uom" header="Unit of Material" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="quantity" header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="warehouse" header="Warehouse" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="depth" header="Depth" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="bom_type" header="Bom Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                    </DataTable>
                </Dialog>
            </div>

            <div class="mx-26 mb-26">
                <Tabs v-model="activeTab">
                    <TabList>
                        <Tab value="0">Actual Material Price</Tab>
                        <Tab value="1">Sales Quantity</Tab>
                    </TabList>

                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="matSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Actual Material Used</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'acmat')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('acmat')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="actualMaterial"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filterACMAT"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtACMAT"
                                >
                                    <Column
                                        field="item_code"
                                        header="Material Code"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                        ><template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="bom.description"
                                        header="Name"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : '-' }}
                                        </template>
                                    </Column>

                                    <Column field="jan_price" header="January Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jan_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="jan_qty" header="January (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jan_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="feb_price" header="February Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.feb_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="feb_qty" header="February (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.feb_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="mar_price" header="March Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.mar_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="mar_qty" header="March (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.mar_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="apr_price" header="April Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.apr_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="apr_qty" header="April (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.apr_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="may_price" header="May Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.may_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="may_qty" header="May (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.may_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="jun_price" header="June Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jun_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="jun_qty" header="June (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jun_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="jul_price" header="July Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jul_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="jul_qty" header="July (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.jul_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="aug_price" header="August Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.aug_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="aug_qty" header="August (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.aug_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="sep_price" header="September Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.sep_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="sep_qty" header="September (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.sep_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="oct_price" header="October Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.oct_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="oct_qty" header="October (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.oct_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="nov_price" header="November Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.nov_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="nov_qty" header="November (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.nov_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>

                                    <Column field="dec_price" header="December Amount" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.dec_price).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                    <Column field="dec_qty" header="December (Qty)" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ Number(data.dec_qty).toLocaleString('id-ID') }}
                                        </template>
                                    </Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="matSection" class="p-2">
                                <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between">
                                    <h2 class="mb-4 text-3xl font-semibold text-gray-900 md:mb-0 dark:text-white">Actual Material Quantity</h2>

                                    <div class="mb-4 flex flex-col items-center gap-4 md:mb-0">
                                        <FileUpload
                                            v-if="auth?.user?.permissions?.includes('Update_MasterData')"
                                            ref="fileUploaderBP"
                                            mode="basic"
                                            name="file"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            chooseIcon="pi pi-upload"
                                            @select="(event) => handleCSVImport(event, 'acsqty')"
                                            class="w-full sm:w-auto"
                                        />

                                        <div class="flex w-full flex-col items-center gap-4 sm:w-auto sm:flex-row">
                                            <Button
                                                icon="pi pi-download"
                                                label=" Export"
                                                unstyled
                                                class="w-full cursor-pointer rounded-xl bg-orange-400 px-4 py-2 text-center font-bold text-slate-900 sm:w-28"
                                                @click="exportCSV('acsqty')"
                                            />
                                        </div>
                                    </div>

                                    <div class="text-right text-gray-700 dark:text-gray-300">
                                        <div>
                                            Last Update :
                                            <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                        </div>
                                        <div>
                                            Data source From :
                                            <span class="text-cyan-400">{{ dataSource[1] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <DataTable
                                    :value="actualSalesQty"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    :rowsPerPageOptions="[10, 20, 50, 100]"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filterACSQTY"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtACSQTY"
                                >
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
                                                placeholder="Search item code"
                                                class="w-full"
                                            /> </template
                                    ></Column>
                                    <Column
                                        field="bom.description"
                                        header="Name"
                                        :showFilterMenu="false"
                                        sortable
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #filter="{ filterModel, filterCallback }">
                                            <InputText
                                                v-model="filterModel.value"
                                                @input="filterCallback()"
                                                placeholder="Search description"
                                                class="w-full"
                                            />
                                        </template>
                                        <template #body="{ data }">
                                            {{ data.bom ? data.bom.description : '-' }}
                                        </template>
                                    </Column>

                                    <Column field="jan_qty" sortable header="January (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="feb_qty" sortable header="February (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="mar_qty" sortable header="March (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle"> </Column>
                                    <Column field="apr_qty" sortable header="April (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle"> </Column>
                                    <Column field="may_qty" sortable header="May (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle"> </Column>
                                    <Column field="jun_qty" sortable header="June (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle"> </Column>
                                    <Column field="jul_qty" sortable header="July (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle"> </Column>
                                    <Column field="aug_qty" sortable header="August (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="sep_qty" sortable header="September (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="oct_qty" sortable header="October (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="nov_qty" sortable header="November (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                    </Column>
                                    <Column field="dec_qty" sortable header="December (Qty)" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
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
