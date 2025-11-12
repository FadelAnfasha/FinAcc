<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';

import { computed, ref, Ref, watch, watchEffect } from 'vue';

const page = usePage();
const toast = useToast();
const prioritiesOption = ref([
    { name: 'Low', code: '1' },
    { name: 'Medium', code: '2' },
    { name: 'High', code: '3' },
    { name: 'Urgent', code: '4' },
]);
const selectedPriority = ref('1');
const today = new Date();
const formattedDate = today
    .toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
    .replace(/\//g, ' - ');

watchEffect(() => {
    const currentFlash = page.props.flash as { success?: string; error?: string } | undefined;
    if (currentFlash?.success) {
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: currentFlash.success,
            life: 3000,
        });
    }

    if (currentFlash?.error) {
        toast.add({
            severity: 'error', // Ganti dengan 'error' jika library toast Anda menggunakan ini untuk pesan kesalahan/danger
            summary: 'Rejected',
            detail: currentFlash.error,
            life: 3000,
        });
    }
});

const form = useForm({
    name: (page.props.auth.user as UserWithNPK)?.name || '',
    npk: (page.props.auth.user as UserWithNPK)?.npk || '',
    priority: '1',
    created_at: formattedDate,
    description: '',
    status: '1',
    attachment: null as File | null,
    updated_at: new Date().toISOString().split('T')[0],
});

watch(
    selectedPriority,
    (newPriorityCode) => {
        form.priority = newPriorityCode;
    },
    { immediate: true },
);

// Define proper types
interface Priority {
    id: number;
    priority: string;
}

interface Status {
    id: number;
    status: string;
}

interface RequestItem {
    id: number;
    name: string;
    npk: string;
    priority: Priority;
    created_at: string;
    description: string;
    status: Status;
    impact: string;
    revision: string;
    attachment: File;
    updated_at: string;
}

const acceptRequest = (id: number) => {
    router.post(`rfs/${id}/accept`);
};

const rejectRequest = (id: number) => {
    router.post(`rfs/${id}/reject`);
};

const executeRequest = (id: number) => {
    router.post(`rfs/${id}/execute`);
};

const userReview = (id: number) => {
    router.post(`rfs/${id}/uat`);
};

const showDialog: Ref<boolean> = ref(false);
const viewDialog: Ref<boolean> = ref(false);

const userName = computed(() => page.props.auth?.user?.name ?? '');
const impactValue = ref<string>('');
const revisionValue = ref<string>('');
const selectedId = ref<number | null>(null);
const dialogType = ref<string | null>(null); // Tambahkan baris ini

const viewRequest = (id: number) => {
    selectedId.value = id;
    viewDialog.value = true;
};

// Properti terkomputasi untuk mencari item yang sesuai dari `items`
const selectedRequest = computed(() => {
    return items.value.find((req) => req.id === selectedId.value);
});

const closeViewDialog = () => {
    viewDialog.value = false;
    selectedId.value = null; // Reset ID agar dialog bersih saat dibuka lagi
};

const user_acceptance = (id: number, type: string) => {
    selectedId.value = id;
    dialogType.value = type;
    showDialog.value = true;
};

const revision = (id: number, type: string) => {
    selectedId.value = id;
    dialogType.value = type;
    showDialog.value = true;
};

const isImpactValid = computed(() => {
    return impactValue.value.trim() !== '';
});

const submitAcceptance = () => {
    if (selectedId.value !== null) {
        // Objek form untuk dikirimkan
        const form = {
            impact: impactValue.value,
        };

        // Mengirim permintaan POST dengan data form
        router.post(`rfs/${selectedId.value}/finish`, form, {
            onSuccess: () => {
                // Berhasil: tutup dialog dan reset nilai
                showDialog.value = false;
                impactValue.value = '';
                selectedId.value = null;
            },
            onError: (errors) => {
                // Gagal: tampilkan pesan error
                console.error('Terjadi kesalahan:', errors);
            },
        });
    }
};

const isRevisionValid = computed(() => {
    return revisionValue.value.trim() !== '';
});

const submitRevision = () => {
    if (selectedId.value !== null) {
        // Objek form untuk dikirimkan
        const form = {
            revision: revisionValue.value,
        };

        // Mengirim permintaan POST dengan data form
        router.post(`rfs/${selectedId.value}/revision`, form, {
            onSuccess: () => {
                // Berhasil: tutup dialog dan reset nilai
                showDialog.value = false;
                revisionValue.value = '';
                selectedId.value = null;
            },
            onError: (errors) => {
                // Gagal: tampilkan pesan error
                console.error('Terjadi kesalahan:', errors);
            },
        });
    }
};

const closeDialog = () => {
    showDialog.value = false;
    impactValue.value = '';
    revisionValue.value = '';
};

const { services, auth } = defineProps({
    services: Array,
    auth: Object,
});

const filters = ref({
    name: { value: null, matchMode: 'contains' },
    'priority.priority': { value: null, matchMode: 'equals' },
    created_at: { value: null, matchMode: 'contains' },
    description: { value: null, matchMode: 'contains' },
    impact: { value: null, matchMode: 'contains' },
    'status.status': { value: null, matchMode: 'equals' },
    updated_at: { value: null, matchMode: 'contains' },
});

// Data with proper typing and added status field
const items = computed(() => page.props.services as RequestItem[]);
const priorities = ['All', 'low', 'medium', 'high', 'urgent'];
const statuses = ['All', 'wait_for_review', 'accepted', 'rejected', 'in_progress', 'user_acceptance', 'revision', 'finish'];

const getPriorityClass = (priority: string) => {
    switch (priority) {
        case 'low':
            return 'bg-green-200 text-green-700';
        case 'medium':
            return 'bg-blue-200 text-blue-700';
        case 'high':
            return 'bg-orange-200 text-orange-700';
        case 'urgent':
            return 'bg-red-200 text-red-700';
        default:
            return 'bg-gray-300 text-gray-800';
    }
};

const getStatusClass = (status: string): string => {
    switch (status) {
        case 'wait_for_review':
            // Warna oranye yang kuat untuk status "warning"
            return 'bg-amber-400 text-black';
        case 'accepted':
            // Biru muda untuk status yang disetujui
            return 'bg-blue-400 text-black';
        case 'rejected':
            // Merah adalah warna standar untuk "ditolak"
            return 'bg-red-400 text-black';
        case 'in_progress':
            // teal yang jelas untuk status "sedang berjalan"
            return 'bg-teal-400 text-black';
        case 'user_acceptance':
            // Warna ungu yang berbeda untuk "aksi yang dibutuhkan dari pengguna"
            return 'bg-fuchsia-300 text-black';
        case 'revision':
            // Merah adalah warna standar untuk "ditolak"
            return 'bg-rose-300 text-black';
        case 'finish':
            // Hijau adalah warna standar untuk "selesai"
            return 'bg-green-400 text-black';
        default:
            // Abu-abu untuk status netral atau tidak diketahui
            return 'bg-gray-300 text-black';
    }
};

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        wait_for_review: 'Wait for Review',
        accepted: 'Accepted',
        rejected: 'Rejected',
        in_progress: 'In Progress',
        user_acceptance: 'User Acceptance',
        revision: 'Revision',
        finish: 'Finish',
    };
    return map[status] || status;
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Type assertion for user with npk property
interface UserWithNPK {
    name?: string;
    npk?: string;
    [key: string]: any;
}

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref();
const loading = ref(false);

// Ref for create form section
const createFormSection = ref<HTMLElement | null>(null);
const dataSection = ref<HTMLElement | null>(null);

// Handle file selection with proper typing
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.attachment = file;
        selectedFileName.value = file.name;
    }
};

// Submit form
const submitForm = () => {
    form.post('rfs/store', {
        forceFormData: true, // WAJIB untuk upload file
        onStart: () => {
            form.clearErrors();
        },
        onSuccess: () => {
            form.reset();
            selectedFileName.value = null;
            if (fileInput.value) {
                fileInput.value = null;
            }
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors);
        },
    });
};

// Reset form
const resetForm = () => {
    form.reset();
    selectedFileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    selectedPriority.value = '1';
};

// Auto scroll to create form
const scrollToCreateForm = () => {
    if (createFormSection.value) {
        createFormSection.value.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
};

const scrollTodataSection = () => {
    if (dataSection.value) {
        dataSection.value.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
};
</script>

<template>
    <Head title="RFS" />
    <AppLayout>
        <Dialog v-model:visible="showDialog" header="Review Confirmation" modal class="w-[30rem]" :closable="false">
            <hr class="border-gray-200" />

            <div class="space-y-4">
                <p>
                    Hi <span class="text-red-400">{{ userName }}</span
                    >,
                </p>

                <div v-if="dialogType == 'uat'">
                    <p>
                        Please describe the impact of
                        <strong class="text-green-500">Improvement/Request</strong>?
                    </p>
                    <Textarea v-model="impactValue" autoResize rows="3" cols="50" class="mb-4" placeholder="Impact" required />
                    <strong class="mt-1 text-sm text-red-500" v-if="!isImpactValid">Impact cannot be empty!</strong>
                    <div class="mt-6 flex justify-end gap-2">
                        <Button
                            type="button"
                            label="Cancel"
                            unstyled
                            class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                            @click="closeDialog"
                        />
                        <Button
                            type="button"
                            label="Submit"
                            unstyled
                            :disabled="!isImpactValid"
                            class="w-28 cursor-pointer rounded-xl px-4 py-2 text-center font-bold text-slate-900"
                            :class="{ 'bg-green-500 hover:bg-green-700': isImpactValid, 'cursor-not-allowed bg-gray-400': !isImpactValid }"
                            @click="submitAcceptance"
                        />
                    </div>
                </div>

                <div v-if="dialogType == 'rev'">
                    <p>
                        Please describe the revision from the
                        <strong class="text-lime-300">request</strong>?
                    </p>
                    <Textarea v-model="revisionValue" autoResize rows="3" cols="50" class="mb-4" placeholder="Describe" required />
                    <strong class="mt-1 text-sm text-red-500" v-if="!isRevisionValid">Description of revision cannot be empty!</strong>
                    <div class="mt-6 flex justify-end gap-2">
                        <Button
                            type="button"
                            label="Cancel"
                            unstyled
                            class="w-28 cursor-pointer rounded-xl bg-red-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-red-700"
                            @click="closeDialog"
                        />
                        <Button
                            type="button"
                            label="Submit"
                            unstyled
                            :disabled="!isRevisionValid"
                            class="w-28 cursor-pointer rounded-xl px-4 py-2 text-center font-bold text-slate-900"
                            :class="{ 'bg-green-500 hover:bg-green-700': isRevisionValid, 'cursor-not-allowed bg-gray-400': !isRevisionValid }"
                            @click="submitRevision"
                        />
                    </div>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="viewDialog" header="Request Detail" modal class="w-[60rem]" :closable="false">
            <hr class="border-gray-200" />
            <div class="space-y-4 p-4">
                <div v-if="selectedRequest" class="space-y-2">
                    <div class="grid grid-cols-4 gap-6">
                        <div class="col-span-4 w-full rounded-xl bg-slate-200 px-2 py-1 dark:bg-slate-200">
                            <p class="font-semibold text-gray-700 dark:text-gray-700">Request Description :</p>
                            <p class="text-md text-gray-700 dark:text-gray-700">{{ selectedRequest.description }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-100">Status :</p>
                            <p
                                class="inline-block w-full rounded-full px-4 py-1 text-center text-xs font-semibold"
                                :class="getStatusClass(selectedRequest.status.status)"
                            >
                                {{ statusLabel(selectedRequest.status.status) }}
                            </p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-100">Priority :</p>
                            <p
                                class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                :class="getPriorityClass(selectedRequest.priority.priority)"
                            >
                                {{ capitalize(selectedRequest.priority.priority) }}
                            </p>
                        </div>

                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-100">Request By :</p>
                            <p class="text-md text-gray-700 dark:text-gray-100">{{ selectedRequest.name }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-100">NPK :</p>
                            <p class="text-md text-gray-700 dark:text-gray-100">{{ selectedRequest.npk }}</p>
                        </div>

                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-100">Requested at :</p>
                            <p class="text-md text-gray-700 dark:text-gray-100">{{ selectedRequest.created_at }}</p>
                        </div>

                        <div>
                            <p class="font-semibold text-gray-700 dark:text-gray-100">Updated at :</p>
                            <p class="text-md text-gray-700 dark:text-gray-100">{{ selectedRequest.updated_at }}</p>
                        </div>

                        <div class="col-span-2 w-full rounded-xl bg-slate-200 px-2 py-1 dark:bg-gray-200">
                            <p class="font-semibold text-gray-700 dark:text-gray-700">Impact :</p>
                            <p class="text-md text-gray-700 dark:text-gray-700">{{ selectedRequest.impact ?? 'Not finish yet.' }}</p>
                        </div>
                        <div class="col-span-2 w-full rounded-xl bg-slate-200 px-2 py-1 dark:bg-slate-200">
                            <p class="font-semibold text-gray-700 dark:text-gray-700">Revision :</p>
                            <p class="text-md text-gray-700 dark:text-gray-700">{{ selectedRequest.revision ?? '-' }}</p>
                        </div>
                        <div class="col-span-2 w-full rounded-xl bg-slate-200 px-2 py-1 dark:bg-slate-200">
                            <p class="font-semibold text-gray-700 dark:text-gray-700">Attachment :</p>
                            <a
                                v-if="selectedRequest.attachment"
                                :href="`/storage/${selectedRequest.attachment}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-md inline-flex items-center gap-1 rounded bg-blue-500 px-3 py-1 text-xs font-semibold text-gray-700 text-white hover:bg-blue-600 dark:text-gray-100"
                            >
                                <i class="pi pi-external-link" /> View
                            </a>
                            <div v-else>
                                <p class="text-md text-gray-700 dark:text-gray-700">No Attachment</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p class="text-center text-gray-500">Loading data...</p>
                </div>
                <div class="mt-6 flex justify-end">
                    <Button
                        type="button"
                        label="Close"
                        unstyled
                        class="w-28 cursor-pointer rounded-xl bg-gray-500 px-4 py-2 text-center font-bold text-white hover:bg-gray-700"
                        @click="closeViewDialog"
                    />
                </div>
            </div>
        </Dialog>

        <!-- Data Table Section -->
        <section ref="dataSection" class="m-6 scroll-mt-16">
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-start text-3xl font-bold text-gray-900 dark:text-white">Request Data</h2>
                    <p class="text-start text-gray-600 dark:text-gray-400">Display all request, create request, and approving request.</p>
                </div>
                <Button
                    label=" Create"
                    severity="info"
                    v-if="auth?.user?.role !== 'Admin'"
                    @click="scrollToCreateForm"
                    icon="pi pi-plus"
                    unstyled
                    class="w-full cursor-pointer rounded-xl bg-emerald-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700 sm:w-auto"
                />
            </div>

            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-1 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        List of Request
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <DataTable
                v-model:filters="filters"
                :value="items"
                :customSort="true"
                removableSort
                paginator
                :rows="10"
                :rowsPerPageOptions="[5, 10, 15, 20]"
                dataKey="id"
                filterDisplay="row"
                :loading="loading"
                :globalFilterFields="['name', 'priority', 'created_at', 'description', 'impact', 'status', 'updated_at']"
                tableStyle="min-width: 50rem"
                sortField="created_at"
                class="text-sm"
                :sortOrder="-1"
            >
                <Column field="created_at" header="Request Date" :showFilterMenu="false" sortable style="width: 15%">
                    <template #filter="{ filterModel, filterCallback }">
                        <DatePicker
                            :modelValue="filterModel.value ? new Date(filterModel.value + 'T00:00:00') : null"
                            @update:modelValue="
                                (val: Date | Date[] | (Date | null)[] | null | undefined) => {
                                    let selectedDate: Date | null = null;

                                    if (val instanceof Date) {
                                        selectedDate = val;
                                    } else if (Array.isArray(val) && val.length > 0) {
                                        if (val[0] instanceof Date) {
                                            selectedDate = val[0];
                                        }
                                    }

                                    if (selectedDate instanceof Date) {
                                        const formatted =
                                            selectedDate.getFullYear() +
                                            '-' +
                                            String(selectedDate.getMonth() + 1).padStart(2, '0') +
                                            '-' +
                                            String(selectedDate.getDate()).padStart(2, '0');
                                        filterModel.value = formatted;
                                    } else {
                                        filterModel.value = null;
                                    }
                                    filterCallback();
                                }
                            "
                            dateFormat="yy-mm-dd"
                            placeholder="yy-mm-dd"
                            :maxDate="new Date()"
                            showIcon
                            selectionMode="single"
                        />
                    </template>
                </Column>

                <Column field="name" header="Name" :showFilterMenu="false" sortable style="width: 10%">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Search name" />
                    </template>
                </Column>

                <Column
                    field="priority.priority"
                    header="Priority"
                    :sortable="true"
                    :showFilterMenu="false"
                    style="width: 10%"
                    class="justify-items-center"
                >
                    <!-- Body Badge -->
                    <template #body="{ data }">
                        <span
                            :class="getPriorityClass(data.priority.priority)"
                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                        >
                            {{ capitalize(data.priority.priority) }}
                        </span>
                    </template>

                    <!-- Filter -->
                    <template #filter="{ filterModel, filterCallback }">
                        <div class="flex justify-center">
                            <Select
                                v-model="filterModel.value"
                                :options="priorities"
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
                                    <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500"> Select priority </span>
                                    <span
                                        v-else
                                        :class="getPriorityClass(value)"
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
                                        :class="getPriorityClass(option)"
                                        class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                    >
                                        {{ capitalize(option) }}
                                    </span>
                                </template>
                            </Select>
                        </div>
                    </template>
                </Column>

                <Column field="description" header="Req. Description" :showFilterMenu="false" sortable style="width: 20%">
                    <template #body="{ data }">
                        <div class="max-w-[280px] truncate" :title="data.description" v-tooltip.top="data.description">
                            {{ data.description }}
                        </div>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Search desc" />
                    </template>
                </Column>

                <Column
                    field="status.status"
                    header="Status"
                    :sortable="true"
                    :showFilterMenu="false"
                    style="width: 10%"
                    class="justify-items-center"
                >
                    <!-- Body Badge -->
                    <template #body="{ data }">
                        <span
                            :class="getStatusClass(data.status.status)"
                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                        >
                            {{ statusLabel(data.status.status) }}
                        </span>
                    </template>

                    <!-- Filter -->
                    <template #filter="{ filterModel, filterCallback }">
                        <div class="flex justify-center">
                            <Select
                                v-model="filterModel.value"
                                :options="statuses"
                                placeholder="Select status"
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
                                    <span v-if="!value || value === 'All'" class="w-full text-center text-gray-500"> Select status </span>
                                    <span
                                        v-else
                                        :class="getStatusClass(value)"
                                        class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                    >
                                        {{ statusLabel(value) }}
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
                                        :class="getStatusClass(option)"
                                        class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                    >
                                        {{ statusLabel(option) }}
                                    </span>
                                </template>
                            </Select>
                        </div>
                    </template>
                </Column>

                <Column header="Action" style="width: 30%" class="justify-items-center">
                    <template #body="{ data }">
                        <div class="flex gap-4">
                            <button
                                class="inline-flex cursor-pointer items-center gap-1 rounded bg-blue-400 px-3 py-1 text-xs font-semibold text-black hover:bg-blue-600 hover:text-white"
                                @click="viewRequest(data.id)"
                            >
                                <i class="pi pi-eye" /> View
                            </button>

                            <!-- Approve Reject by Dept Head / Div Head-->
                            <template v-if="auth?.user?.permissions?.includes('Approve') || auth?.user?.permissions?.includes('Reject')">
                                <button
                                    v-if="data.status?.status === 'wait_for_review'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-green-400 px-3 py-1 text-xs font-semibold text-black hover:bg-green-600 hover:text-white"
                                    @click="acceptRequest(data.id)"
                                >
                                    <i class="pi pi-check" /> Accept
                                </button>

                                <button
                                    v-if="data.status?.status === 'wait_for_review'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-red-400 px-3 py-1 text-xs font-semibold text-black hover:bg-red-600 hover:text-white"
                                    @click="rejectRequest(data.id)"
                                >
                                    <i class="pi pi-times" /> Reject
                                </button>
                            </template>

                            <!-- Execute by Admin and User Acceptance -->
                            <template v-if="auth?.user?.roles.includes('Admin')">
                                <button
                                    v-if="data.status?.status === 'accepted' || data.status?.status === 'revision'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="executeRequest(data.id)"
                                >
                                    <i class="pi pi-cog pi-spin" /> Execute
                                </button>

                                <button
                                    v-if="data.status?.status === 'in_progress' && auth?.user?.roles?.includes('Admin')"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="userReview(data.id)"
                                >
                                    <i class="pi pi-check-circle" /> Review
                                </button>
                            </template>

                            <!-- User Review and determined Finish or not yet -->
                            <template v-if="auth?.user.npk === data.npk">
                                <button
                                    v-if="data.status?.status === 'user_acceptance'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="user_acceptance(data.id, 'uat')"
                                >
                                    <i class="pi pi-cog pi-spin" /> Finish
                                </button>
                                <button
                                    v-if="data.status?.status === 'user_acceptance'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-teal-400 px-3 py-1 text-xs font-semibold text-black hover:bg-teal-600 hover:text-white"
                                    @click="revision(data.id, 'rev')"
                                >
                                    <i class="pi pi-pen-to-square" /> Revision
                                </button>
                            </template>

                            <!-- <div class="flex h-6 w-6 items-center justify-center">
                                <i
                                    class="pi pi-spin pi-eye"
                                    style="color: yellow"
                                    v-if="data.status?.status === 'wait_for_review' && !auth?.user?.permissions?.includes('Approve')"
                                ></i>
                                <i
                                    class="pi pi-spin pi-spinner"
                                    style="color: cyan"
                                    v-if="data.status?.status === 'in_progress' && !auth?.user?.permissions?.includes('Execute')"
                                ></i>
                                <i class="pi pi-spin pi-hourglass" style="color: cyan" v-if="data.status === 'accepted'"></i>
                                <i class="pi pi-check-circle" style="color: green" v-if="data.status === 'finish'"></i>
                                <i class="pi pi-times-circle" style="color: red" v-if="data.status === 'rejected'"></i>
                            </div> -->
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>

        <!-- Create Form Section -->
        <section ref="createFormSection" v-if="auth?.user?.role !== 'Admin'" class="m-6 scroll-mt-16">
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-start text-3xl font-bold text-gray-900 dark:text-white">Create Request</h2>
                    <p class="text-start text-gray-600 dark:text-gray-400">Fill the form below to request a service.</p>
                </div>
                <Button
                    label=" See Data"
                    severity="warn"
                    icon="pi pi-eye"
                    variant="outlined"
                    @click="scrollTodataSection"
                    unstyled
                    class="w-full cursor-pointer rounded-xl bg-emerald-400 px-4 py-2 text-center font-bold text-slate-900 hover:bg-emerald-700 sm:w-auto"
                />
            </div>

            <div class="mt-4 mb-8">
                <div class="relative mb-6 text-center">
                    <h1 class="relative z-1 inline-block bg-white px-4 text-2xl font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        Form Request for Service
                    </h1>
                    <hr class="absolute top-1/2 left-0 z-0 w-full -translate-y-1/2 border-gray-300 dark:border-gray-600" />
                </div>
            </div>

            <div class="p-6">
                <div class="mx-auto max-w-2xl">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="mb-2 text-2xl font-bold text-foreground">Request for Service</h1>
                        <p class="text-muted-foreground">Fill this form below to request a service</p>
                    </div>

                    <!-- Form -->
                    <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                        <form @submit.prevent="submitForm" class="space-y-6" enctype="multipart/form-data">
                            <!-- Nama (Read-only) -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="username" class="font-semibold text-secondary">Username :</label>
                                    <InputText id="username" v-model="form.name" disabled />
                                </div>
                            </div>

                            <!-- NPK (Read-only) -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="npk" class="font-semibold text-secondary">NPK :</label>
                                    <InputText id="npk" v-model="form.npk" disabled />
                                </div>
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="priority" class="font-semibold text-secondary">Priority :</label>
                                    <Select
                                        v-model="selectedPriority"
                                        :options="prioritiesOption"
                                        optionLabel="name"
                                        optionValue="code"
                                        placeholder="Select priority"
                                        class="w-full bg-background text-card-foreground"
                                    />
                                </div>
                                <div v-if="form.errors.priority" class="text-sm text-red-500">{{ form.errors.priority }}</div>
                            </div>

                            <!-- Tanggal Input (Read-only) -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="date" class="font-semibold text-secondary">Date :</label>
                                    <InputText id="date" v-model="form.created_at" disabled />
                                </div>
                            </div>

                            <!-- Detail Keperluan -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="description" class="font-semibold text-secondary">Requirement Details :</label>
                                    <Textarea
                                        v-model="form.description"
                                        rows="5"
                                        class="w-full resize-none rounded-md border border-border bg-background px-3 py-2 text-card-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                        :class="{ 'border-red-500': form.errors.description }"
                                        placeholder="Describe the details of your service needs or requests..."
                                        required
                                    ></Textarea>
                                </div>
                                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                            </div>

                            <!-- Lampiran -->
                            <div class="space-y-2">
                                <div class="flex flex-col gap-2">
                                    <label for="attachment" class="font-semibold text-secondary">Attachment :</label>
                                    <div class="flex items-center space-x-3">
                                        <input
                                            type="file"
                                            ref="fileInput"
                                            @change="handleFileSelect"
                                            class="hidden"
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx,.xls"
                                        />
                                        <button
                                            type="button"
                                            @click="fileInput?.click()"
                                            class="rounded-md border border-border bg-secondary px-4 py-2 text-secondary-foreground transition-colors hover:bg-secondary/80"
                                        >
                                            Choose File
                                        </button>
                                        <span v-if="selectedFileName" class="text-sm text-primary">{{ selectedFileName }}</span>
                                        <span v-else class="text-sm text-primary">No files selected</span>
                                    </div>
                                    <p class="text-xs text-primary">Supported formats: PDF, DOC, DOCX, JPG, PNG, XLSX, XLS (Max: 10MB)</p>
                                </div>
                                <div v-if="form.errors.attachment" class="text-sm text-red-500">{{ form.errors.attachment }}</div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 border-t border-border pt-4">
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="rounded-md border border-border px-6 py-2 text-primary transition-colors hover:bg-muted hover:text-foreground"
                                >
                                    Reset
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-primary px-6 py-2 text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Submitting...</span>
                                    <span v-else>Submit Request</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Status Messages -->
                    <div v-if="form.recentlySuccessful" class="mt-4 rounded-md border border-green-200 bg-green-50 p-4">
                        <p class="text-green-800">Request submitted successfully!</p>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
