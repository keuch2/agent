import { Form, Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { NativeSelect } from '@/components/native-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, test, update } from '@/routes/admin/settings/mail';

type MailSettings = {
    is_enabled: boolean;
    host: string;
    port: number;
    encryption: 'tls' | 'ssl' | 'none';
    username: string | null;
    from_address: string;
    from_name: string;
};

const encryptions = [
    { value: 'tls', label: 'STARTTLS (puerto 587)' },
    { value: 'ssl', label: 'SSL (puerto 465)' },
    { value: 'none', label: 'Sin cifrado' },
];

export default function MailSettingsPage({
    settings,
    has_password,
}: {
    settings: MailSettings | null;
    has_password: boolean;
}) {
    return (
        <>
            <Head title="Correo" />
            <div className="max-w-2xl p-4">
                <Heading
                    title="Correo"
                    description="Cuenta SMTP con la que salen las invitaciones y alertas"
                />

                <Form
                    {...update.form()}
                    className="space-y-6"
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['password']}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="host">Servidor</Label>
                                    <Input
                                        id="host"
                                        name="host"
                                        defaultValue={settings?.host}
                                        placeholder="smtp.ejemplo.com"
                                        required
                                    />
                                    <InputError message={errors.host} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="port">Puerto</Label>
                                    <Input
                                        id="port"
                                        name="port"
                                        type="number"
                                        min="1"
                                        max="65535"
                                        defaultValue={settings?.port ?? 587}
                                        required
                                    />
                                    <InputError message={errors.port} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="encryption">Cifrado</Label>
                                    <NativeSelect
                                        id="encryption"
                                        name="encryption"
                                        options={encryptions}
                                        defaultValue={
                                            settings?.encryption ?? 'tls'
                                        }
                                    />
                                    <InputError message={errors.encryption} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="username">Usuario</Label>
                                    <Input
                                        id="username"
                                        name="username"
                                        autoComplete="off"
                                        defaultValue={settings?.username ?? ''}
                                    />
                                    <InputError message={errors.username} />
                                </div>
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="password">Contraseña</Label>
                                    <Input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autoComplete="new-password"
                                        placeholder={
                                            has_password
                                                ? 'Guardada. Dejala vacía para conservarla'
                                                : undefined
                                        }
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="from_address">
                                        Correo remitente
                                    </Label>
                                    <Input
                                        id="from_address"
                                        name="from_address"
                                        type="email"
                                        defaultValue={settings?.from_address}
                                        required
                                    />
                                    <InputError message={errors.from_address} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="from_name">
                                        Nombre remitente
                                    </Label>
                                    <Input
                                        id="from_name"
                                        name="from_name"
                                        defaultValue={
                                            settings?.from_name ??
                                            'Agente IA de ATC'
                                        }
                                        required
                                    />
                                    <InputError message={errors.from_name} />
                                </div>
                            </div>

                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="hidden"
                                    name="is_enabled"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="is_enabled"
                                    value="1"
                                    defaultChecked={
                                        settings?.is_enabled ?? false
                                    }
                                    className="size-4 accent-primary"
                                />
                                Enviar los correos con esta cuenta
                            </label>

                            <div className="flex flex-wrap gap-3">
                                <Button disabled={processing}>Guardar</Button>
                                {settings?.is_enabled && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        asChild
                                    >
                                        <Link
                                            href={test()}
                                            as="button"
                                            preserveScroll
                                        >
                                            Enviarme un correo de prueba
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

MailSettingsPage.layout = {
    breadcrumbs: [{ title: 'Correo', href: edit() }],
};
