<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import dayjs from 'dayjs';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

const dtMAT = ref();
const dtBOM = ref();
const dtPACK = ref();
const dtPROC = ref();
const toast = useToast();
const page = usePage();

const filters = ref({
    item_code: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const loading = ref(false);

const materials = computed(() =>
    (page.props.materials as any[]).map((mat, index) => ({
        ...mat,
        no: index + 1,
        created_at_formatted: formatDate(mat.created_at),
        updated_at_formatted: formatDate(mat.updated_at),
    })),
);

const billOfMaterials = computed(() =>
    (page.props.billOfMaterials as any[]).map((bom, index) => ({
        ...bom,
        no: index + 1,
        created_at_formatted: formatDate(bom.created_at),
        updated_at_formatted: formatDate(bom.updated_at),
    })),
);

const packings = computed(() =>
    (page.props.packings as any[]).map((pack, index) => ({
        ...pack,
        no: index + 1,
        created_at_formatted: formatDate(pack.created_at),
        updated_at_formatted: formatDate(pack.updated_at),
    })),
);

const processes = computed(() =>
    (page.props.processes as any[]).map((proc, index) => ({
        ...proc,
        no: index + 1,
        created_at_formatted: formatDate(proc.created_at),
        updated_at_formatted: formatDate(proc.updated_at),
    })),
);

interface ComponentItem {
    item_code: string;
    description: string;
    // Tambahkan field lain jika diperlukan
}

const lastUpdate = computed(() => {
    // Business Partners
    const mat_update = ((page.props.materials as any[]) ?? []).map((mat) => new Date(mat.updated_at));
    const Max_matUpdate = mat_update.length ? new Date(Math.max(...mat_update.map((d) => d.getTime()))) : null;

    // Cycle Times
    const pack_update = ((page.props.packings as any[]) ?? []).map((pack) => new Date(pack.updated_at));
    const Max_packUpdate = pack_update.length ? new Date(Math.max(...pack_update.map((d) => d.getTime()))) : null;

    // Sales Quantities
    const proc_update = ((page.props.processes as any[]) ?? []).map((proc) => new Date(proc.updated_at));
    const Max_procUpdate = proc_update.length ? new Date(Math.max(...proc_update.map((d) => d.getTime()))) : null;

    const bom_update = ((page.props.billOfMaterials as any[]) ?? []).map((bom) => new Date(bom.updated_at));
    const Max_bomUpdate = bom_update.length ? new Date(Math.max(...bom_update.map((d) => d.getTime()))) : null;

    return [Max_matUpdate, Max_packUpdate, Max_procUpdate, Max_bomUpdate];
});

const dataSource = [
    'Share Others/Finacc/BillOfMaterial/Material Price (MP)/mat_master.csv',
    'Share Others/Finacc/BillOfMaterial/Packing Price (MP)/pack_master.csv',
    'Share Others/Finacc/BillOfMaterial/Process Price (MP)/proc_master.csv',
    'Share Others/Finacc/BillOfMaterial/Bill of Material (BOM)/bom_master.csv',
];

function formatlastUpdate(date: Date | string) {
    return dayjs(date).format('DD MMM YYYY HH:mm:ss');
}

const componentItems = ref((page.props.component as ComponentItem[]) ?? []);
const showComponent = ref(componentItems.value.length > 0);

watch(
    () => page.props.component,
    (newVal) => {
        componentItems.value = (newVal as ComponentItem[]) ?? [];
        showComponent.value = componentItems.value.length > 0;
    },
    { immediate: true },
);

const headerStyle = { backgroundColor: '#758596', color: 'white' };
const bodyStyle = { backgroundColor: '#c8cccc', color: 'black' };

const showDialog = ref(false);
const dialogWidth = ref('40rem');
const editType = ref<'mat' | 'pack' | 'proc' | null>(null);
const destroyType = ref<'mat' | 'pack' | 'proc' | 'bom' | null>(null);
const headerType = ref<any>({});
const showImportDialog = ref(false);
const importInProgress = ref(false);
const editedData = ref<any>({});
const destroyedData = ref<any>({});
const groups = ref([
    { name: 'Raw Material', code: 'RAW MATERIAL' },
    { name: 'Sparepart & Tools', code: 'SPAREPARTS AND TOOLS' },
]);
const manufacturer = ref([
    { name: 'M TL', code: 'M TL' },
    { name: 'M Single', code: 'M Single' },
    { name: 'M Double', code: 'M Double' },
    { name: 'L WT', code: 'L WT' },
    { name: '- No Manufacturer -', code: '- No Manufacturer -' },
]);

function handleCSVImport(event: FileUploadUploaderEvent, type: 'mat' | 'bom' | 'pack' | 'proc') {
    let file: File | undefined;
    if (Array.isArray(event.files)) {
        file = event.files[0];
    } else if (event.files instanceof File) {
        file = event.files;
    }
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found in meta tag!');
        return;
    }

    let $route = null;
    if (type === 'mat') {
        $route = 'mat.import';
    } else if (type === 'bom') {
        $route = 'bom.import';
    } else if (type === 'pack') {
        $route = 'pack.import';
    } else if (type === 'proc') {
        $route = 'proc.import';
    }

    // ✅ Mulai import: tampilkan dialog dan progress
    showImportDialog.value = true;
    importInProgress.value = true;

    router.post(route($route), formData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.add({ severity: 'success', group: 'br', summary: 'Success', detail: 'CSV imported', life: 3000 });

            // ✅ Import selesai
            importInProgress.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', group: 'br', summary: 'Error', detail: 'Import failed', life: 3000 });
            importInProgress.value = false;
        },
    });
}

function exportCSV(type: 'mat' | 'bom' | 'pack' | 'proc') {
    let $type = null;
    let $filename = null;
    if (type === 'mat') {
        $type = dtMAT.value;
        $filename = 'bom';
    } else if (type === 'pack') {
        $type = dtPACK.value;
        $filename = 'pack';
    } else if (type === 'proc') {
        $type = dtPROC.value;
        $filename = 'proc';
    } else if (type === 'bom') {
        $type = dtBOM.value;
        $filename = 'cycle-times';

        if (!$type) return;

        const exportFilename = `${$filename}-${new Date().toISOString().slice(0, 10)}.csv`;

        $type.exportCSV({
            selectionOnly: false,
            filename: exportFilename,
        });
    }
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const yy = String(date.getFullYear());
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
}

function editData(data: any, type: 'mat' | 'pack' | 'proc') {
    console.log('Edit', data);
    editedData.value = { ...data };
    editType.value = type;
    headerType.value = 'Edit data';
    // Atur lebar berdasarkan type
    if (type === 'mat') {
        dialogWidth.value = '40rem';
    } else if (type === 'pack') {
        dialogWidth.value = '40rem';
    } else if (type === 'proc') {
        dialogWidth.value = '40rem';
    }
    showDialog.value = true;
}

function handleSave() {
    if (editType.value === 'mat') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('mat.update', item_code), editedData.value, {
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
                    detail: `Failed to delete data with ${editedData.value.bp_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'pack') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('pack.update', item_code), editedData.value, {
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
                    detail: `Failed to delete data with ${editedData.value.bp_code}`,
                    life: 3000,
                });
            },
        });
    } else if (editType.value === 'proc') {
        const item_code = editedData.value.item_code;
        if (!item_code) return;

        router.put(route('proc.update', item_code), editedData.value, {
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
                    detail: `Failed to delete data with ${editedData.value.bp_code}`,
                    life: 3000,
                });
            },
        });
    }
}

function destroyData(data: any, type: 'mat' | 'pack' | 'proc' | 'bom') {
    destroyedData.value = { ...data };
    destroyType.value = type;
    headerType.value = 'Delete data';
    if (type === 'mat') {
        dialogWidth.value = '40rem';
    } else if (type === 'pack') {
        dialogWidth.value = '40rem';
    }
    if (type === 'proc') {
        dialogWidth.value = '40rem';
    }

    showDialog.value = true;
}

function handleDestroy() {
    if (destroyType.value === 'mat') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('mat.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
                    group: 'br',
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    } else if (destroyType.value === 'pack') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('pack.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
                    group: 'br',
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    } else if (destroyType.value === 'proc') {
        const item_code = destroyedData.value.item_code;
        if (!item_code) return;

        router.delete(route('proc.destroy', item_code), {
            data: destroyedData.value,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Success',
                    detail: `Data ${destroyedData.value.item_code} deleted successfully`,
                    group: 'br',
                    life: 3000,
                });
                showDialog.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'warn',
                    summary: 'Error',
                    group: 'br',
                    detail: `Failed to delete this  ${destroyedData.value.item_code} data`,
                    life: 3000,
                });
            },
        });
    }
}

function viewComponents(bom: any) {
    // buka dialog untuk melihat komponen BOM
    router.get(
        route('bom.components', bom.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Toast group="br" position="bottom-right" />

    <Head title="Bill of Material" />
    <AppLayout>
        <div class="m-6">
            <div class="flex flex-col gap-1">
                <h2 class="mb-2 text-start text-3xl font-bold text-gray-900 dark:text-white">Bill of Material</h2>
                <p class="text-start text-gray-600 dark:text-gray-400">Calculating Bill of Material</p>
            </div>
            <!-- Header Section -->
            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-10 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Master Data Section
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>

                <div class="relative mb-6 text-center">
                    <Dialog v-model:visible="showImportDialog" :closable="false" header="Importing CSV" modal class="w-[30rem]">
                        <div v-if="importInProgress">
                            <ProgressBar mode="indeterminate" style="height: 6px" />
                            <p class="mt-2 text-center">Importing, please wait...</p>
                        </div>
                        <div v-else class="text-center">
                            <p class="mb-3">Import complete!</p>
                            <Button label="Close" icon="pi pi-times" @click="showImportDialog = false" />
                        </div>
                    </Dialog>
                </div>

                <Dialog v-model:visible="showComponent" :header="`Component`" modal class="w-[60rem]">
                    <DataTable :value="componentItems" responsiveLayout="scroll">
                        <Column header="#" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                            <template #body="{ index }">
                                {{ index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="description" header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="uom" header="Unit of Material" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="quantity" header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="warehouse" header="Warehouse" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="depth" header="Depth" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                        <Column field="bom_type" header="Bom Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                    </DataTable>
                </Dialog>

                <Dialog v-model:visible="showDialog" :header="headerType" modal :style="{ width: dialogWidth }" :closable="false">
                    <div v-if="editType === 'mat'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Material Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="description" class="w-24 font-semibold">Description</label>
                            <InputText
                                id="description"
                                class="flex-auto"
                                :value="editedData.bom?.description || '-'"
                                autocomplete="off"
                                :disabled="true"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="in_stock" class="w-24 font-semibold">In Stock</label>
                            <InputNumber
                                id="in_stock"
                                class="flex-auto"
                                inputId="integeronly"
                                :min="0"
                                v-model="editedData.in_stock"
                                autocomplete="off"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_group" class="w-24 font-semibold">Group</label>
                            <Select v-model="editedData.item_group" :options="groups" optionLabel="name" optionValue="code" class="w-full md:w-56" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>

                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        editType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Save" @click="handleSave()"></Button>
                        </div>
                    </div>

                    <div v-if="editType === 'pack'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        editType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Save" @click="handleSave()"></Button>
                        </div>
                    </div>

                    <div v-if="editType === 'proc'" class="space-y-6">
                        <div class="mb-4 flex items-center gap-4">
                            <label for="item_code" class="w-24 font-semibold">Item Code</label>
                            <InputText id="item_code" class="flex-auto" v-model="editedData.item_code" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="description" class="w-24 font-semibold">Description</label>
                            <InputText id="description" class="flex-auto" v-model="editedData.description" autocomplete="off" :disabled="true" />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="price" class="w-24 font-semibold">Price</label>
                            <InputNumber
                                id="price"
                                class="flex-auto"
                                inputId="currency-indonesia"
                                mode="currency"
                                currency="IDR"
                                locale="id-ID"
                                :maxFractionDigits="2"
                                v-model="editedData.price"
                                autocomplete="off"
                                :min="0"
                            />
                        </div>
                        <div class="mb-4 flex items-center gap-4">
                            <label for="manufacturer" class="w-24 font-semibold">Manufacturer</label>
                            <Select
                                v-model="editedData.manufacturer"
                                :options="manufacturer"
                                optionLabel="name"
                                optionValue="code"
                                class="w-full md:w-56"
                            />
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        editType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Save" @click="handleSave()"></Button>
                        </div>
                    </div>

                    <div v-if="destroyType === 'mat'" class="space-y-6">
                        <span>
                            Are you sure want to delete Material data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span> ?
                        </span>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        destroyType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Delete" severity="danger" @click="handleDestroy()"></Button>
                        </div>
                    </div>

                    <div v-if="destroyType === 'pack'" class="space-y-6">
                        <span>
                            Are you sure want to delete Packing data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.bom?.description || '-' }}</span> ?
                        </span>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        destroyType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Delete" severity="danger" @click="handleDestroy()"></Button>
                        </div>
                    </div>

                    <div v-if="destroyType === 'proc'" class="space-y-6">
                        <span>
                            Are you sure want to delete Process data with item code
                            <span class="font-semibold text-red-600">{{ destroyedData.item_code }} </span> and description
                            <span class="font-semibold text-red-600">{{ destroyedData.description || '-' }}</span> ?
                        </span>
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                label="Cancel"
                                severity="secondary"
                                @click="
                                    () => {
                                        showDialog = false;
                                        destroyType = null;
                                    }
                                "
                            ></Button>
                            <Button type="button" label="Delete" severity="danger" @click="handleDestroy()"></Button>
                        </div>
                    </div>
                </Dialog>
            </div>

            <div class="mx-26 mb-26">
                <Tabs value="0">
                    <TabList>
                        <Tab value="0">Material</Tab>
                        <Tab value="1">Packing</Tab>
                        <Tab value="2">Process</Tab>
                        <Tab value="3">Bill of Material</Tab>
                    </TabList>

                    <!-- Process Items Grid -->
                    <TabPanels>
                        <TabPanel value="0">
                            <section ref="matSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Material</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[0] ? formatlastUpdate(lastUpdate[0]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[0] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            resizableColumns
                                            columnResizeMode="expand"
                                            showGridlines
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'mat')"
                                        />

                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('mat')" />
                                    </div>
                                </div>

                                <DataTable
                                    :value="materials"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtBP"
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

                                    <Column field="description" sortable header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data?.bom?.description ?? '-' }}
                                        </template>
                                    </Column>

                                    <Column field="in_stock" sortable header="In Stock" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="item_group" sortable header="Group" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="price" header="Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
                                        </template>
                                    </Column>

                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'mat')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'mat')"
                                                />
                                            </div> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="1">
                            <section ref="packSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Packing</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[1] ? formatlastUpdate(lastUpdate[1]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[1] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'pack')"
                                        />

                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('pack')" />
                                    </div>
                                </div>

                                <DataTable
                                    :value="packings"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtBP"
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
                                    <Column field="price" header="Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
                                        </template>
                                    </Column>
                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'pack')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'pack')"
                                                />
                                            </div> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="2">
                            <section ref="packSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Process</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[2] ? formatlastUpdate(lastUpdate[2]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[2] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'proc')"
                                        />

                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('proc')" />
                                    </div>
                                </div>

                                <DataTable
                                    :value="processes"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtBP"
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
                                        field="description"
                                        sortable
                                        header="Description"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>

                                    <Column field="price" header="Price" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ formatCurrency(data.price) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="manufacturer"
                                        sortable
                                        header="Manufacturer"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>

                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle"
                                        ><template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    severity="warning"
                                                    rounded
                                                    text
                                                    @click="editData(slotProps.data, 'proc')"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    severity="danger"
                                                    rounded
                                                    text
                                                    @click="destroyData(slotProps.data, 'proc')"
                                                />
                                            </div> </template
                                    ></Column>
                                </DataTable>
                            </section>
                        </TabPanel>

                        <TabPanel value="3">
                            <section ref="bomSection" class="p-2">
                                <div class="mb-4 flex items-center justify-between">
                                    <h2 class="text-3xl font-semibold hover:text-indigo-500">Bill of Material</h2>
                                    <div class="flex gap-4">
                                        <div>
                                            <div>
                                                Last Update :
                                                <span class="text-red-300">{{ lastUpdate[3] ? formatlastUpdate(lastUpdate[3]) : '-' }}</span>
                                            </div>
                                            <div>
                                                Data source From : <span class="text-cyan-300">{{ dataSource[3] }}</span>
                                            </div>
                                        </div>
                                        <FileUpload
                                            mode="basic"
                                            chooseIcon="pi pi-upload"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'bom')"
                                        />
                                    </div>
                                </div>
                                <DataTable
                                    :value="billOfMaterials"
                                    tableStyle="min-width: 50rem"
                                    paginator
                                    :rows="10"
                                    resizableColumns
                                    columnResizeMode="expand"
                                    showGridlines
                                    removableSort
                                    v-model:filters="filters"
                                    filterDisplay="row"
                                    :loading="loading"
                                    :globalFilterFields="['item_code']"
                                    class="text-md"
                                    ref="dtCT"
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
                                    <Column field="description" header="Description" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="uom" header="Unit of Material" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="quantity" header="Quantity" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="warehouse" header="Warehouse" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="depth" header="Depth" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
                                    <Column field="bom_type" header="BOM Type" :headerStyle="headerStyle" :bodyStyle="bodyStyle" />

                                    <Column field="created_at_formatted" sortable header="Added at" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.created_at) }}
                                        </template>
                                    </Column>

                                    <Column
                                        field="updated_at_formatted"
                                        sortable
                                        header="Updated at"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    >
                                        <template #body="slotProps">
                                            {{ formatDate(slotProps.data.updated_at) }}
                                        </template>
                                    </Column>

                                    <Column field="action" header="Action" :exportable="false" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-tooltip="'View Component'"
                                                    icon="pi pi-eye"
                                                    severity="info"
                                                    rounded
                                                    text
                                                    @click="viewComponents(slotProps.data)"
                                                />
                                            </div>
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
