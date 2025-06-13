// resources/js/constants/nav.ts
import { LifeBuoy, HandHelping } from 'lucide-vue-next';
import type { NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Bill of Material',
        href: 'bom.index',
        icon: LifeBuoy,
    },
    {
        title: 'Request for Service',
        href: 'rfs.index',
        icon: HandHelping,
    },
];
