// resources/js/constants/nav.ts
import type { NavItem } from '@/types';
import { CircleDollarSign, HandHelping, LifeBuoy, UsersRound } from 'lucide-vue-next';

export const mainNavItems: NavItem[] = [
    {
        title: 'Request for Service',
        href: 'rfs.index',
        icon: HandHelping,
    },
    {
        title: 'Administrator',
        href: 'admin.index',
        icon: UsersRound,
        onlyFor: ['Admin'], // 👈 tambahan
    },
    {
        title: 'Process Cost',
        href: 'pc.index',
        icon: CircleDollarSign,
    },
    {
        title: 'Bill of Material',
        href: 'bom.index',
        icon: LifeBuoy,
    },
];
