import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { WhatsAppChannelForm } from '@/components/whatsapp-channel-form';
import type { WhatsAppChannel } from '@/components/whatsapp-channel-form';
import { destroy, edit, update, verify } from '@/routes/channels/whatsapp';

export default function WhatsApp({
    channel,
}: {
    channel: WhatsAppChannel | null;
}) {
    return (
        <>
            <Head title="WhatsApp" />
            <div className="p-4">
                <Heading
                    title="WhatsApp"
                    description="Vinculá la cuenta de WhatsApp de tu empresa"
                />
                <WhatsAppChannelForm
                    channel={channel}
                    update={update.form()}
                    verify={verify()}
                    disconnect={destroy()}
                />
            </div>
        </>
    );
}

WhatsApp.layout = {
    breadcrumbs: [{ title: 'WhatsApp', href: edit() }],
};
