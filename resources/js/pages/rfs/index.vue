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
import { computed, ref, Ref, watchEffect } from 'vue';

const page = usePage();
const toast = useToast();

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
    priority: Priority;
    created_at: string;
    description: string;
    status: Status;
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
    router.post(`/rfs/${id}/execute`);
};

const showDialog: Ref<boolean> = ref(false);
const userName = computed(() => page.props.auth?.user?.name ?? '');
const impactValue = ref<string>('');
const selectedId = ref<number | null>(null);

const user_acceptance = (id: number) => {
    selectedId.value = id;
    showDialog.value = true;
};

const submitAcceptance = () => {
    if (selectedId.value !== null) {
        // Objek form untuk dikirimkan
        const form = {
            impact: impactValue.value,
        };

        // Mengirim permintaan POST dengan data form
        router.post(`/rfs/${selectedId.value}/uat`, form, {
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

const closeDialog = () => {
    showDialog.value = false;
    impactValue.value = '';
};

const finishRequest = (id: number) => {
    router.post(`/rfs/${id}/finish`);
};

const props = defineProps({
    services: Array,
    auth: Object,
});

const filters = ref({
    name: { value: null, matchMode: 'contains' },
    'priority.priority': { value: null, matchMode: 'equals' },
    created_at: { value: null, matchMode: 'contains' },
    description: { value: null, matchMode: 'contains' },
    'status.status': { value: null, matchMode: 'equals' },
    updated_at: { value: null, matchMode: 'contains' },
});

// Data with proper typing and added status field
const items = computed(() => page.props.services as RequestItem[]);
const priorities = ['All', 'low', 'medium', 'high', 'urgent'];
const statuses = ['All', 'wait_for_review', 'accepted', 'in_progress', 'finish', 'rejected'];

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
            return 'bg-yellow-400 text-yellow-900';
        case 'accepted':
            return 'bg-sky-400 text-sky-900';
        case 'in_progress':
            return 'bg-indigo-400 text-indigo-900';
        case 'user_acceptance':
            return 'bg-amber-400 text-amber-900';
        case 'finish':
            return 'bg-green-500 text-green-900';
        case 'rejected':
            return 'bg-rose-500 text-rose-900';
        default:
            return 'bg-gray-300 text-gray-800';
    }
};

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        wait_for_review: 'Wait for Review',
        accepted: 'Accepted',
        user_acceptance: 'User Acceptance',
        in_progress: 'In Progress',
        finish: 'Finish',
        rejected: 'Rejected',
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

// Form data with proper typing
const form = useForm({
    name: (page.props.auth.user as UserWithNPK)?.name || '',
    npk: (page.props.auth.user as UserWithNPK)?.npk || '',
    priority: '2',
    created_at: new Date().toISOString().split('T')[0],
    description: '',
    status: '1',
    attachment: null as File | null,
    updated_at: new Date().toISOString().split('T')[0],
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref('');
const loading = ref(false);

// Ref for create form section
const createFormSection = ref<HTMLElement | null>(null);
const dataSection = ref<HTMLElement | null>(null);

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
    form.post('rfs', {
        forceFormData: true, // WAJIB untuk upload file
        onStart: () => {
            form.clearErrors();
        },
        onSuccess: () => {
            form.reset();
            selectedFileName.value = '';
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
};
</script>

<template>
    <Head title="RFS" />
    <AppLayout>
        <div class="mt-4 mb-8">
            <Dialog v-model:visible="showDialog" header="Review Confirmation" modal class="w-[30rem]" :closable="false">
                <div class="space-y-4">
                    <p>
                        Hi <span class="text-red-400">{{ userName }}</span
                        >,
                    </p>
                    <p>
                        Please describe the impact of
                        <strong class="text-green-500">Imrpovement/Request</strong>?
                    </p>
                    <Textarea v-model="impactValue" autoResize rows="3" cols="55" class="mb-4" placeholder="Impact" required />
                </div>
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
                        class="w-28 cursor-pointer rounded-xl bg-green-500 px-4 py-2 text-center font-bold text-slate-900 hover:bg-green-700"
                        @click="submitAcceptance"
                    />
                </div>
            </Dialog>
        </div>

        <!-- Data Table Section -->
        <section ref="dataSection" class="mb-4 scroll-mt-8 p-6">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex flex-col">
                    <h2 class="text-start text-3xl font-bold text-gray-900 dark:text-white">Request Data</h2>
                    <p class="text-start text-gray-600 dark:text-gray-400">Display all request, create request, and approving request.</p>
                </div>
                <Button
                    label=" Create"
                    severity="info"
                    v-if="auth?.user?.role !== 'Admin'"
                    unstyled
                    @click="scrollToCreateForm"
                    icon="pi pi-plus"
                    class="rounded-xl border border-teal-500 bg-white px-3 py-1 text-teal-500 hover:border-white hover:bg-teal-500 hover:text-white"
                />
            </div>

            <DataTable
                v-model:filters="filters"
                :value="items"
                :customSort="true"
                removableSort
                paginator
                :rows="10"
                dataKey="id"
                filterDisplay="row"
                :loading="loading"
                :globalFilterFields="['name', 'priority', 'created_at', 'description', 'status', 'updated_at']"
                tableStyle="min-width: 50rem"
                class="text-sm"
                sortField="created_at"
                :sortOrder="-1"
            >
                <Column field="created_at" header="Request Date" :showFilterMenu="false" sortable style="width: 20%">
                    <template #filter="{ filterModel, filterCallback }">
                        <DatePicker
                            :modelValue="filterModel.value ? new Date(filterModel.value + 'T00:00:00') : null"
                            @update:modelValue="
                                // PERUBAHAN KRITIS: Perluas tipe 'val' untuk mencakup semua kemungkinan yang disebutkan error
                                (val: Date | Date[] | (Date | null)[] | null | undefined) => {
                                    let selectedDate: Date | null = null;

                                    if (val instanceof Date) {
                                        selectedDate = val;
                                    } else if (Array.isArray(val) && val.length > 0) {
                                        // Jika val adalah array, ambil elemen pertama jika itu Date
                                        // Ini mengasumsikan Anda hanya tertarik pada tanggal pertama untuk filter tunggal
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

                <Column field="name" header="Name" :showFilterMenu="false" sortable style="width: 20%">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-model="filterModel.value" @input="filterCallback()" placeholder="Search name" />
                    </template>
                </Column>

                <Column
                    field="priority.priority"
                    header="Priority"
                    :sortable="true"
                    :showFilterMenu="false"
                    style="width: 20%"
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
                        <div class="max-w-[200px] truncate" :title="data.description" v-tooltip.top="data.description">
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
                    style="width: 20%"
                    class="justify-items-center"
                >
                    <!-- Body Badge -->
                    <template #body="{ data }">
                        <span
                            :class="getStatusClass(data.status.status)"
                            class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                        >
                            {{ capitalize(data.status.status) }}
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
                                        :class="getStatusClass(option)"
                                        class="inline-block w-full rounded-full px-2 py-1 text-center text-xs font-semibold"
                                    >
                                        {{ capitalize(option) }}
                                    </span>
                                </template>
                            </Select>
                        </div>
                    </template>
                </Column>

                <Column field="attachment" header="Attachment" :showFilterMenu="false" style="width: 20%">
                    <template #body="{ data }">
                        <a
                            v-if="data.attachment"
                            :href="`/storage/${data.attachment}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded bg-blue-500 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-600"
                        >
                            <i class="pi pi-external-link" /> View
                        </a>
                        <span v-else class="text-xs text-gray-400">No Attachment</span>
                    </template>
                </Column>

                <Column field="updated_at" header="Updated at" :showFilterMenu="false" sortable style="width: 20%">
                    <template #filter="{ filterModel, filterCallback }">
                        <DatePicker
                            :modelValue="filterModel.value ? new Date(filterModel.value + 'T00:00:00') : null"
                            @update:modelValue="
                                // PERUBAHAN KRITIS: Perluas tipe 'val' untuk mencakup semua kemungkinan yang disebutkan error
                                (val: Date | Date[] | (Date | null)[] | null | undefined) => {
                                    let selectedDate: Date | null = null;

                                    if (val instanceof Date) {
                                        selectedDate = val;
                                    } else if (Array.isArray(val) && val.length > 0) {
                                        // Jika val adalah array, ambil elemen pertama jika itu Date
                                        // Ini mengasumsikan Anda hanya tertarik pada tanggal pertama untuk filter tunggal
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

                <Column
                    header="Action"
                    style="width: 20%"
                    v-if="!auth?.user?.role?.includes('Director') || !auth?.user?.role?.includes('Deputy Division')"
                >
                    <template #body="{ data }">
                        <div class="flex gap-4">
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
                                    v-if="data.status?.status === 'accepted'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="executeRequest(data.id)"
                                >
                                    <i class="pi pi-cog pi-spin" /> Execute
                                </button>

                                <button
                                    v-if="data.status?.status === 'in_progress' && auth?.user?.roles?.includes('Admin')"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="user_acceptance(data.id)"
                                >
                                    <i class="pi pi-check-circle" /> User Review
                                </button>
                            </template>

                            <!-- User Review and determined Finish or not yet -->
                            <template v-if="auth?.user.npk === data.npk">
                                <button
                                    v-if="data.status?.status === 'user_acceptance'"
                                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-orange-400 px-3 py-1 text-xs font-semibold text-black hover:bg-orange-600 hover:text-white"
                                    @click="user_acceptance(data.id)"
                                >
                                    <i class="pi pi-cog pi-spin" /> Finish
                                </button>
                            </template>

                            <div class="flex h-6 w-6 items-center justify-center">
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
                            </div>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </section>

        <!-- Create Form Section -->
        <section ref="createFormSection" v-if="auth?.user?.role !== 'Admin'" class="mx-24 my-8 scroll-mt-8">
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-3xl font-semibold">Create Form</h2>
                <Button
                    label=" See Data"
                    severity="warn"
                    icon="pi pi-eye"
                    variant="outlined"
                    unstyled
                    @click="scrollTodataSection"
                    class="rounded-xl border border-amber-500 bg-white px-3 py-1 text-amber-500 hover:border-white hover:bg-amber-500 hover:text-white"
                />
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
                                <label class="text-sm font-medium text-card-foreground">Name :</label>
                                <input
                                    type="text"
                                    v-model="form.name"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- NPK (Read-only) -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">NPK :</label>
                                <input
                                    type="text"
                                    v-model="form.npk"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Priority :</label>
                                <select
                                    v-model="form.priority"
                                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-card-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.priority }"
                                >
                                    <option value="1">Low</option>
                                    <option value="2">Medium</option>
                                    <option value="3">High</option>
                                    <option value="4">Urgent</option>
                                </select>
                                <div v-if="form.errors.priority" class="text-sm text-red-500">{{ form.errors.priority }}</div>
                            </div>

                            <!-- Tanggal Input (Read-only) -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Submit Date :</label>
                                <input
                                    type="date"
                                    v-model="form.created_at"
                                    class="w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 py-2 text-card-foreground"
                                    readonly
                                    disabled
                                />
                            </div>

                            <!-- Detail Keperluan -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Requirement Details :</label>
                                <textarea
                                    v-model="form.description"
                                    rows="5"
                                    class="w-full resize-none rounded-md border border-border bg-background px-3 py-2 text-card-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.description }"
                                    placeholder="Describe the details of your service needs or requests..."
                                    required
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
                            </div>

                            <!-- Lampiran -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-card-foreground">Attachment :</label>
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
                                    <span v-if="selectedFileName" class="text-sm text-muted-foreground">{{ selectedFileName }}</span>
                                    <span v-else class="text-sm text-muted-foreground">No files selected</span>
                                </div>
                                <p class="text-xs text-muted-foreground">Supported formats: PDF, DOC, DOCX, JPG, PNG, XLSX, XLS (Max: 10MB)</p>
                                <div v-if="form.errors.attachment" class="text-sm text-red-500">{{ form.errors.attachment }}</div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 border-t border-border pt-4">
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="rounded-md border border-border px-6 py-2 text-muted-foreground transition-colors hover:bg-muted"
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
