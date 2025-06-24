// nav.ts
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
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
        href: 'admin.index',
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
        title: 'Bill of Material',
        href: 'rfs.index',
        icon: 'pi-wrench',
        children: [
            {
                key: 'bomMaster',
                title: 'Master Data',
                icon: 'pi-database',
                href: 'rfs.index',
            },
            {
                key: 'bomReport',
                title: 'Report',
                icon: 'pi-clipboard',
                href: 'rfs.index',
            },
        ],
    },
];
