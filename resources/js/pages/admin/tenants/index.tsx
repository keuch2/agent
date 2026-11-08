import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit as whatsappEdit } from '@/routes/admin/channels/whatsapp';
import { store as startSupport } from '@/routes/admin/support';
import { create, edit, index } from '@/routes/admin/tenants';

type TenantRow = {
    id: number;
    name: string;
    ruc: string | null;
    status: 'onboarding' | 'active' | 'suspended';
    status_label: string;
    plan: string;
    users_count: number;
    created_at: string;
};

type Paginated<T> = {
    data: T[];
    prev_page_url: string | null;
    next_page_url: string | null;
    from: number | null;
    to: number | null;
    total: number;
};

const statusVariant = {
    onboarding: 'secondary',
    active: 'default',
    suspended: 'destructive',
} as const;

export default function TenantsIndex({
    tenants,
}: {
    tenants: Paginated<TenantRow>;
}) {
    return (
        <>
            <Head title="Empresas" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Empresas"
                        description="Clientes activos y en activación del Agente IA de ATC"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Nueva empresa
                        </Link>
                    </Button>
                </div>

                {tenants.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        Todavía no hay empresas. Creá la primera para empezar su
                        activación.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Empresa
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        RUC
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Plan
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Estado
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Usuarios
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Alta
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {tenants.data.map((tenant) => (
                                    <tr
                                        key={tenant.id}
                                        className="border-t hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            <Link
                                                href={edit(tenant.id)}
                                                className="hover:underline"
                                            >
                                                {tenant.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 tabular-nums">
                                            {tenant.ruc ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {tenant.plan}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    statusVariant[tenant.status]
                                                }
                                            >
                                                {tenant.status_label}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {tenant.users_count}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {new Date(
                                                tenant.created_at,
                                            ).toLocaleDateString('es-PY')}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={whatsappEdit(
                                                            tenant.id,
                                                        )}
                                                    >
                                                        WhatsApp
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={startSupport(
                                                            tenant.id,
                                                        )}
                                                        as="button"
                                                    >
                                                        Modo soporte
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {(tenants.prev_page_url || tenants.next_page_url) && (
                    <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <span>
                            {tenants.from}–{tenants.to} de {tenants.total}
                        </span>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!tenants.prev_page_url}
                                asChild={!!tenants.prev_page_url}
                            >
                                {tenants.prev_page_url ? (
                                    <Link href={tenants.prev_page_url}>
                                        Anterior
                                    </Link>
                                ) : (
                                    <span>Anterior</span>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!tenants.next_page_url}
                                asChild={!!tenants.next_page_url}
                            >
                                {tenants.next_page_url ? (
                                    <Link href={tenants.next_page_url}>
                                        Siguiente
                                    </Link>
                                ) : (
                                    <span>Siguiente</span>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

TenantsIndex.layout = {
    breadcrumbs: [{ title: 'Empresas', href: index() }],
};
