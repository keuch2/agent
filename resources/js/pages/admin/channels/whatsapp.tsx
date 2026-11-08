import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { WhatsAppChannelForm } from '@/components/whatsapp-channel-form';
import type { WhatsAppChannel } from '@/components/whatsapp-channel-form';
import { index } from '@/routes/admin/channels';
import { destroy, update, verify } from '@/routes/admin/channels/whatsapp';

export default function AdminWhatsApp({
    tenant,
    channel,
}: {
    tenant: { id: number; name: string };
    channel: WhatsAppChannel | null;
}) {
    return (
        <>
            <Head title={`WhatsApp de ${tenant.name}`} />
            <div className="p-4">
                <Heading
                    title={`WhatsApp de ${tenant.name}`}
                    description="Los cambios quedan registrados en la auditoría"
                />
                <WhatsAppChannelForm
                    channel={channel}
                    update={update.form(tenant.id)}
                    verify={verify(tenant.id)}
                    disconnect={destroy(tenant.id)}
                />
            </div>
        </>
    );
}

AdminWhatsApp.layout = {
    breadcrumbs: [{ title: 'Canales', href: index() }],
};
