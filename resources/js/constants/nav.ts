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
        onlyFor: ['Admin'],
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
                onlyFor: ['Process Cost - Full Access'],
            },
            {
                key: 'pcReport',
                title: 'Report',
                icon: 'pi-clipboard',
                href: 'pc.report',
                onlyFor: ['Process Cost - View', 'Process Cost - Full Access'],
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
                key: 'bomMasterStandard',
                title: 'Standard Master Data',
                icon: 'pi-database',
                href: 'bom.masterStandard',
                onlyFor: ['BOM - Full Access'],
            },
            {
                key: 'bomMasterActual',
                title: 'Actual Master Data',
                icon: 'pi-database',
                href: 'bom.masterActual',
                onlyFor: ['BOM - Full Access'],
            },

            {
                key: 'bomMaster',
                title: 'BOM Master Data',
                icon: 'pi-database',
                href: 'bom.master',
                onlyFor: ['BOM - Full Access'],
            },
            {
                key: 'bomReport',
                title: 'Report',
                icon: 'pi-clipboard',
                href: 'bom.report',
                onlyFor: ['BOM - View', 'BOM - Full Access'],
            },
        ],
    },
];
