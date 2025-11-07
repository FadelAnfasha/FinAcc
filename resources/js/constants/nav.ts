// nav.ts
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    // Dashboard
    {
        key: 'dashboard',
        title: 'Home',
        href: 'dashboard',
        icon: 'pi-warehouse',
    },

    // RFS
    {
        key: 'rfs',
        title: 'Request for Service',
        href: 'rfs.index',
        icon: 'pi-question-circle',
    },

    // Admin
    {
        key: 'admin',
        title: 'Administrator',
        href: 'admin.index',
        icon: 'pi-prime',
        onlyFor: ['Admin'],
    },

    // All Master Data
    {
        key: 'masterData',
        title: 'Master Data',
        href: '#',
        icon: 'pi-database',
        children: [
            {
                key: 'masterData',
                title: 'Process Cost Master Data',
                icon: 'pi-file-import',
                href: 'pc.master',
            },
            {
                key: 'bomMaster',
                title: 'BOM Master Data',
                icon: 'pi-file-import',
                href: 'bom.master',
            },
            {
                key: 'bomMasterStandard',
                title: 'Standard Master Data',
                icon: 'pi-file-import',
                href: 'sc.master',
            },
            {
                key: 'bomMasterActual',
                title: 'Actual Master Data',
                icon: 'pi-file-import',
                href: 'ac.master',
            },
        ],
    },

    // Process Cost Report
    {
        key: 'pc',
        title: 'Process Cost Calculation',
        href: 'pc.report',
        icon: 'pi-wrench',
    },

    // BOM Report
    {
        key: 'bom',
        title: 'Bill of Material Explode',
        href: 'bom.report', // Ubah jadi menu sendiri
        icon: 'pi-receipt',
    },

    // BOM Report

    {
        key: 'sc',
        title: 'Standard Cost Calculation',
        href: 'sc.report',
        icon: 'pi-dollar',
    },

    {
        key: 'ac',
        title: 'Actual Cost Calculation',
        href: 'ac.report', // Ubah jadi menu sendiri
        icon: 'pi-dollar',
    },

    {
        key: 'dc',
        title: 'Difference Cost Calculation',
        href: 'dc.report', // Ubah jadi menu sendiri
        icon: 'pi-dollar',
    },
];
