<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { Permission, Role, User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

const toast = useToast();
const page = usePage();
const props = defineProps({
    services: Array,
    auth: Object,
});
// Dialog states

// ===========================
// ==== Methods for Roles ====
// ===========================
const roles = ref<Role[]>([...(page.props.roles as Role[])]);
const roleDialog = ref(false);

const roleForm = ref<{
    id: number | null;
    name: string;
    permissions: any[];
}>({
    id: null,
    name: '',
    permissions: [],
});

const openRoleDialog = (role: Role | null = null) => {
    if (role) {
        roleForm.value = {
            id: role.id,
            name: role.name,
            permissions: [...role.permissions],
        };
    } else {
        roleForm.value = {
            id: null,
            name: '',
            permissions: [],
        };
    }
    roleDialog.value = true;
};

const saveRole = () => {
    const roleName = roleForm.value.name.trim();

    if (!roleName) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Role name is required',
            life: 3000,
        });
        return;
    }

    const isUpdate = !!roleForm.value.id;
    const routeUrl = isUpdate ? `/admin/roles/${roleForm.value.id}` : '/admin/roles';

    const method = isUpdate ? 'put' : 'post';

    router.visit(routeUrl, {
        method: method,
        data: {
            name: roleForm.value.name,
            permissions: roleForm.value.permissions,
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: isUpdate ? 'Updated' : 'Created',
                detail: `Role ${isUpdate ? 'updated' : 'created'} successfully`,
                life: 3000,
            });
            roleDialog.value = false; // Tutup dialog
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: Object.values(errors).join(', '),
                life: 4000,
            });
        },
    });
};

const deleteRole = (role: Role) => {
    if (!confirm(`Are you sure you want to delete role "${role.name}"?`)) return;

    router.visit(`/admin/roles/${role.id}`, {
        method: 'delete',
        onSuccess: () => {
            // Hapus dari UI
            roles.value = roles.value.filter((r) => r.id !== role.id);
            toast.add({
                severity: 'success',
                summary: 'Deleted',
                detail: 'Role deleted successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to delete role',
                life: 4000,
            });
            console.error(errors);
        },
    });
};

// =================================
// ==== Methods for Permissions ====
// =================================
const permissions = ref<Permission[]>([...(page.props.permissions as Permission[])]);
const permissionDialog = ref(false);
const permissionForm = ref<{
    id: number | null;
    name: string;
}>({
    id: null,
    name: '',
});

const openPermissionDialog = (permission: Permission | null = null) => {
    if (permission) {
        permissionForm.value = {
            id: permission.id,
            name: permission.name,
        };
    } else {
        permissionForm.value = {
            id: null,
            name: '',
        };
    }
    permissionDialog.value = true;
};

const savePermission = () => {
    const permissionName = permissionForm.value.name.trim();

    if (!permissionName) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Permission name is required', life: 3000 });
        return;
    }

    const isUpdate = !!permissionForm.value.id;
    const routeUrl = isUpdate ? `/admin/permissions/${permissionForm.value.id}` : '/admin/permissions';

    const method = isUpdate ? 'put' : 'post';

    router.visit(routeUrl, {
        method: method,
        data: {
            name: permissionForm.value.name,
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: isUpdate ? 'Updated' : 'Created',
                detail: `Permission ${isUpdate ? 'updated' : 'created'} successfully`,
                life: 3000,
            });
            roleDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'error',
                detail: Object.values(errors).join(', '),
                life: 4000,
            });
        },
    });
};

const deletePermission = (permission: Permission) => {
    if (!confirm(`Are you sure you want to delete permission "${permission.name}"?`)) return;

    router.visit(`/admin/permissions/${permission.id}`, {
        method: 'delete',
        onSuccess: () => {
            // Hapus dari UI
            permissions.value = permissions.value.filter((p) => p.id !== permission.id);
            toast.add({
                severity: 'success',
                summary: 'Deleted',
                detail: 'Permission deleted successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to delete permission',
                life: 4000,
            });
        },
    });
};

// =================================
// ==== Methods for Assign Role ====
// =================================
const users = ref<User[]>([...(page.props.users as User[])]);
const userRoleDialog = ref(false);

const userRoleForm = ref({
    userId: null,
    roleId: null,
});

const selectedUser = ref<User | null>(null);
const selectedRole = ref<Role | null>(null);

// Methods for User Role Assignment
const openUserRoleDialog = (user: User | null = null) => {
    selectedUser.value = user;
    selectedRole.value = user ? (roles.value.find((r) => r.name === user.role) ?? null) : null;
    userRoleDialog.value = true;
};

const assignRole = () => {
    if (!selectedRole.value || !selectedUser.value) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'User and Role must be selected',
            life: 3000,
        });
        return;
    }

    router.visit(`/admin/users/${selectedUser.value.id}/assign-role`, {
        method: 'post',
        data: {
            user_id: selectedUser.value.id,
            role_id: selectedRole.value.id,
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Role assigned successfully',
                life: 3000,
            });
            userRoleDialog.value = false;
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: Object.values(errors).join(', '),
                life: 4000,
            });
        },
    });
};

const getRoleTagClass = (roleName: string) => {
    const base = 'inline-block text-center w-30 text-white text-sm font-medium px-3 py-1 rounded-full hover:text-black cursor-pointer';

    switch (roleName) {
        case 'Admin':
            return `${base} bg-slate-500 hover:bg-slate-700`;
        case 'Superior':
            return `${base} bg-teal-600 hover:bg-teal-800`;
        case 'User':
            return `${base} bg-amber-500 hover:bg-amber-700`;
        default:
            return `${base} bg-gray-500 hover:bg-gray-700`;
    }
};

// =================================
// ==== Methods for Regist User ====
// =================================

const registUser = () => {
    const name = (document.getElementById('name') as HTMLInputElement)?.value.trim();
    const npk = (document.getElementById('npk') as HTMLInputElement)?.value.trim();
    const password = (document.getElementById('password') as HTMLInputElement)?.value;
    const confirmPassword = (document.getElementById('confirm_password') as HTMLInputElement)?.value;

    if (!name || !npk || !password || !confirmPassword) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Please fill out all fields',
            life: 3000,
        });
        return;
    }

    if (password !== confirmPassword) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Passwords do not match',
            life: 3000,
        });
        return;
    }

    router.visit('/admin/register', {
        method: 'post',
        data: {
            name: name,
            npk: npk,
            password: password,
            password_confirmation: confirmPassword,
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'User registered successfully',
                life: 3000,
            });
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: Object.values(errors).join(', '),
                life: 4000,
            });
        },
    });
};
</script>

<template>
    <Head title="Administrator" />
    <AppLayout>
        <div class="p-6">
            <div class="mb-4">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Administrator Panel</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage roles, permissions, user registration & role assignments</p>
            </div>

            <Tabs value="0">
                <TabList>
                    <Tab value="0">Create User</Tab>
                    <Tab v-if="auth?.user?.role === 'Admin'" value="1">Roles Management</Tab>
                    <Tab v-if="auth?.user?.role === 'Admin'" value="2">Permission Management</Tab>
                    <Tab v-if="auth?.user?.role === 'Admin'" value="3">User Role Assignment</Tab>
                </TabList>
                <TabPanels>
                    <!-- Roles Tab -->
                    <TabPanel v-if="auth?.user?.role === 'Admin'" header="Roles Management" value="1">
                        <Card>
                            <template #header>
                                <div class="flex items-center justify-between p-4">
                                    <h2 class="text-xl font-semibold">Roles</h2>
                                    <Button label="Add Role" icon="pi pi-plus" @click="openRoleDialog()" class="p-button-success" />
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="roles" responsiveLayout="scroll">
                                    <Column field="name" header="Role Name" sortable></Column>
                                    <Column header="Permissions">
                                        <template #body="slotProps">
                                            <div class="flex flex-wrap gap-1">
                                                <Chip
                                                    v-for="permission in slotProps.data.permissions"
                                                    :key="permission"
                                                    :label="permission"
                                                    class="text-xs"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-text p-button-warning"
                                                    @click="openRoleDialog(slotProps.data)"
                                                    v-tooltip="'Edit'"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    class="p-button-rounded p-button-text p-button-danger"
                                                    @click="deleteRole(slotProps.data)"
                                                    v-tooltip="'Delete'"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- Permissions Management -->
                    <TabPanel v-if="auth?.user?.role === 'Admin'" header="Permissions Management" value="2">
                        <Card>
                            <template #header>
                                <div class="flex items-center justify-between p-4">
                                    <h2 class="text-xl font-semibold">Permissions</h2>
                                    <Button label="Add Permission" icon="pi pi-plus" @click="openPermissionDialog()" class="p-button-success" />
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="permissions" responsiveLayout="scroll">
                                    <Column field="name" header="Permission Name" sortable></Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <div class="flex gap-2">
                                                <Button
                                                    icon="pi pi-pencil"
                                                    class="p-button-rounded p-button-text p-button-warning"
                                                    @click="openPermissionDialog(slotProps.data)"
                                                    v-tooltip="'Edit'"
                                                />
                                                <Button
                                                    icon="pi pi-trash"
                                                    class="p-button-rounded p-button-text p-button-danger"
                                                    @click="deletePermission(slotProps.data)"
                                                    v-tooltip="'Delete'"
                                                />
                                            </div>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- User Role Assignment Tab -->
                    <TabPanel v-if="auth?.user?.role === 'Admin'" header="User Role Assignment" value="3">
                        <Card>
                            <template #header>
                                <div class="p-4">
                                    <h2 class="text-xl font-semibold">Users & Roles</h2>
                                    <p class="text-gray-600 dark:text-gray-400">Assign roles to users</p>
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="users" responsiveLayout="scroll">
                                    <Column field="name" header="Name" sortable></Column>
                                    <Column field="npk" header="NPK" sortable></Column>
                                    <Column header="Current Role">
                                        <template #body="slotProps">
                                            <Tag :value="slotProps.data.role" :class="getRoleTagClass(slotProps.data.role)" unstyled />
                                        </template>
                                    </Column>
                                    <Column header="Actions">
                                        <template #body="slotProps">
                                            <Button
                                                label="Assign Role"
                                                icon="pi pi-user-edit"
                                                class="p-button-rounded p-button-text"
                                                @click="openUserRoleDialog(slotProps.data)"
                                            />
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>
                    </TabPanel>

                    <!-- Register new user Tab -->
                    <TabPanel v-if="auth?.user?.role === 'Admin' || auth?.user?.role === 'Superior'" header="Create User" value="0">
                        <Card>
                            <template #header>
                                <div class="p-4">
                                    <h2 class="text-xl font-semibold">Register User</h2>
                                    <p class="text-gray-600 dark:text-gray-400">Add new user account</p>
                                </div>
                            </template>
                            <template #content>
                                <form>
                                    <div class="mb-5 flex w-full flex-row gap-4">
                                        <!-- Input kiri -->
                                        <div class="flex w-1/2 flex-col gap-1">
                                            <label for="name">Name :</label>
                                            <InputText id="name" name="name" type="text" placeholder="Type here..." />
                                        </div>

                                        <!-- Input kanan -->
                                        <div class="flex w-1/2 flex-col gap-1">
                                            <label for="npk">NPK:</label>
                                            <InputText id="npk" name="npk" type="text" placeholder="Type here..." />
                                        </div>
                                    </div>
                                    <div class="mb-5 flex w-full flex-row gap-4">
                                        <!-- Input kiri -->
                                        <div class="flex w-1/2 flex-col gap-1">
                                            <label for="password">Password :</label>
                                            <InputText id="password" name="password" type="password" placeholder="Type here..." />
                                        </div>

                                        <!-- Input kanan -->
                                        <div class="flex w-1/2 flex-col gap-1">
                                            <label for="confirm_password">Confirm Password :</label>
                                            <InputText id="confirm_password" name="confirm_password" type="password" placeholder="Type here..." />
                                        </div>
                                    </div>
                                    <Button label="Save" icon="pi pi-check" severity="success" @click="registUser" />
                                </form>
                            </template>
                        </Card>
                    </TabPanel>
                </TabPanels>
            </Tabs>

            <!-- Role Dialog -->
            <Dialog v-model:visible="roleDialog" :style="{ width: '450px' }" header="Role Details" :modal="true">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="roleName" class="mb-2 block text-sm font-medium">Role Name</label>
                        <InputText id="roleName" v-model="roleForm.name" class="w-full" placeholder="Enter role name" />
                    </div>
                    <div>
                        <label for="rolePermissions" class="mb-2 block text-sm font-medium">Permissions</label>
                        <MultiSelect
                            v-model="roleForm.permissions"
                            :options="permissions as any[]"
                            optionLabel="name"
                            optionValue="name"
                            placeholder="Select permissions"
                            class="w-full"
                        />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="roleDialog = false" />
                    <Button label="Save" icon="pi pi-check" class="p-button-text" @click="saveRole" />
                </template>
            </Dialog>

            <!-- Permission Dialog -->
            <Dialog v-model:visible="permissionDialog" :style="{ width: '450px' }" header="Permission Details" :modal="true">
                <div class="flex flex-col gap-4">
                    <div>
                        <label for="permissionName" class="mb-2 block text-sm font-medium">Permission Name</label>
                        <InputText id="permissionName" v-model="permissionForm.name" class="w-full" placeholder="Enter permission name" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="permissionDialog = false" />
                    <Button label="Save" icon="pi pi-check" class="p-button-text" @click="savePermission" />
                </template>
            </Dialog>

            <!-- User Role Assignment Dialog -->
            <Dialog v-model:visible="userRoleDialog" :style="{ width: '450px' }" header="Assign Role" :modal="true">
                <div class="flex flex-col gap-4" v-if="selectedUser">
                    <div>
                        <label class="mb-2 block text-sm font-medium">User</label>
                        <p class="text-lg font-semibold">{{ selectedUser.name }} ({{ selectedUser.npk }})</p>
                    </div>
                    <div>
                        <label for="userRole" class="mb-2 block text-sm font-medium">Select Role</label>
                        <Select v-model="selectedRole" :options="roles" optionLabel="name" placeholder="Select a role" class="w-full" />
                    </div>
                </div>
                <template #footer>
                    <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="userRoleDialog = false" />
                    <Button label="Assign" icon="pi pi-check" class="p-button-text" @click="assignRole" />
                </template>
            </Dialog>

            <Toast />
        </div>
    </AppLayout>
</template>
