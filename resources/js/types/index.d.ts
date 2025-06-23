import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

// index.d.ts

export interface NavItem {
    key?: string; // 👈 ditambahkan agar bisa dipakai PrimeVue Tree
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    onlyFor?: string[]; // 👈 role access
    children?: NavItem[]; // 👈 submenu
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    npk: string;
    avatar?: string;
    created_at: string;
    updated_at: string;

    role?: string;
}

export interface Role {
    id: number | null;
    name: string;
    permissions: string[];
}

export interface Permission {
    id: number | null;
    name: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
