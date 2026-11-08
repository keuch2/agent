import { Link, usePage } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';
import { destroy as stopSupport } from '@/routes/admin/support';
import type { Auth } from '@/types';

export function SupportBanner() {
    const { auth } = usePage<{ auth: Auth }>().props;

    if (!auth.support) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-2 bg-amber-100 px-4 py-2 text-sm text-amber-950 dark:bg-amber-950 dark:text-amber-100">
            <span className="flex items-center gap-2">
                <LifeBuoy className="size-4" />
                Modo soporte en <strong>{auth.support.name}</strong>. Tus
                acciones quedan en la auditoría.
            </span>
            <Link
                href={stopSupport()}
                as="button"
                className="cursor-pointer font-medium underline underline-offset-4"
            >
                Salir del modo soporte
            </Link>
        </div>
    );
}
