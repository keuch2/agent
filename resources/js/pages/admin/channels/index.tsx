import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { channelStatusVariant } from '@/components/whatsapp-channel-form';
import type { WhatsAppChannel } from '@/components/whatsapp-channel-form';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/channels';
import { edit } from '@/routes/admin/channels/whatsapp';

type Row = {
    id: number;
    name: string;
    status: WhatsAppChannel['status'];
    status_label: string;
    display_phone_number: string | null;
    last_error: string | null;
    verified_at: string | null;
};

export default function ChannelsIndex({
    tenants,
    webhook,
}: {
    tenants: Row[];
    webhook: { url: string; verify_token: string | null };
}) {
    return (
        <>
            <Head title="Canales" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Canales"
                    description="Estado de la conexión de WhatsApp de cada empresa"
                />

                <section className="grid max-w-3xl gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <p className="text-sm text-muted-foreground sm:col-span-2">
                        Datos para WhatsApp → Configuración de la app de Meta.
                    </p>
                    <div className="grid gap-2">
                        <Label htmlFor="webhook_url">URL de devolución</Label>
                        <Input id="webhook_url" readOnly value={webhook.url} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="verify_token">
                            Token de verificación
                        </Label>
                        <Input
                            id="verify_token"
                            readOnly
                            value={webhook.verify_token ?? 'Sin configurar'}
                        />
                    </div>
                </section>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Empresa
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Número
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Última verificación
                                </th>
                                <th className="px-4 py-3 font-medium">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tenants.map((tenant) => (
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
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                channelStatusVariant[
                                                    tenant.status
                                                ]
                                            }
                                        >
                                            {tenant.status_label}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 tabular-nums">
                                        {tenant.display_phone_number ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {tenant.verified_at
                                            ? new Date(
                                                  tenant.verified_at,
                                              ).toLocaleString('es-PY')
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-destructive">
                                        {tenant.last_error ?? ''}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ChannelsIndex.layout = {
    breadcrumbs: [{ title: 'Canales', href: index() }],
};
