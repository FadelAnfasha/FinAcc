// nav.ts
import type { NavItem } from '@/types';
import { CircleDollarSign, ClipboardList, Database, HandHelping, LifeBuoy, UsersRound } from 'lucide-vue-next';

export const mainNavItems: NavItem[] = [
    {
        key: 'rfs',
        title: 'Request for Service',
        href: 'rfs.index',
        icon: HandHelping,
    },
    {
        key: 'admin',
        title: 'Administrator',
        href: 'admin.index',
        icon: UsersRound,
        onlyFor: ['Admin'],
    },
    {
        key: 'pc',
        title: 'Process Cost',
        href: '#',
        icon: CircleDollarSign,
        children: [
            {
                key: 'pcMaster',
                title: 'Master Data',
                icon: Database,
                href: 'pc.master',
            },
            {
                key: 'pcReport',
                title: 'Report',
                icon: ClipboardList,
                href: 'pc.report',
            },
        ],
    },
    {
        key: 'bom',
        title: 'Bill of Material',
        href: 'rfs.index',
        icon: LifeBuoy,
        children: [
            {
                key: 'bomMaster',
                title: 'Master Data',
                icon: Database,
                href: 'rfs.index',
            },
            {
                key: 'bomReport',
                title: 'Report',
                icon: ClipboardList,
                href: 'rfs.index',
            },
        ],
    },
];
