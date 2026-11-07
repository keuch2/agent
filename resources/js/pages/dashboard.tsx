import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

export default function Dashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Inicio" />
            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={`Hola, ${auth.user.name.split(' ')[0]}`}
                    description={auth.tenant?.name}
                />
                <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                    Acá vas a ver las conversaciones del mes, el uso del plan y
                    los temas más consultados cuando el agente esté activo.
                </p>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
};
