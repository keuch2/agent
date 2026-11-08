import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CreditCard,
    LayoutGrid,
    Mail,
    MessageCircle,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as channelsIndex } from '@/routes/admin/channels';
import { index as plansIndex } from '@/routes/admin/plans';
import { edit as mailSettings } from '@/routes/admin/settings/mail';
import { index as tenantsIndex } from '@/routes/admin/tenants';
import { edit as whatsappEdit } from '@/routes/channels/whatsapp';
import { index as usersIndex } from '@/routes/users';
import type { Auth, NavItem } from '@/types';

function navigation(auth: Auth): {
    label: string;
    home: NavItem['href'];
    items: NavItem[];
} {
    if (auth.user.is_superadmin && !auth.tenant) {
        return {
            label: 'Mister Co.',
            home: tenantsIndex(),
            items: [
                { title: 'Empresas', href: tenantsIndex(), icon: Building2 },
                { title: 'Planes', href: plansIndex(), icon: CreditCard },
                {
                    title: 'Canales',
                    href: channelsIndex(),
                    icon: MessageCircle,
                },
                { title: 'Correo', href: mailSettings(), icon: Mail },
            ],
        };
    }

    const items: NavItem[] = [
        { title: 'Inicio', href: dashboard(), icon: LayoutGrid },
    ];

    if (auth.can.manageUsers) {
        items.push({ title: 'Usuarios', href: usersIndex(), icon: Users });
    }

    if (auth.can.manageChannels) {
        items.push({
            title: 'WhatsApp',
            href: whatsappEdit(),
            icon: MessageCircle,
        });
    }

    return { label: auth.tenant?.name ?? 'Empresa', home: dashboard(), items };
}

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const nav = navigation(auth);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={nav.home} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={nav.items} label={nav.label} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
