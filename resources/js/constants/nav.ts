// nav.ts
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    // Dashboard
    {
        key: 'dashboard',
        title: 'Home',
        href: 'dashboard',
        icon: 'pi-warehouse',
        status: 'open',
    },

    // RFS
    {
        key: 'rfs',
        title: 'Request for Service',
        href: 'rfs.index',
        icon: 'pi-question-circle',
        status: 'open',
    },

    // Admin
    {
        key: 'admin',
        title: 'Administrator',
        href: 'admin.index',
        icon: 'pi-prime',
        onlyFor: ['Admin'],
        status: 'open',
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
                status: 'open', // Add the status property here
            },
            {
                key: 'bomMaster',
                title: 'BOM Master Data',
                icon: 'pi-file-import',
                href: 'bom.master',
                status: 'open', // Add the status property here
            },
            {
                key: 'bomMasterStandard',
                title: 'Standard Master Data',
                icon: 'pi-file-import',
                href: 'sc.master',
                status: 'open', // Add the status property here
            },
            {
                key: 'bomMasterActual',
                title: 'Actual Master Data',
                icon: 'pi-file-import',
                href: 'ac.master',
                status: 'open', // Add the status property here
            },
        ],
    },

    // Production Cost Calculation

    // {
    //     key: 'wd',
    //     title: 'Production Cost (Soon)',
    //     href: 'pc.report',
    //     icon: 'pi-wrench',
    //     status: 'close',
    // },

    // Process Cost Report
    {
        key: 'spc',
        title: 'Standard Process Cost Calculation',
        href: 'pc.report',
        icon: 'pi-wrench',
        status: 'open',
    },

    // Actual Process Cost
    {
        key: 'apc',
        title: 'Actual Process Cost (Soon)',
        href: 'pc.report',
        icon: 'pi-wrench',
        status: 'close',
    },

    // BOM Report
    {
        key: 'bom',
        title: 'Bill of Material Explode',
        href: 'bom.report', // Ubah jadi menu sendiri
        icon: 'pi-receipt',
        status: 'open',
    },

    // BOM Report

    {
        key: 'sc',
        title: 'Standard Cost Calculation',
        href: 'sc.report',
        icon: 'pi-dollar',
        status: 'open',
    },

    {
        key: 'ac',
        title: 'Actual Cost Calculation',
        href: 'ac.report', // Ubah jadi menu sendiri
        icon: 'pi-dollar',
        status: 'open',
    },

    {
        key: 'dc',
        title: 'Difference Cost Calculation',
        href: 'dc.report', // Ubah jadi menu sendiri
        icon: 'pi-dollar',
        status: 'open',
    },
];
