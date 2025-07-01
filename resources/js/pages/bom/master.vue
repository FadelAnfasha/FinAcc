<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import FileUpload, { FileUploadUploaderEvent } from 'primevue/fileupload';
import ProgressBar from 'primevue/progressbar';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';

const dtMAT = ref();
const dtBOM = ref();
const dtPACK = ref();
const dtPROC = ref();
const visible = ref(false);

const toast = useToast();
const page = usePage();

const showImportDialog = ref(false);
const importInProgress = ref(false);

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
            toast.add({ severity: 'success', summary: 'Success', detail: 'CSV imported', life: 3000 });

            // ✅ Import selesai
            importInProgress.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Import failed', life: 3000 });
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

function editMat(bp: any) {
    console.log('Edit', bp);
    // buka dialog edit atau redirect, tergantung desain kamu
}

function deleteMat(bp: any) {
    if (confirm(`Hapus ${bp.bp_code}?`)) {
        router.delete(route('bps.destroy', bp.id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Deleted', detail: 'Data berhasil dihapus' });
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
</script>

<template>
    <Head title="Process Cost" />
    <AppLayout>
        <!-- <template>
            <Dialog v-model:visible="showResultDialog" header="Import Result" modal class="w-[40rem]">
                <div v-if="importResults.success" class="mb-4 text-green-600">
                    {{ importResults.success }}
                </div>

                <div class="mb-2" v-if="importResults.added.length">
                    <strong class="text-green-700">Ditambahkan:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.added" :key="'added-' + item">{{ item }}</li>
                    </ul>
                </div>

                <div class="mb-2" v-if="importResults.updated.length">
                    <strong class="text-yellow-700">Diperbarui:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.updated" :key="'updated-' + item">{{ item }}</li>
                    </ul>
                </div>

                <div class="mb-2" v-if="importResults.invalid.length">
                    <strong class="text-red-700">Gagal:</strong>
                    <ul class="list-disc pl-4">
                        <li v-for="item in importResults.invalid" :key="'invalid-' + item">{{ item }}</li>
                    </ul>
                </div>

                <template #footer>
                    <Button label="Tutup" icon="pi pi-times" @click="showResultDialog = false" />
                </template>
            </Dialog>
        </template> -->
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
            </div>
            <Dialog v-model:visible="showComponent" header="Component Details of ${{  }}" modal class="w-[60rem]">
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
                                        <FileUpload
                                            mode="basic"
                                            name="file"
                                            :auto="true"
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
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBP"
                                >
                                    <Column
                                        field="item_code"
                                        sortable
                                        header="Material Code"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>

                                    <Column field="description" sortable header="Description" :headerStyle="headerStyle" :bodyStyle="bodyStyle">
                                        <template #body="{ data }">
                                            {{ data.bom?.description ?? '-' }}
                                        </template>
                                    </Column>

                                    <Column field="in_stock" sortable header="In Stock" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="item_group" sortable header="Group" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="price" sortable header="Price" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

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
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editMat(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteMat(slotProps.data)" />
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
                                        <FileUpload
                                            mode="basic"
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
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBP"
                                >
                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column field="price" sortable header="Packing Price" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

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
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editMat(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteMat(slotProps.data)" />
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
                                        <FileUpload
                                            mode="basic"
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
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtBP"
                                >
                                    <Column field="item_code" sortable header="Item Code" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>
                                    <Column
                                        field="description"
                                        sortable
                                        header="Description"
                                        :headerStyle="headerStyle"
                                        :bodyStyle="bodyStyle"
                                    ></Column>

                                    <Column field="price" sortable header="Price" :headerStyle="headerStyle" :bodyStyle="bodyStyle"></Column>

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
                                                <Button icon="pi pi-pencil" severity="warning" rounded text @click="editMat(slotProps.data)" />
                                                <Button icon="pi pi-trash" severity="danger" rounded text @click="deleteMat(slotProps.data)" />
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
                                        <FileUpload
                                            mode="basic"
                                            name="file"
                                            :auto="true"
                                            :customUpload="true"
                                            accept=".csv"
                                            chooseLabel="Import CSV"
                                            @uploader="(event) => handleCSVImport(event, 'bom')"
                                        />
                                        <Button icon="pi pi-download" class="text-end" label="Export" @click="exportCSV('bom')" />
                                    </div>
                                </div>
                                <DataTable
                                    :value="billOfMaterials"
                                    tableStyle="min-width: 50rem"
                                    :rows="10"
                                    paginator
                                    removableSort
                                    class="text-md"
                                    filterDisplay="header"
                                    ref="dtCT"
                                >
                                    <Column field="item_code" header="Item Code" sortable :headerStyle="headerStyle" :bodyStyle="bodyStyle" />
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
                                                <Button icon="pi pi-eye" severity="info" rounded text @click="viewComponents(slotProps.data)" />
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
