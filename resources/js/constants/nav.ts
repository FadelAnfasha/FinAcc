// nav.ts
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        key: 'dashboard',
        title: 'Dashboard',
        href: 'dashboard',
        icon: 'pi-bars',
    },
    {
        key: 'rfs',
        title: 'Request for Service',
        href: 'rfs.index',
        icon: 'pi-question-circle',
    },
    {
        key: 'admin',
        title: 'Administrator',
        href: 'admin.index',
        icon: 'pi-prime',
        onlyFor: ['Admin', 'Superior'],
    },
    {
        key: 'pc',
        title: 'Process Cost',
        href: '#',
        icon: 'pi-dollar',
        children: [
            {
                key: 'pcMaster',
                title: 'Master Data',
                icon: 'pi-database',
                href: 'pc.master',
            },
            {
                key: 'pcReport',
                title: 'Report',
                icon: 'pi-clipboard',
                href: 'pc.report',
            },
        ],
    },
    {
        key: 'bom',
        title: 'Standard Cost',
        href: '#',
        icon: 'pi-wrench',
        children: [
            {
                key: 'bomMaster',
                title: 'Master Data',
                icon: 'pi-database',
                href: 'bom.master',
            },
            // {
            //     key: 'bomMaster',
            //     title: 'Standard Master Data',
            //     icon: 'pi-database',
            //     href: 'bom.masterStandard',
            // },
            // {
            //     key: 'bomMaster',
            //     title: 'Actual Master Data',
            //     icon: 'pi-database',
            //     href: 'bom.masterActual',
            // },
            // {
            //     key: 'bomMaster',
            //     title: 'BOM Master Data',
            //     icon: 'pi-database',
            //     href: 'bom.masterStandard',
            // },
            {
                key: 'bomReport',
                title: 'Report',
                icon: 'pi-clipboard',
                href: 'bom.report',
            },
        ],
    },
];
